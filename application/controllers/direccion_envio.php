<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Direccion_Envio extends CI_Controller {
	var $title = 'Direcci&oacute;n de Env&iacute;o'; 		// Capitalize the first letter
	var $subtitle = 'Direcci&oacute;n de Env&iacute;o'; 	// Capitalize the first letter
	var $reg_errores = array();		//validación para los errores
	const CODIGO_MEXICO = "MX";		//constante para verificar el código del país en el efecto del JS.
	
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

	public function index()
	{
		//Recuperar el "id_ClienteNu" de la sesion
		
		//$id_cliente = $this->session->userdata('id_cliente');
		
		//echo 'cliente_Id: '.$id_cliente;
		
		$this->listar();
	}
	
	public function listar($msg = '') 
	{		
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
		$id_cliente = $this->id_cliente;
		/*agregar el script para este formulario*/
		$script_file = "<script type='text/javascript' src='". base_url() ."js/dir_envio.js'></script>";
		$data['script'] = $script_file;
		
		$data['title'] = $this->title;
		$data['subtitle'] = ucfirst('Nueva Direcci&oacute;n');
		
		//catálogo de paises de think
		$lista_paises_think = $this->modelo->listar_paises_think();
		$data['lista_paises_think'] = $lista_paises_think;
				
		//recuperar el listado de las direcciones del cliente
		$data['lista_direcciones'] = $this->modelo->listar_direcciones($id_cliente);
		
		//catalogo de estados
		$lista_estados = $this->consulta_estados();		
		$data['lista_estados_sepomex'] = $lista_estados['estados'];
		
		$data['registrar'] = TRUE;		//se debe mostrar formulario de registro
				
		if ($_POST)	{	
			//Petición de registro
			$consecutivo = $this->modelo->get_consecutivo($id_cliente);			
			
			$form_values = array();		//alojará los datos ingresados previos a la inserción	
			$form_values = $this->get_datos_direccion();			
			
			$form_values['direccion']['id_clienteIn'] = $id_cliente;
			$form_values['direccion']['id_consecutivoSi'] = $consecutivo + 1;		//cambió
			$form_values['direccion']['address_type'] = self::$TIPO_DIR['RESIDENCE'];		//address_type
						
			if (empty($this->reg_errores)) {	
				//si no hay errores en el formulario y se solicita registrar la direccion
				if (isset($form_values['guardar']) || isset($form_values['direccion']['id_estatusSi'])) {
					//verificar que no exista la direccion activa en la BD
					if($this->modelo->existe_direccion($form_values['direccion'])) {	
						//Redirect al listado por que ya existe
						//$url = $this->config->item('base_url').'/index.php/direccion_envio/listar/'.$id_cliente;
						//header("Location: $url");
						$this->listar("La direcci&oacute;n ya est&aacute; registrada.");
						//echo "La direcci&oacute;n ya está registrada.";
						//exit();
					} else {
						//Registrar Localmente
						
						
						if ($this->modelo->insertar_direccion($form_values['direccion'])) {
							$this->listar("Direcci&oacute;n registrada.");
						} else {
							$this->listar("Hubo un error en el registro en CMS.");
							//echo "<br/>Hubo un error en el registro en CMS";
						}
					}						
				} else {
					//si no se guardará la tc, almacenar la info para la venta
					$url = base_url().'/index.php/direccion_facturacion/';		//la sesion debe tomar el cliente
					header("Location: $url");
					exit();	  
					echo "No se almacenar&aacute; la direcci&oacute;n>> Pasar a captura de dir. de facturación<br/> Coming soon...";
				}
			} else {	//Si hubo errores en la captura
				//carga de catálogos de sepomex si ya se hizo la seleccion de estado, ciudad, colonia
				if (!empty($_POST['sel_ciudades']))
				{
					//catálogo de ciudades
					$lista_ciudades = $this->consulta_ciudades($_POST['sel_estados']);		
					$data['lista_ciudades_sepomex'] = $lista_ciudades['ciudades'];
				}
				
				if (!empty($_POST['sel_estados']) && !empty($_POST['sel_ciudades']))
				{
					//catálogo de colonias
					$lista_colonias = $this->consulta_colonias($_POST['sel_estados'], $_POST['sel_ciudades']);		
					$data['lista_colonias_sepomex'] = $lista_colonias['colonias'];
				}
				
				//vuelve a mostrar la información en el formulario 
				$data['reg_errores'] = $this->reg_errores;
				$this->cargar_vista('', 'direccion_envio' , $data);	
			}
		} else {
			//muestra la lista de direcciones sólamente
			$this->cargar_vista('', 'direccion_envio' , $data);
		}
	}

	/**
	 * Edición de la dirección seleccionada
	 */
	public function editar($consecutivo)	//el consecutivo de la direccion
	{
		$id_cliente = $this->id_cliente;
		//inclusión de Scripts
		$script_file = "<script type='text/javascript' src='". base_url() ."js/dir_envio.js'></script>";
		$data['script'] = $script_file;
		
		$data['title'] = $this->title;
		$data['subtitle'] =  $this->subtitle;
		//$data['subtitle'] = ucfirst('Editar Direcci&oacute;n');
		
		//recuperar la información de la dirección
		$detalle_direccion = $this->modelo->detalle_direccion($consecutivo, $id_cliente);
		$data['direccion'] = $detalle_direccion;
		
		//catálogo de paises de think
		$lista_paises_think = $this->modelo->listar_paises_think();
		$data['lista_paises_think'] = $lista_paises_think;
		
		/*muestra lo de sepomex*/
		//catalogo de estados
		$lista_estados = $this->consulta_estados();		
		$data['lista_estados_sepomex'] = $lista_estados['estados'];
		//ciudades		
		$lista_ciudades = $this->consulta_ciudades($detalle_direccion->state);		
		$data['lista_ciudades_sepomex'] = $lista_ciudades['ciudades'];
		//colonias
		$lista_colonias = $this->consulta_colonias($detalle_direccion->state, $detalle_direccion->city);		
		$data['lista_colonias_sepomex'] = $lista_colonias['colonias'];
		
		//Se intentará actualizar la información
		if ($_POST) {			
			//array para la nueva información
			$nueva_info = array();
			//trae datos del formulario para actualizar
			$nueva_info = $this->get_datos_direccion();
			
			if (empty($this->reg_errores)) {	//si no hubo errores
			
				$nueva_info['direccion']['id_clienteIn'] = $id_cliente;
				$nueva_info['direccion']['id_consecutivoSi'] = $consecutivo;	//$this
				
				//var_dump($nueva_info);
				//exit();
				
				$data['msg_actualizacion'] = 
					$this->modelo->actualiza_direccion($consecutivo, $id_cliente, $nueva_info['direccion']);
				//Actualiza y muestra de nuevo el formulario y el mensaje con el resultado de la actualización
			} else {	//sí hubo errores
				$data['msg_actualizacion'] = "Campos incorrectos!!";
				$data['reg_errores'] = $this->reg_errores;
			}	//ERRORES FORMULARIO
		}//If POST
		
		$this->cargar_vista('', 'direccion_envio' , $data);
	}
	
	/**
	 * Eliminación lógica de la dirección en la BD
	 */
	public function eliminar($consecutivo=0)
	{
		$id_cliente = $this->id_cliente;
		$data['title'] = $this->title;
		$data['subtitle'] = ucfirst('Eliminar Direcci&oacute;n');
		
		$msg_eliminacion =
			$this->modelo->eliminar_direccion($id_cliente, $consecutivo);
	
		/*Pendiente el Redirect hacia la dirección de Facturación*/
		//echo $data['msg_eliminacion´];
		
		//cargar la lista de direeciones
		$this->listar($msg_eliminacion);
	}
	
	/**
	 * Verifica si el código de país corresponde con el de México o no
	 */
	public function es_mexico($codigo_pais="") {
		//$codigo_pais = ['codigo_pais'];
		$r = ($codigo_pais == self::CODIGO_MEXICO) ? TRUE : FALSE;
		$es_mexico = array('result' => $r, 'param' => $codigo_pais);
		
		echo json_encode($es_mexico);
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
	
	/**
	 * Recoge los valores del formulario de edición
	 * 
	 */
	private function get_datos_direccion()
	{
		$datos = array();
		//no se usa la funcion de escape '$this->db->escape()', por que en la inserción ya se incluye 
		if($_POST) {
			//AMEX
			if (array_key_exists('txt_calle', $_POST)) {
				if(preg_match('/^[A-Z0-9 \'.-áéíóúÁÉÍÓÚÑñ]{1,50}$/i', $_POST['txt_calle'])) {
					$datos['direccion']['address1'] = $_POST['txt_calle'];
				} else {
					$this->reg_errores['txt_calle'] = 'Ingresa tu calle y n&uacute;mero correctamente';
				}
			}
			if (array_key_exists('txt_numero', $_POST)) {
				if(preg_match('/^[A-Z0-9 -]{1,50}$/i', $_POST['txt_numero'])) {
					$datos['direccion']['address2'] = $_POST['txt_numero'];
				} else {
					$this->reg_errores['txt_numero'] = 'Ingresa tu n&uacute;mero correctamente';
				}
			}
			if (!empty($_POST['txt_num_int'])) {
				if(preg_match('/^[A-Z0-9 -]{1,50}$/i', $_POST['txt_num_int'])) {
					$datos['direccion']['address4'] = $_POST['txt_num_int'];
				} else {
					$this->reg_errores['txt_numero'] = 'Ingresa tu n&uacute;mero correctamente';
				}
			} else {
				$datos['direccion']['address4'] = NULL;
			}
			if (array_key_exists('txt_cp', $_POST)) {
				//regex usada en js
				if(preg_match('/^([1-9]{2}|[0-9][1-9]|[1-9][0-9])[0-9]{3}$/', $_POST['txt_cp'])) {
					$datos['direccion']['zip'] = $_POST['txt_cp'];
				} else {
					$this->reg_errores['txt_cp'] = 'Ingresa tu c&oacute;digo postal correctamente';
				}
			}
						
			if (!empty($_POST['sel_pais'])) {
			//if(preg_match('/^[A-Z]{2}$/i', $_POST['sel_pais'])) {
				$datos['direccion']['codigo_paisVc'] = $_POST['sel_pais'];
			} else {
				$this->reg_errores['sel_pais'] = 'Selecciona tu pa&iacute;s';
			}
			
			if (!empty($_POST['sel_estados'])) {
			//if(preg_match('/^[A-Z  \'.-áéíóúÁÉÍÓÚÑñ]{2, 30}$/i', $_POST['sel_estados'])) {
				$datos['direccion']['state'] = $_POST['sel_estados'];
			} else {
				$this->reg_errores['sel_estados'] = 'Selecciona tu estado';
			}
			if (!empty($_POST['sel_ciudades'])) {
			//if(preg_match('/^[A-Z ()\'.-áéíóúÁÉÍÓÚÑñ]{2, 30}$/i', $_POST['sel_ciudades'])) {
				$datos['direccion']['city'] = $_POST['sel_ciudades'];
			} else {
				$this->reg_errores['sel_ciudades'] = 'Selecciona tu ciudad';
			}
			
			if (!empty($_POST['sel_colonias'])) {
			//if(preg_match('/^[A-Z0-9  \'.-áéíóúÁÉÍÓÚÑñ]{2, 30}$/i', $_POST['sel_colonias'])) {
				$datos['direccion']['address3'] = $_POST['sel_colonias'];
			} else {
				$this->reg_errores['sel_colonias'] = 'Selecciona tu colonia';
			
			}
			
			if (array_key_exists('txt_telefono', $_POST)) {
				if(preg_match('/^[0-9 -]{2,15}$/i', $_POST['txt_telefono'])) {
					$datos['direccion']['phone'] = $_POST['txt_telefono'];
				} else {
					$this->reg_errores['txt_telefono'] = 'Ingresa tu tel&eacute;fono correctamente';
				}
			}
			
			if(array_key_exists('txt_referencia', $_POST)) {
				$datos['direccion']['referenciaVc'] = trim($_POST['txt_referencia']);
			}
			
			
			
			if (filter_var($_POST['txt_email'], FILTER_VALIDATE_EMAIL)) {
				$datos['direccion']['email'] = htmlspecialchars(trim($_POST['txt_email']));
			} else {
				$this->reg_errores['txt_email'] = 'Ingresa una direcci&oacute;n v&aacute;lida.';
			}
			
			if (array_key_exists('chk_guardar', $_POST)) {
				$datos['guardar'] = $_POST['chk_guardar'];		//indicador para saber si se guarda o no la tarjeta
				$datos['direccion']['id_estatusSi'] = 1;
			}
			if (array_key_exists('chk_default', $_POST)) {
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