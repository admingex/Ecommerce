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
	
	function __construct()
    {
        // Call the Model constructor
        parent::__construct();
		
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
		
		//se guarda la promoción en la session
		if (array_key_exists("promo_id", $_GET)) {
			$id_promocion = $_GET['promo_id'];
			$this->session->set_userdata("id_promocion", $id_promocion);
		}
		
		if ($_POST)
		{
			//si es usuario nuevo, se debe registrar
			if (array_key_exists('tipo_inicio', $_POST) && $_POST['tipo_inicio'] == $this::NUEVO) {
				$url = $this->config->item('base_url').'/index.php/registro/';
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
					
					$resultado = $this->login_registro_model->verifica_cliente($this->email, $this->password);
					if ($resultado->num_rows() > 0) {
						
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
					} else {
						$this->login_errores['user_login'] = "Correo o contrase&ntilde;a incorrectos";
						//$data['mensaje'] = "Correo o contrase&ntilde;a incorrectos" ;
					}
				} else {
					$this->login_errores['user_login'] = "Revisar los campos";
				}
			}
		}
		
		$data['login_errores'] = $this->login_errores;
		$this->cargar_vista('', 'login', $data);
	}
	
	private function get_datos_login($value='')
	{
		$datos = array();
		
		if (filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
			$datos['email'] = htmlspecialchars(trim($_POST['email']));
		} else {
			$this->login_errores['email'] = 'Ingrese una direcci&oacute;n v&aacute;lida.';
		}
		
		if (array_key_exists('tipo_inicio', $_POST) && $_POST['tipo_inicio'] == 'registrado') {
			if (!empty($_POST['password'])) {
				$datos['password'] = htmlspecialchars(trim($_POST['password']));
			} else {
				$this->login_errores['password'] = 'Ingrese una contrase&ntilde;a.';
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
			
			//se crea el objeto con la informción del pago exprés, a través de los consecutivos
			$pago_express = new Pago_Express($forma_pago_express->consecutivo, $dir_envio_express->consecutivo, $dir_facturacion_express->consecutivo);
			
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
	
	private function crear_sesion($id_cliente, $nombre, $email)
	{
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
		$this->load->view($folder.'/'.$page, $data);
		$this->load->view('templates/footer', $data);
	}
}

/* End of file login.php */
/* Location: ./application/controllers/login.php */