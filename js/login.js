/**
 * @author harteaga956
 */
$(document).ready(function() {
	var reg_email = /^[^0-9][a-zA-Z0-9_]+([.][a-zA-Z0-9_]+)*[@][a-zA-Z0-9_]+([.][a-zA-Z0-9_]+)*[.][a-zA-Z]{2,4}$/;
	var email = $("#email");
	var passwd = $("#password");
	
	
	/*tipo inicio*/
	$("input[id='tipo_inicio']").bind('click',function(){
		var tipo_inicio = $("#tipo_inicio:checked");	//val()
		if (tipo_inicio.val() == "nuevo") {
			passwd.attr("disabled", true);
		} else {
			passwd.removeAttr("disabled");
		}
	});
	
	/*Inicio de Sesión*/
	$("#enviar").click(function(e) {
		e.preventDefault();
		//alert("tipo " + tipo_inicio.val());
		$(".error").remove();	//limpiar mensajes de error
		//email
		if (!reg_email.test(email.val())) {
			email.focus().after("<span class='error'>Ingresa una dirección de correco válida</span>");
			return false;
		} else if ($.trim(passwd.val()) == "" ) {
			passwd.focus().after("<span class='error'>Ingresa tu contraseña</spam>");
			return false;
		}
		//Ok
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
});