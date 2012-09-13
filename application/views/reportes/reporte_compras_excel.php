<?php 		
header('Content-Type: text/plain; charset=UTF-8');
if(count($compras)>0){
	$cont = "Nombre Cliente\tmail\tDireccion de Envio\tForma Pago\tMonto\tRazon Social\tDireccion Facturacion\tCodigo Aut\tFecha Compra\torderhdr_id\torder_item_seq\tcustomer_id\tcustomer_address_seq\tbill_to_customer_address_seq".chr(13).chr(10);
		
	foreach ($compras as $j => $nueva_compra) {
										
		$cont.=  	$nueva_compra['cliente']->salutation." ".$nueva_compra['cliente']->fname." ".$nueva_compra['cliente']->lname."\t".$nueva_compra['cliente']->email."\t";
		$cont.= 	$nueva_compra['dir_envio']."\t";
		$cont.=	$nueva_compra['medio_pago']."\t";
		$cont.= 	number_format($nueva_compra['monto'], 2 , ".", ",")."\t";
		$cont.= 	$nueva_compra['razon_social']."\t";
		$cont.= 	$nueva_compra['dir_facturacion']."\t";
		$cont.=	$nueva_compra['codigo_autorizacion']."\t";
		$cont.= 	$nueva_compra['fecha_compra']."\t";
												
		if(($nueva_compra['think'])){
			$cont.= 	$nueva_compra['think']->orderhdr_id."\t";
			$cont.=  	$nueva_compra['think']->order_item_seq."\t";
			$cont.= 	$nueva_compra['think']->customer_id."\t";
			$cont.= 	$nueva_compra['think']->customer_address_seq."\t";
			$cont.= 	$nueva_compra['think']->bill_to_customer_address_seq;																										
		}
		else{
			$cont.= " \t \t \t \t ";
		}								
		$cont.= chr(13).chr(10);																				
	}	
	echo $cont;	
}


?>			

