<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include ('dtos/Tipos_Tarjetas.php');
include('util/Pago_Express.php');
include('api.php');

class Orden_Compra extends CI_Controller {
	var $title = 'Verifica tu orden';
	var $subtitle = 'Verifica tu orden';
	var $registro_errores = array();				//validación para los errores
	
	const Tipo_AMEX = 1;
	const User_Ecommerce = 0;
	//var $pago_express;
	
	private $id_cliente;
	private $id_direccion_envio;
	private $id_direccion_facturacion;
	
	
	public static $ESTATUS_COMPRA = array(
		"SOLICITUD_CCTC"			=> 1, 
		"RESPUESTA_CCTC"			=> 2, 
		"REGISTRO_PAGO_ECOMMERCE"	=> 3,
		"PAGO_DEPOSITO_BANCARIO" 	=> 4,
		"ENVIO_CORREO"				=> 5
	);
	
	public static $TIPO_PAGO = array(
		"Prosa"				=> 1, 
		"American_Express"	=> 2, 
		"Deposito_Bancario"	=> 3,
		"Otro"				=> 4
	);
	
	public static $TIPO_DIR = array(
		"RESIDENCE"	=> 0, 
		"BUSINESS"	=> 1, 
		"OTHER"		=> 2
	);
	
	function __construct()
    {
        // Call the Model constructor
        parent::__construct();
		
		//si no hay sesión
		//manda al usuario a la... pagina de login
		$this->redirect_cliente_invalido('id_cliente', '/index.php/login');
		
		//bandera de redirección a la orden en cuanto se llega acá
		$this->session->set_userdata("redirect_to_order", "orden_compra");
		$this->session->set_userdata("destino", "orden_compra");
		
		//cargar el modelo en el constructor
		$this->load->model('orden_compra_model', 'orden_compra_model', true);
		$this->load->model('forma_pago_model', 'forma_pago_model', true);
		$this->load->model('direccion_envio_model', 'direccion_envio_model', true);
		$this->load->model('direccion_facturacion_model', 'direccion_facturacion_model', true);
		
		//si la sesión se acaba de crear, toma el valor inicializar el id del cliente de la session creada en el login/registro
		$this->id_cliente = $this->session->userdata('id_cliente');
		
		//traer del controlador api las funciones encrypt y decrypt
		$this->api = new Api();		
		/*
		echo "<pre>";
		var_dump($this->session->all_userdata());
		echo "</pre>";
		exit();
		*/
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
		$data['requiere_envio'] = $this->session->userdata('requiere_envio');
		
		/*Recuperar la info gral. de la orden*/
		$id_cliente = $this->id_cliente;

		//Tarjeta
		$tarjeta = $this->session->userdata('tarjeta');
		
		//si está en session la información
		if ($this->session->userdata('deposito')) {	//revisar si hay depósito bancario
			
			$data['deposito'] = TRUE;
		
		} else if (!empty($tarjeta)) {
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
		$data['datos_login']='';		
		/*Realizar el pago en CCTC*/
		if ($_POST) {
			$orden_info = array();		
			$orden_info = $this->get_datos_orden();
						
			if (empty($this->registro_errores)) {
				
				//echo "El pago se realizará aquí. CVV: ".$_POST['txt_codigo'];
				
				/*Recuperar la info gral. de la orden*/
				$id_cliente 	= $this->id_cliente;
				//forma pago
				$consecutivo 	= $this->session->userdata('tarjeta') ? $this->session->userdata('tarjeta') : $this->session->userdata('deposito');				
				$id_promocionIn = $this->session->userdata('promocion')->id_promocionIn;
				$digito 		= (isset($_POST['txt_codigo'])) ? $_POST['txt_codigo'] : 0;
				
				// Informaciòn de la Orden //
				$informacion_orden = new InformacionOrden(
					$id_cliente,
					$consecutivo,
					$id_promocionIn,
					$digito
				);
				
				//echo var_dump($informacion_orden);				
				//exit();				
				
				$cliente = new SoapClient("https://cctc.gee.com.mx/ServicioWebCCTC/ws_cms_cctc.asmx?WSDL");
				//$cliente = new SoapClient("http://localhost:11622/ServicioWebPago/ws_cms_cctc.asmx?WSDL");
				
				// Si la información esta en la Session //
				$tipo_pago = self::$TIPO_PAGO['Otro'];	//ninguno válido al inicio
				
				//Configuración de la forma de pago y solicitud de cobro a CCTC
				if ($this->session->userdata('deposito')) {	//Depósito Bancario
					//el usuario de ecommerce será el que se registre para el cobro con esta forma de pago
					$id_cliente = self::User_Ecommerce;	
					
					//para el registro de la compra en ecommerce
					$tipo_pago = self::$TIPO_PAGO['Deposito_Bancario'];
					//$id_forma_pago = 0;
										
					//echo " tipo pago depósito: " . $tipo_pago;
					
					//Registrar la orden de compra y el detalle del pago con depósito 
					$id_compra = $this->registrar_orden_compra($id_cliente, $id_promocionIn, $tipo_pago);
					
					if ($id_compra) {
						$mensaje = "Mensaje de Arlette";
						
						//Mandar correo al cliente con el formato de arlette para notificarle lo que debe hacer
						$envio_correo = $this->enviar_correo("Notificación de compra", $mensaje);
						
						//Redirección a la URL callback con el código nuevo
						 
						//registrar el estatus de la compra correspondiente a la notificación final, esto es después del proceso nocturno
						//$envio_correo = $this->registrar_estatus_compra($id_compra, (int)$id_cliente, self::$ESTATUS_COMPRA['ENVIO_CORREO']);
						
						//manejo envío correo
						if (!$envio_correo) {	//Error
							redirect('mensaje/'.md5(4), 'refresh');
						}
						
						echo "Correo enviado correctamente.";
					} else {
						redirect('mensaje/'.md5(2), 'refresh');
					}
					
				} else if (is_array($this->session->userdata('tarjeta'))) {	//Pago con tarjetas
					$detalle_tarjeta = $this->session->userdata('tarjeta');
					$tc = $detalle_tarjeta['tc'];
					$tc = (array)$tc;
					
					//echo var_dump($tc);
					
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
					
					//si es Visa o Master card
					$tipo_pago = self::$TIPO_PAGO['Prosa'];	
					
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
						
						//si es AMEX
						$tipo_pago = self::$TIPO_PAGO['American_Express'];
					}

					//Pruebas orden compra
					/*
					echo " tipo pago en sesión: " . $tipo_pago;
					echo "<pre>";
					var_dump($informacion_orden);
					echo "</pre>";
					exit();
					 * */
					
					//$id_compra = $this->registrar_orden_compra($id_cliente, $id_promocionIn, $tipo_pago);
					
					//Intentamos el Pago con pasando los objetos a CCTC //
					try {
						$parameter = array(	'informacion_tarjeta' => $tc_soap, 'informacion_amex' => $amex_soap, 'informacion_orden' => $informacion_orden);
						
						//Registro inicial de la compra
						$id_compra = $this->registrar_orden_compra($id_cliente, $id_promocionIn, $tipo_pago);
						
						if (!$id_compra) {	//Si falla el registro inicial de la compra en CCTC
							redirect('mensaje/'.md5(3), 'refresh');
						}
						
						$obj_result = $cliente->PagarTC($parameter);
						
						//Resultado de la petición de cobro a CCTC
						$simple_result = $obj_result->PagarTCResult;
						
						//Registro del estatus de la respuesta de CCTC
						$this->registrar_estatus_compra($id_compra, (int)$id_cliente, self::$ESTATUS_COMPRA['RESPUESTA_CCTC']);
						
						//Registro de la respuesta de CCTC de la compra en ecommerce
						$info_detalle_pago_tc = array('id_compraIn'=> $id_compra, 'id_clienteIn' => $id_cliente, 'id_TCSi' => $tc['id_TCSi'], 
														'id_transaccionBi' => $simple_result->id_transaccionNu, 'respuesta_bancoVc' => $simple_result->respuesta_banco,
														'codigo_autorizacion' => $simple_result->codigo_autorizacion, 'mensaje' => $simple_result->mensaje);
														
						//Registro de la respuesta del pago en ecommerce
						$this->registrar_detalle_pago_tc($info_detalle_pago_tc);
						$this->registrar_estatus_compra($id_compra, (int)$id_cliente, self::$ESTATUS_COMPRA['REGISTRO_PAGO_ECOMMERCE']);
						
						//Envío del correo
						$mensaje = "Se ha realizado el cobro con exito? " . $simple_result->respuesta_banco;
						
						$envio_correo = $this->enviar_correo("Notificación de cobro", $mensaje);
						$estatus_correo = $this->registrar_estatus_compra($id_compra, (int)$id_cliente, self::$ESTATUS_COMPRA['ENVIO_CORREO']);
						
						//manejo envío correo
						if (!($envio_correo && $estatus_correo)) {	//Error
							redirect('mensaje/'.md5(4), 'refresh');
						}
																	
						//obtiene os datos que se van a regresar al sitio																							
						$this->datos_urlback($simple_result->respuesta_banco, $id_compra);
																		
						$data['resultado'] = $simple_result;	
																	
						$this->cargar_vista('', 'orden_compra', $data);
						$this->session->sess_destroy();
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
					
					
					$tipo_pago = ($tc->id_tipo_tarjetaSi == self::Tipo_AMEX) ? self::$TIPO_PAGO['American_Express'] : self::$TIPO_PAGO['Prosa'];
					
					//echo " tipo pago: " . $tipo_pago;
					//exit();
					/*
					echo " tipo pago de la DB: " . $tipo_pago;
					echo "<pre>";
					var_dump($informacion_orden);
					echo "</pre>";
					 * */
					
									
					// Intentamos el Pago con los Id's en  CCTC //
					try {  
						$parameter = array('informacion_orden' => $informacion_orden);
						
						//Registro inicial de la compra						
						$id_compra = $this->registrar_orden_compra($id_cliente, $id_promocionIn, $tipo_pago);
						
						if (!$id_compra) {	//Si falla el registro inicial de la compra en CCTC
							redirect('mensaje/'.md5(3), 'refresh');
						}
						
						//Intento de cobro en CCTC
						$obj_result = $cliente->PagarTcUsandoId($parameter);
						
						//Resultado de la petición de cobro a CCTC
						$simple_result = $obj_result->PagarTcUsandoIdResult;
					
						//Registro de la respuesta de CCTC de la compra en ecommerce
						$info_detalle_pago_tc = array('id_compraIn'=> $id_compra, 'id_clienteIn' => $id_cliente, 'id_TCSi' => $consecutivo, 
														'id_transaccionBi' => $simple_result->id_transaccionNu, 'respuesta_bancoVc' => $simple_result->respuesta_banco,
														'codigo_autorizacion' => $simple_result->codigo_autorizacion, 'mensaje' => $simple_result->mensaje);
														
						//Registro de la respuesta del pago en ecommerce
						$this->registrar_detalle_pago_tc($info_detalle_pago_tc);
						$this->registrar_estatus_compra($id_compra, (int)$id_cliente, self::$ESTATUS_COMPRA['REGISTRO_PAGO_ECOMMERCE']);
						
						//Envío del correo
						$mensaje = "Se ha realizado el cobro con exito? " . $simple_result->respuesta_banco;
						
						$envio_correo = $this->enviar_correo("Notificación de cobro", $mensaje);
						$estatus_correo = $this->registrar_estatus_compra($id_compra, (int)$id_cliente, self::$ESTATUS_COMPRA['ENVIO_CORREO']);
						
						//manejo envío correo
						if (!($envio_correo && $estatus_correo)) {	//Error
							redirect('mensaje/'.md5(4), 'refresh');
						}
					
						
						//Para lo que se devolverá a Teo							
						$this->datos_urlback($simple_result->respuesta_banco, $id_compra);											
						
						$data['resultado'] = $simple_result;								
										
						$this->cargar_vista('', 'orden_compra', $data);
						$this->session->sess_destroy();
						
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
	
	private function datos_urlback($respuesta_banco, $id_compra){
		if($respuesta_banco=='approved'){
			$estatus_pago = 1;
		}
		else{
			//este caso puede ser denied o Incorrect information
			$estatus_pago = 0;
		}
		$data['cadena_comprobacion'] = md5($this->session->userdata('guidx').$this->session->userdata('guidy').$this->session->userdata('guidz').$estatus_pago);
		$data['datos_login'] = $this->api->encrypt($id_compra."|".$this->api->decrypt($this->session->userdata('datos_login'),$this->api->key), $this->api->key);
		$data['urlback'] = $this->session->userdata('sitio')->url_PostbackVc;				
	} 
	 
	private function registrar_orden_compra($id_cliente, $id_promocion, $tipo_pago)
	{
		//Registrar eb la tabla de ordenes
		$id_compra = 0;
		$id_compra = $this->registrar_compra($id_cliente);
		
		//echo "<br/>cliente: ". $id_cliente ;
		
		if ($id_compra) {
			
			///artiulos de la promoción
			$articulos_compra = array();
			$articulos_compra = $this->orden_compra_model->obtener_articulos_promocion($id_promocion);
			
			foreach ($articulos_compra as $articulo) {
				 //preparar la información para insertar los artículos
				$info_articulos[] = array((int)$articulo->id_articulo, $id_compra, (int)$id_cliente, (int)$id_promocion);
			}
			
			///////forma pago///////
			$id_TCSi = 0;
			$info_pago = array();
			//procesar el tipo de pago
			if ($tarjeta = $this->session->userdata('tarjeta')) {
				$id_TCSi = (is_array($tarjeta)) ? $tarjeta['tc']['id_TCSi'] : $tarjeta;
				//$tipo_pago = 1;	//MASTERCARD / VISA/=1,  AMEX=2 
			} else if ($this->session->userdata('deposito')) {
				$id_TCSi = 0;		//consecutivo usado para depósito y tarjetas no guardadas
				//$tipo_pago = 3;	//Depósito Bancario
			}
			
			$info_pago = array('id_compraIn' => $id_compra, 'id_clienteIn' => (int)$id_cliente, 'id_tipoPagoSi' => $tipo_pago, 'id_TCSi' => (int)$id_TCSi);
			
			///////direccion(es)///////
			$info_direcciones = array();
			
			if ($this->session->userdata('requiere_envio')) {
				echo "Sí requiere_envio: Si<br/>";
				if ($dir_envio = $this->session->userdata('dir_envio')) {
					echo "direccion_envio: " . $dir_envio;
					
					$info_direcciones['envio'] = 
						array('id_compraIn' => $id_compra, 'id_clienteIn' => (int)$id_cliente, 'id_consecutivoSi' => (int)$dir_envio, 'address_type' => self::$TIPO_DIR['RESIDENCE']);
				} else {
					//No se efectúa la petición por que falta el dato de envío
					echo "Error: requiere dirección de envío";
					return FALSE;
				}
				
			} else {
				//si n orequiere se vacía
				$info_direcciones['envio'] = array();
			}
			
			if ($this->session->userdata('requiere_factura') !== "no") {
				echo "Sí requiere factura: <br/>".$this->session->userdata('requiere_factura');
				$dir_facturacion = $this->session->userdata('razon_social');
				$razon_social = $this->session->userdata('direccion_f');
				
				if ($dir_facturacion && $razon_social) {
					$info_direcciones['facturacion'] = 
						array('id_compraIn' => $id_compra, 'id_clienteIn' => (int)$id_cliente, 'id_consecutivoSi' => $dir_facturacion, 'id_razonSocialIn' => $razon_social , 'address_type' => self::$TIPO_DIR['BUSINESS']);
				} else {
					echo "Error: falta la dirección de facturación";
					return FALSE;
				}
			} else {
				//si n orequiere se vacía
				$info_direcciones['facturacion'] = array();
			}
			
			///////estatus de registro de la compra///////
			$estatus = ($tipo_pago == self::$TIPO_PAGO['Deposito_Bancario']) ? self::$ESTATUS_COMPRA['PAGO_DEPOSITO_BANCARIO'] : self::$ESTATUS_COMPRA['SOLICITUD_CCTC'];
			$info_estatus = array('id_compraIn' => $id_compra, 'id_clienteIn' => (int)$id_cliente, 'id_estatusCompraSi' => $estatus);
			
			/////////////registrar compra inicial en BD/////// 
			$registro_orden = $this->orden_compra_model->registrar_compra_inicial($info_articulos, $info_pago, $info_direcciones, $info_estatus);
			//echo "compra: " . $id_compra;
			//exit();
			return $id_compra;
		} else {
			//Error en el registro de la compra
			return FALSE;
		}
		
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
	 * Registrar alguno de los estatus de la compra
	 * id_compraIn, id_clienteIn, id_estatusCompreSi, timestamp
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
	 * Registro del detalle del pago en ecommerce 
	 */
	 private function registrar_detalle_pago_tc($info_detalle_pago_tc) {
	 	try {
			return $this->orden_compra_model->insertar_detalle_pago_tc($info_detalle_pago_tc);
		} catch (Exception $ex ) {
			echo "Error en el registro detalle del pago en ecomerce: " .$ex->getMessage();
			return FALSE;
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
	 * Envía un correo 
	 */
	private function enviar_correo($asunto, $mensaje) {
		$headers = "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
	    $headers .= "From: GexWeb<soporte@expansion.com.mx>\r\n";
		
		$email = $this->session->userdata('email');
					
		return ($email && mail($email, 'Recuperar password', $mensaje));
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