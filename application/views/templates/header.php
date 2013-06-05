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
<!-- Google Tag Manager Flock -->
	<noscript><iframe src="//www.googletagmanager.com/ns.html?id=GTM-DGF6"
	height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
	<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
	new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
	j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
	'//www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
	})(window,document,'script','dataLayer','GTM-DGF6');</script>
<!-- End Google Tag Manager Flock -->
	
<!-- analitics Google -->	
	<script type="text/javascript">
	  var _gaq = _gaq || [];
	  _gaq.push(['_setAccount', 'UA-36639811-2']);
	  _gaq.push(['_setDomainName', 'grupoexpansion.mx']);
	  _gaq.push(['_trackPageview']);
	
	  (function() {
	    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
	    ga.src = ('https:' == document.location.protocol ? 'https://ssl' :
		'http://www') + '.google-analytics.com/ga.js';
	    var s = document.getElementsByTagName('script')[0];
		s.parentNode.insertBefore(ga, s);
	  })();	
	</script>
<!-- End analitics Google -->
	
<?php
	##para agregar las tags de google
	$band = 0;
	if (array_key_exists('tags_google', $this->session->all_userdata()))
		$band = 1;
		//echo "sesion tags_google ses->".$this->session->userdata('tags_google');
	if (isset($articulo_oc))
		$band = 1;
		//echo "sesion tags_google var->".$tags_google;
	if ($band == 1) {
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
	if (isset($url_back['estatus']))
		if($url_back['estatus'] != 0) {
?>
<!-- Google Code for Suscripci&oacute;n Conversion Page -->
	<script type="text/javascript">
		/* <![CDATA[ */
		var google_conversion_id = 989875443;
		var google_conversion_language = "es";
		var google_conversion_format = "2";
		var google_conversion_color = "ffffff";
		var google_conversion_label = "zhSiCIXwhwQQ85mB2AM";
		var google_conversion_value = 0;
		/* ]]> */
	</script>
	<script type="text/javascript" src="https://www.googleadservices.com/pagead/conversion.js">
	</script>
	<noscript>
	<div style="display:inline;">
	<img height="1" width="1" style="border-style:none;" alt="" src="https://www.googleadservices.com/pagead/conversion/989875443/?value=0&amp;label=zhSiCIXwhwQQ85mB2AM&amp;guid=ON&amp;script=0"/>
	</div>
	</noscript>
<?php
	}
?>
    <div id="header-container">
        <header>
            <img src="<?php echo base_url();?>images/logo_expansion.gif" alt="logo gex" width="52" height="52"/>
        </header>
    </div>    
    <div id="main">