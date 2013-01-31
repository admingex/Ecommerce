<?php
echo "<pre>";
	print_r($_POST);
echo "</pre>";
	
/*		
echo "aqui<pre>";
	print_r($this->session->userdata('promo'));
echo "</pre>";		
*/
		
?>

<div class="contenedor-gris">
<table width="100%">	
	<form id="form_registro_usuario" action="<?php echo site_url('suscripcion_express/pago'); ?>" method="POST">	
	<tr>
				<td class="label">
					N&uacute;mero de tarjeta
				</td>
				<td>
					<span class="alinear_izquierda">
						<input type="text" name="txt_numeroTarjeta" id="txt_numeroTarjeta" maxlength="16" autocomplete="off" value="<?php if(isset($_POST['txt_numeroTarjeta'])) echo htmlspecialchars($_POST['txt_numeroTarjeta']);?>"/>
					</span>
					<span class="asterisco">&nbsp;</span>
					<span class="error_mensaje"><?php if(isset($reg_errores['txt_numeroTarjeta'])) echo ($reg_errores['txt_numeroTarjeta']);?></span>
				</td>
			</tr>
			<tr>
				<td>
					&nbsp;
				</td>
				<td>
					<span class="instrucciones_cursivas">Datos del titular (Escríbelos tal como aparecen en tu tarjeta)</span>	
				</td>
			</tr>
			<tr>
				<td class="label">
					Nombre del Titular
				</td>
				<td>
					<span class="alinear_izquierda">
						<input type="text" name="txt_nombre" id="txt_nombre" autocomplete="off" value="<?php if(isset($_POST['txt_nombre'])) echo htmlspecialchars($_POST['txt_nombre']);?>"/>
					</span>
					<span class="asterisco">&nbsp;</span>
					<span class="error_mensaje"><?php if(isset($reg_errores['txt_nombre'])) echo ($reg_errores['txt_nombre']);?></span>
				</td>
			</tr>
			<tr>
				<td class="label">
					Apellido Paterno
				</td>
				<td>
					<span class="alinear_izquierda">
						<input type="text" name="txt_apellidoPaterno" id="txt_apellidoPaterno" autocomplete="off" value="<?php if(isset($_POST['txt_apellidoPaterno'])) echo htmlspecialchars($_POST['txt_apellidoPaterno']);?>"/>
					</span>
					<span class="asterisco">&nbsp;</span>
					<span class="error_mensaje"><?php if(isset($reg_errores['txt_apellidoPaterno'])) echo ($reg_errores['txt_apellidoPaterno']);?></span>
				</td>
			</tr>
			<tr>
				<td class="label">
					Apellido Materno
				</td>
				<td>
					<span class="alinear_izquierda">
						<input type="text" name="txt_apellidoMaterno" id="txt_apellidoMaterno" autocomplete="off" value="<?php if(isset($_POST['txt_apellidoMaterno'])) echo htmlspecialchars($_POST['txt_apellidoMaterno']);?>"/>
					</span>
					<span class="error_mensaje"><?php if(isset($reg_errores['txt_apellidoMaterno'])) echo ($reg_errores['txt_apellidoMaterno']);?></span>
				</td>
			</tr>
			<tr>
				<?php
					date_default_timezone_set("America/Mexico_City");
					$mes = isset($_POST['sel_mes_expira']) ? $_POST['sel_mes_expira'] : 0;
					$anio = isset($_POST['sel_anio_expira']) ? $_POST['sel_anio_expira'] : 0;
					$anio_actual = date('Y');
				?>
				<td class="label">
					Fecha de Expiraci&oacute;n
				</td>
				<td>
					<span class="alinear_izquierda">
						<select id="sel_mes_expira" name="sel_mes_expira">
						<?php 
							for($i = 1; $i <= 12; $i++) {
								$zero = ($i < 10) ? "0" : "";
								if ($i == $mes)
									echo "<option value='$zero$i' selected='true'>$zero$i</option>";
								else 
									echo "<option value='$zero$i'>$zero$i</option>";
							} 
						?>
						</select>
						<select id="sel_anio_expira" name="sel_anio_expira">
							<?php 
								for($i = $anio_actual; $i <= $anio_actual + 7; $i++) {	/*ajustar el periodo de años con constantes/globales en el config.*/
									if ($i == $anio) 
										echo "<option value='".$i."' selected='true'>$i</option>";
									else 
										echo "<option value='".$i."'>$i</option>";
								} 
							?>
						</select>
					</span>
					<span class="asterisco">&nbsp;</span>
					<span class="error_mensaje"><?php if(isset($reg_errores['fecha_error'])) echo ($reg_errores['fecha_error']);?></span>
				</td>
			</tr>
	<tr>
		<td>&nbsp;</td>
		<td><input type="submit" id="enviar" value="&nbsp;" class="finalizar_compra"/></td>
	</tr>			
	</form>
</table>
</div>