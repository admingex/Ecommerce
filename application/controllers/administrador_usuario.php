<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Administrador_usuario extends CI_Controller {
		
	function __construct()
    {
        // Call the Model constructor
        parent::__construct();						

		
		// incluye el modelo de las direcciones de facturacion		
		$this->load->model('direccion_facturacion_model', 'direccion_facturacion_model', true);				
    }
	
	public function index()
	{
	}
	
	public function listar_razon_social($id_cliente = ""){		
		$data['razon_social'] = $this->direccion_facturacion_model->listar_razon_social($id_cliente);					
		echo json_encode($data);
	}
	
}

/* End of file administrador_usuario.php */
/* Location: ./application/controllers/administrador_usuario.php */