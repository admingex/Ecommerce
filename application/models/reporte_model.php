<?php

class Reporte_model extends CI_Model {	
    function __construct(){        
        parent::__construct();		
    }    	
				
	function obtener_usuarios_fecha($fecha_inicio, $fecha_fin){
		
		$fini=$fecha_inicio;
		
		$dia=substr($fecha_fin,8,2);
    	$mes=substr($fecha_fin,5,2);
    	$ano=substr($fecha_fin,0,4);
		
		$fechafinal = mktime(0,0,0,$mes,$dia,$ano);		
		$str=strtotime('+1 day',$fechafinal);
		$ffin=mdate('%Y/%m/%d',$str);			
			
		$qry = "SELECT * FROM CMS_IntCliente 
		        WHERE fecha_registroDt>='$fini' and fecha_registroDt<='$ffin' ";
		$res = $this->db->query($qry);			
		return $res;				 
	}	
			
}