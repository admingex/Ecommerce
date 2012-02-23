<?php
include ('DTO/Tc_DTO.php');

class Direccion_Envio_model extends CI_Model {
	//catálogo de estatus para comparaciones***
	/**
	 * 
	 */
		
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
			address4 as num_int, 
			address3 as colonia,
			zip as cp, 
			state as estado, 
			city as ciudad,
			codigo_paisVc as pais, 
			phone as telefono,
			email');
		$this->db->where(array('id_clienteIn'=> $id_cliente, 'id_estatusSi !=' => 2));		//2 es deshabilitado
		        
		
		$resultado = $this->db->get('CMS_IntDireccion');
        
        //echo $resultado->num_rows();
		return $resultado;
        
        //return $query->result();
    }
	
	function existe_direccion($datos_dir)
	{
		$campos = array('id_clienteIn' 	=> 	$datos_dir['id_clienteIn'], 
						'address_type' => $datos_dir['address_type'],
						'address1' => $datos_dir['address1'],		//calle
						'address2' 	=> 	$datos_dir['address2'],		//numero ext
						'address3' => 	$datos_dir['address3'],		//colonia
						'address4' =>	$datos_dir['address4'],		//num int
						'zip' =>	$datos_dir['zip'],
						'state' =>	$datos_dir['state'],
						'city' =>	$datos_dir['city'],
						'colonia' =>	$datos_dir['colonia'],
						'codigo_paisVc' =>	$datos_dir['codigo_paisVc'],
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