<?php
/*
 * Clases para control de navegación del pago exprés
 * */

class Pago_Express {
    private $forma_pago;		
    private $dir_envio;	
    private $dir_facturacion;
	private $destino;
    
	public function __construct($forma_pago, $dir_envio, $dir_facturacion) 
	{
		/*son objetos con un consecutivo*/
		$this->forma_pago = $forma_pago;
		$this->dir_envio = $dir_envio;
		$this->dir_facturacion = $dir_facturacion;
	}
	
	public function get_forma_pago() {
		return $this->forma_pago->consecutivo;
	}
	
	public function get_dir_envio() {
		return $this->dir_envio->consecutivo;
	}
	
	public function get_dir_facturacion() {
		return $this->dir_facturacion->consecutivo;
	}
	
	public function get_destino() {
		return $this->destino;
	}
	
	public function set_destino($destino) {
		$this->destino = $destino;
	}
	
	/**
	 * Procesa la información del pago exprés y regresa un arreglo
	 * que contiene lo que se deb colocar en sesión e indica el destino
	 * para que continúe el flujo.
	 */
	public function definir_destino($requiere_envio) {
		//regresar el arreglo con las variables de sesión de pago y envío
		$flujo_pago_express = array();
		
		if ($this->get_forma_pago()) {	//tiene forma de pago
			//indicar qué partes se colocarán en sesión y el id
			$flujo_pago_express['tarjeta'] = $this->forma_pago->consecutivo;
			if ($requiere_envio) {
				if ($this->get_dir_envio()) {
					//poner en sesion y pasar a la orden
					$flujo_pago_express['dir_envio'] = $this->dir_envio->consecutivo;
					
					$flujo_pago_express['destino'] = "orden_compra";
					$this->destino = "orden_compra";					
				} else {
					$flujo_pago_express['destino'] = "direccion_envio";
					$this->destino = "direccion_envio";
				}
			} else  {
				//no requiere dirección de envío	
				$flujo_pago_express['destino'] = "orden_compra";
				$this->destino = "orden_compra";
			}
		} else {	//no tiene forma de pago
			if ($requiere_envio && $this->get_dir_envio()) {	//se revisa por si acaso
				//poner en sesión la dirección
				$flujo_pago_express['dir_envio'] = $this->dir_envio->consecutivo;
			} 
			$flujo_pago_express['destino'] = "forma_pago";
			$this->destino =  "forma_pago";
		}
		/*
		echo "<pre>";
		print_r($flujo_pago_express);
		echo "</pre>";
		exit();
		*/
		return $flujo_pago_express;
	}
}

?>