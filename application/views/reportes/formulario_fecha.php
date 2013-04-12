<section class="contenedor" style='padding: 5px 10px 20px 10px'>
<script type="text/javascript">
    $(function(){
    $("#fecha_inicio").datepicker({changeMonth: true, changeYear: true, autoSize: true });
    $("#fecha_inicio").datepicker( "option", "dateFormat", "yy/mm/dd");
    $("#fecha_inicio").datepicker( "option", "showAnim", "slideDown");
    $("#fecha_inicio").datepicker( "setDate" , "<?php if(isset($fecha_inicio))echo $fecha_inicio?>");

    $("#fecha_fin").datepicker({changeMonth: true, changeYear: true, autoSize: true });
    $("#fecha_fin").datepicker( "option", "dateFormat", "yy/mm/dd");
    $("#fecha_fin").datepicker( "option", "showAnim", "slideDown");
    $("#fecha_fin").datepicker( "setDate" , "<?php if(isset($fecha_fin)) echo $fecha_fin?>");
});
</script>

<div class="float_izq">
	<?php
		//echo "<p>Reporte del dia ".$fecha_inicio." al dia ".$fecha_fin."</p>";		
	?>	
</div>
<div style="float: right; padding-bottom: 5px">
	<div style="float: left; margin-right: 5px">
		<?php echo anchor(site_url('reporte/usuarios'),'Reporte Usuarios'); ?>
	</div>
	<div style="float: left; margin-right: 5px">
		<?php echo anchor(site_url('reporte/compras'),'Reporte Compras'); ?>
	</div>
	<div style="float: left; margin-right: 5px">
		<?php echo anchor(site_url('reporte/compras_cliente'),'Compras por Cliente'); ?>
	</div>	
	<div style="float: left">
		<?php echo anchor(site_url('logout'),'cerrar sesion'); ?>
	</div>		
</div>
<div id="pleca-gris"></div>
<?php
if(isset($fecha_inicio))
	if(!empty($fecha_inicio)){
?>
<form name="selecciona_intervalo" action="" method="POST">
	<table>
		<tr>
			<td class="label">Fecha Inicio:</td>
			<td><input type="text" name="fecha_inicio" id="fecha_inicio" value="<?php echo $fecha_inicio;?>" /></td>
			<td class="label">Fecha Fin: </td>
			<td><input type="text" name="fecha_fin" id="fecha_fin" value="<?php echo $fecha_fin;?>" /></td>
			<td><input type="submit" name="Consultar" value="Consultar" class="boton" /></td>
		</tr>
		<tr><td colspan="5"><?php echo "<div class='validation_message'>".$error."</div>"; ?></td></tr>
	</table>
</form>
<?php
} 
if(!isset($fecha_inicio)){
?>
<form name="cliente" action="" method="POST">
	<table>
		<tr>
			<td class="label">id_cliente</td>
			<td><input type="text" name="id_cliente" id="id_cliente" value="<?php if(isset($id_cliente)) echo $id_cliente;?>" /></td>			
			<td><input type="submit" name="Consultar" value="Consultar" class="boton" /></td>
		</tr>
		<tr><td colspan="5"><?php echo "<div class='validation_message'>".$error."</div>"; ?></td></tr>
	</table>
</form>
<?php
} 
?>
<div id="pleca-gris"></div>