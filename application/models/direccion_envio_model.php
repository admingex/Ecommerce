<?php

class Direccion_Envio_model extends CI_Model {
	//catálogo de estatus para comparaciones***
	/**
	 * 
	 */
	public static $CAT_ESTATUS = array(
		"HABILITADA"	=> 1, 
		"DESHABILITADA"	=> 2, 
		"DEFAULT"		=> 3
	);
	
	public static $TIPO_DIR = array(
		"RESIDENCE"	=> 0, 
		"BUSINESS"	=> 1, 
		"OTHER"		=> 2
	);
	
	
    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
    }
    
	/*
	 * Devuelve el listado de las direcciones 
	 * */
	function listar_direcciones($id_cliente)
    {	
		$this->db->select('id_consecutivoSi, address_type, id_clienteIn, 
			address1 as calle, 
			address2 as num_ext,
			address4 as num_int, 
			address3 as colonia,
			zip as cp, 
			state as estado, 
			city as ciudad,
			codigo_paisVc as pais, 
			phone as telefono,
			id_estatusSi,
			email');
		$this->db->where(array(	'id_clienteIn' => $id_cliente, 
								'id_estatusSi != ' => self::$CAT_ESTATUS['DESHABILITADA'],	//2
								'address_type ' => self::$TIPO_DIR['RESIDENCE'])			//Dir_ envio
							  );
		$resultado = $this->db->get('CMS_IntDireccion');
        //echo $resultado->num_rows();
		return $resultado;
        //return $query->result();
    }
	
	/**
	 * Verifica que la dirección no esté duplicada
	 */
	function existe_direccion($datos_dir)
	{
		$campos = array('id_clienteIn' 	=> 	$datos_dir['id_clienteIn'], 
						'address_type' => $datos_dir['address_type'],
						'address1' => $datos_dir['address1'],		//calle
						'address2' 	=> 	$datos_dir['address2'],		//numero ext
						'address3' => 	$datos_dir['address3'],		//colonia
						'address4' =>	$datos_dir['address4'],		//num int
						'zip' =>	$datos_dir['zip'],				//cp
						'state' =>	$datos_dir['state'],
						'city' =>	$datos_dir['city'],
						'codigo_paisVc' =>	$datos_dir['codigo_paisVc'],
						'id_estatusSi !=' => self::$CAT_ESTATUS['DESHABILITADA']);
						
		$resultado = $this->db->get_where('CMS_IntDireccion', $campos);
		
		if($resultado->num_rows() > 0)
		{
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	/**
	 * Devuelve la lista de paises del catálogo de Think
	 * */
	function listar_paises_think() {
		$this->db->select('country_code2 as id_pais, country_name as pais');		
		return $resultado = $this->db->get('CMS_CatPaisThink');
	}
	
	/**
	 * Deshabilita de manera lógica la tarjeta especificada del cliente 
	 */
	function eliminar_tarjeta($id_cliente, $consecutivo)
	{
		$this->db->where(array(	'id_consecutivoSi' => $consecutivo, 'id_clienteIn' => $id_cliente));
		$resultado = $this->db->update('CMS_IntDireccion', array('id_estatusSi' => 2));
		if($resultado) {
			return "Tarjeta eliminada.";
		} else {
			return "Error al tratar de eliminar la tarjeta.";
		}
	}
	
	
	/**
	 * Actualiza la tarjeta especificada del cliente
	 */
	function actualiza_tarjeta($consecutivo, $id_cliente, $nueva_info)
	{
		$this->db->where(array(	'id_consecutivoSi' => $consecutivo, 'id_clienteIn' => $id_cliente));
		$resultado = $this->db->update('CMS_IntDireccion', $nueva_info);
		//echo "resultado".$resultado;
		if($resultado) {
			//echo "Tarjeta actualizada.";
			return "Tarjeta actualizada.";
		} else {
			return "Error al tratar de actualizar la tarjeta.";
		}
	}
	
	/**
	 * Devuelve el detalle de la dirección
	 */
	function detalle_direccion($id_consecutivoSi, $id_cliente)
	{
		$resultado = $this->db->get_where('CMS_IntDireccion', 
								array(	'id_consecutivoSi' => $id_consecutivoSi,
										 'id_clienteIn' => $id_cliente));	
		$row_res = $resultado->row();
		return $row_res;
	}
	
	/**
	 * Devuelve el máximo consecutivo actual del cliente
	 */
	function get_consecutivo($id_cliente) 
	{
		$this->db->select_max('id_consecutivoSi', 'consecutivo');
		$resultado = $this->db->get_where('CMS_IntDireccion', array('id_clienteIn' => $id_cliente));
		$row = $resultado->row();	//regresa un objeto
		
		if(!$row->consecutivo)	{//si regresa null
			return 0;
		} else {
			return $row->consecutivo;	
		}
		/*
		 * consulta en mysql
		 * SELECT IfNULL(MAX(id_consecutivoSi), 0) as consecutivo
			FROM CMS_IntDireccion
			WHERE id_clienteIn = 2;
		*/
		
	}
	
	function insertar_direccion($direccion)
    {
    	//var_dump($direccion);
        $resultado = $this->db->insert('CMS_IntDireccion', $direccion);		//true si se inserta
        return $resultado;
    }
	
}