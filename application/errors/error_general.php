<!DOCTYPE html>
<html lang="en">
<head>
<title>Error</title>
    <link rel="stylesheet" href="<?php echo PAGOS;?>/css/style.css">
    <!--[if IE]><script src="https://html5shiv.googlecode.com/svn/trunk/html5.js"></script><![endif]-->		
	<link type="text/css" href="<?php echo PAGOS;?>/css/blitzer/jquery-ui-1.8.18.custom.css" rel="stylesheet" />
	<style type="text/css">
	.img-logo{
		margin: 65px 10px 28px 57px;
	}
	.img-hojas{
		margin: 7px 20px 0px 66px;
	}
	.imagen-error{
		float: left
	}	
	.textos-error{
		float: left;
		padding-top: 25px;
	}
	.tipo-error{
		color: #C90002;
		font-weight: bold;
		font-size: 36px;
	}
	.detalle-error{
		line-height: 20px;
		padding-top: 25px;
		padding-left: 5px;		
	}
	
	#pleca-2{
		display: block;			
		height: 5px;		
		background: url('<?php echo PAGOS;?>/images/css_sprite_lineas2.jpg') no-repeat;	
		background-position: 0px -7px;	
		clear: both;
	}	
		
	</style>				
</head>
<body>
	<div id="header-container">
        <header>
            <img src="<?php echo PAGOS;?>/images/logo_expansion.gif" alt="logo gex" width="52" height="52"/>            
        </header>        
    </div>    

    
    <div id="main">
    	<img src="<?php echo PAGOS;?>/images/css_sprite_logotipocompleto.jpg" class='img-logo' />
    	<div id="pleca-punteada"></div>
    	<div class="imagen-error">
    		<img src="<?php echo PAGOS;?>/images/css_prite_hojas2.png" class='img-hojas'/>	
    	</div>    	    	
    	<div class="textos-error">
    		<div class="tipo-error">
    			Error General
    		</div>
    		<div id='pleca-2'></div>
    		<div class="detalle-error">
    			<div class="instrucciones">
    				La p&aacute;gina que intentas solicitar no est&aacute; en el servidor<br />
    				Prueba nuevamente en nuestro homepage.    				
    			</div>
    			
				<div class="titulo-proceso-img">&nbsp;		
				</div>							
				<div class="titulo-proceso">
					<a href="<?php echo PAGOS;?>">pagos.grupoexpansion.mx</a>	
				</div>
			</div>	
				
    	</div>   	    				 
	</div>
    <div id="footer-container">
        <footer>
            <img src="<?php echo PAGOS;?>/images/text_expansion.gif" alt="Grupo expansión"width="92" height="28"/>
        </footer>
    </div>
</body>
</html>	
<?php exit();?>