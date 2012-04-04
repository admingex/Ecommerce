<div id="container">	
	<h1><?php echo $title; ?></h1>
	<?php 		
	if($listar){
		include('api/listar.html');
	}
	if($detalle){
		if(!empty($sitio)){
			echo "<br />
				  <b>sitio</b>
				  <br /> <a href='".$sitio->urlVc."' target='new'>".$sitio->urlVc."</a>";
				  echo json_encode($sitio);	
		}		
			
		if(!empty($canal)){
			echo "<br/>
				  <br />
				  <b>Canal</b>
				  <br />descripcion: ".$canal->descripcionVc."
				  <br />addkey: ".$canal->addKeyVc;
				  echo json_encode($canal);						  		
		}		
		
		if(!empty($promocion)){
			echo "<br />
			      <br />
			      Promocion
			      <br />descripcion: ".$promocion->descripcionVc."
			      <br />duracion: ".$promocion->inicio_promocionDt."-".$promocion->fin_promocionDt;
				  echo "<br />".json_encode($promocion);	
			if(!empty($articulos)){
				echo "<br />
			          <br />
			      	  Articulos";		
			    foreach($articulos as $articulo){
			    	echo "<br /><br />tipo producto: ".$articulo['tipo_productoVc']."
			          <br />medio entrega ".$articulo['medio_entregaVc'];
			    }  	
				echo "<br /><span style='background-color: #FFF'>".json_encode($articulos)."</span>";	  	      	  			    			         
			}		  
		}
	}			
	?>		
</div>