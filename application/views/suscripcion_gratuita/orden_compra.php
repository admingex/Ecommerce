<?php
$urlpdf_encriptada = $this->session->userdata('urlpdf_encriptada');
?>
<div id="pleca-punteada"></div>
<div class="contenedor-blanco">

		
	<div class="instrucciones">Por favor verifica la informaci&oacute;n que aparece abajo.</div>

</div>
<section class="contenedor">

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
									    	<b>Gracias por registrar tus datos, te damos la más cordial bienvenida y esperamos que disfrutes tu adquisición.<br /><br />
										  	Puedes acceder al contenido de la siguiente manera: <br />
											- Sigue el siguiente link para acceder al contenido: 
                                            <a href='<?php echo "http://dev.pagos.grupoexpansion.mx/aeromexico/contenido/".$urlpdf_encriptada; ?>'><?php echo "http://dev.pagos.grupoexpansion.mx/aeromexico/contenido/".$urlpdf_encriptada; ?></a><br /><br/>
											- Si seguir el link no funciona, puedes copiar y pegar el link en la barra de dirección de tu navegador, o reescribirla ahí. Te informamos que también hemos enviado un mensaje a tu cuenta de correo electrónico con esta información.<br /><br/>
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
</section>
