<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Direccion_Facturacion extends CI_Controller {
	var $title = 'Direcci&oacute;n de Facturaci&oacute;n'; 		
	var $subtitle = 'Direcci&oacute;n de Facturaci&oacute;n'; 	
	var $mensaje= '';					
	var $reg_errores = array();		//validación para los errores
	
	private $id_cliente;	
	 
	public static $TIPO_DIR = array(
		"RESIDENCE"	=> 0, 
		"BUSINESS"	=> 1, 
		"OTHER"		=>	2
	);
	 
	function __construct(){
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

	public function index(){		
		$this->listar();
	}
	
	public function listar($msg = ''){					
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
		$data['mensaje']='';
		
		//catálogo de paises de think
		$lista_paises_think = $this->modelo->listar_paises_think();
		$data['lista_paises_think'] = $lista_paises_think;						 
		
		//recuperar el listado de las direcciones del cliente
		$data['lista_direcciones'] = $this->modelo->listar_direcciones($id_cliente);
		
		$data['registrar'] = TRUE;		//para indicar que se debe mostrar formulario de registro
		$script_file = "<script type='text/javascript' src='". base_url() ."js/dir_facturacion.js'> </script>";
		$data['script'] = $script_file;
		
		if ($_POST)	{	//si hay parámetros del formulario			
											
			$form_values = array();	//alojará los datos previos a la inserción	
			$form_values = $this->get_datos_direccion();			
						
			$form_values['direccion']['id_clienteIn'] = $id_cliente;
			$form_values['direccion']['id_consecutivoSi'] = $consecutivo + 1;		//cambió
			$form_values['direccion']['address_type'] = self::$TIPO_DIR['BUSINESS'];		//address_type
			
			
			if(empty($this->reg_errores)){
			    if (isset($form_values['direccion']['id_estatusSi'])) {
					if($this->modelo->existe_direccion($form_values['direccion'])) {
							$this->listar("Direcci&oacute;n previamente registrada.");
					}
					else{																	
						if ($this->modelo->insertar_direccion($form_values['direccion'])) {
							$this->listar("Direcci&oacute;n registrada.");
						} 	
						else {
							$this->listar("Hubo un error en el registro en CMS.");
							echo "<br/>Hubo un error en el registro en CMS";
						}
					}											
				} 	
			}	
			else{								
				$data['reg_errores'] = $this->reg_errores;				
				$this->cargar_vista('', 'direccion_facturacion' , $data);	
			}														
					
		} 
		else {
			$this->cargar_vista('', 'direccion_facturacion' , $data);
		}
	}

	public function editar($consecutivo = ''){
				
			$script_file = "<script type='text/javascript' src='". base_url() ."js/dir_facturacion.js'></script>";
			$data['script'] = $script_file;
			
			$data['mensaje']='';
			$data['editar'] = TRUE;
			
			if($consecutivo){						
				$id_cliente = $this->id_cliente;		
				$data['title'] = $this->title;
				$data['subtitle'] = ucfirst('Editar Direcci&oacute;n');
				$data['consecutivo']=$consecutivo;
	
		
				$datos_direccion = $this->modelo->obtener_direccion($id_cliente, $consecutivo);
				$data['datos_direccion']=$datos_direccion;
		
				$lista_paises_think = $this->modelo->listar_paises_think();
				$data['lista_paises_think'] = $lista_paises_think;
																								
				if($_POST){					
					$form_values = array();	//alojará los datos previos a la inserción	
					$form_values = $this->get_datos_direccion();	
					if(empty($this->reg_errores)){									
						$this->modelo->actualizar_direccion($id_cliente, $consecutivo, $form_values['direccion']);
						$this->listar("Actualizacion correcta");
					}
					else{								
						$data['reg_errores'] = $this->reg_errores;				
						$this->cargar_vista('', 'direccion_facturacion' , $data);	
					}																			
				}	
				else{
					$this->cargar_vista('', 'direccion_facturacion', $data);	
				}								
			}
			else{
				$this->listar();
			}					
	}
	
	public function eliminar($consecutivo = ''){		
		$id_cliente = $this->id_cliente;
		$data['title'] = $this->title;		
		$data['subtitle'] = ucfirst('Eliminar Direcci&oacute;n');
		$this->modelo->eliminar_direccion($id_cliente, $consecutivo);
		$this->listar('Registro eliminado');		
	}
	
	
	private function get_datos_direccion(){
		$datos = array();		 		
		
		if(!empty($_POST['txt_rfc'])){
			if((strlen($_POST['txt_rfc'])>13)||(strlen($_POST['txt_rfc'])<12)){
			    $this->reg_errores['txt_rfc'] = 'Por favor ingrese un rfc correcto';		
			}	
			else{
				if(strlen($_POST['txt_rfc'])==12){
					if (preg_match('/^([a-zA-Z]{3})+([0-9]{6})+([a-zA-Z0-9]{3})$/', $_POST['txt_rfc'])) {
						$datos['direccion']['tax_id_number'] = $_POST['txt_rfc'];
					}
					else {
						$this->reg_errores['txt_rfc'] = 'Por favor ingrese un rfc correcto';
					}	
				}
				else if(strlen($_POST['txt_rfc'])==13){					    
					if (preg_match('/^([a-zA-Z]{4})+([0-9]{6})+([a-zA-Z0-9]{3})$/', $_POST['txt_rfc'])) {
						$datos['direccion']['tax_id_number'] = $_POST['txt_rfc'];
					}
					else {
						$this->reg_errores['txt_rfc'] = 'Por favor ingrese un rfc correcto';
					}					
				} 				
			}					
		}			
		else{
			$this->reg_errores['txt_rfc'] = 'Por favor ingrese un rfc';				
		}
		if(!empty($_POST['txt_razon_social'])){
			$datos['direccion']['company'] = $_POST['txt_razon_social'];
		}
		else{
			$this->reg_errores['txt_razon_social'] = 'Por favor ingrese una razón social';
		}
		if(!empty($_POST['txt_calle'])){
			$datos['direccion']['address1'] = $_POST['txt_calle'];
		}
		else{
			$this->reg_errores['txt_calle'] = 'Por favor ingrese una calle valida';
		}
		if(!empty($_POST['txt_numero'])){
			$datos['direccion']['address2'] = $_POST['txt_numero'];
		}
		else{
			$this->reg_errores['txt_numero'] = 'Por favor ingrese un numero';
		}		
		if(!empty($_POST['txt_cp'])){
			if(preg_match('/^[0-9]{5,5}([- ]?[0-9]{4,4})?$/', $_POST['txt_cp'])){
			    $datos['direccion']['zip'] = $_POST['txt_cp'];	
			}			
			else{
			    $this->reg_errores['txt_cp'] = 'Por favor ingrese un codigo postal valido';	
			}
		}
		else{
			$this->reg_errores['txt_cp'] = 'Por favor ingrese un codigo postal valido';
		}
		if(!empty($_POST['txt_colonia'])){
			$datos['direccion']['address3'] = $_POST['txt_colonia'];
		}
		else{
			$this->reg_errores['txt_colonia'] = 'Por favor ingrese una colonia valida';
		}
		if(!empty($_POST['txt_ciudad'])){
			$datos['direccion']['city'] = $_POST['txt_ciudad'];
		}
		else{
			$this->reg_errores['txt_ciudad'] = 'Por favor ingrese una ciudad valida';
		}
		if(!empty($_POST['txt_estado'])){
			$datos['direccion']['state'] = $_POST['txt_estado'];
		}
		else{
			$this->reg_errores['txt_estado'] = 'Por favor ingrese un estado valido';
		}						
		if (preg_match('/^[^0-9][a-zA-Z0-9_]+([.][a-zA-Z0-9_]+)*[@][a-zA-Z0-9_]+([.][a-zA-Z0-9_]+)*[.][a-zA-Z]{2,4}$/', $_POST['txt_email'])) {    		
			$datos['direccion']['email'] = $_POST['txt_email'];					
		}  	
		else{
			$this->reg_errores['txt_email'] = 'Por favor ingrese una correo valida';
		}								
		
		$datos['direccion']['address4'] = $_POST['txt_num_int'];			
		$datos['direccion']['codigo_paisVC'] = $_POST['sel_pais'];	
		
		if (array_key_exists('chk_guardar', $_POST)) {
			$datos['direccion']['id_estatusSi'] = 1;							
		}			
																
		if (array_key_exists('chk_default', $_POST)) {
			$datos['direccion']['id_estatusSi'] = 3;	//indica que será la direccion de facturacion predeterminada					
		}																 	
		return $datos;
	}
				
	private function cargar_vista($folder, $page, $data){	
		//Para automatizar un poco el desplieguee
		$this->load->view('templates/header', $data);
		$this->load->view($folder.'/'.$page, $data);
		$this->load->view('templates/footer', $data);
	}
	
	private function redirect_cliente_invalido($revisar = 'id_cliente', $destino = '/index.php/login', $protocolo = 'http://'){
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