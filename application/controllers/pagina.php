<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Pagina extends CI_Controller {
		
	function __construct(){
        parent::__construct();							
    }
	
	/*public function index($page = 'NULL')
	{
		
	}
	*/
	/**
	 * Muestra los términos de ls links del pie de página
	 */
	public function mostrar($page = 'NULL') {
		$data['title'] = "Páginas de Atención";
		$data['subtitle'] = "Páginas de Atención";
		$data['cargar_pagina'] = $page;
		$this->cargar_vista('', 'vistas_footer', $data);						
	}
	
	/**
	 * Vista de error
	 */
	
	
	/**
	 * Carga una vista especificada
	 */
	public function cargar_vista($folder, $page, $data){
		//Para automatizar un poco el desplieguee
		$this->load->view('templates/header', $data);		
		$this->load->view($folder.'/'.$page, $data);
		$this->load->view('templates/footer', $data);
	}	
}

/* End of file pagina.php */
/* Location: ./application/controllers/pagina.php */