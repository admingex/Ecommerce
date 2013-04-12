<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Registro extends CI_Controller {

	var $title = 'Inicio de Sesi&oacute;n'; 		// Capitalize the first letter
	var $subtitle = 'Crear cuenta de usuario'; 	// Capitalize the first letter
	var $registro_errores = array();
	
	public static $TIPO_DIR = array(
		"RESIDENCE"	=> 0, 
		"BUSINESS"	=> 1, 
		"OTHER"		=> 2
	);
	
	function __construct()
    {
        // Call the Model constructor
        parent::__construct();
		
		//cargar el modelo en el constructor
		$this->load->model('login_registro_model', 'login_registro_model', true);	
		$this->load->model('direccion_envio_model', 'direccion_envio_model', true);	
		$this->load->model('suscripcion_express_model', 'suscripcion_express_model', true);			
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
		//catálogo de paises de think
		$lista_paises_think = $this->direccion_envio_model->listar_paises_think();
		$data['lista_paises_think'] = $lista_paises_think;
		
		$this->cargar_vista('', 'registro', $data);
	}
	
	public function usuario(){
		
		$data['title'] = $this->title;
		$data['subtitle'] = $this->subtitle;
		//echo 'Session: '.$this->session->userdata('id_cliente');
		$script_file = "<script type='text/javascript' src='". base_url() ."js/registro.js'> </script>";
		$data['script'] = $script_file;
		
		$lista_paises_think = $this->direccion_envio_model->listar_paises_think();
		$data['lista_paises_think'] = $lista_paises_think;
				
		if ($_POST){	
			//$cliente_info = array();		
			$cliente_info = $this->get_datos_login();
			
			if(empty($this->registro_errores)) {			//verificar la existencia de email y password.									
					
				$email_registrado = $this->suscripcion_express_model->verifica_registro_email($_POST['email']);															
				if($email_registrado->num_rows() === 0) {	//email no está registrado
					
					$cliente_info['id_clienteIn'] = $this->login_registro_model->next_cliente_id();	//id del cliente
					$pass= $cliente_info['password'];
					$m5_pass = md5($cliente_info['email'].'|'.$cliente_info['password']);		//encriptaciónn definida en el registro de usuarios
    				$cliente_info['password'] = $m5_pass;
					
					$regcte = $this->suscripcion_express_model->registro_cliente($cliente_info); 
					 
					if($regcte === 1){		
						$envio_info = $this->carga_datos_envio();
						if(empty($this->registro_errores)){
							
							$consecutivoenvio = $this->suscripcion_express_model->get_consecutivo_dir($cliente_info['id_clienteIn']);
							$envio_info['direccion']['id_consecutivoSi']= $consecutivoenvio;
							$envio_info['direccion']['address_type'] = self::$TIPO_DIR['RESIDENCE'];		//address_type
							$envio_info['direccion']['id_clienteIn']= $cliente_info['id_clienteIn'];
								
							$regdir = $this->suscripcion_express_model->insertar_direccion($envio_info['direccion']);
							if($regdir === 1){								
																
																					
								if (array_key_exists('requiere_factura', $_POST) && ($_POST['requiere_factura']=='si')) {
									$factura_info = $this->carga_datos_factura();
									if(empty($this->registro_errores)){
										$factura_info['direccion']['id_clienteIn'] = $cliente_info['id_clienteIn'];
										$factura_info['direccion']['email'] = $cliente_info['email'];
										
										$regrs = $this->suscripcion_express_model->insertar_rs($factura_info['direccion']);
										if($regrs === 1){
											$consecutivofact = $this->suscripcion_express_model->get_consecutivo_dir($cliente_info['id_clienteIn']);	
											$factura_info['direccion']['id_consecutivoSi']= $consecutivoenvio;
											$factura_info['direccion']['address_type'] = self::$TIPO_DIR['BUSINESS'];		//address_type
											$factura_info['direccion']['id_clienteIn']= $cliente_info['id_clienteIn'];	
											
											$regdirf = $this->suscripcion_express_model->insertar_direccion($factura_info['direccion']);
											
											$datars = array('requiere_factura' => 'si');
											$this->session->set_userdata($datars);
											
											if($regdirf === 1){
												if($this->envia_mail($cliente_info)){
													$this->crear_sesion($cliente_info['id_clienteIn'], $cliente_info['salutation'], $cliente_info['email']);	//crear sesion,
													echo "  <form name='inicio_sesion' action='".site_url('login')."' method='post'>
							    								<input type='text' name='email' value='".$cliente_info['email']."' style='display: none' />
							    								<input type='text' name='tipo_inicio' value='registrado' style='display: none' />
														    	<input type='text' name='password' value='".$pass."' style='display: none' />
														    	<input type='submit' name='enviar' value='Iniciar sesion' style='display: none' />
															</form>";
													echo "<script>document.inicio_sesion.submit();</script>";						
												}
												else{
													$this->registro_errores['user_reg'] = "No se pudo realizar el envío del mail en el sistema";
													$data['registro_errores'] = $this->registro_errores;	
												}
												
											}
											else{
												echo "no se registro la dirección";
											}
												
										}
										else{
											echo "no se registro Razon Social";	
										}										
									}
									else{
										$data['registro_errores'] = $this->registro_errores; 
										$this->cargar_vista('', 'registro', $data);	
									}							
															
								}
								else{
									$this->session->set_userdata('requiere_factura', 'no');
									if($this->envia_mail($cliente_info)){
										$this->crear_sesion($cliente_info['id_clienteIn'], $cliente_info['salutation'], $cliente_info['email']);	//crear sesion,
										echo "  <form name='inicio_sesion' action='".site_url('login')."' method='post'>
				    								<input type='text' name='email' value='".$cliente_info['email']."' style='display: none' />
				    								<input type='text' name='tipo_inicio' value='registrado' style='display: none' />
											    	<input type='text' name='password' value='".$pass."' style='display: none' />
											    	<input type='submit' name='enviar' value='Iniciar sesion' style='display: none' />
												</form>";
										echo "<script>document.inicio_sesion.submit();</script>";						
									}
									else{
										$this->registro_errores['user_reg'] = "No se pudo realizar el envío del mail en el sistema";
										$data['registro_errores'] = $this->registro_errores;	
									}
								}	
								  																	
							}	
							else{
								$this->registro_errores['user_reg'] = "No se pudo realizar el registro de la dirección de envío en el sistema";
								$data['registro_errores'] = $this->registro_errores;
							}				
									
						} 
						else{
							$data['registro_errores'] = $this->registro_errores; 
							$this->cargar_vista('', 'registro', $data);	
						}	
					}
					else{
						$this->registro_errores['user_reg'] = "No se pudo realizar el registro del cliente en el sistema";
						$data['registro_errores'] = $this->registro_errores; 	
					}																		
																																
																																																									
					
					
				} 
				else {
					//redirect('login', 'location', 302);
					$this->registro_errores['user_reg'] = "Solicitaste iniciar sesión como cliente nuevo, pero ya existe una cuenta con el correo ".$cliente_info['email'];
					$data['registro_errores'] = $this->registro_errores; 
					$this->cargar_vista('', 'registro', $data);
					//if(strstr($_SERVER["HTTP_USER_AGENT"], "MSIE")) {
					//	redirect('login', 'location', 302);
					//}
				}
			} 
			else{				
				$data['registro_errores']=$this->registro_errores;				
				$this->cargar_vista('', 'registro', $data);		
				//if(strstr($_SERVER["HTTP_USER_AGENT"], "MSIE")) {
				//	redirect('login', 'location', 302);
				//}		
			}
		}
		else{			
			$this->cargar_vista('', 'registro', $data);
		}				
		
	}
	
	private function registro_cliente($cliente){
		$m5_pass = md5($cliente['email'].'|'.$cliente['password']);		//encriptaciónn definida en el registro de usuarios
    	$cliente['password'] = $m5_pass;
				
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
		$this->registro_errores = array();
		$datos = array();
		
		if(array_key_exists('txt_nombre', $_POST)) {
			if(preg_match('/^[A-Z \'.-áéíóúÁÉÍÓÚÑñ]{2,30}$/i', $_POST['txt_nombre'])) { 
				$datos['salutation'] = $_POST['txt_nombre'];
			} else {
				$this->registro_errores['txt_nombre'] = '<div class="error">Por favor ingresa tu nombre</div>';
			}
		}
		if(array_key_exists('txt_apellidoPaterno', $_POST)) {
			if(preg_match('/^[A-Z \'.-áéíóúÁÉÍÓÚÑñ]{1,30}$/i', $_POST['txt_apellidoPaterno'])) { 
				$datos['fname'] = $_POST['txt_apellidoPaterno'];
			} else {
				$this->registro_errores['txt_apellidoPaterno'] = '<div class="error">Por favor ingresa tu apellido paterno</div>';
			}
		}
		if(array_key_exists('txt_apellidoMaterno', $_POST)) {
			if(preg_match('/^[A-Z \'.-áéíóúÁÉÍÓÚÑñ]{1,30}$/i', $_POST['txt_apellidoMaterno'])) { 
				$datos['lname'] = $_POST['txt_apellidoMaterno'];
			}
			else{
				$datos['lname'] = '';
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
		
	private function carga_datos_envio(){
		$this->registro_errores = array();
		$datos = array();
		
		if (array_key_exists('calle', $_POST)) {
			if(preg_match('/^[A-Z0-9áéíóúÁÉÍÓÚÑñ \'.-]{1,50}$/i', $_POST['calle'])) {
				$datos['direccion']['address1'] = $_POST['calle'];
			}
			else {
				$this->registro_errores['calle'] = '<span class="error">Por favor ingresa una calle</span>';
			}
		}	
		if (array_key_exists('num_ext', $_POST)) {
			if(preg_match('/^[A-Z0-9 -]{1,50}$/i', $_POST['num_ext'])) {
				$datos['direccion']['address2'] = $_POST['num_ext'];
			}
			else {
				$this->registro_errores['num_ext'] = '<span class="error">Por favor ingresa el número exterior</span>';
			}
		}	
		
		if (!empty($_POST['num_int'])) {
			if(preg_match('/^[A-Z0-9 -]{1,50}$/i', $_POST['num_int'])) {
				$datos['direccion']['address4'] = $_POST['num_int'];
			} 
			else {
				$this->registro_errores['num_int'] = '<span class="error">Por favor ingresa el número interior</span>';
			}
		} 
		else {
				$datos['direccion']['address4'] = NULL;
		}
		
		if (!empty($_POST['pais'])) {
			$datos['direccion']['codigo_paisVc'] = $_POST['pais'];
		}
		else {
				$this->reg_errores['sel_pais'] = '<span class="error">Por favor selecciona el pa&iacute;s</span>';
		}
		
		if (array_key_exists('cp', $_POST)) {
			//regex usada en js
			if (preg_match('/^([1-9]{2}|[0-9][1-9]|[1-9][0-9])[0-9]{3}$/', $_POST['cp'])) {
				$datos['direccion']['zip'] = $_POST['cp'];
			} 
			else {
				$this->registro_errores['cp'] = '<span class="error2">Por favor ingresa un código postal de 5 dígitos</span>';
			}
		}	
		
		if (array_key_exists('colonia', $_POST) && trim($_POST['colonia']) != ""){
			$datos['direccion']['address3'] = $_POST['colonia'];
		}
		else {
			$this->registro_errores['colonia'] = '<span class="error">Por favor ingresa la colonia</span>';
		}
		
		if (array_key_exists('ciudad', $_POST) && !empty($_POST['ciudad'])) {
			$datos['direccion']['city'] = $_POST['ciudad'];
		}
		else {
			$this->registro_errores['ciudad'] = '<span class="error">Por favor ingresa la ciudad</span>';
		}
		if (array_key_exists('estado', $_POST) && !empty($_POST['estado'])) {
			$datos['direccion']['state'] = $_POST['estado'];
		}
		else {
			$this->registro_errores['estado'] = '<span class="error">Por favor ingresa el estado</span>';
		}
	
		return $datos;
	}

	private function carga_datos_factura(){
		$this->registro_errores = array();
		$datos = array();						
		
		if(!empty($_POST['txt_rfc'])){
			if((strlen($_POST['txt_rfc'])>13)||(strlen($_POST['txt_rfc'])<12)){
			    $this->registro_errores['txt_rfc'] = '<span class="error">Por favor ingresa tu RFC</span>';		
			}	
			else{
				if(strlen($_POST['txt_rfc'])==12){
					if (preg_match('/^([a-zA-Z]{3})+([0-9]{6})+([a-zA-Z0-9]{3})$/', $_POST['txt_rfc'])) {
						$datos['direccion']['tax_id_number'] = $_POST['txt_rfc'];
					}
					else {
						$this->registro_errores['txt_rfc'] = '<span class="error">Por favor ingresa tu RFC</span>';
					}	
				}
				else if(strlen($_POST['txt_rfc'])==13){					    
					if (preg_match('/^([a-zA-Z]{4})+([0-9]{6})+([a-zA-Z0-9]{3})$/', $_POST['txt_rfc'])) {
						$datos['direccion']['tax_id_number'] = $_POST['txt_rfc'];
					}
					else {
						$this->registro_errores['txt_rfc'] = '<span class="error">Por favor ingresa tu RFC</span>';
					}
				}
			}
		}
		else{
			$this->registro_errores['txt_rfc'] = '<span class="error">Por favor ingresa tu RFC</span>';				
		}
		if(!empty($_POST['txt_razon_social'])){
			$datos['direccion']['company'] = $_POST['txt_razon_social'];
		}
		else{
			$this->registro_errores['txt_razon_social'] = '<span class="error2">Por favor ingresa tu nombre o razón social</span>';
		}
		
		if (array_key_exists('callef', $_POST)) {
			if(preg_match('/^[A-Z0-9áéíóúÁÉÍÓÚÑñ \'.-]{1,50}$/i', $_POST['callef'])) {
				$datos['direccion']['address1'] = $_POST['callef'];
			}
			else {
				$this->registro_errores['callef'] = '<span class="error">Por favor ingresa una calle</span>';
			}
		}	
		if (array_key_exists('num_extf', $_POST)) {
			if(preg_match('/^[A-Z0-9 -]{1,50}$/i', $_POST['num_extf'])) {
				$datos['direccion']['address2'] = $_POST['num_extf'];
			}
			else {
				$this->registro_errores['num_extf'] = '<span class="error">Por favor ingresa el número exterior</span>';
			}
		}	
		
		if (!empty($_POST['num_intf'])) {
			if(preg_match('/^[A-Z0-9 -]{1,50}$/i', $_POST['num_intf'])) {
				$datos['direccion']['address4'] = $_POST['num_intf'];
			} 
			else {
				$this->registro_errores['num_intf'] = '<span class="error">Por favor ingresa el número interior</span>';
			}
		} 
		else {
				$datos['direccion']['address4'] = NULL;
		}
		
		if (!empty($_POST['paisf'])) {
			$datos['direccion']['codigo_paisVc'] = $_POST['paisf'];
		}
		else {
				$this->reg_errores['sel_paisf'] = '<span class="error">Por favor selecciona el pa&iacute;s</span>';
		}
		
		if (array_key_exists('cpf', $_POST)) {
			//regex usada en js
			if (preg_match('/^([1-9]{2}|[0-9][1-9]|[1-9][0-9])[0-9]{3}$/', $_POST['cpf'])) {
				$datos['direccion']['zip'] = $_POST['cpf'];
			} 
			else {
				$this->registro_errores['cpf'] = '<span class="error2">Por favor ingresa un código postal de 5 dígitos</span>';
			}
		}	
		
		if (array_key_exists('coloniaf', $_POST) && trim($_POST['coloniaf']) != ""){
			$datos['direccion']['address3'] = $_POST['coloniaf'];
		}
		else {
			$this->registro_errores['coloniaf'] = '<span class="error">Por favor ingresa la colonia</span>';
		}
		
		if (array_key_exists('ciudadf', $_POST) && !empty($_POST['ciudadf'])) {
			$datos['direccion']['city'] = $_POST['ciudadf'];
		}
		else {
			$this->registro_errores['ciudadf'] = '<span class="error">Por favor ingresa la ciudad</span>';
		}
		if (array_key_exists('estadof', $_POST) && !empty($_POST['estadof'])) {
			$datos['direccion']['state'] = $_POST['estadof'];
		}
		else {
			$this->registro_errores['estadof'] = '<span class="error">Por favor ingresa el estado</span>';
		}
	
		return $datos;
		
	}
		
	private function envia_mail($cliente){
		$headers="Content-type: text/html; charset=UTF-8\r\n";
        $headers.="MIME-Version: 1.0\r\n";
	    $headers .= "From: Pagos Grupo Expansión<servicioaclientes@expansion.com.mx>\r\n";       
		$mensaje="<html>
				  <body>
				  	   <div>Hola, ".$cliente['salutation'].",<br /><br /> 
				  	   </div>									   
				  	   <div>
				  	      Gracias por crear tu cuenta en pagos.grupoexpansion.mx.<br /><br /> 
						  Con tu cuenta podrás almacenar tus datos para que tus siguientes compras sean más ágiles, pues no tendrás necesidad de registrar tus datos cada vez que compres aquí.<br /><br />
						  Te damos la más cordial bienvenida y esperamos que disfrutes tu compra.<br /><br />
						  Estamos disponibles para cualquier pregunta o duda sobre tu cuenta en:<br /><br/>
						  Atención a clientes<br/><br/>
						  Tel. (55) 9177 4342<br/><br/>
						  servicioaclientes@expansion.com.mx<br/><br/>
						  Cordialmente,<br/><br/>
						  Grupo Expansión.<br/>
				  	   </div>								  	   
				  </body>
				  </html>"; 									
																				     		      									
		if(mail($cliente['email'], "=?UTF-8?B?".base64_encode('¡Bienvenido a la plataforma de pagos de Grupo Expansión!')."?=", $mensaje, $headers)){
			return TRUE;
		}			
		else{
			return FALSE;
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