<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Login extends CI_Controller {

	var $title = 'Inicio de Sesi&oacute;n'; 		// Capitalize the first letter
	var $subtitle = 'Login Cliente'; 	// Capitalize the first letter
	
	public function index()
	{
		//$this->load->view('login');
		$data['title'] = $this->title;
		$data['subtitle'] = $this->subtitle;
		
		$this->cargar_vista('', 'login', $data);
	}
	
	
	public function view($page = 'home')
	{
			
		if ( ! file_exists('application/views/pages/'.$page.'.php'))
		{
			// Whoops, we don't have a page for that!
			show_404();
		}
		
		$data['title'] = ucfirst($page); 			// Capitalize the first letter
		$data['subtitle'] = ucfirst('P�ginas'); 	// Capitalize the first letter
		
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

/* End of file login.php */
/* Location: ./application/controllers/login.php */