<?php

class Direccion_Facturacion_model extends CI_Model {
	
    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
    }
    
	function listar_razon_social($id_cliente){					        		
		$resultado = $this->db->get_where('CMS_RelDireccionRazonSocial', array('id_clienteIn'=>$id_cliente));               
		$res=array();
		foreach($resultado->result_array() as $relrsdir){			 
			$resultado2 = $this->db->get_where('CMS_IntRazonSocial', array('id_razonSocialIn'=>$relrsdir['id_razonSocialIn'], 'id_estatusSi !=' =>2));
			if($resultado2->num_rows()!=0){
				$res[]=$resultado2->row();	
			}							
		}						
		return $res;     		      
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
	 * Devuelve el consecutivo actual del cliente
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
        $resultado = $this->db->insert('CMS_IntDireccion', $direccion);		//true si se inserta                
        return $resultado;
    }
	
	function insertar_rs_direccion($dr){
		$resultado = $this->db->insert('CMS_RelDireccionRazonSocial', $dr);		//true si se inserta                
        return $resultado;
	}
	
	function insertar_rs($datos){
		$resultado = $this->db->insert('CMS_IntRazonSocial', $datos);		//true si se inserta        
        return $resultado;
	}
	
	function eliminar_direccion($id_cliente,$id_consecutivo){
		$this->db->where(array(	'id_consecutivoSi' => $id_consecutivo, 'id_clienteIn' => $id_cliente));
		$resultado = $this->db->update('CMS_IntDireccion', array('id_estatusSi' => 2));
		if($resultado) {
			return "Direccion eliminada";
		} else {
			return "Error al tratar de eliminar la direccion.";
		}				
	}	
	
	function eliminar_rs($id_rs){
		$this->db->where(array(	'id_razonSocialIn' => $id_rs));
		$resultado = $this->db->update('CMS_IntRazonSocial', array('id_estatusSi' => 2));
		if($resultado) {
			return "razon social eliminada";
		} else {
			return "Error al tratar de eliminar la razon social.";
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
			return "actualizacion.";
		} 
		else {
			return "Error al tratar de actualizar la tarjeta.";
		}	
	}		
	
	function actualizar_rs($id_rs,$datos){		
		$this->db->where(array(	'id_razonSocialIn' => $id_rs));
		$resultado = $this->db->update('CMS_IntRazonSocial', $datos);
		if($resultado) {
			return "actualizacion.";
		} 
		else {
			return "Error al tratar de actualizar la tarjeta.";
		}	
	}
	
	function quitar_predeterminado($id_cliente){
		$this->db->where(array('id_clienteIn' => $id_cliente, 'address_type'=>1));
		$resultado = $this->db->update('CMS_IntDireccion', array('id_estatusSi' => 1));
		if($resultado) {
			return "actualizacion.";
		} 
		else {
			return "Error al tratar de actualizar la tarjeta.";
		}
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
}