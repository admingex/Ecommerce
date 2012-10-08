<form id="form_editar_direccion_envio" action="" method="post">
<div class='form-label'>Calle</div>
<input type="text" name="txt_calle" id="txt_calle" size="30" value="<?php if(isset($_POST['txt_calle'])) echo htmlspecialchars($_POST['txt_calle']); else echo $direccion->address1; ?>"/>
<?php  if(isset($reg_errores['txt_calle'])) echo ($reg_errores['txt_calle']);?>
<div class="space-label"></div>

<div class='form-label'>N&uacute;mero exterior</div>
<input type="text" name="txt_numero" id="txt_numero" maxlength="5" size="5" value="<?php if(isset($_POST['txt_numero'])) echo htmlspecialchars($_POST['txt_numero']); else echo $direccion->address2; ?>"/>
<?php if(isset($reg_errores['txt_numero'])) echo ($reg_errores['txt_numero']);?>
<div class="space-label"></div>

<div class='form-label'>N&uacute;mero interior</div>
<input type="text" name="txt_num_int" id="txt_num_int" maxlength="5" size="5" value="<?php if(isset($_POST['txt_num_int'])) echo htmlspecialchars($_POST['txt_num_int']);  else if (isset($direccion->address4)) echo $direccion->address4; ?>"/>
<?php if(isset($reg_errores['txt_num_int'])) echo ($reg_errores['txt_num_int']);?>
<div class="space-label"></div>

<div class='form-label'>C&oacute;digo postal</div>
<input type="text" name="txt_cp" id="txt_cp" maxlength="5" size="5" value="<?php if(isset($_POST['txt_cp'])) echo htmlspecialchars($_POST['txt_cp']); else echo $direccion->zip; ?>"/>
<input type="button" id="btn_cp" name="btn_cp" value="&nbsp;" class="llenar_cp"/></span>
<?php if(isset($reg_errores['txt_cp'])) echo ($reg_errores['txt_cp']);?>
<div class="space-label"></div>

<div class='form-label'>Pa&iacute;s</div>
<select id="sel_pais" name="sel_pais">
	<?php
	if (isset($lista_paises_think)) {
		$id_pais = "MX";				//Mexico por default
		$id_pais = $direccion->codigo_paisVc;
		if (isset($_POST['sel_pais']))	//pais seleccionado para la captura
			$id_pais = $_POST['sel_pais'];
			
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
<div class="space-label"></div>

<div class="div_otro_pais">
<div class='form-label'>Estado</div>
<input type="text" name="txt_estado" id="txt_estado" size="30" value="<?php if(isset($_POST['txt_estado'])) echo htmlspecialchars($_POST['txt_estado']); else echo $direccion->state; ?>"/>
<?php if(isset($reg_errores['txt_estado'])) echo ($reg_errores['txt_estado']);?>
</div>
<div class="space-label"></div>

<div class="div_otro_pais">
<div class='form-label'>Ciudad o municipio</div>
<input type="text" name="txt_ciudad" id="txt_ciudad" size="30" value="<?php if(isset($_POST['txt_ciudad'])) echo htmlspecialchars($_POST['txt_ciudad']); else echo $direccion->city; ?>"/>
<?php if(isset($reg_errores['txt_ciudad'])) echo ($reg_errores['txt_ciudad']);?>
</div>
<div class="space-label"></div>

<div class="div_otro_pais">
<div class='form-label'>Colonia</div>
<input type="text" name="txt_colonia" id="txt_colonia" size="30" value="<?php if(isset($_POST['txt_colonia'])) echo htmlspecialchars($_POST['txt_colonia']); else echo $direccion->address3; ?>"/>
<?php if(isset($reg_errores['txt_colonia'])) echo ($reg_errores['txt_colonia']);?>
</div>
<div class="space-label"></div>

<div class="div_mexico">
<div class='form-label'>Estado</div>
<select id="sel_estados" name="sel_estados">
	<option value="">Seleccionar</option>
	<?php
	if (isset($lista_estados_sepomex)) {
		//$clave_estado = "";
		$clave_estado = $direccion->state;
		if (isset($_POST['sel_estados']))
			$clave_estado = $_POST['sel_estados'];
		foreach($lista_estados_sepomex as $estado)
		{
			if ($clave_estado == $estado->clave_estado) {
				echo "<option value='".$estado->clave_estado."' selected='true'>".
					$estado->estado.
					"</option>";
			} else {
				echo "<option value='".$estado->clave_estado."'>".
					$estado->estado.
					"</option>";
			}
		}
	}
	?>
</select>
<?php if(isset($reg_errores['sel_estados'])) echo ($reg_errores['sel_estados']);?>
</div>
<div class="space-label"></div>

<div class="div_mexico">
<div class='form-label'>Ciudad o municipio</div>
<select id="sel_ciudades" name="sel_ciudades">
	<option value="">Seleccionar</option>
	<?php
	if (isset($lista_ciudades_sepomex)) {
		//$clave_ciudad = "";
		$clave_ciudad = $direccion->city;
		if (isset($_POST['sel_ciudades']))
			$clave_ciudad = $_POST['sel_ciudades'];
		
		//echo gettype($lista_ciudades_sepomex) ==   'object';
		//excepcion del DF
		if (gettype($lista_ciudades_sepomex) == 'object') {
			echo "<option value='". $lista_ciudades_sepomex->clave_ciudad. "' selected='true'>".
					$lista_ciudades_sepomex->ciudad.
					"</option>";
		} else {
			foreach($lista_ciudades_sepomex as $ciudad)
			{
				if ($clave_ciudad == $ciudad->clave_ciudad) {
					echo "<option value='". $ciudad->clave_ciudad. "' selected='true'>".
						$ciudad->ciudad.
						"</option>";
				} else if ($ciudad->ciudad != ''){
					echo "<option value='". $ciudad->clave_ciudad. "'>".
						$ciudad->ciudad.
						"</option>";
				}
			}
		}
	}
	?>
</select>
<?php  if(isset($reg_errores['sel_ciudades'])) echo ($reg_errores['sel_ciudades']);?>
</div>
<div class="space-label"></div>

<div class="div_mexico">
<div class='form-label'>Colonia</div>
<select id="sel_colonias" name="sel_colonias">
	<option value="">Seleccionar</option>
	<?php
	if (isset($lista_colonias_sepomex)) {
		//$clave_colonia = "";
		$clave_colonia = $direccion->address3;
		if (isset($_POST['sel_colonias']))
			$clave_colonia = $_POST['sel_colonias'];
		foreach($lista_colonias_sepomex as $colonia)
		{
			if ($clave_colonia == $colonia->colonia) {
				echo "<option value='".$colonia->colonia."' selected='true'>".
					$colonia->colonia.
					"</option>";
			} else {
				echo "<option value='".$colonia->colonia."'>".
					$colonia->colonia.
					"</option>";
			}
		}
	}
	?>
</select>
<?php if(isset($reg_errores['sel_colonias'])) echo ($reg_errores['sel_colonias']);?>
</div>
<div class="space-label"></div>

<div class='form-label'>Tel&eacute;fono</div>
<input type="text" name="txt_telefono" id="txt_telefono" size="30" value="<?php if (isset($_POST['txt_telefono'])) echo htmlspecialchars($_POST['txt_telefono']); else echo $direccion->phone; ?>" />
<?php if(isset($reg_errores['txt_telefono'])) echo ($reg_errores['txt_telefono']);?>
<div class="space-label"></div>

<div class='form-label'>Referencia</div>
<textarea type="text" name="txt_referencia" id="txt_referencia" cols="30" ><?php if(isset($_POST['txt_referencia'])) echo htmlspecialchars($_POST['txt_referencia']); else if (isset($direccion->referenciaVc)) echo htmlspecialchars($direccion->referenciaVc); ?></textarea>
<div class="space-label"></div>					

<div class='form-label'></div>
<input type="checkbox" id="chk_default" name="chk_default"/>Usar para pago express
<div class="space-label"></div>

<div class='form-label'></div>					
<input type="button" id="guardar_direccion" name="guardar_direccion" value="Guardar"  onclick="enviar_dir_envio(<?php echo $direccion->id_consecutivoSi?>)"/> ó
<input type="button" name="cancelar" id="cancelar" value="Cancelar" onclick="$('#boton_datos').click();	"/>

</form>
