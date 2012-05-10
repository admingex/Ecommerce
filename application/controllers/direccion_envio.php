<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Direccion_Envio extends CI_Controller {
	var $title = 'Direcci&oacute;n de Env&iacute;o'; 		// Capitalize the first letter
	var $subtitle = 'Selecciona una direcci&oacute;n de env&iacute;o'; 	// Capitalize the first letter
	var $reg_errores = array();		//validación para los errores
	
	private $id_cliente;
	
	const CODIGO_MEXICO = "MX";		//constante para verificar el código del país en el efecto del JS.
	
	public static $TIPO_DIR = array(
		"RESIDENCE"	=> 0, 
		"BUSINESS"	=> 1, 
		"OTHER"		=> 2
	);
	
	//protected $lista_bancos = array();
	 
	function __construct()
    {
        // Call the Model constructor
        parent::__construct();
		
		//si no hay sesión
		//manda al usuario a la... página de login
		$this->redirect_cliente_invalido('id_cliente', '/index.php/login');
		
		//cargar el modelo en el constructor
		$this->load->model('direccion_envio_model', 'direccion_envio_model', true);
		//la sesion se carga automáticamente
		
		//toma el valor del id cliente de la sesión creada en el login/registro
		$this->id_cliente = $this->session->userdata('id_cliente');
		
		//echo "requiere_envio: " . $this->session->userdata('requiere_envio');
    }

	/**
	 * Se encarga de listar las direcciones de envío
	 */
	public function index()
	{
		$this->listar();
	}
	
	/**
	 * Coloca la dirección seleccionada del listado en session
	 */
	public function seleccionar() {
		if ($_POST) {
			if (array_key_exists('direccion_selecionada', $_POST))
				$this->session->set_userdata('dir_envio', $_POST['direccion_selecionada']);
			
			//Para calcular destino siguiente y actualizxarlo en sesión
			$destino = $this->obtener_destino();
			
			redirect($destino, "refresh");
		}
		else {
			//ir al listado
			redirect("forma_pago/listar", "refresh");
		}		
	}
	
	/**
	 * Lista las direcciones registradas, si hay un mensaje, lo despliega y 
	 * si debe haber redirección la aplica. 
	 */
	public function listar($msg = '', $redirect = TRUE) 
	{		
		/*
		echo 'cliente: '.$this->session->userdata('id_cliente').'<br/>';
		echo 'Session: '.$this->session->userdata('session_id').'<br/>';
		echo 'last_Activity: '.$this->session->userdata('last_activity').'<br/>';
		$ano = date('m.d')	;
		echo "date: ". $ano;	//mes año
		*/
		
		$data['title'] = $this->title;
		$data['subtitle'] = $this->subtitle;		
		$data['mensaje'] = $msg;
		$data['redirect'] = $redirect;
		
		if ($this->input->is_ajax_request()) {
			$direcciones = $this->direccion_envio_model->listar_direcciones($this->id_cliente);
			
			header('Content-Type: application/json',true);
			echo json_encode($direcciones->result());
		} else {
			//listar por default las direcciones del cliente
			$data['lista_direcciones'] = $this->direccion_envio_model->listar_direcciones($this->id_cliente);
			//cargar vista	
			$this->cargar_vista('', 'direccion_envio', $data);
		}
	}
	
	public function registrar() 
	{	
		$id_cliente = $this->id_cliente;
		/*agregar el script para este formulario*/
		$script_file = "<script type='text/javascript' src='". base_url() ."js/dir_envio.js'></script>";
		$data['script'] = $script_file;
		
		$data['title'] = $this->title;
		$data['subtitle'] = $this->subtitle;
		
		//catálogo de paises de think
		$lista_paises_think = $this->direccion_envio_model->listar_paises_think();
		$data['lista_paises_think'] = $lista_paises_think;
				
		//recuperar el listado de las direcciones del cliente
		$data['lista_direcciones'] = $this->direccion_envio_model->listar_direcciones($id_cliente);
		
		//catálogo de estados
		$lista_estados = $this->consulta_estados();
		$data['lista_estados_sepomex'] = $lista_estados['estados'];
		
		$data['registrar'] = TRUE;		//se debe mostrar formulario de registro
				
		if ($_POST)	{	
			//Petición de registro
			$consecutivo = $this->direccion_envio_model->get_consecutivo($id_cliente);			
			
			$form_values = array();		//alojará los datos ingresados previos a la inserción	
			$form_values = $this->get_datos_direccion();			
			
			$form_values['direccion']['id_clienteIn'] = $id_cliente;
			$form_values['direccion']['id_consecutivoSi'] = $consecutivo + 1;		//cambió
			$form_values['direccion']['address_type'] = self::$TIPO_DIR['RESIDENCE'];		//address_type
						
			if (empty($this->reg_errores)) {
				//si no hay errores en el formulario y se solicita registrar la direccion
				if (isset($form_values['guardar']) || isset($form_values['direccion']['id_estatusSi'])) {
					//verificar que no exista la direccion activa en la BD
					if($this->direccion_envio_model->existe_direccion($form_values['direccion'])) {	
						//Redirect al listado por que ya existe
						$this->listar("La direcci&oacute;n ya est&aacute; registrada.", FALSE);
						//echo "La direcci&oacute;n ya está registrada.";
					} else {
						//sólo la primera que se registra se predetermina
						if (isset($form_values['predeterminar']) || $consecutivo == 0) {
							$this->direccion_envio_model->quitar_predeterminado($id_cliente);
							$form_values['direccion']['id_estatusSi'] = 3;
						}
						
						//Registrar en BD
						if ($this->direccion_envio_model->insertar_direccion($form_values['direccion'])) {
							//cargar en sesion
							$this->cargar_en_session($form_values['direccion']['id_consecutivoSi']);
							
							//Para calcular destino siguiente y actualizxarlo en sesión
							$destino = $this->obtener_destino();
							
							//cargar la vista de las tarjetas
							$this->listar("Direcci&oacute;n registrada correctamente.");
						} else {
							$this->listar("Hubo un error en el registro en CMS.", FALSE);
							//echo "<br/>Hubo un error en el registro en CMS";
						}
					}						
				} else {
					//si no se guardará la dirección, almacenar la info para el cobro en sesión temporalmente y pasar a direccón de facturación
					$direccion = $form_values['direccion'];
					$this->cargar_en_session($direccion);
					
					//Para calcular destino siguiente y actualizxarlo en sesión
					$destino = $this->obtener_destino();
					
					$this->listar("Dirección capturada correctamente");
					//redirect('direccion_facturacion');
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
	public function editar($consecutivo = 0)	//el consecutivo de la direccion
	{
		$id_cliente = $this->id_cliente;
		//inclusión de Scripts
		$script_file = "<script type='text/javascript' src='". base_url() ."js/dir_envio.js'></script>";
		$data['script'] = $script_file;
		
		$data['title'] = $this->title;
		$data['subtitle'] = "Edita los campos que quieras modificar";
		
		//recuperar la información de la dirección
		if (!$consecutivo && $this->session->userdata("dir_envio")) {
			$envio_en_sesion = $this->session->userdata("dir_envio");
			
			$dir_envio = null;
			//var_dump($envio_en_sesion);
			//exit();
			/*crear los objetos para la edición tc*/
			foreach ($envio_en_sesion as $key => $value) {
				$dir_envio->$key = $value;
			}
			//var_dump($dir_envio);
			//exit();
			$dir_envio->id_consecutivoSi = 0;	//el id_consecutivoSi (debe ser 0)
			$detalle_direccion = $dir_envio;
				
		} else {
			$detalle_direccion = $this->direccion_envio_model->detalle_direccion($consecutivo, $id_cliente);
		}
		
		$data['direccion'] = $detalle_direccion;
		
		//var_dump($detalle_direccion);
		//exit();
		
		//catálogo de paises de think
		$lista_paises_think = $this->direccion_envio_model->listar_paises_think();
		$data['lista_paises_think'] = $lista_paises_think;
		
		/*muestra lo de sepomex*/
		//catálogo de estados
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
				$nueva_info['direccion']['id_consecutivoSi'] = $consecutivo;
			
				if (isset($nueva_info['predeterminar'])) {
					$this->direccion_envio_model->quitar_predeterminado($id_cliente);
				} else {	//si no es predeterminado se quda sólo como "activa"habilitado"
					$nueva_info['direccion']['id_estatusSi'] = 1;
				}
				
				if (!$consecutivo) {
					$direccion = $nueva_info['direccion'];
					$this->cargar_en_session($direccion);
					
					//Para calcular destino siguiente y actualizxarlo en sesión
					$destino = $this->obtener_destino();
					
					$msg_actualizacion = "Información actualizada";
					$data['msg_actualizacion'] = $msg_actualizacion;
					//var_dump($direccion);
					//exit();
					$this->listar($msg_actualizacion);
				} else {
				
					$msg_actualizacion = 
						$this->direccion_envio_model->actualiza_direccion($consecutivo, $id_cliente, $nueva_info['direccion']);
					
					$data['msg_actualizacion'] = $msg_actualizacion;
					
					//Cargar en sesión la dirección mmodificada
					$this->cargar_en_session($consecutivo);
					
					//Para calcular destino siguiente y actualizxarlo en sesión
					$destino = $this->obtener_destino();
					
					$this->listar($msg_actualizacion);
				}
				//redirect("direccion_facturacion");
				//exit();
			} else {	//ERRORES FORMULARIO
				$data['msg_actualizacion'] = "Campos incorrectos!!";
				$data['reg_errores'] = $this->reg_errores;
				$this->cargar_vista('', 'direccion_envio' , $data);
			}	//ERRORES FORMULARIO
		} else {	//If POST
			$this->cargar_vista('', 'direccion_envio' , $data);
		}
	}
	
	/**
	 * Eliminación lógica de la dirección en la BD
	 */
	public function eliminar($consecutivo = 0)
	{
		$id_cliente = $this->id_cliente;
		$data['title'] = $this->title;
		$data['subtitle'] = 'Eliminar Direcci&oacute;n';
		
		$msg_eliminacion =
			$this->direccion_envio_model->eliminar_direccion($id_cliente, $consecutivo);
		
		//Por si se les ocurre eliminar la dirección que se está ocupando para realizar el pago.
		if ($dir = $this->session->userdata("dir_envio")) {
			if ((int)$dir == (int)$consecutivo) {
				$this->session->unset_userdata("dir_envio");
			}
		}
		
		/*Pendiente el Redirect hacia la dirección de Facturación*/
		//echo $data['msg_eliminacion´];
		
		//cargar la lista de direeciones
		$this->listar($msg_eliminacion, FALSE);
	}
	
	/**
	 * Se enecarga de definir la navegación de la plataforma de acuerdo a la actualización de las formas de pago
	 */
	private function obtener_destino() 
	{
		//Inicializar el destino con un valor por defecto.
		$destino = $this->session->userdata('destino') ? $this->session->userdata('destino') : "forma_pago";
		
		if ($this->session->userdata('tarjeta') || $this->session->userdata('deposito')) {	//tiene forma de pago
			//actualizar valores en sesión
			if ($this->session->userdata('requiere_envio')) {
				//Si hay dirección de envío seleccionada...
				if ($this->session->userdata('dir_envio')) {	
					$destino = "orden_compra";
				} else {
					$destino = "direccion_envio";
				}
			} else {
				//no requiere dirección de envío	
				$destino = "orden_compra";
			}
		} else {	//no tiene forma de pago
			$destino =  "forma_pago";
		}
		
		//Actualizar en sesión
		$this->session->set_userdata('destino', $destino);
		
		return $destino;
	}
	
	/**
	 * Verifica si el código de país corresponde con el de México o no
	 */
	public function es_mexico($codigo_pais="") {
		//$codigo_pais = ['codigo_pais'];
		$r = ($codigo_pais == self::CODIGO_MEXICO) ? TRUE : FALSE;
		$es_mexico = array('result' => $r, 'param' => $codigo_pais);
		
		header('Content-Type: application/json',true);
		echo json_encode($es_mexico);
	}
	
	/**
	 * Regresa el listado de estados para poblar el select correspondiente
	 * en formato JSON
	 */
	public function get_estados()
	{
		//echo json_encode($this->consulta_estados());
		header('Content-Type: application/json',true);
		echo json_encode($this->direccion_envio_model->listar_estados_sepomex()->result_array());
	}
	
	/*
	 * Regresa un array con los resultados
	 */
	private function consulta_estados()
	{
		$resultado = array();
		
		try
		{
			$resultado['estados'] = $this->direccion_envio_model->listar_estados_sepomex()->result();
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
	
	public function get_info_sepomex($cp = 0)
	{
		//echo "cp: ". $cp;
		
		/*if (array_key_exists('codigo_postal', $_POST) && isset($_POST['codigo_postal']))
			$cp = $_POST['codigo_postal'];*/
		
		//$cp = $this->input->post('codigo_postal');
		
		//echo "<br/>Es llamada ajax?: ". $this->input->is_ajax_request() . "<br/>";
		//echo "<script>alert('Peticion Ajax'); </script>";
		//echo json_encode($this->consulta_sepomex($cp));
		
		if (!$cp)
			$cp = $this->input->post('codigo_postal');
		//$cp = $this->input->post('codigo_postal');
		
		//$resultado = array();
		//$resultado->sepomex = $this->direccion_envio_model->obtener_direccion_sepomex($cp)->result();
		$resultado = $this->consulta_sepomex($cp);
		//$this->output->set_content_type("content-type: application/json")->set_output(json_encode($resultado));
		header('Content-Type: application/json',true);
		echo json_encode($resultado);
	}
	
	/*
	 * Regresa un array con los resultados: cp, CIUDAD, Clave estado, ESTADO
	 */
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
		
		/*
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
		*/
	}
	
	/**
	 * Regresa el listado de estados para poblar el select correspondiente
	 * en formato JSON
	 */
	public function get_ciudades($estado = "")
	{
		$estado = $this->input->post('estado');
		$resultado = array();
		$resultado['ciudades'] = $this->direccion_envio_model->listar_ciudades_sepomex($estado)->result_array();
		header('Content-Type: application/json',true);
		echo json_encode($resultado);
		//$estado = $this->input->post('estado');	// ? $this->input->post('estado') : "" ;
		//echo json_encode($this->consulta_ciudades($estado));
	}
	
	private function consulta_ciudades($estado)
	{
		$resultado = array();			
		try {
			$resultado['ciudades'] = $this->direccion_envio_model->listar_ciudades_sepomex($estado)->result();
			$resultado['success'] = true;
			$resultado['msg'] = "Ciudades Resultados";
			return $resultado;
		} catch (Exception $e) {
			$resultado['exception'] =  $exception;
			$resultado['msg'] = $exception->getMessage();
			$resultado['error'] = true;
			return $resultado;
		}
		/*
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
		*/
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
		$estado = $this->input->post('estado');
		$ciudad = $this->input->post('ciudad');

		$resultado = array();
		$resultado['colonias'] = $this->direccion_envio_model->listar_colonias_sepomex($estado, $ciudad)->result_array();
		
		header('Content-Type: application/json',true);
		echo json_encode($resultado);
		//$estado = $this->input->post('estado');	// ? $this->input->post('estado') : "" ;
		//$ciudad = $this->input->post('ciudad');
		//echo "edo: ".$edo;
		//echo json_encode($this->consulta_colonias($estado, $ciudad));
	}
	
	private function consulta_colonias($estado, $ciudad)
	{
		$resultado = array();
		try {
			$resultado['colonias'] = $this->direccion_envio_model->listar_colonias_sepomex($estado, $ciudad)->result();
			$resultado['success'] = true;
			$resultado['msg'] = "Colonias Resultados";
			return $resultado;
		} catch (Exception $e) {
			$resultado['exception'] =  $exception;
			$resultado['msg'] = $exception->getMessage();
			$resultado['error'] = true;
			return $resultado;
		}
		/*
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
		*/ 
	}
	
	/**
	 * Recoge los valores del formulario de registro y edición
	 * 
	 */
	private function get_datos_direccion()
	{
		$datos = array();
		//no se usa la funcion de escape '$this->db->escape()', por que en la inserción ya se incluye 
		if($_POST) {
			if (array_key_exists('txt_calle', $_POST)) {
				if(preg_match('/^[A-Z0-9áéíóúÁÉÍÓÚÑñ \'.-]{1,50}$/i', $_POST['txt_calle'])) {
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
				if (preg_match('/^([1-9]{2}|[0-9][1-9]|[1-9][0-9])[0-9]{3}$/', $_POST['txt_cp'])) {
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
			
			/*Mexico*/
			if (!empty($_POST['sel_pais']) && $_POST['sel_pais'] == self::CODIGO_MEXICO)
			{
				if (!empty($_POST['sel_estados'])) {
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
			} else {
			/*otros paises*/
				if (array_key_exists('txt_colonia', $_POST) && trim($_POST['txt_colonia']) != ""){
					$datos['direccion']['address3'] = $_POST['txt_colonia'];
				}
				else {
					$this->reg_errores['txt_colonia'] = 'Ingresa una colonia válida';
				}
				if (array_key_exists('txt_ciudad', $_POST) && !empty($_POST['txt_ciudad'])) {
					$datos['direccion']['city'] = $_POST['txt_ciudad'];
				}
				else {
					$this->reg_errores['txt_ciudad'] = 'Por favor ingrese una ciudad valida';
				}
				if (array_key_exists('txt_estado', $_POST) && !empty($_POST['txt_estado'])) {
					$datos['direccion']['state'] = $_POST['txt_estado'];
				}
				else {
					$this->reg_errores['txt_estado'] = 'Por favor ingrese un estado valido';
				}	
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
			/*
			if (filter_var($_POST['txt_email'], FILTER_VALIDATE_EMAIL)) {
				$datos['direccion']['email'] = htmlspecialchars(trim($_POST['txt_email']));
			} else {
				$this->reg_errores['txt_email'] = 'Ingresa una direcci&oacute;n v&aacute;lida.';
			}
			*/
			if (array_key_exists('chk_guardar', $_POST)) {
				$datos['guardar'] = $_POST['chk_guardar'];		//indicador para saber si se guarda o no la tarjeta
				$datos['direccion']['id_estatusSi'] = 1;
			}

			if (array_key_exists('chk_default', $_POST)) {
				$datos['direccion']['id_estatusSi'] = 3;	//indica que será la tarjeta predeterminada
				$datos['predeterminar'] = true;
				//$_POST['chk_default'];
				//en la edicion, si no se cambia, que se quede como está, activa!! VERIFICARLO on CCTC
			}
		} 
		//var_dump($datos);
		return $datos;
	}

	private function cargar_en_session($direccion = null)
	{
		if (is_array($direccion)) { //si no se guarda en BD
			$this->session->set_userdata('dir_envio', $direccion);
		} else if ( ((int)$direccion) != 0 && is_int((int)$direccion)) {	//si ya está regiustrada la direccion en BD sólo sube el consecutivo
			$this->session->set_userdata('dir_envio', $direccion);
		} else {	//si no es ninguno de los dos, elimina el elemento de la sesión
			$this->session->unset_userdata('dir_envio');
		}
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