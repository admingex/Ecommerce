<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Login extends CI_Controller {

	var $title = 'Iniciar Sesi&oacute;n'; 		// Capitalize the first letter
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
		$this->load->model('login_registro_model', 'modelo', true);

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
					
					$resultado = $this->modelo->verifica_cliente($this->email, $this->password);
					if ($resultado->num_rows() > 0) {
						$cliente = $resultado->row();
						
						//echo "<h1>Usuario $cliente->id_cliente logeado: $cliente->nombre</h1>";
						//exit();
						$this->crear_sesion($cliente->id_cliente, $cliente->nombre);	//crear sesion,
						/*
						$url = $this->config->item('base_url').'/index.php/forma_pago/'; 
						header("Location: $url");*/
						redirect('forma_pago');
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
	
	private function crear_sesion($id_cliente, $nombre)
	{
		$array_session = array(
			'logged_in' => TRUE,
			'username' 	=> $nombre,
			'id_cliente'=> $id_cliente
		);
		//creación de la sesión
		$this->session->set_userdata($array_session);
	}
	
	private function cargar_vista($folder, $page, $data)
	{	
		//Para automatizar un poco el desplieguee
		$this->load->view('templates/header', $data);
		$this->load->view($folder.'/'.$page, $data);
		$this->load->view('templates/footer', $data);
	}
}

/* End of file login.php */
/* Location: ./application/controllers/login.php */