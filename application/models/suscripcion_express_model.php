<?php


class Suscripcion_Express_Model extends CI_Model {

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
    
	function verifica_registro_email($email='') {
		//verificar que el email no esté registrado
		$qry = "SELECT id_clienteIn, email, LastLockoutDate 
				FROM CMS_IntCliente
				WHERE email = '".$email."' LIMIT 1";
		$res = $this->db->query($qry);
		
		//echo $res->num_rows()." qey: ". $qry;
		//exit();
		
		return $res;
	}
	
	function registro_cliente($datos_cliente='') {		
		
		$qry = "INSERT INTO CMS_IntCliente (id_clienteIn, salutation, fname, lname, email, password)
				VALUES (".$datos_cliente['id_clienteIn'].", '".$datos_cliente['salutation']."', '".$datos_cliente['fname']."', '".$datos_cliente['lname']."', '".$datos_cliente['email']."', '".$datos_cliente['password']."')";
		$res = $this->db->query($qry);
		
		return (int)$res;
	}
	
	function next_cliente_id()
	{
		$qry = "SELECT MAX(id_clienteIn) as consecutivo 
				FROM CMS_IntCliente";
		$res = $this->db->query($qry);
		
		$row = $res->row();	//regresa un objeto
		
		if(!$row->consecutivo)	{//si regresa null
			return 1;
		} else {
			return $row->consecutivo + 1;	
		}
	}
	
	function get_consecutivo_dir($id_cliente) 
	{
		$qry = "SELECT MAX(id_consecutivoSi) as consecutivo 
				FROM CMS_IntDireccion WHERE id_clienteIn=".$id_cliente;
		$res = $this->db->query($qry);
		
		$row = $res->row();	//regresa un objeto
		
		if(!$row->consecutivo)	{//si regresa null
			return 1;
		} else {
			return $row->consecutivo + 1;	
		}
					
	}
	
	function insertar_direccion($datos_direccion)
	{
		$qry = "INSERT INTO CMS_IntDireccion (id_consecutivoSi, address_type, id_clienteIn, address1, address2, address3, zip, state, city, id_estatusSi, codigo_paisVc, address4)
				VALUES ('".
				$datos_direccion['id_consecutivoSi']."', '".
				$datos_direccion['address_type']."', '".
				$datos_direccion['id_clienteIn']."', '".
				$datos_direccion['address1']."', '".
				$datos_direccion['address2']."', '".
				$datos_direccion['address3']."', '".
				$datos_direccion['zip']."', '".
				$datos_direccion['state']."', '".
				$datos_direccion['city']."', '3', '".
				$datos_direccion['codigo_paisVc']."', '".
				$datos_direccion['address4']."')";
				//echo "qry: ".$qry; exit;
		$res = $this->db->query($qry);
		
		return (int)$res;
					
	} 
	
	function existe_direccion($datos_dir)
	{
		//echo 'existe direccion: <pre>'; print_r($datos_dir); echo "</pre>";
		if (empty($datos_dir['address4'])){
			$numinterior = "";
		}
		$campos = array('id_clienteIn' 	=> 	$datos_dir['id_clienteIn'], 
						'address_type' => $datos_dir['address_type'],
						'address1' => $datos_dir['address1'],		//calle
						'address2' 	=> 	$datos_dir['address2'],		//numero ext
						'address3' => 	$datos_dir['address3'],		//colonia
						'address4' =>	$numinterior,		//num int
						'zip' =>	$datos_dir['zip'],				//cp
						'state' =>	$datos_dir['state'],
						'city' =>	$datos_dir['city'],
						'codigo_paisVc' =>	$datos_dir['codigo_paisVc'],
						'id_estatusSi !=' => self::$CAT_ESTATUS['DESHABILITADA']);

		$resultado = $this->db->get_where('CMS_IntDireccion', $campos);
		//echo '<pre>'; print_r($resultado); echo "</pre>"; exit;
		return $resultado;
	}
	
	function insertar_rs($datos_fact)
	{
		$qry = "INSERT INTO CMS_IntRazonSocial (tax_id_number, company, email, id_estatusSi, id_clienteIn )
				VALUES ('".$datos_fact['tax_id_number']."', '".$datos_fact['company']."', '".$datos_fact['email']."', '3', '".$datos_fact['id_clienteIn']."')";
		$res = $this->db->query($qry);
		
		return (int)$res;
	}
	
	function obtener_img_back($oc) {
		$qry = "SELECT nombreVc as nombre, url_imagen_vistaVc  as url_imagen, descripcion_largaVc as descripcion_larga, descripcion_cortaVc as descripcion_corta
				FROM TND_CatOCThink WHERE oc_id='".$oc."'";
		$res = $this->db->query($qry);
		
		return $res;						
	}
}