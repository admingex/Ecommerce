<?php 
if($usuarios->num_rows()!=0){
	echo "<div style='padding: 5px 0px 5px 0px; color: #E70030; font-size: 12px'>Se encontraron ".$usuarios->num_rows()." registros </div>";
?>
<table width="100%" cellpadding="0" cellspacing="0">
	<thead>
		<tr>
			<th>			
				Usuario				
			</th>		
			<th>
				Correo Electronico
			</th>		
			<th>
				Fecha de Registro
			</th>	
		</tr>
	</thead>	
	<tbody class="contenedor-gris">	
<?php
	foreach($usuarios->result_array() as $usuario){
		echo "<tr>
			    <td class='item-lista borde-derecho'>".$usuario['salutation']."&nbsp;".$usuario['fname']."&nbsp;".$usuario['lname']."</td>
			    <td class='item-lista borde-derecho'>".$usuario['email']."</td>
			    <td class='item-lista borde-derecho'>".$usuario['fecha_registroDt']."</td>
			  </tr>";
	}
?>	
	</tbody>
</table>
<?php 	
}
else{
?>
<p>No existen datos en esta fecha</p>
<?php	
}
?>	

</section>