<html>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/> 
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <title><?php echo $title ?> - CMS GEX</title>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="<?php echo base_url();?>css/style.css">
    <!--[if IE]><script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script><![endif]-->		
	<link type="text/css" href="<?php echo base_url();?>css/blitzer/jquery-ui-1.8.18.custom.css" rel="stylesheet" />
	<link type="text/css" href="<?php echo base_url();?>css/validacion.css" rel="stylesheet" />	
	<script type="text/javascript" src="<?php echo base_url();?>js/jquery-1.7.1.min.js"> </script>
	<script type="text/javascript" src="<?php echo base_url();?>js/jquery-ui-1.8.18.custom.min.js"> </script>	
	<?php if (isset($script)) echo $script; ?>	
</head>
<body>
    <div id="header-container">
        <header>
            <img src="<?php echo base_url();?>images/logo_expansion.gif" alt="logo gex" width="52" height="52"/>            
        </header>        
    </div>    

    
    <div id="main">
    		<?php    	    					
				include "menu.html"; 				
				if ($this->session->userdata('promociones') && $this->session->userdata('promocion')) {
					if($this->uri->segment(1)!="orden_compra"){
						include "promocion.html";
					}												
				}			    
			?>