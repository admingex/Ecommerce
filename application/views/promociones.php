<div id="container">	
	<h1><?php echo $title; ?></h1>
	<?php 	
	
	if($listar){
		include('promociones/listar.html');
	}
	if($detalle){
		if(!empty($id_sitio)){
			echo "<br />sitio: ".$id_sitio;
		}
		if(!empty($id_promocion)){
			echo "<br />promocion: ".$id_promocion;
		}	
		if(!empty($id_canal)){
			echo "<br />canal: ".$id_canal;
		}
	}
	?>		
</div>