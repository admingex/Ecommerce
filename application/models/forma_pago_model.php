<?php

class Forma_Pago_model extends CI_Model {
	public static $CAT_ESTATUS = array(
		"HABILITADA"	=> 1, 
		"DESHABILITADA"	=> 2, 
		"DEFAULT"		=> 3
	);

    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
    }
    
	function listar_tarjetas($id_cliente)
    {	
		$this->db->select('id_TCSi, id_clienteIn, nombre_titularVc, apellidoP_titularVc, 
			apellidoM_titularVc, mes_expiracionVc, anio_expiracionVc, descripcionVc, 
			terminacion_tarjetaVc, id_tipo_tarjetaSi, id_estatusSi');
		$this->db->where(array('id_clienteIn'=> $id_cliente, 'id_estatusSi !=' => self::$CAT_ESTATUS['DESHABILITADA']));	//2 es deshabilitado
		        
		
		$resultado = $this->db->get('CMS_IntTC');
        
        //echo $resultado->num_rows();
		return $resultado;
        
        //return $query->result();
    }
	
	/**
	 * Devuelve la lista de países del catálogo de Amex local
	 * */
	function listar_paises_amex() {
		$this->db->select('valorVc as id_pais, descripcionVc as pais');
		$this->db->order_by('pais', 'asc');		
		return $resultado = $this->db->get('CMS_CatPaisAmex');
	}
	
	/**
	 * Revisa si existe la tarjeta con estatus activo.
	 * Revisa el id del cliente, el consecutivo, la terminación, el nombre del titular y
	 * la fecha de expiración.
	 */
	function existe_tc($datos_tc)
	{
		$campos = array('id_clienteIn' 	=> 	$datos_tc['id_clienteIn'],
						'nombre_titularVc' 	=> 	$datos_tc['nombre_titularVc'], 
						'apellidoP_titularVc' => $datos_tc['apellidoP_titularVc'],
						'apellidoM_titularVc' => $datos_tc['apellidoM_titularVc'],
						'mes_expiracionVc' 	=> 	$datos_tc['mes_expiracionVc'],
						'anio_expiracionVc' => 	$datos_tc['anio_expiracionVc'],
						'terminacion_tarjetaVc' =>	$datos_tc['terminacion_tarjetaVc'],
						'id_tipo_tarjetaSi'	=>	$datos_tc['id_tipo_tarjetaSi'],
						'id_estatusSi !=' => self::$CAT_ESTATUS['DESHABILITADA']);
						
		$res = $this->db->get_where('CMS_IntTC', $campos);
		
		if($res->num_rows() > 0)
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
	 * Verifica si el cliente tiene alguna tarjeta como predeterminada
	 */
	function existe_predetereminada($id_cliente)
	{
		$args = array($id_cliente, self::$CAT_ESTATUS['DEFAULT']);
		$qry = "SELECT 	id_TCSi as consecutivo
				FROM 	CMS_IntTC 
				WHERE   id_clienteIn = ?
				AND     id_estatusSi = ?";
		$res = $this->db->query($qry, $args);
		
		return $res->result();
	}

	/**
	 * Quitar la tarjeta predeterminada actual
	 */
	function quitar_predeterminado($id_cliente) {
		$this->db->where(array
							( 'id_clienteIn' => $id_cliente,
							'id_estatusSi' => self::$CAT_ESTATUS['DEFAULT']));
		$resultado = $this->db->update('CMS_IntTC', array('id_estatusSi' => self::$CAT_ESTATUS['HABILITADA']));
	}
	/**
	 * Deshabilita lógicamente la tarjeta en la base de datus cambiando su estatus.
	 */
	function eliminar_tarjeta($id_cliente, $consecutivo)
	{
		$this->db->where(array(	'id_TCSi' => $consecutivo, 'id_clienteIn' => $id_cliente));
		$res = $this->db->update('CMS_IntTC', array('id_estatusSi' => self::$CAT_ESTATUS['DESHABILITADA']));	//Deshabilitar lógicamente
		if($res) {
			return "Tarjeta eliminada exitosamente";
		} else {
			return "Error al tratar de eliminar la tarjeta";
		}
	}
	
	/**
	 * Actualiza la información dela tarjeta.
	 */
	function actualiza_tarjeta($consecutivo, $id_cliente, $nueva_info)
	{
		$this->db->where(array(	'id_TCSi' => $consecutivo, 'id_clienteIn' => $id_cliente));
		$res = $this->db->update('CMS_IntTC', $nueva_info);
		//echo "resultado".$res;
		if($res) {
			//echo "Tarjeta actualizada.";
			return "Tarjeta actualizada correctamente";
		} else {
			return "Error al tratar de actualizar la tarjeta";
		}
	}
	
	/**
	 * Recupera la información de una tarjeta en específico.
	 */
	function detalle_tarjeta($id_TCSi, $id_cliente)
	{
		$res = $this->db->get_where('CMS_IntTC', 
								array(	'id_TCSi' => $id_TCSi,
										 'id_clienteIn' => $id_cliente));	
		$row_res = $res->row();
		return $row_res;
	}
	
	/**
	 * Revisa si existe alguna tarjeta marcada para pago exprés y si no devuelve la primer tarjeta registrada
	 * para ser utilizada para el cobro.
	 */
	function get_pago_express($id_cliente) 
	{
		$this->db->select('id_TCSi as consecutivo');
		$res = $this->db->get_where('CMS_IntTC',
								array('id_clienteIn' => $id_cliente,
										'id_estatusSi' => self::$CAT_ESTATUS['DEFAULT']));
										
		if ($res->num_rows() == 0) {
			//echo "no hay para pago express: ";
			
			//entonces recupera la primer tarjeta activa
			$this->db->select_min('id_TCSi', 'consecutivo');
			$res = $this->db->get_where('CMS_IntTC',
								array('id_clienteIn' => $id_cliente,
										'id_estatusSi' => self::$CAT_ESTATUS['HABILITADA']));
			/*
			echo "<pre>";
			print_r($res->row());
			echo "</pre>";
			*/
		}
			
		$row_res = $res->row();
		
		return $row_res;
	}
	
	/**
	 * Recupera los tipos de tarjeta que se listarán en el catálogo para el registro de la forma de pago.
	 */
	function listar_tipos_tarjeta() 
	{
		//excepto AMEX, esto funciona con la BD local.
		//$this->db->not_like('descripcionVc', 'AMERICAN EXPRESS');
		$this->db->select('id_tipo_tarjetaSi as id_tipo_tarjeta, descripcionVc as descripcion');
		$this->db->order_by('descripcion');
		$res =  $this->db->get_where('CMS_CatTipoTarjeta', array('estatusBi' => TRUE));
		
		return $res->result();	
	}
	
	/**
	 * Devuelve el consecutivo actual del cliente
	 */
	function get_consecutivo($id_cliente) 
	{
		$this->db->trans_start();	//begin Trans
		
		$this->db->select_max('id_TCSi', 'consecutivo');
		$resultado = $this->db->get_where('CMS_IntTC', array('id_clienteIn' => $id_cliente));
		
		$this->db->trans_complete();	//commit Trans
		
		$row = $resultado->row();	//regresa un objeto
		
		if(!$row->consecutivo)	{//si regresa null
			return 0;
		} else {
			return $row->consecutivo;	
		}
		//echo " numrows: $resultado->num_rows<br/>";
		//var_dump($resultado->row());
	}
	
	function insertar_tc($tc)
    {
    	//var_dump($tc);
        $res = $this->db->insert('CMS_IntTC', $tc);		//true si se inserta
        //echo '<bt/>Resultado: '.$res;
        return $res;
    }
	
}