$(document).ready(function() {
	var email = /^[a-zA-Z0-9_\.\-]+@[a-zA-Z0-9\-]+\.[a-zA-Z0-9\-\.]+$/;
	var cp = /^[0-9]{5,5}([- ]?[0-9]{4,4})?$/;
	var rfc1= /^([a-zA-Z]{3})+([0-9]{6})+([a-zA-Z0-9]{3})$/;
	var rfc2= /^([a-zA-Z]{4})+([0-9]{6})+([a-zA-Z0-9]{3})$/;	
	
	$("#guardar_rs").click(function(){
		$(".error").remove();
        if( $("#txt_razon_social").val() == "" ){
            $("#txt_razon_social").focus().after("<span class='error'>Por favor ingresa tu nombre o razón social</span>");            
            return false;            
        }
        else{
        	if(($("#txt_rfc").val() == "") || ($("#txt_rfc").val().length >13) || ($("#txt_rfc").val().length <12)){
        		$("#txt_rfc").focus().after("<span class='error'>Por favor ingresa tu RFC</span>");
        		return false;
        	}
        	else{
        		if(($("#txt_rfc").val().length==12) && (!rfc1.test($("#txt_rfc").val()))){        			
        			$("#txt_rfc").focus().after("<span class='error'>Por favor ingresa tu RFC</span>");
        			return false;
        		}        		
        		else if(($("#txt_rfc").val().length==13) && (!rfc2.test($("#txt_rfc").val()))){
        			$("#txt_rfc").focus().after("<span class='error'>Por favor ingresa tu RFC</span>");
        			return false;
        		}   
        		else{
        			if($("#txt_email").val()=="" || !email.test($("#txt_email").val())){
						$("#txt_email").focus().after("<span class='error2'>Por favor ingresa un correo electrónico válido. Ejemplo: nombre@dominio.mx</span>");
        				return false;
        			}
        			else{        										        				        				        				
        				$('#form_agregar_rs').submit();	        				        				
        			}    
        		}     		
        	}
        }			
	});
	
	$("#guardar_direccion").click(function() {		
		$(".error").remove();        
        			if($("#txt_calle").val()==""){
        				$("#txt_calle").focus().after("<span class='error'>Por favor ingresa una calle</span>");
        				return false;
        			}
        			else{
        				if($("#txt_numero").val()==""){
        					$("#txt_numero").focus().after("<span class='error'>Por favor ingresa el número exterior</span>");
        					return false;
        				}
        				else{
        					if($("#txt_cp").val()=="" || !cp.test($("#txt_cp").val())){
        						$("#txt_cp").focus().after("<span class='error2'>Por favor ingresa un código postal de 5 dígitos</span>");
        						return false;
        					}
        					else{
        						if($("#txt_estado").val()==""){
        							$("#txt_estado").focus().after("<span class='error'>Por favor ingresa el estado</span>");
        							return false;
        						}
        						else{
        							if($("#txt_ciudad").val()==""){
										$("#txt_ciudad").focus().after("<span class='error'>Por favor ingresa la ciudad</span>");
										return false;
        							}
        							else{
        								if($("#txt_colonia").val()==""){
        									$("#txt_colonia").focus().after("<span class='error'>Por favor ingresa la colonia</span>");
        									return false;
        								}	
        								else{        									        									
        										$('#form_agregar_direccion').submit();	
        									        									
        								}
        							}        						    
        						}        						
        					}        						
        				}        				
        			}        		            			
        				  				
	});	
});