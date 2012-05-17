 <?php

class Orden_Compra_model extends CI_Model {

    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
    }
    
	/**
	 * Inserta la orden en la tabla de compras y a su vez registra el estatus de la
	 * compra en la tabla de bitácora;  
	 */
	function insertar_compra($id_cliente)
	{
		$id_compra = 0;
		
		$this->db->trans_start();
		
		//obtener el consecutivo:
		$q = $this->db->query("SELECT MAX(id_compraIn) as consecutivo FROM CMS_IntCompra WHERE id_clienteIn = ".$id_cliente);
		$r = $q->row();
		$id_compra = (!$r->consecutivo) ? 1 : $r->consecutivo + 1;
		
		//Registrar la compra
		$info_compra = array($id_compra, $id_cliente);
		$qry = "INSERT INTO CMS_IntCompra (id_compraIn, id_clienteIn) VALUES (?, ?)";
		$this->db->query($qry, $info_compra);
		//$this->db->query('ANOTHER QUERY...');
		//$this->db->query('AND YET ANOTHER QUERY...');
		
		$this->db->trans_complete();
		
		//$id_compra = $this->get_consecutivo() + 1;
		//$info_compra = array( 'id_compraIn' => $id_compra, 'id_clienteIn' => $id_cliente);
		//$res = $this->db->insert('CMS_IntCompra', $info_compra);
		
		//Revisar el estatus de la transacción
		if ($this->db->trans_status() === FALSE)
		{
		    // generate an error... or use the log_message() function to log your error
		    return FALSE;
		} else { 
			return $id_compra;
		}
	}
	
	/**
	 * Registro inicial de la compra en todas las tablas relacionadas. 
	 */
	function registrar_compra_inicial($articulos, $forma_pago, $direcciones, $estatus)
	{		
		$this->db->trans_start();
		//echo "<br/><br/>articulos: " ;
		foreach ($articulos as $articulo) { //insertar articulos
			$qry = "INSERT INTO CMS_RelCompraArticulo (id_articuloIn, id_compraIn, id_clienteIn, id_promocionIn) VALUES(?, ?, ?, ?)";
			$this->db->query($qry, $articulo);	//se realiza la inserción
			//echo var_dump($articulo)."<br/>";
		}
		
		//echo "<br/>pago: ";
		//echo var_dump($forma_pago)."<br/>";
		$res_pago = $this->db->insert('CMS_RelCompraPago', $forma_pago);
		
		//echo "<br/>direcciones: " ;
		foreach ($direcciones as $direccion) {
			if (!empty($direccion)) {
				//echo var_dump($direccion).", <br/>";
				$res_dir = $this->db->insert('CMS_RelCompraDireccion', $direccion);
			}
		}
		
		//echo "<br/>estatus: " ;
		//echo var_dump($estatus)."<br/>";
		$res_estatus = $this->db->insert('CMS_RelCompraEstatus', $estatus);
		
		$this->db->trans_complete();
		
		//Revisar el estatus de la transacción
		if ($this->db->trans_status() === FALSE)
		{
		    // generate an error... or use the log_message() function to log your error
		    return FALSE;
		} else { 
			return "Registro inicial de la compra ". $estatus['id_compraIn']. " exitoso.";
			//return TRUE;
		}
	}
		
	/**
	 * Registra el estatus de la compra en la bitácora
	 * id_compraIn, id_clienteIn, id_estatusCompreSi, timestamp
	 */
	function insertar_estatus_compra($info_estatus)
	{
		$res = $this->db->insert('CMS_RelCompraEstatus', $info_estatus);
		return $res;
	}
	
	/**
	 * Registrar el detalle de la respuesta del pago de CCTC en ecomerce 
	 */
	function insertar_detalle_pago_tc($info_detalle_pago_tc)
	{
		$res = $this->db->insert('CMS_RelCompraPagoDetalleTC', $info_detalle_pago_tc);
		return $res;
	}
	
	/**
	 * Devuelve el consecutivo del pago para tarjetas no guardadas ó para el depósito bancario
	 * con el usuario de ecommerce cliente _id : 0
	 * "ecommerce_tc"	=> tarjetas
	 * "ecommerce_deposito" => depósito
	 */
	function obtener_consecutivo_forma_pago($nombre_cliente)
	{
		$this->db->select('id_TCSi as consecutivo');
		$res = $this->db->get_where('CMS_IntTC', array("nombre_titularVc", $nombre_cliente));
		
		return $res->row();
	}
	
	/**
	 * Insertar la forma de pago asociada a la compra, si es tarjeta, 
	 * también el consecutivo de la misma
	 */
	function insertar_pago_compra($info_pago_compra) 
	{
		$res = $this->db->insert('CMS_RelCompraPago', $info_pago_compra);
		return $res;
	}
	
	/**
	 * Insertar articulo por promoción
	 * Recibe un array con: $id_articulo, $id_compra, $id_cliente
	 */
	function insertar_articulo_compra($info_articulo_compra) 
	{
		$res = $this->db->insert('CMS_RelCompraArticulo', $info_articulo_compra);
		return $res;
	}
	
	/**
	 * Regresa los ids de artículos de una promoción 
	 */
	function obtener_articulos_promocion($id_promocion)
	{
		$this->db->select('id_articuloIn as id_articulo');
		$this->db->where('id_promocionIn', $id_promocion);
		$res = $this->db->get('CMS_IntArticulo');
		
		return $res->result();
	}
	
	/**
	 * Devuelve el consecutivo actual de la última compra
	 */
	function get_consecutivo() 
	{
		$this->db->select_max('id_compraIn', 'consecutivo');
		$resultado = $this->db->get_where('CMS_IntCompra');
		$row = $resultado->row();	//regresa un objeto de un sólo registro
		
		if(!$row->consecutivo)	{//si regresa null
			return 0;
		} else {
			return $row->consecutivo;	
		}
		//echo " numrows: $resultado->num_rows<br/>";
		//var_dump($resultado->row());
	}
	
	
	
	function verifica_registro_email($email='') {
		//verificar que el email no esté registrado
		$qry = "SELECT email 
				FROM CMS_IntCliente
				WHERE email = '".$email."' LIMIT 1";
		$res = $this->db->query($qry);
		
		//echo $res->num_rows()." qey: ". $qry;
		//exit();
		
		return $res;
	}
	
	function registrar_cliente($cliente = array())
    {
    	$m5_pass = md5($cliente['email'].'|'.$cliente['password']);		//encriptaciónn definida en el registro de usuarios
    	$cliente['password'] = $m5_pass;
        $res = $this->db->insert('CMS_IntCliente', $cliente);		//true si se inserta

        return $res;	//true_false
    }
	
	function next_cliente_id()
	{
		$qry = "SELECT MAX(id_clienteIn) as consecutivo 
				FROM CMS_IntCliente";
		$res = $this->db->query($qry);
		
		$row = $res->row();	//regresa un objeto
		
		if(!$row->consecutivo)	{//si regresa null
			return 0;
		} else {
			return $row->consecutivo + 1;	
		}
	}
}
