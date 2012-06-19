<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Administrador_reportes extends CI_Controller {
		
	function __construct(){
        parent::__construct();							
    }
	
	public function index(){
		echo "<form name='acceso_restringido' action='".site_url('reporte')."' method='post'>
				  Usuario:
			      <input type='text' name='user' value='' /><br />
			      Contrase&ntilde;a:
			      <input type='password' name='pass' value='' /><br />  			      
			      <input type='submit' name='Ingreso' value='Ingresar' />
			  </form>";					
	}
	
	
	public function cargar_vista($folder, $page, $data){
		//Para automatizar un poco el desplieguee
		$this->load->view('templates/header', $data);		
		$this->load->view($folder.'/'.$page, $data);
		$this->load->view('templates/footer', $data);
	}	
}

/* End of file administrador_reportes.php */
/* Location: ./application/controllers/administrador_reportes.php */