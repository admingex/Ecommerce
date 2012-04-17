<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Password extends CI_Controller {

	public static $TIPO_ACTIVIDAD = array(
		"BLOQUEO"=> 0, 
		"DESBLOQUEO"=> 1, 
		"SOLICITUD_PASSWORD"=>2,
		"CAMBIO_PASSWORD"=>3
	);	
	
	var $title = 'Recupera tu contrase&ntilde;a'; 		// Capitalize the first letter
	var $subtitle = 'Recupera tu contrase&ntilde;a'; 	// Capitalize the first letter	
	private $email;
	private $password;
	var $registro_errores = array();		//validación para los errores
	
	function __construct(){
        // Call the Model constructor
        parent::__construct();		
		//cargar el modelo en el constructor
		$this->load->model('password_model', 'password_model', true);			
    }
	
	public function index(){
		$data['title'] = $this->title;
		$data['subtitle'] = $this->subtitle;
		$data['verificar']=FALSE;
		$data['enviado']=FALSE;
		$data['cambiar']=FALSE;	
		$data['mensaje']='';		
		$script_file = "<script type='text/javascript' src='". base_url() ."js/registro.js'> </script>";
		$data['script'] = $script_file;			
		$this->cargar_vista('', 'password',$data);
	}	
	
	public function enviar(){
		$data['title'] = $this->title;
		$data['subtitle'] = $this->subtitle;
		$data['mensaje']='';
		$data['enviado']=FALSE;
		$data['verificar']=FALSE;
		$data['cambiar']=FALSE;	
		$script_file = "<script type='text/javascript' src='". base_url() ."js/registro.js'> </script>";
		$data['script'] = $script_file;	
		if($_POST){
			$datamail=$this->password_model->revisa_mail($_POST['email']);			
			if($datamail->num_rows()==1){
				$data['enviado'] = TRUE;				
				$data['cliente']=$datamail->row();
				$data['password_temporal']= $p = substr(md5(uniqid(rand( ), true)), 5,10);
				$this->load->helper('date');
				$data['timestamp']= $t= mdate('%Y/%m/%d %h:%i:%s',time());
				$this->password_model->guardar_clave_temporal($data['cliente']->id_clienteIn, $p);		
				$this->password_model->guarda_actividad_historico($data['cliente']->id_clienteIn, $p, self::$TIPO_ACTIVIDAD['SOLICITUD_PASSWORD'], $t);		
												  
				$headers="Content-type: text/html; charset=UTF-8\r\n";
                $headers.="MIME-Version: 1.0\r\n";
			    $headers .= "From: GexWeb<soporte@expansion.com.mx>\r\n";            					
								
				if(mail($data['cliente']->email, 'Recuperar password', "http://10.177.73.120/ecommerce/index.php/password/verificar/".$p, $headers)){
					$this->cargar_vista('', 'password', $data);	
				}																														
				else{
					redirect('login');	
				}					
												
			}		
			else{
				$data['mensaje']='No se encuentra en nuestra base de datos';
				$this->cargar_vista('', 'password', $data);
			}
		}
		else{
			$this->cargar_vista('', 'password', $data);
		}
	}
	
	public function cambiar(){
		$data['title'] = "Recupera tu contrase&ntilde;a";
		$data['subtitle'] = "Recupera tu contrase&ntilde;a";
		$data['mensaje']='';	
		$data['cambiar']=TRUE;
		$data['verificar']=FALSE;	
		$data['enviado']=FALSE;		
		
					
		if(!empty($_POST['password'])){
			$val_pass=$this-> valida_password($this->session->userdata('email'), $_POST['password']);						
			if (preg_match ('/^(\w*(?=\w*\d)(?=\w*[a-z])(?=\w*[A-Z])\w*){6,20}$/', $_POST['password']) ) {
				if ($_POST['password'] != $_POST['password_2']) {
					$this->registro_errores['password_2'] = 'Tus contrase&ntilde;as no coincden';									
				} 	
				else {								
					$datos['password'] = htmlspecialchars(trim($_POST['password']));
				}
			}							
		}				
		else {
			$this->registro_errores['password']='Ingrese una nueva contraseña';			
		}					 
		/////revisar
		if(empty($this->registro_errores)){
			$email=$this->session->userdata('email');
			$id_clienteIn=$this->session->userdata('id_clienteIn');
			$password=$this->session->userdata('password');
			$nombre=$this->session->userdata('salutation');
						
			if($this->password_model->historico_clave($id_clienteIn, $email, $_POST['password'])!=1){
				$this->password_model->cambia_password($id_clienteIn, $email,$_POST['password']);
				$this->load->helper('date');
				$t= mdate('%Y/%m/%d %h:%i:%s',time());
				$this->password_model->guarda_actividad_historico($id_clienteIn, $password, self::$TIPO_ACTIVIDAD['CAMBIO_PASSWORD'], $t);
				//creación de la sesión
				$array_session = array(
									'logged_in' => TRUE,
									'username' 	=> $nombre,
									'id_cliente'=> $id_clienteIn
								 );				
				$this->session->set_userdata($array_session);				
				redirect('forma_pago');	
			}																						
			else{					
				$this->registro_errores['password']='no puedes utilizar esta hasta q se tengan 8 historicos';
				$data['registro_errores'] = $this->registro_errores;
				$this->cargar_vista('', 'password',$data);										
			}	
			
		}
		else{
			$data['registro_errores'] = $this->registro_errores;
			$this->cargar_vista('', 'password',$data);			
		}	
															
	}
	
	public function verificar($passtemp= ''){
		$data['title'] = "Recupera tu contrase&ntilde;a";
		$data['subtitle'] = "Recupera tu contrase&ntilde;a";
		$data['mensaje']='';	
		$data['verificar']=TRUE;
		$data['cambiar']=FALSE;	
		$data['enviado']=FALSE;	
		$data['password_temporal']=$passtemp;		
		$script_file = "<script type='text/javascript' src='". base_url() ."js/registro.js'> </script>";
		$data['script'] = $script_file;	
		if($_POST){
			if(!empty($_POST['password_temporal'])){
				$result=$this->password_model->obtiene_cliente($_POST['password_temporal']);
				if($result->num_rows()==0){
					$this->registro_errores['password_temporal']='clave temporal no encontrada';										
				}			
				else{					
					$this->session->set_userdata($result->row());																								
					$data['cambiar']=TRUE;
					$data['verificar']=FALSE;					
				}									 									
			}									
			else {
				$this->registro_errores['password_temporal'] = 'Por favor ingresa una clave temporal v&aacute;lida';				
			}
																					 				 
		}	
		
		if(!empty($this->registro_errores)){
			$data['registro_errores'] = $this->registro_errores;	
		}		
			
		$this->cargar_vista('', 'password',$data);		
					
	}		
	
	private function contiene_mayuscula($cad){
		$may='ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		for($i=0; $i<strlen($cad); $i++){
			if(strstr($may, $cad[$i])){
				return TRUE;		
			}
		}	
		return FALSE;
	}

	private function contiene_minuscula($cad){
		$min='abcdefghijklmnopqrstuvwxyz';
		for($i=0; $i<strlen($cad); $i++){
			if(strstr($min, $cad[$i])){
				return TRUE;		
			}
		}	
		return FALSE;
	}
	
	private function contiene_numero($cad){
		$num='0123456789';
		for($i=0; $i<strlen($cad); $i++){
			if(strstr($num, $cad[$i])){
				return TRUE;		
			}
		}	
		return FALSE;
	}

	private function contiene_consecutivos($cad){
		for($i=2; $i<strlen($cad); $i++){		
			$term0=$cad[($i-2)];
			$term1=$cad[($i-1)];
			$term2=$cad[$i];									
			if(($term0==$term1)&&($term1==$term2)){
				return FALSE;
			}		     		            			  		
   		}   	
   		return TRUE;
	}

	private function valida_password($correo, $pass){		
		$cadlogin = explode('@',$correo);
		if(strlen($pass)<8){		
			$this->registro_errores['password'] = 'debe contener por lo menos 8 caracteres';
		}
		else{
			if(preg_match('/[^a-zA-Z0-9]/', $pass)){
				$this->registro_errores['password'] = 'deben ser numero y letras solamente';			
			}
			else{
				if(stristr($pass,$cadlogin[0])){
					$this->registro_errores['password'] = 'no debe contener login';				
				}					
				else{
					if(!$this->contiene_mayuscula($pass)){
						$this->registro_errores['password'] = 'debe contener por lo menos 1 mayuscula';					
					}	
					else{
						if(!$this->contiene_minuscula($pass)){
							$this->registro_errores['password'] = 'debe contener por lo menos 1 minuscula';						
						}
						else{
							if(!$this->contiene_numero($pass)){
								$this->registro_errores['password'] = 'debe contener por lo menos 1 numero';							
							}
							else{
								if(!$this->contiene_consecutivos($pass)){
									$this->registro_errores['password'] = 'contiene consecutivos';								
								}
								else{
									$datos['password']=htmlspecialchars(trim($pass));
								}
							}
						}				
					}							
				}
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

/* End of file login.php */
/* Location: ./application/controllers/password.php */