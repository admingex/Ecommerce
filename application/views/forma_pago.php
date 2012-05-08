<section id="descripcion-proceso">
	<div class="titulo-proceso-img">&nbsp;		
	</div>			
	<div class="titulo-proceso">
		<?php echo $subtitle; ?>	
	</div>
</section>
<div id="pleca-punteada"></div>
<section class="contenedor">
	
		<?php
			if (isset($vista_detalle)) {	//Tipo de detalle a desplegar
				if($vista_detalle == 'tc') {
					include ('forma_pago/editar.html'); 	
				} else if ($vista_detalle == 'amex') {
					include ('forma_pago/editar_amex.html');
				}
			} else {
				//si re registrará una nueva tarjeta, se incluyen las formas 
				if (isset($form)) {
					if($form == 'tc') {
						include ('forma_pago/listar.html');
						include ('forma_pago/agregar.html');
						//otras formas de pago
						include ('forma_pago/otras_formas.html');
					} else if ($form == 'amex') {
						include ('forma_pago/agregar_amex.html');
					}
				} else {
					include ('forma_pago/listar.html');
					//otras formas de pago
					include ('forma_pago/otras_formas.html');
				}
			} 
		?>
	
	<?php
		if (!empty($mensaje)) {
	?>
			<div id="dialog" title="Resultado" >
				<p><?php echo $mensaje;?></p>
			</div>
	<?php
		}
		if (!empty($lista_tarjetas) && $lista_tarjetas->num_rows()) {
	?>
	<div id="dialog-confirm" title="Eliminar Tarjeta">
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
						"Ok": function() {
							<?php
							//Por default recirecciona a la raiz del módulo
							$url_redirect = site_url("forma_pago");
							if (isset($redirect) && $redirect) {
								//revisar si la redirección es hacia el resumen de la orden
								if ($this->session->userdata("destino")) {
									//$url_redirect = site_url($this->session->userdata("redirect_to_order"));
									$url_redirect = site_url($this->session->userdata("destino"));
								} else {
									$url_redirect = site_url("direccion_envio");
								}
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
					'¿Seguro que desea eliminar esta tarjeta?</p>');
				
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
</section>