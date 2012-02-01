<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Forma_Pago extends CI_Controller {

	public function index()
	{
				
		//cargar vista
		
		$data['title'] = ucfirst('forma de Pago'); 		// Capitalize the first letter
		$data['subtitle'] = ucfirst('forma de Pago'); 	// Capitalize the first letter
		
		
		$this->cargar_vista('', 'forma_pago', $data);
	}
	
	public function view($page = 'home')
	{
			
		if ( ! file_exists('application/views/pages/'.$page.'.php'))
		{
			// Whoops, we don't have a page for that!
			show_404();
		}
		
		$data['title'] = ucfirst($page); 			// Capitalize the first letter
		$data['subtitle'] = ucfirst('Páginas'); 	// Capitalize the first letter
		$this->cargar_vista('pages', $page, $data);
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