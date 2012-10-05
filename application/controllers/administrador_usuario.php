<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include('api.php');
class Administrador_usuario extends CI_Controller {
	
	var $reg_errores = array();
	
	public static $FORMA_PAGO = array(
		1 =>	"Prosa", 
		2 =>	"American Express", 
		3 =>	"Deposito Bancario",
		4 =>	"Otro"
	);
		
	function __construct()
    {
        // Call the Model constructor
        parent::__construct();						

		
		// incluye el modelo de las direcciones de facturacion		
		$this->load->model('direccion_facturacion_model', 'direccion_facturacion_model', true);				
		$this->load->model('direccion_envio_model', 'direccion_envio_model', true);
		$this->load->model('forma_pago_model', 'forma_pago_model', true);
		$this->load->model('login_registro_model', 'login_registro_model', true);
		$this->load->model('reporte_model', 'reporte_model', true);
		
		$this->api = new Api();
    }
	
	public function index()
	{
	}
	
	### obtener los datos del cliente por id
	public function cliente_id($id_cliente = ""){		
		$cliente = $this->login_registro_model->obtener_cliente_id($id_cliente);
		if($cliente->num_rows() > 0){
			$data['cliente'] = $cliente->row();			
			echo json_encode($data);
		} else{
			echo json_encode($data);
		}				
	}
	
	## obtener las compras que ha realizado el cliente
	public function compras_cliente(){
				
		$data['id_cliente'] = $id_cliente = $_POST['id_cliente'];			 
		## obtener las compras pagadas del cliente
		$compras_cliente = $this->reporte_model->obtener_compras_cliente($id_cliente);
		
		if($compras_cliente->num_rows()>0){
			$data['compras'] = array();
			$todas_compras = $compras_cliente->result_array();			
			foreach($todas_compras as $ind => $compra){
				$id_compra = $compra['id_compraIn'];				
				$data['compras'][$ind]['compra'] = $compra;
				
				//se obtiene el medio y la fecha de pago
				$forma_pago = $this->reporte_model->obtener_medio_pago($id_compra, $id_cliente);
				if($forma_pago -> num_rows > 0){
					$data['compras'][$ind]['medio_pago'] = self::$FORMA_PAGO[($forma_pago->row()->id_tipoPagoSi)];	
					$data['compras'][$ind]['fecha_compra'] = 	$forma_pago->row()->fecha_registroTs;				
				}
				else{
					$data['compras'][$ind]['medio_pago'] = "no existe";
					$data['compras'][$ind]['fecha_pago'] = "no existe";					
				}
				//se obtiene el codigo de autorizacion de la transaccion
				$ca = $this->reporte_model->obtener_codigo_autorizacion($id_compra, $id_cliente);
				if($ca->num_rows() > 0){
					$data['compras'][$ind]['respuesta_banco'] = $ca->row()->respuesta_bancoVc;
				}			
				else{
					$data['compras'][$ind]['respuesta_banco'] = "no hay respuesta";
				}
				
				//se obtiene el id de promocion de la compra
				$id_promo = $this->reporte_model->obtener_promo_compra($id_compra, $id_cliente);
				
				// se obtiene el detalle de la promocion
				$promocion = $this->reporte_model->obtener_detalle_promo($id_promo);	
				if($promocion->num_rows()>0){
					$data['compras'][$ind]['promocion'] = $promocion->row();
				}
				//se obtiene el total de articulos en la promocion y el total que se pago por ellos 
				$articulos_res = $this->reporte_model->obtener_articulos($id_promo);
				$articulos = $articulos_res->result_array();							 
				$monto = 0;

				// Se obtienen los articulos de cada promocion y el total pagado por ellos 							
				foreach( $articulos as $i => $articulo){
					if($articulo['issue_id']){
						$issue = $this->reporte_model->obtener_issue($articulo['issue_id']);						
						$articulos[$i]['tipo_productoVc']= $issue->row()->descripcionVc;
					}
					else{
						$articulos[$i]['tipo_productoVc'] = $articulo['tipo_productoVc'];
					}
					$monto+= $articulo['tarifaDc'];
				}
				
				$data['compras'][$ind]['articulos'] = $articulos;				
				$data['compras'][$ind]['monto'] = $monto;												
			}
		}
		else{
			$data['compras'] = NULL;
		}
		
		$this->load->view('reportes/reporte_compras_usuario', $data);		
	}
	
	## obtener el detalle de cada una de las compras que tiene el cliente
	public function detalle_compra($id_compra = "", $id_cliente = ""){
		
		$data['compra']['id_compra'] = $id_compra; 
		$data['compra']['direccion_amex'] = NULL;
		$data['compra']['codigo_autorizacion'] = NULL;
		
		//se obtiene el medio y la fecha de pago
		$forma_pago = $this->reporte_model->obtener_medio_pago($id_compra, $id_cliente);
		
		if($forma_pago -> num_rows > 0){
			//si el pago es con prosa se obtiene el detalle de la tarjeta
			if(($forma_pago->row()->id_tipoPagoSi == 1) || ($forma_pago->row()->id_tipoPagoSi == 2)){				
				$tc = $this->reporte_model->obtener_tc($id_cliente, $forma_pago->row()->id_tipoPagoSi);				
				$data['compra']['medio_pago'] = $tc->row()->descripcionVc." terminación ".$tc->row()->terminacion_tarjetaVc;				
					
				//se obtiene el codigo de autorizacion si es que existe
				$ca = $this->reporte_model->obtener_codigo_autorizacion($id_compra, $id_cliente);
				if($ca->num_rows() > 0 ){									
					if($ca->row()->codigo_autorizacionVc > 0){
						$data['compra']['codigo_autorizacion'] = "<span class='info-negro'>codigo de autorización:</span> ".$ca->row()->codigo_autorizacionVc;
					}
					else{
						$data['compra']['codigo_autorizacion'] = "<span class='info-negro'>codigo de autorización:</span> ".$ca->row()->codigo_autorizacionVc ."<br />". $ca->row()->respuesta_bancoVc ;
					}
				}	
				else{
					$data['compra']['codigo_autorizacion'] = "<span class='info-negro'>(No se realizo el cobro)</span>";	
				}
					
			}
			else{				
				$data['compra']['medio_pago'] = self::$FORMA_PAGO[($forma_pago->row()->id_tipoPagoSi)];					
			}
			
			//si el pago es con amex se obtiene el detalle de la tarjeta y la direccion de amex
			if($forma_pago->row()->id_tipoPagoSi == 2){				
				$data['compra']['direccion_amex'] = "direccion ammex";	
			}
										
			$data['compra']['fecha_compra'] = 	$forma_pago->row()->fecha_registroTs;				
		}
		else{
			$data['compra']['medio_pago'] = NULL;
			$data['compra']['fecha_pago'] = NULL;				
		}
				
		//se obtiene la direccion de envio si es que existe			
		$dir_envio = $this->reporte_model->obtener_dir_envio($id_compra, $id_cliente);
		if($dir_envio->num_rows() > 0){
				$data['compra']['dir_envio'] = 	$dir_envio->row()->address1." ".
												$dir_envio->row()->address2." ".
												$dir_envio->row()->address4."<br />".
												$dir_envio->row()->zip."<br />".
														$dir_envio->row()->address3."<br />".
														$dir_envio->row()->city."<br />".
														$dir_envio->row()->state;	
		}
		else{
			$data['compra']['dir_envio']= "No requiere";
		}
		
		//se obtiene la direccion de facturacion y Razon Social
		$facturacion = $this->reporte_model->obtener_facturacion($id_compra, $id_cliente);
		if($facturacion->num_rows() > 0){								
			$consecutivo = $facturacion->row()->id_consecutivoSi;
			$id_rs = $facturacion->row()->id_razonSocialIn;
			
			$dir_facturacion = $this->reporte_model->obtener_dir_facturacion($consecutivo, $id_cliente);				
			$data['compra']['dir_facturacion']  =  $dir_facturacion->row()->address1." ".
														$dir_facturacion->row()->address2." ".
														$dir_facturacion->row()->address4."<br />".
														$dir_facturacion->row()->zip."<br />".
														$dir_facturacion->row()->address3."<br />".
														$dir_facturacion->row()->city."<br />".
														$dir_facturacion->row()->state;
			
			$rs = $this->reporte_model->obtener_razon_social($id_rs);
			$data['compra']['razon_social'] = $rs->row()->company."<br />".$rs->row()->tax_id_number;											
			
		}						
		else{
			$data['compra']['dir_facturacion'] = NULL;
			$data['compra']['razon_social'] = NULL;
		}
		
		//se obtiene el id de promocion de la compra
		$id_promo = $this->reporte_model->obtener_promo_compra($id_compra, $id_cliente);
		
		// se obtiene el detalle de la promocion
		$promocion = $this->reporte_model->obtener_detalle_promo($id_promo);	
		if($promocion->num_rows()>0){
			$data['compra']['promocion'] = $promocion->row();
		}
					
		
		//se obtiene el total de articulos en la promocion y el total que se pago por ellos 
		$articulos_res = $this->reporte_model->obtener_articulos($id_promo);
		$articulos = $articulos_res->result_array();							 
		$monto = 0;

		// Se obtienen los articulos de cada promocion y el total pagado por ellos 							
		foreach( $articulos as $i => $articulo){
			if($articulo['issue_id']){
				$issue = $this->reporte_model->obtener_issue($articulo['issue_id']);						
				$articulos[$i]['tipo_productoVc']= $issue->row()->descripcionVc;
			}
			else{
				$articulos[$i]['tipo_productoVc'] = $articulo['tipo_productoVc'];
			}
			$monto+= $articulo['tarifaDc'];
		}
		$data['compra']['articulos'] = $articulos;
		$data['compra']['monto'] = $monto;
													
		$this->load->view('reportes/detalle_compra', $data);
	}
	
	###actualizar la informacion del cliente, los datos se reciben mediante POST y el id de cliente viene en GET
	public function actualizar_cliente($id_cliente = ""){
				
		if($_POST){
			// se valida que la informacion sea correcta			
			$cliente_info =	$this->valida_datos_update();
			$cliente_info['id_clienteIn'] = $id_cliente;
				
			// si existe algun error se vuelve a solicitar la onformacion	
			if(!empty($this->login_errores)){
					$data['error'] = 1;					
					$data['errores'] = $this->login_errores;
				// si no hay errores se procede con la actualizacion						
			} else{				
				if($this->login_registro_model->actualizar_cliente($cliente_info)){
					$data['error'] = 0;							
				} else{
					$data['error'] = 1;
				}				
			}
			
		}
		// se regresa la informacion en json si ocurrio algun error se regresa 1 en caso contrario se regresa 0		
		echo json_encode($data);
				
	}
	
	// valida que la informacion del cliente se acorrecta
	public function valida_datos_update(){
		$datos = array();
		//revisamos que venga encriptada la informcaion del usuario (mail y password), en caso contraro regresamos el error
		if(!empty($_POST['log_data'])){
			$login_data = $this->api->decrypt($_POST['log_data'], $this->api->key);
			$login_data = explode('|',$login_data);
			$mail_registrado = 	$login_data[0]; 												   	
			$pass_registrado = 	$login_data[1];						
		} else {
			$this->login_errores['email'] = '<div class="error2">Información incompleta.</div>';
			$this->login_errores['password'] = '<div class="error2">Información incompleta.</div>';
		}	
				
		// revisamos que el mail sea valido		
		if (filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)){
			// si el mail es igual al registrado no se cambia la contraseña en caso contrario se envia la contraseña actual para que se arme el md5 
			if($mail_registrado == $_POST['email']){
				$datos['email'] = htmlspecialchars(trim($_POST['email']));
			} else{
				$datos['email'] = htmlspecialchars(trim($_POST['email']));
				$datos['password'] = $pass_registrado;
			}
		} else {
			$this->login_errores['email'] = '<div class="error2">Por favor ingresa una dirección de correo válida. Ejemplo: nombre@dominio.mx</div>';		
		}
		
		
		if(array_key_exists('nombre', $_POST)){
			if(preg_match('/^[A-Z \'.-áéíóúÁÉÍÓÚÑñ]{1,30}$/i', $_POST['nombre'])) { 
				$datos['salutation'] = $_POST['nombre'];
			} else{
				$this->login_errores['nombre'] = '<div class="error2">Por favor ingresa tu nombre correctamente</div>';
			}
		} 
		
		if(array_key_exists('apellido_paterno', $_POST)){
			if(preg_match('/^[A-Z \'.-áéíóúÁÉÍÓÚÑñ]{1,30}$/i', $_POST['apellido_paterno'])) { 
				$datos['fname'] = $_POST['apellido_paterno'];
			} else{
				$this->login_errores['apellido_paterno'] = '<div class="error2">Por favor ingresa tu apellido paterno</div>';
			}
		}
		
		if(array_key_exists('apellido_materno', $_POST)){
			if(preg_match('/^[A-Z \'.-áéíóúÁÉÍÓÚÑñ]{1,30}$/i', $_POST['apellido_materno'])) { 
				$datos['lname'] = $_POST['apellido_materno'];
			}
		}
					
		if(!empty($_POST['password_actual'])){
						
			if($pass_registrado == $_POST['password_actual']){
				if(!empty($_POST['nuevo_password'])){
					$datos['password'] = $_POST['nuevo_password'];
				} else{
					$datos['password'] = $_POST['password_actual'];
				}																								
			} else{
				$this->login_errores['password'] = '<div class="error2">Password actual incorrecto</div>';
			}												
		} else{
			
		}	
		
											
		return $datos;				
		
	}
	
	// se obtiene un listado JSON con las Razones Sociales que tenga guardadas el cliente
	public function listar_razon_social($id_cliente = ""){		
		$data['rs'] = $this->direccion_facturacion_model->listar_razon_social($id_cliente);						
		echo json_encode($data);
	}
	
	// se obtiene un listado JSON con las Direcciones de Envio que tenga guardadas el cliente
	public function listar_direccion_envio($id_cliente = ""){
		$data['direccion_envio'] = $this->direccion_envio_model->listar_direcciones($id_cliente)->result_array();							
		echo json_encode($data);
	}
	
	// se obtiene un listado JSON con las Razones Sociales que tenga guardadas el cliente
	public function listar_tarjetas($id_cliente = ""){
		$data['tarjetas'] = $this->forma_pago_model->listar_tarjetas($id_cliente)->result_array();							
		echo json_encode($data);
	}
	
	// Funcion en la cual se edita la informacion del usuario.
	public function editar_rs($consecutivo = 0){								
		
		// obtenemos la informacion de la razon social a editar							
		$datos_rs = $this->direccion_facturacion_model->obtener_rs($consecutivo);
			
		if ($consecutivo) {
			// se guarda el id de la razoon social										
			$data['consecutivo']=$consecutivo;
			// se guarda la informacion de razon social
			$data['datos_direccion'] = $datos_rs;										
																
			// si se enviaron los datos del formulario  seguimos con el proceso en caso contrario solo desplegamos la informacion del ciente q se va a a actualizar																					
			if($_POST){				
								
				$form_values = array();	//alojará los datos previos a la inserción	
				$form_values = $this->get_datos_rs(); // se obtienen los datos validados o los errores por mostrar					
				
				// si no  hay errores en la informacion continuamos en caso contrario se muestran los errores en la vista		
				if (empty($this->reg_errores)) {										
					
					//asignamos el id de razon social		
					$form_values['direccion']['id_razonSocialIn'] = $consecutivo;																								
											
					// se actualiza la informacion						
					if($this->direccion_facturacion_model->actualizar_rs($consecutivo, $form_values['direccion'])){
						if($_POST['chk_default'] == 1 || $consecutivo == 0) {
							$this->direccion_facturacion_model->establecer_predeterminado_rs($_POST['id_cliente'], $consecutivo);						
						}						
						echo "<label id='update_correcto'>1</label>";
					}	
					
											
				}
				// caso contraio se muestran los errores en la vista 
				else{												
					$data['reg_errores'] = $this->reg_errores;		
					$this->load->view('administrador_usuario/editar_rs', $data);								
				}																			
			}	// caso contrario solo se muestra la informacion por actualizar
			else{
				$this->load->view('administrador_usuario/editar_rs', $data);	
			}								
		}
								
	}
	
	// funcion que revisa que los datos enviados para actualizar la razon social sean correctos
	private function get_datos_rs(){
		$datos = array();		 		
		
		if(!empty($_POST['txt_rfc'])){
			if((strlen($_POST['txt_rfc'])>13)||(strlen($_POST['txt_rfc'])<12)){
				$this->reg_errores['txt_rfc'] = '<span class="error">Por favor ingresa tu RFC</span>';		
			} else{
				if(strlen($_POST['txt_rfc'])==12){
					if (preg_match('/^([a-zA-Z]{3})+([0-9]{6})+([a-zA-Z0-9]{3})$/', $_POST['txt_rfc'])) {
						$datos['direccion']['tax_id_number'] = $_POST['txt_rfc'];
					} else {
						$this->reg_errores['txt_rfc'] = '<span class="error">Por favor ingresa tu RFC</span>';
					}	
				} else if(strlen($_POST['txt_rfc'])==13){					    
					if (preg_match('/^([a-zA-Z]{4})+([0-9]{6})+([a-zA-Z0-9]{3})$/', $_POST['txt_rfc'])) {
						$datos['direccion']['tax_id_number'] = $_POST['txt_rfc'];
					}else {
						$this->reg_errores['txt_rfc'] = '<span class="error">Por favor ingresa tu RFC</span>';
					}
				}
			}
		} else{
			$this->reg_errores['txt_rfc'] = '<span class="error">Por favor ingresa tu RFC</span>';				
		}
		
		if(!empty($_POST['txt_razon_social'])){
			$datos['direccion']['company'] = $_POST['txt_razon_social'];
		} else{
			$this->reg_errores['txt_razon_social'] = '<span class="error2">Por favor ingresa tu nombre o razón social</span>';
		}
		if(!empty($_POST['txt_email'])){
			if (filter_var($_POST['txt_email'], FILTER_VALIDATE_EMAIL)) {			    		
				$datos['direccion']['email'] = $_POST['txt_email'];					
			} else{
				$this->reg_errores['txt_email'] = '<span class="error2">Por favor ingresa un correo electrónico válido. Ejemplo: nombre@dominio.mx</span>';
			}	
			
		} else{
			$this->reg_errores['txt_email'] = '<span class="error2">Por favor ingresa un correo electrónico válido. Ejemplo: nombre@dominio.mx</span>';
		}	
		if ($_POST['chk_default'] == 1) {
			$datos['direccion']['id_estatusSi'] = 3;	//indica que será la razon social predeterminada			
		} else{
			$datos['direccion']['id_estatusSi'] = 1;	//indica que será la razon social
		}		
		
		return $datos;								
	}
	
	// funcion para eliminar una razon social por id, el label id es para comprobar que se elimino correctamente en el AJAX
	public function eliminar_rs($id_rs = ''){		
		if($this->direccion_facturacion_model->eliminar_rs($id_rs)){
			echo "<label id='eliminar_correcto'>1</label>";	
		}				
	}

	// Funcion para actualizar los datops de TC
	public function editar_tc($id_tc, $id_tipo, $id_cliente){
		
		if($_POST){
			echo "post";
		}
		else{
			echo "TC: ".$id_tc . " ->TIPO:" . $id_tipo . "->CLIENTE:" . $id_cliente;	
		}
	}	
	
	//Funcion para eliminar direccion de envio		
	public function eliminar_dir_envio($id_dir_envio = "", $id_cliente){
		echo $id_dir_envio ."Cliente: " .$id_cliente;	
		if(!stristr($this->direccion_envio_model->eliminar_direccion($id_cliente, $id_dir_envio), "error")){
			echo "<label id='eliminar_direccion'>1</label>";	
		}						
		//$msg_eliminacion = ;
						
	}
	
	//Funcion para eliminar una tarjeta
	public function eliminar_tc($id_tc, $id_cliente){												
		if ($this->eliminar_tarjeta_interfase_CCTC($id_cliente, $id_tc)) {					
			if(!stristr($this->forma_pago_model->eliminar_tarjeta($id_cliente, $id_tc), "error")){
				echo "<label id='eliminar_tarjeta'>1</label>";
			}
		}	
	}
	
	private function eliminar_tarjeta_interfase_CCTC($id_cliente = 0, $consecutivo = 0) {
		if (isset($id_cliente, $consecutivo)) {
			// Metemos todos los parametros (Objetos) necesarios a una clase dinámica llamada paramátros //
			$parametros = new stdClass;
			$parametros->id_cliente = $id_cliente;
			$parametros->consecutivo = $consecutivo;
			
			// Hacemos un encode de los objetos para poderlos pasar por POST ...
			$param = json_encode($parametros);
					
			// Inicializamos el CURL / SI no funciona se puede habilitar en el php.ini //
			$c = curl_init();
			// CURL de la URL donde se haran las peticiones //
			curl_setopt($c, CURLOPT_URL, 'http://localhost/interfase_cctc/interfase.php');
			//curl_setopt($c, CURLOPT_URL, 'http://10.43.29.196/interface_cctc/solicitar_post.php');
			// Se enviaran los datos por POST //
			curl_setopt($c, CURLOPT_POST, true);
			// Que nos envie el resultado del JSON //
			curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
			// Enviamos los parametros POST //
			curl_setopt($c, CURLOPT_POSTFIELDS, 'accion=EliminarTarjeta&token=123456&parametros='.$param);
			// Ejecutamos y recibimos el JSON //
			$resultado = curl_exec($c);
			// Cerramos el CURL //
			curl_close($c);
		
			return json_decode($resultado);
		}
	}
}

/* End of file administrador_usuario.php */
/* Location: ./application/controllers/administrador_usuario.php */