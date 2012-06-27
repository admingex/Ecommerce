<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Pago extends CI_Controller {
		
	function __construct(){
        parent::__construct();	
								
    }
	
	public function index(){
		foreach (array_keys($this->session->userdata) as $key){
    		$this->session->unset_userdata($key);
		} 		
		$data['title']="Proceso de Pago";					  			
		$this->session->set_userdata('guidz', $this->guid());
		$this->load->view('templates/header', $data);	
		$this->load->view('links_pago');
		$this->load->view('templates/footer');   	             	        
													
	}	
	
	public function guid(){
    	if (function_exists('com_create_guid')){
        	return com_create_guid();
    	}
    	else{
        	mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
        	$charid = strtoupper(md5(uniqid(rand(), true)));
        	$hyphen = chr(45);// "-"
        	$uuid = chr(123)// "{"
                .substr($charid, 0, 8).$hyphen
                .substr($charid, 8, 4).$hyphen
                .substr($charid,12, 4).$hyphen
                .substr($charid,16, 4).$hyphen
                .substr($charid,20,12)
                .chr(125);// "}"
        	return $uuid;
    	}
	}


	public function encrypt($str, $key){
    	$block = mcrypt_get_block_size('des', 'ecb');
    	$pad = $block - (strlen($str) % $block);
    	$str .= str_repeat(chr($pad), $pad);
    	return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $str, MCRYPT_MODE_ECB));
	}
	
	public function decrypt($str, $key){
		$str=base64_decode($str);
    	$str = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $str, MCRYPT_MODE_ECB);
    	$block = mcrypt_get_block_size('des', 'ecb');
    	$pad = ord($str[($len = strlen($str)) - 1]);
    	return substr($str, 0, strlen($str) - $pad);
	}
		
}

/* End of file pago.php */
/* Location: ./application/controllers/pago.php */