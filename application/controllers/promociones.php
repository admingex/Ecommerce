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
		$data['detalle'] = TRUE;
		$data['listar'] = FALSE;
		$data['title']='Promocion';	
						
		if(!empty($_GET['sitio'])){
			$rsitio= $this->modelo->obtener_sitio($_GET['sitio']);	
			if($rsitio->num_rows()!=0){
				$data['sitio']=$rsitio->row();
			}							
		}										
		
		if(!empty($_GET['canal'])){
			$rcanal= $this->modelo->obtener_canal($_GET['canal']);	
			if($rcanal->num_rows()!=0){
				$data['canal']=$rcanal->row();
			}	
		}
		
		if(!empty($_GET['promocion'])){
			$rpromocion= $this->modelo->obtener_promocion($_GET['promocion']);	
			if($rpromocion->num_rows()!=0){
				$data['promocion']=$rpromocion->row();
				$rarticulos= $this->modelo->obtener_articulos($_GET['promocion']);
				if($rarticulos->num_rows()!=0){
					$data['articulos']=$rarticulos->result_array();
				}
			}				
		}
		
		echo json_encode($data);
		$this->cargar_vista('', 'promociones', $data);									
		
	}
	
	public function listar(){
		$data['title']='Promociones';		
		$data['listar'] = TRUE;
		$data['detalle'] = FALSE;
		$data['articulos']=$this->modelo->obtener_promociones();
		$this->cargar_vista('', 'promociones', $data);				
	}
	
		
	private function cargar_vista($folder, $page, $data){	
		//Para automatizar un poco el desplieguee
		$this->load->view('templates/header', $data);
		$this->load->view($folder.'/'.$page, $data);
		$this->load->view('templates/footer', $data);
	}
	
}
