 <?php

class Recordar_Password_model extends CI_Model {

	
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
		$qry = "SELECT *
				FROM CMS_Inthistoricocliente
				WHERE claveVc = '".$clave_temporal."'";
		$res = $this->db->query($qry);
		$row = $res->row();
		
		$qry2 = "SELECT *
				FROM CMS_IntCliente
				WHERE id_clienteIn = '".$row->id_clienteIn."'";
		$res2 = $this->db->query($qry2);
		$row2= $res2->row();
		
		return $row2;
	}
	
	function obtiene_numero_historicos(){
					
	}	
	
	function registrar_cliente($cliente = array()){   	
    	$m5_pass = md5($cliente['email'].'|'.$cliente['password']);	
    	$cliente['password'] = $m5_pass;
        $res = $this->db->insert('CMS_IntCliente', $cliente);		    
        return $res;	
    }
		
}