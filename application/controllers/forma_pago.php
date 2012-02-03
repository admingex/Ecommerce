<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Forma_Pago extends CI_Controller {
	var $title = 'Forma de Pago'; 		// Capitalize the first letter
	var $subtitle = 'Forma de Pago'; 	// Capitalize the first letter
	
	function __construct()
    {
        // Call the Model constructor
        parent::__construct();
		
		//cargar el modelo en el constructor
		$this->load->model('forma_pago_model', 'fp', true);
    }

	public function index($id_cliente = 1)	//Para pruebas se usa 1
	{
		$data['title'] = $this->title;
		$data['subtitle'] = $this->subtitle;
		
		$_SESSION['id_cliente'] = $id_cliente;
		//Recuperar el "id_ClienteNu" del post
		/** 
			TO DO
		*/
		
		$this->listar($id_cliente, $data);
	}
	
	public function listar($id_cliente = 1) 
	{
		$data['title'] = $this->title;
		$data['subtitle'] = ucfirst('listar Tarjetas');
		
		//listar por default las tarjetas del cliente
		$data['resultado'] = $this->fp->listar_tarjetas($id_cliente);
	
		//cargar vista	
		$this->cargar_vista('', 'forma_pago', $data);
	}
	
	public function registrar($form = 'tc') 
	{
		$data['title'] = $this->title;
		$data['subtitle'] = ucfirst('registrar Tarjetas');
		
		$data['title'] = ucfirst('forma de Pago'); 		// Capitalize the first letter
		$data['subtitle'] = ucfirst('forma de Pago'); 	// Capitalize the first letter
		//$data['id_cliente'] = $_SESSION['id_cliente'];
		$data['form'] = $form;
		if ($_POST)	{	//si hay parámetros del formulario
			if ($form == 'tc')
			{/*registro de tarjeta normal*/
				/*TO DO*/
				
			}
			else if ($form == 'amex')
			{/*registro de tarjeta AMEX*/
				/*TO DO*/
			}
		} else {
			$this->cargar_vista('', 'forma_pago' , $data);
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