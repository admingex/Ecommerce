<?php

class Forma_Pago_model extends CI_Model {

    var $title   = '';
    var $content = '';
    var $date    = '';
	
	var $id_ClienteIn;
	var $consecutivo_tarjeta = 0;
	var $id_tipo_tarjeta;
	
	

    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
    }
    
	function listar_tarjetas($id_user)
    {	
		$this->db->select('id_TCSi, id_ClienteIn, nombre_titularVc, apellidoP_titularVc, 
			apellidoM_titularVc, fecha_expiracionVc, descripcionVc, numeros_TarjetaVc, id_estatusSi');
		$this->db->where('id_ClienteIn', $id_user);
		$this->db->where('id_estatusSi !=', 2);		//2 es deshabilitado o eliminación lógica, validar con un catálogo
		//recupera las tarjetas activas
		$resultado = $this->db->get('CMS_IntTC');
        
        //echo $resultado->num_rows();
		return $resultado;
        
        //return $query->result();
    }
	
	/*
	 * Devuelve el numero consecutivo en turno con mayor valor de las tarjetas del cliente
	 * $id_cliente: el identificador en session del cliente 
	 * */
	function obtener_consecutivo($id_cliente)
	{
		$this->db->select_max('id_TCSi', 'consecutivo');
		$this->db->where('id_ClienteIn', $id_cliente);
		$res = $this->db->get('CMS_IntTC');
		
		$consecutivo = $res->row_array();
		
		//si regresa nulo, no hay tarjetas para ese cliente
		if(!$consecutivo['consecutivo']) {
			//echo 'consec. es null: 0';
			return 0;
		} else {
			//echo 'consec. es '.$consecutivo['consecutivo'];
			return $consecutivo['consecutivo'];
		}
		//return $res->row_array();
	}
	
	function insertar_tarjeta($informacion_tc)
    {
        //$this->date    = time();
        return $this->db->insert('CMS_IntTC', $informacion_tc);
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