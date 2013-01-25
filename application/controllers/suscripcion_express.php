<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include('api.php');
class Suscripcion_Express extends CI_Controller {
	
	var $title = 'Reporte de Usuarios'; 		// Capitalize the first letter
	var $subtitle = 'Reporte de Usuarios'; 	// Capitalize the first letter
	
	public static $FORMA_PAGO = array(
		1 =>	"Prosa", 
		2 =>	"American Express", 
		3 =>	"Deposito Bancario",
		4 =>	"Otro"
	);
		
	function __construct(){
        parent::__construct();						
		$this->load->model('direccion_facturacion_model', 'direccion_facturacion_model', true);
		$this->load->model('direccion_envio_model', 'direccion_envio_model', true);				
		$this->load->helper('date');
		$this->api = New Api();										
    }
	
	public function index(){
		$this->datos();									
	}	
	
	public function datos($sitio = "", $canal = "", $promocion = ""){
		$data['title']='Suscripción Express';
		
		if(is_numeric($sitio) && is_numeric($canal) && is_numeric($promocion)){
			
			$lista_paises_think = $this->direccion_facturacion_model->listar_paises_think();
			$data['lista_paises_think'] = $lista_paises_think;
			
			$data['promo'] = $this->api->obtener_detalle_promo($sitio, $canal, $promocion );			
			$this->session->set_userdata('promo', $data['promo']);
			
			$this->load->view('templates/header', $data);							
			$this->load->view('suscripcion_express/registro_cliente');
		}	
		else{
			#promocion inexistente				
			$data['mensaje']="Información insuficiente para completar la orden";
			$this->load->view('templates/header', $data);
			$this->load->view('mensaje', $data); 			
		}						
		
	}
	
	public function pago(){
		$data['title']='Suscripción express';			
		
		
		
		$this->load->view('templates/header', $data);					
		$this->load->view('suscripcion_express/pago');
	}

	public function get_info_sepomex($cp = 0)
	{
		
		
		if (!$cp)
			$cp = $this->input->post('codigo_postal');
		
		
		$resultado = $this->consulta_sepomex($cp);
		/*
		echo "<pre>";
			print_r($resultado);
		echo "</pre>";
		*/
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
			
}

/* End of file reporte.php */
/* Location: ./application/controllers/reporte.php */