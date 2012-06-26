<?php

class Direccion_Facturacion_model extends CI_Model {
	
    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
    }
    
	function listar_razon_social($id_cliente){		
		$resultado = $this->db->get_where('CMS_IntRazonSocial', array('id_clienteIn'=>$id_cliente, 'id_estatusSi !=' =>2));								
		return $resultado->result_array();      		
    }
	
	function listar_direcciones($id_cliente){	
		$this->db->select('id_consecutivoSi, address_type, id_clienteIn, company, 
			tax_id_number as rfc,
			address1 as calle, 
			address2 as num_ext, 
			address3 as colonia, 
			address4 as num_int,
			zip as cp, 
			state as estado, 
			city as ciudad, 
			phone as telefono, 
			codigo_paisVc as pais,
			id_estatusSi,
			email');
			
		$this->db->where(array('id_clienteIn'=> $id_cliente, 'id_estatusSi !=' => 2, 'address_type'	=>	1));	//2 es deshabilitado 2 direccion facturacion		        		
		$resultado = $this->db->get('CMS_IntDireccion');
               
		return $resultado;           
    }
	
	function existe_direccion($datos){		
		$campos = array('id_clienteIn' 	=> 	$datos['id_clienteIn'], 
						'address_type' => $datos['address_type'],
						'address1' => $datos['address1'],		//calle
						'address2' 	=> 	$datos['address2'],		//numero ext
						'address3' => 	$datos['address3'],		//colonia
						'address4' =>	$datos['address4'],		//num int
						'zip' =>	$datos['zip'],				//cp
						'state' =>	$datos['state'],
						'city' =>	$datos['city']				
						);
										
		$resultado = $this->db->get_where('CMS_IntDireccion', $campos);
		
		if($resultado->num_rows() == 0){
			return FALSE;
		} 
		else {
			return TRUE;
		}
	}
	
	function listar_paises_think() {
		$this->db->select('country_code2 as id_pais, country_name as pais');					
		return $resultado = $this->db->get('CMS_CatPaisThink');	
	}
	
	
	/**
	 * Devuelve el consecutivo actual de la direccion del cliente
	 */
	function get_consecutivo($id_cliente) {
		$this->db->select_max('id_consecutivoSi', 'consecutivo');
		$resultado = $this->db->get_where('CMS_IntDireccion', array('id_clienteIn' => $id_cliente));
		$row = $resultado->row();	//regresa un objeto
		
		if(!$row->consecutivo)	{//si regresa null
			return 0;
		} else {
			return $row->consecutivo;	
		}		
	}
	
	function insertar_direccion($direccion){
		$datos2=array('address1'=>$direccion['address1'],
		              'address2'=>$direccion['address2'], 
		              'zip'=>$direccion['zip'], 
		              'address3'=>$direccion['address3'],
		              'city'=>$direccion['city'],
		              'state'=>$direccion['state'],
		              'address4'=>$direccion['address4'],
		              'codigo_paisVc'=>$direccion['codigo_paisVc'],
		              'id_clienteIn'=>$direccion['id_clienteIn'],		              
		              'address_type'=>$direccion['address_type']
					  );
										
		$resultado = $this->db->get_where('CMS_IntDireccion', $datos2);		
		if($resultado->num_rows() == 0){
			$resultado = $this->db->insert('CMS_IntDireccion', $direccion);		//true si se inserta					     
        	$id=$direccion['id_consecutivoSi'];
		}	
		else{			
			$id=$resultado->row()->id_consecutivoSi;
		}			
		return $id;  
						                                                                                                        		
    }
	
	function insertar_rs_direccion($dr){		
		$resultado = $this->db->insert('CMS_RelDireccionRazonSocial', $dr);		//true si se inserta                
        return $resultado;
	}
	
	function insertar_rs($datos){
		$datos2=array('tax_id_number'=>$datos['tax_id_number'], 'company'=>$datos['company'], 'email'=>$datos['email'], 'id_clienteIn'=>$datos['id_clienteIn']);
		// Busco si el dato existe lo selecciono								
		$res = $this->db->get_where('CMS_IntRazonSocial', $datos2);	
		if($res->num_rows()==0){
			$resultado = $this->db->query("INSERT INTO CMS_IntRazonSocial (tax_id_number, company, email, id_estatusSi, id_clienteIn )VALUES (?,?,?,?,?)",$datos);		//true si se inserta		     
        	$id=$this->db->insert_id();
		}	
		else{			
			$id=$res->row()->id_razonSocialIn;
		}			
		return $id;
	}
	
	function get_consecutivo_rs($id_cliente) {		
		$resultado = $this->db->get_where('CMS_IntRazonSocial', array('id_clienteIn' => $id_cliente, 'id_estatusSi !='=>2));		
		return $resultado->num_rows();				
	}
	
	function eliminar_direccion($id_cliente,$id_consecutivo){
		$this->db->where(array(	'id_consecutivoSi' => $id_consecutivo, 'id_clienteIn' => $id_cliente));
		$resultado = $this->db->update('CMS_IntDireccion', array('id_estatusSi' => 2));
		if($resultado) {
			return "La dirección ha sido eliminada exitosamente de tu cuenta";
		} else {
			return "Hubo un error al eliminar tu dirección. Por favor intenta de nuevo.";
		}				
	}	
	
	function eliminar_rs($id_rs){
		$this->db->where(array(	'id_razonSocialIn' => $id_rs));
		$resultado = $this->db->update('CMS_IntRazonSocial', array('id_estatusSi' => 2));
		if($resultado) {
			return "RFC eliminado exitosamente de tu cuenta";
		} else {
			return "Hubo un error al eliminar tu RFC. Por favor intenta de nuevo.";
		}		
	}	
	
	function obtener_direccion($id_cliente, $id_consecutivo) {
		$this->db->select('id_consecutivoSi, address_type, id_clienteIn, company, 
			tax_id_number as rfc,
			address1 as calle, 
			address2 as num_ext, 
			address3 as colonia, 
			address4 as num_int,
			zip as cp, 
			state as estado, 
			city as ciudad, 
			codigo_paisVC as pais,	
			id_estatusSi as estatus,		
			email');
			
		$this->db->where(array(	'id_consecutivoSi' => $id_consecutivo, 'id_clienteIn' => $id_cliente));		        		
		$resultado = $this->db->get('CMS_IntDireccion');
		$row = $resultado->row();     
		return $row;  		
	}
	
	function obtiene_rs_dir($id_cliente, $id_rs){
		$resultado = $this->db->get_where('CMS_RelDireccionRazonSocial', array('id_razonSocialIn'=>$id_rs, 'id_clienteIn'=>$id_cliente));
		return $resultado;		
	}
	
	function obtener_rs($id_rs) {
		$resultado = $this->db->get_where('CMS_IntRazonSocial', array('id_razonSocialIn'=>$id_rs));				
		$row = $resultado->row();     
		return $row;  		
	}
	
	function busca_relacion($cte, $rs, $ds){
		$resultado = $this->db->get_where('CMS_RelDireccionRazonSocial', 
										   array('id_razonSocialIn'=>$rs,
										         'id_clienteIn'=>$cte,
										         'id_consecutivoSi'=> $ds, 
										   )
										 );
		return $resultado;								 
	}
	
	function actualizar_direccion($id_cliente, $id_consecutivo,$datos){		
		$this->db->where(array(	'id_consecutivoSi' => $id_consecutivo, 'id_clienteIn' => $id_cliente));
		$resultado = $this->db->update('CMS_IntDireccion', $datos);
		if($resultado) {
			return "Tu dirección ha sido actualizada exitosamente.";
		} 
		else {
			return "Hubo un error al actualizar tu dirección. Por favor intenta de nuevo.";
		}	
	}		
	
	function actualizar_rs($id_rs,$datos){		
		$this->db->where(array(	'id_razonSocialIn' => $id_rs));
		$resultado = $this->db->update('CMS_IntRazonSocial', $datos);
		if($resultado) {
			return "Tu RFC ha sido actualizado exitosamente.";
		} 
		else {
			return "Hubo un error al actualizar tu RFC. Por favor intenta de nuevo.s";
		}	
	}
	
	function establecer_predeterminado($id_cliente, $consecutivo){
		$this->db->where(array('id_clienteIn' => $id_cliente, 'address_type'=>1, 'id_estatusSi !='=>2));
		$resultado = $this->db->update('CMS_IntDireccion', array('id_estatusSi' => 1));
		
		$this->db->where(array('id_clienteIn' => $id_cliente, 'id_consecutivoSi'=>$consecutivo));
		$resultado = $this->db->update('CMS_IntDireccion', array('id_estatusSi' => 3));				
	}	
	
	function establecer_predeterminado_rs($id_cliente, $id_rs){
		$this->db->where(array('id_clienteIn' => $id_cliente, 'id_estatusSi !='=>2));
		$resultado = $this->db->update('CMS_IntRazonSocial', array('id_estatusSi' => 1));
		
		$this->db->where(array('id_clienteIn' => $id_cliente, 'id_razonSocialIn'=>$id_rs));
		$resultado = $this->db->update('CMS_IntRazonSocial', array('id_estatusSi' => 3));				
	}	
	
	function get_pago_express($id_cliente) 
	{
		$this->db->select('id_consecutivoSi as consecutivo');
		$res = $this->db->get_where('CMS_IntDireccion',
								array('id_clienteIn' => $id_cliente,
										'address_type' => 1,//self::$TIPO_DIR['BISINESS'],
										'id_estatusSi' => 3));//self::$CAT_ESTATUS['DEFAULT']));

		if ($res->num_rows() == 0) {
			//echo "no hay direccion para pago express: ";
			
			//entonces recupera la primer tarjeta activa
			$this->db->select_min('id_consecutivoSi', 'consecutivo');
			$res = $this->db->get_where('CMS_IntDireccion',
								array('id_clienteIn' => $id_cliente,
										'address_type' => 1,//self::$TIPO_DIR['RESIDENCE'],
										'id_estatusSi' => 1));//self::$CAT_ESTATUS['HABILITADA']));
		}

		$row_res = $res->row();
		return $row_res;
	}
	
	function get_pago_express_rs($id_cliente)
	{
		$this->db->select('id_razonSocialIn as consecutivo');
		$res = $this->db->get_where('CMS_IntRazonSocial',
								array('id_clienteIn' => $id_cliente,
										'id_estatusSi' => 3));//self::$CAT_ESTATUS['DEFAULT']));

		if ($res->num_rows() == 0) {
			//echo "no hay direccion para pago express: ";
			
			//entonces recupera la primer tarjeta activa
			$this->db->select_min('id_razonSocialIn', 'consecutivo');
			$res = $this->db->get_where('CMS_IntRazonSocial',
								array('id_clienteIn' => $id_cliente,
										'id_estatusSi' => 1));//self::$CAT_ESTATUS['HABILITADA']));
		}

		$row_res = $res->row();
		return $row_res;
	}
}