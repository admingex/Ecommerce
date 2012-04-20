<section id="descripcion-proceso">
	<div class="titulo-proceso-img">&nbsp;		
	</div>			
	<div class="titulo-proceso">
		<?php echo $subtitle; ?>	
	</div>
</section>
<div id="pleca-punteada"></div>

<section class="contenedor">
	<?php								
		if($enviado){
	?>
			<div class="contenedor-gris">
	<?php		
			include ('login/password_enviado.html');
		}
		if($verificar){
	?>		
			<div class="instrucciones_mensaje">
				Esta es la clave que recibiste en tu correo electr&oacute;nico junto con la liga a esta pagina
			</div>			
			<div class="contenedor-gris">
	<?php				
			include ('login/verificar.html');	
		}
		if($cambiar){
	?>
			<div class="instrucciones_mensaje">
			Escribe una nueva contrase&ntilde;a y confirmala. La contrase&ntilde;a debe tener al menos 8 caracteres y contener letras mayusculas, minusculas y numeros
			</div>
			<div class="contenedor-gris">
	<?php		
			include ('login/cambiar.html');
		}
		else if((!$enviado)&&(!$cambiar)&&(!$verificar)){
	?>		
			<div class="instrucciones_mensaje">
				Para recuperar tu contrase&ntilde;a, escribe tu correo electronico. Te enviaremos un correo con las instrucciones que debes seguir
			</div>
			<div class="contenedor-gris">
	<?php		
			include ('login/recordar.html');
		}
			
	?>
	</div>
	<script type="text/javascript">
	$(function(){
		$('#dialog').dialog({
			position:['top',160],
			modal: true,
			show: 'slide',
			autoOpen: true,					
			buttons: {
				"Ok" : function(){ 
					       $(this).dialog("close"); 					       
					   }
			}
		});		
																						
	});
	</script>
	<?php if($mensaje){
	?>	
		<div id="dialog" title="Mensaje del servidor" >
			<p><?php echo $mensaje;?></p>
		</div>
	<?php		
	}			
	?>	

</section>