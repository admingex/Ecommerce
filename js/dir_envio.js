/**
 * @author harteaga956
 */

$(document).ready(function() {
	alert('hola mundo ecommerce!');
	//submit
	$("form[id='login']").submit(function(event) {
		event.preventDefault();
		
		var email = jQuery.trim($('#email').val());
		var password = jQuery.trim($('#password').val());
		
		if(email == '') {
			$('#email').next().text('email requerido');
			return false;
		} else {
			$('#email').next().text();
		}
		
		var email = $('#email');
		
		if (email != undefined && jQuery.trim(email.val()) != '') {
			//validar
			var reg = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;
			if (!reg.test(jQuery.trim(email.val()))) {
				email.next().text('email mal escrito');
				return false;
			} else {
				email.next().text('');
			}
		}
		
		//al menos seleccionar un modo de login...		
		//var opcion = $("input[name='tipo_inicio']");
		//var tipo = $("input[name='tipo_inicio']:checked");
		//validar el tipo de login
		var tipo = $("input[name='tipo_inicio']:checked").val();
		alert ('tipo_inicio ' + tipo + '  ?' );
		
		var opcion = $("#tipo_inicio");
		//alert(typeof(opcion));
		if (!opcion.is(':checked') || opcion == undefined) {
			if (opcion != 'undefined') {
			
				alert ('seleccionar algun tipo de inicio de sesion');
				//alert ('tipo_iniico' + tipo + '  ?' );				
				return false;	
			}
		}
		
		
		
		//alert(tipo == 'registrado');
		if (tipo == 'registrado') {
			
			if(password == '') {
				//alert('contraseña requerida');
				$('#password').next().text('contraseña requerida');
				return false;
			}
		}
		//else {
			//$('#password').next().text('contrase&ntilde;a  no requerida jj');
			//alert('passwd: ' + password);
			
			//return false;
			
		//}
		$('form').submit();
		
		
		
		
	});

		//tipo de login
	$("input[name='tipo_inicio']")
		.change(function() { //cuando seleccionen un tipo de login
		
		//if ($(this).is(':checked'))
		
		var tipo = $(this).val();
		
		if(tipo == 'nuevo') {
			$('#password').attr('disabled', 'disabled');
			//alert(tipo);
		} else if (tipo == 'registrado') {
			//alert(tipo);
			$('#password').removeAttr('disabled');
			$('#password').focus().select();
		}
		/*var opcion = $("input[name='tipo_inicio']");
		if (opcion != undefined) {
			//alert (opcion);
			if (opcion.is(':checked'))
				var tipo = $("input[name='tipo_inicio']:checked");
				alert ('opcion seleccionada: ' + tipo.val());
			return false;
		}
		*/
	
	});

});