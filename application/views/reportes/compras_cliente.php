<style type="text/css">
	ul{
		width: 705px;		
	}
	li{
		list-style: none;
		width: 350px;
		border: solid 1px #CCC;	
		border-bottom: solid 1px #E70030;	
		#float: left
	}
	table{
		color: #000000;
		font-size: 12px;
		font-weight: bold;		
	}
	.titulo{
		font-weight: normal;
		text-transform: capitalize;
	}
</style>
<?php	
	//echo "id cliente: ".$id_cliente;
	if(isset($consulta_compras)){
		/*	
		echo "<pre>";
			print_r($consulta_compras);
		echo "</pre>";
		*/
		echo "<ul>";
			foreach($consulta_compras as $compra){
				echo "<li>
				          <table>
				          	  <tr>
				          	  	  <td class='titulo'>id transaccion
				          	  	  </td>
				          	  	  <td>".$compra['id_transaccionBi']."
				          	  	  </td>
				          	  </tr>
				          	  <tr>
				          	  	  <td class='titulo'>compra
				          	  	  </td>
				          	  	  <td>".$compra['id_compraIn']."
				          	  	  </td>
				          	  </tr>
				          	  <tr>
								  <td class='titulo'>id articulo
								  </td>
								  <td>".$compra['id_articuloIn']."
								  </td>
							  </tr>
							  <tr>
								  <td class='titulo'>promocion
								  </td>
								  <td>".$compra['id_promocionIn']."
								  </td>
							  </tr>
							  <tr>
								  <td class='titulo'>orderhdr_id
								  </td>
								  <td>".$compra['orderhdr_id']."
								  </td>
							  </tr>
							  <tr>
								  <td class='titulo'>order_item_seq
								  </td>
								  <td>".$compra['order_item_seq']."
								  </td>
							  </tr>
							  <tr>
								  <td class='titulo'>customer_id
								  </td>
								  <td>".$compra['customer_id']."
								  </td>
							  </tr>
							  <tr>
								  <td class='titulo'>oc_id
								  </td>
								  <td>".$compra['oc_id']."
								  </td>
							  </tr>
							  <tr>
								  <td class='titulo'>nombre
								  </td>
								  <td>".$compra['nombreVc']."
								  </td>
							  </tr>
							  <tr>
								  <td class='titulo'>order_code_id
								  </td>
								  <td>".$compra['order_code_id']."
								  </td>
							  </tr>
							  <tr>
								  <td class='titulo'>source_code_id
								  </td>
								  <td>".$compra['source_code_id']."
								  </td>
							  </tr>
							  <tr>
								  <td class='titulo'>tarifa
								  </td>
								  <td>".$compra['tarifaDC']."
								  </td>
							  </tr>
							  <tr>
								  <td class='titulo'>moneda
								  </td>
								  <td>".$compra['monedaVc']."
								  </td>
							  </tr>
							  <tr>
								  <td class='titulo'>fecha compra
								  </td>
								  <td>".$compra['fecha_registroTs']."
								  </td>
							  </tr>
							  <tr>
								  <td class='titulo'>codigo autorizacion
								  </td>
								  <td>".$compra['codigo_autorizacionVc']."
								  </td>
							  </tr>
							  <tr>
								  <td class='titulo'>fecha aprobacion
								  </td>
								  <td>".$compra['fecha2']."
								  </td>
							  </tr>
							  <tr>
								  <td class='titulo'>taxable
								  </td>
								  <td>".$compra['taxableBi']."
								  </td>
							  </tr>
				          </table>					      
				      </li>";
			}	      			      
		echo "</ul>";
		
	}
	else {
		echo "<div style='color: #E70030'>No existe información</div>";
	}	
	
?>
<!--
<tr>
	<td>
	</td>
	<td>".$compra['']."
	</td>
</tr>
-->