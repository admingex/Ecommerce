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
			$data['fecha_inicio']=mdate('%Y/%m/%d',time());
			$data['fecha_fin']=mdate('%Y/%m/%d',time());
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
				$rel_dir_envio = $this->reporte_model->obtener_rel_dir_envio($compra['id_compraIn'], $compra['id_clienteIn']);				
				
				if($rel_dir_envio->num_rows() > 0){
						/////obtener_dir_facturacion ocupo esta funcion para obteer la direccion de envio ya que recibe los mismos parametros
						$dir_envio = $this->reporte_model->obtener_dir_facturacion($rel_dir_envio->row()->id_consecutivoSi, $compra['id_clienteIn']);																
						$data['compras'][$i]['dir_envio'] = 	 $dir_envio->row()->address1." ".$dir_envio->row()->address2." ".
																 $dir_envio->row()->address4." ".
																 $dir_envio->row()->zip." ".
																 $dir_envio->row()->address3." ".
																 $dir_envio->row()->city." ".
																 $dir_envio->row()->state;	
				}
				else{
					$data['compras'][$i]['dir_envio']= "No requiere";
				}	
				
				//se obtiene el medio de pago
				$forma_pago = $this->reporte_model->obtener_medio_pago($compra['id_compraIn'], $compra['id_clienteIn']);
				/*
				echo "<pre>";
					print_r($forma_pago);
				echo "</pre>";
				*/
				$data['compras'][$i]['medio_pago']='';
				$data['compras'][$i]['fecha_compra']='';
				if($forma_pago->num_rows()> 0){
					$data['compras'][$i]['medio_pago'] = self::$FORMA_PAGO[($forma_pago->row()->id_tipoPagoSi)];	
					$data['compras'][$i]['fecha_compra'] = 	$forma_pago->row()->fecha_registroTs;
				}
					
				// se obtiene la promocion adquirida			
				$id_promo = $this->reporte_model->obtener_promo_compra($compra['id_compraIn'], $compra['id_clienteIn']);
				/*
				echo "<pre>";
					print_r($id_promo);
				echo "</pre>";
				 */ 
				
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
					$data['compras'][$i]['dir_facturacion']  =  $dir_facturacion->row()->address1." ".$dir_facturacion->row()->address2." ".
																$dir_facturacion->row()->address4." ".
																$dir_facturacion->row()->zip." ".
																$dir_facturacion->row()->address3." ".
																$dir_facturacion->row()->city." ".
																$dir_facturacion->row()->state;
					
					$rs = $this->reporte_model->obtener_razon_social($id_rs);
					$data['compras'][$i]['razon_social'] = $rs->row()->tax_id_number." ".$rs->row()->company;											
					
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
						$data['compras'][$i]['codigo_autorizacion'] = $ca->row()->codigo_autorizacionVc ." ". $ca->row()->respuesta_bancoVc ;
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
								
				header("Content-Type: text/plain");
				header("Expires: 0");
				header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
				header('Content-type: text/html; charset=UTF-8');
				header("content-disposition: attachment;filename=Reporte_compras_".date("Y-m-d_H-i").".txt");			
				$this->load->view('reportes/reporte_compras_excel',$data);
			}	
			else{							
				$this->load->view('templates/header',$data);
				$this->load->view('reportes/formulario_fecha',$data);	
				$this->load->view('reportes/reporte_compras',$data);
			}	
		}
		else{			
			$data['fecha_inicio']=mdate('%Y/%m/%d',time());
			$data['fecha_fin']=mdate('%Y/%m/%d',time());
			
			$data['error']="ingrese un intervalo valido con el formato (aaaa/mm/dd) para fecha de inicio y fecha fin";
			$this->load->view('templates/header',$data);
			$this->load->view('reportes/formulario_fecha',$data);	
		}		
				
	}	

	public function compras_cliente(){
		$data['error']='';	
		$data['title']=$this->title;
				
		
		if($_POST){			
			if(is_numeric($_POST['id_cliente'])){
				$id_cliente = $_POST['id_cliente'];
				$data['id_cliente']= $id_cliente;
				
				$consulta = $this->reporte_model->compras_cliente_id($id_cliente);				
				if($consulta->num_rows()>0){
					$data['consulta_compras'] = $this->reporte_model->compras_cliente_id($id_cliente)->result_array();					
				}
				
								
				$this->load->view('templates/header', $data);
				$this->load->view('reportes/formulario_fecha', $data);
				$this->load->view('reportes/compras_cliente', $data);		
				
			}			
			else{
				$data['error']="ingrese un ID de cliente valido, este solo puede ser numerico";
				$this->load->view('templates/header', $data);
				$this->load->view('reportes/formulario_fecha', $data);							
			}						
		}
		else{
			$this->load->view('templates/header', $data);
			$this->load->view('reportes/formulario_fecha', $data);						
		}			
				
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