<div id="container">
	
	<h1><?php echo $subtitle; ?></h1>
	
	
	<?php include ('forma_pago/listar.html'); ?>
	<?php 
		if (isset($form)) {
			if($form == 'tc') {
				include ('forma_pago/agregar.html'); 	
			} else if ($form == 'amex') {
				include ('forma_pago/agregar_amex.html');
			}
		} 
		
	?>
	
	<p class="footer">Page rendered in <strong>{elapsed_time}</strong> seconds</p>
</div>