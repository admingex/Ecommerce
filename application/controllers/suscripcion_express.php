<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include('dtos/Tipos_Tarjetas.php');
include('util/Pago_Express.php');
include('api.php');

class Suscripcion_Express extends CI_Controller {
	
	var $title = 'Suscripción Express';
	var $subtitle = 'Reporte de Usuarios';
	var $detalle_promociones = array();
	
	const HASH_PAGOS = "P3lux33n3l357ux3";	//hash que se utiliza vara validar la información del lado de CCTC
	const Tipo_AMEX = 1;
	
	public static $FORMA_PAGO = array (
		1 =>	"Prosa", 
		2 =>	"American Express", 
		3 =>	"Deposito Bancario",
		4 =>	"Otro"
	);
	
	public static $TIPO_DIR = array (
		"RESIDENCE"	=> 0,
		"BUSINESS"	=> 1,
		"OTHER"		=> 2
	);
	
	public static $ESTATUS_COMPRA = array (
		"SOLICITUD_CCTC"			=> 1,
		"RESPUESTA_CCTC"			=> 2,
		"REGISTRO_PAGO_ECOMMERCE"	=> 3,
		"PAGO_DEPOSITO_BANCARIO" 	=> 4,
		"ENVIO_CORREO"				=> 5
	);
	
	public static $TIPO_PAGO = array (
		"Prosa"				=> 1, 
		"American_Express"	=> 2, 
		"Deposito_Bancario"	=> 3,
		"Otro"				=> 4
	);
	
	function __construct()
	{
        parent::__construct();
		$this->load->model('direccion_facturacion_model', 'direccion_facturacion_model', true);
		$this->load->model('direccion_envio_model', 'direccion_envio_model', true);
		$this->load->model('forma_pago_model', 'forma_pago_model', true);
		$this->load->model('suscripcion_express_model', 'suscripcion_express_model', true);
		$this->load->model('orden_compra_model', 'orden_compra_model', true);
		
		$this->load->helper('date');
		$this->api = New Api();
    }
	
	/**
	 * Método del controlador que atiende la petición por defaul del sitio sin parámetros
	 */
	public function index()
	{
		$this->datos();
	}
	
	/**
	 * Método que atenderá la compra de la suscripciónm exprés en base a la informción:
	 * Sitio, Canal, Promoción
	 * @param int $sitio el id del sitio de la promoción 
	 * @param int $canal el id del canal de la promoción 
	 * @param int $promocion el id de la promoción 
	 */
	public function datos($sitio="", $canal="", $promocion="")
	{
		$data['title'] = 'Suscripción Exprés';
		
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
			
			## Para los Google Tag Manager 
			#### TO DO  robustecer esta validacion para que el oc_id pueda ser de cualquier publicacion, por ahora sólo Quién
			if (isset($data['detalle_promociones']['articulo_oc'])) {
				//limpiar la sesión y la variable
				$this->session->unset_userdata('tags_google');
				unset($data['tags_google']);
				// revisar si está el oc_id de Quién
				if (array_key_exists(94, $data['detalle_promociones']['articulo_oc'])) {
					$data['tags_google'] = 1;
					$this->session->set_userdata('tags_google', 1);
				}
			}
			//echo "Encontró la promoción? ". key_exists("detalle_promociones", $data);	//'datos: $_REQUEST
			/*echo '<pre>';print_r($data['detalle_promociones']); echo "</pre>";exit;*/
			
			// el oc_id para la imagen de fondo de la promoción
			$oc_id =  key($data['detalle_promociones']['articulo_oc']);
			// se pasa la imagen en el data de la vista y se pone en la sesión
			$data['imagen_back'] = $this->suscripcion_express_model->obtener_img_back($oc_id)->row()->url_imagen;
			$this->session->set_userdata('imagen_back', $data['imagen_back']);
			
			$data['metatags'] = $this->img_obtiene($oc_id);
			$this->session->set_userdata('oc_id_img', $oc_id);
			/*echo '<pre>';print_r($data); echo "</pre>";exit;*/
			
			$moneda_pais= $data['detalle_promociones']['moneda'];
			/*echo '<pre>';print_r($moneda_pais); echo "</pre>";exit;*/
			
			if ($_POST) {
				$datos = $this->get_datos_registro();
				
				if (empty($this->registro_errores)) {
					
					$this->session->set_userdata('email', $datos['email']);
					$cte = $this->suscripcion_express_model->verifica_registro_email($datos['email']);
					
					// si es cliente registrado
					if ($cte->num_rows() > 0) {
						/*echo "SI EXISTE USUARIO";
							exit();*/
						$ctereg = end($cte->result_object());
						
						/*echo '<pre>';print_r($ctereg); echo "</pre>";exit;*/
						$this->session->set_userdata('id_cliente', $ctereg->id_clienteIn);
						
						$datos['direccion']['address_type'] = self::$TIPO_DIR['RESIDENCE'];	//address_type
						$datos['direccion']['id_clienteIn']= $ctereg->id_clienteIn;
						$dirreg = $this->suscripcion_express_model->existe_direccion($datos['direccion']);
						//echo 'existe regdir: <pre>'; print_r($dirreg)."<br/>"; echo "num_rows > ". $dirreg->num_rows(); print_r($datos['direccion']);echo "</pre>"; exit;
						// si tiene alguna dirección de envío registrada, pasa al pago
						if ($dirreg->num_rows() > 0) {
							//echo '<pre>'; echo "regdir". print_r($dirreg)."<br/>"; print_r($cte->result_object()); print_r($datos['direccion']); print_r($datos); echo "</pre>"; exit;
							$dir = end($dirreg->result_object());
							/*echo "SI EXISTE DIRECCIÓN";
							exit();*/
							//echo '<pre>';print_r($dir); echo "</pre>";exit;
							$this->session->set_userdata('consecutivo', $dir->id_consecutivoSi);
							$pago = site_url('suscripcion_express/pago/'.$sitio.'/'.$canal.'/'.$promocion);
							header("Location: $pago");
						} else {	// si no tiene alguna dirección de envío registrada, la registra
							/*echo "NO EXISTE DIRECCIÓN";
							exit();*/
							$consecutivo = $this->suscripcion_express_model->get_consecutivo_dir($ctereg->id_clienteIn);
							$datos['direccion']['id_consecutivoSi'] = $consecutivo;
							$datos['direccion']['address_type'] = self::$TIPO_DIR['RESIDENCE'];		//address_type
							$datos['direccion']['id_clienteIn']= $ctereg->id_clienteIn;
							
							$regdir = $this->suscripcion_express_model->insertar_direccion($datos['direccion']);
							/*echo 'after insert, regdir <pre>'; $regdir; print_r($cte->result_object()); print_r($datos['direccion']); print_r($datos); echo "</pre>"; exit;*/
							if ($regdir === 1) {	//si se registró correctamente la dirección
								$this->session->set_userdata('consecutivo', $consecutivo);
								$pago = site_url('suscripcion_express/pago/'.$sitio.'/'.$canal.'/'.$promocion);
								header("Location: $pago");
							} else {
								$data['mensaje'] = "Ocurrió un problema al hacer el registro de dirección, intentelo nuevamente.";
								$this->load->view('templates/header', $data);
								$this->load->view('mensaje', $data);
							}
						}
					} else {	// si es cliente nuevo
						/*echo "NO EXISTE USUARIO";
							exit();*/
						$id_cliente = $this->suscripcion_express_model->next_cliente_id();	//id del cliente
						$datos['id_clienteIn'] = $id_cliente;
						####
						####
						#### falta armar el formato del correo para enviar el password y notificar el registro
						####
						####
						//paswwor a enviar
						$pass = $this->genera_pass();
						$datos['password'] = md5($datos['email']."|".$pass);
						
						$regcte = $this->suscripcion_express_model->registro_cliente($datos);
						if ($regcte === 1) {
						
							$headers = "Content-type: text/html; charset=UTF-8\r\n";
			                $headers .= "MIME-Version: 1.0\r\n";
						    $headers .= "From: Pagos Grupo Expansión<servicioaclientes@expansion.com.mx>\r\n";
							$mensaje = 
								"<html>
									<body>
								  	   	<div>Hola, ".$datos['salutation'].",<br /><br /> 
								   		</div>									   
									   	<div>
									    	Gracias por crear tu cuenta en pagos.grupoexpansion.mx.<br /><br /> 
										  	Con tu cuenta podrás almacenar tus datos para que tus siguientes compras sean más ágiles, pues no tendrás necesidad de registrar tus datos cada vez que compres aquí.<br /><br />
										  	Te damos la más cordial bienvenida y esperamos que disfrutes tu compra.<br /><br />
										  	Tu password de acceso a nuestra plataforma de pagos es la siguiente: ".$pass."<br /><br />
											Estamos disponibles para cualquier pregunta o duda sobre tu cuenta en:<br /><br/>
										  	Atención a clientes<br/><br/>
										  	Tel. (55) 9177 4342<br/><br/>
											servicioaclientes@expansion.com.mx<br/><br/>
											Cordialmente,<br/><br/>
											Grupo Expansión.<br/>
								  	   </div>								  	   
								  	</body>
	  							</html>";
							//Si se envía correctamente el correo de registro
							if (mail($datos['email'], "=?UTF-8?B?".base64_encode('¡Bienvenido a la plataforma de pagos de Grupo Expansión!')."?=", $mensaje, $headers)) {
								
								$this->session->set_userdata('id_cliente', $id_cliente);
								$consecutivo = $this->suscripcion_express_model->get_consecutivo_dir($id_cliente);
								$datos['direccion']['id_consecutivoSi']= $consecutivo;
								$datos['direccion']['address_type'] = self::$TIPO_DIR['RESIDENCE'];		//address_type
								$datos['direccion']['id_clienteIn']= $id_cliente;
								
								$regdir = $this->suscripcion_express_model->insertar_direccion($datos['direccion']);
								if ($regdir === 1) {
									$this->session->set_userdata('consecutivo', $consecutivo);
									$pago = site_url('suscripcion_express/pago/'.$sitio.'/'.$canal.'/'.$promocion);
									header("Location: $pago");
								} else {
									$data['mensaje'] = "Ocurrió un problema al hacer el registro de la dirección, inténtelo nuevamente";
									$this->load->view('templates/header', $data);
									$this->load->view('mensaje', $data);
								}
							}													
						} else {
							$data['mensaje'] = "Ocurrió un problema al hacer el registro del cliente, inténtelo nuevamente.";
							$this->load->view('templates/header', $data);
							$this->load->view('mensaje', $data);
						}
					}
				} else {
					$data['registro_errores'] = $this->registro_errores;
					$this->cargar_vista('', 'suscripcion_express/registro_cliente' , $data);
				}
			} else {
				$this->cargar_vista('', 'suscripcion_express/registro_cliente' , $data);
			}
		} else {
			#promocion inexistente				
			$data['mensaje']="Información insuficiente para completar la orden";
			$this->load->view('templates/header', $data);
			$this->load->view('mensaje', $data);
		}
	}
	
	/**
	 * Atenderá el proceso del pago luego del registro del cliente y/o de su dirección
	 */
	public function pago($sitio='', $canal='', $promocion ='')
	{
		
		$data['title']='Suscripción express';
		
		$sitio = $this->session->userdata('id_sitio');
		$canal = $this->session->userdata('id_canal');
		$promocion = $this->session->userdata('id_promocion');
		$id_cliente = $this->session->userdata('id_cliente');
		/*echo "<pre>";
			print_r($id_cliente);
		echo "</pre>";
		exit();
		 * && !empty($data['detalle_promociones']) */
		
		if (is_numeric($sitio) && !empty($sitio) && is_numeric($canal) && !empty($canal) && is_numeric($promocion) && !empty($promocion) && !empty($id_cliente)) {
		$promoexp = array(array('id_sitio'=>$sitio, 'id_canal'=>$canal, 'id_promocion'=>$promocion));
		$data['detalle_promociones'] = $this->api->obtiene_articulos_y_promociones($promoexp);
		/*
		echo "<pre>";
			print_r($data['detalle_promociones']);
		echo "</pre>";	
		*/
		
		$lista_paises_amex = $this->forma_pago_model->listar_paises_amex();
		$data['lista_paises_amex'] = $lista_paises_amex;
		
		$data['promo'] = $this->api->obtener_detalle_promo($sitio, $canal, $promocion );
		$data['lista_tipo_tarjeta'] = $this->forma_pago_model->listar_tipos_tarjeta();
		$data['metatags'] = $this->img_obtiene($this->session->userdata('oc_id_img'));
		

		$this->load->view('suscripcion_express/header', $data);
		/*echo "<pre>";	
		print_r($data['detalle_promociones']['lleva_ra']);
		echo "</pre>";*/
		
		if ($_POST) {
			
			$tarjeta = $this->get_datos_tarjeta();
			
			if (empty($this->reg_errores)) {
				/*
				echo "<pre>";							
					print_r($_POST);
				echo "</pre>";
				echo "<pre>";	
					print_r($this->session->all_userdata());
				echo "</pre>";
				echo "<pre>";	
					print_r($tarjeta);
				echo "</pre>";
				*/
				//guarda en sesion los datos de tarjeta
				$this->session->set_userdata('tarjeta', $tarjeta);
								
				//si la promocion lleva RA entra a este caso
				if ($data['detalle_promociones']['lleva_ra'] != 0){
					//se guarda en sesion un indicador para guardar TC
					$this->session->set_userdata('gt', 1);
				}	
				
				//id de cliente recuperado de session
				$id_cliente = $this->session->userdata('id_cliente');																	
																																										
				//funcion para registrar la tarjeta											
				$this->registrar_tc($id_cliente);																
								
				echo "  <form id='form_pago' name='form_pago' action ='".site_url('suscripcion_express/checkout/'.$sitio.'/'.$canal.'/'.$promocion)."' method='POST'>
							<input type='text' name='txt_codigo' value='".$_POST['txt_codigo']."' style='display: none' />
							<input type='text' name='sel_tipo_tarjeta' value='".$_POST['sel_tipo_tarjeta']."' style='display: none' />							
							<input type='submit' name='enviar' value = '' style='display: none'/>
						</form>";
				echo " <script>document.getElementById('form_pago').submit()</script>";
				
				//$this->checkout();
				//$pago = site_url('suscripcion_express/resumen');
				//header("Location: $pago");													
				 				
			}
			else{
				$data['reg_errores'] = $this->reg_errores;					
				$this->cargar_vista('', 'suscripcion_express/pago' , $data);				
			}												 												
		}		
		else{			
			$this->cargar_vista('', 'suscripcion_express/pago' , $data);						 
		}
		}
	else {
		#promocion inexistente				
			$data['mensaje']="Información insuficiente para completar la orden";
			$this->load->view('templates/header', $data);
			$this->load->view('mensaje', $data);
	}
		
	}
	
	
	/**
	 * Recupera y despliega la información de la orden de compra en curso.
	 */
	public function resumen() 
	{
		$data['title']='Suscripción express';		
		$this->detalle_promociones =$this->api->obtiene_articulos_y_promociones($this->session->userdata('promociones'));
		$data['detalle_promociones']= $this->detalle_promociones;
		/*
		echo "<pre>";
			print_r($this->session->all_userdata());	
			print_r($this->detalle_promociones);		
		echo "</pre>";
		*/
						
		
		//gestión de la dirección de envío	TRUE / FALSE
		$data['requiere_envio'] = $this->session->userdata('consecutivo');
		
		/*Recuperar la info gral. de la orden*/
		$id_cliente = $this->session->userdata('id_cliente');

		//recuperar la tarjeta/forma de pago
		$tarjeta = $this->session->userdata('tarjeta');
		
		//print_r($tarjeta);
		
		//revisar si hay depósito bancario como forma de pago
		if (!empty($tarjeta)) {		
			//no se guarda la tarjeta en la BD, la información sólo está en sesión
			if (is_array($this->session->userdata('tarjeta'))) {
				$tarjeta = (object)$tarjeta;
				$detalle_tarjeta = (object)$tarjeta->tc;
				
				$data['tc'] = $detalle_tarjeta;
				
				//if ($detalle_tarjeta->id_tipo_tarjetaSi == 1) { //la tarjeta es AMERICAN EXPRESS
				if ($detalle_tarjeta->id_tipo_tarjetaSi == 1) { //la tarjeta es AMERICAN EXPRESS
					$data['amex'] = (object)$tarjeta->amex;
					//en este caso se consultará la info del WS
				}

			} else if (is_integer((int)$this->session->userdata('tarjeta'))) {				
				//la tarjeta está guardada en la BD 
				$consecutivo = $this->session->userdata('tarjeta');		//consecutivo de la tarjeta para el cliente
				
				//trae la información de la TC de l BD
				$detalle_tarjeta = $this->forma_pago_model->detalle_tarjeta($consecutivo, $id_cliente);
				$data['tc'] = $detalle_tarjeta;
			
				//if ($detalle_tarjeta->id_tipo_tarjetaSi == 1) { //la tarjeta es AMERICAN EXPRESS
				if ($detalle_tarjeta->id_tipo_tarjetaSi == 1) { //la tarjeta es AMERICAN EXPRESS
					//$data['amex'] = $this->detalle_tarjeta_CCTC($id_cliente, $consecutivo);			//antigua interfase con CCTC
					$data['amex'] = $this->obtener_detalle_interfase_CCTC($id_cliente, $consecutivo);	//cambio de interfase con CCTC
					//en este caso se consultará la info del WS
					//print_r($data['amex']);
					//exit;
				}
				
			}
			
		}
		
		//dir_envío
		$dir_envio = $this->session->userdata('consecutivo');
		//de momento esta no se ocupa ya que la compra express es de una sola promocion
		
		
		
		/**
		 * Ajuste de direcciones múltiples:
		 * 	todas las promociones tendrán asociadas una dirección antes de pasar a mostrar el resumen de la orden, sin importar que sólo sea la misma para todas. 
		 * 	Si existe en sesión "dse" ("dse" => direcciones de envio), se recupera la información de las direcciones asociadas.
		 * 	Si no existe en sesión "dse" ("dse" => direcciones de envio), se crea aquí.
		 */
		
		$detalles_direcciones = array();		//Información de las direcciones que se mostrará
		
		if (!empty($dir_envio)) {	//si no existe, se crea "dse" y se recupera la información de la dirección general para todas las promociones
			//recuperar las promociones para saber cuál requiere envío
			$detalle_promociones = NULL;
			if ($this->detalle_promociones) {
				$detalle_promociones = $this->detalle_promociones;
			}

			//si existe una dirección de envío inicial y la información detallada de las promociones...
			if (is_integer((int)$dir_envio) && $detalle_promociones) {
				
				//lo que se pasará a la vista para las direcciones de las promociones
				$promos_dirs = array();					//arreglo (id_promocion => id_direccion)
				
				//se crea "dse" ("dse" => direcciones de envio)
				$dir_general = $dir_envio;	//echo "dir_gral. " . $dir_general . "<br/>";
				
				//$detalles_direcciones = array();		//información de las direcciones que se mostrará, se pasará a la vista en el data
				$detalle_dir_gral = $this->direccion_envio_model->detalle_direccion($dir_general, $id_cliente);	//detalle de la información para todas las promociones
				
				//colocar en el arreglo de direcciones todas las promociones que puedan tener envío
				foreach ($detalle_promociones['descripciones_promocion'] as $p) {
					//si requiere dirección de envío se mete al arreglo
					if ($p['promocion']->requiere_envio) {
						$id_promo = $p['promocion']->id_promocionIn;
						$promos_dirs[$id_promo] = $dir_general;
						$detalles_direcciones[$id_promo] = $detalle_dir_gral;
					}
				}
				//colocar en el arreglo de direcciones el id de la promoción que se quiere asociar con alguna dirección antes de ponerlo en sesión
				$this->session->set_userdata('dse', $promos_dirs);

				$data['direcciones'] = $detalles_direcciones;
				$data['dse'] = $promos_dirs;
			}
		}	
		
		$this->session->userdata('requiere_factura', 'no');
		$data['no_mostrar_promo']=TRUE;

		//colocar en sesión que ya pasó por el resumen de la orden de compra
		$this->id_cliente = $this->session->set_userdata('paso_orden_compra', TRUE);
						
		//cargar vista
		$this->cargar_vista('', 'suscripcion_express/orden_compra' , $data);
		
		//$this->cargar_vista('', 'orden_compra', $data);
	}
	
	public function checkout($sitio='', $canal = '', $promocion = '')
	{
		
		
				
		$data['title'] = "Resultado de la petición de cobro";
		$data['subtitle'] = "Resultado de la petición de cobro";	
		$data['datos_login'] = '';
		$data['metatags'] = $this->img_obtiene($this->session->userdata('oc_id_img'));	
		$id_cliente=$this->session->userdata('id_cliente');
		
		###se calculan nuevamente los datos para mostar el resumen si el codigo de verificacionno es correcto
		$this->detalle_promociones =$this->api->obtiene_articulos_y_promociones($this->session->userdata('promociones'));
		$data['detalle_promociones']= $this->detalle_promociones;	
		/*echo "<pre>";
			print_r($data['detalle_promociones']);
		echo "</pre>";
		exit();		*/
		$this->load->view('suscripcion_express/header', $data);	
				
		$data['requiere_envio'] = $this->session->userdata('consecutivo');
		//GUARDA EN SESIÓN SI REQUIERE ENVIO TRUE O FALSE
		$this->session->set_userdata('requiere_envio', $data['requiere_envio']);
		$dir_envio = $this->session->userdata('consecutivo');
		
		/*echo '<pre>'; print_r($data['detalle_promociones']); echo "</pre>";
		echo '<pre>'; print_r($dir_envio); echo "</pre>";
		exit();*/
		 /*$info_sesion = $this->session->all_userdata();
		 echo '<pre>'; print_r($dir_envio); echo "</pre>";
		exit();*/
		 
		$detalles_direcciones = array();		//Información de las direcciones que se mostrará

		if (!empty($dir_envio)) {	//si no existe, se crea "dse" y se recupera la información de la dirección general para todas las promociones
			//recuperar las promociones para saber cuál requiere envío
			$detalle_promociones = NULL;
			if ($this->detalle_promociones) {
				$detalle_promociones = $this->detalle_promociones;
			}
		 /*echo '<pre>'; print_r($detalle_promociones); echo "</pre>";
		exit();*/

			//si existe una dirección de envío inicial y la información detallada de las promociones...
			if (is_integer((int)$dir_envio) && $detalle_promociones) {
				
				//lo que se pasará a la vista para las direcciones de las promociones
				$promos_dirs = array();					//arreglo (id_promocion => id_direccion)
				
				//se crea "dse" ("dse" => direcciones de envio)
				$dir_general = $dir_envio;	//echo "dir_gral. " . $dir_general . "<br/>";
				
				//$detalles_direcciones = array();		//información de las direcciones que se mostrará, se pasará a la vista en el data
				$detalle_dir_gral = $this->direccion_envio_model->detalle_direccion($dir_general, $id_cliente);	//detalle de la información para todas las promociones
				/*echo '<pre>'; print_r($detalle_dir_gral); echo "</pre>";
				exit();*/
				//colocar en el arreglo de direcciones todas las promociones que puedan tener envío
				foreach ($detalle_promociones['descripciones_promocion'] as $p) {
					//si requiere dirección de envío se mete al arreglo
					if ($p['promocion']->requiere_envio) {
						$id_promo = $p['promocion']->id_promocionIn;
						$promos_dirs[$id_promo] = $dir_general;
						$detalles_direcciones[$id_promo] = $detalle_dir_gral;
					}
				}
				/*echo '<pre>'; print_r($detalles_direcciones); echo "</pre>";
				echo '<pre>'; print_r($promos_dirs); echo "</pre>";
				exit();*/
				//colocar en el arreglo de direcciones el id de la promoción que se quiere asociar con alguna dirección antes de ponerlo en sesión
				$this->session->set_userdata('dse', $promos_dirs);

				$data['direcciones'] = $detalles_direcciones;
				$data['dse'] = $promos_dirs;
			}
		}
		
		$tarjeta = $this->session->userdata('tarjeta');
		
		/*print_r($tarjeta);
		echo '<pre>'; print_r($tarjeta); echo "</pre>";
		exit();*/
		
		//revisar si hay TC como forma de pago
		if (!empty($tarjeta)) {		
			//no se guarda la tarjeta en la BD, la información sólo está en sesión
			if (is_array($this->session->userdata('tarjeta'))) {
				
				$tarjeta = (object)$tarjeta;
				$detalle_tarjeta = (object)$tarjeta->tc;
				
				$data['tc'] = $detalle_tarjeta;
				
				//if ($detalle_tarjeta->id_tipo_tarjetaSi == 1) { //la tarjeta es AMERICAN EXPRESS
				if ($detalle_tarjeta->id_tipo_tarjetaSi == 1) { //la tarjeta es AMERICAN EXPRESS
					$data['amex'] = (object)$tarjeta->amex;
					//en este caso se consultará la info del WS
				}
				/*echo '<pre> array'; print_r($data['tc']); echo "</pre>";
				exit();*/

			} else if (is_integer((int)$this->session->userdata('tarjeta'))) {				
				//la tarjeta está guardada en la BD 
				$consecutivo = $this->session->userdata('tarjeta');		//consecutivo de la tarjeta para el cliente
				//trae la información de la TC de l BD
				$detalle_tarjeta = $this->forma_pago_model->detalle_tarjeta($consecutivo, $id_cliente);
				$data['tc'] = $detalle_tarjeta;
			
				//if ($detalle_tarjeta->id_tipo_tarjetaSi == 1) { //la tarjeta es AMERICAN EXPRESS
				if ($detalle_tarjeta->id_tipo_tarjetaSi == 1) { //la tarjeta es AMERICAN EXPRESS
					//$data['amex'] = $this->detalle_tarjeta_CCTC($id_cliente, $consecutivo);			//antigua interfase con CCTC
					$data['amex'] = $this->obtener_detalle_interfase_CCTC($id_cliente, $consecutivo);	//cambio de interfase con CCTC
					//en este caso se consultará la info del WS
				}
				/*echo '<pre> entero'; print_r($data['tc']); echo "</pre>";
				exit();*/
				
			}
			
		}	
			
		###se calculan nuevamente los datos para mostar el resumen si el codigo de verificacionno es correcto	
		
		
		/*Realizar el pago en CCTC*/
		if ($_POST) {
			$orden_info = array();		
			$orden_info = $this->get_datos_orden();
			/*echo '<pre> entero'; print_r($orden_info); echo "</pre>";
			exit();*/
									
			if (empty($this->registro_errores)) {
								
				/*Recuperar la info gral. de la orden*/
				$id_cliente = $this->session->userdata('id_cliente');
				
				//verificar que exista la información de los artículos y promociones de la compra
				$detalle_promociones = NULL;
				if ($this->detalle_promociones) {
					$detalle_promociones = $this->detalle_promociones;
				}
				
				
				//forma pago
				$consecutivo 	= $this->session->userdata('tarjeta') ? $this->session->userdata('tarjeta') : $this->session->userdata('deposito');
				
				//promociones que se van a comprar, vienen en el detalle de la promoción en un array llamado "ids_promociones"
				$ids_promociones = $detalle_promociones['ids_promociones'];
				//$ids_promociones = $this->session->userdata('promocion')->id_promocionIn;				
				/*echo '<pre> entero'; print_r($ids_promociones); echo "</pre>";
				exit();*/
			
				//direcciones de envío que utilizaremos para la compra
				$ids_direcciones_envio = array();
				$ids_direcciones_envio = $this->session->userdata('dse');
				/*echo '<pre> entero'; print_r($ids_direcciones_envio); echo "</pre>";
				exit();*/
				
				//detalles para las direcciones asociadas
				$detalles_direcciones = array();
				if ($ids_direcciones_envio) {
					/*echo '<pre> ENTRO AL IF CON DIRECCIONES DE ENVIO'; print_r($ids_direcciones_envio); echo "</pre>";
					exit();*/
					foreach ($ids_direcciones_envio as $id_promocion => $id_direccion_env) {
						$detalles_direcciones[$id_promocion] = $this->direccion_envio_model->detalle_direccion($id_direccion_env, $id_cliente);
						/*echo '<pre> ENTRO AL FOR CON DIRECCIONES DE ENVIO'; print_r($detalles_direcciones[$id_promocion]); echo "</pre>";
						exit();*/
					}
				}
				
				
								
				//$digito = (isset($_POST['txt_codigo'])) ? $_POST['txt_codigo'] : 0;
				$digito = (!empty($orden_info)) ? $orden_info['cvv'] : 0;
				
				//encriptación del dígito verificador...
				$digito_rsa = $this->encriptar_rsa_texto($digito);
				
				//revisar que se haya encriptado correctamente
				if (!$digito_rsa) {
					//si regresa con FALSE, hubo un error en la encriptación, cancelar la compra
					redirect('mensaje/'.md5(7), 'refresh');		//error al encriptar la información
										
				}
				/*echo '<pre> entero'; print_r($digito_rsa); echo "</pre>";
				exit();*/
				### Ajuste para saber qué tipo de tarjeta se utiliza en base al primer dígito del número de la tarjeta 
				/**
				 * Sólo hay que insertar el campo en la tabla "CMS_RelCompraPago" al momento de solicitar el cobro en el registro de la compra.
				 */
				$primer_digito = 0;	//depósito bancario en principio
				
				
				// informaciòn de la Orden para pedir que se cobre en CCTC
				$informacion_orden = new stdClass;
				//inicialización de valores de los miembros del objeto
				$informacion_orden->id_clienteIn = $id_cliente;				//id del cliente
				$informacion_orden->consecutivo_cmsSi = $consecutivo;		//consecutivo de la tarjeta
				$informacion_orden->digito = $digito_rsa;					//dígito verificador encriptado
				$informacion_orden->id_compraIn = 0;						//id de la compra, inicialmente 0
				$informacion_orden->id_promocionIn = 0;						//id de la promocion, inicialmente 0
				
				$moneda = $detalle_promociones['moneda'];					//el identificador del tipo de moneda utilizado para el cobro
				$informacion_orden->currency = $moneda;
											
				//recuperar el total de la compra con ayuda del API
				$monto = $detalle_promociones['total_pagar'] + $detalle_promociones['total_iva'];
				$informacion_orden->monto = $monto;						//monto total a que se cobrará a través de la plataforma
				
				//el hash se debe calcular y colocar en el objeto que se pasará a CCTC
				$informacion_orden->hash = md5($digito_rsa . $id_cliente . self::HASH_PAGOS . $monto . $moneda);		//hash que se utiliza vara validar la información del lado de CCTC
				
				//renovación automática...
				$informacion_orden->ra = $detalle_promociones['lleva_ra'];	//del detalle de las promociones
				
				#### Comienza el proceso de cobro / pago
				
				$tipo_pago = self::$TIPO_PAGO['Otro'];	//ninguno válido al inicio
				
				/*echo '<pre> entero'; print_r($informacion_orden); echo "</pre>";
				exit();			*/	
				#### Configuración de la forma de pago y solicitud de cobro a CCTC
				
				if (is_array($this->session->userdata('tarjeta'))) {
					//////////// pago con tarjetas no guardada en BD y que está en sesión
					echo "TARJETA NO GUARDADA";
					exit();	
				
					$detalle_tarjeta = $this->session->userdata('tarjeta');					
					$tc = $detalle_tarjeta['tc'];
					$data['tc']= (object)$detalle_tarjeta['tc'];
					$tc = (array)$tc;
					
					//echo var_dump($tc);
					$tc_soap = new stdClass;
					$tc_soap->id_clienteIn = $tc['id_clienteIn'];
					$tc_soap->consecutivo_cmsSi = $tc['id_TCSi'];
					$tc_soap->id_tipo_tarjeta = $tc['id_tipo_tarjetaSi'];
					$tc_soap->nombre_titular = $tc['nombre_titularVc'];
					$tc_soap->apellidoP_titular = $tc['apellidoP_titularVc'];
					$tc_soap->apellidoM_titular = $tc['apellidoM_titularVc'];
					$tc_soap->numero = $tc['terminacion_tarjetaVc'];
					$tc_soap->mes_expiracion = $tc['mes_expiracionVc'];
					$tc_soap->anio_expiracion = $tc['anio_expiracionVc'];
					$tc_soap->renovacion_automatica = 1;
					
					//para saber  qué tipo de tarjeta es
					$primer_digito = substr($tc_soap->numero, 0, 1);	//sólo el primero
					
					//consecutivo de la información del pago es 1 para que pase el cobro
					$informacion_orden->consecutivo_cmsSi = $tc['id_TCSi'];		//Debe ser 0
					
					//si es Visa o Master card
					$tipo_pago = self::$TIPO_PAGO['Prosa'];	
					
					$amex_soap = NULL;
					
					//if ($detalle_tarjeta['tc']['id_tipo_tarjetaSi'] == 1) { //es AMERICAN EXPRESS
					if ($detalle_tarjeta['tc']['id_tipo_tarjetaSi'] == self::Tipo_AMEX) { //es AMERICAN EXPRESS
						$amex = $detalle_tarjeta['amex'];
						if (isset($amex)) {
							$amex_soap = new stdClass;
							$amex_soap->id_clienteIn = $amex['id_clienteIn'];
							$amex_soap->consecutivo_cmsSi = $amex['id_TCSi'];
							$amex_soap->nombre =$amex['nombre_titularVc'];
							$amex_soap->apellido_paterno = $amex['apellidoP_titularVc'];
							$amex_soap->apellido_materno = $amex['apellidoM_titularVc'];
							$amex_soap->pais = $amex['pais'];
							$amex_soap->codigo_postal = $amex['codigo_postal'];
							$amex_soap->calle = $amex['calle'];
							$amex_soap->ciudad = $amex['ciudad'];
							$amex_soap->estado = $amex['estado'];
							$amex_soap->mail = $amex['mail'];
							$amex_soap->telefono = $amex['telefono'];
						}
						
						//si es AMEX
						$tipo_pago = self::$TIPO_PAGO['American_Express'];
					}
										
					//intentamos el Pago con pasando los objetos a CCTC //
					try {
						
						//si la compra no se ha generado y guardado en sesión en algún intento previo de pago
						if ($this->session->userdata('id_compra')) {
							$id_compra = $this->session->userdata('id_compra');
						} else {	//////registrar la orden de compra y el detalle del pago con Tc "no guardada"
							$id_compra = $this->registrar_orden_compra($id_cliente, $ids_promociones, $ids_direcciones_envio, $tipo_pago, $primer_digito);	
						}
						
						if (!$id_compra) {			//Si falla el registro inicial de la compra en CCTC
							redirect('mensaje/'.md5(3), 'refresh');
						}
						
						//pasar el id de la compra para el pago
						$informacion_orden->id_compraIn = $id_compra;
						
						##Test
						/*echo "info_orden<pre>";
						print_r($informacion_orden);
						echo "</pre>";
						exit();
						*/
						
						//petición de pago a través de la interfase, el resultado ya es un objeto
						$simple_result = $this->solicitar_pago_CCTC_objetos($tc_soap, $amex_soap, $informacion_orden);
						
						## Test Result
						//echo "simple_result<pre>";
						//print_r($simple_result);
						//echo "</pre>";
						//exit;
						
						//Registro del estatus de la respuesta de CCTC
						$this->registrar_estatus_compra($id_compra, (int)$id_cliente, self::$ESTATUS_COMPRA['RESPUESTA_CCTC']);
						
						//Registro de la respuesta de CCTC de la compra en ecommerce
						$info_detalle_pago_tc = array('id_compraIn'=> $id_compra, 'id_clienteIn' => $id_cliente, 'id_TCSi' => $tc['id_TCSi'], 
														'id_transaccionBi' => $simple_result->id_transaccionNu, 'respuesta_bancoVc' => $simple_result->respuesta_banco,
														'codigo_autorizacionVc' => $simple_result->codigo_autorizacion, 'mensaje' => $simple_result->mensaje);
														
						//Registro de la respuesta del pago en ecommerce
						$this->registrar_detalle_pago_tc($info_detalle_pago_tc);
						//actualizar el id de la tarjeta, el primer_digito y el tipo_pago en "CMS_RelCompraPago", es como un trigger
						$this->actualizar_info_compra_tc($id_cliente, $id_compra, NULL, $primer_digito, $tipo_pago);
						
						$this->registrar_estatus_compra($id_compra, (int)$id_cliente, self::$ESTATUS_COMPRA['REGISTRO_PAGO_ECOMMERCE']);
						
						/*
						## para la prueba del correo
						$id_compra = 0;	//para el test
						$simple_result = NULL;
						$simple_result->codigo_autorizacion = 12321;
						## end pruebas*/
						
						//envío del correo
						$mensaje = "<html>
									  <body>
									  	   <div>
									  	   Hola ".$this->session->userdata('username').",<br />
										   Gracias por tu orden en pagos.grupoexpansion.mx
										   <br />
										   <br />
										   <table cellspacing='0' style=' border: solid; border-width: 1px; border-color: #E70030; width: 600px'>
										       <thead style='background-color: #E70030; color: #FFF'>
										           <tr>
										               <th colspan='2' align='left'>Pago, envío y facturación
										               </th>
										           </tr>    
										       </thead>
										       <tbody>
											   	   <tr>
											   	       <td valign='top' style='width: 300px;'>
											   	         <b>Método de pago:</b><br />".$detalle_tarjeta['tc']['descripcionVc']." con terminación ".substr($detalle_tarjeta['tc']['terminacion_tarjetaVc'], -4)."<br />
											   	         <b>Código de autorización:</b>&nbsp;&nbsp;".$simple_result->codigo_autorizacion."<br />
											   	         <b>Fecha de autorización:</b>&nbsp;&nbsp;".mdate('%d-%m-%Y')."<br /><br />";
											   	       										   	       													
								$mensaje.=   	      "</td>
											   	       <td valign='top' style='width: 300px;'>
											   	           <b>Requiere factura:</b>&nbsp;".$this->session->userdata('requiere_factura')."<br /><br />";
											   	       
											   	       	   if ($this->session->userdata('requiere_factura')=='si') {
											   	       	       $mensaje.="<b>Razón social:</b><br />";	
											   	       	       $rs = $this->direccion_facturacion_model->obtener_rs($this->session->userdata('razon_social'));
											   	       	       $mensaje.=$rs->company."<br />";	
											   	       	       $mensaje.=$rs->tax_id_number."<br /><br />";
											   	       	       										   	       	       
											   	       	       $mensaje.="<b>Dirección de facturación:</b><br />";
											   	       	       $df = $this->direccion_facturacion_model->obtener_direccion($id_cliente, $this->session->userdata('direccion_f'));	  										   	       	       
											   	       	       $mensaje.= $df->calle."&nbsp;".$df->num_ext."&nbsp;".(!empty($df->num_int) ? ", Int. ".$df->num_int : "") . "<br/>";
															   $mensaje.= $df->cp."<br/>".
																 		  $df->ciudad."<br/>".
														 		          $df->estado."<br/>".
															 	 		  $df->pais."&nbsp;";
											   	       	   }
											   	       	   
								$mensaje.=			  "</td>
											   	   </tr>
											   </tbody>	   
										   </table>
										   <table cellspacing='0' style=' border: solid; border-width: 1px; border-color: #E70030; width: 600px'>
										   	   <thead style='background-color: #E70030; color: #FFF'>
										   	       <tr>
										   	           <th colspan='4' align='left'>
										   	               <b>Resumen de orden:</b>
										   	           </th>
										   	       </tr>
										   	   </thead>
										   	   <tbody>
										   	       <tr>
										   	           <td colspan='4' align='left'><b>Número de orden:</b>&nbsp;&nbsp;".$id_compra."
										   	           </td>										   	           										   	          
										   	       </tr>
										   	       <tr>
										   	       	   <td colspan='4'>
										   	       	       <b>Productos en la orden:</b>	   
										   	       	   </td>
										   	       </tr>";
							### se usará "$detalle_promociones", para mostrar la información de los artículos de la orden
							//IVA inicial de la compra
							$iva_compra = 0.0;
							$iva_message = "";
							$subtotal = 0;
							foreach ($detalle_promociones['descripciones_promocion'] as $promociones) {
						
								//para los artículos de las promos que lleven IVA
								$iva_message  = "";		//en principio no lleva para la promocion
								
								//revisar si se cobra IVA
								if ($promociones['promocion']->iva_promocion > 0) { //($articulo['taxableBi']) {
									$iva_compra += $promociones['promocion']->iva_promocion;	//ya se calcula desde el API para el la promoción
									$iva_message  = "<b>costo m&aacute;s IVA</b>";	//en principio no lleva para la promocion
								}
								
								if (strstr($promociones['promocion']->descripcionVc, '|' )) {
									$mp = explode('|', $promociones['promocion']->descripcionVc);
									$nmp = count($mp);
									if ($nmp == 2) {
										$desc_promo = $mp[0];
									} else if ($nmp == 3) {
										$desc_promo = $mp[1];
									}
								} else {
									$desc_promo = $promociones['promocion']->descripcionVc;
								}
								
								//indicador de que requiere envío
								$promo_requiere_envio = $promociones['promocion']->requiere_envio;
								
								$mensaje.= "<tr><td colspan='4'>".$desc_promo."</td></tr>";
								//sacar la descripción que se mostrará de la promoción
								foreach ($promociones['articulos'] as $articulo) {
									$mensaje .= 
									"<tr>
										<td colspan='2' class='instrucciones'>";
									if ($articulo['issue_id']) {
										foreach ($detalle_promociones['tipo_productoVc'] as $k => $v) {
											if ($k == $articulo['issue_id']) {
												if (strstr($v, '|' )) {
													$mp = explode('|',$v);
													$nmp = count($mp);
													if ($nmp == 2) {
														$desc_art = $mp[0];
													} else if ($nmp == 3) {
														$desc_art = $mp[1];
													}
												} else {
													$desc_art = $v;
												}
											}
										}
									} else {										
										$desc_art=$articulo['tipo_productoVc']."&nbsp;";								
										foreach($detalle_promociones['articulo_oc'] as $i => $oc){
											if($i == $articulo['oc_id'] ){
												$desc_art.= $oc;	
											}																	
										}
									}
									//medio de entrega del artículo
									$medio_entrega = empty($articulo['medio_entregaVc']) ? "" : $articulo['medio_entregaVc']; 
																	
									$mensaje .= "<div>".$desc_art. "&nbsp;<div class='label-promo-rojo'>$iva_message</div></div><br/>";
									
									//direcciones de envío asociadas
									if ($promo_requiere_envio) {
										//id de la promoción que se quiere asociar con otra dirección
										$id_promo = $promociones['promocion']->id_promocionIn;
										
										//detalles de las direcciones
										if (!empty($detalles_direcciones) && array_key_exists($id_promo, $detalles_direcciones)) {
										 	$d = $detalles_direcciones[$id_promo];
										 	
										 	$mensaje .= "Calle " . $d->address1 . ", Número " .$d->address2. " " . (isset($d->address4) ? ", Interior ".$d->address4 : "") . "<br/>";
											$mensaje .= "C.P. " . $d->zip . " " . $d->city . ", ". $d->state . " ". $d->codigo_paisVc . ", Tel. " . $d->phone . "&nbsp;";
										 }
									}
									//sumar al subtotal de la compra
									$subtotal += $promociones['promocion']->subtotal_promocion;
									//precio de la promoción
									$mensaje .=
										"</td>
										<td>&nbsp;</td>
										<td class='instrucciones' align='right'>$" . number_format($articulo['tarifaDc'], 2, '.', ',') . "&nbsp;" . $moneda . "</td>" .
									"</tr>";
								}
							}
														
		   	       			$mensaje.= "<tr>
							   	           <td colspan='2'>&nbsp;
							   	           </td>
							   	           <td align='right'>Sub-total:
							   	           </td>
							   	           <td align='right'>$".number_format($detalle_promociones['total_pagar'], 2, '.', ',')."&nbsp;".$moneda."
							   	           </td>
							   	       </tr>
							   	       <tr>
							   	           <td colspan='2'>&nbsp;
							   	           </td>
							   	           <td align='right'>I.V.A
							   	           </td>
							   	           <td align='right'>$".number_format($detalle_promociones['total_iva'], 2, '.', ',')."&nbsp;".$moneda."
							   	           </td>
							   	       </tr>
							   	       <tr>
							   	           <td colspan='2' width='325px'>&nbsp;
							   	           </td>
							   	           <td align='right' width='180px'><b>Total de la orden</b>
							   	           </td>
							   	           <td align='right' width='95px'><b>$".number_format($detalle_promociones['total_pagar'] + $detalle_promociones['total_iva'], 2, '.', ',')."&nbsp;" . $moneda ."</b>
							   	           </td>
							   	       </tr>										   	       												   
							   	   </tbody>
							   </table>
							   <br />
							   <br />
							   <b>¿Solicitaste factura?</b>
							   <br />
							   <br />
							   Si solicitaste factura, recibirás un correo con la liga para descargarla en un periodo máximo de 24 horas.
							   <br />
							   <br />
							   Gracias por comprar en Grupo Expansión.									  	   																																																										  	
					  	  	</div>
						</body>
						</html>";
						
						
						
						##echo $mensaje;
						##exit;
						
						$envio_correo = FALSE;
						$estatus_pago = 0;
						
						///Envío de correo sólo en caso de que el cobro haya sido exitoso
						if (strtolower($simple_result->respuesta_banco) == "approved") {
							$estatus_pago = 1;
							$envio_correo = $this->enviar_correo("Confirmación de compra", $mensaje);
							$estatus_correo = $this->registrar_estatus_compra($id_compra, (int)$id_cliente, self::$ESTATUS_COMPRA['ENVIO_CORREO']);
							
							//manejo envío correo
							if (!($envio_correo && $estatus_correo)) {	//Error
								redirect('mensaje/'.md5(4), 'refresh');
							}
						}
						
						//Para lo que se devolverá a Teo
						###De momento se queda deshabilitado ya que solo se contempla el caso para suscripciones							
						//$data['url_back'] = $this->datos_urlback($simple_result->respuesta_banco, $id_compra);
						
						$data['resultado'] = $simple_result;
						$data['moneda'] = $moneda;				//para desplegar la respuesta de cobro
						$data['estatus_pago']=$estatus_pago	;	
						$data['id_compra']=$id_compra;		
						$this->cargar_vista('', 'suscripcion_express/orden_compra', $data);
						
						if ($estatus_pago==1) {
							$this->session->sess_destroy();	
						}
						
					} catch (Exception $exception) {	//antes SoapFault
						//echo $exception;
						//echo '<br/>error: <br/>'.$exception->getMessage();
						return NULL;
					}
					
				} else {
					//////// la informacion de la TC está guardada en la BD
					//recupera la información de la TC
					/*echo "TARJETA SI GUARDADA";
					exit();	*/
					$detalle_tarjeta = $this->forma_pago_model->detalle_tarjeta($consecutivo, $id_cliente);										
					$tc = $detalle_tarjeta;
					$data['tc'] = $detalle_tarjeta;
					
					$tipo_pago = ($tc->id_tipo_tarjetaSi == self::Tipo_AMEX) ? self::$TIPO_PAGO['American_Express'] : self::$TIPO_PAGO['Prosa'];
					
					
					$primer_digito = $this->obtener_primer_digito_tc($id_cliente, $consecutivo);
					/*
					echo " tipo pago: " . $tipo_pago;
					echo " tipo pago de la DB: " . $tipo_pago;
					echo "<pre>";
					print_r($informacion_orden);
					echo "</pre>";
					exit;
					*/
					
					// Intentamos el Pago con los Id's en  CCTC //
					try {
						
						//si la compra no se ha generado y guardado en sesión en algún intento previo de pago
						if ($this->session->userdata('id_compra')) {
							
							$id_compra = $this->session->userdata('id_compra');
							/*echo "IF SESSION USER DATA ID_COMPRA: ".$id_compra;
							exit();*/
						} else {
							/*echo "ELSE REGISTRAR ORDEN COMPRA_COMPRA: ".$ids_promociones.$ids_direcciones_envio."ACABA DE IMPRIMIR";
							echo "<pre>";
							print_r($ids_promociones, $ids_direcciones_envio);
							echo "</pre>";
							exit();	
							echo '<pre> ELSE REGISTRAR ORDEN COMPRA_COMPRA: '; print_r($id_cliente); echo "</pre>";
							echo '<pre> ELSE REGISTRAR ORDEN COMPRA_COMPRA: '; print_r($ids_promociones); echo "</pre>";
							echo '<pre> ELSE REGISTRAR ORDEN COMPRA_COMPRA: '; print_r($ids_direcciones_envio); echo "</pre>";
							echo '<pre> ELSE REGISTRAR ORDEN COMPRA_COMPRA: '; print_r($tipo_pago); echo "</pre>";
							echo '<pre> ELSE REGISTRAR ORDEN COMPRA_COMPRA: '; print_r($primer_digito); echo "</pre>";
							exit();*/
							$id_compra = $this->registrar_orden_compra($id_cliente, $ids_promociones, $ids_direcciones_envio, $tipo_pago, $primer_digito);
							
						}
						
						if (!$id_compra) {	//Si falla el registro inicial de la compra en CCTC
							echo "compra registrada!!!!";exit;
							redirect('mensaje/'.md5(3), 'refresh');
						}
						
						//pasar el id de la compra para el pago
						$informacion_orden->id_compraIn = $id_compra;
						
						//echo "info orden<pre>";
						//print_r($informacion_orden);
						//echo "</pre><br/>";
						//exit;
						
						//petición de pago a través de la interfase, el resultado ya es un objeto
						$simple_result = $this->solicitar_pago_CCTC_ids($informacion_orden);

						//Registro del estatus de la respuesta de CCTC
						$this->registrar_estatus_compra($id_compra, (int)$id_cliente, self::$ESTATUS_COMPRA['RESPUESTA_CCTC']);
						
						/*
						echo "simple_result<pre> gettype->";//.gettype($simple_result);
						print_r($simple_result);
						echo "</pre>";
						exit;
						*/
						//Registro de la respuesta de CCTC de la compra en ecommerce
						$info_detalle_pago_tc = array('id_compraIn'=> $id_compra, 'id_clienteIn' => $id_cliente, 'id_TCSi' => $consecutivo, 
														'id_transaccionBi' => $simple_result->id_transaccionNu, 'respuesta_bancoVc' => $simple_result->respuesta_banco,
														'codigo_autorizacionVc' => $simple_result->codigo_autorizacion, 'mensaje' => $simple_result->mensaje);

						//Registro de la respuesta del pago en ecommerce
						$this->registrar_detalle_pago_tc($info_detalle_pago_tc);
						$this->registrar_estatus_compra($id_compra, (int)$id_cliente, self::$ESTATUS_COMPRA['REGISTRO_PAGO_ECOMMERCE']);
						#echo "compra registrada final";exit;
						## para la prueba del correo
						/*$id_compra = 4;	//para el test
						$simple_result = NULL;
						$simple_result->codigo_autorizacion = 12321;
						## end pruebas*/
						
						##direcciones de envío
					   	//direcciones de envío ya se tienen recuperadas al principio del proceso de cobro
			   	       	//$ids_direcciones_envio;
			   	       	//$detalles_direcciones	//los detalles de las anteriores direcciones
						
						//Envío del correo
						$mensaje = "<html>
									  <body>
									  	   <div>
									  	   Hola ".$this->session->userdata('username').",<br />
										   Gracias por tu orden en pagos.grupoexpansion.mx
										   <br />
										   <br />
										   <table cellspacing='0' style=' border: solid; border-width: 1px; border-color: #E70030; width: 600px'>
										       <thead style='background-color: #E70030; color: #FFF'>
										           <tr>
										               <th colspan='2' align='left'>Pago, envío y facturación</th>
										           </tr>
										       </thead>
										       <tbody>
											   	   <tr>
											   	       <td valign='top' style='width: 300px;'>
											   	           <b>Método de pago:</b><br />".$detalle_tarjeta->descripcionVc." con terminación ".$detalle_tarjeta->terminacion_tarjetaVc."<br />
											   	           <b>Código de autorización:</b>&nbsp;&nbsp;".$simple_result->codigo_autorizacion."<br />
											   	           <b>Fecha de autorización:</b>&nbsp;&nbsp;".mdate('%d-%m-%Y')."<br /><br />";
											   	       
								$mensaje.=   	      "</td>
											   	       <td valign='top' style='width: 300px;'></td>
											   	   </tr>
											   </tbody>	   
										   </table>
										   <table cellspacing='0' style=' border: solid; border-width: 1px; border-color: #E70030; width: 600px'>
										   	   <thead style='background-color: #E70030; color: #FFF'>
										   	       <tr>
										   	           <th colspan='4' align='left'>
										   	               <b>Resumen de orden:</b>
										   	           </th>
										   	       </tr>
										   	   </thead>
										   	   <tbody>
										   	       <tr>
										   	           <td colspan='4' align='left'><b>Número de orden:</b>&nbsp;&nbsp;".$id_compra."
										   	           </td>										   	           										   	          
										   	       </tr>
										   	       <tr>
										   	       	   <td colspan='4'>
										   	       	       <b>Productos en la orden:</b>	   
										   	       	   </td>
										   	       </tr>";
						
							### se usará "$detalle_promociones", para mostrar la información de los artículos de la orden
							//IVA inicial de la compra
							$iva_compra = 0.0;
							$iva_message = "";
							$subtotal = 0;
							foreach ($detalle_promociones['descripciones_promocion'] as $promociones) {
						
								//para los artículos de las promos que lleven IVA
								$iva_message  = "";		//en principio no lleva para la promocion
								
								//revisar si se cobra IVA
								if ($promociones['promocion']->iva_promocion > 0) { //($articulo['taxableBi']) {
									$iva_compra += $promociones['promocion']->iva_promocion;	//ya se calcula desde el API para el la promoción
									$iva_message  = "<b>costo m&aacute;s IVA</b>";	//en principio no lleva para la promocion
								}
								
								if (strstr($promociones['promocion']->descripcionVc, '|' )) {
									$mp = explode('|', $promociones['promocion']->descripcionVc);
									$nmp = count($mp);
									if ($nmp == 2) {
										$desc_promo = $mp[0];
									} else if ($nmp == 3) {
										$desc_promo = $mp[1];
									}
								} else {
									$desc_promo = $promociones['promocion']->descripcionVc;
								}
								
								//indicador de que requiere envío
								$promo_requiere_envio = $promociones['promocion']->requiere_envio;
								
								$mensaje.= "<tr><td colspan='4'>".$desc_promo."</td></tr>";
								
								//sacar la descripción que se mostrará de la promoción
								foreach ($promociones['articulos'] as $articulo) {
									$mensaje .= 
									"<tr>
										<td colspan='2' class='instrucciones'>";
									if ($articulo['issue_id']) {
										foreach ($detalle_promociones['tipo_productoVc'] as $k => $v) {
											if ($k == $articulo['issue_id']) {
												if (strstr($v, '|' )) {
													$mp = explode('|',$v);
													$nmp = count($mp);
													if ($nmp == 2) {
														$desc_art = $mp[0];
													} else if ($nmp == 3) {
														$desc_art = $mp[1];
													}
												} else {
													$desc_art = $v;
												}
											}
										}
									} else {										
										$desc_art=$articulo['tipo_productoVc']."&nbsp;";								
										foreach($detalle_promociones['articulo_oc'] as $i => $oc){
											if($i == $articulo['oc_id'] ){
												$desc_art.= $oc;	
											}																	
										}
									}
									//medio de entrega del artículo
									$medio_entrega = empty($articulo['medio_entregaVc']) ? "" : $articulo['medio_entregaVc']; 
																	
									$mensaje .= "<div>".$desc_art. "&nbsp;<div class='label-promo-rojo'>$iva_message</div></div><br/>";
									
									//direcciones de envío asociadas
									if ($promo_requiere_envio) {
										//id de la promoción que se quiere asociar con otra dirección
										$id_promo = $promociones['promocion']->id_promocionIn;
										
										//detalles de las direcciones
										if (!empty($detalles_direcciones) && array_key_exists($id_promo, $detalles_direcciones)) {
										 	$d = $detalles_direcciones[$id_promo];
										 	
										 	$mensaje .= "Calle " . $d->address1 . ", Número " .$d->address2. " " . (isset($d->address4) ? ", Interior ".$d->address4 : "") . "<br/>";
											$mensaje .= "C.P. " . $d->zip . " " . $d->city . ", ". $d->state . " ". $d->codigo_paisVc . ", Tel. " . $d->phone . "&nbsp;";
										 }
									}
									//sumar al subtotal de la compra
									$subtotal += $promociones['promocion']->subtotal_promocion;
									//precio de la promoción
									$mensaje .=
										"</td>
										<td>&nbsp;</td>
										<td class='instrucciones' align='right'>$" . number_format($articulo['tarifaDc'], 2, '.', ',') . "&nbsp;" . $moneda . "</td>" .
									"</tr>";
								}
							}
														
		   	       			$mensaje.= "<tr>
							   	           <td colspan='2'>&nbsp;
							   	           </td>
							   	           <td align='right'>Sub-total:
							   	           </td>
							   	           <td align='right'>$".number_format($detalle_promociones['total_pagar'], 2, '.', ',')."&nbsp;".$moneda."
							   	           </td>
							   	       </tr>
							   	       <tr>
							   	           <td colspan='2'>&nbsp;
							   	           </td>
							   	           <td align='right'>I.V.A
							   	           </td>
							   	           <td align='right'>$".number_format($detalle_promociones['total_iva'], 2, '.', ',')."&nbsp;".$moneda."
							   	           </td>
							   	       </tr>
							   	       <tr>
							   	           <td colspan='2' width='325px'>&nbsp;
							   	           </td>
							   	           <td align='right' width='180px'><b>Total de la orden</b>
							   	           </td>
							   	           <td align='right' width='95px'><b>$".number_format($detalle_promociones['total_pagar'] + $detalle_promociones['total_iva'], 2, '.', ',')."&nbsp;" . $moneda ."</b>
							   	           </td>
							   	       </tr>										   	       												   
							   	   </tbody>
							   </table>
							   <br />
							   <br />
							   <b>¿Solicitaste factura?</b>
							   <br />
							   <br />
							   Si solicitaste factura, recibirás un correo con la liga para descargarla en un periodo máximo de 24 horas.
							   <br />
							   <br />
							   Gracias por comprar en Grupo Expansión.									  	   																																																										  	
					  	  	</div>
						</body>
						</html>";
						#echo $mensaje;
						#exit;
						$estatus_pago = 0;
						/*$simple_result->respuesta_banco= "approved";*/
						/*echo "simple_result<pre> gettype->";//.gettype($simple_result);
						print_r($simple_result);
						echo "</pre>";
						exit();*/
						
						if (strtolower($simple_result->respuesta_banco) == "approved") {
							$estatus_pago = 1;
							$envio_correo = $this->enviar_correo("Confirmación de compra", $mensaje);
							$estatus_correo = $this->registrar_estatus_compra($id_compra, (int)$id_cliente, self::$ESTATUS_COMPRA['ENVIO_CORREO']);
							
							//manejo envío correo
							if (!($envio_correo && $estatus_correo)) {	//Error
								redirect('mensaje/'.md5(4), 'refresh');
							}
						}
						
						//Para lo que se devolverá a Teo
						###De momento se queda deshabilitado ya que solo se contempla el caso para suscripciones							
						//$data['url_back'] = $this->datos_urlback($simple_result->respuesta_banco, $id_compra);
						
						$data['resultado'] = $simple_result;
						$data['moneda'] = $moneda;				//para desplegar la respuesta de cobro
						$data['estatus_pago']=$estatus_pago	;	
						$data['id_compra']=$id_compra;		
						$this->cargar_vista('', 'suscripcion_express/orden_compra', $data);
						
						if ($estatus_pago==1) {
							$this->session->sess_destroy();	
						}
						
					} catch (Exception $exception) {	//antes SoapFault
						//errores en desarrollo
						//echo $exception;
						//echo '<br/>error: <br/>'.$exception->getMessage();
						return NULL;
					}
				}
			} else {	//If Errores													

				$data['reg_errores'] = $this->registro_errores;
				$this->cargar_vista('', 'suscripcion_express/orden_compra' , $data);
				
			}
		} 
		else { //si llega sin una petición
			/*
			$data['mensaje']="Información insuficiente para completar la orden";
			$this->load->view('templates/header', $data);
			$this->load->view('mensaje', $data);
			 */
			$this->cargar_vista('', 'suscripcion_express/orden_compra' , $data);  
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

	private function get_datos_tarjeta(){
		$datos = array();
		$tipo = '';
		//echo "tipo : ". $tipo;
		//no se usa la funcion de escape '$this->db->escape()', por que en la inserción ya se incluye 
		
			if (array_key_exists('sel_tipo_tarjeta', $_POST)) {
				$datos['tc']['id_tipo_tarjetaSi'] = $_POST['sel_tipo_tarjeta'];
				$tipo = $_POST['sel_tipo_tarjeta'];
			}
			/*
			echo "<pre>";
				print_r($_POST);
			echo "</pre>";
			echo $_POST['sel_tipo_tarjeta'];
			exit;
			*/
			
			if (array_key_exists('txt_numeroTarjeta', $_POST)) {
				if ($this->validar_tarjeta($datos['tc']['id_tipo_tarjetaSi'], trim($_POST['txt_numeroTarjeta']))) {					 
					$datos['tc']['terminacion_tarjetaVc'] = trim($_POST['txt_numeroTarjeta']);	//substr($_POST['txt_numeroTarjeta'], strlen($_POST['txt_numeroTarjeta']) - 4);
				} else {
					$this->reg_errores['txt_numeroTarjeta'] = 'Por favor ingrese un numero de tarjeta v&aacute;lido';
				}
			}
			
			if (array_key_exists('txt_nombre', $_POST)) {
				if(preg_match('/^[A-ZáéíóúÁÉÍÓÚÑñ \'.-]{1,30}$/i', $_POST['txt_nombre'])) { 
					$datos['tc']['nombre_titularVc'] = $_POST['txt_nombre'];
					if ($tipo == 1) {
						$datos['amex']['nombre_titularVc'] = $_POST['txt_nombre'];
					}
				} else {
					$this->reg_errores['txt_nombre'] = 'Ingresa tu nombre correctamente';
				}
			}
			if (array_key_exists('txt_apellidoPaterno', $_POST)) {
				if(preg_match('/^[A-ZáéíóúÁÉÍÓÚÑñ \'.-]{1,30}$/i', $_POST['txt_apellidoPaterno'])) { 
					$datos['tc']['apellidoP_titularVc'] = $_POST['txt_apellidoPaterno'];
					if ($tipo == 1) {
						$datos['amex']['apellidoP_titularVc'] = $_POST['txt_apellidoPaterno'];
					}
				} else {
					$this->reg_errores['txt_apellidoPaterno'] = 'Ingresa tu apellido correctamente';
				}
			}
			if (array_key_exists('txt_apellidoMaterno', $_POST) && !empty($_POST['txt_apellidoMaterno'])) {
				if(preg_match('/^[A-ZáéíóúÁÉÍÓÚÑñ \'.-]{1,30}$/i', $_POST['txt_apellidoMaterno'])) {
					$datos['tc']['apellidoM_titularVc'] = $_POST['txt_apellidoMaterno'];
					if ($tipo == 1) {	//Amex
						$datos['amex']['apellidoM_titularVc'] = $_POST['txt_apellidoMaterno'];
					}
				} else {
					$this->reg_errores['txt_apellidoMaterno'] = 'Ingresa tu apellido correctamente';
				}
			} else {
				$datos['tc']['apellidoM_titularVc'] = "";
					if ($tipo == 1) {
						$datos['amex']['apellidoM_titularVc'] = "";
					}
			}
			
			if (array_key_exists('sel_mes_expira', $_POST)) {
				$datos['tc']['mes_expiracionVc'] = $_POST['sel_mes_expira']; 
			}
			if (array_key_exists('sel_anio_expira', $_POST)) { 
				$datos['tc']['anio_expiracionVc'] = $_POST['sel_anio_expira'];  
			}
			
			
			if (array_key_exists("txt_codigo", $_POST)) {
				if (preg_match('/^[0-9]{3,4}$/', $_POST['txt_codigo'])) { 
					$datos['cvv'] = $_POST['txt_codigo'];
				} else {
					$this->reg_errores['txt_codigo'] = 'Ingresa un código de seguridad válido';
				}
			}
			
			//AMEX
		if($tipo ==1){
			
			
				
			if (array_key_exists('txt_calle', $_POST)) {
				if(preg_match('/^[A-Z0-9 \'.-áéíóúÁÉÍÓÚÑñ]{2,40}$/i', $_POST['txt_calle'])) {
					$datos['amex']['calle'] = $_POST['txt_calle'];
				} else {
					$this->reg_errores['txt_calle'] = 'Ingresa tu calle y n&uacute;mero correctamente';
				}
			} 
			
			if (array_key_exists('txt_cp', $_POST)) {
				//regex usada en js
				if(preg_match('/^([1-9]{2}|[0-9][1-9]|[1-9][0-9])[0-9]{3}$/', $_POST['txt_cp'])) {
					$datos['amex']['codigo_postal'] = $_POST['txt_cp'];
				} else {
					$this->reg_errores['txt_cp'] = 'Ingresa tu c&oacute;digo postal correctamente';
				}
			} 
			
			if (array_key_exists('txt_ciudad', $_POST)) {
				if(preg_match('/^[A-Z0-9 \'.,-áéíóúÁÉÍÓÚÑñ]{2,40}$/i', $_POST['txt_ciudad'])) {
					$datos['amex']['ciudad'] = $_POST['txt_ciudad'];
				} else {
					$this->reg_errores['txt_ciudad'] = 'Ingresa tu ciudad correctamente';
				}
			} 
			
			if (array_key_exists('txt_estado', $_POST)) {
				if(preg_match('/^[A-Z \'.-áéíóúÁÉÍÓÚÑñ]{2,40}$/i', $_POST['txt_estado'])) {
					$datos['amex']['estado'] = $_POST['txt_estado'];
				} else {
					$this->reg_errores['txt_estado'] = 'Ingresa tu estado correctamente';
				}
			}
			if (array_key_exists('sel_pais', $_POST)) {
				if ($_POST['sel_pais'] != "") {
					$datos['amex']['pais'] = $_POST['sel_pais'];
				} else {
					$this->reg_errores['sel_pais'] = 'Selecciona tu pa&iacute;s';
				}
			}
			else {
				$datos['amex']['pais'] = '';
			}
			
			if (array_key_exists('txt_email', $_POST) && trim($_POST['txt_email']) != "") {
				if(filter_var($_POST['txt_email'], FILTER_VALIDATE_EMAIL)) {
					$datos['amex']['mail'] = $_POST['txt_email'];
				} else {
					$this->reg_errores['txt_email'] = 'Ingresa tu email correctamente (opcional)';
				}
			} else {
				$datos['amex']['mail'] = '';
			}
			
			if (array_key_exists('txt_telefono', $_POST)) {
				if(preg_match('/^[0-9 -]{8,20}$/i', $_POST['txt_telefono'])) {
					$datos['amex']['telefono'] = $_POST['txt_telefono'];
				} else {
					$this->reg_errores['txt_telefono'] = 'Ingresa tu tel&eacute;fono correctamente';
				}
			} /*else {
				$datos['amex']['telefono'] = '';
			}*/
		}	
		
		
		//echo 'si no hay errores, $reg_errores esta vacio? '.empty($this->reg_errores).'<br/>';
		return $datos;
	}

	private function validar_tarjeta($tipo_tarjeta, $num_tarjeta) {
		$reg_visa 			= '/^4[0-9]{12}(?:[0-9]{3})?$/';
		$reg_master_card 	= '/^5[1-5][0-9]{14}$/';
		$reg_amex			= '/^3[47][0-9]{13}$/';
		//1 => Aamex, >1 => PROSA
		
		if ($tipo_tarjeta > 1 && !preg_match($reg_visa, $num_tarjeta) && !preg_match($reg_master_card, $num_tarjeta)) {
			return false;
		} else if ($tipo_tarjeta == 0 && !preg_match($reg_amex, $num_tarjeta)) {
			return false;
		} else if (!$this->validarLuhn($num_tarjeta)) {
			return false;
		} 
		
		return true;		//tarjeta válida
	}
	
	private function validarLuhn($num_tarjeta) {
		$num_card = array(16);
		$len = 0;
		$tarjeta_valida = false;
	
		//Obtener los dígitos de la tarjeta
		for ($i = 0; $i < strlen($num_tarjeta); $i++) {
			$num_card[$len++] = (int)($num_tarjeta[$i]);
		}
			
		//algoritmo Luhn
		$checksum = 0;
		for ($i = $len - 1; $i >= 0; $i--) {
			if ($i % 2 == $len % 2) {
				$n = $num_card[$i] * 2;
				$checksum += (int)($n / 10) + ($n % 10);
			} else {
				$checksum += $num_card[$i];
			}
		}
		
		$tarjeta_valida = ($checksum % 10 == 0);
		
		return $tarjeta_valida;
	}
	
	private function cargar_vista($folder, $page, $data){	
		//Para automatizar un poco el desplieguee
		$this->load->view('suscripcion_express/header', $data);
		if(!isset($data['no_mostrar_promo'])){							
			$this->load->view('suscripcion_express/promocion.html', $data);
		}																						
		$this->load->view($folder.'/'.$page, $data);
		$this->load->view('suscripcion_express/footer', $data);
	}
	
	private function genera_pass(){
		$str = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890";
        $contra = "";
        for ($i=1; $i <= 8; $i++) {
        	$contra .= substr($str,rand(0,62),1);
        }
		
		return $contra;
	}
	
	/*
	 * Registro de la información de la tarjeta
	 */
	private function registrar_tc($id_cliente) {
		
		$consecutivo = $this->forma_pago_model->get_consecutivo($id_cliente);
		$lista_tipo_tarjeta = $this->forma_pago_model->listar_tipos_tarjeta();	//$this->listar_tipos_tarjeta_WS();
		
		$form_values = array();
		$form_values = $this->get_datos_tarjeta();
		
		$form_values['tc']['id_clienteIn'] = $id_cliente;
		$form_values['tc']['id_TCSi'] = $consecutivo + 1;
												
		if($form_values['tc']['id_tipo_tarjetaSi']==1){					
			$form_values['amex']['id_clienteIn'] = $id_cliente;
			$form_values['amex']['id_TCSi'] = $consecutivo + 1;
		}		
		else{
			$form_values['amex']=null;
		} 
		
		
		
		
		//si no hay errores configurar la información de la tarjeta
		$form_values['tc']['descripcionVc'] = $this->get_descripcion_tarjeta($form_values['tc']['id_tipo_tarjetaSi'], $lista_tipo_tarjeta);
		//$form_values['amex'] = null;	//para que no se tome encuenta por el momento
		
		$tipo = $form_values['tc']['id_tipo_tarjetaSi'];	//1 es AMEX
		/*
		echo "<pre>";
		print_r($form_values);
		echo "<pre>";
		exit;
		 */ 
		
		//si no hay errores y se solicita registrar la tarjeta en BD
		if ($this->session->userdata('gt') == 1) {
			//verificar que no exista la tarjeta activa en la BD
			$num_completo = $form_values['tc']['terminacion_tarjetaVc'];
			$num_temp = substr($num_completo, strlen($num_completo) - 4);
			$primer_digito = substr($num_completo, 0, 1);	// primer dígito de la tarjeta
			$form_values['tc']['terminacion_tarjetaVc'] = $num_temp;
			$existTc = $this->forma_pago_model->existe_tc_express($form_values['tc']); 
			if ($existTc) {
				///TO DO si la tarjeta ya esta registrada seleccionar los datos correspondientes
				$form_values['tc']['terminacion_tarjetaVc'] = $num_completo;
				$form_values['tc']['terminacion_tarjetaVc'] = $num_temp;
				$form_values['tc']['primer_digitoTi'] = $primer_digito;
				$form_values['tc']['id_TCSi'] = $existTc->row()->id_TCSi;
				
				if ($form_values['tc']['id_tipo_tarjetaSi'] == 1) {
					$form_values['amex']['id_TCSi'] = $existTc->row()->id_TCSi;
				}
				//$this->consecutivo_tc = $existTc->row()->id_TCSi;	
				//cargar en session y redireccionar
				$this->cargar_en_session($form_values['tc']['id_TCSi']);
				
			} else {
				//verifica si hay o no dirección activa predeterminada
				$existe_predeterminada = $this->existe_predetereminada($id_cliente);
					
				//sólo la primera que se registra se predetermina
				if (isset($form_values['predeterminar']) || $consecutivo ==  0 || !$existe_predeterminada) {
					$this->forma_pago_model->quitar_predeterminado($id_cliente);
					$form_values['tc']['id_estatusSi'] = 3;
				}
				//para registrar en CCTC
				$form_values['tc']['terminacion_tarjetaVc'] = $num_completo;
				
				//se manda insertar en CCTC
				//if ($this->registrar_tarjeta_CCTC($form_values['tc'], $form_values['amex'])) {	//Se registró exitosamente! en CCTC";
				if ($this->registrar_tarjeta_interfase_CCTC($form_values['tc'], $form_values['amex'])) {	//Se registró exitosamente! en CCTC";
					
					//sólo registrar los últimos 4 dígitos de la TC, pra el registro en la bd de ecommerce
					$form_values['tc']['terminacion_tarjetaVc'] = $num_temp;
					$form_values['tc']['primer_digitoTi'] = $primer_digito;
					//registrar localmente
					if ($this->forma_pago_model->insertar_tc($form_values['tc'])) {
						$this->consecutivo_tc = $form_values['tc']['id_TCSi'];	//cual  es el que se registra
						//cargar en session y redireccionar
						$this->cargar_en_session($form_values['tc']['id_TCSi']);
												
					} else {
						
						echo "<br/>Hubo un error en el registro en CMS";
					}
				} else {
					
					echo "Hubo un error en el registro en CCTC";
				}
			}	//ya registrada	
		} else {
			//no se quiere guardar en BD
			$form_values['tc']['id_TCSi'] = 0;		//consec. es cero para las ediciones posibles
			
			//para la session
			$tarjeta = array('tc' => $form_values['tc'], $form_values['amex']);				
			
			//Verificar el flujo() => cargar o no en session y redireccionar
			$this->cargar_en_session($tarjeta);
			//echo "no guardar";			
		}
		 
		
	}

	private function get_descripcion_tarjeta($id_tipo_tarjetaSi, $lista_tipo_tarjeta){
		$descripcion = "Tarjeta no registrada";
		foreach ($lista_tipo_tarjeta as $tipo_tarjeta) {
			if ($id_tipo_tarjetaSi == $tipo_tarjeta->id_tipo_tarjeta) {
				$descripcion = $tipo_tarjeta->descripcion;
				return $descripcion;
			}
		}
		return $descripcion;
	}
	
	private function cargar_en_session($tarjeta = null){
		//echo 'tarjeta: '.$tarjeta.'<br/> is int: ' . (int)($tarjeta) . '<br/>is array: ' . is_array($tarjeta) . '<br/>tipo: ' . gettype($tarjeta) . '<br/>';
		if (is_array($tarjeta)) { //si no se guarda en BD
			$this->session->set_userdata('tarjeta', $tarjeta);
		} else if ( ((int)$tarjeta) != 0 && is_int((int)$tarjeta)) {	//si ya está regiustrada la tarjeta en BD sólo sube el consecutivo
			$this->session->set_userdata('tarjeta', $tarjeta);
		} else {	//si no es ninguno de los dos, elimina el elemento de la sesión
			$this->session->unset_userdata('tarjeta');
		}
		//Por default se elimina el pago con depósito bancario
		$this->session->unset_userdata('deposito');
	}

	private function existe_predetereminada($id_cliente){
		return $this->forma_pago_model->existe_predetereminada($id_cliente);
	}
	
	private function registrar_tarjeta_interfase_CCTC($tc, $amex = null)
	{
		//mapeo de la tc
		$tc_soap = new stdClass;
		$tc_soap->id_clienteIn = $tc['id_clienteIn'];
		$tc_soap->consecutivo_cmsSi = $tc['id_TCSi'];
		$tc_soap->id_tipo_tarjeta = $tc['id_tipo_tarjetaSi'];
		$tc_soap->nombre_titular = $tc['nombre_titularVc'];
		$tc_soap->apellidoP_titular = $tc['apellidoP_titularVc'];
		$tc_soap->apellidoM_titular = $tc['apellidoM_titularVc'];
		$tc_soap->numero = $tc['terminacion_tarjetaVc'];
		$tc_soap->mes_expiracion = $tc['mes_expiracionVc'];
		$tc_soap->anio_expiracion = $tc['anio_expiracionVc'];
		$tc_soap->renovacion_automatica = 1;
		
		//mapeo Amex
		if (isset($amex)) {
			$amex_soap = new stdClass;
			$amex_soap->id_clienteIn = $amex['id_clienteIn'];
			$amex_soap->consecutivo_cmsSi = $amex['id_TCSi'];
			$amex_soap->nombre =$amex['nombre_titularVc'];
			$amex_soap->apellido_paterno = $amex['apellidoP_titularVc'];
			$amex_soap->apellido_materno = $amex['apellidoM_titularVc'];
			$amex_soap->pais = $amex['pais'];
			$amex_soap->codigo_postal = $amex['codigo_postal'];
			$amex_soap->calle = $amex['calle'];
			$amex_soap->ciudad = $amex['ciudad'];
			$amex_soap->estado = $amex['estado'];
			$amex_soap->mail = $amex['mail'];
			$amex_soap->telefono = $amex['telefono'];
			
		} else {
			$amex_soap = null;
		}
		
		########## petición a la Interfase
		// Metemos todos los parametros (Objetos) necesarios a una clase dinámica llamada paramátros //
		$parametros = new stdClass;
		$parametros->tc_soap = $tc_soap;
		$parametros->amex_soap = $amex_soap;
		
		// Hacemos un encode de los objetos para poderlos pasar por POST ...
		$param = json_encode($parametros);
		
		/*
		echo "<pre>";
		print_r($parametros);
		echo "</pre>". "ecoded:" ;
		echo $param."<br/>";
		//exit;
		
		$p = json_decode($param);
		$objetos = $this->ArrayToObject($p);
		echo "<pre>";
		print_r($objetos);
		echo "</pre>";
		exit;
		*/
		// Inicializamos el CURL / SI no funciona se puede habilitar en el php.ini //
		$c = curl_init();
		// CURL de la URL donde se haran las peticiones //
		//curl_setopt($c, CURLOPT_URL, 'http://dev.interfase.mx/interfase.php');
		curl_setopt($c, CURLOPT_URL, 'http://10.43.29.196/interfase_cctc/interfase.php');
		//curl_setopt($c, CURLOPT_URL, 'http://localhost/interfase_cctc/interfase.php');
		
		// Se enviaran los datos por POST //
		curl_setopt($c, CURLOPT_POST, true);
		// Que nos envie el resultado del JSON //
		curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
		// Enviamos los parametros POST //
		curl_setopt($c, CURLOPT_POSTFIELDS, 'accion=RegistrarTarjeta&token=123456&parametros='.$param);
		// Ejecutamos y recibimos el JSON //
		$resultado = curl_exec($c);
		// Cerramos el CURL //
		curl_close($c);
		/*
		echo "Resultado<pre>";
		print_r(json_decode($resultado));
		echo "</pre>";
		exit;
		*/
		return json_decode($resultado);
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
	
	private function encriptar_rsa_texto($texto = "") {
		include('rsa/Crypt/RSA.php');		//pára encriptar la clave de la tc
		
		$rsa = new Crypt_RSA();
		$ciphered_text = FALSE;
		//if (file_exists('application/controllers/rsa/public.pem')) {
		if (file_exists('application/controllers/rsa/public_cms.pem')) {
			
			//se carga la 'public key'
			//$rsa->loadKey(file_get_contents('application/controllers/rsa/public.pem')); 	// public key con password "3xp4n5i0n"
			$rsa->loadKey(file_get_contents('application/controllers/rsa/public_cms.pem')); 	// public key con password "3xp4n5i0n"
			
			//algoritmo de encriptación
			$rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);	//2
			
			//texto cifrado:
			$ciphertext = $rsa->encrypt($texto);
			
			$ciphered_text = base64_encode($ciphertext);
			//texto cifrado
			//echo "texto cifrado" . $ciphertext . "<br/>";
			
			//texto codificado
			//echo "base 64 encode: " . base64_encode($ciphertext) . "<br/>";
			
		}
		
		return $ciphered_text;
	}
	
	private function obtener_primer_digito_tc($id_cliente, $id_tc) {
		$res = $this->orden_compra_model->obtener_primer_digito_tc($id_cliente, $id_tc)->row()->primer_digitoTi;
		$digito =  ($res) ? $res : 0;
		//echo "digito: " . $digito ."-<br/>";
		//exit;
		
		return $res;
		
	}
	
	private function registrar_orden_compra($id_cliente, $ids_promociones, $ids_direcciones_envio, $tipo_pago, $primer_digito = NULL)
	{
		/*echo '<pre> ENTRO A LA FUNCION REGISTRAR_ORDEM_COMPRA '; print_r($id_cliente); echo "</pre>";
							echo '<pre> ELSE REGISTRAR ORDEN COMPRA_COMPRA: '; print_r($ids_promociones); echo "</pre>";
							echo '<pre> ELSE REGISTRAR ORDEN COMPRA_COMPRA: '; print_r($ids_direcciones_envio); echo "</pre>";
							echo '<pre> ELSE REGISTRAR ORDEN COMPRA_COMPRA: '; print_r($tipo_pago); echo "</pre>";
							echo '<pre> ELSE REGISTRAR ORDEN COMPRA_COMPRA: '; print_r($primer_digito); echo "</pre>";
							exit();*/
		//Registrar eb la tabla de órdenes
		$id_compra = 0;
		$id_compra = $this->registrar_compra($id_cliente);
		
		if ($id_compra) {
			$this->session->set_userdata('id_compra', $id_compra);
			
			//los artículos que se registrarán para la compra 
			$info_articulos = array();
			
			foreach ($ids_promociones as $key => $id_promo) {
				///artíclos de la promoción
				$articulos_compra = array();
				$articulos_compra = $this->orden_compra_model->obtener_articulos_promocion($id_promo);
				
				//////////artículos///////////
				foreach ($articulos_compra as $articulo) {
					 //preparar la información para insertar los artículos
					$info_articulos[] = array((int)$articulo->id_articulo, $id_compra, (int)$id_cliente, (int)$id_promo);
				}
			}
			
			/*echo "<pre>";
			print_r($info_articulos);
			echo "</pre>";
			exit();*/
			
			/////// forma pago ///////
			$id_TCSi = 0;
			$info_pago = array();
			//procesar el tipo de pago
			if ($tarjeta = $this->session->userdata('tarjeta')) {
				$id_TCSi = (is_array($tarjeta)) ? NULL: (int)$this->session->userdata('tarjeta');
				//$tipo_pago = 1;	//MASTERCARD / VISA/=1,  AMEX=2 
			} else if ($this->session->userdata('deposito')) {
				$id_TCSi = NULL;		//consecutivo usado para depósito y tarjetas no guardadas
				//$tipo_pago = 3;	//Depósito Bancario
			}
			
			$info_pago = array('id_compraIn' => $id_compra, 'id_clienteIn' => (int)$id_cliente, 'id_tipoPagoSi' => $tipo_pago, 'id_TCSi' => $id_TCSi, 'primer_digitoTi' => $primer_digito);
			
			
			#PRUEBAS DIRECCIONES ENVIO
			/*$REQUIERE_ENVIO_VAR = $this->session->userdata('requiere_envio');
			echo "<pre> INFO PAGO ANTES DEL IF DE DIRECCIONES DE ENVIO";
			print_r($REQUIERE_ENVIO_VAR);
			echo "</pre>"; 
			echo '<pre> ELSE REGISTRAR ORDEN COMPRA_COMPRA: '; print_r($ids_direcciones_envio); echo "</pre>";
			exit();*/
			#FIN PRUEBAS DIRECCIONES ENVIO
			
			/////// direccion(es) de envío///////
			$info_direcciones = array();
			//las direcciones de envío vienene como argumento en la llamada
			if ($this->session->userdata('requiere_envio') && !empty($ids_direcciones_envio)) {
				/*echo "Sí requiere_envio: Si<br/>";
				exit();*/
				### Ajustado para múltiples direcciones
				###if ($dir_envio = $this->session->userdata('dir_envio')) {
				foreach ($ids_direcciones_envio as $id_promo => $id_direccion) {
					
					//echo "direccion_envio: " . $dir_envio;
					
					$info_direcciones['envio'][] = 
						array('id_compraIn' => $id_compra, 'id_clienteIn' => (int)$id_cliente, 'id_promocionIn' => $id_promo, 'id_consecutivoSi' => (int)$id_direccion, 'address_type' => self::$TIPO_DIR['RESIDENCE']);
						//array('id_compraIn' => $id_compra, 'id_clienteIn' => (int)$id_cliente, 'id_consecutivoSi' => (int)$dir_envio, 'address_type' => self::$TIPO_DIR['RESIDENCE']);
				} 
				/*echo '<pre> LLENA ARREGLO DIRECCIONES DE ENVIO: '; print_r($info_direcciones); echo "</pre>";
				exit();*/
				
				
			} else if ($this->session->userdata('requiere_envio') && empty($ids_direcciones_envio)) {
					//No se efectúa la petición por que falta el dato de envío
					/*echo "ERROR requiere_envio: Si<br/>";
				exit();*/
					echo "Error: la compra requiere dirección de envío.";
					return FALSE;
			} else {
				//si no requiere se vacía el arreglo
				/*echo "NO requiere_envio: Si<br/>";
				exit();*/
				$info_direcciones['envio'] = array();
			}
			
			////////// dirección de facturación //////////
			
			//si no requiere se vacía
			$info_direcciones['facturacion'] = array();
			
			/*
			echo "<pre>";
			print_r($info_direcciones);
			echo "</pre>";
			exit;
			 */ 
			///////estatus de registro de la compra///////
			$estatus = ($tipo_pago == self::$TIPO_PAGO['Deposito_Bancario']) ? self::$ESTATUS_COMPRA['PAGO_DEPOSITO_BANCARIO'] : self::$ESTATUS_COMPRA['SOLICITUD_CCTC'];
			$info_estatus = array('id_compraIn' => $id_compra, 'id_clienteIn' => (int)$id_cliente, 'id_estatusCompraSi' => $estatus);
			
			/*echo "<pre>";
			print_r($info_direcciones);
			echo "</pre>";
			exit;*/			


			/*ECHO "YA VA A REGISTRAR COMPRA INICIAL";
			echo "<pre>";
			print_r($info_articulos);
			echo "</pre>";
			echo "<pre>";
			print_r($info_pago);
			echo "</pre>";
			echo "<pre>";
			print_r($info_direcciones);
			echo "</pre>";
			echo "<pre>";
			print_r($info_estatus);
			echo "</pre>";
			exit();*/
			
			///////////// registrar compra inicial en BD /////// 
			$registro_orden = $this->orden_compra_model->registrar_compra_inicial($info_articulos, $info_pago, $info_direcciones, $info_estatus);
			/*echo "compra: " . $id_compra;
			exit();*/
			return $id_compra;
		} else {
			//Error en el registro de la compra
			$this->session->unset_userdata('id_compra');	
			return FALSE;
		}
		
	}
	
	/**
	 * Redistro de la compra
	 */
	private function registrar_compra($id_cliente)
	{
		try {
			return $this->orden_compra_model->insertar_compra($id_cliente);
		} catch (Exception $ex ) {
			echo "Error en el registro del la compra: " .$ex->getMessage();
			return FALSE;
		}
	}
	
	private function solicitar_pago_CCTC_ids($informacion_orden) {
		if (isset($informacion_orden)) {
			// Metemos todos los parametros (Objetos) necesarios a una clase dinámica llamada paramátros //
			$parametros = new stdClass;
			$parametros->informacion_orden = $informacion_orden;
			
			// Hacemos un encode de los objetos para poderlos pasar por POST ...
			$param = json_encode($parametros);
			/*
			echo "<pre>";
			print_r($parametros);
			echo "</pre>";
			echo $param."<br/>";
			exit;
			*/
			// Inicializamos el CURL / SI no funciona se puede habilitar en el php.ini //
			$c = curl_init();
			// CURL de la URL donde se haran las peticiones //
			//curl_setopt($c, CURLOPT_URL, 'http://dev.interfase.mx/interfase.php');
			curl_setopt($c, CURLOPT_URL, 'http://10.43.29.196/interfase_cctc/interfase.php');
			//curl_setopt($c, CURLOPT_URL, 'http://localhost/interfase_cctc/interfase.php');
			// Se enviaran los datos por POST //
			curl_setopt($c, CURLOPT_POST, true);
			// Que nos envie el resultado del JSON //
			curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
			// Enviamos los parametros POST //
			curl_setopt($c, CURLOPT_POSTFIELDS, 'accion=PagarConIds&token=123456&parametros='.$param);
			// Ejecutamos y recibimos el JSON //
			$resultado = curl_exec($c);
			// Cerramos el CURL //
			curl_close($c);
			
			//echo "<pre>";
			//print_r(json_decode($resultado));
			//echo "</pre>";
			//exit;
			
			return json_decode($resultado);
		}
	}
	
	/**
	 * Método que realiza la petición de cobro a la interfase que intercomunica con el sistema de CCTC 
	 */
	private function solicitar_pago_CCTC_objetos($tc_soap = NULL, $amex_soap = NULL, $informacion_orden = NULL) {		
		if (isset($tc_soap, $informacion_orden)) {
			// Metemos todos los parametros (Objetos) necesarios a una clase dinámica llamada paramátros //
			$parametros = new stdClass;
			$parametros->tc_soap = $tc_soap;
			$parametros->amex_soap = $amex_soap;
			$parametros->informacion_orden = $informacion_orden;
			
			// Hacemos un encode de los objetos para poderlos pasar por POST ...
			$param = json_encode($parametros);
			
			//echo "<pre>";
			//print_r($parametros);
			//echo "</pre>";
			//echo $param."<br/>";
			//exit;
			
			// Inicializamos el CURL / SI no funciona se puede habilitar en el php.ini //
			$c = curl_init();
			// CURL de la URL donde se haran las peticiones //
			//curl_setopt($c, CURLOPT_URL, 'http://dev.interfase.mx/interfase.php');
			curl_setopt($c, CURLOPT_URL, 'http://10.43.29.196/interfase_cctc/interfase.php');
			//curl_setopt($c, CURLOPT_URL, 'http://localhost/interfase_cctc/interfase.php');
			// Se enviaran los datos por POST //
			curl_setopt($c, CURLOPT_POST, true);
			// Que nos envie el resultado del JSON //
			curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
			// Enviamos los parametros POST //
			curl_setopt($c, CURLOPT_POSTFIELDS, 'accion=PagarConObjetos&token=123456&parametros='.$param);
			// Ejecutamos y recibimos el JSON //
			$resultado = curl_exec($c);
			// Cerramos el CURL //
			curl_close($c);
			
			//echo "<pre>";
			//print_r(json_decode($resultado));
			//echo "</pre>";
			//exit;
			
			return json_decode($resultado);
		}
	}
	
	private function obtener_detalle_interfase_CCTC($id_cliente = 0, $consecutivo = 0) {
		if (isset($id_cliente, $consecutivo)) {
			// Metemos todos los parametros (Objetos) necesarios a una clase dinámica llamada paramátros //
			$parametros = new stdClass;
			$parametros->id_cliente = $id_cliente;
			$parametros->consecutivo = $consecutivo;
			
			// Hacemos un encode de los objetos para poderlos pasar por POST ...
			$param = json_encode($parametros);
			/*
			echo "<pre>";
			print_r($parametros);
			echo "</pre>Param: ";
			echo $param."<br/>";
			*/
			// Inicializamos el CURL / SI no funciona se puede habilitar en el php.ini //
			$c = curl_init();
			// CURL de la URL donde se haran las peticiones //
			//curl_setopt($c, CURLOPT_URL, 'dev.interfase.mx/interfase.php');
			curl_setopt($c, CURLOPT_URL, 'http://10.43.29.196/interfase_cctc/interfase.php');
			//curl_setopt($c, CURLOPT_URL, 'http://localhost/interfase_cctc/interfase.php');
			// Se enviaran los datos por POST //
			curl_setopt($c, CURLOPT_POST, true);
			// Que nos envie el resultado del JSON //
			curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
			// Enviamos los parametros POST //
			curl_setopt($c, CURLOPT_POSTFIELDS, 'accion=ObtenerDetalleAmex&token=123456&parametros='.$param);
			// Ejecutamos y recibimos el JSON //
			$resultado = curl_exec($c);
			// Cerramos el CURL //
			curl_close($c);
			/*
			echo "Resultado<pre>";
			print_r(json_decode($resultado));
			echo "</pre>";
			exit;
			*/
			return json_decode($resultado);
		}
	}

	private function registrar_estatus_compra($id_compra, $id_cliente, $id_estatusCompra)
	{
		try {
			$info_estatus = array('id_compraIn' => $id_compra, 'id_clienteIn' => $id_cliente, 'id_estatusCompraSi' => $id_estatusCompra);
			return $this->orden_compra_model->insertar_estatus_compra($info_estatus);
		} catch (Exception $ex ) {
			echo "Error en el registro del estatus de la compra: " .$ex->getMessage();
			return FALSE;
		}
	}
	
	private function registrar_detalle_pago_tc($info_detalle_pago_tc) {
		try {
			return $this->orden_compra_model->insertar_detalle_pago_tc($info_detalle_pago_tc);
		} catch (Exception $ex ) {
			echo "Error en el registro detalle del pago en ecomerce: " .$ex->getMessage();
			return FALSE;
		}
	}
	
	private function actualizar_info_compra_tc($id_cliente, $id_compra, $id_tc, $primer_digito, $tipo_pago)
	{
		$new_data = array('id_TCSi' => $id_tc, 'primer_digitoTi' => $primer_digito, 'id_tipoPagoSi' => $tipo_pago);
		try {
			return $this->orden_compra_model->actualizar_info_compra_tc($id_cliente, $id_compra, $new_data);
		} catch (Exception $ex ) {
			echo "Error en la actualización de la forma de pago: " .$ex->getMessage();
			return FALSE;
		}
	}	
	
	/**
	 * Envía un correo 
	 */
	private function enviar_correo($asunto, $mensaje) {
		$headers = "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
	    $headers .= "From: Pagos Grupo Expansión<pagosmercadotecnia@expansion.com.mx>\r\n";
		$headers .= "Bcc: abarrales@expansion.com.mx, aespinosa@expansion.com.mx, jramirez@expansion.com.mx, jesus.aguilar@externo.expansion.com.mx, harteaga@expansion.com.mx\r\n";
		
		
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
		$datos['descripcion_larga'] = $this->suscripcion_express_model->obtener_img_back($oc_id)->row()->descripcion_larga;
		$datos['descripcion_corta'] = $this->suscripcion_express_model->obtener_img_back($oc_id)->row()->descripcion_corta;
		$datos['nombre'] = $this->suscripcion_express_model->obtener_img_back($oc_id)->row()->nombre;
		return $datos;
	}
	
}

/* End of file suscripcion_express.php */
/* Location: ./application/controllers/suscripcion_express.php */
