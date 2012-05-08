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
if($compras->num_rows()!=0){
	
	foreach ($compras->result_array() as $compra) {
		echo "<br />".$compra['id_compraIn'];
	}	
}
?>
<table width="100%" cellpadding="0" cellspacing="0">
	<thead>
		<tr>
			<th>			
				Nombre Cliente				
			</th>		
			<th>
				Dir Envio
			</th>		
			<th>
				Medio de Pago
			</th>
			<th>
				Monto
			</th>
			<th>
				Razon Social
			</th>
			<th>
				Dir Facturacion			
			</th>
			<th>
				Codigo Aut
			</th>
			<th>
				Fecha Compra
			</th>
		</tr>
	</thead>	
	<tbody class="contenedor-gris">	
		<tr>
			<td>
				1
			</td>
			<td>
				2
			</td>
			<td>
				3
			</td>
			<td>
				4
			</td>
			<td>
				5
			</td>
			<td>
				6
			</td>
			<td>
				7
			</td>	
			<td>
				8
			</td>		
		</tr>
	</tbody>
</table>
</div>
</section>