<div id="container">			
	<?php	
		if(isset($solicita_factura)){
			if($solicita_factura){
	?>					
			<p><b>¿requieres factura?</b></p>
			<p><input type="submit" value="No requiero factura, continuar" /></p>	
			<div id="pleca">&nbsp;</div>
							
	<?php
			}
		}				
		if((isset($editar_rs))){
			if($editar_rs){
				include ('direccion_facturacion/editar_rs.html');	
			}			
			
		}
		if((isset($editar_direccion))){
			if($editar_direccion){
				include ('direccion_facturacion/editar.html');	
			}			
			
		}
		
		if(isset($registrar_direccion)){
			if($registrar_direccion){
				include ('direccion_facturacion/listar.html');	
			}
									
		}
		if(isset($nueva_direccion)){
			if($nueva_direccion){
				include ('direccion_facturacion/agregar.html');	
			}			
		}												
		
		if(isset($registrar_rs)){
			if($registrar_rs){
				include ('direccion_facturacion/listar_rs.html');	
			}				
			
			if(isset($nueva_rs)){
				if($nueva_rs){
					include ('direccion_facturacion/agregar_rs.html');
				}															
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
</div>