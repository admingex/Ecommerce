<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Mensaje extends CI_Controller {
		
	function __construct(){
        parent::__construct();							
    }
	
	public function index(){						
	}
	
	public function idm($id=''){
		$this->session->sess_destroy();
		$data['title']="Mensaje";
		// mensaje numero 1 para informacion incompleta				
		if ($id==md5(1)){			
			$data['mensaje']="Informacion insuficiente para completar la orden";
			$this->cargar_vista('', 'mensaje', $data);
		} else if ($id==md5(2)){	//Error en el registro de la compra en Ecommerce			
			$data['mensaje'] = "No se pudo resgistrar la compra en la plataforma de pagos para el depósito bancario.";
			$this->cargar_vista('', 'mensaje', $data);
		} else if ($id==md5(3)){	//Error en el registro de la compra en Ecommerce			
			$data['mensaje'] = "No se pudo resgistrar la compra en la plataforma de pagos para la tarjeta solicitada.";
			$this->cargar_vista('', 'mensaje', $data);
		} else if ($id==md5(4)){	//Error en el registro de la compra en Ecommerce			
			$data['mensaje'] = "No se pudo resgistrar enviar el correo de notificación al usuario.";
			$this->cargar_vista('', 'mensaje', $data);
		}
	}
	public function cargar_vista($folder, $page, $data){
		//Para automatizar un poco el desplieguee
		$this->load->view('templates/header', $data);		
		$this->load->view($folder.'/'.$page, $data);
		$this->load->view('templates/footer', $data);
	}	
}

/* End of file mensaje.php */
/* Location: ./application/controllers/mensaje.php */