$(document).ready(function() {
	var email = /^[a-zA-Z0-9_\.\-]+@[a-zA-Z0-9\-]+\.[a-zA-Z0-9\-\.]+$/;
	var error='';
	$("#enviar").click(function() {		
		$(".error").remove();
		$(".error2").remove();
        if( $("#txt_nombre").val() == "" ){        	
            $("#txt_nombre").focus().after("<span class='error'>Por favor ingresa tu nombre</span>");            
            return false;            
        }   
        else{
        	if($("#txt_apellidoPaterno").val()==""){
        		$("#txt_apellidoPaterno").focus().after("<span class='error'>Por favor ingresa tu apellido paterno</span>");
        		return false;
        	}  
        	else{
        		if($("#email").val()=="" || !email.test($("#email").val())){
        			$("#email").focus().after("<span class='error2'>Por favor ingresa un correo electrónico <br />válido. Ejemplo: nombre@dominio.mx</span>");            
            		return false;
        		}
        		else{        			
        			if(!valida_password($("#email").val(),$("#password").val())){        				        				
        				return false;
        			}
        			else{
        				if($("#password").val()!=$("#password_2").val()){
        					$("#password_2").focus().after("<span class='error2'>Las contraseñas ingresadas no son idénticas. Por favor intenta de nuevo.</span>");
        					return false;
        				}
        				else{        					
        					$("#form_registro_usuario").submit();
        				}        				
        			}        				
        		}        		
        	}      	
        }            		  				
	});
	$("#recordar_password").click(function() {		
		$(".error").remove();
		$(".error2").remove();
        if($("#email").val()=="" || !email.test($("#email").val())){        	
            $("#email").focus().after("<span class='error2'>Por favor ingresa un correo electrónico <br />válido. Ejemplo: nombre@dominio.mx</span>");            
            return false;            
        }   
        else{  
        	$("#form_recordar_password").submit();      	     	
        }            		  				
	});
	$("#cambiar_password").click(function() {		
		$(".error").remove();
		$(".error2").remove();
        if($("#password_temporal").val()==""){        	
            $("#password_temporal").focus().after("<span class='error'>Ingresa una clave temporal</span>");            
            return false;            
        }   
        else{
        	if(!valida_password("aaa@",$("#password").val())){ 
        		//$("#password").focus().after("<span class='error2'>Por favor escribe una contraseña que contenga al menos 8 caracteres, letras mayúsculas, minúsculas y números</span>");            
            	return false;            
        	}
        	else{
        		if($("#password").val()!=$("#password_2").val()){
        			$("#password_2").focus().after("<span class='error2'>Las contraseñas ingresadas no son idénticas. Por favor intenta de nuevo.</span>");
        			return false;
        		}
        		else{        			
        			$("#form_cambiar_password").submit();
        		}        		        			
        	}          	      	     
        }            		  				
	});	
});

function valida_password(acc,pass){
	var cadlogin=(acc.split("@",1));
	var nc= /^([0-9a-zA-Z])+$/;		
	
	if(pass.length<8){
		$("#password").focus().after("<span class='error'>Debe contener por lo menos 8 caracteres</span>");
		return false;
	}
	else{
		if(!nc.test(pass)){
			$("#password").focus().after("<span class='error'>Solo debe incluir letras y numeros</span>");
			return false;			
		}		
		else{			
			if((pass==cadlogin)){
				$("#password").focus().after("<span class='error2'>La contraseña no debe contener una parte del correo electrónico ingresado</span>");
				return false;				
			}
			else{				
   				if(!contiene_mayuscula(pass)){
   					$("#password").focus().after("<span class='error2'>Debe contener por lo menos una mayuscula</span>");
   					return false;   					
   				}   			
   				else{
   					if(!contiene_numero(pass)){
   						$("#password").focus().after("<span class='error'>Debe contener por lo menos un numero</span>");
   						return false;   						
   					}
   					else{
   						if(!contiene_minuscula(pass)){
   							$("#password").focus().after("<span class='error'>Debe contener por lo menos una minuscula</span>");
   							return false;   							
   						}
   						else{
   							if(!contiene_consecutivos(pass)){
   								$("#password").focus().after("<span class='error2'>No se debe incluir el mismo caracter mas de 2 veces</span>");
   								return false;   								
   							}
   							else{
   								return true;
   							}   							
   						}   						
   					}   				
   				}   								
			}					
		}					
	}
}
function contiene_mayuscula(cad){
	var may='ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	for(i=0; i<cad.length; i++){				
    	if (may.indexOf(cad.charAt(i),0)!=-1){
        	return 1;
      	}         			  			
   	}
   	return 0;
}
function contiene_minuscula(cad){
	var min='abcdefghijklmnopqrstuvwxyz';
	for(i=0; i<cad.length; i++){				
    	if (min.indexOf(cad.charAt(i),0)!=-1){
        	return 1;
      	}         			  			
   	}
   	return 0;
}
function contiene_numero(cad){
	var num='0123456789';
	for(i=0; i<cad.length; i++){				
    	if (num.indexOf(cad.charAt(i),0)!=-1){
        	return 1;
      	}         			  			
   	}
   	return 0;
}

function contiene_consecutivos(cad){
	for(i=2; i<cad.length; i++){		
		term0=cad.charAt(i-2);
		term1=cad.charAt(i-1);
		term2=cad.charAt(i);										
		if((term0==term1)&&(term1==term2)){
			return 0;
		}		     		            			  		
   	}   	
   	return 1;
}