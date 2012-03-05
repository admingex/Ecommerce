<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include ('DTOS/Tipos_Tarjetas.php');

class Forma_Pago extends CI_Controller {
	var $title = 'Forma de Pago'; 		// Capitalize the first letter
	var $subtitle = 'Seleccionar Forma de Pago'; 	// Capitalize the first letter
	var $reg_errores = array();		//validación para los errores
	//var $tc = array();
	private $id_cliente;
	//protected $lista_bancos = array();
	 
	function __construct()
    {
        // Call the Model constructor
        parent::__construct();
		
		//cargar el modelo en el constructor
		$this->load->model('forma_pago_model', 'modelo', true);
		//la sesion se carga automáticamente
		
		//si no hay sesión
				//manda al usuario a la... pagina de login
		$this->redirect_cliente_invalido('id_cliente', '/index.php/login');
		
		//si la sesión se acaba de crear, toma el valor inicializar el id del cliente de la session creada en el login/registro
		$this->id_cliente = $this->session->userdata('id_cliente');

    }

	public function index()	//Para pruebas se usa 1
	{
		//Recuperar el "id_ClienteNu" de la sesion
		
		//$id_cliente = $this->session->userdata('id_cliente');
		
		//echo 'cliente_Id: '.$id_cliente;
		
		//echo 'tipo '. gettype($tc);
		//echo 'cliente_Id'.$tc->cliente_id;
		//var_dump($tc);
		
		$this->listar();
	}
	
	public function listar($msg = '', $redirect = TRUE) 
	{	
		/*asignación de la session*/
		//$id_cliente = $this->session->userdata('id_cliente');
		//$this->session->set_userdata('id_cliente', $id_cliente);
		
		/*
		echo 'cliente: '.$this->session->userdata('id_cliente').'<br/>';
		echo 'Session: '.$this->session->userdata('session_id').'<br/>';
		echo 'last_Activity: '.$this->session->userdata('last_activity').'<br/>';
		*/	
		
		//EL usuario se tomará de la sesión...
		
		$data['title'] = $this->title;
		$data['subtitle'] = $this->subtitle;		
		$data['mensaje'] = $msg;
		$data['redirect'] = $redirect;
		
		//listar por default las tarjetas del cliente
		$data['lista_tarjetas'] = $this->modelo->listar_tarjetas($this->id_cliente);
		
		//cargar vista	
		$this->cargar_vista('', 'forma_pago', $data);
		
	}
	
	public function registrar($form = 'tc') 
	{
		//echo 'Session: '.$this->session->userdata('id_cliente');
		
		$id_cliente = $this->id_cliente;
		
		$consecutivo = $this->modelo->get_consecutivo($id_cliente);
		
		$data['title'] = $this->title;
		$data['subtitle'] = ucfirst('Nueva Forma de Pago');
		
		//catálogo que se obtendrá del CCTC
		$lista_tipo_tarjeta = $this->lista_tipos_tarjeta_WS();
		$data['lista_tipo_tarjeta'] = $lista_tipo_tarjeta;
		
		//se aloja como parte del objeto para obtener la descripcion, no necesario
		//$this->lista_bancos =  $lista_tipo_tarjeta;
				
		//recuperar el listado de las tarjetas del cliente
		$data['lista_tarjetas'] = $this->modelo->listar_tarjetas($id_cliente);
		
		$data['form'] = $form;		//para indicar qué formulario mostrar
		if ($_POST)	{	//si hay parámetros del formulario
			
			//común
			$form_values = array();	//alojará los datos previos a la inserción	
			$form_values = $this->get_datos_tarjeta();
			
			$form_values['tc']['id_clienteIn'] = $id_cliente;
			$form_values['tc']['id_TCSi'] = $consecutivo + 1;		//cambió
				
				/* Se debe validar si se quiere guardar la tarjeta antes de registrarla
				 * esto con: $form_values['guardar']
				 * si no se quiere guardar se continua con el proceso
				 * */
			if (empty($this->reg_errores)) {	
				//si no hay errores y se solicita registrar la tarjeta
				if (isset($form_values['guardar']) || isset($form_values['tc']['id_estatusSi'])) {
					//verificar que no exista la tarjeta activa en la BD
					//var_dump($form_values);
					//exit();
					if ($form == 'tc') {
						$form_values['tc']['descripcionVc'] = $lista_tipo_tarjeta[$form_values['tc']['id_tipo_tarjetaSi'] - 1]->descripcion;
						$form_values['amex'] = null;	//para que no se tome encuenta.
					} else if ($form == 'amex') {
						$form_values['amex']['id_clienteIn'] = $id_cliente;
						$form_values['amex']['id_TCSi'] = $consecutivo + 1;
						$form_values['tc']['id_tipo_tarjetaSi'] = 1;
						$form_values['tc']['descripcionVc'] = "AMERICAN EXPRESS";
					}
					//echo var_dump($form_values);
					//exit();
					
					if($this->modelo->existe_tc($form_values['tc'])) {	//Redirect al listado por que ya existe
						//$url = $this->config->item('base_url').'/index.php/forma_pago/listar/'.$id_cliente;
						//header("Location: $url");
						$this->listar("La tarjeta ya está registrada.", FALSE);
						//echo "La tarjeta ya está registrada.";
						//exit();
					} else {
						//echo "se manda insertar en CCTC";
						
						if ($this->registrar_tarjeta_CCTC($form_values['tc'], $form_values['amex'])) {
						//if (1) {	//echo "Se registró exitosamente! en CCTC";
							
							//Registrar Localmente
							if ($this->modelo->insertar_tc($form_values['tc'])) {
								$this->listar("Tarjeta registrada correctamente.");
							} else {
								$this->listar("Hubo un error en el registro en CMS.", FALSE);
								//echo "<br/>Hubo un error en el registro en CMS";
							}
						} else {
							$this->listar("Hubo un error en el registro en CCTC.", FALSE);
							//echo "Hubo un error en el registro en CCTC";
						}
					}						
				} else {
					/*Poner en session la TC/AMEX*/
					//si no se guardará la tc, almacenar la info para la venta  
					echo "no se almacenará la TC >> Pasar a captura de dir. de envío<br/> Coming soon...";
					//exit();	
				}
				//se envía la tc al WS de CCTC y de acuerdo a la respuesta...
				/*					
				//De momento se regresará al listado de tarjetas
				if ($this->modelo->insertar_tc($form_values['tc'])) {
					$url = $this->config->item('base_url').'/index.php/forma_pago/listar/'.$id_cliente;
					header("Location: $url");
					exit();	
				}
				*/
			} else {	//Si hubo errores
				//vuelve a mostrar la info.
				$data['reg_errores'] = $this->reg_errores;
				$this->cargar_vista('', 'forma_pago' , $data);	
			}
		} else {
			$this->cargar_vista('', 'forma_pago' , $data);
		}
	}

	public function editar($consecutivo)	//el consecutivo de la tarjeta
	{
		$id_cliente = $this->id_cliente;
		
		$data['title'] = $this->title;
		$data['subtitle'] = ucfirst('editar Forma de Pago');
		
		//recuperar la información local de la tc
		$detalle_tarjeta = $this->modelo->detalle_tarjeta($consecutivo, $id_cliente);
		//Siempre se trae la info para tc
		$data['tarjeta_tc'] = $detalle_tarjeta;
		//var_dump($detalle_tarjeta);
		//exit();
		
		//array par al anueva información
		$nueva_info = array();
		
		if ($detalle_tarjeta->id_tipo_tarjetaSi == 1) { //es AMERICAN EXPRESS
			$data['vista_detalle'] = 'amex';
			$data['tarjeta_amex'] = $this->detalle_tarjeta_CCTC($id_cliente, $consecutivo);
			//en este caso se consultará la info del WS
		} else  {	
			//Si no es de tipo American Express, trae la info de la base local
			//y especificat el tipo de la tarjeta y el numero.
			$data['vista_detalle'] = 'tc';
		}

		//Se intentará actualizar la información
		if ($_POST) {
			$tipo_tarjeta = $data['vista_detalle'];
			//trae datos del formulario para actualizar
			//echo "Se va a editar.";
			$nueva_info = $this->get_datos_tarjeta($tipo_tarjeta);	//tc/amex
			
			//errores
			$data['reg_errores'] = $this->reg_errores;	
			
			if (empty($data['reg_errores'])) {	//si no hubo errores
				//preparar la petición al WS, campos comunes
				$nueva_info['tc']['id_clienteIn'] = $id_cliente;
				$nueva_info['tc']['id_TCSi'] = $consecutivo;
				$nueva_info['tc']['terminacion_tarjetaVc'] = $detalle_tarjeta->terminacion_tarjetaVc;
				//Preparación de la sol.
				if ($tipo_tarjeta == 'tc') {
					$nueva_info['amex'] = null;		
					$nueva_info['tc']['id_tipo_tarjetaSi'] = $detalle_tarjeta->id_tipo_tarjetaSi;
					
				} else {
					$nueva_info['amex']['id_clienteIn'] = $id_cliente;
					$nueva_info['amex']['id_TCSi'] = $consecutivo;
				}
				//var_dump($nueva_info);
				//exit();
				
				//actualizar en CCTC
				if($this->editar_tarjeta_CCTC($nueva_info['tc'], $nueva_info['amex'])) {
				//if (1) {
					//echo "Tarjeta actualizada en CCTC.<br/>";
					//checar el estatus:
					if (!isset($nueva_info['tc']['id_estatusSi'])) {	
						//si no está como predeterminada se deja en activo
						//echo "Activo!!!  ";
						$nueva_info['tc']['id_estatusSi'] = 1;
					}					
					//registrar cambios localmente, siempre se manda la info de $nueva_info['tc']
					$data['msg_actualizacion'] = 
						$this->modelo->actualiza_tarjeta($consecutivo, $id_cliente, $nueva_info['tc']);
				} else {
					echo "Error de actualización hacia CCTC.<br/>";	//redirect					
				}
			} else {	//sí hubo errores
				$data['msg_actualizacion'] = "Campos incorrectos";
				//echo "<br/>Campos incorrectos.<br/>";
				//var_dump($this->reg_errores);
			}
			//print_r($nueva_info[$tipo_tarjeta]);
		}//If POST
		
		$this->cargar_vista('', 'forma_pago' , $data);
	}
	
	public function eliminar($consecutivo = '')
	{
		$id_cliente = $this->id_cliente;
		$data['title'] = $this->title;
		$data['subtitle'] = ucfirst('Eliminar Forma de Pago');
		
		//exit();
		if ($this->eliminar_tarjeta_CCTC($id_cliente, $consecutivo)) {
		//if (1) {
			//echo "Eliminado correctamente de CCTC";
			//eliminar lógicamente en la bd local
			$msg_eliminacion =
				$this->modelo->eliminar_tarjeta($id_cliente, $consecutivo);
		} else {
			//echo "no se pudo eliminar correctamente de CCTC";
			$msg_eliminacion = "no se pudo eliminar correctamente de CCTC";
		}
		/*Pendiente el Redirect*/
		//echo "<br/>se eliminó la tarjeta $consecutivo del cliente $id_cliente<br/>";
		//echo $data['msg_eliminacion´];
		
		//cargar la lista
		$this->listar($msg_eliminacion);
		//$this->cargar_vista('', 'forma_pago' , $data);
	}
	
	private function eliminar_tarjeta_CCTC($id_cliente = 0, $consecutivo = 0)
	{
		try {  
			$cliente = new SoapClient("https://cctc.gee.com.mx/ServicioWebCCTC/ws_cms_cctc.asmx?WSDL");
				
			$parameter = array(	'id_clienteNu' => $id_cliente, 'consecutivo_cmsSi' => $consecutivo);
			
			$obj_result = $cliente->EliminarTC($parameter);
			$simple_result = $obj_result->EliminarTCResult;
			
			//print($simple_result);
			
			return $simple_result;
			
		} catch (SoapFault $exception) {
			echo $exception;  
			echo '<br/>error: <br/>'.$exception->getMessage();
			//exit();
			return false;
		}
	}
	
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
	
	private function editar_tarjeta_CCTC($tc, $amex = null)
	{
		$resultado = FALSE;
		//mapeo de la tc
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
			//'renovacion_automatica' => $tc[']//Mandar true para que se guarde
		);
		
		//mapeo Amex
		if (isset($amex)) {
			$amex_soap = new Amex(
				$amex['id_clienteIn'],
				$amex['id_TCSi'],
				$amex['nombre_titularVc'],
				$amex['apellidoP_titularVc'],
				$amex['apellidoM_titularVc'],
				$amex['pais'], $amex['codigo_postal'],
				$amex['calle'], $amex['ciudad'],
				$amex['estado'], $amex['mail'],
				$amex['telefono']
			);
		} else {
			$amex_soap = null;
		}
		//var_dump($tc_soap);
		//var_dump($amex_soap);
		//echo (isset($amex));
		//exit();
		
		try {  
			$cliente = new SoapClient("https://cctc.gee.com.mx/ServicioWebCCTC/ws_cms_cctc.asmx?WSDL");
				
			$parameter = array(	'informacion_tarjeta' => $tc_soap, 'informacion_amex' => $amex_soap);
			
			$obj_result = $cliente->EditarTC($parameter);
			$simple_result = $obj_result->EditarTCResult;
			
			//print($simple_result);
			
			return $simple_result;
			
		} catch (SoapFault $exception) {
			echo $exception;  
			echo '<br/>error: <br/>'.$exception->getMessage();
			//exit();
			return false;
		}
	}
	
	private function registrar_tarjeta_CCTC($tc, $amex = null) 
	{
		$resultado = FALSE;
		//mapeo de la tc
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
			//'renovacion_automatica' => $tc[']//Mandar true para que se guarde
		);
		
		//mapeo Amex
		if (isset($amex)) {
			$amex_soap = new Amex(
				$amex['id_clienteIn'],
				$amex['id_TCSi'],
				$amex['nombre_titularVc'],
				$amex['apellidoP_titularVc'],
				$amex['apellidoM_titularVc'],
				$amex['pais'], $amex['codigo_postal'],
				$amex['calle'], $amex['ciudad'],
				$amex['estado'], $amex['mail'],
				$amex['telefono']
			);
		} else {
			$amex_soap = null;
		}
		
		try {  
			$cliente = new SoapClient("https://cctc.gee.com.mx/ServicioWebCCTC/ws_cms_cctc.asmx?WSDL");
				
			$parameter = array(	'informacion_tarjeta' => $tc_soap, 'informacion_amex' => $amex_soap);
			
			$obj_result = $cliente->InsertarTC($parameter);
			$simple_result = $obj_result->InsertarTCResult;
			
			//print($simple_result);
			
			return $simple_result;
			
		} catch (SoapFault $exception) {
			echo $exception;  
			echo '<br/>error: <br/>'.$exception->getMessage();
			//exit();
			return false;
		}
	}
	/*
	 * Consulta del catálogo de tarjetas de Banco de CCTC
	 * */
	private function lista_tipos_tarjeta_WS() 
	{	
		try {
			//URL del WS debe estar en archivo protegido  
			$cliente = new SoapClient("https://cctc.gee.com.mx/ServicioWebCCTC/ws_cms_cctc.asmx?WSDL");	
			
			//Recupera la lista de bancos
			$ObtenerBancosResponse = $cliente->ObtenerBancos();		//Repuesta inicial del WS
			$ObtenerBancosResult = $ObtenerBancosResponse->ObtenerBancosResult;
			$InformacionBancoArray = $ObtenerBancosResult->InformacionBanco;
			//print var_dump($InformacionBancoArray);	//arreglo de objetos
				
			return $InformacionBancoArray;
			
		} catch (Exception $e) {
			echo "No se pudo recuperar el catálogo de bancos.<br/>";
			echo $e->getMessage();
			exit();
		}
	}
	
	/*
	 * Consulta del catálogo de tarjetad de Banco local
	 * */
	private function lista_tipos_tarjeta() 
	{	
		$lista_tipo_tarjeta = $this->modelo->listar_tipos_tarjeta();
		return $lista_tipo_tarjeta->result();
	}
	
	private function get_datos_tarjeta($tipo = '')
	{
		$datos = array();
		//echo "tipo : ". $tipo;
		//no se usa la funcion de escape '$this->db->escape()', por que en la inserción ya se incluye 
		if($_POST) {
			if(array_key_exists('sel_tipo_tarjeta', $_POST)) {
				$datos['tc']['id_tipo_tarjetaSi'] = $_POST['sel_tipo_tarjeta'];
				//echo "existe sel_tipos"; 
			} else if ($tipo == 'amex') {
				$datos['tc']['id_tipo_tarjetaSi'] = 1;	//Amex
				$datos['tc']['descripcionVc'] = 'AMERICAN EXPRESS';	//Amex
				
			}
			
			if(array_key_exists('txt_numeroTarjeta', $_POST)) { 
				//al final sólo será la terminación de la tarjeta, pero se deben validar los 16 digitos
				if(preg_match('/^[0-9]{16,17}$/', $_POST['txt_numeroTarjeta'])) {
					$datos['tc']['terminacion_tarjetaVc'] = 
					substr($_POST['txt_numeroTarjeta'], strlen($_POST['txt_numeroTarjeta']) - 4);
				} else {
					$this->reg_errores['txt_numeroTarjeta'] = 'Por favor ingrese un numero de tarjeta v&aacute;lido';
				}				
			}
			if(array_key_exists('txt_nombre', $_POST)) {
				if(preg_match('/^[A-Z \'.-áéíóúÁÉÍÓÚÑñ]{2,30}$/i', $_POST['txt_nombre'])) { 
					$datos['tc']['nombre_titularVc'] = $_POST['txt_nombre'];
					if ($tipo = 'amex') {
						$datos['amex']['nombre_titularVc'] = $_POST['txt_nombre'];
					}
				} else {
					$this->reg_errores['txt_nombre'] = 'Ingresa tu nombre correctamente';
				}
			}
			if(array_key_exists('txt_apellidoPaterno', $_POST)) {
				if(preg_match('/^[A-Z \'.-áéíóúÁÉÍÓÚÑñ]{2,30}$/i', $_POST['txt_apellidoPaterno'])) { 
					$datos['tc']['apellidoP_titularVc'] = $_POST['txt_apellidoPaterno'];
					if ($tipo = 'amex') {
						$datos['amex']['apellidoP_titularVc'] = $_POST['txt_apellidoPaterno'];
					}
				} else {
					$this->reg_errores['txt_apellidoPaterno'] = 'Ingresa tu apellido correctamente';
				}
			}
			if(array_key_exists('txt_apellidoMaterno', $_POST)) {
				if(preg_match('/^[A-Z \'.-áéíóúÁÉÍÓÚÑñ]{2,30}$/i', $_POST['txt_apellidoMaterno'])) {
					$datos['tc']['apellidoM_titularVc'] = $_POST['txt_apellidoMaterno'];
					if ($tipo = 'amex') {
						$datos['amex']['apellidoM_titularVc'] = $_POST['txt_apellidoMaterno'];
					}
				} else {
					$this->reg_errores['txt_apellidoMaterno'] = 'Ingresa tu apellido correctamente';
				}
			}
			if(array_key_exists('txt_codigoSeguridad', $_POST)) {
				//este código sólo se almaccena para solicitar el pago 
				$datos['codigo_seguridad'] = $_POST['txt_codigoSeguridad']; 
			}
			if(array_key_exists('sel_mes_expira', $_POST)) {
				$datos['tc']['mes_expiracionVc'] = $_POST['sel_mes_expira']; 
			}
			if(array_key_exists('sel_anio_expira', $_POST)) { 
				$datos['tc']['anio_expiracionVc'] = $_POST['sel_anio_expira'];  
			}
			if(array_key_exists('chk_guardar', $_POST)) {
				$datos['guardar'] = $_POST['chk_guardar'];		//indicador para saber si se guarda o no la tarjeta
				$datos['tc']['id_estatusSi'] = 1;
			}
			if(array_key_exists('chk_default', $_POST)) {
				$datos['tc']['id_estatusSi'] = 3;	//indica que será la tarjeta predeterminada	
				//$_POST['chk_default'];
				//en la edicion, si no se cambia, que se quede como está, activa!! VERIFICARLO on CCTC
			}
			
			//AMEX
			if(array_key_exists('txt_calle', $_POST)) {
				if(preg_match('/^[A-Z0-9 \'.-áéíóúÁÉÍÓÚÑñ]{2,40}$/i', $_POST['txt_calle'])) {
					$datos['amex']['calle'] = $_POST['txt_calle'];
				} else {
					$this->reg_errores['txt_calle'] = 'Ingresa tu calle y n&uacute;mero correctamente';
				}
			}
			if(array_key_exists('txt_cp', $_POST)) {
				//regex usada en js
				if(preg_match('/^([1-9]{2}|[0-9][1-9]|[1-9][0-9])[0-9]{3}$/', $_POST['txt_cp'])) {
					$datos['amex']['codigo_postal'] = $_POST['txt_cp'];
				} else {
					$this->reg_errores['txt_cp'] = 'Ingresa tu c&oacute;digo postal correctamente';
				}
			}
			if(array_key_exists('txt_ciudad', $_POST)) {
				if(preg_match('/^[A-Z0-9 \'.-áéíóúÁÉÍÓÚÑñ]{2,40}$/i', $_POST['txt_ciudad'])) {
					$datos['amex']['ciudad'] = $_POST['txt_ciudad'];
				} else {
					$this->reg_errores['txt_ciudad'] = 'Ingresa tu ciudad correctamente';
				}
			}
			if(array_key_exists('txt_estado', $_POST)) {
				if(preg_match('/^[A-Z \'.-áéíóúÁÉÍÓÚÑñ]{2,40}$/i', $_POST['txt_estado'])) {
					$datos['amex']['estado'] = $_POST['txt_estado'];
				} else {
					$this->reg_errores['txt_estado'] = 'Ingresa tu estado correctamente';
				}
			}
			if(array_key_exists('txt_pais', $_POST)) {
				if(preg_match('/^[A-Z \'.-áéíóúÁÉÍÓÚÑñ]{2,40}$/i', $_POST['txt_pais'])) {
					$datos['amex']['pais'] = $_POST['txt_pais'];
				} else {
					$this->reg_errores['txt_pais'] = 'Ingresa tu pa&iacute;s correctamente';
				}
			}
			if(array_key_exists('txt_email', $_POST) && $_POST['txt_email'] != "") {
				if(filter_var($_POST['txt_email'], FILTER_VALIDATE_EMAIL)) {
					$datos['amex']['mail'] = $_POST['txt_email'];
				} else {
					$this->reg_errores['txt_email'] = 'Ingresa tu email correctamente (opcional)';
				}
			} else {
				$datos['amex']['mail'] = '';
			}
			
			if(array_key_exists('txt_telefono', $_POST)) {
				if(preg_match('/^[0-9 -]{2,15}$/i', $_POST['txt_telefono'])) {
					$datos['amex']['telefono'] = $_POST['txt_telefono'];
				} else {
					$this->reg_errores['txt_telefono'] = 'Ingresa tu tel&eacute;fono correctamente';
				}
			}
		} 
		
		//var_dump($datos);
		//echo 'si no hay errores, $reg_errores esta vacio? '.empty($this->reg_errores).'<br/>';
		return $datos;
	}

	private function cargar_vista($folder, $page, $data)
	{	
		//Para automatizar un poco el desplieguee
		$this->load->view('templates/header', $data);
		$this->load->view($folder.'/'.$page, $data);
		$this->load->view('templates/footer', $data);
	}
	
	/*
	 * Verifica la sesión del usuario
	 * 
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

/* End of file forma_pago.php */
/* Location: ./application/controllers/forma_pago.php */