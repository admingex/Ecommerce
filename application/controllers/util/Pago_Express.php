<?php
/*
 * Clases para control de navegación del pago exprés
 * */

class Pago_Express {
	/*Banderas para el flujo*/
    private $forma_pago;		
    private $dir_envio;	
    private $dir_facturacion;
	private $destino;
	private $requiere_envio;
    
	public function __construct($forma_pago, $dir_envio, $dir_facturacion=NULL) 
	{
		/*son objetos con un consecutivo*/
		
		$this->forma_pago = $forma_pago;
		$this->dir_envio = $dir_envio;
		$this->dir_facturacion = $dir_facturacion;
		
		$this->requiere_envio = FALSE;
	}
	
	public function get_forma_pago() {
		//return $this->forma_pago->consecutivo;
		//echo "tiene forma de compra? ". $this->forma_pago;
		return $this->forma_pago;
	}
	
	/**
	 * Actualizará la información sobre la forma de pago en el objeto
	 */
	public function actualizar_forma_pago($tarjeta) {
		$this->forma_pago = $tarjeta;
	}
	
	public function get_dir_envio() {
		//return $this->dir_envio->consecutivo;
		return isset($this->dir_envio);
	}
	
	/**
	 * Actualizará la información sobre la dirección de envío en el objeto
	 */
	public function actualizar_dir_envio($dir_envio) {
		$this->dir_envio = $dir_envio;
	}
	
	public function get_dir_facturacion() {
		return isset($this->dir_facturacion);
	}
	
	public function get_destino() {
		return $this->destino;
	}
	
	public function set_destino($destino) {
		$this->destino = $destino;
	}
	
	public function get_requiere_envio() {
		return $this->requiere_envio;
	}
	
	/**
	 * Procesa la información del pago exprés y regresa un arreglo
	 * que contiene lo que se deb colocar en sesión e indica el destino
	 * para que continúe el flujo.
	 */
	public function definir_destino_inicial($requiere_envio) {
		$this->requiere_envio = $requiere_envio;
		//regresar el arreglo con las variables de sesión de pago y envío
		$flujo_pago_express = array();
		
		if ($this->get_forma_pago()) {	//tiene forma de pago
			//indicar qué partes se colocarán en sesión y el id
			$flujo_pago_express['tarjeta'] = $this->get_forma_pago();
			if ($this->requiere_envio) {
				if ($this->get_dir_envio()) {
					//poner en sesion y pasar a la orden
					$flujo_pago_express['dir_envio'] = $this->get_dir_envio();
					
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
				$flujo_pago_express['dir_envio'] = $this->get_dir_envio();
			} 
			$flujo_pago_express['destino'] = "forma_pago";
			$this->destino =  "forma_pago";
		}
		/*
		echo "<pre>";
		print_r($flujo_pago_express);
		echo "</pre>";
		echo "<pre>";
		print_r($this);
		echo "</pre>";
		exit();
		*/
		
		return $flujo_pago_express;
	}

	/**
	 * Procesa la información del pago exprés y define a donde se dirige
	 * el flujo del pago.
	 * 
	 */
	public function siguiente_destino() {
		if ($this->get_forma_pago()) {	//tiene forma de pago
			//indicar qué partes se colocarán en sesión y el id
			if ($this->requiere_envio) {
				if ($this->get_dir_envio()) {
					//poner en sesion y pasar a la orden
					$this->destino = "orden_compra";					
				} else {
					$this->destino = "direccion_envio";
				}
			} else  {
				//no requiere dirección de envío	
				$this->destino = "orden_compra";
			}
		} else {	//no tiene forma de pago
			$this->destino =  "forma_pago";
		}
		return $this->destino;
	}
}

?>