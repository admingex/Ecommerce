/**
 * @author harteaga956
 */
$(document).ready(function() {
	var reg_visa = /^4\d{15}$/;		//con guion: /^4\d{3}-?\d{4}-?\d{4}-?\d{4}$/
	var reg_master_card = /^5[1-5]\d{14,15}$/ ;
	var reg_nombres = /^[A-ZáéíóúÁÉÍÓÚÑñ \'.-]{2,30}$/i;
	
	var tipo_tarjeta = $("#sel_tipo_tarjeta");
	var numero_tarjeta	= $("#txt_numeroTarjeta");
	var nombre 	= $("#txt_nombre");
	var appP 	= $("#txt_apellidoPaterno");
	var appM 	= $("#txt_apellidoMaterno");
	
	$("#guardar_tarjeta").click(function(e) {
		e.preventDefault();	
		$(".error").remove();	//limpiar mensajes de error
		
		//tc
		if (tipo_tarjeta.length > 0 && !reg_visa.test(numero_tarjeta.val()) ) {
			numero_tarjeta.focus().after("<span class='error'>Ingresa un número de tarjeta válido</span>");
			return false;
		} else if ( tipo_tarjeta.length == 0 && !reg_master_card.test(numero_tarjeta.val())) {
			numero_tarjeta.focus().after("<span class='error'>Ingresa un número de tarjeta válido</span>");
			return false;
		} else if (!reg_nombres.test($.trim(nombre.val()))) {
			nombre.focus().after("<span class='error'>Ingresa tu nombre correctamente</spam>");
			return false;
		} else if (!reg_nombres.test($.trim(appP.val()))) {
			appP.focus().after("<span class='error'>Ingresa tu appellido correctamente</spam>");
			return false;
		}
		
		//Ok
		$("form[id^='form_registro_tc']").submit();
	});
	
	//fade out error messsage
	numero_tarjeta.change(function(){
		if ( reg_visa.test(numero_tarjeta.val()) || reg_master_card.test(numero_tarjeta.val()) ) {
			$(this).siblings(".error").fadeOut();
		}
	});
	nombre.change(function(){
		if ( nombre.test($.trim(nombre.val())) ) {
			$(this).siblings(".error").fadeOut();
		}
	});
	appP.change(function(){
		if ( appP.test($.trim(appP.val())) ) {
			$(this).siblings(".error").fadeOut();
		}
	});
	/*
	nombre.change(function(){
		if ($.trim(passwd.val()) != "") {
			$(this).siblings(".error").fadeOut();
		}
	});
	*/	
});