<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Administrador_usuario extends CI_Controller {
		
	function __construct()
    {
        // Call the Model constructor
        parent::__construct();						

		
		// incluye el modelo de las direcciones de facturacion		
		$this->load->model('direccion_facturacion_model', 'direccion_facturacion_model', true);				
		$this->load->model('direccion_envio_model', 'direccion_envio_model', true);
		$this->load->model('forma_pago_model', 'forma_pago_model', true);
    }
	
	public function index()
	{
	}
	
	public function listar_razon_social($id_cliente = ""){		
		$data['rs'] = $this->direccion_facturacion_model->listar_razon_social($id_cliente);						
		echo json_encode($data);
	}
	
	public function listar_direccion_envio($id_cliente = ""){
		$data['direccion_envio'] = $this->direccion_envio_model->listar_direcciones($id_cliente)->result_array();							
		echo json_encode($data);
	}
	
	public function listar_tarjetas($id_cliente = ""){
		$data['direccion_envio'] = $this->forma_pago_model->listar_tarjetas($id_cliente)->result_array();							
		echo json_encode($data);
	}
	
}

/* End of file administrador_usuario.php */
/* Location: ./application/controllers/administrador_usuario.php */