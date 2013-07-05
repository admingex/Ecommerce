<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*include('dtos/Tipos_Tarjetas.php');
include('util/Pago_Express.php');*/
include('api.php');

class Aeromexico extends CI_Controller {
	
	var $title = 'Suscripción Gratuita';
	var $subtitle = 'Revista Aeroméxico';
	var $detalle_promociones = array();
	var $key = 'suscripcionaeromexico';
	var $urlpdf_base = 'https://kiosco.grupoexpansion.mx/aeromexico/';
	
	/*const HASH_PAGOS = "P3lux33n3l357ux3";	//hash que se utiliza vara validar la información del lado de CCTC
	const Tipo_AMEX = 1;*/
	
	public static $TIPO_DIR = array (
		"RESIDENCE"	=> 0,
		"BUSINESS"	=> 1,
		"OTHER"		=> 2
	);
	
	
	function __construct()
	{
        parent::__construct();
		$this->load->model('direccion_facturacion_model', 'direccion_facturacion_model', true);
		$this->load->model('direccion_envio_model', 'direccion_envio_model', true);
		$this->load->model('suscripcion_gratuita_model', 'suscripcion_gratuita_model', true);
		
		$this->load->helper('date');
		$this->api = New Api();
    }
	
	/**
	 * Método del controlador que atiende la petición por defaul del sitio sin parámetros
	 */
	public function index()
	{
		$registro = site_url('aeromexico/registro/3/25/1517');
		header("Location: $registro");
	}
	
	/**
	 * Método que atenderá la peticion de suscripcion gratuita en base a la información: Sitio, Canal, Promoción
	 */
	public function registro($sitio="", $canal="", $promocion="")
	{
		$data['title'] = 'Suscripción Aeromexico';
							/*$datos['urlpdf_encriptada'] = $urlpdf_encriptada;*/
		
		// Si los parámetros para buscar la promoción son correctos...
		if (is_numeric($sitio) && !empty($sitio) && is_numeric($canal) && !empty($canal) && is_numeric($promocion) && !empty($promocion)) {
			
			$lista_paises_think = $this->direccion_facturacion_model->listar_paises_think();
			$data['lista_paises_think'] = $lista_paises_think;
			
			// meter la informacón de la promoción externa a la sesión de CI
			$this->session->set_userdata('id_sitio', $sitio);
			$this->session->set_userdata('id_canal', $canal);
			$this->session->set_userdata('id_promocion', $promocion);
			$this->session->set_userdata('promociones', array(array('id_sitio'=>$sitio, 'id_canal'=>$canal, 'id_promocion'=>$promocion)));
			
			// recuperar la información de la promoción en cuestión si es que existe
			$promoexp = array(array('id_sitio'=>$sitio, 'id_canal'=>$canal, 'id_promocion'=>$promocion));
			#### TO DO Revisar la función del API para cachar las excepciones
			$data['detalle_promociones'] = $this->api->obtiene_articulos_y_promociones($promoexp);
			
			//echo "Encontró la promoción? ". key_exists("detalle_promociones", $data);	//'datos: $_REQUEST
			//echo '<pre>';print_r($data['detalle_promociones']); echo "</pre>";exit;
			
			// el oc_id para la imagen de fondo de la promoción
			$oc_id =  key($data['detalle_promociones']['articulo_oc']);
			//echo '<pre>';print_r($data['detalle_promociones']['descripciones_promocion']['articulos']); echo "</pre>";exit;
			// se pasa la imagen en el data de la vista y se pone en la sesión
			$data['imagen_back'] = $this->suscripcion_gratuita_model->obtener_img_back($oc_id)->row()->url_imagen;
			$image_back_aeromexico = $data['imagen_back'];
			//echo '<pre>';print_r($data['imagen_back']); echo "</pre>";exit;
			//$image_back_aeromexico = "cAERO.png";
			$this->session->set_userdata('imagen_back', $image_back_aeromexico);
			
			$data['metatags'] = $this->img_obtiene($oc_id);
			$this->session->set_userdata('oc_id_img', $oc_id);
			/*echo '<pre>';print_r($data); echo "</pre>";exit;*/
			
			$tarifa_dc = $data['detalle_promociones']['tarifa'];
			//echo '<pre>';print_r($tarifa_dc); echo "</pre>";exit;
			
			if (!empty($tarifa_dc) && $tarifa_dc== "0.0000"){
			
				if ($_POST) {
					$datos = $this->get_datos_registro();
					
					if (empty($this->registro_errores)) {
						
						$this->session->set_userdata('email', $datos['email']);
						$cte = $this->suscripcion_gratuita_model->verifica_registro_email($datos['email']);
						
						// si es cliente registrado
						if ($cte->num_rows() > 0) {
							$this->session->set_userdata('reenvio', 1);
							$urlpdf_base = "reenvio";
							$urlpdf_encriptada = $this->encrypt($urlpdf_base);
							$this->session->set_userdata('urlpdf_encriptada', $urlpdf_encriptada);
							//$this->session->set_userdata('email_cliente', $datos['email']);
							$this->session->set_userdata('nombre_cliente', $datos['salutation']);
							
							$this->registro_errores['email'] = '<br><span class="validation_message"><strong>Solicitaste un registro como cliente nuevo, pero ya existe una cuenta con el correo: '.$datos['email'].'. <a href="http://dev.pagos.grupoexpansion.mx/aeromexico/contenido/'.$urlpdf_encriptada.'">Da clic aquí</a> y reenviaremos a esta cuenta de correo la información necesaria para acceder al contenido.</strong></span>';
							$data['registro_errores'] = $this->registro_errores;
							$this->cargar_vista('', 'suscripcion_gratuita/registro_cliente' , $data);
						} else {	// si es cliente nuevo
							$this->session->set_userdata('reenvio', 0);
							$id_cliente = $this->suscripcion_gratuita_model->next_cliente_id();	//id del cliente
							$datos['id_clienteIn'] = $id_cliente;
							
							$regcte = $this->suscripcion_gratuita_model->registro_cliente($datos);
							
							if ($regcte === 1) {
								$urlpdf_base = $datos['email'];
								//$key = "dev.pagos.grupoexpansion.mx.aeromexico";
								/*$urlpdf_base1 = 'http://dev.pagos.grupoexpansion.mx/aeromexico/cACC.pdf';
								$urlpdf_base2 = 'http://dev.pagos.grupoexpansion.mx/aeromexico/cAIR.pdf';
								$urlpdf_base3 = 'http://dev.pagos.grupoexpansion.mx/aeromexico/cGRP.pdf';*/
								
								//$urlpdf_encriptada = $this->encrypt($urlpdf_base, $key);
								
								
								
								//$texto = "Son unos corruptos";
	 
								// Encriptamos el texto
								$urlpdf_encriptada = $this->encrypt($urlpdf_base);
								 
								// Desencriptamos el texto
								//$texto_original = $this->decrypt($texto_encriptado);
								 
								//if ($texto == $texto_original) echo 'Encriptación / Desencriptación realizada correctamente.';
								
								
								/*$cadena = 'El veloz ';
								$patrones = array();
								$patrones[0] = '/';
	
								$sustituciones = array();
								$sustituciones[0] = 'lento';
								echo preg_replace($patrones, $sustituciones, $cadena);*/
	
								//$urlpdf_encriptada = "eJanoKSWrZqaoA";
								$this->session->set_userdata('urlpdf_encriptada', $urlpdf_encriptada);
	
								$headers = "Content-type: text/html; charset=UTF-8\r\n";
				                $headers .= "MIME-Version: 1.0\r\n";
							    $headers .= "From: Suscripción Aeromexico Grupo Expansión<servicioaclientes@expansion.com.mx>\r\n";
								$mensaje = 
									"<html>
										<body>
									  	   	<div>Hola, ".$datos['salutation'].",<br /><br /> 
									   		</div>									   
										   	<div>
										    	Gracias por registrar tus datos.<br /><br /> 
											  	Te damos la más cordial bienvenida y esperamos que disfrutes tu adquisición.<br /><br />
											  	Puedes acceder al contenido de la siguiente manera: <br /><br />
												- Sigue el siguiente link para acceder al contenido: <a href='http://dev.pagos.grupoexpansion.mx/aeromexico/contenido/".$urlpdf_encriptada."'>http://dev.pagos.grupoexpansion.mx/aeromexico/contenido/".$urlpdf_encriptada."</a><br /><br/>
												- Si seguir el link no funciona, puedes copiar y pegar el link en la barra de dirección de tu navegador, o reescribirla ahí.<br /><br/>
												<br />
												Estamos disponibles para cualquier pregunta relacionada con este correo.<br /><br/>
											  	Atención a clientes<br/><br/>
											  	Tel. (55) 9177 4342<br/><br/>
												servicioaclientes@expansion.com.mx<br/><br/>
												Cordialmente,<br/><br/>
												Grupo Expansión.<br/>
									  	   </div>								  	   
									  	</body>
		  							</html>";
								//Si se envía correctamente el correo de registro
								if (mail($datos['email'], "=?UTF-8?B?".base64_encode('¡Bienvenido al contenido Aeromexico de Grupo Expansión!')."?=", $mensaje, $headers)) {
									
									$this->session->set_userdata('id_cliente', $id_cliente);
									$consecutivo = $this->suscripcion_gratuita_model->get_consecutivo_dir($id_cliente);
									$datos['direccion']['id_consecutivoSi']= $consecutivo;
									$datos['direccion']['address_type'] = self::$TIPO_DIR['RESIDENCE'];		//address_type
									$datos['direccion']['id_clienteIn']= $id_cliente;
									
									$regdir = $this->suscripcion_gratuita_model->insertar_direccion($datos['direccion']);
									
									if ($regdir === 1) {
										
										$this->session->set_userdata('consecutivo', $consecutivo);
										$resumen = site_url('aeromexico/resumen/'.$sitio.'/'.$canal.'/'.$promocion);
										header("Location: $resumen");
									} else {
										$data['mensaje'] = "Ocurrió un problema al hacer el registro de la dirección, inténtelo nuevamente";
										$this->load->view('templates/header', $data);
										$this->load->view('mensaje', $data);
									}
								}													
							} else {
								$data['mensaje'] = "Ocurrió un problema al hacer el registro, inténtelo nuevamente.";
								$this->load->view('templates/header', $data);
								$this->load->view('mensaje', $data);
							}
						}
					} else {
						$data['registro_errores'] = $this->registro_errores;
						$this->cargar_vista('', 'suscripcion_gratuita/registro_cliente' , $data);
					}
				} else {
					$this->cargar_vista('', 'suscripcion_gratuita/registro_cliente' , $data);
				}
			}//TARIFA != 0
			else {
				#promocion inexistente				
				$data['mensaje']="Información insuficiente para completar la orden";
				$this->load->view('templates/header', $data);
				$this->load->view('mensaje', $data);	
			}

			
		} else {
			#promocion inexistente				
			$data['mensaje']="Información insuficiente para completar la orden";
			$this->load->view('templates/header', $data);
			$this->load->view('mensaje', $data);
		}
	}
	
	

	public function resumen($sitio='', $canal = '', $promocion = '')
	{
		$data['title'] = "Instrucciones para visualizar la información";
		$data['subtitle'] = "Resultado de la petición";	
		$data['metatags'] = $this->img_obtiene($this->session->userdata('oc_id_img'));	
		$id_cliente=$this->session->userdata('id_cliente');
		$urlpdf_encriptada = $this->session->userdata('urlpdf_encriptada');
		
		// Se calculan nuevamente los datos para mostar el resumen 
		$promoexp = array(array('id_sitio'=>$sitio, 'id_canal'=>$canal, 'id_promocion'=>$promocion));
		$this->detalle_promociones =$this->api->obtiene_articulos_y_promociones($promoexp);
		$data['detalle_promociones']= $this->detalle_promociones;			
		$this->load->view('suscripcion_gratuita/header', $data);	
				

		
			$orden_info = array();		

			if (empty($this->registro_errores)) {
								
				/*Recuperar la info gral. de la orden*/
				$id_cliente = $this->session->userdata('id_cliente');
				$urlpdf_encriptada = $this->session->userdata('urlpdf_encriptada');
				
				//verificar que exista la información de los artículos y promociones de la compra
				$detalle_promociones = NULL;
				if ($this->detalle_promociones) {
					$detalle_promociones = $this->detalle_promociones;
				}
				
				

				//promociones que se van a comprar, vienen en el detalle de la promoción en un array llamado "ids_promociones"
				$ids_promociones = $detalle_promociones['ids_promociones'];
				//$ids_promociones = $this->session->userdata('promocion')->id_promocionIn;				
				/*echo '<pre> entero'; print_r($ids_promociones); echo "</pre>";
				exit();*/
			
				//direcciones de envío que utilizaremos para la compra

				$estatus_pago = 1;
						
						
				//$data['resultado'] = $simple_result;
				//$data['moneda'] = $moneda;				//para desplegar la respuesta de cobro
				$data['estatus_pago']=$estatus_pago	;	
				//$data['id_compra']=$id_compra;		
				/*echo '<pre> entero'; print_r($urlpdf_encriptada); echo "</pre>";
				exit();*/
				$varreenvio = $this->session->userdata('reenvio');
				if ($varreenvio == 1) {
					$this->cargar_vista('', 'suscripcion_gratuita/reenvio', $data);
				}
				else{
					$this->cargar_vista('', 'suscripcion_gratuita/orden_compra', $data);
				}
				//$this->session->sess_destroy();	

						
				
				
				if ($_POST) {
					if ($estatus_pago==1) {
					$this->session->sess_destroy();	
					$redirect = "https://kiosco.grupoexpansion.mx/";
					header("Location: $redirect");
					}
				}


			} else {	//If Errores													

				$data['reg_errores'] = $this->registro_errores;
				$this->cargar_vista('', 'suscripcion_gratuita/orden_compra' , $data);
				
			}
	}

	// Método para validar y descargar el contenido PDF
	public function contenido($cadena_a_desencriptar="")
	{
		//SI ES IGUAL A CÓDIGO DE REENVIO PARA USUARIOS YA REGISTRADOS SE MANDA UN CORREO Y SE IMPRIME ENVIO EXITOSO
		$cadena_desencriptada = $this->decrypt($cadena_a_desencriptar);
		if ($cadena_desencriptada == "reenvio"){
								//Se recupera info suficiente para hacer el reenvio del emial y redireccionar
								$nombre_cliente = $this->session->userdata('nombre_cliente');
								$email_cliente = $this->session->userdata('email');
								$sitio = $this->session->userdata('id_sitio');
								$canal = $this->session->userdata('id_canal');
								$promocion = $this->session->userdata('id_promocion');
								$urlpdf_base = $email_cliente;
								$urlpdf_encriptada = $this->encrypt($urlpdf_base);
								$this->session->set_userdata('urlpdf_encriptada', $urlpdf_encriptada);
								
								$urlpdf_encriptada = $this->session->userdata('urlpdf_encriptada');
								
								$headers = "Content-type: text/html; charset=UTF-8\r\n";
				                $headers .= "MIME-Version: 1.0\r\n";
							    $headers .= "From: Suscripción Aeromexico Grupo Expansión<servicioaclientes@expansion.com.mx>\r\n";
								$mensaje = 
									"<html>
										<body>
									  	   	<div>Hola, ".$nombre_cliente.",<br /><br /> 
									   		</div>									   
										   	<div>
											  	Recibimos una solicitud para recuperar tu acceso al contenido de Aeromexico asociada a este correo. Si tú hiciste esta solicitud, por favor sigue las
instrucciones que aparecen abajo. Si no solicitaste el acceso, puedes ignorar este correo con tranquilidad, pues tu cuenta de cliente está segura. <br /><br />
											  	Puedes acceder al contenido de la siguiente manera: <br />
												- Sigue el siguiente link para acceder al contenido: <a href='http://dev.pagos.grupoexpansion.mx/aeromexico/contenido/".$urlpdf_encriptada."'>http://dev.pagos.grupoexpansion.mx/aeromexico/contenido/".$urlpdf_encriptada."</a><br /><br/>
												- Si seguir el link no funciona, puedes copiar y pegar el link en la barra de dirección de tu navegador, o reescribirla ahí.<br /><br/>
												<br />
												Estamos disponibles para cualquier pregunta relacionada con este correo.<br /><br/>
											  	Atención a clientes<br/><br/>
											  	Tel. (55) 9177 4342<br/><br/>
												servicioaclientes@expansion.com.mx<br/><br/>
												Cordialmente,<br/><br/>
												Grupo Expansión.<br/>
									  	   </div>								  	   
									  	</body>
		  							</html>";
								//Si se envía correctamente el correo de registro
								if (mail($email_cliente, "=?UTF-8?B?".base64_encode('¡Bienvenido al contenido Aeromexico de Grupo Expansión!')."?=", $mensaje, $headers)) {
									/*$this->session->set_userdata('id_cliente', $id_cliente);
									this->session->set_userdata('consecutivo', $consecutivo);*/
									$resumen = site_url('aeromexico/resumen/'.$sitio.'/'.$canal.'/'.$promocion);
									header("Location: $resumen");
								}//END IF ENVIA CORREO
								else {
									$data['mensaje'] = "Ocurrió un problema al hacer el reenvío, inténtelo nuevamente.";
									$this->load->view('templates/header', $data);
									$this->load->view('mensaje', $data);
								}

		}//END IF CODIGO DE REENVIO
		
		//Si no entra a verificar cliente según la cadena a desencriptar 
		else {
			//Desencriptamos el texto
			$cadena_desencriptada = $this->decrypt($cadena_a_desencriptar);

			$verifica_cliente = $this->suscripcion_gratuita_model->verifica_registro_email($cadena_desencriptada);
			
			
			if ($verifica_cliente->num_rows() > 0){
				$archivo1 = 'cACC.pdf';
				$archivo2 = 'cAIR.pdf';
				$archivo3 = 'cGRP.pdf';
				
				$this->session->set_userdata('archivo1', $archivo1);
				$this->session->set_userdata('archivo2', $archivo2);
				$this->session->set_userdata('archivo3', $archivo3);
	
				$this->mostrar_contenido('', 'suscripcion_gratuita/orden_contenido_aero', '$data');
			}
			else {
				#no muestra contenido	
				$data['title'] = 'Suscripción Aeromexico';			
				$data['mensaje']="Información insuficiente para mostrar el contenido";
				$this->load->view('templates/header', $data);
				$this->load->view('mensaje', $data);
			}
				
			/*$cadena_desencriptada = $this->decrypt($url_a_desencriptar,"1517");
			echo '<pre>'; print_r($cadena_desencriptada); echo "</pre>";
					exit();*/
		}

	}

	// Método para descargar archivos
	public function download($archivo_a_descargar){
		$_GET['file'] = $archivo_a_descargar;
		if (!isset($_GET['file']) || empty($_GET['file'])) {
 			exit();
		}
		
		$root = "aeromexico_pdf/";
		$file = basename($_GET['file']);
		$path = $root.$file;
		/*echo $path;
		exit();*/
		$type = '';
 
		if (is_file($path)) {
 			$size = filesize($path);
 			if (function_exists('mime_content_type')) {
 				$type = mime_content_type($path);
 			} else if (function_exists('finfo_file')) {
 				$info = finfo_open(FILEINFO_MIME);
 				$type = finfo_file($info, $path);
 				finfo_close($info);
 			}
 			if ($type == '') {
 				$type = "application/force-download";
 			}
 			// Definir headers
 			header("Content-Type: $type");
 			header("Content-Disposition: attachment; filename=$file");
 			header("Content-Transfer-Encoding: binary");
 			header("Content-Length: " . $size);
 			// Descargar archivo
 			ob_end_clean();
			flush();
 			readfile($path);
			} else {
 				die("El archivo no existe.");
				$registro = site_url('aeromexico/registro/3/27/1517');
				header("Location: $registro");
			}
	}



	public function get_info_sepomex($cp = 0)
	{
		
		
		if (!$cp)
			$cp = $this->input->post('codigo_postal');
		
		
		$resultado = $this->consulta_sepomex($cp);
		
		header('Content-Type: application/json',true);
		echo json_encode($resultado);
	}		
	
	private function consulta_sepomex($codigo_postal)
	{
		$resultado = array();
		
		try
		{
			$resultado['sepomex'] = $this->direccion_envio_model->obtener_direccion_sepomex($codigo_postal)->result();
			$resultado['success'] = true;
			$resultado['msg'] = "Ok";
			
			return $resultado;
		}
		catch (Exception $e)
		{
			$resultado['exception'] =  $exception;
			$resultado['msg'] = $exception->getMessage();
			$resultado['error'] = true;
			return $resultado;	
		}
				
	}	

	private function get_datos_registro()
	{
		$datos = array();
		
		if (array_key_exists('txt_nombre', $_POST)) {
			if (preg_match('/^[A-Z \'.-áéíóúÁÉÍÓÚÑñ]{2,30}$/i', $_POST['txt_nombre'])) {
				$datos['salutation'] = $_POST['txt_nombre'];
			} else {
				$this->registro_errores['txt_nombre'] = '<div class="error">Por favor ingresa tu nombre</div>';
			}
		}
		if (array_key_exists('txt_apellidoPaterno', $_POST)) {
			if(preg_match('/^[A-Z \'.-áéíóúÁÉÍÓÚÑñ]{1,30}$/i', $_POST['txt_apellidoPaterno'])) {
				$datos['fname'] = $_POST['txt_apellidoPaterno'];
			} else {
				$this->registro_errores['txt_apellidoPaterno'] = '<div class="error">Por favor ingresa tu apellido paterno</div>';
			}
		}
		if (array_key_exists('txt_apellidoMaterno', $_POST)) {
			if (preg_match('/^[A-Z \'.-áéíóúÁÉÍÓÚÑñ]{1,30}$/i', $_POST['txt_apellidoMaterno'])) {
				$datos['lname'] = $_POST['txt_apellidoMaterno'];
			} else {
				$datos['lname'] = '';
			}
		}
		if (filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
			$datos['email'] = htmlspecialchars(trim($_POST['email']));
		} else {
			$this->registro_errores['email'] = '<div class="error2">Por favor ingresa un correo electrónico <br />válido. Ejemplo: nombre@dominio.mx</div>';
		}
		if (array_key_exists('calle', $_POST)) {	//Calle
			if (preg_match('/^[A-Z0-9áéíóúÁÉÍÓÚÑñ \'.-]{1,50}$/i', $_POST['calle'])) {
				$datos['direccion']['address1'] = $_POST['calle'];
			} else {
				$this->registro_errores['calle'] = '<span class="error">Por favor ingresa una calle</span>';
			}
		}
		if (array_key_exists('num_ext', $_POST)) {	//Número esterior
			if (preg_match('/^[A-Z0-9 -]{1,50}$/i', $_POST['num_ext'])) {
				$datos['direccion']['address2'] = $_POST['num_ext'];
			} else {
				$this->registro_errores['num_ext'] = '<span class="error">Por favor ingresa el número exterior</span>';
			}
		}
		if (!empty($_POST['num_int'])) {	//Número interior
			if (preg_match('/^[A-Z0-9 -]{1,50}$/i', $_POST['num_int'])) {
				$datos['direccion']['address4'] = $_POST['num_int'];
			} else {
				$this->registro_errores['num_int'] = '<span class="error">Por favor ingresa el número interior</span>';
			}
		}
		else {
				$datos['direccion']['address4'] = NULL;
		}
		if (!empty($_POST['pais'])) {
			$datos['direccion']['codigo_paisVc'] = $_POST['pais'];
		} else {
			$this->reg_errores['sel_pais'] = '<span class="error">Por favor selecciona el pa&iacute;s</span>';
		}
		if (array_key_exists('cp', $_POST)) {
			//regex usada en js
			if (preg_match('/^([1-9]{2}|[0-9][1-9]|[1-9][0-9])[0-9]{3}$/', $_POST['cp'])) {
				$datos['direccion']['zip'] = $_POST['cp'];
			} else {
				$this->registro_errores['cp'] = '<span class="error2">Por favor ingresa un código postal de 5 dígitos</span>';
			}
		}
		if (array_key_exists('colonia', $_POST) && trim($_POST['colonia']) != "") {		//Colonia
			$datos['direccion']['address3'] = $_POST['colonia'];
		} else {
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


	
	private function cargar_vista($folder, $page, $data){	
		//Para automatizar un poco el desplieguee
		$this->load->view('suscripcion_gratuita/header', $data);
		if(!isset($data['no_mostrar_promo'])){							
			$this->load->view('suscripcion_gratuita/promocion.html', $data);
		}																						
		$this->load->view($folder.'/'.$page, $data);
		$this->load->view('suscripcion_gratuita/footer', $data);
	}

	private function mostrar_contenido($folder, $page, $data){	
		//Para automatizar un poco el desplieguee
		/*$this->load->view('suscripcion_gratuita/header', $data);
		if(!isset($data['no_mostrar_promo'])){							
			$this->load->view('suscripcion_gratuita/promocion.html', $data);
		}		*/																				
		$this->load->view($folder.'/'.$page, $data);
		$this->load->view('suscripcion_gratuita/footer', $data);
	}
	


	private function existe_predetereminada($id_cliente){
		return $this->forma_pago_model->existe_predetereminada($id_cliente);
	}
	


	private function get_datos_orden() {
		$datos = array();
		
		if (array_key_exists("txt_codigo", $_POST)) {
			if (preg_match('/^[0-9]{3,4}$/', $_POST['txt_codigo'])) { 
				$datos['cvv'] = $_POST['txt_codigo'];
			} else {
				$this->registro_errores['txt_codigo'] = 'Ingresa un código de seguridad válido';
			}
		}
		
		return $datos;
	}
	

	/**
	 * Envía un correo 
	 */
	private function enviar_correo($asunto, $mensaje) {
		$headers = "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
	    $headers .= "From: Pagos Grupo Expansión<pagosmercadotecnia@expansion.com.mx>\r\n";
		$headers .= "Bcc: jesus.aguilar@externo.expansion.com.mx\r\n";
		
		
		$email = $this->session->userdata('email');
					
		//return ($email && mail($email, $asunto, $mensaje));
		return mail($email, "=?UTF-8?B?".base64_encode($asunto)."?=", $mensaje, $headers);
	}
	
	/**
	 * Obtiene la información para los 'metatags' de TND_CatOCThink en base al oc_id
	 * @param oc_id int el oc_id de la publicación de la promoción
	 * @return metatags array(descripcion_larga, descripcion_corta, nombre) el arreglo de meta-información de la publicación
	 */
	private function img_obtiene($oc_id) {
		$datos = array();
		/*$datos['descripcion_larga'] = $this->suscripcion_express_model->obtener_img_back($oc_id)->row()->descripcion_larga;
		$datos['descripcion_corta'] = $this->suscripcion_express_model->obtener_img_back($oc_id)->row()->descripcion_corta;
		$datos['nombre'] = $this->suscripcion_express_model->obtener_img_back($oc_id)->row()->nombre;*/
		$datos['descripcion_larga'] = "Suscripción Gratis Aeromexico";
		$datos['descripcion_corta'] = "Aeroméxico";
		$datos['nombre'] = "Aeromexico";
		return $datos;
	}
	
	/*Método encripta url pdf
	private function encrypt($string, $key) {
    	$result = '';
   		for($i=0; $i<strlen($string); $i++) {
      		$char = substr($string, $i, 1);
      		$keychar = substr($key, ($i % strlen($key))-1, 1);
      		$char = chr(ord($char)+ord($keychar));
      		$result.=$char;
   		}
   		return base64_encode($result);
	}*/
	
	/*Método desencripta la url del pdf
	private function decrypt($string, $key) {
   		$result = '';
  		$string = base64_decode($string);
   		for($i=0; $i<strlen($string); $i++) {
      		$char = substr($string, $i, 1);
      		$keychar = substr($key, ($i % strlen($key))-1, 1);
      		$char = chr(ord($char)-ord($keychar));
      		$result.=$char;
   		}
   		return $result;
	}*/
	
	/**
* Description of Encrypter*/

    //private static $Key = "dublin";
 
    public static function encrypt ($input) {
    	$Key ="aeromexico";
        //$output = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($Key), $input, MCRYPT_MODE_CBC, md5(md5($Key))));
        $output = base64_encode($input);
        return $output;
    }
 
    public static function decrypt ($input) {
    	$Key ="aeromexico";
        //$output = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($Key), base64_decode($input), MCRYPT_MODE_CBC, md5(md5($Key))), "\0");
		$output = base64_decode($input);
        return $output;
    }
 

	
	
}

/* End of file suscripcion_gratuita.php */
/* Location: ./application/controllers/suscripcion_gratuita.php */