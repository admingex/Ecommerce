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