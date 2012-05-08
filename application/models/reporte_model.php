<?php

class Reporte_model extends CI_Model {	
    function __construct(){        
        parent::__construct();		
    }    	
				
	function obtener_usuarios_fecha($fecha_inicio, $fecha_fin){
						
		$fini=$fecha_inicio;
		$ffin=$this->fecha_fin($fecha_fin);							
			
		$qry = "SELECT * FROM CMS_IntCliente 
		        WHERE fecha_registroDt>='$fini' and fecha_registroDt<'$ffin' ORDER BY fecha_registroDt ASC ";
		$res = $this->db->query($qry);			
		return $res;				 
	}	
	
	function obtener_compras_fecha($fecha_inicio, $fecha_fin){
		$fini=$fecha_inicio;
		$ffin=$this->fecha_fin($fecha_fin);			
			
		$qry = "SELECT * FROM CMS_IntCompra 
		        WHERE fecha_compraDt>='$fini' and fecha_compraDt<'$ffin' ORDER BY id_compraIn ASC ";
		$res = $this->db->query($qry);			
		return $res;
		
	} 
	
	public function fecha_fin($fecha_fin){
		$dia=substr($fecha_fin,8,2);
    	$mes=substr($fecha_fin,5,2);
    	$ano=substr($fecha_fin,0,4);
		
		$fechafinal = mktime(0,0,0,$mes,$dia,$ano);		
		$str=strtotime('+1 day',$fechafinal);
		$ffin=mdate('%Y/%m/%d',$str);
		return $ffin;	
	}
			
}