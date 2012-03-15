<?php

class Promociones_model extends CI_Model {	
    function __construct(){        
        parent::__construct();
    }    	
	
	function obtener_promociones(){		
		$res = $this->db->get('CMS_IntPromocion');				
		return $res;		
	}	
		
}