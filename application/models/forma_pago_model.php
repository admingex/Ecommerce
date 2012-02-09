<?php
include ('DTO/Tc_DTO.php');

class Forma_Pago_model extends CI_Model {

    var $title   = '';
    var $content = '';
    var $date    = '';
	
	var $tc;
	var $amex;
	

    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
		$this->tc = new Tc_DTO();
		$this->amex = new Amex_DTO();
    }
    
	function listar_tarjetas($id_cliente)
    {	
		$this->db->select('id_TCSi, id_ClienteIn, nombre_titularVc, apellidoP_titularVc, 
			apellidoM_titularVc, mes_expiracionVc, anio_expiracionVc, descripcionVc, 
			terminacion_tarjetaVc, id_tipo_tarjetaSi, id_estatusSi');
		$this->db->where(array('id_ClienteIn'=> $id_cliente, 'id_estatusSi !=' => 2));	//2 es deshabilitado
		        
		
		$resultado = $this->db->get('CMS_IntTC');
        
        //echo $resultado->num_rows();
		return $resultado;
        
        //return $query->result();
    }
	
	function existe_tc($datos_tc)
	{
		$campos = array('nombre_titularVc' 	=> 	$datos_tc['nombre_titularVc'], 
						'apellidoP_titularVc' => $datos_tc['apellidoP_titularVc'],
						'apellidoM_titularVc' => $datos_tc['apellidoM_titularVc'],
						'mes_expiracionVc' 	=> 	$datos_tc['mes_expiracionVc'],
						'anio_expiracionVc' => 	$datos_tc['anio_expiracionVc'],
						'terminacion_tarjetaVc' =>	$datos_tc['terminacion_tarjetaVc'],
						'id_tipo_tarjetaSi'	=>	$datos_tc['id_tipo_tarjetaSi'],
						'id_estatusSi !=' => 2);
						
		$res = $this->db->get_where('CMS_IntTC', $campos);
		
		if($res->num_rows() > 0)
		{
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	function eliminar_tarjeta($id_cliente, $consecutivo)
	{
		$this->db->where(array(	'id_TCSi' => $consecutivo, 'id_ClienteIn' => $id_cliente));
		$res = $this->db->update('CMS_IntTC', array('id_estatusSi' => 2));
		if($res) {
			return "Tarjeta eliminada.";
		} else {
			return "Error al tratar de eliminar la tarjeta.";
		}
	}
	
	function actualiza_tarjeta($consecutivo, $id_cliente, $nueva_info)
	{
		$this->db->where(array(	'id_TCSi' => $consecutivo, 'id_ClienteIn' => $id_cliente));
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
										 'id_ClienteIn' => $id_cliente));	
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
		$resultado = $this->db->get_where('CMS_IntTC', array('id_ClienteIn' => $id_cliente));
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
	
	/**
	 * Este método pasa los arreglos tc y amex al WS del CCTC,
	 * almacena únicamente la parte de TC.
	 */
	function insertar_amex($tc, $amex)
    {
    	//var_dump($tc);
        $res = $this->db->insert('CMS_IntTC', $tc);		//true si se inserta
        //echo '<bt/>Resultado: '.$res;
        return $res;
    }
	
	/*Plantillas*/
    function get_last_ten_entries()
    {
        $query = $this->db->get('entries', 10);
        return $query->result();
    }

    function insert_entry()
    {
        $this->title   = $_POST['title']; // please read the below note
        $this->content = $_POST['content'];
        $this->date    = time();

        $this->db->insert('entries', $this);
    }

    function update_entry()
    {
        $this->title   = $_POST['title'];
        $this->content = $_POST['content'];
        $this->date    = time();

        $this->db->update('entries', $this, array('id' => $_POST['id']));
    }

}