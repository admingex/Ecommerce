<div id="container">	
	<h1><?php echo $subtitle; ?></h1>	
	<div style="text-align: right; font-size : 11pt; font-weight:bold; padding-right: 2%; color:#fb3;">
		<a href="<?php echo $this->config->item('base_url'); ?>/index.php/logout/">Cerrar Sesi&oacute;n</a>
	</div>
	<?php				
		if((isset($editar))){			
			include ('direccion_facturacion/editar.html');
		}		
		else{
			include ('direccion_facturacion/listar.html');
			if(isset($registrar)){			
				include ('direccion_facturacion/agregar.html');			
			}
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
			       <?php
					//Por default recirecciona a la raiz del módulo
					$url_redirect = site_url("direccion_facturacion");
					if (isset($redirect) && $redirect) {
						//revisar si la redirección es hacia el resumen de la orden
						if ($this->session->userdata("redirect_to_order")) {
							$url_redirect = site_url($this->session->userdata("redirect_to_order"));
						} else {
							$url_redirect = site_url('orden_compra');
						}
					}
					?>
					$( this ).dialog( "close" );
					window.location.href = "<?php echo $url_redirect; ?>";
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