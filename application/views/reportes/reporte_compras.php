<section class="contenedor">
<div class="contenedor-blanco">
<script type="text/javascript">
$(function(){
    $("#fecha_inicio").datepicker({changeMonth: true, changeYear: true, autoSize: true });
    $("#fecha_inicio").datepicker( "option", "dateFormat", "yy/mm/dd");
    $("#fecha_inicio").datepicker( "option", "showAnim", "slideDown");
    $("#fecha_inicio").datepicker( "setDate" , "<?php echo $fecha_inicio?>");

    $("#fecha_fin").datepicker({changeMonth: true, changeYear: true, autoSize: true });
    $("#fecha_fin").datepicker( "option", "dateFormat", "yy/mm/dd");
    $("#fecha_fin").datepicker( "option", "showAnim", "slideDown");
    $("#fecha_fin").datepicker( "setDate" , "<?php echo $fecha_fin?>");
});
</script>
<?php 
echo "<p>Reporte del dia ".$fecha_inicio." al dia ".$fecha_fin."</p>";
?>
<form name="selecciona_intervalo" action="" method="POST">
Fecha Inicio:<input type="text" name="fecha_inicio" id="fecha_inicio" value="<?php echo $fecha_inicio;?>" />
Fecha Fin: <input type="text" name="fecha_fin" id="fecha_fin" value="<?php echo $fecha_fin;?>" />
<input type="submit" name="Consultar" value="Consultar" />
</form>
<?php 
		
	if(count($compras)>0){
?>	
		<table width="100%" cellpadding="0" cellspacing="0">
			<thead>
				<tr>
					<th class="doble-linea">			
						Nombre Cliente				
					</th>		
					<th class="doble-linea">
						Direccion de Envio
					</th>		
					<th class="doble-linea">
						Medio de Pago
					</th>
					<th class="doble-linea">
						Monto
					</th>
					<th class="doble-linea">
						Razon Social
					</th>
					<th class="doble-linea">
						Dir Facturacion			
					</th>
					<th class="doble-linea">
						Codigo Aut
					</th>
					<th class="doble-linea">
						Fecha Compra
					</th>
				</tr>
			</thead>
			<tbody class="contenedor-gris">
			<?php 			
				foreach ($compras as $nueva_compra) {
										
					echo  " <tr>
								<td class='item-lista'>
									".$nueva_compra['cliente']->salutation."
								</td>
								<td class='item-lista'>
									".$nueva_compra['dir_envio']."
								</td>
								<td class='item-lista'>
									".$nueva_compra['medio_pago']."
								</td>
								<td class='item-lista'>
									".$nueva_compra['monto']."
								</td>
								<td class='item-lista'>
								</td>
								<td class='item-lista'>
								</td>
								<td class='item-lista'>
								</td>
								<td class='item-lista'>
									".$nueva_compra['fecha_compra']."
								</td>
							</tr>";	
				}
			?>	
			</tbody>
		</table>	
<?php		
	}
?>		
	
</div>
</section>