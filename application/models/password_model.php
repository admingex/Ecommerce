 <?php

class Password_model extends CI_Model {

	
    function __construct(){
        // Call the Model constructor
        parent::__construct();
    }
    
	function revisa_mail($email = ''){
					
		$qry = "SELECT * 
				FROM CMS_IntCliente
				WHERE email = '".$email."'";
		$res = $this->db->query($qry);
		
		return $res;
				
	}
	
	function guardar_clave_temporal($id_cliente, $clave){
		$this->db->where(array('id_clienteIn' => $id_cliente));
		$resultado = $this->db->update('CMS_IntCliente', array('clave_temporalVc' => $clave));
		if($resultado) {
			return 1;
		} 
		else {
			return 0;
		}
	}
	
	function guarda_actividad_historico($id_cliente,$clave, $actividad, $time){		
		$campos = array('id_clienteIn' 	=> 	$id_cliente, 
						'claveVc' => $clave,
						'id_tipoActividadSi'=>$actividad,
						'timestampTs'=>$time);
		$this->db->insert('CMS_IntHistoricoCliente', $campos);		
	}
	
	function cambia_password($id_cliente,$email,$password){
		$pass = md5($email.'|'.$password);
		$this->db->where(array('id_clienteIn' => $id_cliente));
		$resultado = $this->db->update('CMS_IntCliente', array('clave_temporalVc' => NULL, 'password'=>$pass));
		if($resultado) {
			return 1;
		} 
		else {
			return 0;
		}	
	}		
	function obtiene_cliente($clave_temporal){					
			$qry2 = "SELECT *
					FROM CMS_IntCliente
					WHERE clave_temporalVc = '".$clave_temporal."'";
			$res = $this->db->query($qry2);	
			return $res;									
	}
	
	function historico_clave($id_cliente, $email, $passw){		
		$qry = "SELECT *
				FROM CMS_IntHistoricoCliente
				WHERE id_clienteIn = '".$id_cliente."' && id_tipoActividadSi='3'";
		$res = $this->db->query($qry);
				
		if(($res->num_rows()>0)&& ($res->num_rows()<8)){
			$pass = md5($email.'|'.$passw);
			foreach($res->result_array() as $clave){
				if($clave['claveVc']==$pass){
					return 1;
				}				
			}													
		}
		else{
			return 0;
		}
		
	}
	
	function registrar_cliente($cliente = array()){   	
    	$m5_pass = md5($cliente['email'].'|'.$cliente['password']);	
    	$cliente['password'] = $m5_pass;
        $res = $this->db->insert('CMS_IntCliente', $cliente);		    
        return $res;	
    }		
		
}