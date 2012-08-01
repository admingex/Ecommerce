<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Registro extends CI_Controller {

	var $title = 'Inicio de Sesi&oacute;n'; 		// Capitalize the first letter
	var $subtitle = 'Crear cuenta de usuario'; 	// Capitalize the first letter
	var $registro_errores = array();
	
	function __construct()
    {
        // Call the Model constructor
        parent::__construct();
		
		//cargar el modelo en el constructor
		$this->load->model('login_registro_model', 'login_registro_model', true);		
		//la sesion se carga automáticamente
    }
	
	public function index($mensaje= "")
	{

		$data['title'] = $this->title;
		$data['subtitle'] = $this->subtitle;
		//echo 'Session: '.$this->session->userdata('id_cliente');
		$script_file = "<script type='text/javascript' src='". base_url() ."js/registro.js'> </script>";
		$data['script'] = $script_file;		
		
		//echo var_dump($data)."<br/>pass ".$_POST['password']."<br/>tipo ".$_POST['tipo_inicio'];
		
		$this->cargar_vista('', 'registro', $data);
	}
	
	public function usuario(){
		
		$data['title'] = $this->title;
		$data['subtitle'] = $this->subtitle;
		//echo 'Session: '.$this->session->userdata('id_cliente');
		$script_file = "<script type='text/javascript' src='". base_url() ."js/registro.js'> </script>";
		$data['script'] = $script_file;		
		if ($_POST){	
			//$cliente_info = array();		
			$cliente_info = $this->get_datos_login();
			
			if(empty($this->registro_errores)) {			//verificar la existencia de email y password.
				$email_registrado = $this->login_registro_model->verifica_registro_email($cliente_info['email']);				
				if($email_registrado->num_rows() == 0) {	//email no está registrado
					
					$cliente_info['id_clienteIn'] = $this->login_registro_model->next_cliente_id();	//id del cliente					
					
					//$res = $this->login_registro_model->registrar_cliente($cliente_info);
										
					/*$m5_pass = md5($cliente_info['email'].'|'.$cliente_info['password']);		//encriptaciónn definida en el registro de usuarios
    				$cliente_info['password'] = $m5_pass;
					//$qry="INSERT INTO CMS_IntCliente (id_clienteIn, salutation, fname, lname, email, password) VALUES (".$cliente_info['id_clienteIn'].", 'giso', 'est', 'ale', 'ddd@ddd.com', '0520d2ac03685b061076ffeaaa2557a2')";
					//$res = mysql_query($qry);
        			$res= $this->db->insert('CMS_IntCliente', $cliente_info);		//true si se inserta
        			echo "resultado del query:".$res;        			
					*/
					//if($this->login_registro_model->registrar_cliente($cliente_info)) {							//registro exitoso
										
					if( $this->registro_cliente($cliente_info)){						
						//se va a revisar el inicio de sesión		
						$this->crear_sesion($cliente_info['id_clienteIn'], $cliente_info['salutation'], $cliente_info['email']);	//crear sesion,
						$this->session->set_userdata('reg_user', 'Registro Exitoso');						
						$headers="Content-type: text/html; charset=UTF-8\r\n";
		                $headers.="MIME-Version: 1.0\r\n";
					    $headers .= "From: Pagos Grupo Expansión<soporte@expansion.com.mx>\r\n";       
						$mensaje="<html>
								  <body>
								  	   <div>Hola, ".$cliente_info['salutation'].",<br /><br /> 
								  	   </div>									   
								  	   <div>
								  	      Gracias por crear tu cuenta en pagos.grupoexpansion.mx.<br /><br /> 
										  Con tu cuenta podrás almacenar tus datos para que tus siguientes compras sean más ágiles, pues no tendrás necesidad de registrar tus datos cada vez que compres aquí.<br /><br />
										  Te damos la más cordial bienvenida y esperamos que disfrutes tu compra.<br /><br />
										  Estamos disponibles para cualquier pregunta o duda sobre tu cuenta en:<br /><br/>
										  Atención a clientes<br/><br/>
										  Tel. (55) 9177 4342<br/><br/>
										  atencionaclientes@expansion.com.mx<br/><br/>
										  Cordialmente,<br/><br/>
										  Grupo Expansión.<br/>
								  	   </div>								  	   
								  </body>
								  </html>"; 									
																								     		      									
						if(mail($cliente_info['email'], "=?UTF-8?B?".base64_encode('¡Bienvenido a la plataforma de pagos de Grupo Expansión!')."?=", $mensaje, $headers)){														
							redirect('login', 'location', 302);	
							$_POST = array();						
						}																																																						
					} else {
						$this->registro_errores['user_reg'] = "No se pudo realizar el registro en el sistema";
						$data['registro_errores'] = $this->registro_errores; 						
						$this->cargar_vista('', 'registro', $data);
					}
					
				} else {
					//redirect('login', 'location', 302);
					$this->registro_errores['user_reg'] = "Solicitaste iniciar sesión como cliente nuevo, pero ya existe una cuenta con el correo ".$cliente_info['email'];
					$data['registro_errores'] = $this->registro_errores; 
					$this->cargar_vista('', 'registro', $data);
				}
			} 
			else{				
				$data['registro_errores']=$this->registro_errores;				
				$this->cargar_vista('', 'registro', $data);				
			}
		}
		else{			
			$this->cargar_vista('', 'registro', $data);
		}				
		
	}
	
	private function registro_cliente($cliente){
		$m5_pass = md5($cliente['email'].'|'.$cliente['password']);		//encriptaciónn definida en el registro de usuarios
    	$cliente['password'] = $m5_pass;
		
		//$this->login_registro_model->registrar_cliente($cliente);
		return 	$this->login_registro_model->registrar_cliente($cliente);
	}

	private function crear_sesion($id_cliente, $nombre, $email)
	{
		$array_session = array(
			'logged_in' => TRUE,
			'username' 	=> $nombre,
			'id_cliente'=> $id_cliente,
			'email' 	=> $email
		);
		//creacion de la sessión
		$this->session->set_userdata($array_session);
	}
	
	private function get_datos_login()
	{
		$datos = array();
		
		if(array_key_exists('txt_nombre', $_POST)) {
			if(preg_match('/^[A-Z \'.-áéíóúÁÉÍÓÚÑñ]{2,30}$/i', $_POST['txt_nombre'])) { 
				$datos['salutation'] = $_POST['txt_nombre'];
			} else {
				$this->registro_errores['txt_nombre'] = '<div class="error">Por favor ingresa tu nombre</div>';
			}
		}
		if(array_key_exists('txt_apellidoPaterno', $_POST)) {
			if(preg_match('/^[A-Z \'.-áéíóúÁÉÍÓÚÑñ]{2,30}$/i', $_POST['txt_apellidoPaterno'])) { 
				$datos['fname'] = $_POST['txt_apellidoPaterno'];
			} else {
				$this->registro_errores['txt_apellidoPaterno'] = '<div class="error">Por favor ingresa tu apellido paterno</div>';
			}
		}		
		if (filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {			
			$datos['email'] = htmlspecialchars(trim($_POST['email']));
		} else {			
			$this->registro_errores['email'] = '<div class="error2">Por favor ingresa un correo electrónico <br />válido. Ejemplo: nombre@dominio.mx</div>';
		}
		if(isset($_POST['email'])&& isset($_POST['password'])){
			if($_POST['email']!=""){
				$pass_info=$this->valida_password($_POST['email'], $_POST['password']);	
			}
						
			if (preg_match ('/^(\w*(?=\w*\d)(?=\w*[a-z])(?=\w*[A-Z])\w*){6,20}$/', $_POST['password']) ) {
				if ($_POST['password'] == $_POST['password_2']) {
					$datos['password'] = htmlspecialchars(trim($_POST['password']));
				} 
				else {
					$this->registro_errores['password_2'] = '<div class="error2">Las contraseñas ingresadas no son idénticas. Por favor intenta de nuevo.</div>';
				}
			} 
			else {
				$this->registro_errores['password_2'] = '<div class="error">Por favor ingresa una contrase&ntilde;a v&aacute;lida</div>';
			}
		}					 			 
		else{
			$this->registro_errores['password'] = '<div class="error">Información incompleta</div>';
		}		
		
		return $datos;
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
							$this->registro_errores['password'] = '<div class="error2"> Debe contener por lo menos una minuscula</div>';						
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
		
	private function cargar_vista($folder, $page, $data)
	{	
		//Para automatizar un poco el desplieguee
		$this->load->view('templates/header', $data);
		$this->load->view('templates/menu.html', $data);		
		$this->load->view($folder.'/'.$page, $data);
		$this->load->view('templates/footer', $data);
	}



}

/* End of file registro.php */
/* Location: ./application/controllers/registro.php */