/**
 * @author harteaga956
 */
var url_base = "http://ecommerce/";
$(document).ready(function() {
	var reg_email = /^[^0-9][a-zA-Z0-9_]+([.][a-zA-Z0-9_]+)*[@][a-zA-Z0-9_]+([.][a-zA-Z0-9_]+)*[.][a-zA-Z]{2,4}$/;
	var email = $("#email");
	var passwd = $("#password");
	var registro = false;	
	
	$('input').bind("click keypress", function() {
		$(".error").remove();
		$(".error2").remove();
		$(".validation_message").remove();
	});
	
	/*tipo inicio*/
	$("#divtipo_inicio2").click(function() {	
		$(".error").remove();
		$(".error2").remove();				
		$("#divtipo_inicio2").removeClass('radio_no_selected').addClass('radio_selected');
		$("#divtipo_inicio").removeClass('radio_selected').addClass('radio_no_selected');
		document.getElementById('tipo_inicio2').checked='checked';
		document.getElementById('tipo_inicio').checked='';
		passwd.removeAttr("disabled");
		registro = false;		
	});
	
	$("#divtipo_inicio").click(function() {				
		$("#divtipo_inicio").removeClass('radio_no_selected').addClass('radio_selected');
		$("#divtipo_inicio2").removeClass('radio_selected').addClass('radio_no_selected');
		document.getElementById('tipo_inicio2').checked='';
		document.getElementById('tipo_inicio').checked='checked';				
		passwd.attr("disabled", true);
		registro = true;	
		consulta_mail($('#email').val());		
	});
	
	/*Inicio de Sesión*/
	$("#enviar").click(function(e) {
		e.preventDefault();
		//alert("tipo " + tipo_inicio.val());
		$(".error").remove();	//limpiar mensajes de error
		
		if (!registro) {
			//email
			if (!reg_email.test(email.val())) {
				email.focus().after("<div class='error2'>Por favor ingresa una dirección de correo válida. Ejemplo: nombre@dominio.mx</div>");
				return false;
			} 
			else if (passwd.val() == "" ) {
				passwd.focus().after("<div class='error2'>Por favor escribe tu contraseña o elige iniciar sesión como cliente nuevo</div>");
				return false;
			}
			else{
				$("form").submit();
			}									
		} 		
		else {
			$("form").attr("action", "registro")
			$("form").submit();
		}
	});
	
	//Recuperar contrasena			 	
	
	$("#olvido_contrasena").click(function(e){
		e.preventDefault();
		//alert("tipo " + tipo_inicio.val());
		$(".error").remove();	//limpiar mensajes de error	
		$("form").attr("action", "password")
		$("form").submit();
	});
	
	//fade out error messsage
	email.change(function(){
		if (reg_email.test(email.val())) {
			$(this).siblings(".error").fadeOut();
		}
	});
	passwd.change(function(){
		if ($.trim(passwd.val()) != "") {
			$(this).siblings(".error").fadeOut();
		}
	});
	
	email.keyup(function(){		
		consulta_mail(this.value);					
	});
		
});


function consulta_mail(mail) {	
	$(".error2").remove();
	$.ajax({
			type: "GET",
			data: {'mail' : mail},
			url: url_base + "login/consulta_mail",
			dataType: "json",				
			async: true,
			
			success: function(data) {	
				if(data.mail){
					cte_reg=document.getElementById('tipo_inicio2').checked;							
					if(!cte_reg && data.mail==1){										
						$('#email').focus().after("<div class='error2'>ya tenemos registrado tu correo, por favor <a href='"+url_base+"password' id='olvido_contrasena' style='color: #FFF'>recupera aquí tu contraseña</a></div>");
					}	
				}
																		  				  									  										
			},
			error: function(data) {
				alert("error: " + data);
			},
			complete: function(data){				
			},
			//async: false,
			cache: false
	}); 	
}