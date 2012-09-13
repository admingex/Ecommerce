<script type="text/javascript">
id_ant=0;
function opendialog(id){	
	if(document.getElementById(id_ant)){
		if(document.getElementById(id_ant).style.display='none'){
			document.getElementById(id_ant).style.display='none';
		}	
	}	
	
	divid = document.getElementById(id);	
	divid.style.display ='block';		
	id_ant = id;	
}

function closedialog(newid){	
	document.getElementById(newid).style.display ='none';			
}
</script>
<?php 
		
	if(count($compras)>0){
	echo "<div style='padding: 5px 0px 5px 0px; color: #E70030; font-size: 12px'>Se encontraron ".count($compras)." registros&nbsp;&nbsp;";	
	echo anchor(site_url('reporte/compras/true'), "Exportar archivo")."</div>";	
?>			
		<table cellpadding="0" cellspacing="0" >			
			<thead>
				<tr>
					<th class="doble-linea">			
						Nombre Cliente				
					</th>		
					<th class="doble-linea">
						Direccion de Envio
					</th>		
					<th class="doble-linea">
						Forma Pago
					</th>
					<th class="doble-linea">
						Monto
					</th>
					<th class="doble-linea">
						Razon Social
					</th>
					<th class="doble-linea">
						Direccion Facturacion			
					</th>
					<th class="doble-linea">
						Codigo Aut
					</th>
					<th class="doble-linea">
						Fecha Compra
					</th>
					<th class="doble-linea">
						Think
					</th>
				</tr>
			</thead>
			<tbody class="contenedor-gris">
			<?php 			
				foreach ($compras as $j => $nueva_compra) {
										
					echo  " <tr>
							    <td colspan='9' style='height: 1px; background-color: #CCC'>							    	
							    </td>
							</tr>
							<tr>
								<td class='item-lista borde-derecho'>
									".$nueva_compra['cliente']->salutation."
									".$nueva_compra['cliente']->fname."
									".$nueva_compra['cliente']->lname."
									".$nueva_compra['cliente']->email."
								</td>
								<td class='item-lista borde-derecho'>
									".$nueva_compra['dir_envio']."
								</td>
								<td class='item-lista borde-derecho'>
									".$nueva_compra['medio_pago']."
								</td>
								<td class='item-lista borde-derecho'>
									".number_format($nueva_compra['monto'], 2 , ".", ",")."
								</td>
								<td class='item-lista borde-derecho'>
									".$nueva_compra['razon_social']."
								</td>
								<td class='item-lista borde-derecho'>
									".$nueva_compra['dir_facturacion']."
								</td>
								<td class='item-lista borde-derecho'>
									".$nueva_compra['codigo_autorizacion']."
								</td>
								<td class='item-lista borde-derecho'>
									".$nueva_compra['fecha_compra']."
								</td>";
								$think = '';
								$newdat = '';
								if(($nueva_compra['think'])){
									$think = $nueva_compra['think']->orderhdr_id;
									$newdat = "orderhdr_id: ".$nueva_compra['think']->orderhdr_id;
									$newdat.= "<br />";
									$newdat.= "order_item_seq: ".$nueva_compra['think']->order_item_seq;
									$newdat.= "<br />";
									$newdat.= "customer_id: ".$nueva_compra['think']->customer_id;
									$newdat.= "<br />";
									$newdat.= "customer_address_seq: ".$nueva_compra['think']->customer_address_seq;
									$newdat.= "<br />";
									$newdat.= "bill_to_customer_address_seq: ".$nueva_compra['think']->bill_to_customer_address_seq;																	
								}
						echo "  <td>
								    <a href='#' onclick=\"opendialog(".$j.")\">$think</a>
								</td>
							</tr>";	
						echo "	<div id='".$j."' style='background-color: #FFF; position: fixed; margin-left: 200px; padding: 15px; display: none'>
									".$newdat."
							 		<a href='#' onclick=\"closedialog(".$j.")\">cerrar</a>
							 	</div>";	
				}
			?>	
			</tbody>
		</table>		
<?php		
	}
	else{
?>
	<p>No existen datos en esta fecha</p>
<?php	
	}
?>				

</section>