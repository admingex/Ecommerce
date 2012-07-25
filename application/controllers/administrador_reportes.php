<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Administrador_reportes extends CI_Controller {
		
	function __construct(){
        parent::__construct();							
    }
	
	public function index(){
		$data['title']='Acceso a Reportes';
		$this->load->view('templates/header',$data);
		$this->load->view('reportes/acceso_reportes');
		$this->load->view('templates/footer');	  					
	}
	
	public function acceso_pago(){
		$data['title']='Acceso a Pago';
		$this->load->view('templates/header',$data);
		$this->load->view('reportes/acceso_pago');
		$this->load->view('templates/footer');	  					
	}
}

/* End of file administrador_reportes.php */
/* Location: ./application/controllers/administrador_reportes.php */