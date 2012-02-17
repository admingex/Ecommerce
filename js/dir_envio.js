/**
 * @author harteaga956
 */

$(document).ready(function() {
	//alert('hola mundo ecommerce GEx!');
	
	$("#btn_cp").click(function() {
		var text_selected = $("#sel_pais option:selected").text();
		var val_selected = $("#sel_pais option:selected").val();
		
		var cp = $("#txt_cp").val();
		
		//alert('valor: ' + val_selected + ', text:' + text_selected);
		//alert('cp: ' +  cp);
		
		$.ajax({
			type: "POST",
			//data: "{'codigo_postal':'55749'}",
			//async: false,
			//url: "https://cctc.gee.com.mx/ServicioWebCCTC/wcf_cms_cctc.svc/ObtenerEstadoJson",
			url: "http://cctc.gee.com.mx/ServicioWebCCTC/wcf_cms_cctc.svc/ObtenerEstadoJson",
			crossDomain: true,
			contentType: "application/json; charset=utf-8",
			//contentType: "text/xml",
			dataType: "jsonp",
			//mimeType: "text/xml",	
			//beforesend: alert(cp),
			//data: "{}",	
			success: function(data) {
				alert("success: " + data.d);
			},
			error:function(data) {
				alert("error: " + data);
			},
			fail: function(data) {
				alert("fail: " + data.d);
			},
			//async: false,
			cache: false
		});	
	});
	
	//submit
	$("form[id='login']").submit(function(event) {
		event.preventDefault();
		
	});

});