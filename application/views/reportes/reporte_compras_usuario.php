<?php
	if($compras){
		echo "	<table width='100%' cellpadding='0' cellspacing='0'>
					<thead>					
						<th>Fecha
						</th>
						<th>No. de Orden
						</th>
						<th>Productos
						</th>
						<th>Total
						</th>
						<th>&nbsp;
						</th>					
					</thead>";	      		
		foreach($compras as $indice => $detalle_compra){
			## intercalar color las filas
			$color = array("#F1F1F1", "#E6E6E6");
			if ($indice %2 == 0) {
				$bck = "style='background-color: ".$color[0]."'";
			}
			else {
				$bck = "style='background-color: ".$color[1]."'";
			}						
			echo "	<tr>
						<td $bck >".$detalle_compra['compra']['fecha_compraDt']."
						</td>
						<td $bck>".$detalle_compra['compra']['id_compraIn']."
						</td>
						<td $bck>";							
			echo "		</td>
						<td $bck align='right'>".number_format($detalle_compra['monto'], 2, '.', ',')."
						</td>
						<td $bck align='right'><a href='#' >Ver detalle</a>
						</td>  
					</tr>";			
		}
		echo "</table>";	
	}
	else{
		echo "Aun no compras nada";
	}
?>