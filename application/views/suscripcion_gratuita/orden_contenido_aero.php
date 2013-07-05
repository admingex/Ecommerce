<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta charset="utf-8">
    
    <meta http-equiv='Cache-Control' content='no-cache'/>
    <meta http-equiv='Pragma' content='no-cache'/>
    <meta http-equiv='Expires' content='Sat, 26 Jul 1997 05:00:00 GMT' />
    
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <title></title>
    <meta name="description" content="<?php echo "Suscríbete a Grupo Expansión, al igual que Quién, Quo, Chilango, IDC y MedioTiempo es una publicación de Grupo Expansión, una empresa de Time Inc localizada en México, Distrito Federal."; ?>">    
    <meta name="author" content="Grupo Expansión A Time Inc. Company, creamos experiencias mediáticas apasionantes para enriquecer tu vida">
    <meta name="keywords" content="suscripcion, descuento, publicaciones, expansión, grupo, time, méxico, distrito federal, expansión, quien, quo, chilango, idc, mediotiempo, celebridades, noticias">

    <link rel="stylesheet" href="https://pagos.grupoexpansion.mx/css/style.css">
    <!--[if IE]><script src="https://html5shiv.googlecode.com/svn/trunk/html5.js"></script><![endif]-->		
	<link type="text/css" href="https://pagos.grupoexpansion.mx/css/blitzer/jquery-ui-1.8.18.custom.css" rel="stylesheet" />
	<link type="text/css" href="https://pagos.grupoexpansion.mx/css/validacion.css" rel="stylesheet" />	
	<script type="text/javascript" src="https://pagos.grupoexpansion.mx/js/jquery-1.7.1.min.js"> </script>
	<script type="text/javascript" src="https://pagos.grupoexpansion.mx/js/tools.js"> </script>
	<script type="text/javascript" src="https://pagos.grupoexpansion.mx/js/jquery-ui-1.8.18.custom.min.js"> </script>
	
	<?php if (isset($script)) echo $script; ?>	
	<style type="text/css">
		body{			
			background-repeat: no-repeat;			
			background-image: url('http://dev.pagos.grupoexpansion.mx/images/cACC.png'); 
			width: 790px; 
			background-position: center 0px;		
			margin-top: 180px;			
		}
		.btn_finalizar_compra{
			background-color: #c30534;
			color: #FFF;
			font-weight: bold;
			border: none;
			height: 30px;
			cursor: pointer;
		}		
		.grisb{
			color: #9b9b9b;
		}
	</style>
		
</head>
<?php
		$archivo_pdf1 = "cGRP.pdf";
		$archivo_pdf2 = "cAIR.pdf";
		$archivo_pdf3 = "cACC.pdf";
		
		?>



	
  <form id="form_orden_compra" action="https://kiosco.grupoexpansion.mx/" method="POST" >
	<div class="contenedor-blanco">				
	<table width="100%" cellpadding="0" cellspacing="0">		
		<thead>
			<tr>
				<th>
					Descargar	contenido			
				</th>
				<th>&nbsp;
				</th>				
			</tr>						
		</thead>
		<tbody class="contenedor-gris">
			<tr>
				<td colspan="2">
				<div>
									    	<b>
										  	Ahora puedes acceder y disfrutar del contenido: <br /><br />
											- <a href='http://dev.pagos.grupoexpansion.mx/aeromexico/download/<?php echo $archivo_pdf1; ?>'>Gran Plan Aeromexico</a><span class="grisb"> (12MB)</span><br /><br/>
											- <a href='http://dev.pagos.grupoexpansion.mx/aeromexico/download/<?php echo $archivo_pdf2; ?>'>Aire Aeromexico</a><span class="grisb"> (50MB)</span><br /><br/>
                                            - <a href='http://dev.pagos.grupoexpansion.mx/aeromexico/download/<?php echo $archivo_pdf3; ?>'>Accent Aeromexico</a><span class="grisb"> (30MB)</span><br /><br/><br />
                                            
											Cordialmente,<br/><br/>
											Grupo Expansión.<br/></b>
								  	   </div>					
				</td>				
			</tr>						
		</tbody>
	</table>		
	</div>
	
	<div class="contenedor-blanco">
		<input type="submit" id="enviar" value="Aceptar" class="btn_finalizar_compra"/>
	</div>
	</form>
