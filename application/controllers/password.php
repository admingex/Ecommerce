<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include('api.php');

class Password extends CI_Controller {

	public static $TIPO_ACTIVIDAD = array(
		"BLOQUEO"=> 0, 
		"DESBLOQUEO"=> 1, 
		"SOLICITUD_PASSWORD"=>2,
		"CAMBIO_PASSWORD"=>3,
		"ACCESO_INCORRECTO"=>4
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
		$this->load->model('login_registro_model', 'login_registro_model', true);		
		$this->api = new Api();		
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
			if (filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {							
				$datamail=$this->password_model->revisa_mail($_POST['email']);			
				if($datamail->num_rows()==1){
					$data['enviado'] = TRUE;				
					$data['cliente']=$datamail->row();
					$data['password_temporal']= $p = substr(md5(uniqid(rand( ), true)), 5,10);
					$this->load->helper('date');
					$data['timestamp']= $t= mdate('%Y/%m/%d %h:%i:%s',time());
					$this->password_model->guardar_clave_temporal($data['cliente']->id_clienteIn, $p);		
					$this->password_model->guarda_actividad_historico($data['cliente']->id_clienteIn, $p, self::$TIPO_ACTIVIDAD['SOLICITUD_PASSWORD'], $t);
					$encript=$this->api->encrypt($this->session->userdata('id_sitio')."|".
												 $this->session->userdata('id_canal')."|".
												 $this->session->userdata('id_promocion')."|".
												 $this->session->userdata('guidx')."|".
												 $this->session->userdata('guidy')."|".
												 $this->session->userdata('guidz')."|", $this->api->key);		
					$encript= rtrim(strtr(base64_encode($encript), '+/', '-_'), '=');
	 
					
					$headers = "From: Pagos Grupo Expansión<servicioaclientes@expansion.com.mx>"."\n"; 
					$headers .= "MIME-Version: 1.0"."\n"; 
					$headers .= "Content-type: text/html; charset=UTF-8"."\r\n"; 											                				           
					$mensaje="<html>
							  <body>
							  	   <div>Hola,
							  	   </div>
							  	   <div>
							  	       En pagos.grupoexpansion.mx, la plataforma de pagos de Grupo Expansión, recibimos una solicitud<br />
							  	       para recuperar la contraseña asociada a este correo. Si tú hiciste esta solicitud, por favor sigue las<br />
							  	       instrucciones que aparecen abajo. Si no solicitaste cambiar tu contraseña, puedes ignorar este correo<br />
							  	       con tranquilidad, pues tu cuenta de cliente está segura.
							  	   </div>
							  	   <br /><br />
							  	   <div>
							  	   	  
							  	   	   	   1. Sigue el link de abajo para cambiar tu contraseña usando nuestro servidor seguro.<br /><br />
							  	   	   	   <a href='https://pagos.grupoexpansion.mx/password/verificar/".$p."/".$encript."'>https://pagos.grupoexpansion.mx/password/verificar/".$p."/".$encript."</a><br /><br />
							  	   	   	   Si seguir el link no funciona, puedes copiar y pegar el link en la barra de dirección de tu<br />
							  	   	   	   navegador, o reescribirla ahí.<br />
							  	   	   	   2. Si se solicita ingresa la clave: ".$p.", caso contrario ir al paso 3<br />
							  	   	   	   Esta no es una contraseña, pero la necesitarás para crear una nueva contraseña.<br />
							  	   	   	   3. Sigue las instrucciones que aparecen en la pantalla para crear tu nueva contraseña.
							  	   	   
							  	   </div>
							  	   <br /><br />
							  	   <div>
							  	       Si tienes alguna pregunta, por favor envía un correo a nuestra área de Atención a Clientes.<br />
							  	       (<u>servicioaclientes@expansion.com.mx</u>).
							  	   </div>
							  	   <br /><br />
							  	   <div>
							  	   	   Gracias por comprar con Grupo Expansión.
							  	   </div>
							  </body>
							  </html>"; 
																							     		      									
					if(mail($data['cliente']->email, "=?UTF-8?B?".base64_encode('Recuperar contraseña')."?=", $mensaje, $headers)){
						$this->cargar_vista('', 'password', $data);	
					}																														
					else{
						redirect('login');	
					}
					 						
													
				}		
				else{
					$data['mensaje']='<span class="error">No se encuentra en nuestra base de datos</span>';
					$this->cargar_vista('', 'password', $data);
				}
			}else {				
				$data['mensaje'] = '<div class="error2">Por favor ingresa una dirección de correo válida. Ejemplo: nombre@dominio.mx</div>';
				$this->cargar_vista('', 'password', $data);
			}	
			
		}
		else{
			$this->cargar_vista('', 'password', $data);
		}
	}
	
	public function cambiar(){
		$data['title'] = "Escribe una nueva contraseña";
		$data['subtitle'] = "Escribe una nueva contraseña";
		$data['mensaje']='';	
		$data['cambiar']=TRUE;
		$data['verificar']=FALSE;	
		$data['enviado']=FALSE;		
		
					
		if(!empty($_POST['password'])){
			$val_pass=$this-> valida_password($this->session->userdata('email'), $_POST['password']);						
			if (preg_match ('/^(\w*(?=\w*\d)(?=\w*[a-z])(?=\w*[A-Z])\w*){6,20}$/', $_POST['password']) ) {
				if ($_POST['password'] != $_POST['password_2']) {
					$this->registro_errores['password_2'] = '<div class="error2">Las contraseñas ingresadas no son idénticas. Por favor intenta de nuevo.</div>';									
				} 	
				else {								
					$datos['password'] = htmlspecialchars(trim($_POST['password']));
				}
			}							
		}				
		else {
			$this->registro_errores['password']='<div class="error">Ingrese una nueva contraseña</div>';			
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
									'id_cliente'=> $id_clienteIn,
									'email' 	=> $email
								 );				
				$this->session->set_userdata($array_session);
				$this->login_registro_model->desbloquear_cuenta($id_clienteIn);													
				//redirect('forma_pago');
				echo "  <form name='inicio_sesion' action='".site_url('login')."' method='post'>
								    	<input type='text' name='email' value='".$email."' style='display: none' />
								    	<input type='text' name='tipo_inicio' value='registrado' style='display: none' />
								    	<input type='text' name='password' value='".$_POST['password']."' style='display: none' />
								    	<input type='submit' name='enviar' value='Iniciar sesion' style='display: none' />
									</form>";
							echo "<script>document.inicio_sesion.submit();</script>";
				//redirect("login", "location", 303);	
			}																						
			else{					
				$this->registro_errores['password']='<div class="validation_message">Por favor ingresa una contraseña que no coincida con ninguna de las últimas ocho contraseñas usadas</div>';
				$data['registro_errores'] = $this->registro_errores;
				$this->cargar_vista('', 'password',$data);										
			}	
			
		}
		else{
			$data['registro_errores'] = $this->registro_errores;
			$this->cargar_vista('', 'password',$data);			
		}	
															
	}
	
	public function verificar($passtemp= '', $datos_continuar=''){
		$data['title'] = "Crea una nueva contraseña";
		$data['subtitle'] = "Crea una nueva contraseña";
		$data['mensaje']='';	
		$data['verificar']=TRUE;
		$data['cambiar']=FALSE;	
		$data['enviado']=FALSE;	
		$data['password_temporal']=$passtemp;		
		$script_file = "<script type='text/javascript' src='". base_url()."js/registro.js'> </script>";
		$data['script'] = $script_file;	
		if($datos_continuar!=""){
			$datos_continuar=base64_decode(str_pad(strtr($datos_continuar, '-_', '+/'), strlen($datos_continuar) % 4, '=', STR_PAD_RIGHT));
			$datos_decrypt=$this->api->decrypt($datos_continuar,$this->api->key);
			$mp=explode('|',$datos_decrypt);
			$this->session->set_userdata('id_sitio', $mp[0]);
			$this->session->set_userdata('id_canal', $mp[1]);
			$this->session->set_userdata('id_promocion', $mp[2]);
			$this->session->set_userdata('guidx', $mp[3]);
			$this->session->set_userdata('guidy', $mp[4]);
			$this->session->set_userdata('guidz', $mp[5]);				
		}
		
		if($_POST){
			if(!empty($_POST['password_temporal'])){
				$result=$this->password_model->obtiene_cliente($_POST['password_temporal']);
				if($result->num_rows()==0){
					$this->registro_errores['password_temporal']='<span class="error2">clave temporal utilizada anteriormente solicita el cambio de contraseña  <a href="'.site_url('password').'" style="color: #FFF">aquí</a></span>';										
				}			
				else{					
					$this->session->set_userdata($result->row());																								
					$data['cambiar']=TRUE;
					$data['verificar']=FALSE;					
				}									 									
			}									
			else {
				$this->registro_errores['password_temporal'] = '<span class="error2">Por favor ingresa una clave temporal v&aacute;lida</span>';				
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
			$this->registro_errores['password'] = '<div class="error">Debe contener por lo menos 8 caracteres</div>';
		}
		else{
			if(preg_match('/[^a-zA-Z0-9]/', $pass)){
				$this->registro_errores['password'] = '<div class="error">Solo debe incluir letras y numeros</div>';			
			}
			else{
				if(stristr($pass,$cadlogin[0])){
					$this->registro_errores['password'] = '<div class="error2">La contraseña no debe contener una parte del correo electrónico ingresado</div>';				
				}					
				else{
					if(!$this->contiene_mayuscula($pass)){
						$this->registro_errores['password'] = '<div class="error2">Debe contener por lo menos una mayuscula</div>';					
					}	
					else{
						if(!$this->contiene_minuscula($pass)){
							$this->registro_errores['password'] = '<div class="error2"> Debe  contener por lo menos una minuscula </div>';						
						}
						else{
							if(!$this->contiene_numero($pass)){
								$this->registro_errores['password'] = '<div class="error">Debe contener por lo menos un numero</div>';							
							}
							else{
								if(!$this->contiene_consecutivos($pass)){
									$this->registro_errores['password'] = '<div class="error2">No se debe incluir el mismo caracter mas de 2 veces</div>';								
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