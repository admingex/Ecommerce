/**
 * @author harteaga956
 */
$(document).ready(function() {
	var reg_email = /^[^0-9][a-zA-Z0-9_]+([.][a-zA-Z0-9_]+)*[@][a-zA-Z0-9_]+([.][a-zA-Z0-9_]+)*[.][a-zA-Z]{2,4}$/;
	var email = $("#email");
	var passwd = $("#password");
	var registro = false;
	
	/*tipo inicio*/
	$("#divtipo_inicio2").click(function() {				
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
	});
	
	/*Inicio de Sesión*/
	$("#enviar").click(function(e) {
		e.preventDefault();
		//alert("tipo " + tipo_inicio.val());
		$(".error").remove();	//limpiar mensajes de error
		
		if (!registro) {
			//email
			if (!reg_email.test(email.val())) {
				email.focus().after("<span class='error'>Ingresa una dirección de correco válida</span>");
				return false;
			} 
			else if (passwd.val() == "" ) {
				passwd.focus().after("<span class='error'>Ingresa tu contraseña</spam>");
				return false;
			}
			else{
				$("form").submit();
			}									
		} else {
			$("form").attr("action", "/ecommerce/index.php/registro/")
			$("form").submit();
		}
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
});