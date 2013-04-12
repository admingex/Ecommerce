/**
 * @author admingex
 */
$(document).ready(function() {
	var cvv = $("#txt_codigo");
	var pago_tarjeta = $("#tarjeta");
	var pago_deposito = $("#deposito");
	var reg_cvv = /^[0-9]{3,4}$/;	//más de 2 y menos de 5
	var cobro = 0;
	
	/*Enviar orden*/
	$("#enviar").click(function(e) {
		e.preventDefault();
		//prevenir doble click
		$(this).css("display", "none");
		
		$(".error").remove();	//limpiar mensajes de error
		
		//alert("forma de pago seleccioada: " + forma_pago);
				
		if (pago_tarjeta.length > 0) {
			if (cvv.length > 0) {
				if (!reg_cvv.test($.trim(cvv.val()))) {
					cvv.focus().after("<span class='error'>Ingresa un código de seguridad válido</span>");
					//habilitar de nuevo al botón
					$(this).css("display", "block");
					return false;
				}
			}
			//Ok
			//alert("Se envía la orden.");
			if(cobro==0){
				cobro = 1;
				$("form").submit();				
			}	
			//alert("envio tarjeta");	
		} else if (pago_deposito.length > 0) {
			$("form").submit();
			//alert("envio depósito");
		} else {
			alert("No hay forma de pago seleccioada.");
		}
	});
	
	//fade out error messsage
	cvv.change(function(){
		if (reg_cvv.test($.trim(cvv.val()))) {
			$(this).siblings(".error").fadeOut();
		}
	});
	
});