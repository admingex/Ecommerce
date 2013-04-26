<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include('dtos/Tipos_Tarjetas.php');
include('util/Pago_Express.php');
include('api.php');

class Orden_Compra extends CI_Controller {
	#Miembros públicos
	var $title = 'Verifica tu orden';
	var $subtitle = 'Verifica tu orden';
	var $registro_errores = array();				//validación para los errores
	
	#Miembros privados
	private $id_cliente;
	private $api;
	private $detalle_promociones;	//contendrá la información de las promociones que se van a cobrar, a través del API
	
	##CONSTANTES
	const Tipo_AMEX = 1;
	const HASH_PAGOS = "P3lux33n3l357ux3";	//hash que se utiliza vara validar la información del lado de CCTC
	
	public static $ESTATUS_COMPRA = array (
		"SOLICITUD_CCTC"			=> 1,
		"RESPUESTA_CCTC"			=> 2,
		"REGISTRO_PAGO_ECOMMERCE"	=> 3,
		"PAGO_DEPOSITO_BANCARIO" 	=> 4,
		"ENVIO_CORREO"				=> 5
	);
	
	public static $TIPO_PAGO = array (
		"Prosa"				=> 1,
		"American_Express"	=> 2,
		"Deposito_Bancario"	=> 3,
		"Otro"				=> 4
	);
	
	public static $TIPO_DIR = array (
		"RESIDENCE"	=> 0,
		"BUSINESS"	=> 1,
		"OTHER"		=> 2
	);
	
	function __construct()
    {
        // Call the Model constructor
        parent::__construct();
		
		$this->output->nocache();
		
		//si no hay sesión
		//manda al usuario a la... página de login
		$this->redirect_cliente_invalido('id_cliente', 'login');
		
		//cargar el modelo en el constructor
		$this->load->model('orden_compra_model', 'orden_compra_model', true);
		$this->load->model('forma_pago_model', 'forma_pago_model', true);
		$this->load->model('direccion_envio_model', 'direccion_envio_model', true);
		$this->load->model('direccion_facturacion_model', 'direccion_facturacion_model', true);
		
		//si la sesión se acaba de crear, toma el valor inicializar el id del cliente de la session creada en el login/registro
		$this->id_cliente = $this->session->userdata('id_cliente');
		
		//traer del controlador api las funciones encrypt y decrypt
		$this->api = new Api();	
		
		//carga el helper para la funcion mdate() para obtener la fecha
		$this->load->helper('date');
		
		//recuperar las promociones
		//si hay promociones, al menos una...
		if ($this->session->userdata('promociones') && $this->session->userdata('promocion')) {
			$this->detalle_promociones = $this->api->obtiene_articulos_y_promociones();
			/*echo "<pre>";
			print_r($this->detalle_promociones);
			echo "</pre>";*/
		}
		
    }

	public function index()
	{
		if ($_POST) {
			if (array_key_exists('direccion_selecionada', $_POST)) {
				$cte = $this->id_cliente;
				$rs = $this->session->userdata('razon_social');
				
				$this->session->set_userdata('direccion_f', $_POST['direccion_selecionada']);
				$ds = $this->session->userdata('direccion_f');
				
				if ($rs != "" && $ds != "") {
					$rbr = $this->direccion_facturacion_model->busca_relacion($cte, $rs, $ds);
					
					if ($rbr->num_rows() == 0) {
						$this->load->helper('date');
						$fecha = mdate('%Y/%m/%d',time());
						$data_dir = array (
                   			'id_clienteIn'  	=> $cte,
                   			'id_consecutivoSi' 	=> $ds,
                   			'id_razonSocialIn' 	=> $rs,
                   			'fecha_registroDt' 	=> $fecha
               			);
						
						$this->direccion_facturacion_model->insertar_rs_direccion($data_dir);
						$this->session->set_userdata('requiere_factura', 'si');
					}
				}
			} else { //si no viene de dirección de facturación
				$id_cliente = $this->id_cliente;
				$rs = $this->session->userdata('razon_social');
				$rdf = $this->direccion_facturacion_model->obtiene_rs_dir($id_cliente, $rs);
			
				foreach ($rdf->result_array() as $dire) {
					$this->session->set_userdata('direccion_f',$dire['id_consecutivoSi']);
				}
			}
			
		} else if ($this->session->userdata('id_dir')) {
			//si viene la información de la dirección de facturación en el POST y ya existía alguna dirección en sesión, se actualiza
			$id_dir = $this->session->userdata('id_dir');
			$this->session->set_userdata('direccion_f',$id_dir);								
		}
		
		//cargar vista del resumen de la compra
		$this->resumen();
	}
	
	/**
	 * Recupera y despliega la información de la orden de compra en curso.
	 */
	public function resumen($msg = '', $redirect = TRUE) 
	{
		$data['title'] = $this->title;
		$data['subtitle'] = $this->subtitle;
		$data['mensaje'] = $msg;
		$data['redirect'] = $redirect;
		
		/*echo "<pre>";
		print_r($this->session->all_userdata());
		print_r($this->detalle_promociones);
		echo "</pre>";*/
		
		//Validación del lado del cliente
		$script_file = "<script type='text/javascript' src='". base_url() ."js/orden_compra.js'> </script>";
		$data['script'] = $script_file;
		
		//gestión de la dirección de envío	TRUE / FALSE
		$data['requiere_envio'] = $this->session->userdata('requiere_envio');
		
		/*Recuperar la info gral. de la orden*/
		$id_cliente = $this->id_cliente;

		//recuperar la tarjeta/forma de pago
		$tarjeta = $this->session->userdata('tarjeta');
		
		//revisar si hay depósito bancario como forma de pago
		if ($this->session->userdata('deposito')) {
			$data['deposito'] = TRUE;
			
		} else if (!empty($tarjeta)) {		
			//no se guarda la tarjeta en la BD, la información sólo está en sesión
			if (is_array($this->session->userdata('tarjeta'))) {
				$tarjeta = (object)$tarjeta;
				$detalle_tarjeta = (object)$tarjeta->tc;
				
				$data['tc'] = $detalle_tarjeta;
				
				//if ($detalle_tarjeta->id_tipo_tarjetaSi == 1) { //la tarjeta es AMERICAN EXPRESS
				if ($detalle_tarjeta->id_tipo_tarjetaSi == self::Tipo_AMEX) { //la tarjeta es AMERICAN EXPRESS
					$data['amex'] = (object)$tarjeta->amex;
					//en este caso se consultará la info del WS
				}

			} else if (is_integer((int)$this->session->userdata('tarjeta'))) {	
				//la tarjeta está guardada en la BD 
				$consecutivo = $this->session->userdata('tarjeta');		//consecutivo de la tarjeta para el cliente
				//trae la información de la TC de l BD
				$detalle_tarjeta = $this->forma_pago_model->detalle_tarjeta($consecutivo, $id_cliente);
				$data['tc'] = $detalle_tarjeta;
			
				//if ($detalle_tarjeta->id_tipo_tarjetaSi == 1) { //la tarjeta es AMERICAN EXPRESS
				if ($detalle_tarjeta->id_tipo_tarjetaSi == self::Tipo_AMEX) { //la tarjeta es AMERICAN EXPRESS
					//$data['amex'] = $this->detalle_tarjeta_CCTC($id_cliente, $consecutivo);			//antigua interfase con CCTC
					$data['amex'] = $this->obtener_detalle_interfase_CCTC($id_cliente, $consecutivo);	//cambio de interfase con CCTC
					//en este caso se consultará la info del WS
				}
				
			}
			
		} else if (empty($tarjeta)) {	// no hay forma de pago con TC
			//recalcular el flujo del proceso de pago
			$destino = $this->obtener_destino();	
			redirect($destino, 'location', 302);
		}
		
		//dir_envío
		$dir_envio = $this->session->userdata('dir_envio');
		$mas_direcciones = $this->session->userdata('dse');		//"dse" => direcciones de envio
		
		/**
		 * Ajuste de direcciones múltiples:
		 * 	todas las promociones tendrán asociadas una dirección antes de pasar a mostrar el resumen de la orden, sin importar que sólo sea la misma para todas. 
		 * 	Si existe en sesión "dse" ("dse" => direcciones de envio), se recupera la información de las direcciones asociadas.
		 * 	Si no existe en sesión "dse" ("dse" => direcciones de envio), se crea aquí.
		 */
		
		$detalles_direcciones = array();		//Información de las direcciones que se mostrará
		
		//existe "dse", se recupera la información de las direcciones
		if ($mas_direcciones) {
			//$detalles_direcciones = array();		//Información de las direcciones que se mostrará	
			foreach ($mas_direcciones as $id_promocion => $id_direccion_env) {
				$detalles_direcciones[$id_promocion] = $this->direccion_envio_model->detalle_direccion($id_direccion_env, $id_cliente);
			}
			//se pasa el arreglo con el detalle de las direcciones a la vista
			$data['direcciones'] = $detalles_direcciones;
			
		} else if (!empty($dir_envio)) {	//si no existe, se crea "dse" y se recupera la información de la dirección general para todas las promociones
			//recuperar las promociones para saber cuál requiere envío
			$detalle_promociones = NULL;
			if ($this->detalle_promociones) {
				$detalle_promociones = $this->detalle_promociones;
			}

			//si existe una dirección de envío inicial y la información detallada de las promociones...
			if (is_integer((int)$dir_envio) && $detalle_promociones) {
				//lo que se pasará a la vista para las direcciones de las promociones
				$promos_dirs = array();					//arreglo (id_promocion => id_direccion)
				
				//se crea "dse" ("dse" => direcciones de envio)
				$dir_general = $dir_envio;	//echo "dir_gral. " . $dir_general . "<br/>";
				
				//$detalles_direcciones = array();		//información de las direcciones que se mostrará, se pasará a la vista en el data
				$detalle_dir_gral = $this->direccion_envio_model->detalle_direccion($dir_general, $id_cliente);	//detalle de la información para todas las promociones
				
				//colocar en el arreglo de direcciones todas las promociones que puedan tener envío
				foreach ($detalle_promociones['descripciones_promocion'] as $p) {
					//si requiere dirección de envío se mete al arreglo
					if ($p['promocion']->requiere_envio) {
						$id_promo = $p['promocion']->id_promocionIn;
						$promos_dirs[$id_promo] = $dir_general;
						$detalles_direcciones[$id_promo] = $detalle_dir_gral;
					}
				}
				//colocar en el arreglo de direcciones el id de la promoción que se quiere asociar con alguna dirección antes de ponerlo en sesión
				$this->session->set_userdata('dse', $promos_dirs);

				$data['direcciones'] = $detalles_direcciones;
				$data['dse'] = $promos_dirs;
			}
		}
		
		//razón social para la dirección de facturación "rs_facturación"
		$consecutivors = $this->session->userdata('razon_social');
		if (isset($consecutivors)) {
			$detalle_facturacion = $this->direccion_facturacion_model->obtener_rs($consecutivors);
			$data['dir_facturacion'] = $detalle_facturacion;
		}
		
		//direccion de facturación
		$consecutivo_dir = $this->session->userdata('direccion_f');
		if (isset($consecutivo_dir)) {
			$detalle_direccion = $this->direccion_facturacion_model->obtener_direccion($id_cliente, $consecutivo_dir);
			$data['direccion_f']=$detalle_direccion;
		}
		
		//Requiere factura
		$data['requiere_factura'] = $this->session->userdata('requiere_factura');
		
		//revisar por si acaso hay errores al invocar esta método
		if ($_POST && $this->registro_errores) {
			$data['reg_errores'] = $this->registro_errores;
		}
		
		//colocar en sesión que ya pasó por el resumen de la orden de compra
		$this->id_cliente = $this->session->set_userdata('paso_orden_compra', TRUE);
		
		//cargar vista
		$this->cargar_vista('', 'orden_compra', $data);
	}

	/**
	 * Realiza el pago a través de CCTC
	 */
	public function checkout() {
		$data['title'] = "Resultado de la petición de cobro";
		$data['subtitle'] = "Resultado de la petición de cobro";	
		$data['datos_login'] = '';
		
		/*Realizar el pago en CCTC*/
		if ($_POST) {
			$orden_info = array();		
			$orden_info = $this->get_datos_orden();
						
			if (empty($this->registro_errores)) {
								
				/*Recuperar la info gral. de la orden*/
				$id_cliente = $this->id_cliente;
				
				//verificar que exista la información de los artículos y promociones de la compra
				$detalle_promociones = NULL;
				if ($this->detalle_promociones) {
					$detalle_promociones = $this->detalle_promociones;
				}
				
				//forma pago
				$consecutivo 	= $this->session->userdata('tarjeta') ? $this->session->userdata('tarjeta') : $this->session->userdata('deposito');
				//promociones que se van a comprar, vienen en el detalle de la promoción en un array llamado "ids_promociones"
				$ids_promociones = $detalle_promociones['ids_promociones'];
				//$ids_promociones = $this->session->userdata('promocion')->id_promocionIn;
				
				//direcciones de envío que utilizaremos para la compra
				$ids_direcciones_envio = array();
				$ids_direcciones_envio = $this->session->userdata('dse');
				
				//detalles para las direcciones asociadas
				$detalles_direcciones = array();
				if ($ids_direcciones_envio) {
					foreach ($ids_direcciones_envio as $id_promocion => $id_direccion_env) {
						$detalles_direcciones[$id_promocion] = $this->direccion_envio_model->detalle_direccion($id_direccion_env, $id_cliente);
					}
				}
				
				//$digito = (isset($_POST['txt_codigo'])) ? $_POST['txt_codigo'] : 0;
				$digito = (!empty($orden_info)) ? $orden_info['cvv'] : 0;
				
				//encriptación del dígito verificador...
				$digito_rsa = $this->encriptar_rsa_texto($digito);
				
				//revisar que se haya encriptado correctamente
				if (!$digito_rsa) {
					//si regresa con FALSE, hubo un error en la encriptación, cancelar la compra
					redirect('mensaje/'.md5(7), 'refresh');		//error al encriptar la información
					
					//la otra es que se quede en el resumen de la compra y el cliente lo intente nuevamente
					redirect('orden_compra/resumen', 'refresh');
				}
				### Ajuste para saber qué tipo de tarjeta se utiliza en base al primer dígito del número de la tarjeta 
				/**
				 * Sólo hay que insertar el campo en la tabla "CMS_RelCompraPago" al momento de solicitar el cobro en el registro de la compra.
				 */
				$primer_digito = 0;	//depósito bancario en principio
				
				
				// informaciòn de la Orden para pedir que se cobre en CCTC
				$informacion_orden = new stdClass;
				//inicialización de valores de los miembros del objeto
				$informacion_orden->id_clienteIn = $id_cliente;				//id del cliente
				$informacion_orden->consecutivo_cmsSi = $consecutivo;		//consecutivo de la tarjeta
				$informacion_orden->digito = $digito_rsa;					//dígito verificador encriptado
				$informacion_orden->id_compraIn = 0;						//id de la compra, inicialmente 0
				$informacion_orden->id_promocionIn = 0;						//id de la promocion, inicialmente 0
				
				$moneda = $detalle_promociones['moneda'];					//el identificador del tipo de moneda utilizado para el cobro
				$informacion_orden->currency = $moneda;
											
				//recuperar el total de la compra con ayuda del API
				$monto = $detalle_promociones['total_pagar'] + $detalle_promociones['total_iva'];
				$informacion_orden->monto = $monto;						//monto total a que se cobrará a través de la plataforma
				
				//el hash se debe calcular y colocar en el objeto que se pasará a CCTC
				$informacion_orden->hash = md5($digito_rsa . $id_cliente . self::HASH_PAGOS . $monto . $moneda);		//hash que se utiliza vara validar la información del lado de CCTC
				
				//renovación automática...
				$informacion_orden->ra = $detalle_promociones['lleva_ra'];	//del detalle de las promociones
				
				#### Comienza el proceso de cobro / pago
				
				$tipo_pago = self::$TIPO_PAGO['Otro'];	//ninguno válido al inicio

								
				#### Configuración de la forma de pago y solicitud de cobro a CCTC
				
				#### pago con Depósito Bancario
				if ($this->session->userdata('deposito')) {			
					//el usuario de ecommerce será el que se registre para el cobro con esta forma de pago					
					$id_cliente = $this->id_cliente;
					
					//para el registro de la compra en ecommerce
					$tipo_pago = self::$TIPO_PAGO['Deposito_Bancario'];
					//para que se muestre el mensaje de pago con deposito bancario
					$data['deposito'] = TRUE;
					//$id_forma_pago = 0;
										
					//echo " tipo pago depósito: " . $tipo_pago;
					
					///////si ya está registrada la compra de algún intento anterioir...
					if ($this->session->userdata('id_compra')) {	
						$id_compra = $this->session->userdata('id_compra');
					} else {	///////registrar la orden de compra y el detalle del pago con depósito
						$id_compra = $this->registrar_orden_compra($id_cliente, $ids_promociones, $ids_direcciones_envio, $tipo_pago, $primer_digito);	
					}
					
					
					## para la prueba del correo
					/*$id_compra = 1;	//para el test
					$simple_result = NULL;
					$simple_result->codigo_autorizacion = 12321;*/
					## end pruebas
					
					if ($id_compra) {
						$mensaje = "<html>
									  <body>
									  	   <div>
									  	   Hola ".$this->session->userdata('username').",<br />
										   Hemos recibido la siguiente orden de compra en pagos.grupoexpansion.mx, con solicitud de pago por depósito bancario.<br /> 
										   Para completar tu compra, sigue las instrucciones que aparecen abajo para realizar tu pago.
										   <br />
										   <br />
										   <table cellspacing='0' style=' border: solid; border-width: 1px; border-color: #E70030; width: 600px'>
										   	   <thead style='background-color: #E70030; color: #FFF'>
										   	       <tr>
										   	           <th colspan='4' align='left'>
										   	               <b>Resumen de orden:</b>
										   	           </th>
										   	       </tr>
										   	   </thead>
										   	   <tbody>
										   	       <tr>
										   	           <td colspan='4' align='left'><b>Número de orden:</b>&nbsp;&nbsp;".$id_compra."
										   	           </td>										   	           										   	          
										   	       </tr>
										   	       <tr>
										   	       	   <td colspan='4'>
										   	       	       <b>Productos en la orden:</b>	   
										   	       	   </td>
										   	       </tr>";
						### se usará "$detalle_promociones", para mostrar la información de los artículos de la orden
						//IVA inicial de la compra
						$iva_compra = 0.0;
						$iva_message = "";
						$subtotal = 0;
						foreach ($detalle_promociones['descripciones_promocion'] as $promociones) {
					
							//para los artículos de las promos que lleven IVA
							$iva_message  = "";		//en principio no lleva para la promocion
							
							//revisar si se cobra IVA
							if ($promociones['promocion']->iva_promocion > 0) { //($articulo['taxableBi']) {
								$iva_compra += $promociones['promocion']->iva_promocion;	//ya se calcula desde el API para el la promoción
								$iva_message  = "<b>costo m&aacute;s IVA</b>";	//en principio no lleva para la promocion
							}
							
							if (strstr($promociones['promocion']->descripcionVc, '|' )) {
								$mp = explode('|', $promociones['promocion']->descripcionVc);
								$nmp = count($mp);
								if ($nmp == 2) {
									$desc_promo = $mp[0];
								} else if ($nmp == 3) {
									$desc_promo = $mp[1];
								}
							} else {
								$desc_promo = $promociones['promocion']->descripcionVc;
							}
							
							//indicador de que requiere envío
							$promo_requiere_envio = $promociones['promocion']->requiere_envio;
							
							//sacar la descripción que se mostrará de la promoción
							
							$mensaje.= "<tr><td colspan='4'>".$desc_promo."</td></tr>";
							
							foreach ($promociones['articulos'] as $articulo) {
								$mensaje .= 
								"<tr>
									<td colspan='2' class='instrucciones'>";
								if ($articulo['issue_id']) {
									foreach ($detalle_promociones['tipo_productoVc'] as $k => $v) {
										if ($k == $articulo['issue_id']) {
											if (strstr($v, '|' )) {
												$mp = explode('|',$v);
												$nmp = count($mp);
												if ($nmp == 2) {
													$desc_art = $mp[0];
												} else if ($nmp == 3) {
													$desc_art = $mp[1];
												}
											} else {
												$desc_art = $v;
											}
										}
									}
								} else {									
									$desc_art=$articulo['tipo_productoVc']."&nbsp;";								
									foreach($detalle_promociones['articulo_oc'] as $i => $oc){
										if($i == $articulo['oc_id'] ){
											$desc_art.= $oc;	
										}																	
									}
								}
								//medio de entrega del artículo
								$medio_entrega = empty($articulo['medio_entregaVc']) ? "" : $articulo['medio_entregaVc']; 
																
								$mensaje .= "<div>".$desc_art. "&nbsp;<div class='label-promo-rojo'>$iva_message</div></div><br/>";
								
								//direcciones de envío asociadas
								if ($promo_requiere_envio) {
									//id de la promoción que se quiere asociar con otra dirección
									$id_promo = $promociones['promocion']->id_promocionIn;
									
									//detalles de las direcciones
									if (!empty($detalles_direcciones) && array_key_exists($id_promo, $detalles_direcciones)) {
									 	$d = $detalles_direcciones[$id_promo];
									 	
									 	$mensaje .= "Calle " . $d->address1 . ", Número " .$d->address2. " " . (isset($d->address4) ? ", Interior ".$d->address4 : "") . "<br/>";
										$mensaje .= "C.P. " . $d->zip . " " . $d->city . ", ". $d->state . " ". $d->codigo_paisVc . ", Tel. " . $d->phone . "&nbsp;";
									 }
								}
								//sumar al subtotal de la compra
								$subtotal += $promociones['promocion']->subtotal_promocion;
								//precio de la promoción
								$mensaje .=
									"</td>
									<td>&nbsp;</td>
									<td class='instrucciones' align='right'>$" . number_format($articulo['tarifaDc'], 2, '.', ',') . "&nbsp;" . $moneda . "</td>" .
								"</tr>";
							}
						}
													
	   	       			$mensaje.= "<tr>
						   	           <td colspan='2'>&nbsp;
						   	           </td>
						   	           <td align='right'>Sub-total:
						   	           </td>
						   	           <td align='right'>$".number_format($detalle_promociones['total_pagar'], 2, '.', ',')."&nbsp;".$moneda."
						   	           </td>
						   	       </tr>
						   	       <tr>
						   	           <td colspan='2'>&nbsp;
						   	           </td>
						   	           <td align='right'>I.V.A
						   	           </td>
						   	           <td align='right'>$".number_format($detalle_promociones['total_iva'], 2, '.', ',')."&nbsp;".$moneda."
						   	           </td>
						   	       </tr>
						   	       <tr>
						   	           <td colspan='2' width='325px'>&nbsp;
						   	           </td>
						   	           <td align='right' width='180px'><b>Total de la orden</b>
						   	           </td>
						   	           <td align='right' width='95px'><b>$".number_format($detalle_promociones['total_pagar'] + $detalle_promociones['total_iva'], 2, '.', ',')."&nbsp;" . $moneda ."</b>
						   	           </td>
						   	       </tr>										   	       												   
						   	   </tbody>
						   </table>";
						   
							$mensaje.=	   "<table cellspacing='0' style=' border: solid; border-width: 1px; border-color: #E70030; width: 600px'>
										       <thead style='background-color: #E70030; color: #FFF'>
										           <tr>
										               <th colspan='2' align='left'>Envío y facturación
										               </th>
										           </tr>    
										       </thead>
										       <tbody>
											   	   <tr>
											   	       <td valign='top' colspan='2'>
											   	           <b>Requiere factura:</b>&nbsp;".$this->session->userdata('requiere_factura')."<br /><br />";
											   	       
											   	       	   if ($this->session->userdata('requiere_factura') == 'si') {
											   	       	       $mensaje.="<b>Razón social:</b><br />";	
											   	       	       $rs = $this->direccion_facturacion_model->obtener_rs($this->session->userdata('razon_social'));
											   	       	       $mensaje.=$rs->company."<br />";	
											   	       	       $mensaje.=$rs->tax_id_number."<br /><br />";
											   	       	       										   	       	       
											   	       	       $mensaje.="<b>Dirección de facturación:</b><br />";
											   	       	       $df = $this->direccion_facturacion_model->obtener_direccion($id_cliente, $this->session->userdata('direccion_f'));	  										   	       	       
											   	       	       $mensaje.= $df->calle."&nbsp;".$df->num_ext."&nbsp;".(!empty($df->num_int) ? ", Int. ".$df->num_int : "") . "<br/>";
															   $mensaje.= $df->cp."<br/>".
																 		  $df->ciudad."<br/>".
														 		          $df->estado."<br/>".
															 	 		  $df->pais."&nbsp;";
											   	       	   }
											   	       	   
								$mensaje.=			  "</td>
											   	   </tr>
											   </tbody>	   
										   </table>
										   <br />
										   <br />
										   <b>Instrucciones para pago con depósito bancario o transferencia electrónica</b><br />
										   Para completar tu orden, sigue los pasos que enlistamos abajo:<br />
										   1. Realiza un depósito por el total de la orden acudiendo a cualquier sucursal BBVA Bancomer,
										   o realizando una transferencia electrónica, por medio del convenio CIE 57351 o directamente a la cuenta que aparece abajo.<br />
										   Es importante que indiques tu nombre y número telefónico como referencia. La cuenta de depósito es la siguiente:<br />
								           Banco: BBVA Bancomer<br />
										   Beneficiario: Expansión S.A. de C.V.<br />
										   Cuenta CLABE:  012180004465210022<br />
										   Cuenta:  0446521002 <br />
										   Sucursal: 1820<br />
										   Plaza: 001<br /><br /> 

										   2. Una vez realizado el depósito por favor confirma tu pago llamando al teléfono (55) 5061 2413 o escribiendo al correo pagosmercadotecnia@expansion.com.mx. <br />
										   Si confirmas por correo, no olvides indicar tu nombre y el producto que adquieres, y enviar escaneada tu ficha de depósito. <br /><br /> 

										   3. Recibirás un correo electrónico confirmando que tu compra ha sido completada.<br /><br />

										   Si tienes dudas, comunícate con nuestro equipo de Atención a Clientes al  (55) 9177 4342 o al correo atencionaclientes@expansion.com.mx<br /><br />		

										   Gracias por comprar en Grupo Expansión.
									  	   																																																										  	  
									  	   </div>
									  </body>
									  </html>";
						##echo $mensaje;
						##exit;
						//mandar correo al cliente con el formato de arlette para notificarle lo que debe hacer
						$envio_correo = $this->enviar_correo("Notificación de compra con depósito bancario", $mensaje);
						 
						//registrar el estatus de la compra correspondiente a la notificación final, esto es después del proceso nocturno
						$estatus_correo = $this->registrar_estatus_compra($id_compra, (int)$id_cliente, self::$ESTATUS_COMPRA['ENVIO_CORREO']);
						
						//manejo envío correo
						if (!$envio_correo) {	//Error
							redirect('mensaje/'.md5(4), 'refresh');
						}
						
						//Manejo del flujo para el depósito bancario
						$data['url_back'] = $this->datos_urlback("approved", $id_compra);
						$data['moneda'] = $moneda;				//para desplegar la respuesta de cobro
						
						$data['pago_deposito']['id_compra'] = $id_compra;
						//$data['deposito']['importe'] = $id_compra;
						//$data['deposito']['clave_promocion'] = $id_compra;
						 
						//Muestra la pantalla de resultado de cobro
						$this->cargar_vista('', 'orden_compra', $data);
						
						if ($data['url_back']['estatus'] != 0) {
							$this->session->sess_destroy();	
						}						
						
					} else {
						redirect('mensaje/'.md5(2), 'refresh');
					}
					
				} else if (is_array($this->session->userdata('tarjeta'))) {
					//////////// pago con tarjetas no guardada en BD y que está en sesión
				
					$detalle_tarjeta = $this->session->userdata('tarjeta');					
					$tc = $detalle_tarjeta['tc'];
					$data['tc']= (object)$detalle_tarjeta['tc'];
					$tc = (array)$tc;
					
					//echo var_dump($tc);
					$tc_soap = new stdClass;
					$tc_soap->id_clienteIn = $tc['id_clienteIn'];
					$tc_soap->consecutivo_cmsSi = $tc['id_TCSi'];
					$tc_soap->id_tipo_tarjeta = $tc['id_tipo_tarjetaSi'];
					$tc_soap->nombre_titular = $tc['nombre_titularVc'];
					$tc_soap->apellidoP_titular = $tc['apellidoP_titularVc'];
					$tc_soap->apellidoM_titular = $tc['apellidoM_titularVc'];
					$tc_soap->numero = $tc['terminacion_tarjetaVc'];
					$tc_soap->mes_expiracion = $tc['mes_expiracionVc'];
					$tc_soap->anio_expiracion = $tc['anio_expiracionVc'];
					$tc_soap->renovacion_automatica = 1;
					
					//para saber  qué tipo de tarjeta es
					$primer_digito = substr($tc_soap->numero, 0, 1);	//sólo el primero
					
					//consecutivo de la información del pago es 1 para que pase el cobro
					$informacion_orden->consecutivo_cmsSi = $tc['id_TCSi'];		//Debe ser 0
					
					//si es Visa o Master card
					$tipo_pago = self::$TIPO_PAGO['Prosa'];	
					
					$amex_soap = NULL;
					
					//if ($detalle_tarjeta['tc']['id_tipo_tarjetaSi'] == 1) { //es AMERICAN EXPRESS
					if ($detalle_tarjeta['tc']['id_tipo_tarjetaSi'] == self::Tipo_AMEX) { //es AMERICAN EXPRESS
						$amex = $detalle_tarjeta['amex'];
						if (isset($amex)) {
							$amex_soap = new stdClass;
							$amex_soap->id_clienteIn = $amex['id_clienteIn'];
							$amex_soap->consecutivo_cmsSi = $amex['id_TCSi'];
							$amex_soap->nombre =$amex['nombre_titularVc'];
							$amex_soap->apellido_paterno = $amex['apellidoP_titularVc'];
							$amex_soap->apellido_materno = $amex['apellidoM_titularVc'];
							$amex_soap->pais = $amex['pais'];
							$amex_soap->codigo_postal = $amex['codigo_postal'];
							$amex_soap->calle = $amex['calle'];
							$amex_soap->ciudad = $amex['ciudad'];
							$amex_soap->estado = $amex['estado'];
							$amex_soap->mail = $amex['mail'];
							$amex_soap->telefono = $amex['telefono'];
						}
						
						//si es AMEX
						$tipo_pago = self::$TIPO_PAGO['American_Express'];
					}
										
					//intentamos el Pago con pasando los objetos a CCTC //
					try {
						
						//si la compra no se ha generado y guardado en sesión en algún intento previo de pago
						if ($this->session->userdata('id_compra')) {
							$id_compra = $this->session->userdata('id_compra');
						} else {	//////registrar la orden de compra y el detalle del pago con Tc "no guardada"
							$id_compra = $this->registrar_orden_compra($id_cliente, $ids_promociones, $ids_direcciones_envio, $tipo_pago, $primer_digito);	
						}
						
						if (!$id_compra) {			//Si falla el registro inicial de la compra en CCTC
							redirect('mensaje/'.md5(3), 'refresh');
						}
						
						//pasar el id de la compra para el pago
						$informacion_orden->id_compraIn = $id_compra;
						
						##Test
						/*echo "info_orden<pre>";
						print_r($informacion_orden);
						echo "</pre>";
						exit();
						*/
						
						//petición de pago a través de la interfase, el resultado ya es un objeto
						$simple_result = $this->solicitar_pago_CCTC_objetos($tc_soap, $amex_soap, $informacion_orden);
						
						## Test Result
						//echo "simple_result<pre>";
						//print_r($simple_result);
						//echo "</pre>";
						//exit;
						
						//Registro del estatus de la respuesta de CCTC
						$this->registrar_estatus_compra($id_compra, (int)$id_cliente, self::$ESTATUS_COMPRA['RESPUESTA_CCTC']);
						
						//Registro de la respuesta de CCTC de la compra en ecommerce
						$info_detalle_pago_tc = array('id_compraIn'=> $id_compra, 'id_clienteIn' => $id_cliente, 'id_TCSi' => $tc['id_TCSi'], 
														'id_transaccionBi' => $simple_result->id_transaccionNu, 'respuesta_bancoVc' => $simple_result->respuesta_banco,
														'codigo_autorizacionVc' => $simple_result->codigo_autorizacion, 'mensaje' => $simple_result->mensaje);
														
						//Registro de la respuesta del pago en ecommerce
						$this->registrar_detalle_pago_tc($info_detalle_pago_tc);
						//actualizar el id de la tarjeta, el primer_digito y el tipo_pago en "CMS_RelCompraPago", es como un trigger
						$this->actualizar_info_compra_tc($id_cliente, $id_compra, NULL, $primer_digito, $tipo_pago);
						
						$this->registrar_estatus_compra($id_compra, (int)$id_cliente, self::$ESTATUS_COMPRA['REGISTRO_PAGO_ECOMMERCE']);
						
						/*
						## para la prueba del correo
						$id_compra = 0;	//para el test
						$simple_result = NULL;
						$simple_result->codigo_autorizacion = 12321;
						## end pruebas*/
						
						//envío del correo
						$mensaje = "<html>
									  <body>
									  	   <div>
									  	   Hola ".$this->session->userdata('username').",<br />
										   Gracias por tu orden en pagos.grupoexpansion.mx
										   <br />
										   <br />
										   <table cellspacing='0' style=' border: solid; border-width: 1px; border-color: #E70030; width: 600px'>
										       <thead style='background-color: #E70030; color: #FFF'>
										           <tr>
										               <th colspan='2' align='left'>Pago, envío y facturación
										               </th>
										           </tr>    
										       </thead>
										       <tbody>
											   	   <tr>
											   	       <td valign='top' style='width: 300px;'>
											   	         <b>Método de pago:</b><br />".$detalle_tarjeta['tc']['descripcionVc']." con terminación ".substr($detalle_tarjeta['tc']['terminacion_tarjetaVc'], -4)."<br />
											   	         <b>Código de autorización:</b>&nbsp;&nbsp;".$simple_result->codigo_autorizacion."<br />
											   	         <b>Fecha de autorización:</b>&nbsp;&nbsp;".mdate('%d-%m-%Y')."<br /><br />";
											   	       										   	       													
								$mensaje.=   	      "</td>
											   	       <td valign='top' style='width: 300px;'>
											   	           <b>Requiere factura:</b>&nbsp;".$this->session->userdata('requiere_factura')."<br /><br />";
											   	       
											   	       	   if ($this->session->userdata('requiere_factura')=='si') {
											   	       	       $mensaje.="<b>Razón social:</b><br />";	
											   	       	       $rs = $this->direccion_facturacion_model->obtener_rs($this->session->userdata('razon_social'));
											   	       	       $mensaje.=$rs->company."<br />";	
											   	       	       $mensaje.=$rs->tax_id_number."<br /><br />";
											   	       	       										   	       	       
											   	       	       $mensaje.="<b>Dirección de facturación:</b><br />";
											   	       	       $df = $this->direccion_facturacion_model->obtener_direccion($id_cliente, $this->session->userdata('direccion_f'));	  										   	       	       
											   	       	       $mensaje.= $df->calle."&nbsp;".$df->num_ext."&nbsp;".(!empty($df->num_int) ? ", Int. ".$df->num_int : "") . "<br/>";
															   $mensaje.= $df->cp."<br/>".
																 		  $df->ciudad."<br/>".
														 		          $df->estado."<br/>".
															 	 		  $df->pais."&nbsp;";
											   	       	   }
											   	       	   
								$mensaje.=			  "</td>
											   	   </tr>
											   </tbody>	   
										   </table>
										   <table cellspacing='0' style=' border: solid; border-width: 1px; border-color: #E70030; width: 600px'>
										   	   <thead style='background-color: #E70030; color: #FFF'>
										   	       <tr>
										   	           <th colspan='4' align='left'>
										   	               <b>Resumen de orden:</b>
										   	           </th>
										   	       </tr>
										   	   </thead>
										   	   <tbody>
										   	       <tr>
										   	           <td colspan='4' align='left'><b>Número de orden:</b>&nbsp;&nbsp;".$id_compra."
										   	           </td>										   	           										   	          
										   	       </tr>
										   	       <tr>
										   	       	   <td colspan='4'>
										   	       	       <b>Productos en la orden:</b>	   
										   	       	   </td>
										   	       </tr>";
							### se usará "$detalle_promociones", para mostrar la información de los artículos de la orden
							//IVA inicial de la compra
							$iva_compra = 0.0;
							$iva_message = "";
							$subtotal = 0;
							foreach ($detalle_promociones['descripciones_promocion'] as $promociones) {
						
								//para los artículos de las promos que lleven IVA
								$iva_message  = "";		//en principio no lleva para la promocion
								
								//revisar si se cobra IVA
								if ($promociones['promocion']->iva_promocion > 0) { //($articulo['taxableBi']) {
									$iva_compra += $promociones['promocion']->iva_promocion;	//ya se calcula desde el API para el la promoción
									$iva_message  = "<b>costo m&aacute;s IVA</b>";	//en principio no lleva para la promocion
								}
								
								if (strstr($promociones['promocion']->descripcionVc, '|' )) {
									$mp = explode('|', $promociones['promocion']->descripcionVc);
									$nmp = count($mp);
									if ($nmp == 2) {
										$desc_promo = $mp[0];
									} else if ($nmp == 3) {
										$desc_promo = $mp[1];
									}
								} else {
									$desc_promo = $promociones['promocion']->descripcionVc;
								}
								
								//indicador de que requiere envío
								$promo_requiere_envio = $promociones['promocion']->requiere_envio;
								
								$mensaje.= "<tr><td colspan='4'>".$desc_promo."</td></tr>";
								//sacar la descripción que se mostrará de la promoción
								foreach ($promociones['articulos'] as $articulo) {
									$mensaje .= 
									"<tr>
										<td colspan='2' class='instrucciones'>";
									if ($articulo['issue_id']) {
										foreach ($detalle_promociones['tipo_productoVc'] as $k => $v) {
											if ($k == $articulo['issue_id']) {
												if (strstr($v, '|' )) {
													$mp = explode('|',$v);
													$nmp = count($mp);
													if ($nmp == 2) {
														$desc_art = $mp[0];
													} else if ($nmp == 3) {
														$desc_art = $mp[1];
													}
												} else {
													$desc_art = $v;
												}
											}
										}
									} else {										
										$desc_art=$articulo['tipo_productoVc']."&nbsp;";								
										foreach($detalle_promociones['articulo_oc'] as $i => $oc){
											if($i == $articulo['oc_id'] ){
												$desc_art.= $oc;	
											}																	
										}
									}
									//medio de entrega del artículo
									$medio_entrega = empty($articulo['medio_entregaVc']) ? "" : $articulo['medio_entregaVc']; 
																	
									$mensaje .= "<div>".$desc_art. "&nbsp;<div class='label-promo-rojo'>$iva_message</div></div><br/>";
									
									//direcciones de envío asociadas
									if ($promo_requiere_envio) {
										//id de la promoción que se quiere asociar con otra dirección
										$id_promo = $promociones['promocion']->id_promocionIn;
										
										//detalles de las direcciones
										if (!empty($detalles_direcciones) && array_key_exists($id_promo, $detalles_direcciones)) {
										 	$d = $detalles_direcciones[$id_promo];
										 	
										 	$mensaje .= "Calle " . $d->address1 . ", Número " .$d->address2. " " . (isset($d->address4) ? ", Interior ".$d->address4 : "") . "<br/>";
											$mensaje .= "C.P. " . $d->zip . " " . $d->city . ", ". $d->state . " ". $d->codigo_paisVc . ", Tel. " . $d->phone . "&nbsp;";
										 }
									}
									//sumar al subtotal de la compra
									$subtotal += $promociones['promocion']->subtotal_promocion;
									//precio de la promoción
									$mensaje .=
										"</td>
										<td>&nbsp;</td>
										<td class='instrucciones' align='right'>$" . number_format($articulo['tarifaDc'], 2, '.', ',') . "&nbsp;" . $moneda . "</td>" .
									"</tr>";
								}
							}
														
		   	       			$mensaje.= "<tr>
							   	           <td colspan='2'>&nbsp;
							   	           </td>
							   	           <td align='right'>Sub-total:
							   	           </td>
							   	           <td align='right'>$".number_format($detalle_promociones['total_pagar'], 2, '.', ',')."&nbsp;".$moneda."
							   	           </td>
							   	       </tr>
							   	       <tr>
							   	           <td colspan='2'>&nbsp;
							   	           </td>
							   	           <td align='right'>I.V.A
							   	           </td>
							   	           <td align='right'>$".number_format($detalle_promociones['total_iva'], 2, '.', ',')."&nbsp;".$moneda."
							   	           </td>
							   	       </tr>
							   	       <tr>
							   	           <td colspan='2' width='325px'>&nbsp;
							   	           </td>
							   	           <td align='right' width='180px'><b>Total de la orden</b>
							   	           </td>
							   	           <td align='right' width='95px'><b>$".number_format($detalle_promociones['total_pagar'] + $detalle_promociones['total_iva'], 2, '.', ',')."&nbsp;" . $moneda ."</b>
							   	           </td>
							   	       </tr>										   	       												   
							   	   </tbody>
							   </table>
							   <br />
							   <br />
							   <b>¿Solicitaste factura?</b>
							   <br />
							   <br />
							   Si solicitaste factura, recibirás un correo con la liga para descargarla en un periodo máximo de 24 horas.
							   <br />
							   <br />
							   Gracias por comprar en Grupo Expansión.									  	   																																																										  	
					  	  	</div>
						</body>
						</html>";
						##echo $mensaje;
						##exit;
						
						$envio_correo = FALSE;
						///Envío de correo sólo en caso de que el cobro haya sido exitoso
						if (strtolower($simple_result->respuesta_banco) == "approved") {
							$envio_correo = $this->enviar_correo("Confirmación de compra", $mensaje);
							$estatus_correo = $this->registrar_estatus_compra($id_compra, (int)$id_cliente, self::$ESTATUS_COMPRA['ENVIO_CORREO']);
							
							//manejo envío correo
							if (!($envio_correo && $estatus_correo)) {	//Error
								redirect('mensaje/'.md5(4), 'refresh');
							}
						}
						
						//obtiene los datos que se van a regresar al sitio	(Teo)																						
						$data['url_back'] = $this->datos_urlback($simple_result->respuesta_banco, $id_compra);
															
						$data['resultado'] = $simple_result;
						$data['moneda'] = $moneda;				//para desplegar la respuesta de cobro
						
						$this->cargar_vista('', 'orden_compra', $data);
						
						if ($data['url_back']['estatus'] != 0) {
							$this->session->sess_destroy();
						}
						
					} catch (Exception $exception) {	//antes SoapFault
						//echo $exception;
						//echo '<br/>error: <br/>'.$exception->getMessage();
						return NULL;
					}
					
				} else {
					//////// la informacion de la TC está guardada en la BD
					//recupera la información de la TC
					$detalle_tarjeta = $this->forma_pago_model->detalle_tarjeta($consecutivo, $id_cliente);
					$tc = $detalle_tarjeta;
					$data['tc'] = $detalle_tarjeta;
					
					$tipo_pago = ($tc->id_tipo_tarjetaSi == self::Tipo_AMEX) ? self::$TIPO_PAGO['American_Express'] : self::$TIPO_PAGO['Prosa'];
					
					$primer_digito = $this->obtener_primer_digito_tc($id_cliente, $consecutivo);
					
					//echo " tipo pago: " . $tipo_pago;
					//echo " tipo pago de la DB: " . $tipo_pago;
					//echo "<pre>";
					//print_r($informacion_orden);
					//echo "</pre>";
					//exit;
					
					
					// Intentamos el Pago con los Id's en  CCTC //
					try {
						
						//si la compra no se ha generado y guardado en sesión en algún intento previo de pago
						if ($this->session->userdata('id_compra')) {
							$id_compra = $this->session->userdata('id_compra');
						} else {
							$id_compra = $this->registrar_orden_compra($id_cliente, $ids_promociones, $ids_direcciones_envio, $tipo_pago, $primer_digito);
						}
						
						if (!$id_compra) {	//Si falla el registro inicial de la compra en CCTC
							//echo "compra registrada!!!!";exit;
							redirect('mensaje/'.md5(3), 'refresh');
						}
						
						//pasar el id de la compra para el pago
						$informacion_orden->id_compraIn = $id_compra;
						
						//echo "info orden<pre>";
						//print_r($informacion_orden);
						//echo "</pre><br/>";
						//exit;
						
						//petición de pago a través de la interfase, el resultado ya es un objeto
						$simple_result = $this->solicitar_pago_CCTC_ids($informacion_orden);

						//Registro del estatus de la respuesta de CCTC
						$this->registrar_estatus_compra($id_compra, (int)$id_cliente, self::$ESTATUS_COMPRA['RESPUESTA_CCTC']);
						
						/*
						echo "simple_result<pre> gettype->";//.gettype($simple_result);
						print_r($simple_result);
						echo "</pre>";
						exit;
						*/
						//Registro de la respuesta de CCTC de la compra en ecommerce
						$info_detalle_pago_tc = array('id_compraIn'=> $id_compra, 'id_clienteIn' => $id_cliente, 'id_TCSi' => $consecutivo, 
														'id_transaccionBi' => $simple_result->id_transaccionNu, 'respuesta_bancoVc' => $simple_result->respuesta_banco,
														'codigo_autorizacionVc' => $simple_result->codigo_autorizacion, 'mensaje' => $simple_result->mensaje);

						//Registro de la respuesta del pago en ecommerce
						$this->registrar_detalle_pago_tc($info_detalle_pago_tc);
						$this->registrar_estatus_compra($id_compra, (int)$id_cliente, self::$ESTATUS_COMPRA['REGISTRO_PAGO_ECOMMERCE']);
						#echo "compra registrada final";exit;
						## para la prueba del correo
						/*$id_compra = 4;	//para el test
						$simple_result = NULL;
						$simple_result->codigo_autorizacion = 12321;
						## end pruebas*/
						
						##direcciones de envío
					   	//direcciones de envío ya se tienen recuperadas al principio del proceso de cobro
			   	       	//$ids_direcciones_envio;
			   	       	//$detalles_direcciones	//los detalles de las anteriores direcciones
						
						//Envío del correo
						$mensaje = "<html>
									  <body>
									  	   <div>
									  	   Hola ".$this->session->userdata('username').",<br />
										   Gracias por tu orden en pagos.grupoexpansion.mx
										   <br />
										   <br />
										   <table cellspacing='0' style=' border: solid; border-width: 1px; border-color: #E70030; width: 600px'>
										       <thead style='background-color: #E70030; color: #FFF'>
										           <tr>
										               <th colspan='2' align='left'>Pago, envío y facturación</th>
										           </tr>
										       </thead>
										       <tbody>
											   	   <tr>
											   	       <td valign='top' style='width: 300px;'>
											   	           <b>Método de pago:</b><br />".$detalle_tarjeta->descripcionVc." con terminación ".$detalle_tarjeta->terminacion_tarjetaVc."<br />
											   	           <b>Código de autorización:</b>&nbsp;&nbsp;".$simple_result->codigo_autorizacion."<br />
											   	           <b>Fecha de autorización:</b>&nbsp;&nbsp;".mdate('%d-%m-%Y')."<br /><br />";
											   	       
								$mensaje.=   	      "</td>
											   	       <td valign='top' style='width: 300px;'>
											   	           <b>Requiere factura:</b>&nbsp;".$this->session->userdata('requiere_factura')."<br /><br />";
											   	       
											   	       	   if ($this->session->userdata('requiere_factura') == 'si') {
											   	       	       $mensaje .= "<b>Razón social:</b><br />";	
											   	       	       $rs = $this->direccion_facturacion_model->obtener_rs($this->session->userdata('razon_social'));
											   	       	       
											   	       	       $mensaje .= $rs->company."<br />";	
											   	       	       $mensaje .= $rs->tax_id_number."<br /><br />";
											   	       	       										   	       	       
											   	       	       $mensaje .= "<b>Dirección de facturación:</b><br />";
											   	       	       $df = $this->direccion_facturacion_model->obtener_direccion($id_cliente, $this->session->userdata('direccion_f'));
															   	  										   	       	       
											   	       	       $mensaje .= $df->calle."&nbsp;".$df->num_ext."&nbsp;".(!empty($df->num_int) ? ", Int. ".$df->num_int : "") . "<br/>";
															   $mensaje .= "C.P. ".$df->cp."<br/>".
																 		  $df->ciudad."<br/>".
														 		          $df->estado."<br/>".
															 	 		  $df->pais."&nbsp;";
											   	       	   }
											   	       	   
								$mensaje.=			  "</td>
											   	   </tr>
											   </tbody>	   
										   </table>
										   <table cellspacing='0' style=' border: solid; border-width: 1px; border-color: #E70030; width: 600px'>
										   	   <thead style='background-color: #E70030; color: #FFF'>
										   	       <tr>
										   	           <th colspan='4' align='left'>
										   	               <b>Resumen de orden:</b>
										   	           </th>
										   	       </tr>
										   	   </thead>
										   	   <tbody>
										   	       <tr>
										   	           <td colspan='4' align='left'><b>Número de orden:</b>&nbsp;&nbsp;".$id_compra."
										   	           </td>										   	           										   	          
										   	       </tr>
										   	       <tr>
										   	       	   <td colspan='4'>
										   	       	       <b>Productos en la orden:</b>	   
										   	       	   </td>
										   	       </tr>";
						
							### se usará "$detalle_promociones", para mostrar la información de los artículos de la orden
							//IVA inicial de la compra
							$iva_compra = 0.0;
							$iva_message = "";
							$subtotal = 0;
							foreach ($detalle_promociones['descripciones_promocion'] as $promociones) {
						
								//para los artículos de las promos que lleven IVA
								$iva_message  = "";		//en principio no lleva para la promocion
								
								//revisar si se cobra IVA
								if ($promociones['promocion']->iva_promocion > 0) { //($articulo['taxableBi']) {
									$iva_compra += $promociones['promocion']->iva_promocion;	//ya se calcula desde el API para el la promoción
									$iva_message  = "<b>costo m&aacute;s IVA</b>";	//en principio no lleva para la promocion
								}
								
								if (strstr($promociones['promocion']->descripcionVc, '|' )) {
									$mp = explode('|', $promociones['promocion']->descripcionVc);
									$nmp = count($mp);
									if ($nmp == 2) {
										$desc_promo = $mp[0];
									} else if ($nmp == 3) {
										$desc_promo = $mp[1];
									}
								} else {
									$desc_promo = $promociones['promocion']->descripcionVc;
								}
								
								//indicador de que requiere envío
								$promo_requiere_envio = $promociones['promocion']->requiere_envio;
								
								$mensaje.= "<tr><td colspan='4'>".$desc_promo."</td></tr>";
								
								//sacar la descripción que se mostrará de la promoción
								foreach ($promociones['articulos'] as $articulo) {
									$mensaje .= 
									"<tr>
										<td colspan='2' class='instrucciones'>";
									if ($articulo['issue_id']) {
										foreach ($detalle_promociones['tipo_productoVc'] as $k => $v) {
											if ($k == $articulo['issue_id']) {
												if (strstr($v, '|' )) {
													$mp = explode('|',$v);
													$nmp = count($mp);
													if ($nmp == 2) {
														$desc_art = $mp[0];
													} else if ($nmp == 3) {
														$desc_art = $mp[1];
													}
												} else {
													$desc_art = $v;
												}
											}
										}
									} else {										
										$desc_art=$articulo['tipo_productoVc']."&nbsp;";								
										foreach($detalle_promociones['articulo_oc'] as $i => $oc){
											if($i == $articulo['oc_id'] ){
												$desc_art.= $oc;	
											}																	
										}
									}
									//medio de entrega del artículo
									$medio_entrega = empty($articulo['medio_entregaVc']) ? "" : $articulo['medio_entregaVc']; 
																	
									$mensaje .= "<div>".$desc_art. "&nbsp;<div class='label-promo-rojo'>$iva_message</div></div><br/>";
									
									//direcciones de envío asociadas
									if ($promo_requiere_envio) {
										//id de la promoción que se quiere asociar con otra dirección
										$id_promo = $promociones['promocion']->id_promocionIn;
										
										//detalles de las direcciones
										if (!empty($detalles_direcciones) && array_key_exists($id_promo, $detalles_direcciones)) {
										 	$d = $detalles_direcciones[$id_promo];
										 	
										 	$mensaje .= "Calle " . $d->address1 . ", Número " .$d->address2. " " . (isset($d->address4) ? ", Interior ".$d->address4 : "") . "<br/>";
											$mensaje .= "C.P. " . $d->zip . " " . $d->city . ", ". $d->state . " ". $d->codigo_paisVc . ", Tel. " . $d->phone . "&nbsp;";
										 }
									}
									//sumar al subtotal de la compra
									$subtotal += $promociones['promocion']->subtotal_promocion;
									//precio de la promoción
									$mensaje .=
										"</td>
										<td>&nbsp;</td>
										<td class='instrucciones' align='right'>$" . number_format($articulo['tarifaDc'], 2, '.', ',') . "&nbsp;" . $moneda . "</td>" .
									"</tr>";
								}
							}
														
		   	       			$mensaje.= "<tr>
							   	           <td colspan='2'>&nbsp;
							   	           </td>
							   	           <td align='right'>Sub-total:
							   	           </td>
							   	           <td align='right'>$".number_format($detalle_promociones['total_pagar'], 2, '.', ',')."&nbsp;".$moneda."
							   	           </td>
							   	       </tr>
							   	       <tr>
							   	           <td colspan='2'>&nbsp;
							   	           </td>
							   	           <td align='right'>I.V.A
							   	           </td>
							   	           <td align='right'>$".number_format($detalle_promociones['total_iva'], 2, '.', ',')."&nbsp;".$moneda."
							   	           </td>
							   	       </tr>
							   	       <tr>
							   	           <td colspan='2' width='325px'>&nbsp;
							   	           </td>
							   	           <td align='right' width='180px'><b>Total de la orden</b>
							   	           </td>
							   	           <td align='right' width='95px'><b>$".number_format($detalle_promociones['total_pagar'] + $detalle_promociones['total_iva'], 2, '.', ',')."&nbsp;" . $moneda ."</b>
							   	           </td>
							   	       </tr>										   	       												   
							   	   </tbody>
							   </table>
							   <br />
							   <br />
							   <b>¿Solicitaste factura?</b>
							   <br />
							   <br />
							   Si solicitaste factura, recibirás un correo con la liga para descargarla en un periodo máximo de 24 horas.
							   <br />
							   <br />
							   Gracias por comprar en Grupo Expansión.									  	   																																																										  	
					  	  	</div>
						</body>
						</html>";
						##echo $mensaje;
						##exit;
						
						if (strtolower($simple_result->respuesta_banco) == "approved") {
							$envio_correo = $this->enviar_correo("Confirmación de compra", $mensaje);
							$estatus_correo = $this->registrar_estatus_compra($id_compra, (int)$id_cliente, self::$ESTATUS_COMPRA['ENVIO_CORREO']);
							
							//manejo envío correo
							if (!($envio_correo && $estatus_correo)) {	//Error
								redirect('mensaje/'.md5(4), 'refresh');
							}
						}
						
						//Para lo que se devolverá a Teo							
						$data['url_back'] = $this->datos_urlback($simple_result->respuesta_banco, $id_compra);
						
						$data['resultado'] = $simple_result;
						$data['moneda'] = $moneda;				//para desplegar la respuesta de cobro
										
						$this->cargar_vista('', 'orden_compra', $data);
						
						if ($data['url_back']['estatus'] != 0) {
							$this->session->sess_destroy();	
						}
						
					} catch (Exception $exception) {	//antes SoapFault
						//errores en desarrollo
						//echo $exception;
						//echo '<br/>error: <br/>'.$exception->getMessage();
						return NULL;
					}
				}
			} else {	//If Errores
				//redirect('orden_compra', 'refresh');
				$this->resumen("El formato del código es incorrecto", TRUE);
			}
		} else { //si llega sin una petición
			redirect('orden_compra', 'refresh');
		}
	}
	
	/**
	 * Método que realiza la petición de cobro a la interfase que intercomunica con el sistema de CCTC 
	 */
	private function solicitar_pago_CCTC_objetos($tc_soap = NULL, $amex_soap = NULL, $informacion_orden = NULL) {		
		if (isset($tc_soap, $informacion_orden)) {
			// Metemos todos los parametros (Objetos) necesarios a una clase dinámica llamada paramátros //
			$parametros = new stdClass;
			$parametros->tc_soap = $tc_soap;
			$parametros->amex_soap = $amex_soap;
			$parametros->informacion_orden = $informacion_orden;
			
			// Hacemos un encode de los objetos para poderlos pasar por POST ...
			$param = json_encode($parametros);
			
			//echo "<pre>";
			//print_r($parametros);
			//echo "</pre>";
			//echo $param."<br/>";
			//exit;
			
			// Inicializamos el CURL / SI no funciona se puede habilitar en el php.ini //
			$c = curl_init();
			// CURL de la URL donde se haran las peticiones //
			//curl_setopt($c, CURLOPT_URL, 'dev.interfase.mx/interfase.php');
			curl_setopt($c, CURLOPT_URL, 'http://10.43.29.196/interfase_cctc/interfase.php');
			//curl_setopt($c, CURLOPT_URL, 'http://localhost/interfase_cctc/interfase.php');
			// Se enviaran los datos por POST //
			curl_setopt($c, CURLOPT_POST, true);
			// Que nos envie el resultado del JSON //
			curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
			// Enviamos los parametros POST //
			curl_setopt($c, CURLOPT_POSTFIELDS, 'accion=PagarConObjetos&token=123456&parametros='.$param);
			// Ejecutamos y recibimos el JSON //
			$resultado = curl_exec($c);
			// Cerramos el CURL //
			curl_close($c);
			
			//echo "<pre>";
			//print_r(json_decode($resultado));
			//echo "</pre>";
			//exit;
			
			return json_decode($resultado);
		}
	}
	
	/**
	 * Función que realiza la petición a la interfase de cobro que enlaza con CCTC 
	 */
	private function solicitar_pago_CCTC_ids($informacion_orden) {
		if (isset($informacion_orden)) {
			// Metemos todos los parametros (Objetos) necesarios a una clase dinámica llamada paramátros //
			$parametros = new stdClass;
			$parametros->informacion_orden = $informacion_orden;
			
			// Hacemos un encode de los objetos para poderlos pasar por POST ...
			$param = json_encode($parametros);
			/*
			echo "<pre>";
			print_r($parametros);
			echo "</pre>";
			echo $param."<br/>";
			exit;
			*/
			// Inicializamos el CURL / SI no funciona se puede habilitar en el php.ini //
			$c = curl_init();
			// CURL de la URL donde se haran las peticiones //
			//curl_setopt($c, CURLOPT_URL, 'dev.interfase.mx/interfase.php');
			curl_setopt($c, CURLOPT_URL, 'http://10.43.29.196/interfase_cctc/interfase.php');
			//curl_setopt($c, CURLOPT_URL, 'http://localhost/interfase_cctc/interfase.php');
			// Se enviaran los datos por POST //
			curl_setopt($c, CURLOPT_POST, true);
			// Que nos envie el resultado del JSON //
			curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
			// Enviamos los parametros POST //
			curl_setopt($c, CURLOPT_POSTFIELDS, 'accion=PagarConIds&token=123456&parametros='.$param);
			// Ejecutamos y recibimos el JSON //
			$resultado = curl_exec($c);
			// Cerramos el CURL //
			curl_close($c);
			
			//echo "<pre>";
			//print_r(json_decode($resultado));
			//echo "</pre>";
			//exit;
			
			return json_decode($resultado);
		}
	}
	
	/**
	 * Obtiene el detalle de la tarjeta Amex desde CCTC.
	 * Siempre será la información de AMEX sólamente
	 */
	private function obtener_detalle_interfase_CCTC($id_cliente = 0, $consecutivo = 0) {
		if (isset($id_cliente, $consecutivo)) {
			// Metemos todos los parametros (Objetos) necesarios a una clase dinámica llamada paramátros //
			$parametros = new stdClass;
			$parametros->id_cliente = $id_cliente;
			$parametros->consecutivo = $consecutivo;
			
			// Hacemos un encode de los objetos para poderlos pasar por POST ...
			$param = json_encode($parametros);
			/*
			echo "<pre>";
			print_r($parametros);
			echo "</pre>Param: ";
			echo $param."<br/>";
			*/
			// Inicializamos el CURL / SI no funciona se puede habilitar en el php.ini //
			$c = curl_init();
			// CURL de la URL donde se haran las peticiones //
			//curl_setopt($c, CURLOPT_URL, 'dev.interfase.mx/interfase.php');
			curl_setopt($c, CURLOPT_URL, 'http://10.43.29.196/interfase_cctc/interfase.php');
			//curl_setopt($c, CURLOPT_URL, 'http://localhost/interfase_cctc/interfase.php');
			// Se enviaran los datos por POST //
			curl_setopt($c, CURLOPT_POST, true);
			// Que nos envie el resultado del JSON //
			curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
			// Enviamos los parametros POST //
			curl_setopt($c, CURLOPT_POSTFIELDS, 'accion=ObtenerDetalleAmex&token=123456&parametros='.$param);
			// Ejecutamos y recibimos el JSON //
			$resultado = curl_exec($c);
			// Cerramos el CURL //
			curl_close($c);
			/*
			echo "Resultado<pre>";
			print_r(json_decode($resultado));
			echo "</pre>";
			exit;
			*/
			return json_decode($resultado);
		}
	}

	/**
	 * Obtiene el detalle de la tarjeta Amex desde CCTC
	 */
	private function detalle_tarjeta_CCTC($id_cliente = 0, $consecutivo = 0)	//siempre será la información de AMEX
	{
		
		//Traer la info de amex
		try {  
			$cliente = new SoapClient("https://cctc.gee.com.mx/ServicioWebCCTC/ws_cms_cctc.asmx?WSDL");
				
			$parameter = array(	'id_clienteNu' => $id_cliente, 'consecutivo_cmsSi' => $consecutivo);
			
			$obj_result = $cliente->ConsultarAmex($parameter);
			$tarjeta_amex = $obj_result->ConsultarAmexResult;	//regresa un objeto
			
			//print($simple_result);
			
			return $tarjeta_amex;
			
		} catch (SoapFault $exception) {
			echo $exception;  
			echo '<br/>error: <br/>'.$exception->getMessage();
			//exit();
			return false;
		}
	}
	
	/**
	 * Registrar toda la información de la orden
	 * El tercer parámetro es para indicar el estatus inicial
	 */
	
	private function datos_urlback($respuesta_banco, $id_compra){
		if($respuesta_banco === "approved"){
			$estatus_pago = 1;
		} else {
			//este caso puede ser denied o Incorrect information, o Duplicated Informaion
			$estatus_pago = 0;
		}					

		$datos = array();		
		$datos['cadena_comprobacion'] = md5($this->session->userdata('guidx').$this->session->userdata('guidy').$this->session->userdata('guidz').$estatus_pago);		
		$datos['estatus']=$estatus_pago;
		$datos['id_compra']=$id_compra;
		
		if(array_key_exists('issues_idc', $this->detalle_promociones)){				
				$datos['datos_login_idc'] = $this->api->encrypt($id_compra."|".$this->api->decrypt($this->session->userdata('datos_login'),$this->api->key).json_encode($this->detalle_promociones['issues_idc']['clave'])."|", $this->api->key);				
				$datos['urlback_idc'] = $this->detalle_promociones['issues_idc']['url_back'];
		}	
		if(array_key_exists('issues_cnn', $this->detalle_promociones)){				
				$datos['datos_login_cnn'] = $this->api->encrypt($id_compra."|".$this->api->decrypt($this->session->userdata('datos_login'),$this->api->key).json_encode($this->detalle_promociones['issues_cnn']['clave'])."|", $this->api->key);				;				
				$datos['urlback_cnn'] = $this->detalle_promociones['issues_cnn']['url_back'];
		}	
		
		$datos['datos_login'] = $this->api->encrypt($id_compra."|".$this->api->decrypt($this->session->userdata('datos_login'),$this->api->key), $this->api->key);
		$datos['urlback'] = $this->session->userdata('sitio')->url_PostbackVc;				
		
		/*echo "<pre>";
		print_r($datos);
		echo "</pre>";
		exit();*/
		
		return $datos;			
	}
	 
	/**
	 * Se registra la orden de compra para las promociones solicitadas
	 * @param $id_cliente Quien solicita comprar
	 * @param $ids_promociones array con los ids de las promociones que se quieren comprar
	 * @param $ids_direcciones_envio array con los ids de las promociones y la direccion de envío asociada a la promocion
	 * @param $tipo_pago La forma de pago que se usará
	 * @param $primer_digito El primer dígito del número de la tarjeta para porder saber de qué tipo es (AMEX, VISA, MasterCard) 
	 */
	private function registrar_orden_compra($id_cliente, $ids_promociones, $ids_direcciones_envio, $tipo_pago, $primer_digito = NULL)
	{
		//Registrar eb la tabla de órdenes
		$id_compra = 0;
		$id_compra = $this->registrar_compra($id_cliente);
		
		if ($id_compra) {
			$this->session->set_userdata('id_compra', $id_compra);
			
			//los artículos que se registrarán para la compra 
			$info_articulos = array();
			
			foreach ($ids_promociones as $key => $id_promo) {
				///artíclos de la promoción
				$articulos_compra = array();
				$articulos_compra = $this->orden_compra_model->obtener_articulos_promocion($id_promo);
				
				//////////artículos///////////
				foreach ($articulos_compra as $articulo) {
					 //preparar la información para insertar los artículos
					$info_articulos[] = array((int)$articulo->id_articulo, $id_compra, (int)$id_cliente, (int)$id_promo);
				}
			}
			/*
			echo "<pre>";
			print_r($info_articulos);
			echo "</pre>";*/
			//exit;
			
			/////// forma pago ///////
			$id_TCSi = 0;
			$info_pago = array();
			//procesar el tipo de pago
			if ($tarjeta = $this->session->userdata('tarjeta')) {
				$id_TCSi = (is_array($tarjeta)) ? NULL: (int)$this->session->userdata('tarjeta');
				//$tipo_pago = 1;	//MASTERCARD / VISA/=1,  AMEX=2 
			} else if ($this->session->userdata('deposito')) {
				$id_TCSi = NULL;		//consecutivo usado para depósito y tarjetas no guardadas
				//$tipo_pago = 3;	//Depósito Bancario
			}
			
			$info_pago = array('id_compraIn' => $id_compra, 'id_clienteIn' => (int)$id_cliente, 'id_tipoPagoSi' => $tipo_pago, 'id_TCSi' => $id_TCSi, 'primer_digitoTi' => $primer_digito);
			/*
			echo "<pre>";
			print_r($info_pago);
			echo "</pre>"; */
			//exit;


			/////// direccion(es) de envío///////
			$info_direcciones = array();
			//las direcciones de envío vienene como argumento en la llamada
			if ($this->session->userdata('requiere_envio') && !empty($ids_direcciones_envio)) {
				//echo "Sí requiere_envio: Si<br/>";
				### Ajustado para múltiples direcciones
				###if ($dir_envio = $this->session->userdata('dir_envio')) {
				foreach ($ids_direcciones_envio as $id_promo => $id_direccion) {
					
					//echo "direccion_envio: " . $dir_envio;
					
					$info_direcciones['envio'][] = 
						array('id_compraIn' => $id_compra, 'id_clienteIn' => (int)$id_cliente, 'id_promocionIn' => $id_promo, 'id_consecutivoSi' => (int)$id_direccion, 'address_type' => self::$TIPO_DIR['RESIDENCE']);
						//array('id_compraIn' => $id_compra, 'id_clienteIn' => (int)$id_cliente, 'id_consecutivoSi' => (int)$dir_envio, 'address_type' => self::$TIPO_DIR['RESIDENCE']);
				} 
				
			} else if ($this->session->userdata('requiere_envio') && empty($ids_direcciones_envio)) {
					//No se efectúa la petición por que falta el dato de envío
					echo "Error: la compra requiere dirección de envío.";
					return FALSE;
			} else {
				//si no requiere se vacía el arreglo
				$info_direcciones['envio'] = array();
			}
			
			////////// dirección de facturación //////////
			if ($this->session->userdata('requiere_factura') !== "no") {
				//echo "Sí requiere factura: <br/>".$this->session->userdata('requiere_factura');
				$dir_facturacion = $this->session->userdata('direccion_f');
				$razon_social = $this->session->userdata('razon_social');
				
				### Ajuste de múltiples direcciones, requiere asociar todas las promociones de la compra con la misma dirección de facturación
				if ($dir_facturacion && $razon_social) {
					//se registrará la dirección de facturación para todas las promociones si es que se requiere
					foreach ($ids_promociones as $key => $id_promo) {
						$info_direcciones['facturacion'][] = 
							array('id_compraIn' => $id_compra, 'id_clienteIn' => (int)$id_cliente, 'id_promocionIn' => $id_promo, 'id_consecutivoSi' => (int)$dir_facturacion, 'id_razonSocialIn' => (int)$razon_social , 'address_type' => self::$TIPO_DIR['BUSINESS']);
					}
					
						//array('id_compraIn' => $id_compra, 'id_clienteIn' => (int)$id_cliente, 'id_consecutivoSi' => (int)$dir_facturacion, 'id_razonSocialIn' => (int)$razon_social , 'address_type' => self::$TIPO_DIR['BUSINESS']);
				} else {
					echo "Error: falta la dirección de facturación";
					return FALSE;
				}
			} else {
				//si no requiere se vacía
				$info_direcciones['facturacion'] = array();
			}
			/*
			echo "<pre>";
			print_r($info_direcciones);
			echo "</pre>";
			exit;
			 */ 
			///////estatus de registro de la compra///////
			$estatus = ($tipo_pago == self::$TIPO_PAGO['Deposito_Bancario']) ? self::$ESTATUS_COMPRA['PAGO_DEPOSITO_BANCARIO'] : self::$ESTATUS_COMPRA['SOLICITUD_CCTC'];
			$info_estatus = array('id_compraIn' => $id_compra, 'id_clienteIn' => (int)$id_cliente, 'id_estatusCompraSi' => $estatus);
			
			/*echo "<pre>";
			print_r($info_direcciones);
			echo "</pre>";
			exit;*/			



			///////////// registrar compra inicial en BD /////// 
			$registro_orden = $this->orden_compra_model->registrar_compra_inicial($info_articulos, $info_pago, $info_direcciones, $info_estatus);
			//echo "compra: " . $id_compra;
//			exit();
			return $id_compra;
		} else {
			//Error en el registro de la compra
			$this->session->unset_userdata('id_compra');	
			return FALSE;
		}
		
	}
	
	/**
	 * Redistro de la compra
	 */
	private function registrar_compra($id_cliente)
	{
		try {
			return $this->orden_compra_model->insertar_compra($id_cliente);
		} catch (Exception $ex ) {
			echo "Error en el registro del la compra: " .$ex->getMessage();
			return FALSE;
		}
	}
	
	/**
	 * Actualizar el id_td de la tarjeta y el primer dígito en la tabla "CMS_RelCompraPago" cuando se solicita un cobro con una TC no guardada en DB.
	 * La información es la que se utilizó para solicitar el cobro en CCTC.
	 * Debido a que pudo ser un intento posterior al inicial, se debe hacer esta actualización,
	 * tal como el trigger en la tabla "CMS_RelCompraPagoDetalleTC".
	 * Si es el intento iniical, la información permanecerá idéntica.
	 * 
	 * @param $id_cliente El id del cliente que compra
	 * @param $id_compra el id de la compra actual
	 * @param $id_tc el id de la tarjeta que se actualizará en la solicitud de pago
	 * @param $primer_digito es el primer dígito de la nueva TC que se quiere utilizar
	 * @param $tipo_pago es el id del catálogo de tipos de pago (1->PROSA, 2->AMEX) que se pueden utilizar 
	 */
	private function actualizar_info_compra_tc($id_cliente, $id_compra, $id_tc, $primer_digito, $tipo_pago) 
	{
		$new_data = array('id_TCSi' => $id_tc, 'primer_digitoTi' => $primer_digito, 'id_tipoPagoSi' => $tipo_pago);
		try {
			return $this->orden_compra_model->actualizar_info_compra_tc($id_cliente, $id_compra, $new_data);
		} catch (Exception $ex ) {
			echo "Error en la actualización de la forma de pago: " .$ex->getMessage();
			return FALSE;
		}
	}
	
	/**
	 * Registrar alguno de los estatus de la compra
	 * id_compraIn, id_clienteIn, id_estatusCompreSi, timestamp
	 */
	private function registrar_estatus_compra($id_compra, $id_cliente, $id_estatusCompra)
	{
		try {
			$info_estatus = array('id_compraIn' => $id_compra, 'id_clienteIn' => $id_cliente, 'id_estatusCompraSi' => $id_estatusCompra);
			return $this->orden_compra_model->insertar_estatus_compra($info_estatus);
		} catch (Exception $ex ) {
			echo "Error en el registro del estatus de la compra: " .$ex->getMessage();
			return FALSE;
		}
	}
	
	/**
	 * Recuperar el primer dígito de la tarjeta guardada que se utilizará para la compra.
	 * Se recuperará de la tabla 'CMS_IntTC', ya que se guardará cada vez que se registre una tarjeta.
	 */
	private function obtener_primer_digito_tc($id_cliente, $id_tc) {
		$res = $this->orden_compra_model->obtener_primer_digito_tc($id_cliente, $id_tc)->row()->primer_digitoTi;
		$digito =  ($res) ? $res : 0;
		//echo "digito: " . $digito ."-<br/>";
		//exit;
		
		return $res;
		
	}
	/**
	 * Registro del detalle del pago en ecommerce 
	 */
	private function registrar_detalle_pago_tc($info_detalle_pago_tc) {
		try {
			return $this->orden_compra_model->insertar_detalle_pago_tc($info_detalle_pago_tc);
		} catch (Exception $ex ) {
			echo "Error en el registro detalle del pago en ecomerce: " .$ex->getMessage();
			return FALSE;
		}
	}
	 
	
	/**
	 * Obtiene los datos para solicitar el cobro de la orden de compra
	 */
	private function get_datos_orden() {
		$datos = array();
		
		if (array_key_exists("txt_codigo", $_POST)) {
			if (preg_match('/^[0-9]{3,4}$/', $_POST['txt_codigo'])) { 
				$datos['cvv'] = $_POST['txt_codigo'];
			} else {
				$this->registro_errores['txt_codigo'] = 'Ingresa un código de seguridad válido';
			}
		}
		
		return $datos;
	}
	
	/**
	 * Envía un correo 
	 */
	private function enviar_correo($asunto, $mensaje) {
		$headers = "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
	    $headers .= "From: Pagos Grupo Expansión<pagosmercadotecnia@expansion.com.mx>\r\n";
		$headers .= "Bcc: abarrales@expansion.com.mx, aespinosa@expansion.com.mx, jramirez@expansion.com.mx, harteaga@expansion.com.mx\r\n";
		
		
		$email = $this->session->userdata('email');
					
		//return ($email && mail($email, $asunto, $mensaje));
		return mail($email, "=?UTF-8?B?".base64_encode($asunto)."?=", $mensaje, $headers);
	}
	
	/**
	 * Encripta la cadena con una llave pública con RSA y regresa la cadena encriptada.
	 * @param $texto lo que se quiere encriptar
	 * @return $ciphered el texto encriptado con la llave pública
	 */
	private function encriptar_rsa_texto($texto = "") {
		include('rsa/Crypt/RSA.php');		//pára encriptar la clave de la tc
		
		$rsa = new Crypt_RSA();
		$ciphered_text = FALSE;
		//if (file_exists('application/controllers/rsa/public.pem')) {
		if (file_exists('application/controllers/rsa/public_cms.pem')) {
			
			//se carga la 'public key'
			//$rsa->loadKey(file_get_contents('application/controllers/rsa/public.pem')); 	// public key con password "3xp4n5i0n"
			$rsa->loadKey(file_get_contents('application/controllers/rsa/public_cms.pem')); 	// public key con password "3xp4n5i0n"
			
			//algoritmo de encriptación
			$rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);	//2
			
			//texto cifrado:
			$ciphertext = $rsa->encrypt($texto);
			
			$ciphered_text = base64_encode($ciphertext);
			//texto cifrado
			//echo "texto cifrado" . $ciphertext . "<br/>";
			
			//texto codificado
			//echo "base 64 encode: " . base64_encode($ciphertext) . "<br/>";
			
		}
		
		return $ciphered_text;
	}
	/**
	 * Se enecarga de definir la navegación de la plataforma de acuerdo a la actualización de las formas de pago
	 */
	private function obtener_destino()
	{
		//Inicializar el destino con un valor por defecto.
		$destino = $this->session->userdata('destino') ? $this->session->userdata('destino') : "forma_pago";
		
		if ($this->session->userdata('tarjeta') || $this->session->userdata('deposito')) {	//tiene forma de pago
			//actualizar valores en sesión
			if ($this->session->userdata('requiere_envio')) {
				//Si hay dirección de envío seleccionada...
				if ($this->session->userdata('dir_envio')) {
					//Si hay dirección de facturación Y razón Social
					if ($this->session->userdata('direccion_f') && $this->session->userdata('razon_social')) {
						$destino = "orden_compra";
					} else {
						$destino = "direccion_facturacion";
					}
				} else {
					$destino = "direccion_envio";
				}
			} else {
				//no requiere dirección de envío
				//Si hay dirección de facturación Y razón Social
				if ($this->session->userdata('direccion_f') && $this->session->userdata('razon_social')) {
					$destino = "orden_compra";
				} else {
					$destino = "direccion_facturacion";
				}
			}
		} else {	//no tiene forma de pago
			$destino = "forma_pago";
		}
		
		//Actualizar en sesión
		$this->session->set_userdata('destino', $destino);
		
		return $destino;
	}
	
	/**
	 * Carga la vista indicada ubicada en la carpeta/folder y se le pasa la información
	 */
	private function cargar_vista($folder, $page, $data)
	{	
		//Para automatizar un poco el desplieguee
		$this->load->view('templates/header', $data);
		$this->load->view('templates/menu.html', $data);
		
		//si hay promociones cargadas al instanciar el controlador, se pasan a la vista 
		if ($this->detalle_promociones) {
			$data['detalle_promociones'] = $this->detalle_promociones;
		}
		$this->load->view($folder.'/'.$page, $data);
		$this->load->view('templates/footer', $data);
	}
	
	/**
	 * Verifica la sesión del usuario
	 * */
	private function redirect_cliente_invalido($revisar = 'id_cliente', $destino = '/index.php/login', $protocolo = 'http://') {
		if (!$this->session->userdata($revisar)) {
			//$url = $protocolo . BASE_URL . $destination; // Define the URL.
			$url = site_url($destino); // Define the URL.
			header("Location: $url");
			exit(); // Quit the script.
		}
	}
	
	/**
	 * Covierte recursivamente los objetos en arrays
	 */
	private function ArrayToObject($array){
      $obj= new stdClass();
      foreach ($array as $k=> $v) {
         if (is_array($v)){
            $v = ArrayToObject($v);   
         }
         $obj->{$k} = $v;
      }
      return $obj;
   }
}

/* End of file orden_compra.php */
/* Location: ./application/controllers/orden_compra.php */
