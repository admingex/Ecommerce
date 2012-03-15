<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Promociones extends CI_Controller {

	var $title = 'Iniciar Sesi&oacute;n'; 		// Capitalize the first letter
	var $subtitle = 'Iniciar Sesi&oacute;n Segura'; 	// Capitalize the first letter
	var $login_errores = array();
	
			
	function __construct(){
        // Call the Model constructor
        parent::__construct();		
		$this->load->model('promociones_model', 'modelo', true);				

    }
	
	public function index(){
		$this->listar();
	}
	
	public function listar(){
		$data['title']='Promociones';		
		$data['listar'] = TRUE;
		$data['detalle'] = FALSE;
		$data['articulos']=$this->modelo->obtener_promociones();
		$this->cargar_vista('', 'promociones', $data);				
	}
	
	public function detalle(){
		$data['detalle'] = TRUE;
		$data['listar'] = FALSE;
		$data['title']='Promociones';	
		$get = $this->uri->uri_to_assoc();
		if(!empty($get['sitio'])){
			$data['id_sitio']=$get['sitio'];	
		}										
		if(!empty($get['promocion'])){
			$data['id_promocion']=$get['promocion'];	
		}
		if(!empty($get['canal'])){
			$data['id_canal']=$get['canal'];	
		}		
		$this->cargar_vista('', 'promociones', $data);
	}	
		
	private function cargar_vista($folder, $page, $data){	
		//Para automatizar un poco el desplieguee
		$this->load->view('templates/header', $data);
		$this->load->view($folder.'/'.$page, $data);
		$this->load->view('templates/footer', $data);
	}
	
}
