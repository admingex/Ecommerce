<?php
//include ('DTO/Tc_DTO.php');

class Login_Registro_model extends CI_Model {

    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
    }
    
	function verifica_cliente($email = '', $password = '')
	{
		$m5_pass = md5($email.'|'.$password);		//encriptaciónn definida en el registro de usuarios
		$qry = "SELECT id_clienteIn as id_cliente, salutation as nombre 
				FROM CMS_IntCliente
				WHERE email = '".$email."' AND password = '".$m5_pass."'
				LIMIT 1";
		$res = $this->db->query($qry);
		
		//echo $res->num_rows()." qey: ". $qry;
		//exit();
		
		return $res;
		/*
		if ($res->num_rows() > 0) {
				//return true;	//"Usuario logeado";
		} else {
			return false;	//"Usuario no registrado";
		}
		//$res->row();
		 * */
	}
	
	function verifica_registro_email($email='') {
		//verificar que el email no esté registrado
		$qry = "SELECT email 
				FROM CMS_IntCliente
				WHERE email = '".$email."' LIMIT 1";
		$res = $this->db->query($qry);
		
		//echo $res->num_rows()." qey: ". $qry;
		//exit();
		
		return $res;
	}
	
	function registrar_cliente($cliente = array())
    {
    	//var_dump($cliente);
    	$m5_pass = md5($cliente['email'].'|'.$cliente['password']);		//encriptaciónn definida en el registro de usuarios
    	$cliente['password'] = $m5_pass;
        $res = $this->db->insert('CMS_IntCliente', $cliente);		//true si se inserta
        //echo '<bt/>Resultado: '.$res;
        return $res;	//true_false
    }
	
	function next_cliente_id()
	{
		$qry = "SELECT MAX(id_clienteIn) as consecutivo 
				FROM CMS_IntCliente";
		$res = $this->db->query($qry);
		
		$row = $res->row();	//regresa un objeto
		
		if(!$row->consecutivo)	{//si regresa null
			return 0;
		} else {
			return $row->consecutivo + 1;	
		}
	}
}