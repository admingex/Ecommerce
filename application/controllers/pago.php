<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Pago extends CI_Controller {
		
	function __construct(){
        parent::__construct();							
    }
	
	public function index(){				
		
		$guidy='{CE5480FD-AC35-4564-AE4D-0B881031F295}';
			  
		if($_POST){
			echo "X:".$this->session->userdata('guidx');
			echo "<br />";
			echo "POST X:".$_POST['guidx'];
			echo "<br />";
			echo "Y:".$guidy;
			echo "<br />";
			echo "POST Y:".$_POST['guidy'];
			echo "<br />";									
			echo "Z:".$this->session->userdata('guidz');
			echo "<br />";					
			echo "POST Z:".$_POST['guidz'];
			echo "<br />";
			echo "Status:".$status=$_POST['status'];
			echo "<br />";			
			echo "<br />";
			echo "Enviaremos:".md5($_POST['guidx'].$_POST['guidy'].$_POST['guidz'].$_POST['status']);
			echo "<br />";
			echo "comparar a fals:".md5($this->session->userdata('guidx').$guidy.$this->session->userdata('guidz').'0');
			echo "<br />";
			echo "comparar a true:".md5($this->session->userdata('guidx').$guidy.$this->session->userdata('guidz').'1');
			echo "<br />";
			echo "encriptado".$_POST['datos_login'];
			echo "<br />";
			echo "aqui".$this->decrypt($_POST['datos_login'],'AC35-4564-AE4D-0B881031F295');	
		}	  
		else{
			$this->session->set_userdata('guidx', $this->guid());
			$this->session->set_userdata('guidz', $this->guid());
			echo "<form name='realizar_pago' action='".site_url()."/api/1/1/1189/pago' method='POST'>
			      	  <input type='text' name='guidx' value='{2A629162-9A1B-11E1-A5B0-5DF26188709B}' size='70'/>
			          <input type='text' name='guidz' value='".$this->session->userdata('guidz')."' size='70'/>
			          <input type='submit' name='enviar' value='Enviar' />			          
		          </form>";
		    echo "<br />";      
		    echo "<form name='realizar_pago' action='".site_url()."/api/1/1/1190/pago' method='POST'>
			      	  <input type='text' name='guidx' value='{2A629162-9A1B-11E1-A5B0-5DF26188709B}' size='70'/>
			          <input type='text' name='guidz' value='".$this->session->userdata('guidz')."' size='70'/>
			          <input type='submit' name='enviar2' value='Enviar2' />
		          </form>";      
		}		
		
		echo "<br />";
		echo $textoenc=$this->encrypt("857|giovanni@correo.com|d73d7a9195fef42befee33466b81d7b2", "GGee00**");
		echo "<br />";
		echo $this->decrypt($textoenc,"GGee00**");										
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