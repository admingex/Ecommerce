<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once 'api.php';

class Reporte extends CI_Controller {
	
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
		$this->load->model('reporte_model', 'reporte_model', true);				
		$this->load->helper('date');
						
		if(array_key_exists('user', $this->session->all_userdata()) || array_key_exists('pass', $this->session->all_userdata()) || array_key_exists('user', $_POST) || array_key_exists('pass', $_POST)){
			if(($this->session->userdata('user')=='aespinosa') || ($_POST['user']=='aespinosa')){
				if(($this->session->userdata('pass')=='Aesp1n0_20120618') || ($_POST['pass']=='Aesp1n0_20120618')){
					$this->session->set_userdata('user', 'aespinosa');
					$this->session->set_userdata('pass', 'Aesp1n0_20120618');									
				}	
				else{
					redirect('mensaje/'.md5(5));
				}
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
		$this->usuarios();		
								
	}		
	
	public function usuarios(){
		$data['title']=$this->title;			
		if(!empty($_POST['fecha_inicio']) && !empty($_POST['fecha_inicio'])){
			$fecha_inicio=$this->input->post('fecha_inicio');
			$fecha_fin=$this->input->post('fecha_fin');
		}
		else{			
			$fecha_inicio= mdate('%Y/%m/%d',time());
			$fecha_fin=mdate('%Y/%m/%d',time());
		}
		$data['fecha_inicio']=$fecha_inicio;
		$data['fecha_fin']=$fecha_fin;	
		$data['error']="";
			
		if($this->is_date($fecha_inicio) && $this->is_date($fecha_fin)){				
			$usuarios=$this->reporte_model->obtener_usuarios_fecha($fecha_inicio, $fecha_fin);		
			$data['usuarios']=$usuarios;		
			$this->load->view('templates/header',$data);	
			$this->load->view('reportes/formulario_fecha',$data);		
			$this->load->view('reportes/reporte_usuarios',$data);		
		}
		else{
			$data['error']="ingrese un intervalo valido con el formato (aaaa/mm/dd) para fecha de inicio y fecha fin";
			$this->load->view('templates/header',$data);
			$this->load->view('reportes/formulario_fecha',$data);											
		}							
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
		$data['compras']=array();	
				
		foreach($compras->result_array() as $i => $compra){
						
			$data['compras'][$i]['compra'] = $compra;													
			$cliente = $this->reporte_model->obtener_cliente($compra['id_clienteIn']);												
			$data['compras'][$i]['cliente'] = $cliente->row();
						
			$dir_envio = $this->reporte_model->obtener_dir_envio($compra['id_compraIn'], $compra['id_clienteIn']);
			if($dir_envio->num_rows() > 0){
					$data['compras'][$i]['dir_envio'] = $dir_envio->row();	
			}
			else{
				$data['compras'][$i]['dir_envio']= "No requiere";
			}	
			
			$forma_pago = $this->reporte_model->obtener_medio_pago($compra['id_compraIn'], $compra['id_clienteIn']);
			$data['compras'][$i]['medio_pago'] = self::$FORMA_PAGO[($forma_pago->row()->id_tipoPagoSi)];	
			$data['compras'][$i]['fecha_compra'] = 	$forma_pago->row()->fecha_registroTs;
						
			$id_promo = $this->reporte_model->obtener_promo_compra($compra['id_compraIn'], $compra['id_clienteIn']);
			
			$articulos = $this->reporte_model->obtener_articulos($id_promo);
			$monto = 0;			
			foreach($articulos->result_array() as $articulo){
				$monto+= $articulo['tarifaDc'];
			}
			$data['compras'][$i]['monto'] = $monto;																			
								
		}
		/*
		echo "<pre>";
				print_r($data);
		echo "</pre>";
		 */						
		$this->load->view('templates/header',$data);
		$this->load->view('reportes/reporte_compras',$data);	
				
	}
	
	public function is_date($date){
         if (preg_match ("/^([0-9]{4})\/([0-9]{2})\/([0-9]{2})$/", $date, $parts)){
             if(checkdate($parts[2],$parts[3],$parts[1]))
             	return true;
         	 else
            	return false;
      	 }
      	 else
      	     return false;
	}
	
}

/* End of file reporte.php */
/* Location: ./application/controllers/reporte.php */