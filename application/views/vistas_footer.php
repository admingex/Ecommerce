<section class="contenedor">
	<div class="contenedor-blanco">
		<div class="titulo-proceso">
			<p>
				<?php 
					if (!empty($cargar_pagina))
					{
						switch($cargar_pagina) {
							case("privacidad"):
								include ('templates/privacidad.html');
								break;
							case("condiciones"):
								include ('templates/condiciones.html');
								break;
							case("ayuda"):
								include ('templates/ayuda.html');
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