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
			url: "http://localhost/ecommerce/index.php/direccion_envio/get_estados/",
			crossDomain: true,
			contentType: "application/json; charset=utf-8",
			dataType: "json",
			success: function(data) {
				alert("success: " + data.msg);
				
			},
			error:function(data) {
				alert("error: " + data.msg);
			},
			fail: function(data) {
				alert("fail: " + data.msg);
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