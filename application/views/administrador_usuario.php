<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include('api.php');
class Administrador_usuario extends CI_Controller {
	
	public static $FORMA_PAGO = array(
		1 =>	"Prosa", 
		2 =>	"American Express", 
		3 =>	"Deposito Bancario",
		4 =>	"Otro"
	);
		
	function __construct()
    {
        // Call the Model constructor
        parent::__construct();						

		
		// incluye el modelo de las direcciones de facturacion		
		$this->load->model('direccion_facturacion_model', 'direccion_facturacion_model', true);				
		$this->load->model('direccion_envio_model', 'direccion_envio_model', true);
		$this->load->model('forma_pago_model', 'forma_pago_model', true);
		$this->load->model('login_registro_model', 'login_registro_model', true);
		$this->load->model('reporte_model', 'reporte_model', true);
		
		$this->api = new Api();
    }
	
	public function index()
	{
	}
	
	public function cliente_id($id_cliente = ""){
		
		$cliente = $this->login_registro_model->obtener_cliente_id($id_cliente);
		if($cliente->num_rows() > 0){
			$data['cliente'] = $cliente->row();			
			echo json_encode($data);
		} else{
			echo json_encode($data);
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
				
				$ca = $this->reporte_model->obtener_codigo_autorizacion($id_compra, $id_cliente);
				if($ca->num_rows() > 0){
					$data['compras'][$ind]['respuesta_banco'] = $ca->row()->respuesta_bancoVc;
				}			
				else{
					$data['compras'][$ind]['respuesta_banco'] = "no hay respuesta";
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
	
	public function actualizar_cliente($id_cliente = ""){
		/*
		echo "<pre>";
			print_r($_POST);
		echo "</pre>";	
		exit;	
		*/
		if($_POST){
						
			$cliente_info =	$this->valida_datos_update();
			$cliente_info['id_clienteIn'] = $id_cliente;
				
			if(!empty($this->login_errores)){
					$data['error'] = 1;					
					$data['errores'] = $this->login_errores;					
			} else{				
				if($this->login_registro_model->actualizar_cliente($cliente_info)){
					$data['error'] = 0;							
				} else{
					$data['error'] = 1;
				}				
			}
			
		}
				
		echo json_encode($data);
				
	}
	
	public function valida_datos_update(){
		$datos = array();
		
		if(!empty($_POST['log_data'])){
			$login_data = $this->api->decrypt($_POST['log_data'], $this->api->key);
			$login_data = explode('|',$login_data);
			$mail_registrado = 	$login_data[0]; 												   	
			$pass_registrado = 	$login_data[1];						
		} else {
			$this->login_errores['email'] = '<div class="error2">Información incompleta.</div>';
			$this->login_errores['password'] = '<div class="error2">Información incompleta.</div>';
		}	
				
				
		if (filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)){
			if($mail_registrado == $_POST['email']){
				$datos['email'] = htmlspecialchars(trim($_POST['email']));
			} else{
				$datos['email'] = htmlspecialchars(trim($_POST['email']));
				$datos['password'] = $pass_registrado;
			}
		} else {
			$this->login_errores['email'] = '<div class="error2">Por favor ingresa una dirección de correo válida. Ejemplo: nombre@dominio.mx</div>';		
		}
		
		
		if(array_key_exists('nombre', $_POST)){
			if(preg_match('/^[A-Z \'.-áéíóúÁÉÍÓÚÑñ]{1,30}$/i', $_POST['nombre'])) { 
				$datos['salutation'] = $_POST['nombre'];
			} else{
				$this->login_errores['nombre'] = '<div class="error2">Por favor ingresa tu nombre correctamente</div>';
			}
		} 
		
		if(array_key_exists('apellido_paterno', $_POST)){
			if(preg_match('/^[A-Z \'.-áéíóúÁÉÍÓÚÑñ]{1,30}$/i', $_POST['apellido_paterno'])) { 
				$datos['fname'] = $_POST['apellido_paterno'];
			} else{
				$this->login_errores['apellido_paterno'] = '<div class="error2">Por favor ingresa tu apellido paterno</div>';
			}
		}
		
		if(array_key_exists('apellido_materno', $_POST)){
			if(preg_match('/^[A-Z \'.-áéíóúÁÉÍÓÚÑñ]{1,30}$/i', $_POST['apellido_materno'])) { 
				$datos['lname'] = $_POST['apellido_materno'];
			}
		}
					
		if(!empty($_POST['password_actual'])){
						
			if($pass_registrado == $_POST['password_actual']){
				if(!empty($_POST['nuevo_password'])){
					$datos['password'] = $_POST['nuevo_password'];
				} else{
					$datos['password'] = $_POST['password_actual'];
				}																								
			} else{
				$this->login_errores['password'] = '<div class="error2">Password actual incorrecto</div>';
			}												
		} else{
			
		}	
		
											
		return $datos;				
		
	}
	
	public function listar_razon_social($id_cliente = ""){		
		$data['rs'] = $this->direccion_facturacion_model->listar_razon_social($id_cliente);						
		echo json_encode($data);
	}
	
	public function listar_direccion_envio($id_cliente = ""){
		$data['direccion_envio'] = $this->direccion_envio_model->listar_direcciones($id_cliente)->result_array();							
		echo json_encode($data);
	}
	
	public function listar_tarjetas($id_cliente = ""){
		$data['tarjetas'] = $this->forma_pago_model->listar_tarjetas($id_cliente)->result_array();							
		echo json_encode($data);
	}
	
}

/* End of file administrador_usuario.php */
/* Location: ./application/controllers/administrador_usuario.php */