<style type="text/css">
	.boton{
		padding: 3px;
		color: #FFFFFF;
		background-color: #E70030;
		border: none;
	}
</style>
<section id="descripcion-proceso">
	<div class="titulo-proceso-img">&nbsp;		
	</div>			
	<div class="titulo-proceso">
		Contenido a la venta	
	</div>
</section>
<div id="pleca-punteada"></div>
<section class="contenedor-gris">
<?php
echo "<table>
	    <tr>
	      <td colspan='2' class='titulo-promo-rojo'>";
echo "	    IDC Online
          </td>
        </tr>
        <tr>
          <td class='label'>Nueva ley de ayuda alimentaría
          </td>
          <td>";
echo "<form name='realizar_pago' action='".site_url('/api/1/7/131/pago')."' method='POST'>
      	  <input type='hidden' name='guidx' value='{2A629162-9A1B-11E1-A5B0-5DF26188709B}' size='70'/>
          <input type='hidden' name='guidz' value='".$this->session->userdata('guidz')."' size='70'/>
          <input type='submit' name='enviar' value='Comprar' class='boton' />	aa		          
      </form>
          </td>
        </tr>
        <tr>
          <td class='label'>
            eXP
          </td>
          <td>";      		
echo "<form name='realizar_pago' action='".site_url('/api/2/8/278/pago')."' method='POST'>
      	  <input type='hidden' name='guidx' value='{119FFA5C-9A1B-11E1-B947-3CF26188709B}' size='70'/>
          <input type='hidden' name='guidz' value='".$this->session->userdata('guidz')."' size='70'/>
          <input type='submit' name='enviar' value='Comprar' class='boton' />		          
      </form>
          </td>
        </tr>
        <tr>
        <td class='label'>
          Servicio de declaraciones y pagos
        </td>
        <td>";	    	  	 
echo "<form name='realizar_pago' action='".site_url('/api/1/7/117/pago')."' method='POST'>
      	  <input type='hidden' name='guidx' value='{2A629162-9A1B-11E1-A5B0-5DF26188709B}' size='70'/>
          <input type='hidden' name='guidz' value='".$this->session->userdata('guidz')."' size='70'/>
          <input type='submit' name='enviar' value='Comprar' class='boton' />
      </form>
        </td>
      </tr>
      <tr>
      	<td colspan='2' class='titulo-promo-rojo' style='padding-top: 30px'>
      	  CNN Expansión
      	</td>
      </tr>
      <tr>
        <td class='label'>
          Noviembre 2010
        </td>
        <td>";	    	  	 
echo "<form name='realizar_pago' action='".site_url('/api/2/8/266/pago')."' method='POST'>
      	  <input type='hidden' name='guidx' value='{119FFA5C-9A1B-11E1-B947-3CF26188709B}' size='70'/>
          <input type='hidden' name='guidz' value='".$this->session->userdata('guidz')."' size='70'/>
          <input type='submit' name='enviar' value='Comprar' class='boton' />
      </form>
        </td>
      </tr>
      <tr>
        <td class='label'>
          EXPANSION 1 de Abril 2011
        </td>
        <td>";	    	  	 
echo "<form name='realizar_pago' action='".site_url('/api/2/8/267/pago')."' method='POST'>
      	  <input type='hidden' name='guidx' value='{119FFA5C-9A1B-11E1-B947-3CF26188709B}' size='70'/>
          <input type='hidden' name='guidz' value='".$this->session->userdata('guidz')."' size='70'/>
          <input type='submit' name='enviar' value='Comprar' class='boton' />
      </form>
        </td>
      </tr>
    </table>";
	  
	    /*
		 echo "<p>Enviar clave del articulo 54e3</p>"; 
		 echo "<form name='realizar_pago' action='".site_url('/api/54e3')."' method='POST'>
		      	  <input type='text' name='guidx' value='{2A629162-9A1B-11E1-A5B0-5DF26188709B}' size='70'/>			          
		          <input type='submit' name='enviar3' value='Enviar3' />
	          </form>";
	          */
	     echo "<p>Enviar clave del articulo 154e3085e402abd</p>"; 
		 echo "<form name='realizar_pago' action='".site_url('/api/154e3085e402abd')."' method='POST'>
		      	  <input type='text' name='guidx' value='{2A629162-9A1B-11E1-A5B0-5DF26188709B}' size='70'/>			          
		          <input type='submit' name='enviar4' value='Enviar4' />
	          </form>";
	          /*
	     echo "<p>Enviar clave del articulo 114e307e43a65a   !No existe!</p>"; 
		 echo "<form name='realizar_pago' action='".site_url('/api/114e307e43a65a')."' method='POST'>
		      	  <input type='text' name='guidx' value='{2A629162-9A1B-11E1-A5B0-5DF26188709B}' size='70'/>			          
		          <input type='submit' name='enviar5' value='Enviar5' />
	          </form>";*/         
?>
</section>

