<section id="descripcion-proceso">
	<div class="titulo-proceso-img">&nbsp;		
	</div>			
	<div class="titulo-proceso">
		<?php echo $subtitle; ?>	
	</div>
</section>
<div id="pleca-punteada"></div>
<div class="contenedor-blanco">
	<div class="instrucciones">Por favor verifica la informaci&oacute;n que aparece abajo. Si tu pago es con tarjeta. Escribe el c&oacute;digo de seguridad que aparece al reverso de la misma. Cuando est&eacute;s listo, da click en finalizar compra para continuar.</div>	
</div>
<section class="contenedor">
	<?php
		if(empty($resultado)){
	?>
	<form id="form_orden_compra" action="<?php echo site_url("orden_compra/checkout"); ?>" method="POST">
	<div class="contenedor-blanco">				
	<table width="100%" cellpadding="0" cellspacing="0">		
		<thead>
			<tr>
				<th>
					Pago env&iacute;o y facturaci&oacute;n					
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
		<table width="100%" cellpadding="0" cellspacing="0" class="">
			<thead>
				<th>
					Productos en la orden 
				</th>
				<th colspan="3">
					&nbsp;
				</th>	
			</thead>
			<tbody class="contenedor-gris"> 				
				<?php
					if ($this->session->userdata('promociones') && $this->session->userdata('promocion')) {			
						$articulos = $this->session->userdata('articulos');
						$total = 0;
						if (!empty($articulos)) {
							foreach ($articulos as $a) 
								$total += $a['tarifaDc'];
						}
				?>	
				<?php 
					if ($this->session->userdata('promocion')) 																	 
						if (!empty($articulos))
							foreach($articulos as $articulo){
				?>
				<tr>
					<td colspan="2" class="titulo-promo-negro2">						
						<?php echo $articulo['tipo_productoVc'] . ", " . $articulo['medio_entregaVc']; ?>
					</td>	
					<td class="titulo-promo-rojo2">$
					</td>				
					<td class="titulo-promo-rojo2" align="right">	
						<?php echo number_format($articulo['tarifaDc'],2,'.',',');?>										
					</td>
				</tr>	
				<?php										    	
							}
				?>				
				<tr>
					<td class="titulo-promo-negro2">
						&nbsp;
					</td>
					<td class="titulo-promo-negro2" align="right">
						IVA
					</td>
					<td class="titulo-promo-rojo2" width="5px">
						$						
					</td>
					<td class="titulo-promo-rojo2" align="right" width="50">
						0.00
					</td>
				</tr>
				
				<tr>
					<td class="titulo-promo-negro2">
						&nbsp;
					</td>
					<td class="titulo-promo-negro2" align="right">
						Total
					</td>
					<td class="titulo-promo-rojo2" width="5px">
						$						
					</td>
					<td class="titulo-promo-rojo2" align="right" width="50">
						<?php echo number_format($total,2,'.',','); ?>
					</td>
				</tr>												
																																																		
					<?php 
					} 
					?>										
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
	}	
	else{
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