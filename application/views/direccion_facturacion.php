	<?php	
		if(isset($solicita_factura)){
			if($solicita_factura){
	?>		
		<section id="descripcion-proceso">
			<div class="titulo-proceso-img">&nbsp;</div>
			<div class="titulo-proceso">¿Requiere Factura?</div>									
		</section>
		<div id="pleca-punteada"></div>
		<div class="contenedor-blanco">
				<form id="form_agregar_direccion" action="<?php echo site_url('direccion_facturacion/requiere_factura');?>" method="POST">
					<input type="hidden" name="requiere_factura" value="no" />
					<input type="submit" value="&nbsp;" class="sin-factura" />	
				</form>
		</div>	
	<?php
			}
		}			
	?>
	<?php if (isset($subtitle)) {
		echo "
		<section id='descripcion-proceso'>
			<div class='titulo-proceso-img'>&nbsp;</div>
			<div class='titulo-proceso'>$subtitle</div>									
		</section>"
		;
	}?>
<div id="pleca-punteada"></div>	
<section class="contenedor">		
	<?php		
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
				echo "<div class='contenedor-blanco'>";
				include ('direccion_facturacion/listar.html');
				echo "</div>";	
			}
									
		}
		if(isset($nueva_direccion)){
			if($nueva_direccion){
				include ('direccion_facturacion/agregar.html');	
			}			
		}												
		
		if(isset($registrar_rs)){
			if($registrar_rs){
		?>			
		<div class="contenedor-blanco">
		<?php		
				include ('direccion_facturacion/listar_rs.html');
		?>
		</div>
		<?php			
			}				
			
			if(isset($nueva_rs)){
				if($nueva_rs){
		?>
		<?php			
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
</section>