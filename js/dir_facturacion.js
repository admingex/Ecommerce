$(document).ready(function() {
	var email = /^[a-zA-Z0-9_\.\-]+@[a-zA-Z0-9\-]+\.[a-zA-Z0-9\-\.]+$/;
	var cp = /^[0-9]{5,5}([- ]?[0-9]{4,4})?$/;
	var rfc1= /^([a-zA-Z]{3})+([0-9]{6})+([a-zA-Z0-9]{3})$/;
	var rfc2= /^([a-zA-Z]{4})+([0-9]{6})+([a-zA-Z0-9]{3})$/;	
	
	$("#guardar_rs").click(function(){
		$(".error").remove();
		$(".error2").remove();     
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
		$(".error2").remove();        
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

function checa_cp(cp){
	url_base='http://ecommerce/'			
	if(cp.length==5){	
		$.ajax({
			type: "POST",
			data: {'codigo_postal' : cp},
			url: url_base + "direccion_envio/get_info_sepomex",
			dataType: "json",				
			async: false,
			success: function(data) {
				//alert("success: " + data.msg);
				if (typeof data.sepomex != null && data.sepomex.length != 0)	{	//regresa un array con las colonias					
					var sepomex = data.sepomex;			//colonias
					var codigo_postal = sepomex[0].codigo_postal;
					var clave_estado = sepomex[0].clave_estado;						
					var estado = sepomex[0].estado;
					var ciudad = sepomex[0].ciudad;
																																	
					$('#txt_estado').val(estado);
					$('#txt_ciudad').val(ciudad);												
					var colonias= new Array();												
					if (sepomex.length > 0){							
						$.each(sepomex, function(indice, colonia) {
							if (colonia.colonia != '') {
								colonias[indice] = colonia.colonia;																								
							}
						});	
						$('#txt_colonia').val('');																				
						$( "#txt_colonia" ).autocomplete({
							source: colonias
						});																
					}	
					
				} 	
				else{																						
					$('#txt_estado').val('');
					$('#txt_ciudad').val('');
					$('#txt_colonia').val('');
				}				
			},
			error: function(data) {
				alert("error: " + data.error);
			},
			complete: function(data) {								
			},
			//async: false,
			cache: false
		});
	}	
}

function llena_formulario(val){
	var datos = val.split(",");
	document.getElementById('txt_calle').value=datos[0];
	document.getElementById('txt_numero').value=datos[1];
	document.getElementById('txt_num_int').value=datos[2];
	document.getElementById('txt_cp').value=datos[3];	
	document.getElementById('txt_estado').value=datos[4];
	document.getElementById('txt_ciudad').value=datos[5];	
	document.getElementById('txt_colonia').value=datos[6];		
}