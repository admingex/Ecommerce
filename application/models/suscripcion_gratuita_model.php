<?php


class Suscripcion_Gratuita_Model extends CI_Model {

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

		//Verificar que el email no esté registrado en la BD
	function verifica_registro_email($email='') {
		$qry = "SELECT id_clienteInA, emailA 
				FROM CMS_IntClienteA
				WHERE emailA = '".$email."' LIMIT 1";
		$res = $this->db->query($qry);
		
		//echo $res->num_rows()." qey: ". $qry;
		//exit();
		
		return $res;
	}
	
	function verifica_registro_id($id='') {
		$qry = "SELECT id_clienteInA, emailA 
				FROM CMS_IntClienteA
				WHERE id_clienteInA = '".$id."' LIMIT 1";
		$res = $this->db->query($qry);
		
		//echo $res->num_rows()." qey: ". $qry;
		//exit();
		
		return $res;
	}
		
	function registro_cliente($datos_cliente='') {		
		
		$qry = "INSERT INTO CMS_IntClienteA (id_clienteInA, salutationA, fnameA, lnameA, emailA)
				VALUES (".$datos_cliente['id_clienteIn'].", '".$datos_cliente['salutation']."', '".$datos_cliente['fname']."', '".$datos_cliente['lname']."', '".$datos_cliente['email']."')";
		$res = $this->db->query($qry);
		
		return (int)$res;
	}
	
	function next_cliente_id()
	{
		$qry = "SELECT MAX(id_clienteInA) as consecutivo 
				FROM CMS_IntClienteA";
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
		$qry = "SELECT MAX(id_consecutivoSiA) as consecutivo 
				FROM CMS_IntDireccionA WHERE id_clienteInA=".$id_cliente;
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
		$qry = "INSERT INTO CMS_IntDireccionA (id_consecutivoSiA, address_typeA, id_clienteInA, address1A, address2A, address3A, zipA, stateA, cityA, id_estatusSiA, codigo_paisVcA, address4A)
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
		else {
			$numinterior = $datos_dir['address4'];
		}
		$campos = array('id_clienteInA' 	=> 	$datos_dir['id_clienteIn'], 
						'address_typeA' => $datos_dir['address_type'],
						'address1A' => $datos_dir['address1'],		//calle
						'address2A' 	=> 	$datos_dir['address2'],		//numero ext
						'address3A' => 	$datos_dir['address3'],		//colonia
						'address4A' =>	$numinterior,		//num int
						'zipA' =>	$datos_dir['zip'],				//cp
						'stateA' =>	$datos_dir['state'],
						'cityA' =>	$datos_dir['city'],
						'codigo_paisVcA' =>	$datos_dir['codigo_paisVc'],
						'id_estatusSi !=' => self::$CAT_ESTATUS['DESHABILITADA']);

		$resultado = $this->db->get_where('CMS_IntDireccionA', $campos);
		//echo '<pre>'; print_r($resultado); echo "</pre>"; exit;
		return $resultado;
	}
	
	function obtener_img_back($oc) {
		$qry = "SELECT nombreVc as nombre, url_imagen_vistaVc  as url_imagen, descripcion_largaVc as descripcion_larga, descripcion_cortaVc as descripcion_corta
				FROM TND_CatOCThink WHERE oc_id='".$oc."'";
		$res = $this->db->query($qry);
		
		return $res;						
	}
	
}