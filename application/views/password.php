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
	if((!$enviado)&&(!$cambiar)&&(!$verificar)){
	?>		
		<div class="intrucciones_mensaje">
			Para recuperar tu contrase&ntilde;a, escribe tu correo electronico. Te enviaremos un correo con las instrucciones que debes seguir
		</div>
	<?php
	}		
	?>
	<div class="contenedor-gris">				
	<?php
		if($enviado){
			include ('login/password_enviado.html');
		}
		if($verificar){
			include ('login/verificar.html');	
		}
		if($cambiar){
			include ('login/cambiar.html');
		}
		else if((!$enviado)&&(!$cambiar)&&(!$verificar)){
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