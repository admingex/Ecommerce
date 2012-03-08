<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Recordar_Password extends CI_Controller {

	public static $TIPO_ACTIVIDAD = array(
		"BLOQUEO"=> 0, 
		"DESBLOQUEO"=> 1, 
		"SOLICITUD_PASSWORD"=>2,
		"CAMBIO_PASSWORD"=>3
	);	
	
	var $title = 'Recordar Password'; 		// Capitalize the first letter
	var $subtitle = 'Recordar Password'; 	// Capitalize the first letter
	var $login_errores = array();
	private $email;
	private $password;
	
	function __construct()
    {
        // Call the Model constructor
        parent::__construct();
		
		//cargar el modelo en el constructor
		$this->load->model('recordar_password_model', 'modelo', true);
		//la sesion se carga automáticamente
    }
	
	public function index(){
		$data['title'] = $this->title;
		$data['subtitle'] = $this->subtitle;
		$data['enviado']=FALSE;
		$data['mensaje']='';		
		$script_file = "<script type='text/javascript' src='". base_url() ."js/registro.js'> </script>";
		$data['script'] = $script_file;			
		$this->cargar_vista('', 'recordar_password',$data);
	}	
	
	public function enviar(){
		$data['title'] = $this->title;
		$data['subtitle'] = $this->subtitle;
		$data['mensaje']='';
		$data['enviado']=FALSE;
		$script_file = "<script type='text/javascript' src='". base_url() ."js/registro.js'> </script>";
		$data['script'] = $script_file;	
		if($_POST){
			$datamail=$this->modelo->revisa_mail($_POST['email']);			
			if($datamail->num_rows()==1){
				$data['enviado'] = TRUE;				
				$data['cliente']=$datamail->row();
				$data['password_temporal']= $p = substr(md5(uniqid(rand( ), true)), 0,10);
				$this->load->helper('date');
				$data['timestamp']= $t= mdate('%Y/%m/%d %h:%i:%s',time());
				$this->modelo->guardar_clave_temporal($data['cliente']->id_clienteIn, $p);		
				$this->modelo->guarda_actividad_historico($data['cliente']->id_clienteIn, $p, self::$TIPO_ACTIVIDAD['SOLICITUD_PASSWORD'], $t);																										
				$this->cargar_vista('', 'recordar_password', $data);	
				//redirect('login');				
				
			}		
			else{
				$data['mensaje']='No se encuentra en nuestra base de datos';
				$this->cargar_vista('', 'recordar_password', $data);
			}
		}
		else{
			$this->cargar_vista('', 'recordar_password', $data);
		}
	}
	
	private function cargar_vista($folder, $page, $data){	
		//Para automatizar un poco el desplieguee
		$this->load->view('templates/header', $data);
		$this->load->view($folder.'/'.$page, $data);
		$this->load->view('templates/footer', $data);
	}
}

/* End of file login.php */
/* Location: ./application/controllers/recordar_password.php */