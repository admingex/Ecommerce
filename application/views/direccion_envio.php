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
	
	<?php
		if (!empty($mensaje)) {
	?>
	<div id="dialog" title="Resultado" >
		<p><?php echo $mensaje?></p>
	</div>
	<?php
		}
		if (!empty($lista_direcciones)) {
	?>
	<div id="dialog-confirm" title="Eliminar Tarjeta">
		<p>
			<span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;">&nbsp;</span>
			¿Seguro que desea eliminar esta tarjeta?
		</p>
	</div>
	<?php
		}
	?>
	
	<div id="scripts">
		<script type="text/javascript">
			$(function() {
				$( "#dialog:ui-dialog" ).dialog( "destroy" );
				
				$( "#dialog" ).dialog({
					resizable: false,
					//height:140,
					modal: true,
					buttons: {
						"Ok": function() {
							$( this ).dialog( "close" );
							location.href = "<?php echo site_url("direccion_envio");?>";
						}
					}
				});
			});
			/*Eliminación*/
			$('a[href*="eliminar"]').click(function(event) {
				event.preventDefault();
				
				$( "#dialog:ui-dialog" ).dialog( "destroy" );
				
				$( "#dialog-confirm" ).dialog({
					resizable: false,
					//height:140,
					modal: true,
					buttons: {
						"Eliminar": function() {
							$( this ).dialog( "close" );
							location.href = "<?php echo site_url("direccion_envio");?>";
						},
						"Cancelar": function() {
							$( this ).dialog( "close" );
						}
					}
				});
			});
		</script>
	</div>
	
	
	
	<p class="footer">Page rendered in <strong>{elapsed_time}</strong> seconds</p>
</div>