
<div id="pleca-punteada"></div>
<div class="contenedor-blanco">
	<?php if (!isset($deposito) && empty($resultado)) { ?>			
	<div class="instrucciones">Por favor verifica la informaci&oacute;n que aparece abajo. Si tu pago es con tarjeta, escribe el c&oacute;digo de seguridad que aparece en tu tarjeta. Cuando est&eacute;s listo, da click en finalizar compra para continuar.</div>
	<?php }?>	
</div>
<section class="contenedor">
	<?php
		if (empty($resultado) && empty($pago_deposito)) {	//Se muestra el resumen de la orden de compra si no viene del checkout
	?>
	<form id="form_orden_compra" action="<?php echo site_url("suscripcion_express/checkout"); ?>" method="POST" >
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
					include ('resumen.html');
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
					/*echo "<pre>";
					//print_r($this->session->all_userdata());
					print_r($detalle_promociones);
					echo "</pre>";*/
					//IVA inicial de la compra
					$iva_compra = 0.0;
					$iva_message = "";
					
					foreach ($detalle_promociones['descripciones_promocion'] as $promociones) {
						/*	
						echo "<pre>";
							print_r($promociones);
						echo "<pre>";
						*/
						
						//exit;
						//para los artículos de las promos que lleven IVA
						
						$iva_message  = "";		//en principio no lleva para la promocion
						//revisar si se cobra IVA
						if ($promociones['promocion']->iva_promocion > 0) { //($articulo['taxableBi']) {
							$iva_compra += $promociones['promocion']->iva_promocion;	//ya se calcula desde el API para el la promoción
							$iva_message  = "costo m&aacute;s IVA";	//en principio no lleva para la promocion
						}
						if (strstr($promociones['promocion']->descripcionVc, '|' )) {
							$mp = explode('|', $promociones['promocion']->descripcionVc);
							$nmp = count($mp);
							if ($nmp == 2) {
								$desc_promo = $mp[0];
							} else if ($nmp == 3) {
								$desc_promo = $mp[1];
							}
						} else {
							$desc_promo = $promociones['promocion']->descripcionVc;
						}
						//indicador de que requiere envío
						$promo_requiere_envio = $promociones['promocion']->requiere_envio;
						echo "<tr><td colspan='4' class='titulo-promo-rojo2'>".$desc_promo."</td></tr>";
						//sacar la descripción que se mostrará de la promoción
						foreach ($promociones['articulos'] as $articulo) {
							echo 
							"<tr>
								<td colspan='2' class='titulo-promo-negro2'>";
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
															
								$desc_art = $articulo['tipo_productoVc']."&nbsp;";								
								foreach($detalle_promociones['articulo_oc'] as $i => $oc){
									if($i == $articulo['oc_id'] ){
										$desc_art.= $oc;	
									}																	
								}																									
																							
							}
							echo "<div>".$desc_art."<div class='label-promo-rojo'>$iva_message</div></div>";
							
							//por si puede agregar una dirección de envío distinta
							if ($promo_requiere_envio) {
									
								//debería haber:
								$direcciones_envio = array();	//arreglo (id_promocion => id_direccion)
								
								//para ver si hay más de una dirección de envío en la compra
								if ($this->session->userdata('dse')) {
									$direcciones_envio = $this->session->userdata('dse'); 
								} else if (isset($dse)) {	//se pasa en el data del controller de la orden en ek resumen.
									$direcciones_envio = $dse;
								}
								
								//id de la promoción que se quiere asociar con otra dirección
								$id_promo = $promociones['promocion']->id_promocionIn;
								
								//para mostrar las direcciones de envío en caso de ser requerido
								if (!empty($direcciones_envio)) {
									
									//buscar $id_promo en la vista de la dirección
									include("detalle_envio_adicional.html");
									//print_r($direcciones_envio);
								}								
								//var_dump($direcciones);
							}
							//precio de la promoción
							echo
								"</td>
								<td class='titulo-promo-rojo2' align='right'>$</td>
								<td class='titulo-promo-rojo2' align='right'>" . number_format($articulo['tarifaDc'], 2, '.', ',') . "&nbsp;" . $detalle_promociones['moneda'] . "</td>" .
							"</tr>";
						}
					}
				?>
				<tr>					
					<td class="titulo-promo-negro2" align="right" colspan="2">IVA</td>
					<td class="titulo-promo-rojo2" align="right">$</td>
					<td class="titulo-promo-rojo2" align="right">
						<?php
							//el iva de la compra calculado arriba
							echo number_format($iva_compra, 2, '.', ',')." ".$detalle_promociones['moneda'];
						?>
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
						<?php echo number_format(($detalle_promociones['total_pagar'] + $iva_compra), 2, '.', ',')."&nbsp;".$detalle_promociones['moneda']; ?>
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
		include ('respuesta_cobro.html');
	}		
	
	?>
	<?php
		if (!empty($mensaje)) {
	?>
	<div id="dialog" title="Resultado">
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
