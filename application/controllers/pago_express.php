<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Pago_express extends CI_Controller {
	
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
		$this->load->helper('date');										
    }
	
	public function index(){
		$data['title']='';		
		
		$lista_paises_think = $this->direccion_facturacion_model->listar_paises_think();
		$data['lista_paises_think'] = $lista_paises_think;
		
		
		if($_POST){
			/*
			echo "<pre>";
				print_r($_POST);
			echo "</pre>";
			 */ 
		}
		$this->load->view('templates/header.php', $data);					
		$this->load->view('pago_express/registro_cliente.php');								
	}		
	
	public function info_sepomex($cp){
		echo $cp;
	}
	
		
}

/* End of file reporte.php */
/* Location: ./application/controllers/reporte.php */