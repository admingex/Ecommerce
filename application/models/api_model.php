<?php

class Api_model extends CI_Model {	
    function __construct(){        
        parent::__construct();
    }    	
	
		
	
	function obtener_sitio($id_sitio){		
		$res = $this->db->get_where('CMS_CatSitio', array('id_sitioSi'=>$id_sitio));
		return $res;
	}
	
	function obtener_canal($id_canal){		
		$res = $this->db->get_where('CMS_CatCanal', array('id_canalSi'=>$id_canal));
		return $res;
	}
	
	function obtener_promocion($id_promocion){		
		$res = $this->db->get_where('CMS_IntPromocion', array('id_promocionIn'=>$id_promocion));
		return $res;
	}
		
	function obtener_sitios(){
		$this->db->group_by('id_sitioSi');		
		$res = $this->db->get('CMS_RelPromocionSitioCanal');				
		return $res;		
	}
	
	function obtener_canales_sitio($id_sitio){
		$this->db->group_by('id_canalSi');    	
		$res = $this->db->get_where('CMS_RelPromocionSitioCanal', array('id_sitioSi'=>$id_sitio));							
		return $res;
	}
	
	function obtener_promociones_canales_sitio($id_sitio, $id_canal){		
		$res = $this->db->get_where('CMS_RelPromocionSitioCanal', array('id_sitioSi'=>$id_sitio, 'id_canalSi'=>$id_canal));							
		return $res;
	}
	
	
	function obtener_articulos($id_promocion){		
		$res = $this->db->get_where('CMS_IntArticulo', array('id_promocionIn'=>$id_promocion));
		return $res;
	}	
			
}