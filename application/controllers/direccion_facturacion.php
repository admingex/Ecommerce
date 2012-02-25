<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Direccion_Facturacion extends CI_Controller {
	var $title = 'Direcci&oacute;n de Facturaci&oacute;n'; 		// Capitalize the first letter
	var $subtitle = 'Direcci&oacute;n de Facturaci&oacute;n'; 	// Capitalize the first letter
	var $reg_errores = array();		//validación para los errores
	
	private $id_cliente;	
	 
	public static $TIPO_DIR = array(
		"RESIDENCE"	=> 0, 
		"BUSINESS"	=> 1, 
		"OTHER"		=>	2
	);
	 
	function __construct()
    {
        // Call the Model constructor
        parent::__construct();
		
		//cargar el modelo en el constructor
		$this->load->model('direccion_facturacion_model', 'modelo', true);
		//la sesion se carga automáticamente
		
		//si no hay sesión
		//manda al usuario a la... página de login
		//$this->redirect_cliente_invalido('id_cliente', '/index.php/login');
		
		//si la sesión se acaba de crear, toma el valor inicializar el id del cliente de la session creada en el login/registro
		$this->id_cliente = $this->session->userdata('id_cliente');
    }

	public function index()	//Para pruebas se usa 1
	{		
		$this->listar();
	}
	
	public function listar($msg = '') 
	{					
		//EL usuario se tomará de la sesión...		
		$data['title'] = $this->title;
		$data['subtitle'] = $this->subtitle;		
		$data['mensaje'] = $msg;
		
		//listar por default las direcciones del cliente
		$data['lista_direcciones'] = $this->modelo->listar_direcciones($this->id_cliente);
		
		//cargar vista	
		$this->cargar_vista('', 'direccion_facturacion', $data);		
	}
	
	public function registrar() {				
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
		
		if ($_POST)	{	//si hay parámetros del formulario			
											
			$form_values = array();	//alojará los datos previos a la inserción	
			$form_values = $this->get_datos_direccion();
						
			$form_values['direccion']['id_clienteIn'] = $id_cliente;
			$form_values['direccion']['id_consecutivoSi'] = $consecutivo + 1;		//cambió
			$form_values['direccion']['address_type'] = self::$TIPO_DIR['BUSINESS'];		//address_type
								
			//var_dump($form_values);
									
			if (isset($form_values['guardar']) || isset($form_values['direccion']['id_estatusSi'])) {
																				
				if ($this->modelo->insertar_direccion($form_values['direccion'])) {
					$this->listar("Direcci&oacute;n registrada.");
				} 
				else {
					$this->listar("Hubo un error en el registro en CMS.");
					echo "<br/>Hubo un error en el registro en CMS";
				}										
			} 
			
		} 
		else {
			$this->cargar_vista('', 'direccion_facturacion' , $data);
		}
	}

	public function editar($consecutivo)	//el consecutivo de la tarjeta
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
		
		$this->cargar_vista('', 'direccion_facturacion' , $data);
	}
	
	public function eliminar($consecutivo = ''){		
		$id_cliente = $this->id_cliente;
		$data['title'] = $this->title;
		$data['subtitle'] = ucfirst('Eliminar Direcci&oacute;n');
		$this->modelo->eliminar_direccion($id_cliente, $consecutivo);
		$this->listar();
		
	}
	
	
private function get_datos_direccion(){
	$datos = array();		 
	if($_POST) {
			
		$datos['direccion']['tax_id_number'] = $_POST['txt_rfc'];
		$datos['direccion']['company'] = $_POST['txt_razon_social'];		
		$datos['direccion']['address1'] = $_POST['txt_calle'];							
		$datos['direccion']['address2'] = $_POST['txt_numero'];
		$datos['direccion']['address3'] = $_POST['txt_colonia'];				
		$datos['direccion']['address4'] = $_POST['txt_num_int'];
		$datos['direccion']['zip'] = $_POST['txt_cp'];					
		$datos['direccion']['state'] = $_POST['txt_estado'];
		$datos['direccion']['city'] = $_POST['txt_ciudad'];
		$datos['direccion']['email'] = $_POST['txt_email'];				
									
		if (array_key_exists('chk_guardar', $_POST)) {
			$datos['guardar'] = $_POST['chk_guardar'];	//indicador para saber si se guarda o no la tarjeta
			$datos['direccion']['id_estatusSi'] = 1;
		}
		if (array_key_exists('chk_default', $_POST)) {
				$datos['direccion']['id_estatusSi'] = 3;	//indica que será la tarjeta predeterminada					
		}
	} 		
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

/* End of file direccion_facturacion.php */
/* Location: ./application/controllers/direccion_facturacion.php */