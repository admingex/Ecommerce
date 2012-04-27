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
if($usuarios->num_rows()!=0){

?>
<table width="100%" cellpadding="0" cellspacing="0">
	<thead>
		<tr>
			<th>			
				Usuario				
			</th>		
			<th>
				Correo Electronico
			</th>		
			<th>
				Fecha de Registro
			</th>	
		</tr>
	</thead>	
	<tbody class="contenedor-gris">	
<?php
	foreach($usuarios->result_array() as $usuario){
		echo "<tr>
			    <td class='item-lista borde-derecho'>".$usuario['salutation']."&nbsp;".$usuario['fname']."&nbsp;".$usuario['lname']."</td>
			    <td class='item-lista borde-derecho'>".$usuario['email']."</td>
			    <td class='item-lista borde-derecho'>".$usuario['fecha_registroDt']."</td>
			  </tr>";
	}
?>	
	</tbody>
</table>
<?php 	
}
else{
?>
<p>No existen datos en esta fecha</p>
<?php	
}
?>	
</div>
</section>