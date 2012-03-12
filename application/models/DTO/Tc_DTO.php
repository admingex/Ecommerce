<?php

class Tc_DTO {
    var $id_clienteIn;				//el cliente asociado
    var $id_TCSi;	//el consecutivo
    var $nombre_titularVc;
    var $apellidoP_titularVc;
    var $apellidoM_titularVc;
    var $mes_expiracionVc;
    var $anio_expiracionVc;
	var $descripcionVc;				//es opcional... sólo local]
	var $terminacion_tarjetaVc;		//el número de la tarjeta enviado
	var $id_tipo_tarjetaSi;
	var $id_estatusSi;				//es local
    
	public function __construct () 
	{
		
	}
}
class Amex_DTO {
    var $id_clienteIn;				//el cliente asociado
    var $id_TCSi;	//el consecutivo
    var $pais;
    var $codigo_postal;
    var $calle;
    var $ciudad;
    var $estado;
	var $mail;	//es opcional
	var $telefono;
    
	public function __construct () 
	{
		//$this->id_tipo_tarjetaSi = 1;	//Amex por default
	}
}
?>