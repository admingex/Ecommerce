<div id="container">
	
	<h1><?php echo $subtitle; ?></h1>
	<div style="text-align: right; font-size : 11pt; font-weight:bold; padding-right: 2%; color:#fb3;">
		<a href="<?php echo $this->config->item('base_url'); ?>/index.php/logout/">Cerrar Sesi&oacute;n</a>
	</div>
	<?php
		if (isset($vista_detalle)) {	//Tipo de detalle a desplegar
			if($vista_detalle == 'tc') {
				include ('forma_pago/editar.html'); 	
			} else if ($vista_detalle == 'amex') {
				include ('forma_pago/editar_amex.html');
			}
		} else {
			
	
	 		include ('forma_pago/listar.html');
			//si re registrará una nueva tarjeta, se incluyen las formas 
			if (isset($form)) {
				if($form == 'tc') {
					include ('forma_pago/agregar.html'); 	
				} else if ($form == 'amex') {
					include ('forma_pago/agregar_amex.html');
				}
			}
		} 
	?>
	<p class="footer">Page rendered in <strong>{elapsed_time}</strong> seconds</p>
</div>