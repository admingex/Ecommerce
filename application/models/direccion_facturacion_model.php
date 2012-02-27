<?php
include ('DTO/Tc_DTO.php');

class Direccion_Facturacion_model extends CI_Model {
	
    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
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
			email');
			
		$this->db->where(array('id_clienteIn'=> $id_cliente, 'id_estatusSi !=' => 2, 'address_type'	=>	1));	//2 es deshabilitado 2 direccion facturacion		        		
		$resultado = $this->db->get('CMS_IntDireccion');
               
		return $resultado;           
    }
	
	function existe_direccion($datos_tc){
		$campos = array('nombre_titularVc' 	=> 	$datos_tc['nombre_titularVc'], 
						'apellidoP_titularVc' => $datos_tc['apellidoP_titularVc'],
						'apellidoM_titularVc' => $datos_tc['apellidoM_titularVc'],
						'mes_expiracionVc' 	=> 	$datos_tc['mes_expiracionVc'],
						'anio_expiracionVc' => 	$datos_tc['anio_expiracionVc'],
						'terminacion_tarjetaVc' =>	$datos_tc['terminacion_tarjetaVc'],
						'id_tipo_tarjetaSi'	=>	$datos_tc['id_tipo_tarjetaSi'],						
						'id_estatusSi !=' => 2);
						
		$resultado = $this->db->get_where('CMS_IntDireccion', $campos);
		
		if($resultado->num_rows() > 0){
			return TRUE;
		} 
		else {
			return FALSE;
		}
	}
	
	function listar_paises_think() {
		$this->db->select('country_code2 as id_pais, country_name as pais');					
		return $resultado = $this->db->get('CMS_CatPaisThink');	
	}
	
	
	/**
	 * Devuelve el consecutivo actual del cliente
	 */
	function get_consecutivo($id_cliente){
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
	
	function eliminar_direccion($id_cliente,$id_consecutivo){
		$this->db->where(array(	'id_consecutivoSi' => $id_consecutivo, 'id_clienteIn' => $id_cliente));
		$resultado = $this->db->update('CMS_IntDireccion', array('id_estatusSi' => 2));
		if($resultado) {
			return "Tarjeta eliminada.";
		} else {
			return "Error al tratar de eliminar la tarjeta.";
		}		
	}	
	
	function obtener_direccion($id_cliente, $id_consecutivo){
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
}