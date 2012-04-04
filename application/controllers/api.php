<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Api extends CI_Controller {

	var $title = 'Iniciar Sesi&oacute;n'; 		// Capitalize the first letter
	var $subtitle = 'Iniciar Sesi&oacute;n Segura'; 	// Capitalize the first letter
	var $login_errores = array();
	
			
	function __construct(){
        // Call the Model constructor
        parent::__construct();		
		$this->load->model('api_model', 'modelo', true);	
		$this->session->set_userdata('promociones', array());			
    }
	
	public function index(){												
	}
	
	public function listar($sitio= "", $canal= "", $promocion= "", $formato= ""){								
		$data['title']='Promociones';		
		$data['listar'] = TRUE;
		$data['detalle'] = FALSE;					
		
		$segm=$this->uri->total_segments();
		if($segm==2){
			$cad=$this->uri->segment(2);
			if(($cad=="json")||($cad=="xml")||($cad=="html")){
				$sitio="";
				$formato=$cad;
			}
		}
		if($segm==3){
			$cad=$this->uri->segment(3);
			if(($cad=="json")||($cad=="xml")||($cad=="html")){
				$canal="";
				$formato=$cad;
			}
		}
		if($segm==4){
			$cad=$this->uri->segment(4);
			if(($cad=="json")||($cad=="xml")||($cad=="html")){
				$promocion="";
				$formato=$cad;
			}
		}		
					
		
		if((empty($sitio)) && (empty($canal)) && (empty($promocion))){			
			$sitios=$this->modelo->obtener_sitios();
			foreach($sitios->result_array() as $siti){
				$res=$this->modelo->obtener_sitio($siti['id_sitioSi']);
				$sit=$res->row();				
				$data['sitios'][]=array('urlVc'=>$sit->urlVc, 'id_sitioSi'=>$sit->id_sitioSi);
			}					
			
			$this->formato($formato, $data);			
		}
		
		else if(($sitio) && (empty($canal)) && (empty($promocion))){
			$canales=$this->modelo->obtener_canales_sitio($sitio);
			foreach($canales->result_array() as $cana){
				$res=$this->modelo->obtener_canal($cana['id_canalSi']);
				$can=$res->row();
				$data['canales'][]=array('id_canalSi'=>$can->id_canalSi , 'descripcionVc'=>$can->descripcionVc, 'addKeyVc'=>$can->addKeyVc);				
			}
			$this->formato($formato, $data);					
		}
		else if(($sitio) && ($canal) && (empty($promocion))){			
			$promociones=$this->modelo->obtener_promociones_canales_sitio($sitio, $canal);			
			foreach($promociones->result_array() as $promo){
				$res=$this->modelo->obtener_promocion($promo['id_promocionIn']);
				$prom=$res->row();
				$data['promociones'][]=array('id_promocionIn'=>$prom->id_promocionIn, 
						                     'descripcionVc'=>$prom->descripcionVc, 
						                     'inicio_promocionDt'=>$prom->inicio_promocionDt,
						                     'fin_promocionDt'=>$prom->fin_promocionDt,
						                     'terminoVc'=>$prom->terminoVc,
						                     'fecha_creacionDt'=>$prom->fecha_creacionDt,
						                     'email_usuario_altaVc'=>$prom->email_usuario_altaVc,
						                     'precioF'=>$prom->precioF
											 );				
			}			
			$this->formato($formato, $data);						
		}
		
		else if(($sitio) && ($canal) && ($promocion)){
						
			$this->detalle($sitio, $canal, $promocion, $formato);
		}			
	}
	
	public function detalle($sitio, $canal, $promocion, $formato){
	  	
    	$data['detalle'] = TRUE;	  	
    	$data['listar'] = FALSE;	  	
    	$data['title']='Promociones';  
	  	    	
	  	
    	if(!empty($sitio)){	  
	  		$rsitio= $this->modelo->obtener_sitio($sitio);	
			if($rsitio->num_rows()!=0){
				$data['sitio']=$rsitio->row();
			}		  
    	}
		
		if(!empty($canal)){
	  		$rcanal= $this->modelo->obtener_canal($canal);	
			if($rcanal->num_rows()!=0){
				$data['canal']=$rcanal->row();
			}      	  	
    	}                    
	  	
    	if(!empty($promocion)){	  
	  		$rpromocion= $this->modelo->obtener_promocion($promocion);	
			if($rpromocion->num_rows()!=0){
				$data['promocion']=$rpromocion->row();
				$rarticulos= $this->modelo->obtener_articulos($promocion);
				if($rarticulos->num_rows()!=0){
					$data['articulos']=$rarticulos->result_array();
				}
			}      		
    	}		
		$this->session->set_userdata('promociones', $data);
		$this->formato($formato,$data);	  	       		
		//redirect('login'); 			  	    		  		
  }  
		
			
	private function formato($formato, $data){		
		if((empty($formato)) || ($formato=='json')){
			$this->output->set_content_type('application/json')->set_output(json_encode($data));			
		}
		else{
			if($formato=="xml"){
																				
				$response ='<?xml version="1.0" encoding="utf-8"?>';
				
				if(!empty($data['sitios'])){
					if(is_array($data['sitios'])){
						$response .= "<sitio>";	
						foreach($data['sitios'] as $sit){
							$response .="<id_sitioSi>";
							$response .=$sit['id_sitioSi'];
							$response .="</id_sitioSi>";
							
							$response .="<urlVc>";
							$response .=$sit['urlVc'];
							$response .="</urlVc>";					
						}
						$response .='</sitio>';		
					}					
				}
				if(!empty($data['canales'])){
					if(is_array($data['canales'])){
						$response .= "<canal>";	
						foreach($data['canales'] as $can){
							$response .="<id_canalSi>";
							$response .=$can['id_canalSi'];
							$response .="</id_canalSi>";
							
							$response .="<descripcionVc>";
							$response .=$can['descripcionVc'];
							$response .="</descripcionVc>";					
						}
						$response .='</canal>';		
					}					
				}
				if(!empty($data['promociones'])){
					if(is_array($data['promociones'])){
						$response .= "<promocion>";	
						foreach($data['promociones'] as $prom){
							$response .="<id_promocionIn>";
							$response .=$prom['id_promocionIn'];
							$response .="</id_promocionIn>";
							
							$response .="<descripcionVc>";
							$response .=$prom['descripcionVc'];
							$response .="</descripcionVc>";					
						}
						$response .='</promocion>';		
					}					
				}
				
				if(!empty($data['sitio'])){
					$response .="<detalle>";										
					$response .="<sitio>";
					$response .=$data['sitio']->urlVc;
					$response .="</sitio>";
					$response .="<canal>";
					$response .=$data['canal']->id_canalSi;
					$response .="</canal>";
					$response .="<promocion>";
					$response .=$data['promocion']->descripcionVc;
					$response .="</promocion>";					
					
					foreach($data['articulos'] as $articulo){
						$response .="<articulo>";
						$response .=$articulo['tipo_productoVc'];
						$response .="</articulo>";
					}																
											 
					$response .="</detalle>";			 										
				}					
				$this->output->set_content_type("content-type: text/xml")->set_output($response);																									        		        								                																																				
			}
			else if($formato=="html"){				
				$this->cargar_vista('', 'api', $data);	
			}															
		}		
	}		
			
	private function cargar_vista($folder, $page, $data){	
		//Para automatizar un poco el desplieguee
		$this->load->view('templates/header', $data);
		$this->load->view($folder.'/'.$page, $data);
		$this->load->view('templates/footer', $data);
	}
	
}
