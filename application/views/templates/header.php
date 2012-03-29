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
	
	
	<style type="text/css">		

		a {
			color: #003399;
			background-color: transparent;
			font-weight: normal;
		}
		p{
			clear: both;
		}
		#pleca{
			width: 100%;
			height: 1px;
			background-color: #CCC;	
		}
		h1 {
			color: #444;
			background-color: transparent;
			border-bottom: 1px solid #D0D0D0;			
			font-weight: normal;
			margin: 0 0 14px 0;
			padding: 14px 15px 10px 15px;
			clear: both;
		}

		code {
			font-family: Consolas, Monaco, Courier New, Courier, monospace;			
			background-color: #f9f9f9;
			border: 1px solid #D0D0D0;
			color: #002166;
			display: block;
			margin: 14px 0 14px 0;
			padding: 12px 10px 12px 10px;
		}
		
		ul li{
			/*width: %;		/*145px;*/
			padding: 4px 0px 4px 4px;
			list-style-type: none;				
			/*border: 1px solid #000;*/
			text-align: left;
			text-indent:0%;
			/*background-color: #fa3;*/
		}
		p.footer{
			text-align: right;			
			border-top: 1px solid #D0D0D0;
			line-height: 32px;
			padding: 0 10px 0 10px;
			margin: 20px 0 0 0;
		}
		
		
		.item_requerid {
			width: 170px;
			height: 22px;
			padding-right: 4px;
			padding-top: 4px;
			font-weight: bold;
			float: left;
			clear: left;
			text-align: right;
			/*border: 1px solid #000;*/
		}

		.form_requerid {
			width: 30%;
			/*height: 22px;*/			
			float: left;
			clear: right;
			padding: 2px 0 8px 2px;
			/*border: 1px solid #000;*/			
		}
		
		
		#list_headers {
			padding-left: 4px;
		}

		.column_header {	
			width: 15%;	/*160px*/		
			font-weight: bold;
			padding: 4px 0px 4px 4px;
			float: left;
			/*color: #903;*/
			/*background-color: #fd3;*/
			text-align: left;
			text-indent: 1%;
			/*border-right: solid 2px #002166;*/
			border-bottom: solid 1px #002166;
		}
		
		#list_results {
			/*padding-left: 0px;*/
			padding: 15px 0px 15px 0px;
		}
			
			
		#list_results  ul{
			margin: 0;
			padding: 0;
			clear: both;
		}
		
		#list_results  ul li{	
			width: 15%;	/*145px;*/		
			padding: 4px 0px 4px 4px;
			list-style-type: none;
			display: inline;
			float: left;			
			/*border: 1px solid #000;*/
			text-align: left;
			text-indent:0%;
			/*background-color: #fa3;*/
		}

		#list_results  li a{
			width: 100px;	
			font-weight: bold;
			color:#002166;
			text-decoration: none;
		}
		
		#list_results  li a:hover{
			color:#fb3;
		}
		
		.form_button {
			/*padding: 15px 0px 15px 0px;*/
			padding: 15px 0px 0px 15px;
			clear: both;
		}
		
		thead {			
			vertical-align: top;
			text-align: left;	
			background-color: #ccc;													
		}		
		td{
			border-bottom: solid 1px #CCC
		}			
				
	</style>
</head>
<body>
    <div id="header-container">
        <header>
            <a href='<?php echo base_url();?>'><img src="<?php echo base_url();?>images/logo_expansion.gif" alt="logo gex" width="52" height="52" /></a>            
        </header>
    </div>    
    <div id="main">
    		<?php
				include "menu.html"; 
			    include ("promocion.html");
			?>
