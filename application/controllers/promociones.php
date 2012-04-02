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
		
		//echo json_encode($data);
		$this->session->set_userdata('promociones', $data);			
		$this->cargar_vista('', 'promociones', $data);
		//redirect('login');									
		
	}
	
	public function detalle(){
	  	
    	$data['detalle'] = TRUE;	  	
    	$data['listar'] = FALSE;	  	
    	$data['title']='Promociones';  
	  	
    	$get = $this->uri->uri_to_assoc();
	  	
    	if(!empty($get['sitio'])){	  
	  		$rsitio= $this->modelo->obtener_sitio($get['sitio']);	
			if($rsitio->num_rows()!=0){
				$data['sitio']=$rsitio->row();
			}		  
    	}
		
		if(!empty($get['canal'])){
	  		$rcanal= $this->modelo->obtener_canal($get['canal']);	
			if($rcanal->num_rows()!=0){
				$data['canal']=$rcanal->row();
			}      	  	
    	}                    
	  	
    	if(!empty($get['promocion'])){	  
	  		$rpromocion= $this->modelo->obtener_promocion($get['promocion']);	
			if($rpromocion->num_rows()!=0){
				$data['promocion']=$rpromocion->row();
				$rarticulos= $this->modelo->obtener_articulos($get['promocion']);
				if($rarticulos->num_rows()!=0){
					$data['articulos']=$rarticulos->result_array();
				}
			}      		
    	}	  	        
	  	
    	$this->cargar_vista('', 'promociones', $data);	  	
  }  
	
	public function listar(){
		$data['title']='Promociones';		
		$data['listar'] = TRUE;
		$data['detalle'] = FALSE;
		$get = $this->uri->uri_to_assoc();	
		
		if((empty($get['sitio'])) && (empty($get['canal'])) && (empty($get['promocion']))){			
			$sitios=$this->modelo->obtener_sitios();
			foreach($sitios->result_array() as $sitio){
				$res=$this->modelo->obtener_sitio($sitio['id_sitioSi']);
				$sit=$res->row();				
				$data['sitios'][]=array('urlVc'=>$sit->urlVc, 'id_sitioSi'=>$sit->id_sitioSi);
			}					
			$this->cargar_vista('', 'promociones', $data);
			
		}
		
		else if(($get['sitio']) && (empty($get['canal'])) && (empty($get['promocion']))){
			$canales=$this->modelo->obtener_canales_sitio($get['sitio']);
			foreach($canales->result_array() as $canal){
				$res=$this->modelo->obtener_canal($canal['id_canalSi']);
				$can=$res->row();
				$data['canales'][]=array('id_canalSi'=>$can->id_canalSi , 'descripcionVc'=>$can->descripcionVc, 'addKeyVc'=>$can->addKeyVc);				
			}
			$this->cargar_vista('', 'promociones', $data);	
		}
		else if(($get['sitio']) && ($get['canal']) && (empty($get['promocion']))){			
			$promociones=$this->modelo->obtener_promociones_canales_sitio($get['sitio'], $get['canal']);			
			foreach($promociones->result_array() as $promocion){
				$res=$this->modelo->obtener_promocion($promocion['id_promocionIn']);
				$prom=$res->row();
				$data['promociones'][]=array('id_promocionIn'=>$prom->id_promocionIn, 'descripcionVc'=>$prom->descripcionVc);				
			}
			$this->cargar_vista('', 'promociones', $data);
		}
		
		else if(($get['sitio']) && ($get['canal']) && ($get['promocion'])){
			echo "aqui todos";
			$this->detalle();
		}
		//$data['articulos']=$this->modelo->obtener_promociones();
				
														
	}
			
	private function cargar_vista($folder, $page, $data){	
		//Para automatizar un poco el desplieguee
		$this->load->view('templates/header', $data);
		$this->load->view($folder.'/'.$page, $data);
		$this->load->view('templates/footer', $data);
	}
	
}
