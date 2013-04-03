
<?php
/*
echo "<pre>";
	print_r($this->session->all_userdata());
echo "</pre>";		
*/
		
?>

<div class="contenedor-gris">
<table width="100%">	
	<form id="form_registro_usuario" action="" method="POST">
			<tr>
				<td class="label">
					Banco Emisor
				</td>
				<td>
					<span class="alinear_izquierda">
						<select id="sel_tipo_tarjeta" name="sel_tipo_tarjeta" onchange="revisa_amex(this.value)">
							<?php
							if (isset($lista_tipo_tarjeta)) {
								$tipo_tarjeta_id = 0;
								if (isset($_POST['sel_tipo_tarjeta']))
									$tipo_tarjeta_id = $_POST['sel_tipo_tarjeta'];
								foreach($lista_tipo_tarjeta as $tipo_banco)
								{
									if($tipo_banco->id_tipo_tarjeta != '17'){
										if ($tipo_tarjeta_id == $tipo_banco->id_tipo_tarjeta) {
											echo "<option value='".$tipo_banco->id_tipo_tarjeta."' selected='true'>".
												$tipo_banco->descripcion.
												"</option>";											
										} else {
											echo "<option value='".$tipo_banco->id_tipo_tarjeta."'>".
												$tipo_banco->descripcion.
												"</option>";
										}
									}	
								}
							}
							?>
							<option value='17' <?php if($tipo_tarjeta_id== 17) echo "selected='true'";?>>OTRO</option>
						</select>
					</span>
					<span class="asterisco">&nbsp;</span>
					<span class="error_mensaje"><?php if(isset($reg_errores['sel_tipo_tarjeta'])) echo ($reg_errores['sel_tipo_tarjeta']);?></span>
				</td>
			</tr>	
			<tr>
				<td class="label">
					N&uacute;mero de tarjeta
				</td>
				<td>
					<span class="alinear_izquierda">
						<input type="text" name="txt_numeroTarjeta" id="txt_numeroTarjeta" maxlength="16" autocomplete="off" value="<?php if(isset($_POST['txt_numeroTarjeta'])) echo htmlspecialchars($_POST['txt_numeroTarjeta']);?>"/>
					</span>
					<span class="asterisco">&nbsp;</span>
					<?php if(isset($reg_errores['txt_numeroTarjeta'])) echo "<span class='error2'>".($reg_errores['txt_numeroTarjeta'])."</span>";?>
				</td>
			</tr>
			<tr>
				<td class="label">
					<div id="tarjeta">C&oacute;digo de seguridad:								
					</div>		
				</td>
				<td>						
					
						<input type="text" name="txt_codigo" id="txt_codigo" maxlength="4" size="5" autocomplete="off"
							value="<?php if(isset($_POST['txt_codigo'])) echo htmlspecialchars($_POST['txt_codigo']);?>"/>													
						<a href="#" class="tip_trigger">
							<div class="interrogacion" style="display: inline-block"></div>			
							<span class="tip">
								<div style="margin-left: auto; margin-right: auto; text-align: center"><img src="<?php echo base_url();?>images/cvv2_code.jpg" /></div>
							</span>			
						</a>	
						<?php if(isset($reg_errores['txt_codigo'])) echo ("<div class='error'>".$reg_errores['txt_codigo']."</div>");?>						
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
					<?php if(isset($reg_errores['txt_nombre'])) echo "<span class='error'>".($reg_errores['txt_nombre'])."</span>";?>
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
					<?php if(isset($reg_errores['txt_apellidoPaterno'])) echo "<span class='error'>".($reg_errores['txt_apellidoPaterno'])."</span>";?>
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
					<?php if(isset($reg_errores['txt_apellidoMaterno'])) echo "<span class='error'>".($reg_errores['txt_apellidoMaterno'])."</span>";?>
				</td>
			</tr>
			<tr>
				<?php					
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
				<td class="titulo-promo-rojo ammex" colspan="2">
					Información requerida para realizar el pago con Tarjetas American Express				
				</td>
			</tr>
			<tr>
				<td class="label ammex">
					Calle y n&uacute;mero
				</td>
				<td class="ammex">
					<span class="alinear_izquierda">
						<input type="text" name="txt_calle" id="txt_calle" size="40" autocomplete="off" value="<?php if(isset($_POST['txt_calle'])) echo htmlspecialchars($_POST['txt_calle']);?>"/>
					</span>
					<span class="asterisco">&nbsp;</span>
					<?php if(isset($reg_errores['txt_calle'])) echo "<span class='error'>".($reg_errores['txt_calle'])."</span>";?>
				</td>
			</tr>	
			<tr>
				<td class="label ammex">
					C&oacute;digo postal
				</td>
				<td class="ammex">
					<span class="alinear_izquierda">
						<input type="text" name="txt_cp" id="txt_cp" maxlength="5" size="5" autocomplete="off" value="<?php if(isset($_POST['txt_cp'])) echo htmlspecialchars($_POST['txt_cp']);?>"/>
					</span>
					<span class="asterisco">&nbsp;</span>
					<?php if(isset($reg_errores['txt_cp'])) echo "<span class='error'>".($reg_errores['txt_cp'])."</span>";?>
				</td>
			</tr>
			<tr>
				<td class="label ammex">
					Pa&iacute;s
				</td>
				<td class="ammex">
					<span class="alinear_izquierda">
						<select id="sel_pais" name="sel_pais">
						<?php
						if (isset($lista_paises_amex)) {
							$id_pais = "MEX";				//Mexico por default
							if (isset($_POST['sel_pais']))	//pais seleccionado para la captura
								$id_pais = $_POST['sel_pais'];
								
							foreach($lista_paises_amex->result() as $pais)
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
				</span>
				<span class="asterisco">&nbsp;</span>
				<span class="error_mensaje"><?php if(isset($reg_errores['sel_pais'])) echo ($reg_errores['sel_pais']);?></span>
				</td>
			</tr>
			<tr>
				<td class="label ammex">
					Estado
				</td>
				<td class="ammex">
					<span class="alinear_izquierda">
						<input type="text" name="txt_estado" id="txt_estado" size="30" autocomplete="off" value="<?php if(isset($_POST['txt_estado'])) echo htmlspecialchars($_POST['txt_estado']);?>"/>
					</span>
					<span class="asterisco">&nbsp;</span>
					<?php if(isset($reg_errores['txt_estado'])) echo "<span class='error'>".($reg_errores['txt_estado'])."</span>";?>
				</td>
			</tr>
			<tr>
				<td class="label ammex">
					Ciudad o Municipio
				</td>
				<td class="ammex">
					<span class="alinear_izquierda">
						<input type="text" name="txt_ciudad" id="txt_ciudad" size="30" autocomplete="off" value="<?php if(isset($_POST['txt_ciudad'])) echo htmlspecialchars($_POST['txt_ciudad']);?>"/>
					</span>
					<span class="asterisco">&nbsp;</span>
					<?php if(isset($reg_errores['txt_ciudad'])) echo "<span class='error'>".($reg_errores['txt_ciudad'])."</span>";?>
				</td>
			</tr>
			<tr>
				<td class="label ammex">
					Email
				</td>
				<td class="ammex">
					<span class="alinear_izquierda">
						<input type="text" name="txt_email" id="txt_email" size="30" autocomplete="off" value="<?php if(isset($_POST['txt_email'])) echo htmlspecialchars($_POST['txt_email']);  else echo $this->session->userdata('email');?>"/>
					</span>
					
					<?php if(isset($reg_errores['txt_email'])) echo "<span class='error'>".($reg_errores['txt_email'])."</span>";?>
				</td>
			</tr>
			<tr>
				<td class="label ammex">
					Tel&eacute;fono
				</td>
				<td class="ammex">
					<span class="alinear_izquierda">
						<input type="text" name="txt_telefono" id="txt_telefono" size="30" autocomplete="off" value="<?php if(isset($_POST['txt_telefono'])) echo htmlspecialchars($_POST['txt_telefono']);?>"/>
					</span>
					<span class="asterisco">&nbsp;</span>
					<?php if(isset($reg_errores['txt_telefono'])) echo "<span class='error'>".($reg_errores['txt_telefono'])."</span>";?>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<div class="ast_medio">
						<span class="instrucciones_cursivas_der"><span class="asterisco">&nbsp;</span>Estos campos son obligatorios</span>
					</div>
				</td>
			</tr>			
		
			<tr>
				<td>&nbsp;</td>
				<td><input type="submit" id="enviar" value="Finalizar compra" class="btn_finalizar_compra"/></td>
			</tr>	
	</form>
</table>
</div>

<style type="text/css">
	.ammex{
		display: none;
	}
</style>
<script type="text/javascript">
	function revisa_amex(id){
		if(id==1){
			$('.ammex').css('display', 'table-cell');
			//alert ('AMEX');
		}	
		else{
			$('.ammex').css('display', 'none');
		}	
	}
</script>

<?php
	if(isset($_POST['sel_tipo_tarjeta']))
		if($_POST['sel_tipo_tarjeta']==1)
			echo "<script>$('.ammex').css('display', 'table-cell');</script>";
		
?>
