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
		
		/*		
		$this->output->set_header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
		$this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
		$this->output->set_header('Pragma: no-cache');
		$this->output->set_header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		*/
		$this->output->nocache();
		
		if ($this->session->userdata('destino')) {
			//$this->session->userdata('destino');
			redirect($this->session->userdata('destino'), 'location', 303);
			exit();
		}		
			//header("Location: $destino");
		
		
		/*
		$this->load->driver('cache');
		header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
		$this->cache->clean();*/
		//echo "exito clean cache: " . $this->cache->clean();
		
		//cargar el modelo en el constructor
		$this->load->model('login_registro_model', 'login_registro_model', true);
		//cargar los modelos para revisar el pago exprés
		$this->load->model('forma_pago_model');
		$this->load->model('direccion_envio_model');
		$this->load->model('direccion_facturacion_model');
		
		$this->api = new Api();
    }
	
	public function index()
	{		
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
					$mail_cte=$this->login_registro_model->verifica_registro_email($this->email);																		
					if($mail_cte->num_rows()!=0){
						$fecha_lock = $mail_cte->row()->LastLockoutDate;
						$id_cliente = $mail_cte->row()->id_clienteIn;						
						if($fecha_lock!='0000-00-00 00:00:00'){
							if($this->tiempo_desbloqueo($fecha_lock)){
								$this->login_registro_model->desbloquear_cuenta($id_cliente);
								$t= mdate('%Y/%m/%d %h:%i:%s',time());								
								$this->password_model->guarda_actividad_historico($id_cliente, '', self::$TIPO_ACTIVIDAD['DESBLOQUEO'], $t);							
							}
						}
																									
						$num_intentos = $this->login_registro_model->obtiene_numero_intentos($id_cliente);	
						
						if($num_intentos<3){
							$resultado = $this->login_registro_model->verifica_cliente($this->email, $this->password);							
							if ($resultado->num_rows() > 0) {
								//Reguardar la información de la promoción
								
								//destruir la sesión de PHP y mandar la información en la sesión de CI con un nuevo ID
								$this->cambiar_session();
								
								//encryptar login y pass y guardarlo en session											
								$cliente = $resultado->row();
								$dl = $this->api->encrypt($cliente->email."|".$this->password, $this->api->key);
								$this->session->set_userdata('datos_login',$dl);
								
								//se crea la sessión con la información del cliente
								$this->crear_sesion($cliente->id_cliente, $cliente->nombre, $this->email);	//crear sesion
								
								//por defaulr no se considera la dirección d facturación
								$datars = array('requiere_factura' => 'no');
								$this->session->set_userdata($datars);
								
								//detecta a donde va el ususario a partir de la promoción que se tiene en sesión
								$destino = $this->obtener_destino($cliente->id_cliente);						
								
								//colocar en sessión el destino
								$data_destino = array('destino' => $destino);
								$this->session->set_userdata($data_destino);
								
								//Flujo
								redirect($destino);
							} 
							else{
								$this->login_errores['user_login'] = "Hubo un error con la combinación ingresada de correo y contraseña.<br />Por favor intenta de nuevo.";																			
								$t= mdate('%Y/%m/%d %h:%i:%s',time());								
								$this->password_model->guarda_actividad_historico($id_cliente, $this->password, self::$TIPO_ACTIVIDAD['ACCESO_INCORRECTO'], $t);							
								$this->login_registro_model->suma_intento_fallido($id_cliente, $num_intentos, $t);	
							}
						}
						else{
							if($num_intentos==3){
								$t= mdate('%Y/%m/%d %h:%i:%s',time());	
								$this->password_model->guarda_actividad_historico($id_cliente, '', self::$TIPO_ACTIVIDAD['BLOQUEO'], $t);
								$this->login_registro_model->suma_intento_fallido($id_cliente, $num_intentos, $t);	
							}
							$this->login_errores['user_login'] = "Ha excedido el número máximo de intentos permitidos para iniciar sesión.<br />
							                                      Su cuenta permanecerá bloqueada por 30 minutos";
							
						}						
					}					
					else {						
						$this->login_errores['user_login'] = "Hubo un error con la combinación ingresada de correo y contraseña.<br />Por favor intenta de nuevo.";																																					
						//$data['mensaje'] = "Correo o contrase&ntilde;a incorrectos" ;
					}
				}
			}
		}		
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
			
			//detecta a donde va el ususario a partir de la promoción que se tiene en sesión
			$destino = $this->obtener_destino($cliente->id_cliente);						
			
			//colocar en sessión el destino
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
		
		$respromo = $this->api->obtener_detalle_promo($this->session->userdata('id_sitio'), $this->session->userdata('id_canal'), $this->session->userdata('id_promocion'));
		//echo "res_promo: ". var_dump($respromo);
		if ($respromo) {
			foreach ($respromo['articulos'] as $res) {
			$temp[] = array('tarifaDc' 			=> 	$res['tarifaDc'], 
							'tipo_productoVc' 	=> 	$res['tipo_productoVc'], 
							'medio_entregaVc'	=>	$res['medio_entregaVc'], 
							'monedaVc'	=>	$res['monedaVc'], 
							'requiere_envioBi'	=>	(bool)$res['requiere_envioBi']);
			}
			
			$this->session->set_userdata('sitio', $respromo['sitio']);
			$this->session->set_userdata('promocion', $respromo['promocion']);
			$this->session->set_userdata('articulos', $temp);				
			$this->session->set_userdata('promociones', TRUE);
			
			//echo "<pre>";
			//var_dump($this->session->all_userdata());
			//echo "</pre>";
			
			$forma_pago_express = $this->forma_pago_model->get_pago_express($id_cliente);		//devolverá un obj
			$dir_envio_express = $this->direccion_envio_model->get_pago_express($id_cliente);		//devolverá un obj
			
			//dirección de facturación queda excluida de esta validación, se preguntará en el resumen de la orden
			$dir_facturacion_express = $this->direccion_facturacion_model->get_pago_express($id_cliente);	//devolverá un obj
			$razon_social_express = $this->direccion_facturacion_model->get_pago_express_rs($id_cliente);	//devolverá un obj
			
			//se crea el objeto con la informción del pago exprés, a través de los consecutivos
			$pago_express = new Pago_Express($forma_pago_express->consecutivo, $dir_envio_express->consecutivo, $dir_facturacion_express->consecutivo, $razon_social_express->consecutivo);
			
			//revisar si requiere forma de envío
			$requiere_envio = FALSE;		
			
			if ($this->session->userdata('promociones')) {			
				$articulos = $this->session->userdata('articulos');
				
				foreach($articulos as $articulo) {				
					if ($articulo['requiere_envioBi'] != FALSE) {
						$requiere_envio = TRUE;
						//echo "requiere envio";
						break;
					}
				}
			}
			
			//Requiere envío en sesión
			$this->session->set_userdata('requiere_envio', $requiere_envio);
				
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
			
			
			//**Obsoleto**//
			//se coloca el objeto en sesión para ocuparlo en los demás controladores
			//$this->session->set_userdata('pago_express', $pago_express);
			//***//
			
			//Sólo se usará el objeto de Pago Exprés en este controlador por el momento.
			return $pago_express->get_destino();	
		} else {
			//enviar a página de mensaje "La promoción que solicitó ya no existe...etc."
			return "mensaje/".md5(1);
		}
	}
	
	/**
	 * regenerar el id de la sesión y re-asignarlo
	 */
	 private function cambiar_session() {
		//regenerar el id de la sesión y asignarlo
		session_regenerate_id();
		
		$new_id = session_id();
		
		$this->session->set_userdata('session_id', $new_id);
		/*
		echo "<pre>";
		print_r($this->session->all_userdata());
		echo "<pre>";
		
		exit();
		 * */		
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
		
		//                   Horas Minutos Segundos mes dia ano
		$fecha_lock_unix = mktime($hor,$min,$seg,$mes,$dia,$ano);
		$hora_unix = mktime(mdate('%h'), mdate('%i'), mdate('%s'), mdate('%m'), mdate('%d'), mdate('%Y'));
		// Se suman 30 minutos a la hora y fecha actual		
		$str=strtotime('+30 minutes',$fecha_lock_unix);
												
		if($str<=$hora_unix){
			return TRUE;
		}
		else{
			return FALSE;
		}	
	}
	
	public function consulta_mail(){
		//$value['mail']=$_GET['mail'];
		$res=$this->login_registro_model->verifica_registro_email($_GET['mail']);
		$value['mail']=$res->num_rows();
		echo json_encode($value);			
	}
}

/* End of file login.php */
/* Location: ./application/controllers/login.php */