<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Direccion_Envio extends CI_Controller {
	var $title = 'Direcci&oacute;n de Env&iacute;o'; 		// Capitalize the first letter
	var $subtitle = 'Direcci&oacute;n de Env&iacute;o'; 	// Capitalize the first letter
	var $reg_errores = array();		//validación para los errores
	
	public static $TIPO_DIR = array(
		"RESIDENCE"	=> 0, 
		"BUSINESS"	=> 1, 
		"OTHER"		=>	2
	);
	
	
	private $id_cliente;
	//protected $lista_bancos = array();
	 
	function __construct()
    {
        // Call the Model constructor
        parent::__construct();
		
		//cargar el modelo en el constructor
		$this->load->model('direccion_envio_model', 'modelo', true);
		//la sesion se carga automáticamente
		
		//si no hay sesión
		//manda al usuario a la... página de login
		//$this->redirect_cliente_invalido('id_cliente', '/index.php/login');
		
		//si la sesión se acaba de crear, toma el valor inicializar el id del cliente de la session creada en el login/registro
		$this->id_cliente = $this->session->userdata('id_cliente');

    }

	public function index()	//Para pruebas se usa 1
	{
		//Recuperar el "id_ClienteNu" de la sesion
		
		//$id_cliente = $this->session->userdata('id_cliente');
		
		//echo 'cliente_Id: '.$id_cliente;
		
		//echo 'tipo '. gettype($tc);
		//echo 'cliente_Id'.$tc->cliente_id;
		//var_dump($tc);
		
		$this->listar();
	}
	
	public function listar($msg = '') 
	{	
		/*asignación de la session*/
		//$id_cliente = $this->session->userdata('id_cliente');
		//$this->session->set_userdata('id_cliente', $id_cliente);
		
		/*
		echo 'cliente: '.$this->session->userdata('id_cliente').'<br/>';
		echo 'Session: '.$this->session->userdata('session_id').'<br/>';
		echo 'last_Activity: '.$this->session->userdata('last_activity').'<br/>';
		$ano = date('m.d')	;
		echo "date: ". $ano;
		*/
		//EL usuario se toma de la sesión...
		
		$data['title'] = $this->title;
		$data['subtitle'] = $this->subtitle;		
		$data['mensaje'] = $msg;
		
		//listar por default las direcciones del cliente
		$data['lista_direcciones'] = $this->modelo->listar_direcciones($this->id_cliente);
		
		//cargar vista	
		$this->cargar_vista('', 'direccion_envio', $data);
		
	}
	
	public function registrar() 
	{
		//echo 'Session: '.$this->session->userdata('id_cliente');
		
		$id_cliente = $this->id_cliente;
		
		$consecutivo = $this->modelo->get_consecutivo($id_cliente);
		
		$data['title'] = $this->title;
		$data['subtitle'] = ucfirst('Nueva Direcci&oacute;n');
		
		//catálogo de paises de think
		$lista_paises_think = $this->modelo->listar_paises_think();
		$data['lista_paises_think'] = $lista_paises_think;
				
		//recuperar el listado de las direcciones del cliente
		$data['lista_direcciones'] = $this->modelo->listar_direcciones($id_cliente);
		
		$data['registrar'] = TRUE;		//para indicar que se debe mostrar formulario de registro
		/*agregar el script para este formulario*/
		$script_file = "<script type='text/javascript' src='". base_url() ."js/dir_envio.js'> </script>";
		$data['script'] = $script_file;
		
		//$data['alert'] = "alert('hola mundo ecommerce GEx!');";
		 
		
		if ($_POST)	{	//si hay parámetros del formulario
			
			//común
			$form_values = array();	//alojará los datos previos a la inserción	
			$form_values = $this->get_datos_direccion();
			
			$form_values['direccion']['id_clienteIn'] = $id_cliente;
			$form_values['direccion']['id_consecutivoSi'] = $consecutivo + 1;		//cambió
			$form_values['direccion']['address_type'] = self::$TIPO_DIR['RESIDENCE'];		//address_type
			
			/*
			 *  Se debe validar si se quiere guardar la direccion antes de registrarla
			 * esto con: $form_values['guardar']
			 * si no se quiere guardar se continua con el proceso
			 * */
			if (empty($this->reg_errores)) {	
				//si no hay errores y se solicita registrar la direccion
				if (isset($form_values['guardar']) || isset($form_values['direccion']['id_estatusSi'])) {
					//verificar que no exista la direccion activa en la BD
					//var_dump($form_values);
					//exit();
						$form_values['direccion']['id_TCSi'] = $consecutivo + 1;
						$form_values['direccion']['id_consecitivoSi'] = 1;
						$form_values['direccion']['descripcionVc'] = "AMERICAN EXPRESS";
					//echo var_dump($form_values);
					//exit();
					
					if($this->modelo->existe_direccion($form_values['direccion'])) {	//Redirect al listado por que ya existe
						//$url = $this->config->item('base_url').'/index.php/direccion_envio/listar/'.$id_cliente;
						//header("Location: $url");
						$this->listar($id_cliente, "La direcci&oacute;n ya está registrada.");
						//echo "La direcci&oacute;n ya está registrada.";
						//exit();
					} else {
						//Registrar Localmente
						if ($this->modelo->insertar_direccion($form_values['direccion'])) {
							$this->listar("Direcci&oacute;n registrada.");
						} else {
							$this->listar("Hubo un error en el registro en CMS.");
							echo "<br/>Hubo un error en el registro en CMS";
						}
					}						
				} else {
					//si no se guardará la tc, almacenar la info para la venta
					$url = base_url().'/index.php/direccion_facturacion/';		//la sesion debe tomar el cliente
					header("Location: $url");
					exit();	  
					echo "No se almacenar&aacute; la direcci&oacute;n>> Pasar a captura de dir. de facturación<br/> Coming soon...";
					//exit();	
				}
				/*
				//De momento se regresará al listado de direccions
				if ($this->modelo->insertar_direccion($form_values['tc'])) {
					$url = $this->config->item('base_url').'/index.php/direccion_envio/listar/'.$id_cliente;
					header("Location: $url");
					exit();	
				}
				*/
			} else {	//Si hubo errores
				//vuelve a mostrar la info.
				$data['reg_errores'] = $this->reg_errores;
				$this->cargar_vista('', 'direccion_envio' , $data);	
			}
		} else {
			$this->cargar_vista('', 'direccion_envio' , $data);
		}
	}

	public function editar($consecutivo)	//el consecutivo de la direccion
	{
		$id_cliente = $this->id_cliente;
		
		$data['title'] = $this->title;
		$data['subtitle'] = ucfirst('Editar Direcci&oacute;n');
		
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
					$data['msg_actualizacion'] = 
						$this->modelo->actualiza_tarjeta($consecutivo, $id_cliente, $nueva_info['tc']);
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
		
		$this->cargar_vista('', 'direccion_envio' , $data);
	}
	
	public function eliminar($consecutivo = '')
	{
		$id_cliente = $this->id_cliente;
		$data['title'] = $this->title;
		$data['subtitle'] = ucfirst('Eliminar Direcci&oacute;n');
		
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
		$this->listar($id_cliente, $msg_eliminacion);
		//$this->cargar_vista('', 'direccion_envio' , $data);
	}
	
	
	/**
	 * Regresa el listado de estados para poblar el select correspondiente
	 * en formato JSON
	 */
	public function get_estados()
	{
		echo json_encode($this->consulta_estados());
	}
	
	/*
	 * Regresa un array con los resultados
	 */
	private function consulta_estados()
	{
		$resultado = array();
		
		try {
			$cliente = new SoapClient("https://cctc.gee.com.mx/ServicioWebCCTC/ws_cms_cctc.asmx?WSDL");
		
			$parameter = array(	);	
			
			$obj_result = $cliente->ObtenerEstado($parameter);
			$simple_result = $obj_result->ObtenerEstadoResult;		
			
			$resultado['estados'] = $simple_result->InformacionEstado;
			
			
			//echo var_dump($simple_result) . "<br/>";
			foreach($resultado['estados'] as $estado) {
				//echo $estado->clave_estado." -> " . $estado->estado . "<br/>";
			}
						
			$resultado['success'] = true;
			$resultado['msg'] = "Ok";
			
			return $resultado;
			
		} catch (Exception $e)	{
			$resultado['exception'] =  $exception;
			$resultado['msg'] = $exception->getMessage();
			$resultado['error'] = true;
			//echo "No se pudo recuperar el catálogo de SEPOMEX.<br/>";
			//echo $e->getMessage();
			//exit();
			return $resultado;	
		}
		
	}
	
	public function get_info_sepomex($cp = 0)
	{
		//echo "cp: ". $cp;
		
		/*if (array_key_exists('codigo_postal', $_POST) && isset($_POST['codigo_postal']))
			$cp = $_POST['codigo_postal'];*/
		
		$cp = $this->input->post('codigo_postal');
		
		//echo "<br/>Es llamada ajax?: ". $this->input->is_ajax_request() . "<br/>";
		//echo "<script>alert('Peticion Ajax'); </script>";
		echo json_encode($this->consulta_sepomex($cp));
	}
	
	/*
	 * Regresa un array con los resultados
	 */
	private function consulta_sepomex($codigo_postal)
	{
		$resultado = array();
		
		try {
			$cliente = new SoapClient("https://cctc.gee.com.mx/ServicioWebCCTC/ws_cms_cctc.asmx?WSDL");
		
			$parameter = array( "codigo_postal" => $codigo_postal );	
			
			$obj_result = $cliente->ObtenerEstadoCiudad($parameter);
			//por si no regresa ningún resultado
			$simple_result = isset($obj_result->ObtenerEstadoCiudadResult) ?
				 $obj_result->ObtenerEstadoCiudadResult : null;
			
			//var_dump($obj_result);
			$resultado['sepomex'] = $simple_result;
			
			$resultado['success'] = true;
			$resultado['msg'] = "Sepomex Ok";
			
			
			/*if ($this->input->is_ajax_request())
				$resultado['esAjax'] = "  Peticion Ajax";*/
			
			/*if (isset($_POST['codigo_postal']))
				$cp = $_POST['codigo_postal'];
			$resultado['cp'] = $cp;*/
			
			return $resultado;
			
		} catch (Exception $e)	{
			$resultado['exception'] =  $exception;
			$resultado['msg'] = $exception->getMessage();
			$resultado['error'] = true;
			//echo "No se pudo recuperar el catálogo de SEPOMEX.<br/>";
			//echo $e->getMessage();
			//exit();
			return $resultado;	
		}
	}
	
	/**
	 * Regresa el listado de estados para poblar el select correspondiente
	 * en formato JSON
	 */
	public function get_ciudades($estado = "")
	{
		$estado = $this->input->post('estado');	// ? $this->input->post('estado') : "" ;
		//echo "edo: ".$edo;
		echo json_encode($this->consulta_ciudades($estado));
	}
	
	private function consulta_ciudades($estado)
	{
		$resultado = array();	
		try {
			//URL del WS debe estar en archivo protegido
			$cliente = new SoapClient("https://cctc.gee.com.mx/ServicioWebCCTC/ws_cms_cctc.asmx?WSDL");	
			$parameter = array(	'estado' => $estado);
			
			$obj_result = $cliente->ObtenerCiudad($parameter);			
			$simple_result = $obj_result->ObtenerCiudadResult;
			
			
			if (isset($simple_result->InformacionCiudad)) {	//por si no regresa ningún resultado
				$resultado['ciudades'] = $simple_result->InformacionCiudad;	//es un array de objects	
			} else {
				$resultado['ciudades'] = NULL;
			}
			
			$resultado['success'] = true;
			$resultado['msg'] = "Ciudades Resultados";
			//var_dump($resultado);
			
			return $resultado;
			
		} catch (Exception $e) {
			$resultado['exception'] =  $exception;
			$resultado['msg'] = $exception->getMessage();
			$resultado['error'] = true;
			//echo "No se pudo recuperar el catálogo de SEPOMEX.<br/>";
			//echo $e->getMessage();
			//exit();
			return $resultado;
		}
	}
	
	/*
	 * Regresa la lista de colonias correspondientes
	 * params:
	 * $estado:string = clave del estado en cuestión
	 * $estado:string = clave del estado en cuestión
	 * 
	 * return:
	 * $resuktado:json object = listado de colonias en formato JSON
	 * */
	
	public function get_colonias($estado = "", $ciudad = "")
	{
		$estado = $this->input->post('estado');	// ? $this->input->post('estado') : "" ;
		$ciudad = $this->input->post('ciudad');
		//echo "edo: ".$edo;
		echo json_encode($this->consulta_colonias($estado, $ciudad));
	}
	
	private function consulta_colonias($estado, $ciudad)
	{
		$resultado = array();
		try {
			//URL del WS debe estar en archivo protegido
			$cliente = new SoapClient("https://cctc.gee.com.mx/ServicioWebCCTC/ws_cms_cctc.asmx?WSDL");	
			$parameter = array(	'estado' => $estado, 'ciudad' => $ciudad);
			
			$obj_result = $cliente->ObtenerColonia($parameter);
			$simple_result = $obj_result->ObtenerColoniaResult;
			
			
			if (isset($simple_result->InformacionColonia)) {	//por si no regresa ningún resultado
				$resultado['colonias'] = $simple_result->InformacionColonia;	//es un array de objects	
			} else {
				$resultado['colonias'] = NULL;
			}
			
			$resultado['success'] = true;
			$resultado['msg'] = "Colonias Resultados";
			//var_dump($resultado);
			
			return $resultado;
			
		} catch (Exception $e) {
			$resultado['exception'] =  $exception;
			$resultado['msg'] = $exception->getMessage();
			$resultado['error'] = true;
			//echo "No se pudo recuperar el catálogo de SEPOMEX.<br/>";
			//echo $e->getMessage();
			//exit();
			return $resultado;
		}
	}
	
	private function get_datos_direccion()
	{
		$datos = array();
		//no se usa la funcion de escape '$this->db->escape()', por que en la inserción ya se incluye 
		if($_POST) {
			
			if(array_key_exists('txt_numeroTarjeta', $_POST)) { 
				//al final sólo será la terminación de la tarjeta, pero se deben validar los 16 digitos
				if(preg_match('/^[0-9]{16,17}$/', $_POST['txt_numeroTarjeta'])) {
					$datos['tc']['terminacion_tarjetaVc'] = 
					substr($_POST['txt_numeroTarjeta'], strlen($_POST['txt_numeroTarjeta']) - 4);
				} else {
					$this->reg_errores['txt_numeroTarjeta'] = 'Por favor ingrese un numero de tarjeta v&aacute;lido';
				}				
			}
			if(array_key_exists('txt_nombre', $_POST)) {
				if(preg_match('/^[A-Z \'.-áéíóúÁÉÍÓÚÑñ]{2,30}$/i', $_POST['txt_nombre'])) { 
					$datos['tc']['nombre_titularVc'] = $_POST['txt_nombre'];
					if ($tipo = 'amex') {
						$datos['amex']['nombre_titularVc'] = $_POST['txt_nombre'];
					}
				} else {
					$this->reg_errores['txt_nombre'] = 'Ingresa tu nombre correctamente';
				}
			}
			if(array_key_exists('txt_apellidoPaterno', $_POST)) {
				if(preg_match('/^[A-Z \'.-áéíóúÁÉÍÓÚÑñ]{2,30}$/i', $_POST['txt_apellidoPaterno'])) { 
					$datos['tc']['apellidoP_titularVc'] = $_POST['txt_apellidoPaterno'];
					if ($tipo = 'amex') {
						$datos['amex']['apellidoP_titularVc'] = $_POST['txt_apellidoPaterno'];
					}
				} else {
					$this->reg_errores['txt_apellidoPaterno'] = 'Ingresa tu apellido correctamente';
				}
			}
			if(array_key_exists('txt_apellidoMaterno', $_POST)) {
				if(preg_match('/^[A-Z \'.-áéíóúÁÉÍÓÚÑñ]{2,30}$/i', $_POST['txt_apellidoMaterno'])) {
					$datos['tc']['apellidoM_titularVc'] = $_POST['txt_apellidoMaterno'];
					if ($tipo = 'amex') {
						$datos['amex']['apellidoM_titularVc'] = $_POST['txt_apellidoMaterno'];
					}
				} else {
					$this->reg_errores['txt_apellidoMaterno'] = 'Ingresa tu apellido correctamente';
				}
			}
			if(array_key_exists('txt_codigoSeguridad', $_POST)) {
				//este código sólo se almaccena para solicitar el pago 
				$datos['codigo_seguridad'] = $_POST['txt_codigoSeguridad']; 
			}
			if(array_key_exists('sel_mes_expira', $_POST)) {
				$datos['tc']['mes_expiracionVc'] = $_POST['sel_mes_expira']; 
			}
			if(array_key_exists('sel_anio_expira', $_POST)) { 
				$datos['tc']['anio_expiracionVc'] = $_POST['sel_anio_expira'];  
			}
			
			
			//AMEX
			if(array_key_exists('txt_calle', $_POST)) {
				if(preg_match('/^[A-Z0-9 \'.-áéíóúÁÉÍÓÚÑñ]{2,40}$/i', $_POST['txt_calle'])) {
					$datos['amex']['calle'] = $_POST['txt_calle'];
				} else {
					$this->reg_errores['txt_calle'] = 'Ingresa tu calle y n&uacute;mero correctamente';
				}
			}
			if(array_key_exists('txt_cp', $_POST)) {
				//regex usada en js
				if(preg_match('/^([1-9]{2}|[0-9][1-9]|[1-9][0-9])[0-9]{3}$/', $_POST['txt_cp'])) {
					$datos['amex']['codigo_postal'] = $_POST['txt_cp'];
				} else {
					$this->reg_errores['txt_cp'] = 'Ingresa tu c&oacute;digo postal correctamente';
				}
			}
			if(array_key_exists('txt_ciudad', $_POST)) {
				if(preg_match('/^[A-Z0-9 \'.-áéíóúÁÉÍÓÚÑñ]{2,40}$/i', $_POST['txt_ciudad'])) {
					$datos['amex']['ciudad'] = $_POST['txt_ciudad'];
				} else {
					$this->reg_errores['txt_ciudad'] = 'Ingresa tu ciudad correctamente';
				}
			}
			if(array_key_exists('txt_estado', $_POST)) {
				if(preg_match('/^[A-Z \'.-áéíóúÁÉÍÓÚÑñ]{2,40}$/i', $_POST['txt_estado'])) {
					$datos['amex']['estado'] = $_POST['txt_estado'];
				} else {
					$this->reg_errores['txt_estado'] = 'Ingresa tu estado correctamente';
				}
			}
			
			if(array_key_exists('sel_pais', $_POST)) {
				if(preg_match('/^[A-Z]{2}$/i', $_POST['sel_pais'])) {
					$datos['direccion']['codigo_paisVc'] = $_POST['sel_pais'];
				} else {
					$this->reg_errores['sel_pais'] = 'Selecciona tu pa&iacute;s';
				}
			}
			if(array_key_exists('sel_estados', $_POST)) {
				if(preg_match('/^[A-Z ]{2, 30}$/i', $_POST['sel_estados'])) {
					$datos['direccion']['state'] = $_POST['sel_estado'];
				} else {
					$this->reg_errores['sel_estado'] = 'Selecciona tu estado';
				}
			}
			
			
			if(array_key_exists('txt_telefono', $_POST)) {
				if(preg_match('/^[0-9 -]{2,15}$/i', $_POST['txt_telefono'])) {
					$datos['direccion']['telefono'] = $_POST['txt_telefono'];
				} else {
					$this->reg_errores['txt_telefono'] = 'Ingresa tu tel&eacute;fono correctamente';
				}
			}
			if (filter_var($_POST['txt_email'], FILTER_VALIDATE_EMAIL)) {
				$datos['direccion']['txt_email'] = htmlspecialchars(trim($_POST['txt_email']));
			} else {
				$this->registro_errores['txt_email'] = 'Ingresa una direcci&oacute;n v&aacute;lida.';
			}
			
			if(array_key_exists('chk_guardar', $_POST)) {
				$datos['guardar'] = $_POST['chk_guardar'];		//indicador para saber si se guarda o no la tarjeta
				$datos['direccion']['id_estatusSi'] = 1;
			}
			if(array_key_exists('chk_default', $_POST)) {
				$datos['direccion']['id_estatusSi'] = 0;	//indica que será la tarjeta predeterminada	
				//$_POST['chk_default'];
				//en la edicion, si no se cambia, que se quede como está, activa!! VERIFICARLO on CCTC
			}
		} 
		
		//var_dump($datos);
		//echo 'si no hay errores, $reg_errores esta vacio? '.empty($this->reg_errores).'<br/>';
		return $datos;
	}

	private function cargar_vista($folder, $page, $data)
	{	
		//Para automatizar un poco el desplieguee
		$this->load->view('templates/header', $data);
		$this->load->view($folder.'/'.$page, $data);
		$this->load->view('templates/footer', $data);
	}
	
	private function redirect_cliente_invalido($revisar = 'id_cliente', $destino = '/index.php/login', $protocolo = 'http://') 
	{
		if (!$this->session->userdata($revisar)) {
			//$url = $protocolo . BASE_URL . $destination; // Define the URL.
			$url = $this->config->item('base_url') . $destino; // Define the URL.
			header("Location: $url");
			exit(); // Quit the script.
		}
	}

}

/* End of file direccion_envio.php */
/* Location: ./application/controllers/direccion_envio.php */