<section id="descripcion-proceso">
	<div class="titulo-proceso-img">&nbsp;		
	</div>			
	<div class="titulo-proceso">
		<?php echo $subtitle; ?>	
	</div>
</section>
<div id="pleca-punteada"></div>
<div class="contenedor-blanco">
	<?php if(!isset($deposito) && empty($resultado)){ ?>			
	<div class="instrucciones">Por favor verifica la informaci&oacute;n que aparece abajo. Si tu pago es con tarjeta, escribe el c&oacute;digo de seguridad que aparece en tu tarjeta. Cuando est&eacute;s listo, da click en finalizar compra para continuar.</div>
	<?php }?>	
</div>
<section class="contenedor">
	<?php
		if (empty($resultado) && empty($pago_deposito)) {	//Se muestra el resumen de la orden de compra si no viene del checkout
	?>
	<form id="form_orden_compra" action="<?php echo site_url("orden_compra/checkout"); ?>" method="POST">
	<div class="contenedor-blanco">				
	<table width="100%" cellpadding="0" cellspacing="0">		
		<thead>
			<tr>
				<th>
					Pago, Env&iacute;o y Facturaci&oacute;n					
				</th>
				<th>&nbsp;
				</th>				
			</tr>						
		</thead>
		<tbody class="contenedor-gris">
			<tr>
				<td colspan="2">
				<?php
					include ('orden_compra/resumen.html');
				?>	
				</td>				
			</tr>						
		</tbody>
	</table>		
	</div>
	
	<div class="contenedor-blanco">
		<table cellpadding="0" cellspacing="0">
			<thead>				
				<th colspan="3">
					Productos en la orden 
				</th>
				<th>&nbsp;</th>				
			</thead>
			<tbody class="contenedor-gris"> 
				<?php
					foreach ($detalle_promociones['descripciones_promocion'] as $promociones) {
						if (strstr($promociones['promocion']->descripcionVc, '|' )) {
							$mp = explode('|', $promociones['promocion']->descripcionVc);
							$nmp = count($mp);
							if ($nmp == 2) {
								$desc_promo = $mp[0];
							} else if($nmp==3) {
								$desc_promo = $mp[1];
							}
						} else {
							$desc_promo = $promociones['promocion']->descripcionVc;
						}
						//indicador de que requiere envío
						$promo_requiere_envio = $promociones['promocion']->requiere_envio;
						
						//sacar la descripción que se mostrará de la promoción
						foreach ($promociones['articulos'] as $articulo) {
							echo "<tr>
								<td colspan='2' class='titulo-promo-negro2'>".$desc_promo;
							if ($articulo['issue_id']) {
								foreach ($detalle_promociones['tipo_productoVc'] as $k => $v) {
									if ($k == $articulo['issue_id']) {
										if (strstr($v, '|' )) {
											$mp = explode('|',$v);
											$nmp = count($mp);
											if ($nmp == 2) {
												$desc_art = $mp[0];
											} else if ($nmp == 3) {
												$desc_art = $mp[1];
											}
										} else {
											$desc_art = $v;
										}
									}
								}
							} else {
								$desc_art = $articulo['tipo_productoVc'];
							}
							echo "<div>".$desc_art."</div>";
							
							//por si puede agregar una dirección de envío distinta
							if ($promo_requiere_envio) {
								//para ver si hay más de una dirección de envío en la compra
								$des = $this->session->userdata('dse');
								//id de la promoción que se quiere asociar con otra dirección
								$id_promo = $promociones['promocion']->id_promocionIn;
								## Test
								//cero es que no tiene más de una dirección asociada 
								$id_dir_envio = (!empty($des)) ? $des[$id_promo]: 0;
								if ($des) {
									//buscar $id_promo en la vista de la dirección
									include("orden_compra/detalle_envio_adicional.html");
								}
								echo "<br/><span>&nbsp;<a href='" . site_url('direccion_envio/direccion_adicional/'.$id_promo) . "'>Usar otra dirección de envío</a></span>";
							}
							echo 
								"</td>
								<td class='titulo-promo-rojo2' align='right'>$</td>
								<td class='titulo-promo-rojo2' align='right'>".number_format($promociones['promocion']->total_promocion, 2, '.', ',')."&nbsp;".$detalle_promociones['moneda']."</td>".
								"</tr>";
						}
					}
				?>
				<tr>					
					<td class="titulo-promo-negro2" align="right" colspan="2">
						IVA
					</td>
					<td class="titulo-promo-rojo2" align="right">
						$						
					</td>
					<td class="titulo-promo-rojo2" align="right">
						0.00 <?php echo $detalle_promociones['moneda'];?>
					</td>
				</tr>
				
				<tr>					
					<td class="titulo-promo-negro2" align="right" colspan="2" style="width: 80%">
						Total
					</td>
					<td class="titulo-promo-rojo2" style="width: 5%;" align="right">
						$						
					</td>
					<td class="titulo-promo-rojo2" style="width: 150px" align="right">
						<?php echo number_format(($detalle_promociones['total_pagar']),2,'.',',')."&nbsp;".$detalle_promociones['moneda']; ?>
					</td>
				</tr>																																																																											
				<tr>
					<td colspan="4" class="titulo-promo-negro2" align="right">						
						<input type="submit" id="enviar" name="enviar" value="&nbsp;" class="finalizar_compra"/>						
					</td>
					
				</tr>
			</tbody>
		</table>
	</div>
	</form>	
	<?php
	} else {	//Se muestra el resultado de la petición de cobro
		include ('orden_compra/respuesta_cobro.html');
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
							$( this ).dialog( "close" );
							//$url_redirect = site_url('direccion_envio');
						}
					}
				});
			});
		</script>
	</div>	
</section>