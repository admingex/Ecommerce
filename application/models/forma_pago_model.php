<?php

class Forma_Pago_model extends CI_Model {
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
	 * Quitar la tarjeta predeterminada actual
	 */
	function quitar_predeterminado($id_cliente) {
		$this->db->where(array
							( 'id_clienteIn' => $id_cliente,
							'id_estatusSi' => self::$CAT_ESTATUS['DEFAULT']));
		$resultado = $this->db->update('CMS_IntTC', array('id_estatusSi' => self::$CAT_ESTATUS['HABILITADA']));
	}
	
	function eliminar_tarjeta($id_cliente, $consecutivo)
	{
		$this->db->where(array(	'id_TCSi' => $consecutivo, 'id_clienteIn' => $id_cliente));
		$res = $this->db->update('CMS_IntTC', array('id_estatusSi' => self::$CAT_ESTATUS['DESHABILITADA']));	//Deshabilitar lógicamente
		if($res) {
			return "Tarjeta eliminada.";
		} else {
			return "Error al tratar de eliminar la tarjeta.";
		}
	}
	
	function actualiza_tarjeta($consecutivo, $id_cliente, $nueva_info)
	{
		$this->db->where(array(	'id_TCSi' => $consecutivo, 'id_clienteIn' => $id_cliente));
		$res = $this->db->update('CMS_IntTC', $nueva_info);
		//echo "resultado".$res;
		if($res) {
			//echo "Tarjeta actualizada.";
			return "Tarjeta actualizada.";
		} else {
			return "Error al tratar de actualizar la tarjeta.";
		}
	}
	
	function detalle_tarjeta($id_TCSi, $id_cliente)
	{
		$res = $this->db->get_where('CMS_IntTC', 
								array(	'id_TCSi' => $id_TCSi,
										 'id_clienteIn' => $id_cliente));	
		$row_res = $res->row();
		return $row_res;
	}
	
	function listar_tipos_tarjeta() 
	{
		//excepto AMEX, esto funciona con la BD local.
		$this->db->not_like('descripcionVc', 'AMERICAN EXPRESS');	
		return $this->db->get_where('CMS_CatTipoTarjeta', array('estatusBi' => TRUE));	
	}
	
	/**
	 * Devuelve el consecutivo actual del cliente
	 */
	function get_consecutivo($id_cliente) 
	{
		$this->db->select_max('id_TCSi', 'consecutivo');
		$resultado = $this->db->get_where('CMS_IntTC', array('id_clienteIn' => $id_cliente));
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