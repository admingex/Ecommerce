/**
 * @author harteaga956
 */

$(document).ready(function() {
	//alert('hola mundo ecommerce GEx!');
	$("#btn_cp").ajaxError(function() {
	//	alert('Error Handler invoked when an error ocurs!');		//Ok
	});
	//cargar el catálogo de estados
	/* 
	$.getJSON("http://localhost/ecommerce/index.php/direccion_envio/get_estados",
		function(data) {
			$.each(data.estados, function(indice, estado) {
				$("<option></option>").attr("value", estado.clave_estado).html(estado.estado).appendTo("#sel_estados");
			});
		}
	);
	*/
		
	/*Ocultar campos abiertos de estado, ciudad, colonia*/
	$('#div_otro_pais').hide();
	
	
	//onChange:
	$('#sel_pais').change(function() {
		/*hacer un toggle si es necesario*/
		var es_mx = false; 
		$.getJSON("http://localhost/ecommerce/index.php/direccion_envio/es_mexico/" + $(this).val(),
			function(data) {			
				if (!data.result) {	//no es México
					$('#div_mexico').hide();
					$('#div_otro_pais').show();
				} else {
					$('#div_mexico').show();
					$('#div_otro_pais').hide();
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
		if (!cp) {
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
				if (typeof data.sepomex != null)	{	//si enecontró algun cp válido
					//alert('data is ok, tipo: ' + typeof(data));
					var sepomex = data.sepomex;
					var codigo_postal = sepomex.codigo_postal;
					var clave_estado = sepomex.clave_estado;
					
					var estado = sepomex.estado;
					var ciudad = sepomex.ciudad;
											
					$("#resultado").text('estado: ' + sepomex.estado);
											
					$("#sel_estados").val(clave_estado);
					
					//catálogo estados
					/*
					$.getJSON("http://localhost/ecommerce/index.php/direccion_envio/get_estados", function(data) {
						$.each(data.estados, function(indice, estado) {
							$("<option></option>").attr("value", estado.clave_estado).html(estado.estado).appendTo("#sel_estados");
						});
					})
					.complete(function() { 
						//alert("Estado: " + estado + ", ciudad: " + ciudad + ", cp: " + codigo_postal); 
					});	
					*/
					
					//carga del catálogo ciudades y selección
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
							/*
							var ciudades = datos.ciudades;
							$.each(datos.ciudades, function(indice, ciudad) {
								if (ciudad.clave_ciudad != '') {
									$("<option></option>").attr("value", ciudad.clave_ciudad).html(ciudad.ciudad).appendTo("#sel_ciudades");
								}
							});
							*/
							//select choosen city
							$("#sel_ciudades").val(ciudad);
							//trigger ciudades change 
							$("#sel_ciudades").trigger('change');
						}, 
						"json"
					);
					/*
					$.ajax({
						type: "POST",
						data: {'codigo_postal' : cp},
						url: "http://localhost/ecommerce/index.php/direccion_envio/get_info_sepomex",
						dataType: "json",				
						async: false,
						success: function(data) {
						}
					});
					*/
					
					//$("#sel_ciudades [value='" + ciudad + "']").attr('selected', 'true');
					
				}
				//var ciudad 
				$("#estados").text(data.msg);
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


	//submit validation
	$("form[id='form_direccion_envio']").submit(function(event) {
		//event.preventDefault();
	});
});

function actualizar_ciudades(clave_estado) {
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
					$("<option></option>").attr("value", '').html('Selecionar').appendTo("#sel_ciudades");
					$.each(ciudades, function(indice, ciudad) {
						if (ciudad.clave_ciudad != '') {
						
							$("<option></option>").attr("value", ciudad.clave_ciudad).html(ciudad.ciudad).appendTo("#sel_ciudades");
						}
					});
				}
			}
		}, 
		"json"
	);
}

function actualizar_colonias(clave_estado, ciudad) {
	$.post( 'http://localhost/ecommerce/index.php/direccion_envio/get_colonias',
		// when the Web server responds to the request
		{ 'estado': clave_estado, 'ciudad': ciudad},
		function(datos) {
			var colonias = datos.colonias;
			
			$("#sel_colonias").empty();
			$("<option></option>").attr("value", '').html('Selecionar').appendTo("#sel_colonias");
			
			if (colonias != null) {
				$.each(colonias, function(indice, colonia) {
					$("<option></option>").attr("value", colonia.colonia).html(colonia.colonia).appendTo("#sel_colonias");
				});
			}
			//seleecionar la ciudad seleccionada
			//$("#sel_ciudades").val(ciudad);
		}, 
		"json"
	);
}

function actualizar_cp(clave_estado, ciudad, colonia) {
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
}

function carga_detalles_sepomex() {
	/*Para la edición*/
	/*
	var form_edicion = $('#form_editar_direccion_envio');
	
	if (form_edicion.length != 0) {
		//$('#btn_cp').click();
		//od.
		//carga_detalles_sepomex();
	}*/
	var edo = $('#sel_estados');
	var cp = $('#txt_cp');
	$('#btn_cp').trigger('click');
	
	if (edo.val() != '') {
		//$('#sel_estados').trigger('change');		
	}
}
