<div id="container">
	
	<h1><?php echo $subtitle; ?></h1>
	<div style="text-align: right; font-size : 11pt; font-weight:bold; padding-right: 2%; color:#fb3;">
		<a href="<?php echo $this->config->item('base_url'); ?>/index.php/logout/">Cerrar Sesi&oacute;n</a>
	</div>
	<?php
		if (isset($direccion)) {	//detalle a desplegar
			include ('direccion_envio/editar.html'); 
		} else {
	 		include ('direccion_envio/listar.html');
			
			//si re registrará una nueva tarjeta, se incluyen las formas
			if (isset($registrar)) {
					include ('direccion_envio/agregar.html');				
			}
		} 
	?>
	<p class="footer">Page rendered in <strong>{elapsed_time}</strong> seconds</p>
</div>