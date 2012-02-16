<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Registro extends CI_Controller {

	var $title = 'Inicio de Sesi&oacute;n'; 		// Capitalize the first letter
	var $subtitle = 'Registro de  Cliente'; 	// Capitalize the first letter
	var $registro_errores = array();
	
	function __construct()
    {
        // Call the Model constructor
        parent::__construct();
		
		//cargar el modelo en el constructor
		$this->load->model('login_registro_model', 'modelo', true);
		//la sesion se carga automáticamente
    }
	
	public function index()
	{

		$data['title'] = $this->title;
		$data['subtitle'] = $this->subtitle;
		//echo 'Session: '.$this->session->userdata('id_cliente');
		
		if ($_POST)
		{	
			//$cliente_info = array();		
			$cliente_info = $this->get_datos_login();
			
			if(empty($this->registro_errores)) {			//verificar la existencia de email y password.
				$email_registrado = $this->modelo->verifica_registro_email($cliente_info['email']);
				if($email_registrado->num_rows() == 0) {	//email no está registrado
					
					$cliente_info['id_clienteIn'] = $this->modelo->next_cliente_id() + 1;	//id del cliente
					
					if($this->registrar_cliente($cliente_info)) {						//registro exitoso	
						$this->crear_sesion($cliente_info['id_clienteIn'], $cliente_info['salutation']);	//crear sesion,
						$url = $this->config->item('base_url').'/index.php/forma_pago/'; 
						header("Location: $url");
						//exit();
					} else {
						$this->registro_errores['user_reg'] = "No se pudo realizar el registro en el sistema";
						$_POST = array();
					}
				} else {
					$this->registro_errores['user_reg'] = "Parece que tu correo ha sido registrado";
				}
			} else {
				$this->registro_errores['user_reg'] = "Revisar los campos";
			}
		}
		
		$data['registro_errores'] = $this->registro_errores;
		
		//echo var_dump($data)."<br/>pass ".$_POST['password']."<br/>tipo ".$_POST['tipo_inicio'];
		
		$this->cargar_vista('', 'registro', $data);
	}
	
	private function crear_sesion($id_cliente, $nombre)
	{
		$array_session = array(
			'logged_in' => TRUE,
			'username' 	=> $nombre,
			'id_cliente'=> $id_cliente
		);
		//creacion de la sessión
		$this->session->set_userdata($array_session);
	}
	
	private function registrar_cliente($cliente = array())
	{
		//$cliente_id = $this->modelo->next_cliente_id();
		//$cliente['id_clienteIn'] = $cliente_id;
		
		//var_dump($cliente);		
		//exit();
		return $this->modelo->registrar_cliente($cliente);
	}
	
	private function get_datos_login()
	{
		$datos = array();
		
		if(array_key_exists('txt_nombre', $_POST)) {
			if(preg_match('/^[A-Z \'.-áéíóúÁÉÍÓÚÑñ]{2,30}$/i', $_POST['txt_nombre'])) { 
				$datos['salutation'] = $_POST['txt_nombre'];
			} else {
				$this->registro_errores['txt_nombre'] = 'Ingresa tu nombre por favor';
			}
		}
		if(array_key_exists('txt_apellidoPaterno', $_POST)) {
			if(preg_match('/^[A-Z \'.-áéíóúÁÉÍÓÚÑñ]{2,30}$/i', $_POST['txt_apellidoPaterno'])) { 
				$datos['fname'] = $_POST['txt_apellidoPaterno'];
			} else {
				$this->registro_errores['txt_apellidoPaterno'] = 'Ingresa tu apellido correctamente';
			}
		}
		if(array_key_exists('txt_apellidoMaterno', $_POST)) {
			if(preg_match('/^[A-Z \'.-áéíóúÁÉÍÓÚÑñ]{2,30}$/i', $_POST['txt_apellidoMaterno'])) {
				$datos['lname'] = $_POST['txt_apellidoMaterno'];
			} else {
				$this->registro_errores	['txt_apellidoMaterno'] = 'Ingresa tu apellido correctamente';
			}
		}
		if (filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
			$datos['email'] = htmlspecialchars(trim($_POST['email']));
		} else {
			$this->registro_errores['email'] = 'Ingrese una direcci&oacute;n v&aacute;lida.';
		}
		
		if (preg_match ('/^(\w*(?=\w*\d)(?=\w*[a-z])(?=\w*[A-Z])\w*){6,20}$/', $_POST['password']) ) {
			if ($_POST['password'] == $_POST['password_2']) {
				$datos['password'] = htmlspecialchars(trim($_POST['password']));
			} else {
				$this->registro_errores['password_2'] = 'Tus contrase&ntilde;as no coincden';
			}
		} else {
			$this->registro_errores['password'] = 'Por favor ingresa una contrase&ntilde;a v&aacute;lida';
		}
		
		return $datos;
	}
	
	private function cargar_vista($folder, $page, $data)
	{	
		//Para automatizar un poco el desplieguee
		$this->load->view('templates/header', $data);
		$this->load->view($folder.'/'.$page, $data);
		$this->load->view('templates/footer', $data);
	}
}

/* End of file registro.php */
/* Location: ./application/controllers/registro.php */