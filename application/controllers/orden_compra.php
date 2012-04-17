<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include ('DTOS/Tipos_Tarjetas.php');
include('util/Pago_Express.php');

class Orden_Compra extends CI_Controller {
	var $title = 'Orden de Compra';
	var $subtitle = 'Orden de Compra';
	var $registro_errores = array();				//validación para los errores
	
	private $id_cliente;
	 
	function __construct()
    {
        // Call the Model constructor
        parent::__construct();
		
		//si no hay sesión
		//manda al usuario a la... pagina de login
		$this->redirect_cliente_invalido('id_cliente', '/index.php/login');
		
		//bandera de redirección
		$this->session->set_userdata("redirect_to_order", "orden_compra");
		
		//cargar el modelo en el constructor
		$this->load->model('forma_pago_model', 'tarjeta_modelo', true);
		$this->load->model('direccion_envio_model', 'envio_modelo', true);
		$this->load->model('direccion_facturacion_model', 'facturacion_modelo', true);
		
		//si la sesión se acaba de crear, toma el valor inicializar el id del cliente de la session creada en el login/registro
		$this->id_cliente = $this->session->userdata('id_cliente');

    }

	public function index() {		
		if ($_POST) {
			if (array_key_exists('razon_social_seleccionada', $_POST)){
				$this->session->set_userdata('razon_social', $_POST['razon_social_seleccionada']);
				$this->session->set_userdata('requiere_factura', 'si');						
			}						
		}	
		else if($this->session->userdata('id_rs')) {
			$id_rs=$this->session->userdata('id_rs');
			$this->session->set_userdata('razon_social', $id_rs);		
			$this->session->set_userdata('requiere_factura', 'si');					 		
		}
		
		if ($_POST) {
			if (array_key_exists('direccion_selecionada', $_POST))  {
				echo $cte=$this->id_cliente;
				echo "este".$rs=$this->session->userdata('id_rs');				
				$this->session->set_userdata('razon_social', $rs);				
				$this->session->set_userdata('direccion_f', $_POST['direccion_selecionada']);
				echo $ds = $this->session->userdata('direccion_f');
				$rbr = $this->facturacion_modelo->busca_relacion($cte, $rs, $ds);
				if ($rbr->num_rows() == 0){					
					$this->load->helper('date');
					$fecha = mdate('%Y/%m/%d',time());
					$data_dir = array(
                   		'id_clienteIn'  => $cte,
                   		'id_consecutivoSi' => $ds,
                   		'id_razonSocialIn' => $rs,
                   		'fecha_registroDt' => $fecha                    				                    		
               		);																										
					$this->facturacion_modelo->insertar_rs_direccion($data_dir);		
					$this->session->set_userdata('requiere_factura', 'si');	
				}																			
			}	
			else {
			$id_cliente = $this->id_cliente;
			$rs = $this->session->userdata('razon_social');
			$rdf = $this->facturacion_modelo->obtiene_rs_dir($id_cliente, $rs);		
				foreach ($rdf->result_array() as $dire) {					
					$this->session->set_userdata('direccion_f',$dire['id_consecutivoSi']);
				}			
			}								
		} else if($this->session->userdata('id_dir')){
			$id_dir=$this->session->userdata('id_dir');
			$this->session->set_userdata('direccion_f',$id_dir);								
		}
		
		$this->resumen();
	}
	
	/**
	 * Recupera y despliega la información de la orden de compra en curso
	 */
	public function resumen($msg = '', $redirect = TRUE) 
	{
		$data['title'] = $this->title;
		$data['subtitle'] = $this->subtitle;
		$data['mensaje'] = $msg;
		$data['redirect'] = $redirect;
		
		//Validación del lado del cliente
		$script_file = "<script type='text/javascript' src='". base_url() ."js/orden_compra.js'> </script>";
		$data['script'] = $script_file;
		/*
		echo "<pre>";
		print_r($pe=$this->session->userdata('pago_express'));
		echo "</pre>".$pe->get_destino();
		*/
		/*Recuperar la info gral. de la orden*/
		$id_cliente = $this->id_cliente;

		//Tarjeta
		$tarjeta = $this->session->userdata('tarjeta');
		//si está en session la información
		if (!empty($tarjeta)) {
			//no se guarda en la BD
			if (is_array($this->session->userdata('tarjeta'))) {
				$tarjeta = (object)$tarjeta;
				$detalle_tarjeta = (object)$tarjeta->tc;
				
				//echo var_dump($detalle_tarjeta);
				
				$data['tc'] = $detalle_tarjeta;
				
				if ($detalle_tarjeta->id_tipo_tarjetaSi == 1) { //es AMERICAN EXPRESS
					$data['amex'] = (object)$tarjeta->amex;
					//en este caso se consultará la info del WS
				}
				//echo var_dump($data);
				//exit();
			} else if (is_integer((int)$this->session->userdata('tarjeta'))) {
				
				$consecutivo = $this->session->userdata('tarjeta');
				
				$detalle_tarjeta = $this->tarjeta_modelo->detalle_tarjeta($consecutivo, $id_cliente);
				$data['tc'] = $detalle_tarjeta;	//trae la tc
			
				if ($detalle_tarjeta->id_tipo_tarjetaSi == 1) { //es AMERICAN EXPRESS
					$data['amex'] = $this->detalle_tarjeta_CCTC($id_cliente, $consecutivo);
					//en este caso se consultará la info del WS
				}
			} 
			//Considerar el Depósito Bancario como forma de pago
		}
		
		//dir_envío
		$dir_envio = $this->session->userdata('dir_envio');
		if (!empty($dir_envio)) {
			if (is_array($dir_envio)) {
				//por si no se guarda en la BD
				$data['dir_envio'] = (object)$dir_envio;
			} else if (is_integer((int)$dir_envio)){
				//recupera info de la BD
				$consecutivo = (int)$dir_envio;
				$detalle_envio = $this->envio_modelo->detalle_direccion($consecutivo, $id_cliente);
				$data['dir_envio'] = $detalle_envio;	
			}
		}
		
		//rs_facturación
		$consecutivors = $this->session->userdata('razon_social');
		if (isset($consecutivors)) {
			$detalle_facturacion = $this->facturacion_modelo->obtener_rs($consecutivors);
			$data['dir_facturacion']=$detalle_facturacion;		
		}		
		
		//direccion facturación
		$consecutivo_dir = $this->session->userdata('direccion_f');
		if (isset($consecutivo_dir)) {			
			$detalle_direccion = $this->facturacion_modelo->obtener_direccion($id_cliente, $consecutivo_dir);
			$data['direccion_f']=$detalle_direccion;			
		}
		
		//Si acaso hay errores
		if($_POST && $this->registro_errores) {
			$data['reg_errores'] = $this->registro_errores;
		}		
		
		//cargar vista	
		$this->cargar_vista('', 'orden_compra', $data);
	}

	/**
	 * Realiza el pago a través de CCTC
	 */
	public function checkout() {
		/*Realizar el pago en CCTC*/
		
		if ($_POST) {
			$orden_info = array();		
			$orden_info = $this->get_datos_orden();
						
			if (empty($this->registro_errores)) {
				
				//echo "El pago se realizará aquí. CVV: ".$_POST['txt_codigo'];
				
				/*Recuperar la info gral. de la orden*/
				$id_cliente 	= $this->id_cliente;
				$consecutivo 	= $this->session->userdata('tarjeta');
				$id_promocionIn = 1;
				$digito 		= $_POST['txt_codigo'];
				
				// Informaciòn de la Orden //
				$informacion_orden = new InformacionOrden(
					$id_cliente,
					$consecutivo,
					$id_promocionIn,
					$digito
				);
				
				$cliente = new SoapClient("https://cctc.gee.com.mx/ServicioWebCCTC/ws_cms_cctc.asmx?WSDL");
				//$cliente = new SoapClient("http://localhost:11622/ServicioWebPago/ws_cms_cctc.asmx?WSDL");
				
				// Si la información esta en la Session //
				if (is_array($this->session->userdata('tarjeta'))) {
					$detalle_tarjeta = $this->session->userdata('tarjeta');
					$tc = $detalle_tarjeta['tc'];
					$tc = (array)$tc;
					
					echo var_dump($tc);
					
					$tc_soap = new Tc(
						$tc['id_clienteIn'],
						$tc['id_TCSi'],
						$tc['id_tipo_tarjetaSi'],
						$tc['nombre_titularVc'],
						$tc['apellidoP_titularVc'],
						$tc['apellidoM_titularVc'],
						$tc['terminacion_tarjetaVc'],
						$tc['mes_expiracionVc'],
						$tc['anio_expiracionVc']
					);
					
					$amex_soap = NULL;
					if ($detalle_tarjeta['tc']['id_tipo_tarjetaSi'] == 1) { //es AMERICAN EXPRESS
						$amex = $detalle_tarjeta['amex'];
						if (isset($amex)) {
							$amex_soap = new Amex(
								$amex['id_clienteIn'],
								$amex['id_TCSi'],
								$amex['nombre_titularVc'],
								$amex['apellidoP_titularVc'],
								$amex['apellidoM_titularVc'],
								$amex['pais'],
								$amex['codigo_postal'],
								$amex['calle'],
								$amex['ciudad'],
								$amex['estado'],
								$amex['mail'],
								$amex['telefono']
							);
						}
					}
		
					// Intentamos el Pago con pasando los objetos a CCTC //
					try {  
						$parameter = array(	'informacion_tarjeta' => $tc_soap, 'informacion_amex' => $amex_soap, 'informacion_orden' => $informacion_orden);
						$obj_result = $cliente->PagarTC($parameter);
						$simple_result = $obj_result->PagarTCResult;
		
						var_dump($simple_result);
						//return $simple_result;
					} catch (SoapFault $exception) { 
						echo $exception;  
						echo '<br/>error: <br/>'.$exception->getMessage();
						return NULL;
					}
					
				} else { // La informacion esta en la Base de Datos Local //
					echo "La informacion esta en la Base de Datos Local";
					$detalle_tarjeta = $this->tarjeta_modelo->detalle_tarjeta($consecutivo, $id_cliente);
					$tc = $detalle_tarjeta;	//trae la tc
					// Intentamos el Pago con los Id's en  CCTC //
					try {  
						$parameter = array('informacion_orden' => $informacion_orden);
						$obj_result = $cliente->PagarTcUsandoId($parameter);
						$simple_result = $obj_result->PagarTcUsandoIdResult;
					
						var_dump($simple_result);
						//return $simple_result;
					} catch (SoapFault $exception) {
						//errores en desarrollo
						echo $exception;  
						echo '<br/>error: <br/>'.$exception->getMessage();
						return NULL;
					}
				}
			} else {
				//redirect('orden_compra', 'refresh');
				$this->resumen("El formato del código es incorrecto", TRUE);
			}
		} else { //si llega sin una petición
			redirect('orden_compra', 'refresh');
		}
	}

	/**
	 * Obtiene el detalle de la tarjeta Amex desde CCTC
	 */
	private function detalle_tarjeta_CCTC($id_cliente=0, $consecutivo=0)	//siempre será la información de AMEX
	{
		//Traer la info de amex
		try {  
			$cliente = new SoapClient("https://cctc.gee.com.mx/ServicioWebCCTC/ws_cms_cctc.asmx?WSDL");
				
			$parameter = array(	'id_clienteNu' => $id_cliente, 'consecutivo_cmsSi' => $consecutivo);
			
			$obj_result = $cliente->ConsultarAmex($parameter);
			$tarjeta_amex = $obj_result->ConsultarAmexResult;	//regresa un objeto
			
			//print($simple_result);
			
			return $tarjeta_amex;
			
		} catch (SoapFault $exception) {
			echo $exception;  
			echo '<br/>error: <br/>'.$exception->getMessage();
			//exit();
			return false;
		}
	}
	
	/**
	 * Obtiene los datos para solicitar el cobro de la orden de compra
	 */
	private function get_datos_orden() {
		$datos = array();
		
		if (array_key_exists("txt_codigo", $_POST)) {
			if (preg_match('/^[0-9]{3,4}$/', $_POST['txt_codigo'])) { 
				$datos['cvv'] = $_POST['txt_codigo'];
			} else {
				$this->registro_errores['txt_codigo'] = 'Ingresa un código de seguridad válido';
			}
		}
		/*
		echo "cvv ok: " . preg_match('/^[0-9]{3,4}$/', $_POST['txt_codigo']);
		var_dump($datos);
		var_dump($this->registro_errores);
		exit();
		*/
		return $datos;
	}
	
	/**
	 * Carga la vista indicada ubicada en la carpeta/folder y se le pasa la información
	 */
	private function cargar_vista($folder, $page, $data)
	{	
		//Para automatizar un poco el desplieguee
		$this->load->view('templates/header', $data);
		$this->load->view($folder.'/'.$page, $data);
		$this->load->view('templates/footer', $data);
	}
	
	/**
	 * Verifica la sesión del usuario
	 * */
	private function redirect_cliente_invalido($revisar = 'id_cliente', $destino = '/index.php/login', $protocolo = 'http://') {
		if (!$this->session->userdata($revisar)) {
			//$url = $protocolo . BASE_URL . $destination; // Define the URL.
			$url = $this->config->item('base_url') . $destino; // Define the URL.
			header("Location: $url");
			exit(); // Quit the script.
		}
	}

}

/* End of file orden_compra.php */
/* Location: ./application/controllers/orden_compra.php */