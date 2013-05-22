<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include('util/Pago_Express.php');
include('api.php');

class Login extends CI_Controller {
	
	var $title = 'Iniciar Sesi&oacute;n'; 				// Capitalize the first letter
	var $subtitle = 'Iniciar Sesi&oacute;n Segura'; 	// Capitalize the first letter
	var $login_errores = array();
	
	const NUEVO = "nuevo";
	private $email;
	private $password;
	
	public static $TIPO_ACTIVIDAD = array(
		"BLOQUEO"=> 0, 
		"DESBLOQUEO"=> 1, 
		"SOLICITUD_PASSWORD"=>2,
		"CAMBIO_PASSWORD"=>3,
		"ACCESO_INCORRECTO"=>4
	);	
	
	
	function __construct()
    {
        // Call the Model constructor
        parent::__construct();
		$this->load->model('password_model', 'password_model', true);
		$this->load->helper('date');
		
		//para utilizar la sesión de PHP
		session_start();
		
		$this->output->nocache();
		
		if ($this->session->userdata('destino')) {
			//$this->session->userdata('destino');
			//redirect($this->session->userdata('destino'), 'location', 303);
			exit();
		}		
		
		//cargar el modelo en el constructor
		$this->load->model('login_registro_model', 'login_registro_model', true);
		//cargar los modelos para revisar el pago exprés
		$this->load->model('forma_pago_model');
		$this->load->model('direccion_envio_model');
		$this->load->model('direccion_facturacion_model');
		
		//carga el modelo del api para obtener el detalle del sitio 
		$this->load->model('api_model', 'api_model', true);			
		
		$this->api = new Api();
    }
	
	public function index()
	{
		//obtiene el detalle del sitio del cual viene el pago para mostrar el logo correspondiente
		if ($this->session->userdata('promociones')) {	//si trae varias promociones
			$promociones = $this->session->userdata('promociones');
				    
			foreach ($promociones as $promocion) {
				$id_sit = $promocion['id_sitio'];
				
				// obtiene los artículos de la promocion para revisar si viene algun oc_id para revisar si se deben incluir las tags de google				 
				$respromo = $this->api->obtener_detalle_promo($promocion['id_sitio'], $promocion['id_canal'], $promocion['id_promocion']);
				foreach( $respromo['articulos'] as $articulo) {
					## todo robustecer esta validacion para que el oc_id pueda ser de cualquier publicacion, aplica sólo Quién por el momento					
					if ($articulo['oc_id'] == 94) {
						$data['tags_google'] = 1;
						$this->session->set_userdata('tags_google', 1);
						break;
					}
				}
			}
			
			$this->session->unset_userdata('sitio');
			$datsit = $this->api_model->obtener_sitio($id_sit);
			
			$this->session->set_userdata('sitio', $datsit->row());
			$data['url_imageVc'] = $datsit->row()->url_imageVc;
			$data['url_sitio'] = $datsit->row()->urlVc;
		}																								
				
		//inclusión de Scripts
		$script_file = "<script type='text/javascript' src='". base_url() ."js/login.js'></script>";
		$data['script'] = $script_file;
		
		$data['title'] = $this->title;
		$data['subtitle'] = $this->subtitle;
		
		if ($_POST)
		{
			//si es usuario nuevo, se debe registrar
			if (array_key_exists('tipo_inicio', $_POST) && $_POST['tipo_inicio'] == $this::NUEVO) {
				$url = site_url('registro');
				header("Location: $url");
				exit();
			} else {
				//recupera y valida info de los campos
				$login_info = array();
				$login_info = $this->get_datos_login();
				$data['mensaje'] = "";
				
				if (empty($this->login_errores)) {
					//verificar que el usuario esté registrado
					$this->email = $login_info['email'];
					$this->password = $login_info['password'];
					$mail_cte = $this->login_registro_model->verifica_registro_email($this->email);
					
					//si encontró al usuario registrado										
					if ($mail_cte->num_rows()!=0) {	
						$fecha_lock = $mail_cte->row()->LastLockoutDate;
						$id_cliente = $mail_cte->row()->id_clienteIn;
						
						if ($fecha_lock != '0000-00-00 00:00:00') {	//verificar que la cuenta no esté bloqueada
							if ($this->tiempo_desbloqueo($fecha_lock)) {
								$this->login_registro_model->desbloquear_cuenta($id_cliente);	//desbloquear la cuenta
								$t = mdate('%Y/%m/%d %h:%i:%s',time());
								$this->password_model->guarda_actividad_historico($id_cliente, '', self::$TIPO_ACTIVIDAD['DESBLOQUEO'], $t);
							}
						}
						//número de intentos fallidos
						$num_intentos = $this->login_registro_model->obtiene_numero_intentos($id_cliente);	
						
						if ($num_intentos < 3) {	//verificar que la cuenta no esté bloqueada
							//verificar la contraseña y el correo del cliente, si lo encuentra trae :
							/**
							 * 	id_clienteIn as id_cliente, 
							 * 	salutation as nombre, 
							 * 	email, 
							 * 	password
							 * */
							$resultado = $this->login_registro_model->verifica_cliente($this->email, $this->password);
							
							//si la información es válida...el cliente puede iniciar sesión
							if ($resultado->num_rows() > 0) {	
								//Reguardar la información de la promoción
								
								//destruir la sesión de PHP y mandar la información en la sesión de CI con un nuevo ID
								$this->cambiar_session();
								
								//encriptar login y password y guardarlos en sesión para el proceso de validación con IDC
								$cliente = $resultado->row();
								$dl = $this->api->encrypt($cliente->email."|".$this->password."|", $this->api->key);
								$this->session->set_userdata('datos_login',$dl);
								
								//se pasa a la sessión la información del cliente como cliente "logueado" en el sistema de cobros
								$this->crear_sesion($cliente->id_cliente, $cliente->nombre, $this->email);
								
								//por default no se considera la dirección d facturación
								$datars = array('requiere_factura' => 'no');
								$this->session->set_userdata($datars);
								
								//detecta a donde va el ususario a partir de la/las promoción/promociones que se tiene en sesión
								$destino = $this->obtener_destino($cliente->id_cliente);		
								
								//colocar en sessión el destino
								$data_destino = array('destino' => $destino);
								$this->session->set_userdata($data_destino);
								
								//continuando con el flujo
								redirect($destino);
								
							} else {	//IF la información de inicio de sesión es correcta
								//si la informción no es correcta
								$this->login_errores['user_login'] = "Hubo un error con la combinación ingresada de correo y contraseña.<br />Por favor intenta de nuevo.";
								//registrar el intento fallido en la bitácora de actividad del cliente
								$t = mdate('%Y/%m/%d %h:%i:%s',time());
								$this->password_model->guarda_actividad_historico($id_cliente, $this->password, self::$TIPO_ACTIVIDAD['ACCESO_INCORRECTO'], $t);
								//actualiza en la BD el número de intentos fallidos para el cliente
								$t = mdate('%Y/%m/%d %h:%i:%s',time());
								$this->login_registro_model->suma_intento_fallido($id_cliente, $num_intentos, $t);
							}
							
						} else {
							if ($num_intentos == 3) {	//si ya tiene tres intentos fallidos
								//registrar el bloqueo de la cuenta en la bitácora de actividad del cliente
								$t = mdate('%Y/%m/%d %h:%i:%s',time());	
								$this->password_model->guarda_actividad_historico($id_cliente, '', self::$TIPO_ACTIVIDAD['BLOQUEO'], $t);
								//actualiza en la BD el número de intentos fallidos para el cliente
								$t = mdate('%Y/%m/%d %h:%i:%s',time());		
								$this->login_registro_model->suma_intento_fallido($id_cliente, $num_intentos, $t);	
							}
							//mensaje para mostrar al cliente en la pantalla de inicio de sesión
							$this->login_errores['user_login'] = "Ha excedido el número máximo de intentos permitidos para iniciar sesión.<br/>".
							                                      "Su cuenta permanecerá bloqueada por 30 minutos";
						}
						
					} else { // IF si encontró al usuario registrado
						$this->login_errores['user_login'] = "Hubo un error con la combinación ingresada de correo y contraseña.<br/>Por favor intenta de nuevo.";																																					
						//$data['mensaje'] = "Correo o contrase&ntilde;a incorrectos" ;
					}
				}
			}
		}
		//cargar la vista de login
		$data['login_errores'] = $this->login_errores;
		$this->cargar_vista('', 'login', $data);
	}
	
	/**
	 * Si ya se cargó la sesión del cliente después de un registro o una recuperación de contraseña
	 */
	public function verificar_inicio_sesion($id_cliente) {
		if ($this->session->userdata('id_cliente') == $id_cliente) {
			//por defaulr no se considera la dirección d facturación
			$datars = array('requiere_factura' => 'no');
			$this->session->set_userdata($datars);
			
			//detecta a dónde va el ususario a partir de la promoción que se tiene en sesión
			$destino = $this->obtener_destino($cliente->id_cliente);						
			
			//colocar en sesión el destino
			$data_destino = array('destino' => $destino);
			$this->session->set_userdata($data_destino);
			
			//Flujo
			redirect($destino, 'location', 303);
		}
	} 
	
	private function get_datos_login($value='')
	{
		$datos = array();
		
		if (filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
			$datos['email'] = htmlspecialchars(trim($_POST['email']));
		} else {
			$this->login_errores['email'] = '<div class="error2">Por favor ingresa una dirección de correo válida. Ejemplo: nombre@dominio.mx</div>';
		}
		
		if (array_key_exists('tipo_inicio', $_POST) && $_POST['tipo_inicio'] == 'registrado') {
			if (!empty($_POST['password'])) {
				$datos['password'] = htmlspecialchars(trim($_POST['password']));
			} else {
				$this->login_errores['password'] = '<div class="error2">Por favor escribe tu contraseña o selecciona iniciar sesión como cliente nuevo.</div>';
			}					
		} else {
			$this->login_errores['user_login'] = 'Selecciona alguna modalidad';
		}
		
		return $datos;
	}
	
	/**
	 * Regresa el destino del flujo a partir del perfil inicial del cliente,
	 * dependiendo de lo que los artículos de la promoción requieran
	 * y coloca en sesión lo necesario para el pago exprés
	 */
	private function obtener_destino($id_cliente)
	{
		//Procesar la promoción
		
		//para revisar renovación automática desde el principio
		$lleva_ra = FALSE;
		
		//revisamos que por lo menos tengamos una promocion
		if (($this->session->userdata('promociones')) != "") {
			if (count($this->session->userdata('promociones')) > 0) {
				
				//revisar si requiere forma de envío
				$requiere_envio = FALSE;
				
				foreach ($this->session->userdata('promociones') as $promocion) {
					// obtiene los artículos de la promocion				 
					$respromo = $this->api->obtener_detalle_promo($promocion['id_sitio'], $promocion['id_canal'], $promocion['id_promocion']);
					
					foreach($respromo['articulos'] as $articulo) {
						//requiere envío
						if ($articulo['requiere_envioBi'] != FALSE) {
							$requiere_envio = TRUE;
							//echo "requiere envio";
							//break;		//ya no se usa para que revise tabíen RA
						}
						
						//si requiere renovación automática:
						if ($articulo['renovacion_automaticaBi']) {
							$lleva_ra = TRUE;
							if ($requiere_envio) break;	//ya no tiene caso seguir revisando los demás artículos
						}
					}
				}
				
				//señalat que se requiere envío en la sesión
				$this->session->set_userdata('lleva_ra', $lleva_ra);
				
				//señalat que se requiere envío en la sesión
				$this->session->set_userdata('requiere_envio', $requiere_envio);	
				
				//el siguiente parámetro es para indicar al controlador si es necesario cargar el archivo views/templates/promocion.html => hay alguna promoción
				$this->session->set_userdata('promocion', TRUE);								
				
				/*echo "<pre>";
				var_dump($this->session->all_userdata());
				echo "</pre>";
				exit;*/
				
				$forma_pago_express = $this->forma_pago_model->get_pago_express($id_cliente);		//devolverá un obj
				$dir_envio_express = $this->direccion_envio_model->get_pago_express($id_cliente);		//devolverá un obj
				
				//dirección de facturación queda excluida de esta validación, se preguntará en el resumen de la orden
				$dir_facturacion_express = $this->direccion_facturacion_model->get_pago_express($id_cliente);	//devolverá un obj
				$razon_social_express = $this->direccion_facturacion_model->get_pago_express_rs($id_cliente);	//devolverá un obj
				
				//se crea el objeto con la información del pago exprés, a través de los consecutivos
				$pago_express = new Pago_Express($forma_pago_express->consecutivo, $dir_envio_express->consecutivo, $dir_facturacion_express->consecutivo, $razon_social_express->consecutivo);					
					
				//obtener el array de lo que se subirá a sesion
				$flujo_pago_express = $pago_express->definir_destino_inicial($requiere_envio);
				
				//Colocar en sesión lo necesario
				if (array_key_exists('tarjeta', $flujo_pago_express)) {
					//$this->session->set_userdata('tarjeta', $pago_express->get_forma_pago());
					$this->session->set_userdata('tarjeta', $forma_pago_express->consecutivo);
				}
				
				if (array_key_exists('dir_envio', $flujo_pago_express)) {
					//$this->session->set_userdata('dir_envio', $pago_express->get_dir_envio());
					$this->session->set_userdata('dir_envio', $dir_envio_express->consecutivo);
				}
				
				if (array_key_exists('dir_facturacion', $flujo_pago_express) && array_key_exists('razon_social', $flujo_pago_express)) {
					$this->session->set_userdata('direccion_f', $dir_facturacion_express->consecutivo);
					$this->session->set_userdata('razon_social', $razon_social_express->consecutivo);
					$this->session->set_userdata('requiere_factura', 'si');
				}
				
				//Sólo se usará el objeto de Pago Exprés en este controlador
				return $pago_express->get_destino();
			} else {
				//enviar a página de mensaje "La promoción que solicitó ya no existe...etc."
				return "mensaje/".md5(1);
			}
			
		} else {	//IF trae promociones
			//enviar a página de mensaje "La promoción que solicitó ya no existe...etc."
			return "mensaje/".md5(1);
		}
	}
	
	/**
	 * regenerar el id de la sesión y re-asignarlo
	 */
	private function cambiar_session() {
		//regenerar el id de la sesión y asignar uno nuevo
		session_regenerate_id();
		
		$new_id = session_id();
		
		$this->session->set_userdata('session_id', $new_id);
		/*echo "<pre>";
		print_r($this->session->all_userdata());
		echo "<pre>";
		exit();*/
	}
	
	private function crear_sesion($id_cliente, $nombre, $email)
	{
		//se crea la nueva
		$array_session = array(
			'logged_in' => TRUE,
			'username' 	=> $nombre,
			'id_cliente'=> $id_cliente,
			'email' 	=> $email
		);
		//creación de la sesión
		$this->session->set_userdata($array_session);
	}
	
	public function cargar_vista($folder, $page, $data)
	{	
		//Para automatizar un poco el desplieguee
		$this->load->view('templates/header', $data);
		$this->load->view('templates/menu.html', $data);		
		$this->load->view($folder.'/'.$page, $data);
		$this->load->view('templates/footer', $data);
	}
	
	/*
	 * Verifica la sesión del usuario
	 * 
	 * */
	private function redirect_cliente_invalido($revisar = 'id_cliente', $destino = 'login', $protocolo = 'http://') {
		if (!$this->session->userdata($revisar)) {
			//$url = $protocolo . BASE_URL . $destination; // Define the URL.
			$url = site_url($destino); // Define the URL.
			header("Location: $url");
			exit(); // Quit the script.
		}
	}
	
	public function tiempo_desbloqueo($fecha_lock){
		$seg = substr($fecha_lock,17,2);
		$min = substr($fecha_lock,14,2);
		$hor = substr($fecha_lock,11,2);
		$dia = substr($fecha_lock,8,2);
		$mes = substr($fecha_lock,5,2);
		$ano = substr($fecha_lock,0,4);
		
		//                   Horas Minutos Segundos mes dia año
		$fecha_lock_unix = mktime($hor,$min,$seg,$mes,$dia,$ano);
		$hora_unix = mktime(mdate('%h'), mdate('%i'), mdate('%s'), mdate('%m'), mdate('%d'), mdate('%Y'));
		// Se suman 30 minutos a la hora y fecha actual		
		$str=strtotime('+30 minutes',$fecha_lock_unix);
												
		if ($str <= $hora_unix) {
			return TRUE;
		} else {
			return FALSE;
		}	
	}
	
	public function consulta_mail() {
		if (filter_var($_GET['mail'], FILTER_VALIDATE_EMAIL)) {
			$res = $this->login_registro_model->verifica_registro_email($_GET['mail']);
			$value['mail'] = $res->num_rows();
			echo json_encode($value);
		}
		else{
			$value['mail'] = 0;
			echo json_encode($value);
		}	
	}
	
}

/* End of file login.php */
/* Location: ./application/controllers/login.php */