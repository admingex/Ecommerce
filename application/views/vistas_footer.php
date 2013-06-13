<?php
	echo '<div style="float: right; width: 100px; margin-top: 40px"><a href='.site_url().' >Regresar</a></div>'; 
?>
<section class="contenedor">
	<div class="contenedor-blanco">
		<div class="titulo-proceso">			
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
		</div> 
	</div>
</section>
<?php 
	if ($var == 1) {
		echo '<div style="width: 100px; float: right; margin-bottom: 20px"><a href='.site_url().' >Regresar</a></div>';
	}
?>