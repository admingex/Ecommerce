<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Logout extends CI_Controller {

	var $title = 'Cerrar Sesi&oacute;n'; 		// Capitalize the first letter
	var $subtitle = 'Terminar Sesi&oacute;n Segura'; 	// Capitalize the first letter
	var $logout_errores = array();
	
	function __construct()
    {
        // Call the Model constructor
        parent::__construct();
		
		//cargar el modelo en el constructor, servirá para la sesión en DB
		$this->load->model('login_registro_model', 'modelo', true);
		//la sesion se carga automáticamente
    }
	
	public function index()
	{
		$this->cerrar_session();
		//$this->load->view('login');
		
		//$this->cargar_vista('', 'login', $data);
	}
	
	private function cerrar_session() {
		//destruir la session
		$this->session->sess_destroy();
		
		//redirect to login
		$url = $this->config->item('base_url').'/index.php/login/';
		header("Location: $url");
		exit();
	}
}

/* End of file logout.php */
/* Location: ./application/controllers/logout.php */