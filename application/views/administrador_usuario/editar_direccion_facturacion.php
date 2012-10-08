<form id="form_agregar_direccion" action="" method="POST">
<div class='form-label'>Calle</div>		
<input type="text" name="txt_calle" id="txt_calle" size="30" value="<?php if(isset($_POST['txt_calle'])) echo htmlspecialchars($_POST['txt_calle']); else echo $datos_direccion->calle;?>"/>								
<?php if(isset($reg_errores['txt_calle'])) echo ($reg_errores['txt_calle']);?>
<div class="space-label"></div>

<div class='form-label'>N&uacute;mero exterior</div>
<input type="text" name="txt_numero" id="txt_numero" maxlength="5" size="5" value="<?php if(isset($_POST['txt_numero'])) echo htmlspecialchars($_POST['txt_numero']); else echo $datos_direccion->num_ext;?>" />
<?php if(isset($reg_errores['txt_numero'])) echo ($reg_errores['txt_numero']);?>
<div class="space-label"></div>
			
<div class='form-label'>N&uacute;mero interior</div>
<input type="text" name="txt_num_int" id="txt_num_int" maxlength="5" size="5" value="<?php if(isset($_POST['txt_num_int'])) echo htmlspecialchars($_POST['txt_num_int']); else echo $datos_direccion->num_int;?>"/>
<div class="space-label"></div>

<div class='form-label'>C&oacute;digo postal</div>
<input type="text" name="txt_cp" id="txt_cp" maxlength="5" size="5" value="<?php if(isset($_POST['txt_cp'])) echo htmlspecialchars($_POST['txt_cp']); else echo $datos_direccion->cp;?>"/>
<?php if(isset($reg_errores['txt_cp'])) echo ($reg_errores['txt_cp']);?>
<div class="space-label"></div>

<div class='form-label'>Pa&iacute;s</div>
<select id="sel_pais" name="sel_pais">
	<?php
	if (isset($lista_paises_think)) {
		$id_pais = "MX";
		if (isset($_POST['sel_pais']))	//pais seleccionado para la captura
			$id_pais = $_POST['sel_pais'];
		//echo '<option value="AM">America</option>';	
		foreach($lista_paises_think->result() as $pais)
		{
			if ($id_pais == $pais->id_pais) {
				echo "<option value='".$pais->id_pais."' selected='true'>".
					$pais->pais.
					"</option>";
			} else {
				echo "<option value='".$pais->id_pais."'>".
					$pais->pais.
					"</option>";
			}
		}
	}
	?>
</select>
<?php if(isset($reg_errores['sel_pais'])) echo ($reg_errores['sel_pais']);?>

<div class='form-label'>Estado</div>
<input type="text" name="txt_estado" id="txt_estado" size="30" value="<?php if(isset($_POST['txt_estado'])) echo htmlspecialchars($_POST['txt_estado']); else echo $datos_direccion->estado;?>"/>
<?php if(isset($reg_errores['txt_estado'])) echo ($reg_errores['txt_estado']);?>
<div class="space-label"></div>

<div class='form-label'>Ciudad</div>
<input type="text" name="txt_ciudad" id="txt_ciudad" size="30" value="<?php if(isset($_POST['txt_ciudad'])) echo htmlspecialchars($_POST['txt_ciudad']); else echo $datos_direccion->ciudad;?>"/>
<?php if(isset($reg_errores['txt_ciudad'])) echo ($reg_errores['txt_ciudad']);?>
<div class="space-label"></div>

<div class='form-label'>Colonia</div>
<input type="text" name="txt_colonia" id="txt_colonia" size="30" value="<?php if(isset($_POST['txt_colonia'])) echo htmlspecialchars($_POST['txt_colonia']); else echo $datos_direccion->colonia;?>"/>
<?php if(isset($reg_errores['txt_colonia'])) echo ($reg_errores['txt_colonia']);?>
<div class="space-label"></div>
<div class='form-label'>&nbsp;</div>
<input type="checkbox" id="chk_default" name="chk_default"/>					
Usar como direcci&oacute;n de facturaci&oacute;n para pago express
<div class="space-label"></div>

<div class='form-label'>&nbsp;</div>
<input type="button" name="guardar_direccion" id="guardar_direccion" value="Guardar" onclick="enviar_dir_facturacion('<?php echo $datos_direccion->id_consecutivoSi?>')" />
<input type="button" name="cancelar" id="cancelar" value="Cancelar" onclick="$('#boton_datos').click();	"/>									
				
</form>