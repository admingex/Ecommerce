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
			apellidoM_titularVc, fecha_expiracionVc, descripcionVc, numeros_TarjetaVc');
		$this->db->where('id_ClienteIn', $id_user);
		
        
		
		$resultado = $this->db->get('CMS_IntTC');
        
        //echo $resultado->num_rows();
		return $resultado;
        
        //return $query->result();
    }
	
	function insertar_tarjeta()
    {
        $this->title   = $_POST['title']; // please read the below note
        $this->content = $_POST['content'];
        $this->date    = time();

        $this->db->insert('entries', $this);
    }
	
	/*Recoge parametros*/
	/*metodo: POST / GET*/
	function recupera_parametros($metodo) 
	{
		if($metodo == 'POST')
		{
			
		}	
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