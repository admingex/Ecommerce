<div class='titulo-descripcion'>
	<div class='img-hoja'></div>Datos de envío y facturación
	<div class='pleca-titulo'></div>
</div>
<div style='margin-top:18px'></div><div class='encabezado-descripcion'>Editar Razón Social</div>

		
<form id="form_agregar_rs" action="" method="POST">
	<div class='form-label'>Nombre o Raz&oacute;n Social</div>		
	<input type="text" name="txt_razon_social" id="txt_razon_social" size="30" value="<?php if(isset($_POST['txt_razon_social'])) echo htmlspecialchars($_POST['txt_razon_social']); else echo $datos_direccion->company;?>" class="input-tex"/>
	<?php if(isset($reg_errores['txt_razon_social'])) echo ($reg_errores['txt_razon_social']);?>	
	<div class='space-label'></div>
	<div class="form-label">RFC</div>
	<input type="text" name="txt_rfc" id="txt_rfc" size="30" value="<?php if(isset($_POST['txt_rfc'])) echo htmlspecialchars($_POST['txt_rfc']); else echo  $datos_direccion->tax_id_number;?>" class="input-tex"/>
	<?php if(isset($reg_errores['txt_rfc'])) echo ($reg_errores['txt_rfc']);?>		
	<div class='space-label'></div>	
	<div class="form-label">Correo Electr&oacute;nico de Env&iacute;o</div>
	<input type="text" name="txt_email" id="txt_email" value="<?php if(isset($_POST['txt_email'])) echo htmlspecialchars($_POST['txt_email']); else echo  $datos_direccion->email;?>" size="30" class="input-tex"/>
	<?php if(isset($reg_errores['txt_email'])) echo ($reg_errores['txt_email']);?>		
	<div class='space-label'></div>
	<div class="form-label">&nbsp;</div>	
	<div class="check-label"><input type="checkbox" id="chk_default" name="chk_default" <?php if($datos_direccion->id_estatusSi == 3) echo "checked='TRUE'"?>/>&nbsp;Usar como raz&oacute;n social para pago express</div>	
	<div class='space-label'></div>
	<div class='space-label'></div>		
	<div class="form-label">&nbsp;</div>	
	<input type="button" " name="actualizar_rs" id="actualizar_rs" value="Actualizar" onclick="enviar_rs('<?php echo $consecutivo ?>')" /> &oacute;
	<input type="button" name="cancelar" id="cancelar" value="Cancelar" onclick="$('#boton_datos').click();	"/>																	
</form>
