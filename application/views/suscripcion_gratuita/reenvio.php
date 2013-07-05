<?php
$email = $this->session->userdata('email');
?>
<div id="pleca-punteada"></div>
<div class="contenedor-blanco">

		
	<div class="instrucciones">Por favor verifica la informaci&oacute;n que aparece abajo.</div>

</div>


	<form id="form_orden_compra" action="https://kiosco.grupoexpansion.mx/" method="POST" >
	<div class="contenedor-blanco">				
	<table width="100%" cellpadding="0" cellspacing="0">		
		<thead>
			<tr>
				<th>
					Forma de Env&iacute;o				
				</th>
				<th>&nbsp;
				</th>				
			</tr>						
		</thead>
		<tbody class="contenedor-gris">
			<tr>
				<td colspan="2">
				<div>
									    	<b>Una vez más te damos la más cordial bienvenida y esperamos que disfrutes tu adquisición.<br /><br />
										  	Te informamos que hemos enviado un mensaje a tu cuenta de correo electrónico <?php echo $email; ?> con la información necesaria para que puedas acceder al contenido.<br /><br/>
											<br />
											Estamos disponibles para cualquier pregunta relacionada con este correo.<br />
										  	Atención a clientes<br/>
										  	Tel. (55) 9177 4342<br/>
											servicioaclientes@expansion.com.mx<br/><br/>
											Cordialmente,<br/><br/>
											Grupo Expansión.<br/></b>
								  	   </div>					
				</td>				
			</tr>						
		</tbody>
	</table>		
	</div>
	
	<div class="contenedor-blanco">
		<input type="submit" id="enviar" value="Finalizar" class="btn_finalizar_compra"/>
	</div>
	</form>

	<div id="scripts">
		<script type="text/javascript">
			/*mensaje y redirección*/
			$(function() {
				$( "#dialog:ui-dialog" ).dialog( "destroy" );
				
				$( "#dialog" ).dialog({
					resizable: false,
					//height:140,
					modal: true,
					buttons: {
						"Ok": function() {
							$( this ).dialog( "close" );
							//$url_redirect = site_url('direccion_envio');
						}
					}
				});
			});
		</script>
	</div>
