<?php
/*
 * Clases para invocación del WS
 * */

class Tc {
    private $id_clienteIn;				//el cliente asociado
    private $consecutivo_cmsSi;			//$id_TCSi;	//el consecutivo
    private $id_tipo_tarjeta;
    private $nombre;
    private $apellido_paterno;
    private $apellido_materno;
    private $numero;
    private $mes_expiracion;
	private $anio_expiracion;			//el número de la tarjeta enviado
	private $renovacion_automatica;
    
	public function __construct($id_clienteIn, $id_TCSi, $id_tipo_tarjetaSi,
		$nombre_titularVc, $apellidoP_titularVc, $apellidoM_titularVc, 
		$terminacion_tarjetaVc, $mes_expiracionVc, $anio_expiracionVc, 
		$renovacion_automatica = TRUE) 
	{
		$this->id_clienteIn = $id_clienteIn;
		$this->consecutivo_cmsSi = $id_TCSi;
		$this->id_tipo_tarjeta = $id_tipo_tarjetaSi;
		$this->nombre = $nombre_titularVc;
		$this->apellido_paterno = $apellidoP_titularVc; 
		$this->apellido_materno = $apellidoM_titularVc;
		$this->numero = $terminacion_tarjetaVc;
		$this->mes_expiracion = $mes_expiracionVc;
		$this->anio_expiracion = $anio_expiracionVc;
		$this->renovacion_automatica = $renovacion_automatica;
	}
}

class Amex {
    private $id_clienteIn;			//el cliente asociado
    private $consecutivo_cmsSi;		//el consecutivo
    private $nombre;
    private $apellido_paterno;
    private $apellido_materno;
    private $pais;
    private $codigo_postal;
    private $calle;
    private $ciudad;
    private $estado;
	private $mail;					//es opcional
	private $telefono;
    
	public function __construct($id_clienteIn, $id_TCSi,
		$nombre_titularVc, $apellidoP_titularVc, $apellidoM_titularVc, 
		$pais, $codigo_postal, $calle, $ciudad, $estado,
		$mail = '', $telefono) 
	{
		$this->id_clienteIn = $id_clienteIn;
		$this->consecutivo_cmsSi = $id_TCSi;
		$this->nombre =$nombre_titularVc;
		$this->apellido_paterno = $apellidoP_titularVc;
		$this->apellido_materno = $apellidoM_titularVc;
		$this->pais = $pais;
		$this->codigo_postal = $codigo_postal;
		$this->calle = $calle;
		$this->ciudad = $ciudad;
		$this->estado = $estado;
		$this->mail = $mail;
		$this->telefono = $telefono;
	}
}

class InformacionOrden {
    private $id_clienteIn;				//el cliente asociado
    private $consecutivo_cmsSi;			//$id_TCSi;	//el consecutivo
    private $id_promocionIn;
    private $digito;
    
	public function __construct($id_clienteIn, $id_TCSi, $id_promocionIn, $digito) 
	{
		$this->id_clienteIn = $id_clienteIn;
		$this->consecutivo_cmsSi = $id_TCSi;
		$this->id_promocionIn = $id_promocionIn;
		$this->digito = $digito;
	}
}

?>