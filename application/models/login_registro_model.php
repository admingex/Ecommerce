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
		$qry = "SELECT id_clienteIn as id_cliente, salutation as nombre, email, password
				FROM CMS_IntCliente
				WHERE email = '".$email."' AND password = '".$m5_pass."'
				LIMIT 1";
		$res = $this->db->query($qry);
		
		//echo $res->num_rows()." qey: ". $qry;
		
		return $res;
	}
	
	function verifica_registro_email($email='') {
		//verificar que el email no esté registrado
		$qry = "SELECT id_clienteIn, email, LastLockoutDate 
				FROM CMS_IntCliente
				WHERE email = '".$email."' LIMIT 1";
		$res = $this->db->query($qry);
		
		//echo $res->num_rows()." qey: ". $qry;
		//exit();
		
		return $res;
	}
	
	function registro_cliente($datos_cliente='') {		
		
		$qry = "INSERT INTO CMS_IntCliente (id_clienteIn, salutation, fname, lname, email, password)
				VALUES (".$datos_cliente['id_clienteIn'].", '".$datos_cliente['salutation']."', '".$datos_cliente['fname']."', '".$datos_cliente['lname']."', '".$datos_cliente['email']."', '".$datos_cliente['password']."')";
		$res = $this->db->query($qry);
		
		return (int)$res;
	}
	
	function registrar_cliente($cliente = array())
    {
    	$nuevo_res='';
    	$this->db->trans_start();	//begin Trans
    	/*
    	$qry = "SELECT MAX(id_clienteIn) as consecutivo 
				FROM CMS_IntCliente";
		$res = $this->db->query($qry);
		
		$row = $res->row();	//regresa un objeto
		$cliente['id_clienteIn']= ($row->consecutivo+1);
		$res->free_result();
		*/
				 		    						
        $nuevo_res= $this->db->insert('CMS_IntCliente', $cliente);		//true si se inserta
        
        $this->db->trans_complete();
        return (int)$nuevo_res;        	
                
				
    }
	
	function next_cliente_id()
	{
		$qry = "SELECT MAX(id_clienteIn) as consecutivo 
				FROM CMS_IntCliente";
		$res = $this->db->query($qry);
		
		$row = $res->row();	//regresa un objeto
		
		if(!$row->consecutivo)	{//si regresa null
			return 1;
		} else {
			return $row->consecutivo + 1;	
		}
	}
	
	function obtiene_numero_intentos($id_cliente) {
		$query = "SELECT FailedPasswordAttemptCount FROM CMS_IntCliente WHERE id_clienteIn='".$id_cliente."'";
		$res = $this->db->query($query);
		return $res->row()->FailedPasswordAttemptCount;
	}
	
	function suma_intento_fallido($id_cliente, $num_intentos, $t) {						
		$numin = $num_intentos + 1;		
		$query2 = "UPDATE  CMS_IntCliente SET FailedPasswordAttemptCount='".$numin."', LastLockoutDate='".$t."'  WHERE id_clienteIn='".$id_cliente."'";
		$res2 = $this->db->query($query2);
		return $res2;				
	}
	
	function desbloquear_cuenta($id_cliente) {								
		$query2 = "UPDATE  CMS_IntCliente SET FailedPasswordAttemptCount=NULL, LastLockoutDate=NULL  WHERE id_clienteIn='".$id_cliente."'";
		$res2 = $this->db->query($query2);
		return $res2;				
	}
	
	function obtener_cliente_id($id_cliente){
							
		$qry = "SELECT id_clienteIn, salutation as nombre, fname as apellido_paterno, lname as apellido_materno, email, password  
				FROM CMS_IntCliente
				WHERE id_clienteIn = ".$id_cliente;
		$res = $this->db->query($qry);		
		return $res;
					
	}
	
	function actualizar_cliente($datos){
		if(array_key_exists('password', $datos)){
			$datos['password'] = md5($datos['email']."|".$datos['password']);	
		}
		
		$this->db->where(array(	'id_clienteIn' => $datos['id_clienteIn']));
		$resultado = $this->db->update('CMS_IntCliente', $datos);
		return $resultado;		
	}
}