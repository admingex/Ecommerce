/**
 * @author harteaga956
 */
$(document).ready(function() {
	var cvv = $("#txt_codigo");
	var forma_pago = $("#forma_pago");
	var reg_cvv = /^[0-9]{3,4}$/;	//más de 2 y menos de 5
	
	/*Enviar orden*/
	$("#enviar").click(function(e) {
		e.preventDefault();
		
		$(".error").remove();	//limpiar mensajes de error
		
		//alert("forma de pago seleccioada: " + forma_pago);
				
		if (forma_pago) {
			if (cvv) {
				if (!reg_cvv.test($.trim(cvv.val()))) {
					cvv.focus().after("<span class='error'>Ingresa un código de seguridad válido</span>");
					return false;
				}
			}
			//Ok
			//alert("Se envía la orden.");
			$("form").submit();	
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