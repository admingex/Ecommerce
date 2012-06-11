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
	 * Devuelve el listado de las direcciones registradas del cliente
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
	 * Verifica si el cliente tiene alguna dirección como predeterminada 
	 */
	function existe_predetereminada($id_cliente)
	{
		$args = array($id_cliente, self::$TIPO_DIR['RESIDENCE'], self::$CAT_ESTATUS['DEFAULT']);
		$qry = "SELECT 	id_consecutivoSi as consecutivo
				FROM 	CMS_IntDireccion 
				WHERE   id_clienteIn = ?
				AND     address_type = ?
				AND     id_estatusSi = ?";
		$res = $this->db->query($qry, $args);
		
		return $res->result();
	}
	
	/**
	 * Verifica que la dirección que se quiere registrar no esté duplicada
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
	 * Si existe alguna compra con este cliente
	 */
	function existe_compra($id_cliente)
	{
		$qry = "SELECT id_compraIn FROM CMS_IntCompra WHERE id_clienteIn = ? ";
						
		$res = $this->db->query($qry, array($id_cliente));
		
		if($res->num_rows() > 0)
		{
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	/**
	 * Quitar la dirección predeterminada actual
	 */
	function quitar_predeterminado($id_cliente) {
		$this->db->where(array
							( 'id_clienteIn' => $id_cliente,
							'id_estatusSi' => self::$CAT_ESTATUS['DEFAULT']));
		$resultado = $this->db->update('CMS_IntDireccion', array('id_estatusSi' => self::$CAT_ESTATUS['HABILITADA']));
	}
	 
	/**
	 * Devuelve la lista de países del catálogo de Think
	 * */
	function listar_paises_think() {
		$this->db->select('country_code2 as id_pais, country_name as pais');
		$this->db->order_by('pais', 'asc');		
		return $resultado = $this->db->get('CMS_CatPaisThink');
	}

	/**
	 * Devuelve la lista de Estados del Catalogo Local de SEPOMEX //
	 * */
	function listar_estados_sepomex() {
		$this->db->select('EDO as clave_estado, ESTADO as estado');
		$this->db->order_by('estado asc');		
		return $resultado = $this->db->get('CMS_CatEstado');
	}

	/**
	 * Devuelve la lista de Ciudades del Catalogo local de SEPOMEX //
	 * */
	function listar_ciudades_sepomex($cve_estado) {
		$this->db->select('CIUDAD as clave_ciudad, CIUDAD as ciudad');
		$this->db->from('CMS_CatCiudad');
		$this->db->join('CMS_CatEstado', 'CMS_CatEstado.cve_estado = CMS_CatCiudad.cve_estado');
		$this->db->where('CMS_CatEstado.EDO', $cve_estado);
		$this->db->order_by('ciudad asc');
		$this->db->distinct();
		return $resultado = $this->db->get();
	}
	
	/**
	 * Devuelve la lista de Colonias del Catalogo local de SEPOMEX //
	 * */
	function listar_colonias_sepomex($cve_estado, $cve_ciudad) {
		$this->db->select('CMS_CatEstado.EDO as estado, CMS_CatCiudad.CIUDAD as ciudad, CMS_CatCodigoPostal.COLONIA AS colonia, CMS_CatCodigoPostal.ZIP as codigo_postal');
		$this->db->from('CMS_CatCodigoPostal');
		$this->db->join('CMS_CatCiudad', 'CMS_CatCiudad.cve_ciudad = CMS_CatCodigoPostal.cve_ciudad');
		$this->db->join('CMS_CatEstado', 'CMS_CatEstado.cve_estado = CMS_CatCodigoPostal.cve_estado');
		$this->db->where('CMS_CatEstado.cve_estado = CMS_CatCiudad.cve_estado');
		$this->db->where(array( 'CMS_CatEstado.EDO' => $cve_estado, 'CMS_CatCiudad.CIUDAD' => $cve_ciudad));
		$this->db->order_by('colonia asc');
		$this->db->distinct();
		return $resultado = $this->db->get();
	}
	
	
	/**
	 * Devuelve los datos de dirección por Código Postal  
	**/
	function obtener_direccion_sepomex($codigo_postal) {
		$this->db->select('CMS_CatEstado.EDO as clave_estado, CMS_CatEstado.ESTADO as estado, CMS_CatCiudad.CIUDAD as ciudad, CMS_CatCodigoPostal.ZIP as codigo_postal, CMS_CatCodigoPostal.COLONIA AS colonia,');
		$this->db->from('CMS_CatCodigoPostal');
		$this->db->join('CMS_CatCiudad', 'CMS_CatCiudad.cve_ciudad = CMS_CatCodigoPostal.cve_ciudad');
		$this->db->join('CMS_CatEstado', 'CMS_CatEstado.cve_estado = CMS_CatCodigoPostal.cve_estado');
		$this->db->where('CMS_CatEstado.cve_estado = CMS_CatCiudad.cve_estado');
		$this->db->where(array( 'CMS_CatCodigoPostal.ZIP' => $codigo_postal));
		//$this->db->order_by('colonia asc');
		$this->db->distinct();
		return $resultado = $this->db->get();
	}
	
	/**
	 * Deshabilita de manera lógica la tarjeta especificada del cliente 
	 */
	function eliminar_direccion($id_cliente, $consecutivo)
	{
		$this->db->where(array('id_consecutivoSi' => $consecutivo, 'id_clienteIn' => $id_cliente));
		$resultado = $this->db->update('CMS_IntDireccion', array('id_estatusSi' => self::$CAT_ESTATUS['DESHABILITADA']));
		if($resultado) {
			return "La dirección ha sido eliminada exitosamente de tu cuenta";
		} else {
			return "Hubo un error al eliminar tu dirección. Por favor intenta de nuevo.";
		}
	}
		
	/**
	 * Actualiza la información de la dirección especificada del cliente
	 */
	function actualizar_direccion($consecutivo, $id_cliente, $nueva_info)
	{
		$this->db->where(array(	'id_consecutivoSi' => $consecutivo, 'id_clienteIn' => $id_cliente));
		$resultado = $this->db->update('CMS_IntDireccion', $nueva_info);
		//echo "resultado".$resultado;
		if($resultado) {
			//echo "Direcci&oacute;n actualizada.";
			return "Tu dirección ha sido actualizada exitosamente";
		} else {
			return "Hubo un error al actualizar tu dirección. Por favor intenta de nuevo.";
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
	 * Regresa la dirección de envío predeterminada si es que tiene alguna
	 */
	function get_pago_express($id_cliente) 
	{
		$this->db->select('id_consecutivoSi as consecutivo');
		$res = $this->db->get_where('CMS_IntDireccion',
								array('id_clienteIn' => $id_cliente,
										'address_type' => self::$TIPO_DIR['RESIDENCE'],
										'id_estatusSi' => self::$CAT_ESTATUS['DEFAULT']));
		if ($res->num_rows() == 0) {
			//echo "no hay direccion para pago express: ";
			
			//entonces recupera la primer tarjeta activa
			$this->db->select_min('id_consecutivoSi', 'consecutivo');
			$res = $this->db->get_where('CMS_IntDireccion',
								array('id_clienteIn' => $id_cliente,
										'address_type' => self::$TIPO_DIR['RESIDENCE'],
										'id_estatusSi' => self::$CAT_ESTATUS['HABILITADA']));
			/*
			echo "<pre>";
			print_r($res->row());
			echo "</pre>";
			*/
			//exit();
		}
		//print_r($res->row());
		//exit();
		$row_res = $res->row();
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
	
	/**
	 * Inserta el registro en la BD
	 */
	function insertar_direccion($direccion)
    {
    	//var_dump($direccion);
        $resultado = $this->db->insert('CMS_IntDireccion', $direccion);		//true si se inserta
        return $resultado;
    }
	
}