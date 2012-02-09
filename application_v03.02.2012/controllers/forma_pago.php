<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Forma_Pago extends CI_Controller {
	var $title = 'Forma de Pago'; 		// Capitalize the first letter
	var $subtitle = 'Forma de Pago'; 	// Capitalize the first letter
	
	function __construct()
    {
        // Call the Model constructor
        parent::__construct();
		
		$this->load->library('session');
		
		//cargar el modelo en el constructor
		$this->load->model('forma_pago_model', 'model');
		
    }

	public function index($id_cliente = 1)	//Para pruebas se usa 1
	{
		$data['title'] = $this->title;
		$data['subtitle'] = $this->subtitle;
		
		//Recuperar el "id_ClienteNu" del post
		/** 
			TO DO
		*/
		
		$this->listar($id_cliente, $data);
	}
	
	public function listar($id_cliente='') 
	{
		$data['title'] = $this->title;
		$data['subtitle'] = $this->subtitle;	//ucfirst('listar Tarjetas');
		
		//Se guarda el user del usuario en sesión
		//echo isset($id_cliente);
		if (isset($id_cliente))
			$this->session->set_userdata('id_cliente', $id_cliente);
					
		//echo 'session:'.$this->session->userdata('id_cliente');
		
		//listar las tarjetas del cliente solicitado *por encriptar o 'hashear'
		$data['resultado'] = $this->model->listar_tarjetas($id_cliente);
		
		//cargar vista	
		$this->cargar_vista('', 'forma_pago', $data);
	}
	
	public function registrar($form = 'tc') 
	{
		$data['title'] = $this->title;
		$data['subtitle'] = ucfirst('registrar Tarjeta');
		
		echo 'session:'.$this->session->userdata('id_cliente').'<br/>';
		
		/*
		 * Cargar catálogos, fechas, SEPOMEX, etc...
		 * */
		
		
		//$data['id_cliente'] = $_SESSION['id_cliente'];
		$data['form'] = $form;	//?
		
		if ($_POST)	{	//si hay parámetros del formulario
			//recuperar el id del cliente en cuestion
			$id_cliente = $this->session->userdata('id_cliente');
			/*recupera parámetros*/
			$temp_form = $this->recupera_parametros('POST');
			
			$informacion_tc = array();
			$informacion_amex = array();
			
			$max_actual = $this->model->obtener_consecutivo($id_cliente);
			/*Ajustar de acuerdo a la lógica del wS*/				
			$consecutivoTc = $max_actual + 1;
			
			/*registro de tarjeta normal*/
			if ($form == 'tc')
			{	
				//Obtener el consecutivo de la tarjeta del cliente
				
				
				$informacion_tc['id_TCSi'] = $consecutivoTc;		//consecutivo de la tarjeta
				$informacion_tc['id_ClienteIn'] = $id_cliente;
					//Pendiente del catélogo
				$informacion_tc['descripcionVc'] = 'Visa/Master Card';
				
				
				//echo "<br/>consecutivo: $consecutivoTc",var_dump($max_actual);
				
				foreach ($temp_form['tc'] as $key => $value) {
					//echo "$key: ". $value."<br/>";
					$informacion_tc[$key] = $value;
				}
				
				/*información que se enviará al Ws de CCTC*/
				
				foreach ($informacion_tc as $key => $value) {
					echo "$key: ". $value."<br/>";
					//$informacion_tc[$key] = $value;
				}
				
				
				//inserta y carga vista
				/*Usar el servicio de Armando y recibir resultados
				 * Si la inserción fue correcta, 
				 * 
				 * */
				
				//$this->model->insertar_tarjeta($informacion_tc);
				
				//carga vista
				$this->listar($id_cliente);
			}
			else if ($form == 'amex')
			{/*registro de tarjeta AMEX*/
				/*TO DO*/
				$informacion_amex['id_TCSi'] = $consecutivoTc;		//consecutivo de la tarjeta
				$informacion_amex['id_ClienteIn'] = $id_cliente;
				
				foreach ($temp_form['amex'] as $key => $value) {
					//echo "$key: ". $value."<br/>";
					$informacion_amex[$key] = $value;
				}
				
				foreach ($informacion_amex as $key => $value) {
					echo "$key: ". $value."<br/>";
					//$informacion_tc[$key] = $value;
				}
				
				$this->listar($id_cliente);
			}
		} else {
			$this->cargar_vista('', 'forma_pago' , $data);
		}
	}
	
	/*Recoge parametros*/
	/*metodo: POST / GET*/
	function recupera_parametros($metodo='POST') 
	{
		//para realizar la inserción
		$datos = array();
		
		if($metodo == 'POST')
		{
			if(array_key_exists('txt_numeroTarjeta', $_POST)) { 
				$datos['tc']['numeros_TarjetaVc'] = $this->db->escape($_POST['txt_numeroTarjeta']); 
			}
			if(array_key_exists('txt_nombre', $_POST)) { 
				$datos['tc']['nombre_titularVc'] = $this->db->escape($_POST['txt_nombre']); 
			}
			if(array_key_exists('txt_apellidoP', $_POST)){
				$datos['tc']['apellidoP_titularVc'] = $this->db->escape($_POST['txt_apellidoP']); 
			}
			if(array_key_exists('txt_apellidoM', $_POST)){
				$datos['tc']['apellidoM_titularVc'] = $this->db->escape($_POST['txt_apellidoM']); 
			}
			if(array_key_exists('sel_mes_expira', $_POST)){
				$mes = $this->db->escape($_POST['sel_mes_expira']); 
			}
			if(array_key_exists('sel_anio_expira', $_POST)){
				$anio = $this->db->escape($_POST['sel_anio_expira']); 
			}
			if(isset($$mes, $anio)) {
				$datos['tc']['fecha_expiracionVc'] = $mes.'/'.$anio;
			} else {
				/*¿se debe validar?*/
				$datos['tc']['fecha_expiracionVc'] = '01/2000';
			}
			/*AMEX*/
			if(array_key_exists('txt_pais', $_POST)){
				$datos['amex']['pais']= $this->db->escape($_POST['txt_pais']); 
			}
			if(array_key_exists('txt_cp', $_POST)){
				$datos['amex']['codigo_postal']= $this->db->escape($_POST['txt_cp']); 
			}
			if(array_key_exists('txt_calle', $_POST)){
				$datos['amex']['calle']= $this->db->escape($_POST['txt_calle']); 
			}
			if(array_key_exists('txt_colonia', $_POST)){
				$datos['amex']['colonia']= $this->db->escape($_POST['txt_colonia']); 
			}
			if(array_key_exists('txt_ciudad', $_POST)){
				$datos['amex']['ciudad']= $this->db->escape($_POST['txt_ciudad']); 
			}
			if(array_key_exists('txt_estado', $_POST)){
				$datos['amex']['estado']= $this->db->escape($_POST['txt_estado']); 
			}
			if(array_key_exists('txt_email', $_POST)){
				$datos['amex']['email']= $this->db->escape($_POST['txt_email']); 
			}
			if(array_key_exists('txt_telefono', $_POST)){
				$datos['amex']['telefono']= $this->db->escape($_POST['txt_telefono']); 
			}
			/*AMEX*/
			return $datos;
		}	
	}
	
	private function cargar_vista($folder, $page, $data)
	{	
		//Para automatizar un poco el desplieguee
		$this->load->view('templates/header', $data);
		$this->load->view($folder.'/'.$page, $data);
		$this->load->view('templates/footer', $data);
	}

}

/* End of file forma_pago.php */
/* Location: ./application/controllers/forma_pago.php */