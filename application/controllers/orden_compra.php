<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include ('dtos/Tipos_Tarjetas.php');
include('util/Pago_Express.php');

class Orden_Compra extends CI_Controller {
	var $title = 'Verifica tu orden';
	var $subtitle = 'Verifica tu orden';
	var $registro_errores = array();				//validación para los errores
	var $pago_express;
	
	private $id_cliente;
	private $id_direccion_envio;
	private $id_direccion_facturacion;
	
	public static $ESTATUS_COMPRA = array(
		"SOLICITUD_CCTC"			=> 1, 
		"RESPUESTA_CCTC"			=> 2, 
		"REGOSTRO_PAGO_ECOMERCE"	=> 3,
		"ENVIO_CORREO"				=> 4
	);
	
	
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
		$this->load->model('orden_compra_model', 'orden_compra_model', true);
		$this->load->model('forma_pago_model', 'forma_pago_model', true);
		$this->load->model('direccion_envio_model', 'direccion_envio_model', true);
		$this->load->model('direccion_facturacion_model', 'direccion_facturacion_model', true);
		
		//El flujo se recupera acá
		$this->pago_express = $this->session->userdata("pago_express");
		echo "destino: " . $this->session->userdata("pago_express")->get_destino();
		var_dump($this->pago_express);
		
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
				$cte = $this->id_cliente;
				$rs = $this->session->userdata('id_rs');
								
				$this->session->set_userdata('razon_social', $rs);				
				$this->session->set_userdata('direccion_f', $_POST['direccion_selecionada']);
				
				$ds = $this->session->userdata('direccion_f');
				$rbr = $this->direccion_facturacion_model->busca_relacion($cte, $rs, $ds);
				
				if ($rbr->num_rows() == 0) {					
					$this->load->helper('date');
					$fecha = mdate('%Y/%m/%d',time());
					$data_dir = array(
                   		'id_clienteIn'  => $cte,
                   		'id_consecutivoSi' => $ds,
                   		'id_razonSocialIn' => $rs,
                   		'fecha_registroDt' => $fecha                    				                    		
               		);																										
					$this->direccion_facturacion_model->insertar_rs_direccion($data_dir);		
					$this->session->set_userdata('requiere_factura', 'si');	
				}																			
			}	
			else {
			$id_cliente = $this->id_cliente;
			$rs = $this->session->userdata('razon_social');
			$rdf = $this->direccion_facturacion_model->obtiene_rs_dir($id_cliente, $rs);		
				foreach ($rdf->result_array() as $dire) {					
					$this->session->set_userdata('direccion_f',$dire['id_consecutivoSi']);
				}			
			}								
		} else if($this->session->userdata('id_dir')){
			$id_dir = $this->session->userdata('id_dir');
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
		
		//gestión de la dirección de envío con el obj. de pago exprés
		$data['requiere_envio'] = $this->pago_express->get_requiere_envio();
		
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
				
				$detalle_tarjeta = $this->forma_pago_model->detalle_tarjeta($consecutivo, $id_cliente);
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
				$detalle_envio = $this->direccion_envio_model->detalle_direccion($consecutivo, $id_cliente);
				$data['dir_envio'] = $detalle_envio;
			}
		}
		
		//rs_facturación
		$consecutivors = $this->session->userdata('razon_social');
		if (isset($consecutivors)) {
			$detalle_facturacion = $this->direccion_facturacion_model->obtener_rs($consecutivors);
			$data['dir_facturacion']=$detalle_facturacion;		
		}		
		
		//direccion facturación
		$consecutivo_dir = $this->session->userdata('direccion_f');
		if (isset($consecutivo_dir)) {			
			$detalle_direccion = $this->direccion_facturacion_model->obtener_direccion($id_cliente, $consecutivo_dir);
			$data['direccion_f']=$detalle_direccion;			
		}
		
		//Si acaso hay errores
		if($_POST && $this->registro_errores) {
			$data['reg_errores'] = $this->registro_errores;
		}		
		
		//echo "direcciones: ". $this->id_direccion_envio. ", " . $this->id_direccion_facturacion;
		//cargar vista	
		$this->cargar_vista('', 'orden_compra', $data);
	}

	/**
	 * Realiza el pago a través de CCTC
	 */
	public function checkout() {
		$data['title'] = "Resultado de la petición de cobro";
		$data['subtitle'] = "Resultado de la petición de cobro";
		
		/*Realizar el pago en CCTC*/
		if ($_POST) {
			$orden_info = array();		
			$orden_info = $this->get_datos_orden();
						
			if (empty($this->registro_errores)) {
				
				//echo "El pago se realizará aquí. CVV: ".$_POST['txt_codigo'];
				
				/*Recuperar la info gral. de la orden*/
				$id_cliente 	= $this->id_cliente;
				$consecutivo 	= $this->session->userdata('tarjeta');
				$id_promocionIn = $this->session->userdata('promocion')->id_promocionIn;
				$digito 		= $_POST['txt_codigo'];
				
				// Informaciòn de la Orden //
				$informacion_orden = new InformacionOrden(
					$id_cliente,
					$consecutivo,
					$id_promocionIn,
					$digito
				);
				
				//echo var_dump($informacion_orden);				
				//exit();
				
				/*Resgistrar TODA la información de la orden*/
				
				
				//echo "<br/>exito del registro de la orden:". $registro_exitoso;
				//exit();
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
						
						//Registro inicial de la compra
						$registro_exitoso = $this->registrar_orden_compra($id_cliente, $id_promocionIn);
						
						$obj_result = $cliente->PagarTC($parameter);
						
						
						
						//Intento de cobro en CCTC
						$simple_result = $obj_result->PagarTCResult;
						
						//Registro de la respuesta de CCTC de la compra
						//cargar Vista
						//var_dump($simple_result);
						
						$data['resultado'] = $simple_result;
						$this->cargar_vista('', 'orden_compra', $data);
						//enviar Correo
						
						//return $simple_result;
					} catch (SoapFault $exception) { 
						echo $exception;  
						echo '<br/>error: <br/>'.$exception->getMessage();
						return NULL;
					}
					
				} else { // La informacion esta en la Base de Datos Local //
					//echo "La informacion esta en la Base de Datos Local";
					
					$detalle_tarjeta = $this->forma_pago_model->detalle_tarjeta($consecutivo, $id_cliente);
					$tc = $detalle_tarjeta;	//trae la tc
					// Intentamos el Pago con los Id's en  CCTC //
					try {  
						$parameter = array('informacion_orden' => $informacion_orden);
						
						//Registro inicial de la compra
						$registro_exitoso = $this->registrar_orden_compra($id_cliente, $id_promocionIn);
						//Intento de cobro en CCTC
						$obj_result = $cliente->PagarTcUsandoId($parameter);
						
						//Registro de la respuesta de CCTC de la compra
						//cargar Vista
						$simple_result = $obj_result->PagarTcUsandoIdResult;
					
						//var_dump($simple_result);
						
						$data['resultado'] = $simple_result;
						$this->cargar_vista('', 'orden_compra', $data);
						//return $simple_result;
					} catch (SoapFault $exception) {
						//errores en desarrollo
						echo $exception;  
						echo '<br/>error: <br/>'.$exception->getMessage();
						return NULL;
					}
				}
			} else {	//If Errores
				//redirect('orden_compra', 'refresh');
				$this->resumen("El formato del código es incorrecto", TRUE);
			}
		} else { //si llega sin una petición
			redirect('orden_compra', 'refresh');
		}
	}
	
	/**
	 * Registrar toda la información de la orden
	 * El tercer parámetro es para indicar el estatus inicial
	 */
	private function registrar_orden_compra($id_cliente, $id_promocion)
	{
		//Registrar eb la tabla de ordenes
		$id_compra = $this->registrar_compra($id_cliente);
		
		$exito = FALSE;
		if ($id_compra) {
			//Registrar el articulo y la orden de compra a la que pertenece.
			
			//$this->db->trans_start();
	
			$registro_articulos = $this->registrar_articulos_compra($id_compra, $id_cliente, $id_promocion);
			if (!$registro_articulos)
			{
				echo "<p>Error en el registro del los articulos</p>";
			}
			
			//Registrar la forma de pago relacionada con una compra
			$registro_pago = $this->registrar_pago_compra($id_compra, $id_cliente);
			if (!$registro_articulos)
			{
				echo "<p>Error en el registro del pago</p>"; 
			}
			
			//registrar el estatus
			$estatus_compra = $this->registrar_estatus_compra($id_compra, $id_cliente, self::$ESTATUS_COMPRA['SOLICITUD_CCTC']);
			if (!$estatus_compra)
			{
				echo "<p>Error en el registro del estatus de la transacción</p>";
			}
			
			//registrar las direcciones con la compra
			$dir_envio = ($this->session->userdata('dir_envio'))? $this->session->userdata('dir_envio') : NULL;
			$dir_facturacion = ($this->session->userdata('dir_facturacion'))? $this->session->userdata('dir_facturacion') : NULL;
			
			/*
			if ($dir_envio || $dir_facturacion) {
				$direcciones_compra = $this->registrar_direcciones_compra($id_compra, $id_cliente, $dir_envio, $dir_facturacion );
				if (!$direcciones_compra)
				{
					echo "<p>Error en el registro delas direcciones de la transacción</p>";
				}
			}
			*/
			
			//	$this->db->trans_complete();
			
			$exito = TRUE;
		
		}
		return $exito;
	}
	
	/**
	 * Redistro de la compra
	 */
	private function registrar_compra($id_cliente)
	{
		try {
			return $this->orden_compra_model->insertar_compra($id_cliente);
		} catch (Exception $ex ) {
			echo "Error en el registro del la compra: " .$ex->getMessage();
			return FALSE;
		}
	}
	
	/**
	 * Redistro de las direcciones de la compra
	 */
	private function registrar_direcciones_compra($id_compra, $id_cliente, $dir_envio, $dir_facturacion)
	{
		try {
			return $this->orden_compra_model->insertar_direcciones_compra($id_cliente, $this->id_direccion_envio, $this->id_direccion_facturacion);
		} catch (Exception $ex ) {
			echo "Error en el registro del la compra: " .$ex->getMessage();
			return FALSE;
		}
	}
	
	/**
	 * Registrar el estatus de la compra
	 */
	private function registrar_estatus_compra($id_compra, $id_cliente, $id_estatusCompra)
	{
		try {
			$info_estatus = array('id_compraIn' => $id_compra, 'id_clienteIn' => $id_cliente, 'id_estatusCompraSi' => $id_estatusCompra);
			return $this->orden_compra_model->insertar_estatus_compra($info_estatus);
		} catch (Exception $ex ) {
			echo "Error en el registro del estatus de la compra: " .$ex->getMessage();
			return FALSE;
		}
	}
	
	/**
	 * Registrar la forma de pago relacionada con una compra
	 * Regresa un bool
	 */
	private function registrar_pago_compra($id_compra, $id_cliente) 
	{
		$id_tc = NULL;	//Sólo aplica para tarjetas
		//procesar el tipo de pago
		if ($tarjeta = $this->session->userdata('tarjeta')) {
			$id_tc = (is_array($tarjeta)) ? $tarjeta['tc']['id_TCSi'] : $tarjeta;
			$tipo_pago = 1;	//MASTERCARD / VISA/AMEX/ 
		} else if ($this->session->userdata('deposito')) {
			$tipo_pago = 4;	//Depósito Bancario
		}
		
		//registrar pago
		try {
			$info_pago = array('id_compraIn' => $id_compra, 'id_clienteIn' => $id_cliente, 'id_tipoPagoSi' => $tipo_pago, 'id_TCSi' => $id_tc);
			return $this->orden_compra_model->insertar_pago_compra($info_pago);
		} catch (Exception $ex ) {
			echo "Error en el registro del pago de la compra: " .$ex->getMessage();
			return FALSE;
		}
	}
		
	/**
	 * Registrar los articulos y la orden de compra a la que pertenecen
	 * Regresa un bool
	 */
	private function registrar_articulos_compra($id_compra, $id_cliente, $id_promocion) 
	{
		$articulos_compra = $this->orden_compra_model->obtener_articulos_promocion($id_promocion);
		//echo "articulos: $id_promocion";
		//var_dump($articulos_compra);
		foreach ($articulos_compra as $articulo) {
			if (!$this->orden_compra_model->insertar_articulo_compra(array(
												'id_articuloIn' => $articulo->id_articulo, 
												'id_compraIn' => $id_compra, 
												'id_clienteIn' => $id_cliente))) {
				return FALSE;
			}
		}
		
		return TRUE;
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