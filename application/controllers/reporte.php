<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once 'api.php';

class Reporte extends CI_Controller {
	
	var $title = 'Reporte de Usuarios'; 		// Capitalize the first letter
	var $subtitle = 'Reporte de Usuarios'; 	// Capitalize the first letter
	
	public static $FORMA_PAGO = array(
		1 =>	"Prosa", 
		2 =>	"American Express", 
		3 =>	"Deposito Bancario",
		4 =>	"Otro"
	);
		
	function __construct(){
        parent::__construct();						
		$this->load->model('reporte_model', 'reporte_model', true);				
		$this->load->helper('date');
		/*
		echo "<pre>";
			print_r($_POST);				
		echo "</pre>";
		exit;
		 */
		if(array_key_exists('user', $this->session->all_userdata()) || array_key_exists('pass', $this->session->all_userdata()) || array_key_exists('user', $_POST) || array_key_exists('pass', $_POST)){
			if(($this->session->userdata('user')=='aespinosa') || ($_POST['user']=='aespinosa') || ($_POST['user']=='mercadotecnia')){
				if(($this->session->userdata('pass')=='Aesp1n0_20120618') || ($_POST['pass']=='Aesp1n0_20120618') || ($_POST['pass']=='m3rc4d0t3cn14')){
					$this->session->set_userdata('user', 'aespinosa');
					$this->session->set_userdata('pass', 'Aesp1n0_20120618');									
				}	
				else{
					redirect('mensaje/'.md5(5));
				}
			}				
			else{
					redirect('mensaje/'.md5(5));
			}
		}
		else{
			redirect('mensaje/'.md5(5));
		}						
    }
	
	public function index(){
		$this->usuarios();		
								
	}		
	
	public function usuarios(){		
		$data['title']=$this->title;	
		$data['error']="";
			
		if(!empty($_POST['fecha_inicio'])){
			$fecha_inicio=$this->input->post('fecha_inicio');
			$fecha_fin=$this->input->post('fecha_fin');
		}
		else if($this->session->userdata('fecha_inicio')){
			$fecha_inicio = $this->session->userdata('fecha_inicio');
			$fecha_fin = $this->session->userdata('fecha_fin');
		}
		else{			
			$fecha_inicio= mdate('%Y/%m/%d',time());
			$fecha_fin=mdate('%Y/%m/%d',time());
		}
		$data['fecha_inicio']=$fecha_inicio;
		$data['fecha_fin']=$fecha_fin;
		$this->session->set_userdata('fecha_inicio', $fecha_inicio);
		$this->session->set_userdata('fecha_fin', $fecha_fin);														
			
		if($this->is_date($fecha_inicio) && $this->is_date($fecha_fin)){				
			$usuarios=$this->reporte_model->obtener_usuarios_fecha($fecha_inicio, $fecha_fin);		
			$data['usuarios']=$usuarios;		
			$this->load->view('templates/header',$data);	
			$this->load->view('reportes/formulario_fecha',$data);		
			$this->load->view('reportes/reporte_usuarios',$data);		
		}
		else{
			$data['error']="ingrese un intervalo valido con el formato (aaaa/mm/dd) para fecha de inicio y fecha fin";
			$this->load->view('templates/header',$data);
			$this->load->view('reportes/formulario_fecha',$data);											
		}							
	}	
	
	public function compras($export = ""){
		
		$data['error']='';	
		$data['title']=$this->title;		
		if(!empty($_POST['fecha_inicio'])){
			$fecha_inicio=$this->input->post('fecha_inicio');
			$fecha_fin=$this->input->post('fecha_fin');
		}
		else if($this->session->userdata('fecha_inicio')){
			$fecha_inicio = $this->session->userdata('fecha_inicio');
			$fecha_fin = $this->session->userdata('fecha_fin');
		}
		else{			
			$fecha_inicio= mdate('%Y/%m/%d',time());
			$fecha_fin=mdate('%Y/%m/%d',time());
		}
		$data['fecha_inicio']=$fecha_inicio;
		$data['fecha_fin']=$fecha_fin;
		$this->session->set_userdata('fecha_inicio', $fecha_inicio);
		$this->session->set_userdata('fecha_fin', $fecha_fin);
		
		if($this->is_date($fecha_inicio) && $this->is_date($fecha_fin)){
			$compras= $this->reporte_model->obtener_compras_fecha($fecha_inicio, $fecha_fin);
			$data['compras']=array();	
					
			foreach($compras->result_array() as $i => $compra){
						
				$data['compras'][$i]['compra'] = $compra;	
				
				//guarda los datos del cliente												
				$cliente = $this->reporte_model->obtener_cliente($compra['id_clienteIn']);												
				$data['compras'][$i]['cliente'] = $cliente->row();
				
				//se obtiene la direccion de envio si es que existe			
				$dir_envio = $this->reporte_model->obtener_dir_envio($compra['id_compraIn'], $compra['id_clienteIn']);
				if($dir_envio->num_rows() > 0){
						$data['compras'][$i]['dir_envio'] = $dir_envio->row();	
				}
				else{
					$data['compras'][$i]['dir_envio']= "No requiere";
				}	
				
				//se obtiene el medio de pago
				$forma_pago = $this->reporte_model->obtener_medio_pago($compra['id_compraIn'], $compra['id_clienteIn']);
				$data['compras'][$i]['medio_pago'] = self::$FORMA_PAGO[($forma_pago->row()->id_tipoPagoSi)];	
				$data['compras'][$i]['fecha_compra'] = 	$forma_pago->row()->fecha_registroTs;
					
				// se obtiene la promocion adquirida			
				$id_promo = $this->reporte_model->obtener_promo_compra($compra['id_compraIn'], $compra['id_clienteIn']);
				
				//se obtiene la suma de los costos de los articulos para obtener el monto a pagado
				$articulos = $this->reporte_model->obtener_articulos($id_promo);			 
				$monto = 0;			
				foreach($articulos->result_array() as $articulo){
					$monto+= $articulo['tarifaDc'];
				}
				$data['compras'][$i]['monto'] = $monto;	
				
				//se obtiene la direccion de facturacion y Razon Social
				$facturacion = $this->reporte_model->obtener_facturacion($compra['id_compraIn'], $compra['id_clienteIn']);
				if($facturacion->num_rows() > 0){								
					$consecutivo = $facturacion->row()->id_consecutivoSi;
					$id_rs = $facturacion->row()->id_razonSocialIn;
					
					$dir_facturacion = $this->reporte_model->obtener_dir_facturacion($consecutivo, $compra['id_clienteIn']);				
					$data['compras'][$i]['dir_facturacion']  =  $dir_facturacion->row()->address1." ".
																$dir_facturacion->row()->address2." ".
																$dir_facturacion->row()->address4."<br />".
																$dir_facturacion->row()->zip."<br />".
																$dir_facturacion->row()->address3."<br />".
																$dir_facturacion->row()->city."<br />".
																$dir_facturacion->row()->state;
					
					$rs = $this->reporte_model->obtener_razon_social($id_rs);
					$data['compras'][$i]['razon_social'] = $rs->row()->tax_id_number."<br />".$rs->row()->company;											
					
				}						
				else{
					$data['compras'][$i]['dir_facturacion'] = "No requiere";
					$data['compras'][$i]['razon_social'] = "No requiere";
				}	
				
				//se obtiene el codigo de autorizacion si es que existe
				$ca = $this->reporte_model->obtener_codigo_autorizacion($compra['id_compraIn'], $compra['id_clienteIn']);
				if($ca->num_rows() > 0 ){									
					if($ca->row()->codigo_autorizacionVc > 0){
						$data['compras'][$i]['codigo_autorizacion'] = $ca->row()->codigo_autorizacionVc;
					}
					else{
						$data['compras'][$i]['codigo_autorizacion'] = $ca->row()->codigo_autorizacionVc ."<br />". $ca->row()->respuesta_bancoVc ;
					}
				}
				else{
					$data['compras'][$i]['codigo_autorizacion'] = "No encontrado";	
				}
				
				$dthink = $this->reporte_model->obtener_detalle_think($compra['id_compraIn'], $compra['id_clienteIn']);
				if($dthink){
					$data['compras'][$i]['think'] = $dthink->row();
				}	
				else{
					$data['compras'][$i]['think'] = array();	
				}
						 						
												
			}
			/*
			echo "<pre>";
					print_r($data);
			echo "</pre>";
			 * */
			if($export == "true" ){
								
				header("Content-Type: application/ms-excel");
				header("Expires: 0");
				header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
				header('Content-type: text/html; charset=utf-8');
				header("content-disposition: attachment;filename=Reporte_compras_".date("Y-m-d_H-i").".xls");			
				$this->load->view('reportes/reporte_compras_excel',$data);
			}	
			else{							
				$this->load->view('templates/header',$data);
				$this->load->view('reportes/formulario_fecha',$data);	
				$this->load->view('reportes/reporte_compras',$data);
			}	
		}
		else{
			$data['error']="ingrese un intervalo valido con el formato (aaaa/mm/dd) para fecha de inicio y fecha fin";
			$this->load->view('templates/header',$data);
			$this->load->view('reportes/formulario_fecha',$data);	
		}		
				
	}
	
	public function compras_cliente(){		
		$data['id_cliente'] = $id_cliente = $_POST['id_cliente'];
		//$data['id_cliente'] = $id_cliente = 28;		 
		$compras_cliente = $this->reporte_model->obtener_compras_cliente($id_cliente);
		if($compras_cliente->num_rows()>0){
			$data['compras'] = array();
			$todas_compras = $compras_cliente->result_array();			
			foreach($todas_compras as $ind => $compra){
				$id_compra = $compra['id_compraIn'];				
				$data['compras'][$ind]['compra'] = $compra;
				
				//se obtiene el medio y la fecha de pago
				$forma_pago = $this->reporte_model->obtener_medio_pago($id_compra, $id_cliente);
				if($forma_pago -> num_rows > 0){
					$data['compras'][$ind]['medio_pago'] = self::$FORMA_PAGO[($forma_pago->row()->id_tipoPagoSi)];	
					$data['compras'][$ind]['fecha_compra'] = 	$forma_pago->row()->fecha_registroTs;				
				}
				else{
					$data['compras'][$ind]['medio_pago'] = "no existe";
					$data['compras'][$ind]['fecha_pago'] = "no existe";
					
				}
				
				//se obtiene el id de promocion de la compra
				$id_promo = $this->reporte_model->obtener_promo_compra($id_compra, $id_cliente);
				
				// se obtiene el detalle de la promocion
				$promocion = $this->reporte_model->obtener_detalle_promo($id_promo);	
				if($promocion->num_rows()>0){
					$data['compras'][$ind]['promocion'] = $promocion->row();
				}
				//se obtiene el total de articulos en la promocion y el total que se pago por ellos 
				$articulos_res = $this->reporte_model->obtener_articulos($id_promo);
				$articulos = $articulos_res->result_array();							 
				$monto = 0;

				// Se obtienen los articulos de cada promocion y el total pagado por ellos 							
				foreach( $articulos as $i => $articulo){
					if($articulo['issue_id']){
						$issue = $this->reporte_model->obtener_issue($articulo['issue_id']);						
						$articulos[$i]['tipo_productoVc']= $issue->row()->descripcionVc;
					}
					else{
						$articulos[$i]['tipo_productoVc'] = $articulo['tipo_productoVc'];
					}
					$monto+= $articulo['tarifaDc'];
				}
				$data['compras'][$ind]['articulos'] = $articulos;
				/*
				echo "<pre>";
					print_r($data);
				echo "</pre>";
				*/					
				
				$data['compras'][$ind]['monto'] = $monto;
												
			}
		}
		else{
			$data['compras'] = NULL;
		}
		
		$this->load->view('reportes/reporte_compras_usuario', $data);		
	}
	
	public function detalle_compra($id_compra = "", $id_cliente = ""){
		$data['compra']['id_compra'] = $id_compra; 
		$data['compra']['direccion_amex'] = NULL;
		$data['compra']['codigo_autorizacion'] = NULL;
		
		//se obtiene el medio y la fecha de pago
		$forma_pago = $this->reporte_model->obtener_medio_pago($id_compra, $id_cliente);
		
		if($forma_pago -> num_rows > 0){
			//si el pago es con prosa se obtiene el detalle de la tarjeta
			if(($forma_pago->row()->id_tipoPagoSi == 1) || ($forma_pago->row()->id_tipoPagoSi == 2)){				
				$tc = $this->reporte_model->obtener_tc($id_cliente, $forma_pago->row()->id_tipoPagoSi);				
				$data['compra']['medio_pago'] = $tc->row()->descripcionVc." terminación ".$tc->row()->terminacion_tarjetaVc;				
					
				//se obtiene el codigo de autorizacion si es que existe
				$ca = $this->reporte_model->obtener_codigo_autorizacion($id_compra, $id_cliente);
				if($ca->num_rows() > 0 ){									
					if($ca->row()->codigo_autorizacionVc > 0){
						$data['compra']['codigo_autorizacion'] = "<span class='info-negro'>codigo de autorización:</span> ".$ca->row()->codigo_autorizacionVc;
					}
					else{
						$data['compra']['codigo_autorizacion'] = "<span class='info-negro'>codigo de autorización:</span> ".$ca->row()->codigo_autorizacionVc ."<br />". $ca->row()->respuesta_bancoVc ;
					}
				}	
				else{
					$data['compra']['codigo_autorizacion'] = "<span class='info-negro'>(No se realizo el cobro)</span>";	
				}
					
			}
			else{				
				$data['compra']['medio_pago'] = self::$FORMA_PAGO[($forma_pago->row()->id_tipoPagoSi)];					
			}
			
			//si el pago es con amex se obtiene el detalle de la tarjeta y la direccion de amex
			if($forma_pago->row()->id_tipoPagoSi == 2){				
				$data['compra']['direccion_amex'] = "direccion ammex";	
			}
										
			$data['compra']['fecha_compra'] = 	$forma_pago->row()->fecha_registroTs;				
		}
		else{
			$data['compra']['medio_pago'] = NULL;
			$data['compra']['fecha_pago'] = NULL;				
		}
				
		//se obtiene la direccion de envio si es que existe			
		$dir_envio = $this->reporte_model->obtener_dir_envio($id_compra, $id_cliente);
		if($dir_envio->num_rows() > 0){
				$data['compra']['dir_envio'] = 	$dir_envio->row()->address1." ".
												$dir_envio->row()->address2." ".
												$dir_envio->row()->address4."<br />".
												$dir_envio->row()->zip."<br />".
														$dir_envio->row()->address3."<br />".
														$dir_envio->row()->city."<br />".
														$dir_envio->row()->state;	
		}
		else{
			$data['compra']['dir_envio']= "No requiere";
		}
		
		//se obtiene la direccion de facturacion y Razon Social
		$facturacion = $this->reporte_model->obtener_facturacion($id_compra, $id_cliente);
		if($facturacion->num_rows() > 0){								
			$consecutivo = $facturacion->row()->id_consecutivoSi;
			$id_rs = $facturacion->row()->id_razonSocialIn;
			
			$dir_facturacion = $this->reporte_model->obtener_dir_facturacion($consecutivo, $id_cliente);				
			$data['compra']['dir_facturacion']  =  $dir_facturacion->row()->address1." ".
														$dir_facturacion->row()->address2." ".
														$dir_facturacion->row()->address4."<br />".
														$dir_facturacion->row()->zip."<br />".
														$dir_facturacion->row()->address3."<br />".
														$dir_facturacion->row()->city."<br />".
														$dir_facturacion->row()->state;
			
			$rs = $this->reporte_model->obtener_razon_social($id_rs);
			$data['compra']['razon_social'] = $rs->row()->company."<br />".$rs->row()->tax_id_number;											
			
		}						
		else{
			$data['compra']['dir_facturacion'] = NULL;
			$data['compra']['razon_social'] = NULL;
		}
		
		//se obtiene el id de promocion de la compra
		$id_promo = $this->reporte_model->obtener_promo_compra($id_compra, $id_cliente);
		
		// se obtiene el detalle de la promocion
		$promocion = $this->reporte_model->obtener_detalle_promo($id_promo);	
		if($promocion->num_rows()>0){
			$data['compra']['promocion'] = $promocion->row();
		}
					
		
		//se obtiene el total de articulos en la promocion y el total que se pago por ellos 
		$articulos_res = $this->reporte_model->obtener_articulos($id_promo);
		$articulos = $articulos_res->result_array();							 
		$monto = 0;

		// Se obtienen los articulos de cada promocion y el total pagado por ellos 							
		foreach( $articulos as $i => $articulo){
			if($articulo['issue_id']){
				$issue = $this->reporte_model->obtener_issue($articulo['issue_id']);						
				$articulos[$i]['tipo_productoVc']= $issue->row()->descripcionVc;
			}
			else{
				$articulos[$i]['tipo_productoVc'] = $articulo['tipo_productoVc'];
			}
			$monto+= $articulo['tarifaDc'];
		}
		$data['compra']['articulos'] = $articulos;
		$data['compra']['monto'] = $monto;
		
		// Obtiene informacion de tarjeta
		/*				
		echo "<pre>";
			print_r($data);
		echo "</pre>";
		*/											
		$this->load->view('reportes/detalle_compra', $data);
	}
	
	public function is_date($date){
         if (preg_match ("/^([0-9]{4})\/([0-9]{2})\/([0-9]{2})$/", $date, $parts)){
             if(checkdate($parts[2],$parts[3],$parts[1]))
             	return true;
         	 else
            	return false;
      	 }
      	 else
      	     return false;
	}
	
}

/* End of file reporte.php */
/* Location: ./application/controllers/reporte.php */