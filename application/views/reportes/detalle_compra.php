<div class='titulo-descripcion'>
	<div class='img-hoja'></div>
	Resumen de orden
	<div value="Regresar" class="boton-regresar" onclick="document.getElementById('boton_historial').click()"></div>
	<div class='pleca-titulo'></div>
</div>
<?php
		
	if($compra){
		echo "	<div class='encabezado-descripcion'>Datos generados</div>
				<div class='contenedor1'>
					<div class='clear'>
						<span class='info-negro'>Fecha de Orden: </span>
						<span class='info-rojo'>".$compra['fecha_compra']."</span>
					</div>
					<div class='clear'>
						<span class='info-negro'>Número de Orden: </span>
						<span class='info-rojo'>".$compra['id_compra']."</span>
					</div>
					<div class='clear'>
						<span class='info-negro'>importe total: </span>
						<span class='info-rojo'>".number_format($compra['monto'], 2, '.', ',')."</span>
					</div>
				</div>";
				
		echo "	<div class='encabezado-descripcion'>Producto ordenado</div>				
					<table width='100%' cellspacing='0' cellpadding='0'>
						<thead>
							<th>Producto
							</th>
							<th>Dirección de Envío
							</th>
							<th>Precio
							</th>
						</thead>	
						<tbody class='contenedor1'>
							<tr>
								<td>";
								$monto_compra = 0;
								foreach($compra['articulos'] as $articulo){
							
									//revisar si la descripcion de la promocion tiene slash's quitarlos
									if(stristr($compra['promocion']->descripcionVc, "|")){
										$mp=explode('|',$compra['promocion']->descripcionVc);
										$nmp=count($mp);
										if($nmp==2){
											$desc_promo = $mp[0];		
										}	
										else if($nmp==3){
											$desc_promo = $mp[1];
										}								
									}
									else{
										$desc_promo = $compra['promocion']->descripcionVc;	
									}
									//revisar si la descripcion del articulo tiene slash's quitarlos	
									if(stristr($articulo['tipo_productoVc'], "|")){
										$ma=explode('|',$articulo['tipo_productoVc']);
										$nma=count($ma);
										if($nma==2){
											$desc_art = $ma[0];		
										}	
										else if($nma==3){
											$desc_art = $ma[1];
										}								
									}
									else{
										$desc_art = $articulo['tipo_productoVc'];	
									}
									// mostrar la descripcion de promocion y articulo sin slash's
									echo $desc_promo."<br />".
										 $desc_art."<br />";	
									$monto_compra+= $articulo['tarifaDc'];	 						
								}
		echo "					</td>
								<td>".$compra['dir_envio']."
								</td>
								<td>".number_format($monto_compra, 2, '.', ',')."
								</td>
							</tr>
						</tbody>					
					</table>
					<div style='margin-top:18px'>
					</div>";		
		
						
		echo "	<div class='encabezado-descripcion'>Información de pagos</div>
				<div class='contenedor1'>
					<span class='info-negro'>Pagado con: </span>
					<span class='info-rojo'>".$compra['medio_pago']. "&nbsp;&nbsp;&nbsp;" .$compra['codigo_autorizacion']."</span>
				</div>";
				
		if($compra['razon_social']){		
		echo "	<div class='encabezado-descripcion'>Información de facturación</div>
				<div class='contenedor2 width50'>
					<div class='info-rojo'>Facturado a :</div>
					<div class='info-negro'>".$compra['razon_social']."</div>					
				</div>
				<div class='contenedor1 width50'>
					<div class='info-rojo'>Dirección de facturación</div>
					<div class='info-negro'>".$compra['dir_facturacion']."</div>
				</div>";
		}				
			
	}
	else{
		echo "Aun no compras nada";
	}	
?>