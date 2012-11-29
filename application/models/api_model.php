<?php

class Api_model extends CI_Model {	
    function __construct(){        
        parent::__construct();
    }
	
	function obtener_sitio($id_sitio) {		
		$res = $this->db->get_where('CMS_CatSitio', array('id_sitioSi'=>$id_sitio));
		return $res;
	}		
	
	function obtener_canal($id_canal){		
		$res = $this->db->get_where('CMS_CatCanal', array('id_canalSi'=>$id_canal));
		return $res;
	}
	
	function obtener_promocion($id_promocion) {		
		$res = $this->db->get_where('CMS_IntPromocion', array('id_promocionIn'=>$id_promocion));
		return $res;
	}
	
	function obtener_promocion_like($cad){
		$this->db->like('descripcionVc', $cad, 'before');
		$res = $this->db->get('CMS_IntIssue');				
		if($res->num_rows()!=0){			
			$res_art=$this->db->get_where('CMS_IntArticulo', array('issue_id'=>$res->row()->issue_id));
			if($res_art->num_rows()>0){
				$res_prom=$this->db->get_where('CMS_IntPromocion', array('id_promocionIn'=>$res_art->row()->id_promocionIn));
				return $res_prom;				
			}	
		} 
		else{
			return FALSE;
		}			
			
	}
	
	function obtener_sitio_guidx($guidx){
		$res = $this->db->get_where('CMS_CatSitio', array('private_KeyVc'=>$guidx));
		if($res->num_rows()!=0){
			return $res;
		}
		else{
			return FALSE;
		}
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
	
	function obtener_promociones_canales_sitio($id_sitio, $id_canal) {		
		$res = $this->db->get_where('CMS_RelPromocionSitioCanal', array('id_sitioSi'=>$id_sitio, 'id_canalSi'=>$id_canal));							
		return $res;
	}
	
	
	function obtener_articulos($id_promocion){		
		$res = $this->db->get_where('CMS_IntArticulo', array('id_promocionIn'=>$id_promocion));
		return $res;
	}		
	
	function obtener_canal_promocion($id_promocion, $id_sitio){
		$res = $this->db->get_where('CMS_RelPromocionSitioCanal', array('id_promocionIn'=>$id_promocion, 'id_sitioSi'=>$id_sitio));
		if($res->num_rows()!=0){
			return $res;
		}
		else{
			return FALSE;
		}
	}	
	
	function obtener_issue($issue_id){
		$res = $this->db->get_where('CMS_IntIssue', array('issue_id'=>$issue_id));
		return $res;
	}		
	
	function obtener_ocid($ocid){
		$res = $this->db->get_where('TND_CatOCThink', array('oc_id'=>$ocid));
		if($res->num_rows()!=0){
			return $res;
		}
		else{
			return FALSE;
		}
	}
}