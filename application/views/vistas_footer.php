<div style="position: relative; width: 100px; left: 630px; top: 40px"><a href='<?php echo site_url(); ?>' >Regresar</a></div>
<section class="contenedor">
	<div class="contenedor-blanco">
		<div class="titulo-proceso">
			<p>
				<?php 
					if (!empty($cargar_pagina))
					{
						$var=0;
						switch($cargar_pagina) {
							case("privacidad"):
								include ('templates/privacidad.html');	
								$var = 1;							
								break;
							case("condiciones"):
								include ('templates/condiciones.html');
								break;
							case("ayuda"):
								include ('templates/ayuda.html');
								$var = 1;																
								break;
							case("contacto"):
								include ('templates/contacto.html');
								break;
							default:
								redirect('login', 'refresh', 303);
								break;		
						}
					} else {
						redirect('login', 'refresh', 303);
					}
				?>
			</p>			
		</div> 
	</div>
</section>
<?php 
	if($var==1){
		echo '<div style="position: relative; width: 100px; left: 630px;"><a href='.site_url().' >Regresar</a></div>';
	}
?>