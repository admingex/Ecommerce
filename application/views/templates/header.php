<html> 
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta charset="utf-8">
    
    <meta http-equiv='Cache-Control' content='no-cache'/>
    <meta http-equiv='Pragma' content='no-cache'/>
    <meta http-equiv='Expires' content='Sat, 26 Jul 1997 05:00:00 GMT' />
    
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <title><?php echo $title ?> - Pagos Grupo Expansión</title>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="<?php echo base_url();?>css/style.css">
    <!--[if IE]><script src="https://html5shiv.googlecode.com/svn/trunk/html5.js"></script><![endif]-->		
	<link type="text/css" href="<?php echo base_url();?>css/blitzer/jquery-ui-1.8.18.custom.css" rel="stylesheet" />
	<link type="text/css" href="<?php echo base_url();?>css/validacion.css" rel="stylesheet" />	
	<script type="text/javascript" src="<?php echo base_url();?>js/jquery-1.7.1.min.js"> </script>
	<script type="text/javascript" src="<?php echo base_url();?>js/tools.js"> </script>
	<script type="text/javascript" src="<?php echo base_url();?>js/jquery-ui-1.8.18.custom.min.js"> </script>
		
	<?php if (isset($script)) echo $script; ?>	
		
</head>
<body>
	
<?php 
	
##para agregar las tags de google
$band=0;
if(array_key_exists('tags_google', $this->session->all_userdata()))
	$band = 1;
	//echo "sesion tags_google ses->".$this->session->userdata('tags_google');
if(isset($tags_google))
	$band = 1;
	//echo "sesion tags_google var->".$tags_google;
	
if($band == 1){			
	?>
<!-- Google Tag Manager -->
	<noscript><iframe src="//www.googletagmanager.com/ns.html?id=GTM-F8GW"
	height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
	<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
	new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
	j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
	'//www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
	})(window,document,'script','dataLayer','GTM-F8GW');</script>
<!-- End Google Tag Manager -->
<?php
}
?>

    <div id="header-container">
        <header>
            <img src="<?php echo base_url();?>images/logo_expansion.gif" alt="logo gex" width="52" height="52"/>            
        </header>        
    </div>    

    
    <div id="main">
    		