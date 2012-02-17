<?php
include ('DTO/Tc_DTO.php');

class Direccion_Facturacion_model extends CI_Model {
	
    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
    }
    
	function listar_direcciones($id_cliente)
    {	
		$this->db->select('id_consecutivoSi, address_type, id_clienteIn, 
			address1 as calle, 
			address2 as num_ext, 
			address3 as colonia, 
			zip as cp, 
			state as estado, 
			city as ciudad, 
			phone as telefono, 
			email');
		$this->db->where(array('id_clienteIn'=> $id_cliente, 'id_estatusSi !=' => 2));	//2 es deshabilitado
		        
		
		$resultado = $this->db->get('CMS_IntDireccion');
        
        //echo $resultado->num_rows();
		return $resultado;
        
        //return $query->result();
    }
	
	function existe_direccion($datos_tc)
	{
		$campos = array('nombre_titularVc' 	=> 	$datos_tc['nombre_titularVc'], 
						'apellidoP_titularVc' => $datos_tc['apellidoP_titularVc'],
						'apellidoM_titularVc' => $datos_tc['apellidoM_titularVc'],
						'mes_expiracionVc' 	=> 	$datos_tc['mes_expiracionVc'],
						'anio_expiracionVc' => 	$datos_tc['anio_expiracionVc'],
						'terminacion_tarjetaVc' =>	$datos_tc['terminacion_tarjetaVc'],
						'id_tipo_tarjetaSi'	=>	$datos_tc['id_tipo_tarjetaSi'],
						'id_estatusSi !=' => 2);
						
		$resultado = $this->db->get_where('CMS_IntDireccion', $campos);
		
		if($resultado->num_rows() > 0)
		{
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	function listar_paises_think() {
		$this->db->select('country_code2 as id_pais, country_name as pais');		
		
		
		return $resultado = $this->db->get('CMS_CatPaisThink');

		//echo "<b/>".var_dump($paises)."<b/>";		
		//foreach ($resultado->result() as $pais) {
			//echo $pais->id_pais. "-> " . $pais->pais."<br/>";
		//}
	}
	
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
	
	function detalle_tarjeta($id_consecutivoSi, $id_cliente)
	{
		$resultado = $this->db->get_where('CMS_IntDireccion', 
								array(	'id_consecutivoSi' => $id_consecutivoSi,
										 'id_clienteIn' => $id_cliente));	
		$row_res = $resultado->row();
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
		$this->db->select_max('id_consecutivoSi', 'consecutivo');
		$resultado = $this->db->get_where('CMS_IntDireccion', array('id_clienteIn' => $id_cliente));
		$row = $resultado->row();	//regresa un objeto
		
		if(!$row->consecutivo)	{//si regresa null
			return 0;
		} else {
			return $row->consecutivo;	
		}
		//echo " numrows: $resultado->num_rows<br/>";
		//var_dump($resultado->row());
	}
	
	function insertar_direccion($direccion)
    {
    	//var_dump($direccion);
        $resultado = $this->db->insert('CMS_IntDireccion', $direccion);		//true si se inserta
        //echo '<bt/>Resultado: '.$resultado;
        return $resultado;
    }
	
}