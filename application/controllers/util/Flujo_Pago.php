<?php
/*
 * Clases para control de navegación del pago exprés
 * */

class Flujo_Pago {
    private $forma_pago;		
    private $dir_envio;	
    private $dir_facturacion;
    
    
    
	public function __construct($forma_pago, $dir_envio, $dir_facturacion) 
	{
		$this->forma_pago = $forma_pago;
		$this->dir_envio = $dir_envio;
		$this->dir_facturacion = $dir_facturacion;
	}
	
	public function get_forma_pago() {
		return $this->forma_pago;
	}
	
	public function get_dir_envio() {
		return $this->dir_envio;
	}
	
	public function get_dir_facturacion() {
		return $this->dir_facturacion;
	}
}

?>