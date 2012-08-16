<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Api extends CI_Controller {

	var $title = 'Iniciar Sesi&oacute;n'; 		// Capitalize the first letter
	var $subtitle = 'Iniciar Sesi&oacute;n Segura'; 	// Capitalize the first letter
	var $login_errores = array();	
	var $key="";		
			
	function __construct(){
        // Call the Model constructor
        parent::__construct();		
		$this->load->model('api_model', 'api_model', true);
		$this->key='AC35-4564-AE4D-0B881031F295';										
    }
	
	public function index(){												
	}
	
	public function listar($sitio= "", $canal= "", $promocion= "", $formato= ""){
		//limpiar datos en session 
		foreach (array_keys($this->session->userdata) as $key){			
			if(($key!='ip_address') && ($key!='session_id') && ($key!='user_agent') && ($key!='last_activity')){
				$this->session->unset_userdata($key);	
			}			    		
		} 
													
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
			//buscar los articulos con la clave enviada			
			else if(((!is_numeric($cad))|| (strlen($cad)>2)) && ($_POST) ){				
				//echo $cad;
				//$file_path = '/var/www/html/pagos/application/controllers/log.txt';
				/*
				$file_path = './utils/log.txt';
				$file=fopen($file_path, 'a');
				fwrite($file,json_encode($_POST)."--".$cad."--".time()."--".$_SERVER['REMOTE_ADDR']."\n");
				fclose($file);
				//print_r($_POST);
				*/
				
				echo $this->obtener_url_promo($cad, $_POST);
				exit();				
			}
			
		}
		if($segm==3){			
			if($this->uri->segment(2)=='carrito'){
				$datos_decrypt_url=base64_decode(str_pad(strtr($this->uri->segment(3), '-_', '+/'), strlen($this->uri->segment(3)) % 4, '=', STR_PAD_RIGHT));
				$datos_decrypt=$this->decrypt($datos_decrypt_url, $this->key);
				$datos_decrypt=unserialize($datos_decrypt);
				
				if($_POST){										
					/*
					echo "<pre>";
						print_r($_POST);
						print_r($datos_decrypt);						
					echo "</pre>";
					exit;	
					*/													
					
					if(!empty($_POST['guidx']) && !empty($_POST['guidz'])){
						//obtener el id del sitio por medio del guidx 						
						$ressit = $this->api_model->obtener_sitio_guidx($_POST['guidx']);
						$sitio = $ressit->row()->id_sitioSi;
						//obtengo la llave privada en la DB
						$guidxdb=$this->api_model->obtener_sitio($sitio)->row();						
						//compara si es igual a la que se recibe en post si es igual se guardan los datos en session de lo contrario se niega el acceso									
						if($guidxdb->private_KeyVc==$_POST['guidx']){							
							$this->session->set_userdata(array( 'guidx'=>$_POST['guidx'],
													   			'guidy'=>'{CE5480FD-AC35-4564-AE4D-0B881031F295}',
													   			'guidz'=>$_POST['guidz'],
													   			'promociones'=>$datos_decrypt												   			
													   )
												 );
							/*					 
							echo "<pre>";						
								print_r($this->session->all_userdata());						
							echo "</pre>";								
								
							foreach($this->session->userdata('promociones') as $v){
								echo "<br />".$v['id_sitio']."--".$v['id_canal']."--".$v['id_promocion'];
							}		
							exit;
							*/		
													 							
							// si vienen los datos de login de la tienda manda el formuario de acceso					 								
							if(array_key_exists('datos_login', $_POST)){							
								$dat_log=$this->decrypt($_POST['datos_login'], $this->key);
								$mp=explode('|',$dat_log);
								echo "  <form name='inicio_sesion' action='".site_url('login')."' method='post'>
									    	<input type='text' name='email' value='".$mp[0]."' style='display: none' />
									    	<input type='text' name='tipo_inicio' value='registrado' style='display: none' />
									    	<input type='text' name='password' value='".$mp[1]."' style='display: none' />
									    	<input type='submit' name='enviar' value='Iniciar sesion' style='display: none' />
										</form>";
								echo "<script>document.inicio_sesion.submit();</script>";
							}
							else{
								//si no hay datos de uauario redirige a la tienda a login
								redirect('login');	
							}											 		 											
						}
						else{							
							$this->session->unset_userdata();
							redirect('login');	
						}																 	
					}		
					else{					
						$this->session->unset_userdata();
						redirect('login');					
					}
													
				}												
				exit();
			}
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
		if($formato=="pago"){
		    $formato="";
		}				
		
		if((empty($sitio)) && (empty($canal)) && (empty($promocion))){			
			$sitios=$this->api_model->obtener_sitios();
			foreach($sitios->result_array() as $siti){
				$res=$this->api_model->obtener_sitio($siti['id_sitioSi']);
				$sit=$res->row();				
				$data['sitios'][]=array('urlVc'=>$sit->urlVc, 'id_sitioSi'=>$sit->id_sitioSi);
			}								
			$this->formato($formato, $data);			
		}
		
		else if(($sitio) && (empty($canal)) && (empty($promocion))){			
			$canales=$this->api_model->obtener_canales_sitio($sitio);
			if($canales->num_rows()!=0){
				foreach($canales->result_array() as $cana){
					$res=$this->api_model->obtener_canal($cana['id_canalSi']);
					$can=$res->row();
					$data['canales'][]=array('id_canalSi'=>$can->id_canalSi , 'descripcionVc'=>$can->descripcionVc, 'addKeyVc'=>$can->addKeyVc);				
				}	
			}		
			else{
				$data['error']['sitio']="no existe informacion del sitio";
			}	
			$this->formato($formato, $data);					
		}
		else if(($sitio) && ($canal) && (empty($promocion))){					
			$promocion=$this->api_model->obtener_promociones_canales_sitio($sitio, $canal);
			if($promocion->num_rows()!=0){
				foreach($promocion->result_array() as $promo){
					$res=$this->api_model->obtener_promocion($promo['id_promocionIn']);
					$prom=$res->row();
					$data['promocion'][]=array('id_promocionIn'=>$prom->id_promocionIn, 
						                     'descripcionVc'=>$prom->descripcionVc, 
						                     'inicio_promocionDt'=>$prom->inicio_promocionDt,
						                     'fin_promocionDt'=>$prom->fin_promocionDt,
						                     'terminoVc'=>$prom->terminoVc,
						                     'fecha_creacionDt'=>$prom->fecha_creacionDt,
						                     'email_usuario_altaVc'=>$prom->email_usuario_altaVc,
						                     'precioF'=>$prom->precioF
											 );				
				}				
			}		
			else{				
				$data['error']['canal']='no existe informacion de este canal';
			}									
			$this->formato($formato, $data);						
		}		
		else if(($sitio) && ($canal) && ($promocion)){
			$ultimosegmento=$this->uri->segment($segm);									
			$this->detalle($sitio, $canal, $promocion, $formato, $ultimosegmento);
		}			
	}
	
	public function detalle($sitio, $canal, $promocion, $formato, $ultimosegmento){
	  	
    	$data['detalle'] = TRUE;	  	
    	$data['listar'] = FALSE;	  	
    	$data['title']='Promociones';  
	  	$pago=FALSE;	    
		  
    	if(!empty($sitio)){	  
	  		$rsitio= $this->api_model->obtener_sitio($sitio);	
			if($rsitio->num_rows()!=0){
				$data['sitio']=$rsitio->row();
			}		 
			else{				
				$data['error']['sitio']="no existe informacion de este sitio";
			} 
    	}
		
		if(!empty($canal)){
	  		$rcanal= $this->api_model->obtener_canal($canal);	
			if($rcanal->num_rows()!=0){
				$data['canal']=$rcanal->row();
			}      
			else{
				$data['error']['canal']="no existe informacion de este canal";
			}	  	
    	}                    
	  	
    	if(!empty($promocion)){	  
	  		$rpromocion= $this->api_model->obtener_promocion($promocion);	
			if($rpromocion->num_rows()!=0){
				$data['promocion']=$rpromocion->row();
				$rarticulos= $this->api_model->obtener_articulos($promocion);
				if($rarticulos->num_rows()!=0){
					$data['articulos']=$rarticulos->result_array();
					if($ultimosegmento=="pago"){									
						$pago=TRUE;						
					}
				}
				else{
					unset($data['sitio']);
					unset($data['canal']);
					$data['error']['articulos']="no existen articulos en esta promocion";
				}				
			}      		
			else{
				unset($data['sitio']);
				unset($data['canal']);
				$data['error']['promocion']="no existe informacion de esta promocion";
			}
    	}							  
		if($pago){
			if($_POST){				
				if(!empty($_POST['guidx']) && !empty($_POST['guidz'])){
					//obtengo la llave privada en la DB
					$guidxdb=$this->api_model->obtener_sitio($sitio)->row();
					//compara si es igual a la que se recibe en post si es igual se guardan los datos en session de lo contrario se niega el acceso									
					if($guidxdb->private_KeyVc==$_POST['guidx']){
						$this->session->set_userdata(array( 'guidx'=>$_POST['guidx'],
												   			'guidy'=>'{CE5480FD-AC35-4564-AE4D-0B881031F295}',
												   			'guidz'=>$_POST['guidz'],
												   			'promociones'=>
												   			array(array('id_sitio'=>$sitio, 
												   				  'id_canal'=>$canal, 
												   				  'id_promocion'=>$promocion))	  												   			
												   )
											 );
						// si vienen los datos de login de la tienda manda el formuario de acceso					 								
						if(array_key_exists('datos_login', $_POST)){							
							$dat_log=$this->decrypt($_POST['datos_login'], $this->key);
							$mp=explode('|',$dat_log);
							echo "  <form name='inicio_sesion' action='".site_url('login')."' method='post'>
								    	<input type='text' name='email' value='".$mp[0]."' style='display: none' />
								    	<input type='text' name='tipo_inicio' value='registrado' style='display: none' />
								    	<input type='text' name='password' value='".$mp[1]."' style='display: none' />
								    	<input type='submit' name='enviar' value='Iniciar sesion' style='display: none' />
									</form>";
							echo "<script>document.inicio_sesion.submit();</script>";
						}
						else{
							//si no hay datos de uauario redirige a la tienda a login
							redirect('login');	
						}											 		 											
					}
					else{
						$this->session->unset_userdata();
						redirect('login');	
					}											
					 	
				}		
				else{					
					$this->session->unset_userdata();
					redirect('login');					
				}																	
			}	
			else{
				redirect('mensaje/'.md5(1));	
			}		
		}	       		
		else{
			$this->session->set_userdata('promociones',$data);
			$this->formato($formato,$data);
		}
		 			  	    		  		
  }  
		
	public function obtener_detalle_promo($sitio, $canal, $promocion){
		$data=array();
		if(!empty($sitio)){	  
	  		$rsitio= $this->api_model->obtener_sitio($sitio);	
			if($rsitio->num_rows()!=0){
				$data['sitio']=$rsitio->row();
			}		 
			else{
				$data['error']['sitio']="no existe informacion de este sitio";
			} 
    	}
		
		if(!empty($canal)){
	  		$rcanal= $this->api_model->obtener_canal($canal);	
			if($rcanal->num_rows()!=0){
				$data['canal']=$rcanal->row();
			}      
			else{
				$data['error']['canal']="no existe informacion de este canal";
			}	  	
    	}                    
	  	
    	if(!empty($promocion)){	  
	  		$rpromocion= $this->api_model->obtener_promocion($promocion);	
			if($rpromocion->num_rows()!=0){
				$data['promocion']=$rpromocion->row();
				$rarticulos= $this->api_model->obtener_articulos($promocion);
				if($rarticulos->num_rows()!=0){
					$data['articulos']=$rarticulos->result_array();																									
				}
				else{
					$data['error']['articulos']="no existen articulos en esta promocion";
				}				
			}      		
			else{
				$data['error']['promocion']="no existe informacion de esta promocion";
			}
    	}	
		return $data;
	}	
			
	private function formato($formato, $data){		
		if((empty($formato)) || ($formato=='json')){						
			echo json_encode($data);					 																	
		}
		else{
			if($formato=="xml"){				
				if(!empty($data['error'])){
					$response ='<?xml version="1.0" encoding="utf-8"?>';
					$response .='<errores>';
					
					if(isset($data['error']['sitio'])){
						$response .='<error>';						
						$response .=$data['error']['sitio'];
						$response .='</error>';	
					}												
										
					if(isset($data['error']['canal'])){
						$response .='<error>';						
						$response .=$data['error']['canal'];
						$response .='</error>';
					}
					    
					if(isset($data['error']['promocion'])){
						$response .='<error>';
						$response .= $data['error']['promocion'];
						$response .='</error>';	
					}
					if(isset($data['error']['articulos'])){
						$response .='<error>';
						$response .= $data['error']['articulos'];
						$response .='</error>';	
					}											
					$response .='</errores>';					
				}	
				else{					
					$response ='<?xml version="1.0" encoding="utf-8"?>';	
																	
					if(!empty($data['sitios'])){												
						if(is_array($data['sitios'])){
							$response .="<detalle>";	
							$response .= "<sitios>";	
							foreach($data['sitios'] as $sit){
								$response .= "<sitio>";
								$response .="<id_sitioSi>";
								$response .=$sit['id_sitioSi'];
								$response .="</id_sitioSi>";
							
								$response .="<urlVc>";
								$response .=$sit['urlVc'];
								$response .="</urlVc>";
								$response .= "</sitio>";					
							}
							$response .='</sitios>';		
							$response .="</detalle>";	
						}					
					}
					if(!empty($data['canales'])){
											
						if(is_array($data['canales'])){
							$response .="<detalle>";	
							$response .= "<canales>";
							foreach($data['canales'] as $can){
								$response .= "<canal>";
								$response .="<id_canalSi>";
								$response .=$can['id_canalSi'];
								$response .="</id_canalSi>";							
								$response .="<descripcionVc>";
								$response .=$can['descripcionVc'];							
								$response .="</descripcionVc>";		
								$response .= "</canal>";			
							}
							$response .='</canales>';		
							$response .="</detalle>";	
						}					
					}
					if(!empty($data['promocion'])){
											
						if(is_array($data['promocion'])){						
							$response .="<detalle>";	
							$response .= "<promociones>";	
							foreach($data['promocion'] as $prom){
								$response .= "<promocion>";
								$response .="<id_promocionIn>";
								$response .=$prom['id_promocionIn'];
								$response .="</id_promocionIn>";
							
								$response .="<descripcionVc>";
								$response .=$prom['descripcionVc'];
								$response .="</descripcionVc>";
							
								$response .="<inicio_promocionDt>";
								$response .=$prom['inicio_promocionDt'];
								$response .="</inicio_promocionDt>";
							
								$response .="<fin_promocionDt>";
								$response .=$prom['fin_promocionDt'];
								$response .="</fin_promocionDt>";
							
								$response .="<terminoVc>";
								$response .=$prom['terminoVc'];
								$response .="</terminoVc>";
							
								$response .="<fecha_creacionDt>";
								$response .=$prom['fecha_creacionDt'];
								$response .="</fecha_creacionDt>";
							
								$response .="<email_usuario_altaVc>";
								$response .=$prom['email_usuario_altaVc'];
								$response .="</email_usuario_altaVc>";		
							
								$response .="<precioF>";
								$response .=$prom['precioF'];
								$response .="</precioF>";	
								$response .= "</promocion>";										
							}
							$response .='</promociones>';
							$response .="</detalle>";			
						}											
					}
				
					if(!empty($data['sitio'])){						
						$response .="<detalle>";	
						$response .="<sitios>";									
						$response .="<sitio>";
						$response .="<id_sitioSi>";
						$response .=$data['sitio']->id_sitioSi;
						$response .="</id_sitioSi>";							
						$response .="<urlVc>";
						$response .=$data['sitio']->urlVc;
						$response .="</urlVc>";													
						$response .="</sitio>";
						$response .="</sitios>";
					
						$response .="<canales>";
						$response .="<canal>";
						$response .="<id_canalSi>";
						$response .=$data['canal']->id_canalSi;
						$response .="</id_canalSi>";							
						$response .="<descripcionVc>";
						$response .=$data['canal']->descripcionVc;
						$response .="</descripcionVc>";					
						$response .="</canal>";
						$response .="</canales>";
					
						$response .="<promociones>";
						$response .="<promocion>";
						$response .="<id_promocionIn>";
						$response .=$data['promocion']->id_promocionIn;
						$response .="</id_promocionIn>";							
						$response .="<descripcionVc>";
						$response .=$data['promocion']->descripcionVc;
						$response .="</descripcionVc>";							
						$response .="<inicio_promocionDt>";
						$response .=$data['promocion']->inicio_promocionDt;
						$response .="</inicio_promocionDt>";							
						$response .="<fin_promocionDt>";
						$response .=$data['promocion']->fin_promocionDt;
						$response .="</fin_promocionDt>";							
						$response .="<terminoVc>";
						$response .=$data['promocion']->terminoVc;
						$response .="</terminoVc>";							
						$response .="<fecha_creacionDt>";
						$response .=$data['promocion']->fecha_creacionDt;
						$response .="</fecha_creacionDt>";							
						$response .="<email_usuario_altaVc>";
						$response .=$data['promocion']->email_usuario_altaVc;
						$response .="</email_usuario_altaVc>";
						$response .="<precioF>";
						$response .=$data['promocion']->precioF;
						$response .="</precioF>";						
						$response .="</promocion>";	
						$response .="</promociones>";
									
						$response .="<articulos>";
						foreach($data['articulos'] as $articulo){
							$response .="<articulo>";							
							$response .="<id_articuloIn>";
							$response .=$articulo['id_articuloIn'];
							$response .="</id_articuloIn>";
							$response .="<id_promocionIn>";
							$response .=$articulo['id_promocionIn'];
							$response .="</id_promocionIn>";
							$response .="<order_code_type>";
							$response .=$articulo['order_code_type'];
							$response .="</order_code_type>";
							$response .="<order_code_id>";
							$response .=$articulo['order_code_id'];
							$response .="</order_code_id>";																		
							$response .="<source_code_id>";
							$response .=$articulo['source_code_id'];
							$response .="</source_code_id>";
							$response .="<oc_id>";
							$response .=$articulo['oc_id'];
							$response .="</oc_id>";
							$response .="<tipo_productoVc>";
							$response .=$articulo['tipo_productoVc'];
							$response .="</tipo_productoVc>";
							$response .="<medio_entregaVc>";
							$response .=$articulo['medio_entregaVc'];
							$response .="</medio_entregaVc>";
							$response .="<product_id>";
							$response .=$articulo['product_id'];
							$response .="</product_id>";
							$response .="<tarifaDc>";
							$response .=$articulo['tarifaDc'];
							$response .="</tarifaDc>";
							$response .="<monedaVc>";
							$response .=$articulo['monedaVc'];
							$response .="</monedaVc>";
							$response .="<taxableBi>";
							$response .=(int)$articulo['taxableBi'];
							$response .="</taxableBi>";
							$response .="<renovacion_automaticaBi>";
							$response .=(int)$articulo['renovacion_automaticaBi'];
							$response .="</renovacion_automaticaBi>";
							$response .="<id_tipo_distribucionSi>";
							$response .=$articulo['id_tipo_distribucionSi'];
							$response .="</id_tipo_distribucionSi>";						
							$response .="<numero_ejemplares>";
							$response .=$articulo['numero_ejemplares'];
							$response .="</numero_ejemplares>";
							$response .="<product_color>";
							$response .=$articulo['product_color'];
							$response .="</product_color>";
							$response .="<product_size>";
							$response .=$articulo['product_size'];
							$response .="</product_size>";
							$response .="<product_style>";
							$response .=$articulo['product_style'];
							$response .="</product_style>";
							$response .="<qty>";
							$response .=$articulo['qty'];
							$response .="</qty>";
							$response .="<issue_id>";
							$response .=$articulo['issue_id'];
							$response .="</issue_id>";
							$response .="<id_tipoArticuloSi>";
							$response .=$articulo['id_tipoArticuloSi'];
							$response .="</id_tipoArticuloSi>";
							$response .="</articulo>";
						}
						$response .="</articulos>";																											
						$response .="</detalle>";			 										
					}														
				}				
				if(strstr($_SERVER["HTTP_USER_AGENT"], "MSIE")) {					
						header("content-type: text/xml");					
						$this->output->set_output($response);							
				}  		
				else{					
						$this->output->set_content_type("content-type: text/xml")->set_output($response);									
				}																																																        		        								                																																				
			}
			else if($formato=="html"){				
				$this->cargar_vista('', 'api', $data);
			}															
		}		
	}		
	
	## funcion que devuelve los parametros sitio, canal, promocion en un JSON para IDC mediante la busqueda en el campo descripcionVc en la tabla IntIssue
	public function obtener_url_promo($cad, $post){
		$url=FALSE;
		$sitio=$this->api_model->obtener_sitio_guidx($post['guidx']);
		if($sitio){
			$id_sitio=$sitio->row()->id_sitioSi;	
		}				
		$promociones=$this->api_model->obtener_promocion_like($cad);
		if($promociones){			
			foreach($promociones->result_array() as $promocion){
				$canal=$this->api_model->obtener_canal_promocion($promocion['id_promocionIn'], $id_sitio);
				if($canal){
					$url= json_encode($canal->row());																									
				}								
			}
		}				
		return $url;
	}
	
	public function obtiene_articulos_y_promociones(){
		$datos=array();
		$total = 0;
		$det_promo=0;
		$datos['numero_promociones']=count($this->session->userdata('promociones'));
		$datos['tipo_productoVc']=array();	
		foreach($this->session->userdata('promociones') as $promocion){				
			// obtiene las promociones y los articulos que contienen				 
			$respromo = $this->obtener_detalle_promo($promocion['id_sitio'], $promocion['id_canal'], $promocion['id_promocion']);
			if($det_promo==0){
				/*
				echo "<pre>";
					print_r($respromo['promocion']);					
				echo "</pre>";
				*/	
				//obtiene 			
				$datos['descripcion_promocion'] = $respromo['promocion']->descripcionVc;
				foreach($respromo['articulos'] as $articulo){																	
					if($articulo['issue_id']){
						$issue = $this->api_model->obtener_issue($articulo['issue_id']);						
						$datos['articulo_promocion'][] =  $issue->row()->descripcionVc;	
					}
					else{
						$datos['articulo_promocion'][]= $articulo['tipo_productoVc'];	
					}												
				}
				
				//exit;
				//if(){
					
				//}
				//$datos['articulo_promocion'] = $respromo['articulos']; 	
				$det_promo=1;								
			}		
			$datos['descripciones_promocion'][] =	$respromo;
						
			foreach($respromo['articulos'] as $articulo){
				if($articulo['issue_id']){
					$issue = $this->api_model->obtener_issue($articulo['issue_id']);
					$datos['tipo_productoVc'][($articulo['issue_id'])] = $issue->row()->descripcionVc;
				}
				$total = $total + $articulo['tarifaDc']; 	
				if($articulo['monedaVc']){
					$datos['moneda'] = $articulo['monedaVc'];	
				}
							
			}									
		}
				
		$datos['total_pagar']=$total;
		return $datos;
	}	
	
	public function encrypt($str, $key){
		$str=trim($str);
    	$block = mcrypt_get_block_size('des', 'ecb');
    	$pad = $block - (strlen($str) % $block);
    	$str .= str_repeat(chr($pad), $pad);
    	return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $str, MCRYPT_MODE_ECB));
	}
	
	public function decrypt($str, $key){
		$str=base64_decode($str);
    	$str = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $str, MCRYPT_MODE_ECB);
    	$block = mcrypt_get_block_size('des', 'ecb');
    	$pad = ord($str[($len = strlen($str)) - 1]);
    	return substr($str, 0, strlen($str) - $pad);
	}
			
	public function cargar_vista($folder, $page, $data){	
		//Para automatizar un poco el desplieguee
		$this->load->view('templates/header', $data);
		$this->load->view($folder.'/'.$page, $data);
		$this->load->view('templates/footer', $data);
	}	
}
