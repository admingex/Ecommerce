<section id="descripcion-proceso">
	<div class="titulo-proceso-img">&nbsp;		
	</div>			
	<div class="titulo-proceso">
		Acceso a Reportes	
	</div>
</section>
<div id="pleca-punteada"></div>
<section class="contenedor-gris">
<form name='acceso_restringido' action="<?php echo site_url('reporte') ?>" method='post'>
	<table>
	<tr>
		<td class="label">
			Usuario:
		</td>
		<td>
			<input type='text' name='user' value='' />		
		</td>	
	</tr>
	<tr>
		<td class="label">
			Contrase&ntilde;a:	
		</td>
		<td>
			<input type='password' name='pass' value='' />	
		</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td><input type='submit' name='Ingreso' value='Ingresar' /></td>
	</tr> 			          
    </table>
 </form>
</section>