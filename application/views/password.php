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
				Esta es la clave que recibiste en tu correo electr&oacute;nico junto con la liga a esta página
			</div>			
			<div class="contenedor-gris">
	<?php				
			include ('login/verificar.html');	
		}
		if($cambiar){
	?>
			<div class="instrucciones_mensaje">
			Escribe una nueva contrase&ntilde;a y confírmala. La contrase&ntilde;a debe tener al menos 8 caracteres y contener letras mayúsculas, minúsculas y al menos un número
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
</section>