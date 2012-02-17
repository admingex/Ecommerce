<div id="container">
	
	<h1><?php echo $subtitle; ?></h1>
	<div style="text-align: right; font-size : 11pt; font-weight:bold; padding-right: 2%; color:#fb3;">
		<a href="<?php echo $this->config->item('base_url'); ?>/index.php/logout/">Cerrar Sesi&oacute;n</a>
	</div>
	<?php
		if (isset($vista_detalle)) {	//Tipo de detalle a desplegar
			if($vista_detalle == 'tc') {
				include ('direccion_facturacion/editar.html'); 	
			} else if ($vista_detalle == 'amex') {
				include ('direccion_facturacion/editar_amex.html');
			}
		} else {
	 		include ('direccion_facturacion/listar.html');
			
			//si re registrará una nueva tarjeta, se incluyen las formas
			if (isset($registrar)) {
					include ('direccion_facturacion/agregar.html');				
			}
		} 
	?>
	<p class="footer">Page rendered in <strong>{elapsed_time}</strong> seconds</p>
</div>