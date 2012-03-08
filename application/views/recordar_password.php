<div id="container">
	
	<h1><?php echo $subtitle; ?></h1>
	
	<?php
		if($enviado){
			include ('login/password_enviado.html');
		}
		else{
			include ('login/recordar.html');
		}
			
	?>
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
	
	<p class="footer">Page rendered in <strong>{elapsed_time}</strong> seconds</p>
</div>