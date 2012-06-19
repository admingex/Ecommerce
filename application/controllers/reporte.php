<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once 'api.php';

class Reporte extends CI_Controller {
	
	var $title = 'Reporte de Usuarios'; 		// Capitalize the first letter
	var $subtitle = 'Reporte de Usuarios'; 	// Capitalize the first letter
		
	function __construct(){
        parent::__construct();						
		$this->load->model('reporte_model', 'reporte_model', true);				
		$this->load->helper('date');
						
		if(($this->session->userdata('user')=='aespinosa') || ($_POST['user']=='aespinosa')){
			if(($this->session->userdata('pass')=='Aesp1n0_20120618') || ($_POST['pass']=='Aesp1n0_20120618')){
				$this->session->set_userdata('user', 'aespinosa');
				$this->session->set_userdata('pass', 'Aesp1n0_20120618');
				$this->usuarios();
			}	
			else{
				redirect('mensaje/'.md5(5));
			}			
		}
		else{
			redirect('mensaje/'.md5(5));
		}							
    }
	
	public function index(){		
								
	}		
	
	public function usuarios(){
		$data['title']=$this->title;		
		if($_POST){
			$fecha_inicio=$this->input->post('fecha_inicio');
			$fecha_fin=$this->input->post('fecha_fin');
		}
		else{			
			$fecha_inicio= mdate('%Y/%m/%d',time());
			$fecha_fin=mdate('%Y/%m/%d',time());
		}
		$data['fecha_inicio']=$fecha_inicio;
		$data['fecha_fin']=$fecha_fin;
		
		$usuarios=$this->reporte_model->obtener_usuarios_fecha($fecha_inicio, $fecha_fin);		
		$data['usuarios']=$usuarios;		
		$this->load->view('templates/header',$data);
		echo anchor(site_url('logout'),'cerrar sesion');
		$this->load->view('reportes/reporte_usuarios',$data);		
	}	
	
	public function compras(){
		$data['title']=$this->title;		
		if($_POST){
			$fecha_inicio=$this->input->post('fecha_inicio');
			$fecha_fin=$this->input->post('fecha_fin');
		}
		else{			
			$fecha_inicio= mdate('%Y/%m/%d',time());
			$fecha_fin=mdate('%Y/%m/%d',time());
		}
		$data['fecha_inicio']=$fecha_inicio;
		$data['fecha_fin']=$fecha_fin;
		
		$compras= $this->reporte_model->obtener_compras_fecha($fecha_inicio, $fecha_fin);
		$data['compras']=$compras;		
		$this->load->view('templates/header',$data);
		$this->load->view('reportes/reporte_compras',$data);	
				
	}
	
}

/* End of file reporte.php */
/* Location: ./application/controllers/reporte.php */