<meta http-equiv="content-type" content="application/xhtml+xml; charset=UTF-8" />
<?php 
		
	if(count($compras)>0){
?>	
		<table border="1" >			
				<tr>
					<td>			
						Nombre Cliente				
					</td>		
					<td>
						Direccion de Envio
					</td>		
					<td>
						Forma Pago
					</td>
					<td>
						Monto
					</td>
					<td>
						Razon Social
					</td>
					<td>
						Direccion Facturacion			
					</td>
					<td>
						Codigo Aut
					</td>
					<td>
						Fecha Compra
					</td>
					<td>
						orderhdr_id
					</td>
					<td>
						order_item_seq	
					</td>
					<td>
						customer_id
					</td>
					<td>
						customer_address_seq
					</td>
					<td>
						bill_to_customer_address_seq
					</td>
				</tr>					
			<?php 			
				foreach ($compras as $j => $nueva_compra) {
										
					echo  " 
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
								if(($nueva_compra['think'])){
									echo "<td>".$nueva_compra['think']->orderhdr_id."</td>
										  <td>".$nueva_compra['think']->order_item_seq."</td>
										  <td>".$nueva_compra['think']->customer_id."</td>
										  <td>".$nueva_compra['think']->customer_address_seq."</td>
										  <td>".$nueva_compra['think']->bill_to_customer_address_seq."</td>";																										
								}
								else{
									echo "<td> </td>
										  <td> </td>
										  <td> </td>
										  <td> </td>
										  <td> </td>";
								}
						echo "
							</tr>";							
				}
			?>	
			
		</table>		
<?php		
	}
?>			

