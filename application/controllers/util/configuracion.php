<?php

//Mensaje para el correo con depósito bancario
	$cadCorreoCIE = " Usted eligió la Forma de Pago en Sucursal, es necesario que envíe por fax al (55) 9177-41-08 la ficha de depósito del sistema Bancomer CIE, Convenio <b>0582060</b>, a nombre de Expansión, S.A. de C.V. <br />";
	$cadCorreoCIE += " En referencia anote el número de orden <b>" .$id_compra. "</b> para poder identificar su pago.<br />";
    $cadCorreoCIE += " <a href='https://www.e-cash.com.mx/echeques/EchqRecibo.asp?convenio=0582060&empresa=EXPANSI%D3N%2C+S%2EA%2E+DE+C%2EV%2E&referencia=" .$id_compra. "&concepto=" .$clave_promocion. "&importe=" .$importe. "&fs=s' target='_blank' > <b><u>Ejemplo de llenado de Ficha</u></b></a> <br /><br />";
    $cadCorreoCIE += " <b><font color='red'>IMPORTANTE:</font></b> Haga click en la siguiente liga o botón, para realizar la transferencia a través de Bancomer.com <br />";
    $cadCorreoCIE += "<a href=https://www.bancomer.com/cheque/echeques/default.asp?numeroConvenio=0582060&empresa=EXPANSI%D3N%2C+S%2EA%2E+DE+C%2EV%2E&referencia=" .$id_compra. "&concepto=" .$clave_promocion. "&importe=" .$importe. "&fs=s' target='_blank' > <u>";
    $cadCorreoCIE += "https://www.bancomer.com/cheque/echeques/default.asp?numeroConvenio=0582060&empresa=EXPANSI%D3N%2C+S%2EA%2E+DE+C%2EV%2E&referencia=" .$id_compra. "&concepto=" .$clave_promocion. "&importe=" .$importe. "&fs=s </u></a><br /><br />";


?>