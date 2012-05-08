/**
 * @author harteaga956
 */

$(document).ready(function() {
	var forms = $("form[id*='registro']");
	
	var reg_cp = /^([1-9]{2}|[0-9][1-9]|[1-9][0-9])[0-9]{3}$/;
	var reg_email = /^[^0-9][a-zA-Z0-9_]+([.][a-zA-Z0-9_]+)*[@][a-zA-Z0-9_]+([.][a-zA-Z0-9_]+)*[.][a-zA-Z]{2,4}$/;
	var reg_nombres = /^[A-ZáéíóúÁÉÍÓÚÑñ \'.-]{2,30}$/i;
	var reg_numeros = /^[A-Z0-9 -.#/]{1,50}$/i;
	var reg_direccion = /^[A-Z0-9 \'.,-áéíóúÁÉÍÓÚÑñ]{2,50}$/i;
	var reg_telefono = /^[0-9 ()+-]{10,20}$/
	
	var calle	= $("#txt_calle");
	var num_ext	= $("#txt_numero");
	var cp		= $("#txt_cp");
	var pais 	= $("#sel_pais");
	var estado_t 	= $("#txt_estado");
	var ciudad_t 	= $("#txt_ciudad");
	var colonia_t	= $("#txt_colonia");
	var estado_s 	= $("#sel_estados");
	var ciudad_s 	= $("#sel_ciudades");
	var colonia_s	= $("#sel_colonias");
	var telefono= $("#txt_telefono");
	var estado, ciudad, colonia;
	
	if (forms.length > 0) {
		//alert("cuantos hay: " + forms.length);
		$("a.agregar_direccion").parent().parent().remove();
	}
	
	//alert('hola mundo ecommerce GEx!');
	$("#btn_cp").ajaxError(function() {
		//alert('Error Handler invoked when an error ocurs on CP field!');		//Ok
	});

	/*validacion_registro*/
	$("#guardar_direccion").click(function(e) {
		e.preventDefault();
		$(".error").remove();
		
		estado 	= (estado_t.is(":visible")) ? estado_t : estado_s;	
		ciudad 	= (ciudad_t.is(":visible")) ? ciudad_t : ciudad_s;
		colonia	= (colonia_t.is(":visible")) ? colonia_t : colonia_s;
					
		if (!reg_direccion.test($.trim(calle.val()))) {
			calle.focus().after("<span class='error'>Ingresa la calle correctamente</span>");
			return false;
		} else if (!reg_numeros.test($.trim(num_ext.val()))) {
			num_ext.focus().after("<span class='error'>Ingresa tu número exterior correctamente</span>");
			return false;
		} else if (!reg_cp.test($.trim(cp.val()))) {
			cp.focus().after("<span class='error'>Ingresa tu código postal correctamente</span>");
			return false;
		}  else if (pais.val() == '') {
			pais.focus().after("<span class='error'>Ingresa tu país correctamente</span>");
			return false;
		} else if ((estado_t.is(":visible") && !reg_direccion.test($.trim(estado_t.val()))) || (estado_s.is(":visible") && estado_s.val() == '')) {
			estado.focus().after("<span class='error'>Ingresa tu estado correctamente</span>");
			return false;
		} else if ((ciudad_t.is(":visible") && !reg_direccion.test($.trim(ciudad_t.val()))) || (ciudad_s.is(":visible") && ciudad_s.val() == '')) {
			ciudad.focus().after("<span class='error'>Ingresa tu ciudad correctamente</span>");
			return false;
		} else if ((colonia_t.is(":visible") && !reg_direccion.test($.trim(colonia_t.val()))) || (colonia_s.is(":visible") && colonia_s.val() == '')) {
			colonia.focus().after("<span class='error'>Ingresa tu colonia correctamente</span>");
			return false;
		} else if (!reg_telefono.test($.trim(telefono.val()))) {
			telefono.focus().after("<span class='error'>Ingresa tu teléfono correctamente</span>");
			return false;
		}
		//alert('ok');
		$(this).parents("form").submit();
	});		
	
	calle.change(function() {
		if ( reg_direccion.test($.trim(calle.val())) ) {
			$(this).siblings(".error").fadeOut();
		}
	});
	num_ext.change(function() {
		if ( reg_direccion.test($.trim(num_ext.val())) ) {
			$(this).siblings(".error").fadeOut();
		}
	});
	cp.change(function() {
		if ( reg_cp.test($.trim(cp.val())) ) {
			$(this).siblings(".error").fadeOut();
		}
	});
	pais.change(function() {
		if ( reg_direccion.test($.trim(pais.val())) ) {
			$(this).siblings(".error").fadeOut();
		}
	});
	
	estado_s.change(function() {
		if ( reg_direccion.test($.trim(estado_s.val())) ) {
			$(this).siblings(".error").fadeOut();
		}
	});
	ciudad_s.change(function() {
		if ( reg_direccion.test($.trim(ciudad_s.val())) ) {
			$(this).siblings(".error").fadeOut();
		}
	});
	colonia_s.change(function() {
		if ( reg_direccion.test($.trim(colonia_s.val())) ) {
			$(this).siblings(".error").fadeOut();
		}
	});	
	estado_t.change(function() {
		if ( reg_direccion.test($.trim(estado_t.val())) ) {
			$(this).siblings(".error").fadeOut();
		}
	});
	ciudad_t.change(function() {
		if ( reg_direccion.test($.trim(ciudad_t.val())) ) {
			$(this).siblings(".error").fadeOut();
		}
	});
	colonia_t.change(function() {
		if ( reg_direccion.test($.trim(colonia_t.val())) ) {
			$(this).siblings(".error").fadeOut();
		}
	});
	telefono.change(function() {
		if ( reg_telefono.test($.trim(telefono.val())) ) {
			$(this).siblings(".error").fadeOut();
		}
	});
	
	/*Ocultar campos abiertos de estado, ciudad, colonia*/
	$('tr.div_otro_pais').hide();
	
	//onChange:
	$('#sel_pais').change(function() {
		/*hacer un toggle si es necesario*/
		var es_mx = false; 
		$.getJSON("http://localhost/ecommerce/index.php/direccion_envio/es_mexico/" + $(this).val(),
			function(data) {
				if (!data.result) {	//no es México
					$('tr.div_mexico').hide();
					$('tr.div_otro_pais').show();
				} else {
					$('tr.div_mexico').show();
					$('tr.div_otro_pais').hide();
				}
			}
		);
	}).change();	//se lanza al inicio de la carga para verificar al inicio
	
	$('#sel_estados').change(function() {
		//actualizar ciudad y colonia
		var clave_estado = $("#sel_estados option:selected").val();
		//alert('change estado ' + clave_estado);
		actualizar_ciudades(clave_estado);
		
		//limpiar las colonias
		$("#sel_colonias").empty();
		$("<option></option>").attr("value", '').html('Selecionar').appendTo("#sel_colonias");
	});
	
	$('#sel_ciudades').change(function() {
		//actualizar ciudad y colonia
		var clave_estado = $("#sel_estados option:selected").val();
		var ciudad = $("#sel_ciudades option:selected").val();
		
		//alert('change estado ' + clave_estado + '' + 'change ciudad ' + ciudad + '');
		actualizar_colonias(clave_estado, ciudad);
	});
	
	$('#sel_colonias').change(function() {
		//actualizar cp en base a la colonia seleccionada
		var clave_estado = $("#sel_estados option:selected").val();
		var ciudad = $("#sel_ciudades option:selected").val();
		var colonia = $("#sel_colonias option:selected").val();
		
		actualizar_cp(clave_estado, ciudad, colonia);
	});
	
	//con el botón de llenar sólo se recupera y selecciona edo. y ciudad
	$("#btn_cp").click(function() {
		var text_selected = $("#sel_pais option:selected").text();
		var val_selected = $("#sel_pais option:selected").val();
		
		var cp = $.trim($("#txt_cp").val());	//.val();
		
		//validar cp
		if (!cp || !reg_cp.test($.trim(cp))) {
			alert('Por favor ingresa un código válido');
			return false
		}
		
		//var sel_estados = $("#sel_estados");
		
		$.ajax({
			type: "POST",
			data: {'codigo_postal' : cp},
			url: "http://localhost/ecommerce/index.php/direccion_envio/get_info_sepomex",
			dataType: "json",				
			async: false,
			success: function(data) {
				//alert("success: " + data.msg);
				if (typeof data.sepomex != null)	{	//regresa un array con las colonias
					//alert('data is ok, tipo: ' + typeof(data));
					var sepomex = data.sepomex;			//colonias
					var codigo_postal = sepomex[0].codigo_postal;
					var clave_estado = sepomex[0].clave_estado;
					
					var estado = sepomex[0].estado;
					var ciudad = sepomex[0].ciudad;
											
					$("#sel_estados").val(clave_estado);
					
					//alert("Estado: " + estado + ", ciudad: " + ciudad + ", cp: " + codigo_postal);
					//$("#sel_estados").trigger('change');
					
					
					//carga del catálogo ciudades y selección
					$.ajax({
						type: "POST",
						data: {'estado': clave_estado},
						url: "http://localhost/ecommerce/index.php/direccion_envio/get_ciudades",
						dataType: "json",				
						async: false,
						success: function(datos) {
							var ciudades = datos.ciudades;
							
							$("#sel_ciudades").empty();
							$("<option></option>").attr("value", '').html('Selecionar').appendTo("#sel_ciudades");
							
							if (ciudades.length == undefined) {	//DF sólo devuelve un obj de ciudad.
								$("<option></option>").attr("value", ciudades.clave_ciudad).html(ciudades.ciudad).appendTo("#sel_ciudades");
								$("#sel_ciudades").trigger('change');	//trigger cities' change event
							} else {							//ciudades.length == 'undefined'
								
								$.each(ciudades, function(indice, ciudad) {
									if (ciudad.clave_ciudad != '') {
									
										$("<option></option>").attr("value", ciudad.clave_ciudad).html(ciudad.ciudad).appendTo("#sel_ciudades");
									}
								});
							}
							//select choosen city
							$("#sel_ciudades").val(ciudad);
							
							//trigger ciudades change 
							//$("#sel_ciudades").trigger('change');
							
							//Cargar las colonias
							$("#sel_colonias").empty();
							if (sepomex.length > 1)
								$("<option></option>").attr("value", '').html('Selecionar').appendTo("#sel_colonias");
							$.each(sepomex, function(indice, colonia) {
								if (colonia.colonia != '') {
									$("<option></option>").attr("value", colonia.colonia).html(colonia.colonia).appendTo("#sel_colonias");
								}
							});
						},
						error: function(data) {
							alert("error al recuperar ciudades: " + data.error);
						}
					});
					/*
					$.post( 'http://localhost/ecommerce/index.php/direccion_envio/get_ciudades',
						// when the Web server responds to the request
						{ 'estado': clave_estado},
						function(datos) {
							var ciudades = datos.ciudades;
							
							$("#sel_ciudades").empty();
							$("<option></option>").attr("value", '').html('Selecionar').appendTo("#sel_ciudades");
							
							if (ciudades.length == undefined) {	//DF sólo devuelve un obj de ciudad.
								$("<option></option>").attr("value", ciudades.clave_ciudad).html(ciudades.ciudad).appendTo("#sel_ciudades");
								$("#sel_ciudades").trigger('change');	//trigger cities' change event
							} else {							//ciudades.length == 'undefined'
								
								$.each(ciudades, function(indice, ciudad) {
									if (ciudad.clave_ciudad != '') {
									
										$("<option></option>").attr("value", ciudad.clave_ciudad).html(ciudad.ciudad).appendTo("#sel_ciudades");
									}
								});
							}
							//select choosen city
							$("#sel_ciudades").val(ciudad);
							
							//trigger ciudades change 
							//$("#sel_ciudades").trigger('change');
							
							//Cargar las colonias
							$("#sel_colonias").empty();
							if (sepomex.length > 1)
								$("<option></option>").attr("value", '').html('Selecionar').appendTo("#sel_colonias");
							$.each(sepomex, function(indice, colonia) {
								if (colonia.colonia != '') {
									$("<option></option>").attr("value", colonia.colonia).html(colonia.colonia).appendTo("#sel_colonias");
								}
							});
						}, 
						"json"
					);
					*/
					
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
	});

});

function actualizar_ciudades(clave_estado) {
	$.ajax({
		type: "POST",
		data: {'estado': clave_estado},
		url: "http://localhost/ecommerce/index.php/direccion_envio/get_ciudades",
		dataType: "json",				
		async: false,
		success: function(datos) {
			var ciudades = datos.ciudades;
			
			$("#sel_ciudades").empty();
			$("<option></option>").attr("value", '').html('Selecionar').appendTo("#sel_ciudades");
			
			if (ciudades != null) {
				if (ciudades.length == undefined) {	//DF sólo devuelve un obj de ciudad.
					$("<option></option>").attr("value", ciudades.clave_ciudad).html(ciudades.ciudad).appendTo("#sel_ciudades");
					$("#sel_ciudades").trigger('change');	//trigger cities' change event
				} else {							//ciudades.length == 'undefined'
					$.each(ciudades, function(indice, ciudad) {
						if (ciudad.clave_ciudad != '') {
							$("<option></option>").attr("value", ciudad.clave_ciudad).html(ciudad.ciudad).appendTo("#sel_ciudades");
						}
					});
				}
			}
		},
		error: function(data) {
			alert("error al recuperar ciudades 2: " + data.error);
		}
	});
	/*
	$.post( 'http://localhost/ecommerce/index.php/direccion_envio/get_ciudades',
		// when the Web server responds to the request
		{ 'estado': clave_estado},
		function(datos) {
			var ciudades = datos.ciudades;
			
			$("#sel_ciudades").empty();
			$("<option></option>").attr("value", '').html('Selecionar').appendTo("#sel_ciudades");
			
			if (ciudades != null) {
				if (ciudades.length == undefined) {	//DF sólo devuelve un obj de ciudad.
					$("<option></option>").attr("value", ciudades.clave_ciudad).html(ciudades.ciudad).appendTo("#sel_ciudades");
					$("#sel_ciudades").trigger('change');	//trigger cities' change event
				} else {							//ciudades.length == 'undefined'
					$.each(ciudades, function(indice, ciudad) {
						if (ciudad.clave_ciudad != '') {
							$("<option></option>").attr("value", ciudad.clave_ciudad).html(ciudad.ciudad).appendTo("#sel_ciudades");
						}
					});
				}
			}
		}, 
		"json"
	);*/
}

function actualizar_colonias(clave_estado, ciudad) {
	$.ajax({
		type: "POST",
		data: { 'estado': clave_estado, 'ciudad': ciudad },
		url: "http://localhost/ecommerce/index.php/direccion_envio/get_colonias",
		dataType: "json",				
		async: false,
		success: function(datos) {
			var colonias = datos.colonias;
			
			$("#sel_colonias").empty();
			$("<option></option>").attr("value", '').html('Selecionar').appendTo("#sel_colonias");
			
			if (colonias != null) {
				$.each(colonias, function(indice, colonia) {
					$("<option></option>").attr("value", colonia.colonia).html(colonia.colonia).appendTo("#sel_colonias");
				});
			}
		},
		error: function(data) {
			alert("error al recuperar colonias: " + data.error);
		}
	});
	/*
	$.post( 'http://localhost/ecommerce/index.php/direccion_envio/get_colonias',
		// when the Web server responds to the request
		{ 'estado': clave_estado, 'ciudad': ciudad },
		function(datos) {
			var colonias = datos.colonias;
			
			$("#sel_colonias").empty();
			$("<option></option>").attr("value", '').html('Selecionar').appendTo("#sel_colonias");
			
			if (colonias != null) {
				$.each(colonias, function(indice, colonia) {
					$("<option></option>").attr("value", colonia.colonia).html(colonia.colonia).appendTo("#sel_colonias");
				});
			}
		}, 
		"json"
	);
	*/
}

function actualizar_cp(clave_estado, ciudad, colonia) {
	$.ajax({
		type: "POST",
		data: { 'estado': clave_estado, 'ciudad': ciudad},
		url: "http://localhost/ecommerce/index.php/direccion_envio/get_colonias",
		dataType: "json",				
		async: false,
		success: function(datos) {
			var colonias = datos.colonias;
			
			$.each(colonias, function(indice, col) {
				if (colonia == col.colonia)
					$("#txt_cp").val(col.codigo_postal);
					//$("<option></option>").attr("value", colonia.colonia).html(colonia.colonia).appendTo("#sel_colonias");
			});
		},
		error: function(data) {
			alert("error al recuperar colonias para actualizar el CP: " + data.error);
		}
	});
	/*
	$.post( 'http://localhost/ecommerce/index.php/direccion_envio/get_colonias',
		// when the Web server responds to the request
		{ 'estado': clave_estado, 'ciudad': ciudad},
		function(datos) {
			var colonias = datos.colonias;
			
			$.each(colonias, function(indice, col) {
				if (colonia == col.colonia)
					$("#txt_cp").val(col.codigo_postal);
					//$("<option></option>").attr("value", colonia.colonia).html(colonia.colonia).appendTo("#sel_colonias");
			});
		}, 
		"json"
	);
	*/
}
