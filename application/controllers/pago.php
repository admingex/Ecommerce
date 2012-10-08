<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Pago extends CI_Controller {
		
	function __construct(){
        parent::__construct();						
					
		if(array_key_exists('user', $this->session->all_userdata()) || array_key_exists('pass', $this->session->all_userdata()) || array_key_exists('user', $_POST) || array_key_exists('pass', $_POST)){
			if(($this->session->userdata('user')=='aespinosa') || ($_POST['user']=='aespinosa') || ($_POST['user']=='mercadotecnia')){
				if(($this->session->userdata('pass')=='Aesp1n0_20120618') || ($_POST['pass']=='Aesp1n0_20120618') || ($_POST['pass']=='m3rc4d0t3cn14')){
					$this->session->set_userdata('user', 'aespinosa');
					$this->session->set_userdata('pass', 'Aesp1n0_20120618');
					$this->pago();				
				} else {
					redirect('mensaje/'.md5(6));
				}
			} else {
				redirect('mensaje/'.md5(6));
			}
		} else{
			redirect('mensaje/'.md5(6));
		}
    }
	
	public function index() {  	             	        
													
	}
	
	public function pago() {
		$data['title'] = "Proceso de Pago";
		$this->session->set_userdata('guidz', $this->guid());
		$this->load->view('templates/header', $data);
		$this->load->view('links_pago');
		$this->load->view('templates/footer');
	}
	
	public function guid() {
    	if (function_exists('com_create_guid')) {
        	return com_create_guid();
    	} else {
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

	public function encrypt($str, $key) {
    	$block = mcrypt_get_block_size('des', 'ecb');
    	$pad = $block - (strlen($str) % $block);
    	$str .= str_repeat(chr($pad), $pad);
    	return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $str, MCRYPT_MODE_ECB));
	}
	
	public function decrypt($str, $key) {
		$str = base64_decode($str);
    	$str = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $str, MCRYPT_MODE_ECB);
    	$block = mcrypt_get_block_size('des', 'ecb');
    	$pad = ord($str[($len = strlen($str)) - 1]);
    	return substr($str, 0, strlen($str) - $pad);
	}
		
}

/* End of file pago.php */
/* Location: ./application/controllers/pago.php */