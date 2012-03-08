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
		if (!empty($lista_direcciones) && $lista_direcciones->num_rows()) {
	?>
	<div id="dialog-confirm" title="Eliminar Dirección">
		<!--p>
			<span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;">&nbsp;</span>
			¿Seguro que desea eliminar esta tarjeta?
		</p-->
	</div>
	<?php
		}
	?>
	
	<div id="scripts">
		<script type="text/javascript">
			/*mensaje y redirección*/
			$(function() {
				$( "#dialog:ui-dialog" ).dialog( "destroy" );
				
				$( "#dialog" ).dialog({
					resizable: false,
					//height:140,
					modal: true,
					buttons: {
						"Ok": function() {	//ok
							<?php
							//Por default recirecciona a la raiz del módulo
							$url_redirect = site_url("direccion_envio");
							if (isset($redirect) && $redirect) {
								$url_redirect = site_url('direccion_facturacion');
							}
							?>
							$( this ).dialog( "close" );
							window.location.href = "<?php echo $url_redirect; ?>";
						}
					}
				});
			});
			/*Eliminación*/
			$('a[href*="eliminar"]').click(function(event) {
				event.preventDefault();
				var url_destino = $(this).attr("href");
				
				$( "#dialog:ui-dialog" ).dialog( "destroy" );
				//mensaje
				$("#dialog-confirm").empty().append('<p>' +
					'<span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;">&nbsp;</span>' +
					'¿Seguro que deseas eliminar esta dirección?</p>');
				
				$( "#dialog-confirm" ).dialog({
					resizable: false,
					//height:140,
					modal: true,
					buttons: {
						"Eliminar": function() {
							$( this ).dialog( "close" );
							window.location.href = url_destino;
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