<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Orden_Compra extends CI_Controller {
	var $title = 'Orden de Compra';
	var $subtitle = 'Orden de Compra';
	var $reg_errores = array();				//validación para los errores
	
	private $id_cliente;
	 
	function __construct()
    {
        // Call the Model constructor
        parent::__construct();
		
		//si no hay sesión
		//manda al usuario a la... pagina de login
		$this->redirect_cliente_invalido('id_cliente', '/index.php/login');
		
		//bandera de redirección
		$this->session->set_userdata("redirect_to_order", "orden_compra");
		
		//cargar el modelo en el constructor
		$this->load->model('forma_pago_model', 'tarjeta_modelo', true);
		$this->load->model('direccion_envio_model', 'envio_modelo', true);
		$this->load->model('direccion_facturacion_model', 'facturacion_modelo', true);
		
		//si la sesión se acaba de crear, toma el valor inicializar el id del cliente de la session creada en el login/registro
		$this->id_cliente = $this->session->userdata('id_cliente');

    }

	public function index()	
	{
		if ($_POST) {
			if (array_key_exists('direccion_selecionada', $_POST))
				$this->session->set_userdata('dir_facturacion', $_POST['direccion_selecionada']);
		}
		$this->resumen();
	}
	
	public function resumen($msg = '', $redirect = TRUE) 
	{
		$data['title'] = $this->title;
		$data['subtitle'] = $this->subtitle;
		$data['mensaje'] = $msg;
		$data['redirect'] = $redirect;
		
		/*Recuperar la info gral. de la orden*/
		$id_cliente = $this->id_cliente;
		
		//Tarjeta
		$consecutivo = $this->session->userdata('tarjeta');
		
		if (is_array($this->session->userdata('tarjeta'))) {
			$detalle_tarjeta = $this->session->userdata('tarjeta');
			
			//echo var_dump($detalle_tarjeta);
			
			$data['tc'] = $detalle_tarjeta['tc'];
			if ($detalle_tarjeta['tc']['id_tipo_tarjetaSi'] == 1) { //es AMERICAN EXPRESS
				$data['amex'] = $detalle_tarjeta['amex'];
				//en este caso se consultará la info del WS
			}
			
		} else {
			$detalle_tarjeta = $this->tarjeta_modelo->detalle_tarjeta($consecutivo, $id_cliente);
			$data['tc'] = $detalle_tarjeta;	//trae la tc
		
			if ($detalle_tarjeta->id_tipo_tarjetaSi == 1) { //es AMERICAN EXPRESS
				$data['amex'] = $this->detalle_tarjeta_CCTC($id_cliente, $consecutivo);
				//en este caso se consultará la info del WS
			}
		}
		
		//dir_envío
		$consecutivo = $this->session->userdata('dir_envio');
		if (is_array($consecutivo)) {
			//$detalle_envio = $this->session->userdata('dir_envio');
			$data['dir_envio'] = $consecutivo;
		} else {
			$detalle_envio = $this->envio_modelo->detalle_direccion($consecutivo, $id_cliente);
			$data['dir_envio'] = $detalle_envio;	
		}
		
		//dir_facturación
		$consecutivo = $this->session->userdata('dir_facturacion');
		if (is_array($consecutivo)) {
			//$detalle_envio = $this->session->userdata('dir_envio');
			$data['dir_facturacion'] = $consecutivo;
		} else {
			$detalle_facturacion = $this->facturacion_modelo->obtener_direccion($id_cliente, $consecutivo);
			$data['dir_facturacion']=$detalle_facturacion;
		}		
		
		//cargar vista	
		$this->cargar_vista('', 'orden_compra', $data);
	}
	
	public function checkout() {
		/*Realizar el pago en CCTC*/
		echo "El pago se realizará aquí.";	
	}
	
	public function registrar($form = 'tc') 
	{
		//echo 'Session: '.$this->session->userdata('id_cliente');
		
		$id_cliente = $this->id_cliente;
		
		$consecutivo = $this->modelo->get_consecutivo($id_cliente);
		
		$data['title'] = $this->title;
		$data['subtitle'] = ucfirst('Nueva Forma de Pago');
		
		//catálogo que se obtendrá del CCTC
		$lista_tipo_tarjeta = $this->lista_tipos_tarjeta_WS();
		$data['lista_tipo_tarjeta'] = $lista_tipo_tarjeta;
		
		$script_file = "<script type='text/javascript' src='". base_url() ."js/forma_pago.js'></script>";
		$data['script'] = $script_file;
				
		//recuperar el listado de las tarjetas del cliente
		$data['lista_tarjetas'] = $this->modelo->listar_tarjetas($id_cliente);
		
		$data['form'] = $form;		//para indicar qué formulario mostrar
		if ($_POST)	{	//si hay parámetros del formulario
			
			//común
			$form_values = array();	//alojará los datos previos a la inserción	
			$form_values = $this->get_datos_tarjeta($form);
			
			$form_values['tc']['id_clienteIn'] = $id_cliente;
			$form_values['tc']['id_TCSi'] = $consecutivo + 1;		//cambió
				
				/* Se debe validar si se quiere guardar la tarjeta antes de registrarla
				 * esto con: $form_values['guardar']
				 * si no se quiere guardar se continua con el proceso
				 * */
			//echo var_dump($form_values);
			//exit();
			if (empty($this->reg_errores)) {	
				//si no hay errores y se solicita registrar la tarjeta
				if (isset($form_values['guardar']) || isset($form_values['tc']['id_estatusSi'])) {
					//verificar que no exista la tarjeta activa en la BD
					//var_dump($form_values);
					//exit();
					if ($form == 'tc') {
						$form_values['tc']['descripcionVc'] = $lista_tipo_tarjeta[$form_values['tc']['id_tipo_tarjetaSi'] - 1]->descripcion;
						$form_values['amex'] = null;	//para que no se tome encuenta.
					} else if ($form == 'amex') {
						$form_values['amex']['id_clienteIn'] = $id_cliente;
						$form_values['amex']['id_TCSi'] = $consecutivo + 1;
						$form_values['tc']['id_tipo_tarjetaSi'] = 1;
						$form_values['tc']['descripcionVc'] = "AMERICAN EXPRESS";
					}
					//echo var_dump($form_values);
					//exit();
					
					if($this->modelo->existe_tc($form_values['tc'])) {	//Redirect al listado por que ya existe
						//$url = $this->config->item('base_url').'/index.php/forma_pago/listar/'.$id_cliente;
						//header("Location: $url");
						$this->listar("La tarjeta ya está registrada.", FALSE);
						//echo "La tarjeta ya está registrada.";
						//exit();
					} else {
						//echo "se manda insertar en CCTC";
						
						if ($this->registrar_tarjeta_CCTC($form_values['tc'], $form_values['amex'])) {
						//if (1) {	//echo "Se registró exitosamente! en CCTC";
							
							//Registrar Localmente
							if ($this->modelo->insertar_tc($form_values['tc'])) {
								$this->listar("Tarjeta registrada correctamente.");
							} else {
								$this->listar("Hubo un error en el registro en CMS.", FALSE);
								//echo "<br/>Hubo un error en el registro en CMS";
							}
						} else {
							$this->listar("Hubo un error en el registro en CCTC.", FALSE);
							//echo "Hubo un error en el registro en CCTC";
						}
					}						
				} else {
					/*Poner en session la TC/AMEX*/
					//si no se guardará la tc, almacenar la info para la venta  
					echo "no se almacenará la TC >> Pasar a captura de dir. de envío<br/> Coming soon...";
					//exit();	
				}
				//se envía la tc al WS de CCTC y de acuerdo a la respuesta...
				/*					
				//De momento se regresará al listado de tarjetas
				if ($this->modelo->insertar_tc($form_values['tc'])) {
					$url = $this->config->item('base_url').'/index.php/forma_pago/listar/'.$id_cliente;
					header("Location: $url");
					exit();	
				}
				*/
			} else {	//Si hubo errores
				//vuelve a mostrar la info.
				$data['reg_errores'] = $this->reg_errores;
				$this->cargar_vista('', 'forma_pago' , $data);	
			}
		} else {
			$this->cargar_vista('', 'forma_pago' , $data);
		}
	}

	public function editar($consecutivo)	//el consecutivo de la tarjeta
	{
		$id_cliente = $this->id_cliente;
		
		$data['title'] = $this->title;
		$data['subtitle'] = ucfirst('editar Forma de Pago');
		
		//recuperar la información local de la tc
		$detalle_tarjeta = $this->modelo->detalle_tarjeta($consecutivo, $id_cliente);
		//Siempre se trae la info para tc
		$data['tarjeta_tc'] = $detalle_tarjeta;
		//var_dump($detalle_tarjeta);
		//exit();
		
		//array par al anueva información
		$nueva_info = array();
		
		if ($detalle_tarjeta->id_tipo_tarjetaSi == 1) { //es AMERICAN EXPRESS
			$data['vista_detalle'] = 'amex';
			$data['tarjeta_amex'] = $this->detalle_tarjeta_CCTC($id_cliente, $consecutivo);
			//en este caso se consultará la info del WS
		} else  {	
			//Si no es de tipo American Express, trae la info de la base local
			//y especificat el tipo de la tarjeta y el numero.
			$data['vista_detalle'] = 'tc';
		}

		//Se intentará actualizar la información
		if ($_POST) {
			$tipo_tarjeta = $data['vista_detalle'];
			//trae datos del formulario para actualizar
			//echo "Se va a editar.";
			$nueva_info = $this->get_datos_tarjeta($tipo_tarjeta);	//tc/amex
			
			//errores
			$data['reg_errores'] = $this->reg_errores;	
			
			if (empty($data['reg_errores'])) {	//si no hubo errores
				//preparar la petición al WS, campos comunes
				$nueva_info['tc']['id_clienteIn'] = $id_cliente;
				$nueva_info['tc']['id_TCSi'] = $consecutivo;
				$nueva_info['tc']['terminacion_tarjetaVc'] = $detalle_tarjeta->terminacion_tarjetaVc;
				//Preparación de la sol.
				if ($tipo_tarjeta == 'tc') {
					$nueva_info['amex'] = null;		
					$nueva_info['tc']['id_tipo_tarjetaSi'] = $detalle_tarjeta->id_tipo_tarjetaSi;
					
				} else {
					$nueva_info['amex']['id_clienteIn'] = $id_cliente;
					$nueva_info['amex']['id_TCSi'] = $consecutivo;
				}
				//var_dump($nueva_info);
				//exit();
				
				//actualizar en CCTC
				if($this->editar_tarjeta_CCTC($nueva_info['tc'], $nueva_info['amex'])) {
				//if (1) {
					//echo "Tarjeta actualizada en CCTC.<br/>";
					//checar el estatus:
					if (!isset($nueva_info['tc']['id_estatusSi'])) {	
						//si no está como predeterminada se deja en activo
						//echo "Activo!!!  ";
						$nueva_info['tc']['id_estatusSi'] = 1;
					}					
					//registrar cambios localmente, siempre se manda la info de $nueva_info['tc']
					$msg_actualizacion = $this->modelo->actualiza_tarjeta($consecutivo, $id_cliente, $nueva_info['tc']);
					$data['msg_actualizacion'] = $msg_actualizacion;
						
					$this->listar($msg_actualizacion);
				} else {
					echo "Error de actualización hacia CCTC.<br/>";	//redirect					
				}
			} else {	//sí hubo errores
				$data['msg_actualizacion'] = "Campos incorrectos";
				//echo "<br/>Campos incorrectos.<br/>";
				//var_dump($this->reg_errores);
			}
			//print_r($nueva_info[$tipo_tarjeta]);
		}//If POST
		
		$this->cargar_vista('', 'forma_pago' , $data);
	}
	
	public function eliminar($consecutivo = '')
	{
		$id_cliente = $this->id_cliente;
		$data['title'] = $this->title;
		$data['subtitle'] = ucfirst('Eliminar Forma de Pago');
		
		//exit();
		if ($this->eliminar_tarjeta_CCTC($id_cliente, $consecutivo)) {
		//if (1) {
			//echo "Eliminado correctamente de CCTC";
			//eliminar lógicamente en la bd local
			$msg_eliminacion =
				$this->modelo->eliminar_tarjeta($id_cliente, $consecutivo);
		} else {
			//echo "no se pudo eliminar correctamente de CCTC";
			$msg_eliminacion = "no se pudo eliminar correctamente de CCTC";
		}
		/*Pendiente el Redirect*/
		//echo "<br/>se eliminó la tarjeta $consecutivo del cliente $id_cliente<br/>";
		//echo $data['msg_eliminacion´];
		
		//cargar la lista
		$this->listar($msg_eliminacion, FALSE);
		//$this->cargar_vista('', 'forma_pago' , $data);
	}
	
	private function detalle_tarjeta_CCTC($id_cliente=0, $consecutivo=0)	//siempre será la información de AMEX
	{
		//Traer la info de amex
		try {  
			$cliente = new SoapClient("https://cctc.gee.com.mx/ServicioWebCCTC/ws_cms_cctc.asmx?WSDL");
				
			$parameter = array(	'id_clienteNu' => $id_cliente, 'consecutivo_cmsSi' => $consecutivo);
			
			$obj_result = $cliente->ConsultarAmex($parameter);
			$tarjeta_amex = $obj_result->ConsultarAmexResult;	//regresa un objeto
			
			//print($simple_result);
			
			return $tarjeta_amex;
			
		} catch (SoapFault $exception) {
			echo $exception;  
			echo '<br/>error: <br/>'.$exception->getMessage();
			//exit();
			return false;
		}
	}

	private function cargar_vista($folder, $page, $data)
	{	
		//Para automatizar un poco el desplieguee
		$this->load->view('templates/header', $data);
		$this->load->view($folder.'/'.$page, $data);
		$this->load->view('templates/footer', $data);
	}
	
	/*
	 * Verifica la sesión del usuario
	 * 
	 * */
	private function redirect_cliente_invalido($revisar = 'id_cliente', $destino = '/index.php/login', $protocolo = 'http://') {
		if (!$this->session->userdata($revisar)) {
			//$url = $protocolo . BASE_URL . $destination; // Define the URL.
			$url = $this->config->item('base_url') . $destino; // Define the URL.
			header("Location: $url");
			exit(); // Quit the script.
		}
	}

}

/* End of file orden_compra.php */
/* Location: ./application/controllers/orden_compra.php */