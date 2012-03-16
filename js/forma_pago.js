/**
 * @author harteaga956
 */

$(document).ready(function() {
	var reg_email = /^[^0-9][a-zA-Z0-9_]+([.][a-zA-Z0-9_]+)*[@][a-zA-Z0-9_]+([.][a-zA-Z0-9_]+)*[.][a-zA-Z]{2,4}$/;	
	var reg_nombres = /^[A-ZáéíóúÁÉÍÓÚÑñ \'.-]{2,30}$/i;
	var reg_direccion = /^[A-Z0-9 \'.,-áéíóúÁÉÍÓÚÑñ]{2,50}$/i;
	var reg_cp = /^([1-9]{2}|[0-9][1-9]|[1-9][0-9])[0-9]{3}$/;
	var reg_telefono = /^[0-9 ()+-]{8,20}$/
	
	var tipo_tarjeta = $("#sel_tipo_tarjeta");
	var numero_tarjeta	= $("#txt_numeroTarjeta");
	var nombre 	= $("#txt_nombre");
	var appP 	= $("#txt_apellidoPaterno");
	var appM 	= $("#txt_apellidoMaterno");
	
	var calle	= $("#txt_calle");
	var cp		= $("#txt_cp");
	var ciudad 	= $("#txt_ciudad");
	var estado 	= $("#txt_estado");
	var pais 	= $("#txt_pais");
	var email 	= $("#txt_email");
	var telefono= $("#txt_telefono");
	
	$("#guardar_tarjeta").click(function(e) {
		e.preventDefault();	
		$(".error").remove();	//limpiar mensajes de error
		
		//tc-prosa
		if (numero_tarjeta.length > 0 && !validarTarjeta(tipo_tarjeta, $.trim(numero_tarjeta.val()))) {
			numero_tarjeta.focus().after("<span class='error'>Ingresa un número de tarjeta válido</span>");
			return false;
		} else if (!reg_nombres.test($.trim(nombre.val()))) {
			nombre.focus().after("<span class='error'>Ingresa tu nombre correctamente</spam>");
			return false;
		} else if (!reg_nombres.test($.trim(appP.val()))) {
			appP.focus().after("<span class='error'>Ingresa tu appellido correctamente</spam>");
			return false;
		} else if ($.trim(appM.val()) != "" && !reg_nombres.test($.trim(appM.val()))) {
			appM.focus().after("<span class='error'>Ingresa tu appellido correctamente</spam>");
			return false;
		}	
		//Amex
		else if (numero_tarjeta.length > 0 && tipo_tarjeta.length == 0) {
			if (!reg_direccion.test($.trim(calle.val()))) {
				calle.focus().after("<span class='error'>Ingresa calle y número correctamente</spam>");
				return false;
			} else if (!reg_cp.test($.trim(cp.val()))) {
				cp.focus().after("<span class='error'>Ingresa tu código postal correctamente</spam>");
				return false;
			} else if (!reg_direccion.test($.trim(ciudad.val()))) {
				ciudad.focus().after("<span class='error'>Ingresa tu ciudad correctamente</spam>");
				return false;
			} else if (!reg_direccion.test($.trim(estado.val()))) {
				estado.focus().after("<span class='error'>Ingresa tu estado correctamente</spam>");
				return false;
			} else if (!reg_direccion.test($.trim(pais.val()))) {
				pais.focus().after("<span class='error'>Ingresa tu país correctamente</spam>");
				return false;
			} else if ($.trim(email.val()) != "" && !reg_email.test($.trim(email.val()))) {
				email.focus().after("<span class='error'>Ingresa tu correo correctamente</spam>");
				return false;
			} else if (!reg_telefono.test($.trim(telefono.val()))) {		//no vacío
				telefono.focus().after("<span class='error'>Ingresa tu teléfono correctamente</spam>");
				return false;
			} 
		}
		//amex
		
		//Ok
		//$("form[id^='form_registro_tc']").submit();
		$(this).parents("form").submit();
	});
	
	//fade out error messsage
	numero_tarjeta.change(function() {
		if ( validarTarjeta(tipo_tarjeta, $.trim(numero_tarjeta.val())) ) {
			$(this).siblings(".error").fadeOut();
		}
	});
	nombre.change(function() {
		if ( reg_nombres.test($.trim(nombre.val())) ) {
			$(this).siblings(".error").fadeOut();
		}
	});
	appP.change(function() {
		if ( reg_nombres.test($.trim(appP.val())) ) {
			$(this).siblings(".error").fadeOut();
		}
	});
	appM.change(function() {
		if ( reg_nombres.test($.trim(appM.val())) ) {
			$(this).siblings(".error").fadeOut();
		}
	})
	calle.change(function() {
		if ( reg_direccion.test($.trim(calle.val())) ) {
			$(this).siblings(".error").fadeOut();
		}
	});
	cp.change(function() {
		if ( reg_cp.test($.trim(cp.val())) ) {
			$(this).siblings(".error").fadeOut();
		}
	});
	ciudad.change(function() {
		if ( reg_direccion.test($.trim(ciudad.val())) ) {
			$(this).siblings(".error").fadeOut();
		}
	});
	estado.change(function() {
		if ( reg_direccion.test($.trim(estado.val())) ) {
			$(this).siblings(".error").fadeOut();
		}
	});
	pais.change(function() {
		if ( reg_direccion.test($.trim(pais.val())) ) {
			$(this).siblings(".error").fadeOut();
		}
	});
	email.change(function() {
		if ( reg_email.test($.trim(email.val())) ) {
			$(this).siblings(".error").fadeOut();
		}
	});
	telefono.change(function() {
		if ( reg_telefono.test($.trim(telefono.val())) ) {
			$(this).siblings(".error").fadeOut();
		}
	});
	//if (if (tipo_tarjeta.length == 0))
	/*
	nombre.change(function(){
		if ($.trim(passwd.val()) != "") {
			$(this).siblings(".error").fadeOut();
		}
	});
	*/	
});
/*
 *Validar con el algoritmo de Luhn
 */
function validarTarjeta(tipo_tarjeta, num_tarjeta) {
	var reg_visa 		= /^4[0-9]{12}(?:[0-9]{3})?$/;
	var reg_master_card = /^5[1-5][0-9]{14}$/;
	var reg_amex		= /^3[47][0-9]{13}$/;
		
	if (tipo_tarjeta.length > 0 && !reg_visa.test(num_tarjeta) && !reg_master_card.test(num_tarjeta)) {
			return false;
		} else if (tipo_tarjeta.length == 0 && !reg_amex.test(num_tarjeta)) {
			return false;
		} else if (!validarLuhn(num_tarjeta)) {
			return false;
		} 
		
		return true;		//tarjeta válida
}

function validarLuhn(num_tarjeta) {
	var num_card = new Array(16);
	var i;
	var len = 0;
	var tarjeta_valida = false;
	var str = "";
	
	//Obtener los dígitos de la tarjeta
	for (i = 0; i < num_tarjeta.length; i++) {
		num_card[len++] = parseInt(num_tarjeta.charAt(i));
	}
		
	//algoritmo Luhn
	var checksum = 0
	for (i = len - 1; i >= 0; i--) {
		if (i % 2 == len % 2) {
			var n = num_card[i] * 2;
			checksum += parseInt(n / 10) + (n % 10);
		} else {
			checksum += num_card[i];
		}
	}
	
	tarjeta_valida = (checksum % 10 == 0);
	
	return tarjeta_valida;
	//alert ("es valida: " + tarjeta_valida);
}