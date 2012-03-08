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
		echo 
		$campos = array('id_clienteIn' 	=> 	$id_cliente, 
						'claveVc' => $clave,
						'id_tipoActividadSi'=>$actividad,
						'timestampTs'=>$time);
		$this->db->insert('CMS_IntHistoricoCliente', $campos);		
	}
	
	function registrar_cliente($cliente = array()){
    	//var_dump($cliente);
    	$m5_pass = md5($cliente['email'].'|'.$cliente['password']);		//encriptaciónn definida en el registro de usuarios
    	$cliente['password'] = $m5_pass;
        $res = $this->db->insert('CMS_IntCliente', $cliente);		//true si se inserta
        //echo '<bt/>Resultado: '.$res;
        return $res;	//true_false
    }
		
}