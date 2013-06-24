<?php
/*
	echo "<pre>";
		print_r($promo);
	echo "</pre>";---
*/
?>
<div class="contenedor-gris">
<table width="100%">	
	<form id="form_registro_usuario" action="" method="POST">	
	<tr>
		<td class="label"> 
			Nombre
		</td>
		<td>
			<input type="text" name="txt_nombre" id="txt_nombre" 
				value="<?php if(isset($_POST['txt_nombre'])) echo htmlspecialchars($_POST['txt_nombre']);?>"  size="35"/>
			<?php if(isset($registro_errores['txt_nombre'])) echo $registro_errores['txt_nombre'];?>
		</td>
	</tr>	
	<tr>
		<td class="label">
			Apellido Paterno	
		</td>
		<td>
			<input type="text" name="txt_apellidoPaterno" id="txt_apellidoPaterno" 
				value="<?php if(isset($_POST['txt_apellidoPaterno'])) echo htmlspecialchars($_POST['txt_apellidoPaterno']);?>" size="35" />
			<?php if(isset($registro_errores['txt_apellidoPaterno'])) echo $registro_errores['txt_apellidoPaterno'];?>
		</td>
	</tr>	
	<tr>
		<td class="label">
			Apellido Materno
		</td>
		<td>
			<input type="text" name="txt_apellidoMaterno" id="txt_apellidoMaterno" 
				value="<?php if(isset($_POST['txt_apellidoMaterno'])) echo htmlspecialchars($_POST['txt_apellidoMaterno']);?>"  size="35"/>
			<?php if(isset($registro_errores['txt_apellidoMaterno'])) echo $registro_errores['txt_apellidoMaterno'];?>
		</td>
	</tr>							
	<tr>
		<td class="label">
			Correo electr&oacute;nico
		</td>
		<td>
			<input type="text" name="email" id="email"  
				value="<?php if(isset($_POST['email'])) echo htmlspecialchars($_POST['email']);?>" size="35"/>
			<?php if((isset($_POST['email'])&& isset($_POST['txt_nombre']))&&(isset($registro_errores['email']))) echo $registro_errores['email'];?>
		</td>
	</tr>		
	<tr>
		<td class="label">
			Calle
		</td>
		<td>
			<input type="text" name="calle" id="calle"  
				value="<?php if(isset($_POST['calle'])) echo htmlspecialchars($_POST['calle']);?>" size="35"/>
			<?php if(isset($registro_errores['calle'])) echo $registro_errores['calle'];?>
		</td>
	</tr>
	<tr>
		<td class="label">
			Número Exterior
		</td>
		<td>
			<input type="text" name="num_ext" id="num_ext"  
				value="<?php if(isset($_POST['num_ext'])) echo htmlspecialchars($_POST['num_ext']);?>" size="7"/>
			<?php if(isset($registro_errores['num_ext'])) echo $registro_errores['num_ext'];?>
		</td>
	</tr>
	<tr>
		<td class="label">
			Número Interior
		</td>
		<td>
			<input type="text" name="num_int" id="num_int"  
				value="<?php if(isset($_POST['num_int'])) echo htmlspecialchars($_POST['num_int']);?>" size="7"/>
			<?php if(isset($registro_errores['num_int'])) echo $registro_errores['num_int'];?>
		</td>
	</tr>
	<tr>
		<td class="label">
			País
		</td>
		<td>	
			<select name='pais'>
			<?php
				$id_pais = "MX";				//Mexico por default
				if (isset($_POST['sel_pais']))	//pais seleccionado para la captura
					$id_pais = $_POST['sel_pais'];
				
				foreach($lista_paises_think->result_object() as $pais){
					if ($id_pais == $pais->id_pais) {
						echo "<option value='".$pais->id_pais."' selected='true'>".$pais->pais."</option>";
					} 
					else {
						echo "<option value='".$pais->id_pais."'>".$pais->pais."</option>";
					}				
				}
			?>									
			</select>			
		</td>
	</tr>
	<tr>
		<td class="label">
			Código Postal
		</td>
		<td>
			<input type="text" name="cp" id="cp"  
				value="<?php if(isset($_POST['cp'])) echo htmlspecialchars($_POST['cp']);?>" size="7" onkeyup="checa_cp(this.value)" autocomplete="off"/>
			<?php if(isset($registro_errores['cp'])) echo $registro_errores['cp'];?>
		</td>
	</tr>	
	<tr>
		<td class="label">
			Estado
		</td>
		<td>
			<input type="text" name="estado" id="estado"  
				value="<?php if(isset($_POST['estado'])) echo htmlspecialchars($_POST['estado']);?>" size="35"/>
			<?php if(isset($registro_errores['estado'])) echo $registro_errores['estado'];?>
		</td>
	</tr>
	<tr>
		<td class="label">
			Ciudad
		</td>
		<td>
			<input type="text" name="ciudad" id="ciudad"  
				value="<?php if(isset($_POST['ciudad'])) echo htmlspecialchars($_POST['ciudad']);?>" size="35"/>
			<?php if(isset($registro_errores['ciudad'])) echo $registro_errores['ciudad'];?>
		</td>
	</tr>
	<tr>
		<td class="label">
			Colonia
		</td>
		<td>
			<input type="text" name="colonia" id="colonia"  
				value="<?php if(isset($_POST['colonia'])) echo htmlspecialchars($_POST['colonia']);?>" size="35"/>
			<?php if(isset($registro_errores['colonia'])) echo $registro_errores['colonia'];?>
		</td>
	</tr>
	
	<tr>
		<td colspan="2" class="label_izq">			
			<?php if(isset($registro_errores['user_reg'])) echo "<span class='validation_message'>".$registro_errores['user_reg']."</span>";?>			
		</td>
	</tr>
		
	<tr>
		<td>&nbsp;</td>
		<td><input type="submit" id="enviar" value="Continuar" class="btn_finalizar_compra"/></td>
	</tr>			
	</form>
</table>
</div>

<script>
function checa_cp(cp){
	
		url_base='http://dev.pagos.grupoexpansion.mx/'			
		if(cp.length==5){			
			$.ajax({
				type: "POST",
				data: {'codigo_postal' : cp},
				url: url_base + "suscripcion_express/get_info_sepomex",
				dataType: "json",				
				async: false,
				success: function(data) {
								
					if (typeof data.sepomex != null && data.sepomex.length != 0)	{	//regresa un array con las colonias
									
						var sepomex = data.sepomex;			//colonias
						var codigo_postal = sepomex[0].codigo_postal;
						var clave_estado = sepomex[0].clave_estado;						
						var estado = sepomex[0].estado;
						var ciudad = sepomex[0].ciudad;
																																	
						$('#estado').val(estado);
						$('#ciudad').val(ciudad);												
						var colonias= new Array();												
						if (sepomex.length > 0){							
							$.each(sepomex, function(indice, colonia) {
								if (colonia.colonia != '') {
									colonias[indice] = colonia.colonia;																								
								}
							});	
							$('#colonia').val('');																				
							$( "#colonia" ).autocomplete({
								source: colonias
							});																
						}										
					} 	
					else{																						
						$('#estado').val('');
						$('#ciudad').val('');
						$('#colonia').val('');
					}								
			},
			error: function(data) {				
				alert("error: " + data.error);
			},
			complete: function(data) {								
			},
			//async: false,
			cache: false
		});
	  }	
	
}

</script>