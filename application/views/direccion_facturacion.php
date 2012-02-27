<div id="container">
	
	<h1><?php echo $subtitle; ?></h1>
	<h2><?=$mensaje?></h2>
	<div style="text-align: right; font-size : 11pt; font-weight:bold; padding-right: 2%; color:#fb3;">
		<a href="<?php echo $this->config->item('base_url'); ?>/index.php/logout/">Cerrar Sesi&oacute;n</a>
	</div>
	<?php				
		if((isset($editar))){			
			include ('direccion_facturacion/editar.html');
		}		
		else{
			include ('direccion_facturacion/listar.html');
			if(isset($registrar)){			
				include ('direccion_facturacion/agregar.html');			
			}
		}								 														
	?>
	<p class="footer">Page rendered in <strong>{elapsed_time}</strong> seconds</p>
</div>