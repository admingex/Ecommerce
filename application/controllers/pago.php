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
		}	  
		else{
			$this->session->set_userdata('guidx', com_create_guid());
			$this->session->set_userdata('guidz', com_create_guid());
			echo "<form name='realizar_pago' action='".site_url()."/api/1/1/1189/pago' method='POST'>
			      	  <input type='text' name='guidx' value='".$this->session->userdata('guidx')."' size='70'/>
			          <input type='text' name='guidz' value='".$this->session->userdata('guidz')."' size='70'/>
			          <input type='submit' name='enviar' value='Enviar' />
		          </form>";
		    echo "<br />";      
		    echo "<form name='realizar_pago' action='".site_url()."/api/1/1/1190/pago' method='POST'>
			      	  <input type='text' name='guidx' value='".$this->session->userdata('guidx')."' size='70'/>
			          <input type='text' name='guidz' value='".$this->session->userdata('guidz')."' size='70'/>
			          <input type='submit' name='enviar' value='Enviar' />
		          </form>";      
		}		
		
		echo "<br />";
		echo $textoenc=$this->encrypt("857|giovanni@correo.com|d73d7a9195fef42befee33466b81d7b2", "GGee00**");
		echo "<br />";
		echo $this->decrypt($textoenc,"GGee00**");
			
							
	}	

	public function encrypt($str, $key){
    	$block = mcrypt_get_block_size('des', 'ecb');
    	$pad = $block - (strlen($str) % $block);
    	$str .= str_repeat(chr($pad), $pad);
    	return mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $str, MCRYPT_MODE_ECB);
	}

	public function decrypt($str, $key){
    	$str = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $str, MCRYPT_MODE_ECB);
    	$block = mcrypt_get_block_size('des', 'ecb');
    	$pad = ord($str[($len = strlen($str)) - 1]);
    	return substr($str, 0, strlen($str) - $pad);
	}
		
}

/* End of file pago.php */
/* Location: ./application/controllers/pago.php */