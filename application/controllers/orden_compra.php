<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include('dtos/Tipos_Tarjetas.php');
include('util/Pago_Express.php');
include('api.php');

class Orden_Compra extends CI_Controller {
	var $title = 'Verifica tu orden';
	var $subtitle = 'Verifica tu orden';
	var $registro_errores = array();				//validación para los errores
	
	private $id_cliente;
	private $api;
	
	const Tipo_AMEX = 1;
	
	public static $ESTATUS_COMPRA = array(
		"SOLICITUD_CCTC"			=> 1, 
		"RESPUESTA_CCTC"			=> 2, 
		"REGISTRO_PAGO_ECOMMERCE"	=> 3,
		"PAGO_DEPOSITO_BANCARIO" 	=> 4,
		"ENVIO_CORREO"				=> 5
	);
	
	public static $TIPO_PAGO = array(
		"Prosa"				=> 1, 
		"American_Express"	=> 2, 
		"Deposito_Bancario"	=> 3,
		"Otro"				=> 4
	);
	
	public static $TIPO_DIR = array(
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
		//manda al usuario a la... pagina de login
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
		
    }

	public function index() {					
		
		if ($_POST) {
			if (array_key_exists('direccion_selecionada', $_POST))  {
				$cte = $this->id_cliente;
				$rs = $this->session->userdata('razon_social');								
												
				$this->session->set_userdata('direccion_f', $_POST['direccion_selecionada']);				
				$ds = $this->session->userdata('direccion_f');
				
				if($rs!="" && $ds != ""){
					$rbr = $this->direccion_facturacion_model->busca_relacion($cte, $rs, $ds);				
					if ($rbr->num_rows() == 0) {					
						$this->load->helper('date');
						$fecha = mdate('%Y/%m/%d',time());
						$data_dir = array(
                   			'id_clienteIn'  => $cte,
                   			'id_consecutivoSi' => $ds,
                   			'id_razonSocialIn' => $rs,
                   			'fecha_registroDt' => $fecha                    				                    		
               			);																										
						$this->direccion_facturacion_model->insertar_rs_direccion($data_dir);		
						$this->session->set_userdata('requiere_factura', 'si');	
					}	
				}																							
			} else {
			$id_cliente = $this->id_cliente;
			$rs = $this->session->userdata('razon_social');
			$rdf = $this->direccion_facturacion_model->obtiene_rs_dir($id_cliente, $rs);		
				foreach ($rdf->result_array() as $dire) {					
					$this->session->set_userdata('direccion_f',$dire['id_consecutivoSi']);
				}			
			}								
		} else if ($this->session->userdata('id_dir')) {
			$id_dir = $this->session->userdata('id_dir');
			$this->session->set_userdata('direccion_f',$id_dir);								
		}
		
		$this->resumen();
	}
	
	/**
	 * Recupera y despliega la información de la orden de compra en curso
	 */
	public function resumen($msg = '', $redirect = TRUE) 
	{
		$data['title'] = $this->title;
		$data['subtitle'] = $this->subtitle;
		$data['mensaje'] = $msg;
		$data['redirect'] = $redirect;
		
		//Validación del lado del cliente
		$script_file = "<script type='text/javascript' src='". base_url() ."js/orden_compra.js'> </script>";
		$data['script'] = $script_file;
		
		//gestión de la dirección de envío con el obj. de pago exprés
		$data['requiere_envio'] = $this->session->userdata('requiere_envio');
		
		/*Recuperar la info gral. de la orden*/
		$id_cliente = $this->id_cliente;

		//Tarjeta
		$tarjeta = $this->session->userdata('tarjeta');
		
		//si está en session la información
		if ($this->session->userdata('deposito')) {	//revisar si hay depósito bancario
			$data['deposito'] = TRUE;
		} else if (!empty($tarjeta)) {
			//no se guarda en la BD
			if (is_array($this->session->userdata('tarjeta'))) {
				$tarjeta = (object)$tarjeta;
				$detalle_tarjeta = (object)$tarjeta->tc;
				
				//echo var_dump($detalle_tarjeta);
				
				$data['tc'] = $detalle_tarjeta;
				
				if ($detalle_tarjeta->id_tipo_tarjetaSi == 1) { //es AMERICAN EXPRESS
					$data['amex'] = (object)$tarjeta->amex;
					//en este caso se consultará la info del WS
				}
				//echo var_dump($data);
				//exit();
			} else if (is_integer((int)$this->session->userdata('tarjeta'))) {
				
				$consecutivo = $this->session->userdata('tarjeta');
				
				$detalle_tarjeta = $this->forma_pago_model->detalle_tarjeta($consecutivo, $id_cliente);
				$data['tc'] = $detalle_tarjeta;	//trae la tc
			
				if ($detalle_tarjeta->id_tipo_tarjetaSi == 1) { //es AMERICAN EXPRESS
					//$data['amex'] = $this->detalle_tarjeta_CCTC($id_cliente, $consecutivo);
					$data['amex'] = $this->obtener_detalle_interfase_CCTC($id_cliente, $consecutivo);
					//en este caso se consultará la info del WS
				}
			} 
			//Considerar el Depósito Bancario como forma de pago
		} else if (empty($tarjeta)) {
			$destino = $this->obtener_destino();
			redirect($destino, 'location', 302);
		}
		
		//dir_envío
		$dir_envio = $this->session->userdata('dir_envio');
		if (!empty($dir_envio)) {
			if (is_array($dir_envio)) {
				//por si no se guarda en la BD
				$data['dir_envio'] = (object)$dir_envio;
			} else if (is_integer((int)$dir_envio)){
				//recupera info de la BD
				$consecutivo = (int)$dir_envio;
				$detalle_envio = $this->direccion_envio_model->detalle_direccion($consecutivo, $id_cliente);
				$data['dir_envio'] = $detalle_envio;
			}
		}
		
		//rs_facturación
		$consecutivors = $this->session->userdata('razon_social');
		if (isset($consecutivors)) {
			$detalle_facturacion = $this->direccion_facturacion_model->obtener_rs($consecutivors);
			$data['dir_facturacion']=$detalle_facturacion;		
		}		
		
		//direccion facturación
		$consecutivo_dir = $this->session->userdata('direccion_f');
		if (isset($consecutivo_dir)) {			
			$detalle_direccion = $this->direccion_facturacion_model->obtener_direccion($id_cliente, $consecutivo_dir);
			$data['direccion_f']=$detalle_direccion;			
		}
		
		//Requiere factura
		$data['requiere_factura']=$this->session->userdata('requiere_factura');
		
		//Si acaso hay errores
		if($_POST && $this->registro_errores) {
			$data['reg_errores'] = $this->registro_errores;
		}		
		
		//echo "direcciones: ". $this->id_direccion_envio. ", " . $this->id_direccion_facturacion;
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
				
				//echo "El pago se realizará aquí. CVV: ".$_POST['txt_codigo'];
				
				/*Recuperar la info gral. de la orden*/
				$id_cliente 	= $this->id_cliente;
				//forma pago
				$consecutivo 	= $this->session->userdata('tarjeta') ? $this->session->userdata('tarjeta') : $this->session->userdata('deposito');				
				$id_promocionIn = $this->session->userdata('promocion')->id_promocionIn;
				$digito 		= (isset($_POST['txt_codigo'])) ? $_POST['txt_codigo'] : 0;
				
				// Informaciòn de la Orden //
				$informacion_orden = new stdClass;
				$informacion_orden->id_clienteIn = $id_cliente;
				$informacion_orden->consecutivo_cmsSi = $consecutivo;
				$informacion_orden->id_promocionIn = $id_promocionIn;
				$informacion_orden->digito = $digito;
				/*
				$informacion_orden = new InformacionOrden(
					$id_cliente,
					$consecutivo,		//de la TC
					$id_promocionIn,	
					$digito				//CVV
				);
				 * */

				// Si la información esta en la Session //
				$tipo_pago = self::$TIPO_PAGO['Otro'];	//ninguno válido al inicio
				
				//Configuración de la forma de pago y solicitud de cobro a CCTC
				
				///////////////pago con Depósito Bancario
				if ($this->session->userdata('deposito')) {			
					//el usuario de ecommerce será el que se registre para el cobro con esta forma de pago					
					$id_cliente = $this->id_cliente;	//self::User_Ecommerce;						
					
					//para el registro de la compra en ecommerce
					$tipo_pago = self::$TIPO_PAGO['Deposito_Bancario'];
					//para que se muestre el mensaje de pago con deposito bancario
					$data['deposito'] = TRUE;
					//$id_forma_pago = 0;
										
					//echo " tipo pago depósito: " . $tipo_pago;
					
					//Registrar la orden de compra y el detalle del pago con depósito 
					$id_compra = $this->registrar_orden_compra($id_cliente, $id_promocionIn, $tipo_pago);
					
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
										   	       				
													if ($this->session->userdata('promociones') && $this->session->userdata('promocion')) {			
														$articulos = $this->session->userdata('articulos');
														$total = 0;
														if (!empty($articulos)) {
															foreach ($articulos as $a) 
																$total += $a['tarifaDc'];
														}
												 
														if ($this->session->userdata('promocion')) 																	 
															if (!empty($articulos))
																foreach($articulos as $articulo) {
																	$desc_promo = '';
																	if( strstr($this->session->userdata('promocion')->descripcionVc, '|' )){
																		$mp=explode('|',$this->session->userdata('promocion')->descripcionVc);
																		$nmp=count($mp);
																		if($nmp==2){
																			$desc_promo = $mp[0];		
																		}	
																		else if($nmp==3){
																			$desc_promo = $mp[1];
																		}
																	}				
																	else{
																		$desc_promo = $this->session->userdata('promocion')->descripcionVc;
																	}																	
																																		
												  				    $mensaje.="<tr>
																		           <td colspan='2'>".$desc_promo."<br />".
																		           $articulo['tipo_productoVc'] . ", " . $articulo['medio_entregaVc']."
																		           </td>	
																				   <td>&nbsp;</td>				
																				   <td align='right'>$".	
																				       number_format($articulo['tarifaDc'],2,'.',',')."&nbsp;".$articulo['monedaVc']."
																				   </td>
																				</tr>";														
																}																																																																																	
														}
																										   	       										   	       
				   	       $mensaje.= 			  "<tr>
										   	           <td colspan='2'>&nbsp;
										   	           </td>
										   	           <td align='right'>Sub-total:
										   	           </td>
										   	           <td align='right'>$".number_format($total,2,'.',',')."&nbsp;".$articulo['monedaVc']."
										   	           </td>
										   	       </tr>
										   	       <tr>
										   	           <td colspan='2'>&nbsp;
										   	           </td>
										   	           <td align='right'>I.V.A
										   	           </td>
										   	           <td align='right'>$0.00&nbsp;".$articulo['monedaVc']."
										   	           </td>
										   	       </tr>
										   	       <tr>
										   	           <td colspan='2' width='325px'>&nbsp;
										   	           </td>
										   	           <td align='right' width='180px'><b>Total de la orden</b>
										   	           </td>
										   	           <td align='right' width='95px'><b>$".number_format($total,2,'.',',')."&nbsp;".$articulo['monedaVc']."</b>
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
											   	       <td valign='top' style='width: 300px;'>";
											   	       $dir_envio = (int)$this->session->userdata('dir_envio');
											   	       if(!empty($dir_envio)){
											   	       	   $det_env = $this->direccion_envio_model->detalle_direccion($dir_envio, $id_cliente);
											   	       	   $mensaje.="<b>Dirección de envío:</b><br />";
											   	       	   $mensaje.=$det_env->address1. " " .$det_env->address2. " " . (isset($det_env->address4) ? ", Int. ".$det_env->address4 : "") . "<br/>".
											   	       	         $det_env->zip."<br/>".
																 $det_env->city."<br/>".
																 $det_env->state."<br/>".
																 $det_env->codigo_paisVc."<br/>".
																 $det_env->phone."&nbsp;";	
											   	       }
											   	       										   	       													
								$mensaje.=   	      "</td>
											   	       <td valign='top' style='width: 300px;'>
											   	           <b>Requiere factura:</b>&nbsp;".$this->session->userdata('requiere_factura')."<br /><br />";
											   	       
											   	       	   if($this->session->userdata('requiere_factura')=='si'){
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
						
						//mandar correo al cliente con el formato de arlette para notificarle lo que debe hacer
						$envio_correo = $this->enviar_correo("Notificación de compra con depósito bancario", $mensaje);
						
						//redirección a la URL callback con el código nuevo
						 
						//registrar el estatus de la compra correspondiente a la notificación final, esto es después del proceso nocturno
						//$envio_correo = $this->registrar_estatus_compra($id_compra, (int)$id_cliente, self::$ESTATUS_COMPRA['ENVIO_CORREO']);
						
						//manejo envío correo
						if (!$envio_correo) {	//Error
							redirect('mensaje/'.md5(4), 'refresh');
						}
						
						//Manejo del flujo para el depósito bancario
						$data['url_back'] = $this->datos_urlback("approved", $id_compra);
						
						$data['pago_deposito']['id_compra'] = $id_compra;
						//$data['deposito']['importe'] = $id_compra;
						//$data['deposito']['clave_promocion'] = $id_compra;
						 
						//Muestra la pantalla de resultado de cobro
						$this->cargar_vista('', 'orden_compra', $data);
						
						if($data['url_back']['estatus']!=0){
							$this->session->sess_destroy();	
						}						
						
					} else {
						redirect('mensaje/'.md5(2), 'refresh');
					}
					
				} else if (is_array($this->session->userdata('tarjeta'))) {		//////////// pago con tarjetas no guardada y que está en sesión
				
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
					
					//consecutivo de la información del pago es 1 para que pase el cobro
					$informacion_orden->consecutivo_cmsSi = $tc['id_TCSi'];		//Debe ser 0
					/*
					$tc_soap = new Tc(
						$tc['id_clienteIn'],
						$tc['id_TCSi'],
						$tc['id_tipo_tarjetaSi'],
						$tc['nombre_titularVc'],
						$tc['apellidoP_titularVc'],
						$tc['apellidoM_titularVc'],
						$tc['terminacion_tarjetaVc'],
						$tc['mes_expiracionVc'],
						$tc['anio_expiracionVc']
					);
					*/
					//si es Visa o Master card
					$tipo_pago = self::$TIPO_PAGO['Prosa'];	
					
					$amex_soap = NULL;
					
					if ($detalle_tarjeta['tc']['id_tipo_tarjetaSi'] == 1) { //es AMERICAN EXPRESS
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
							/*
							$amex_soap = new Amex(
								$amex['id_clienteIn'],
								$amex['id_TCSi'],
								$amex['nombre_titularVc'],
								$amex['apellidoP_titularVc'],
								$amex['apellidoM_titularVc'],
								$amex['pais'],
								$amex['codigo_postal'],
								$amex['calle'],
								$amex['ciudad'],
								$amex['estado'],
								$amex['mail'],
								$amex['telefono']
							);
							 * */
						}
						
						//si es AMEX
						$tipo_pago = self::$TIPO_PAGO['American_Express'];
					}

					//Pruebas orden compra
					/*
					echo " tipo pago en sesión: " . $tipo_pago;
					echo "<pre>";
					var_dump($informacion_orden);
					echo "</pre>";
					exit();
					*/								
										
					//intentamos el Pago con pasando los objetos a CCTC //
					try {
						//registro inicial de la compra, si falla, redirecciona
						$id_compra = $this->registrar_orden_compra($id_cliente, $id_promocionIn, $tipo_pago);
						
						if (!$id_compra) {			//Si falla el registro inicial de la compra en CCTC
							redirect('mensaje/'.md5(3), 'refresh');
						}
						
						//petición de pago a través de la interfase, el resultado ya es un objeto
						$simple_result = $this->solicitar_pago_CCTC_objetos($tc_soap, $amex_soap, $informacion_orden);
						
						#### Ajuste de Interfase, ya no se ocupará, 23.07.2012
						/*
						$cliente = new SoapClient("https://cctc.gee.com.mx/ServicioWebCCTC/ws_cms_cctc.asmx?WSDL");
						//$cliente = new SoapClient("http://localhost:11622/ServicioWebPago/ws_cms_cctc.asmx?WSDL");
						
						$parameter = array('informacion_tarjeta' => $tc_soap, 'informacion_amex' => $amex_soap, 'informacion_orden' => $informacion_orden);
						
						$obj_result = $cliente->PagarTC($parameter);
						
						//Resultado de la petición de cobro a CCTC
						$simple_result = $obj_result->PagarTCResult;
						*/
						
						//Registro del estatus de la respuesta de CCTC
						$this->registrar_estatus_compra($id_compra, (int)$id_cliente, self::$ESTATUS_COMPRA['RESPUESTA_CCTC']);
						
						//Registro de la respuesta de CCTC de la compra en ecommerce
						$info_detalle_pago_tc = array('id_compraIn'=> $id_compra, 'id_clienteIn' => $id_cliente, 'id_TCSi' => $tc['id_TCSi'], 
														'id_transaccionBi' => $simple_result->id_transaccionNu, 'respuesta_bancoVc' => $simple_result->respuesta_banco,
														'codigo_autorizacionVc' => $simple_result->codigo_autorizacion, 'mensaje' => $simple_result->mensaje);
														
						//Registro de la respuesta del pago en ecommerce
						$this->registrar_detalle_pago_tc($info_detalle_pago_tc);
						$this->registrar_estatus_compra($id_compra, (int)$id_cliente, self::$ESTATUS_COMPRA['REGISTRO_PAGO_ECOMMERCE']);
						
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
													   
											   	      
											   	       $dir_envio = (int)$this->session->userdata('dir_envio');
											   	       if(!empty($dir_envio)){
											   	       	   $det_env = $this->direccion_envio_model->detalle_direccion($dir_envio, $id_cliente);
											   	       	   $mensaje.="<b>Dirección de envío:</b><br />";
											   	       	   $mensaje.=$det_env->address1. " " .$det_env->address2. " " . (isset($det_env->address4) ? ", Int. ".$det_env->address4 : "") . "<br/>".
											   	       	         $det_env->zip."<br/>".
																 $det_env->city."<br/>".
																 $det_env->state."<br/>".
																 $det_env->codigo_paisVc."<br/>".
																 $det_env->phone."&nbsp;";	
											   	       }
											   	       										   	       													
								$mensaje.=   	      "</td>
											   	       <td valign='top' style='width: 300px;'>
											   	           <b>Requiere factura:</b>&nbsp;".$this->session->userdata('requiere_factura')."<br /><br />";
											   	       
											   	       	   if($this->session->userdata('requiere_factura')=='si'){
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
										   	       				
													if ($this->session->userdata('promociones') && $this->session->userdata('promocion')) {			
														$articulos = $this->session->userdata('articulos');
														$total = 0;
														if (!empty($articulos)) {
															foreach ($articulos as $a) 
																$total += $a['tarifaDc'];
														}
												 
														if ($this->session->userdata('promocion')) 																	 
															if (!empty($articulos))
																foreach($articulos as $articulo) {
																	$desc_promo = '';
																	if( strstr($this->session->userdata('promocion')->descripcionVc, '|' )){
																		$mp=explode('|',$this->session->userdata('promocion')->descripcionVc);
																		$nmp=count($mp);
																		if($nmp==2){
																			$desc_promo = $mp[0];		
																		}	
																		else if($nmp==3){
																			$desc_promo = $mp[1];
																		}
																	}				
																	else{
																		$desc_promo = $this->session->userdata('promocion')->descripcionVc;
																	}
																																		
												  				    $mensaje.="<tr>
																		           <td colspan='2'>".$desc_promo."<br />".
																		           $articulo['tipo_productoVc'] . ", " . $articulo['medio_entregaVc']."
																		           </td>	
																				   <td>&nbsp;</td>				
																				   <td align='right'>$".	
																				       number_format($articulo['tarifaDc'],2,'.',',')."&nbsp;".$articulo['monedaVc']."
																				   </td>
																				</tr>";														
																}																																																																																	
														}
																										   	       										   	       
				   	       $mensaje.= 			  "<tr>
										   	           <td colspan='2'>&nbsp;
										   	           </td>
										   	           <td align='right'>Sub-total:
										   	           </td>
										   	           <td align='right'>$".number_format($total,2,'.',',')."&nbsp;".$articulo['monedaVc']."
										   	           </td>
										   	       </tr>
										   	       <tr>
										   	           <td colspan='2'>&nbsp;
										   	           </td>
										   	           <td align='right'>I.V.A
										   	           </td>
										   	           <td align='right'>$0.00&nbsp;".$articulo['monedaVc']."
										   	           </td>
										   	       </tr>
										   	       <tr>
										   	           <td colspan='2' width='325px'>&nbsp;
										   	           </td>
										   	           <td align='right' width='180px'><b>Total de la orden</b>
										   	           </td>
										   	           <td align='right' width='95px'><b>$".number_format($total,2,'.',',')."&nbsp;".$articulo['monedaVc']."</b>
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

						$envio_correo = FALSE;
						///Envío de correo sólo en caso de que el cobro haya sido exitoso
						if(strtolower($simple_result->respuesta_banco)=="approved"){							
							$envio_correo = $this->enviar_correo("Confirmación de compra", $mensaje);
							$estatus_correo = $this->registrar_estatus_compra($id_compra, (int)$id_cliente, self::$ESTATUS_COMPRA['ENVIO_CORREO']);
							
							//manejo envío correo
							if (!($envio_correo && $estatus_correo)) {	//Error
								redirect('mensaje/'.md5(4), 'refresh');
							}
						}						
																	
						//obtiene os datos que se van a regresar al sitio	(Teo)																						
						$data['url_back'] = $this->datos_urlback($simple_result->respuesta_banco, $id_compra);
																		
						$data['resultado'] = $simple_result;	
																	
						$this->cargar_vista('', 'orden_compra', $data);
						if($data['url_back']['estatus']!=0){
							$this->session->sess_destroy();	
						}						
						
					} catch (SoapFault $exception) { 
						//echo $exception;  
						//echo '<br/>error: <br/>'.$exception->getMessage();
						return NULL;
					}
					
				} else { // La informacion esta en la Base de Datos Local //
					//echo "La informacion esta en la Base de Datos Local";
					
					$detalle_tarjeta = $this->forma_pago_model->detalle_tarjeta($consecutivo, $id_cliente);
					$tc = $detalle_tarjeta;	//trae la tc
					$data['tc']=$detalle_tarjeta;
					
					$tipo_pago = ($tc->id_tipo_tarjetaSi == self::Tipo_AMEX) ? self::$TIPO_PAGO['American_Express'] : self::$TIPO_PAGO['Prosa'];
					
					//echo " tipo pago: " . $tipo_pago;
					//exit();
					/*
					echo " tipo pago de la DB: " . $tipo_pago;
					echo "<pre>";
					var_dump($informacion_orden);
					echo "</pre>";
					 * */
					
									
					// Intentamos el Pago con los Id's en  CCTC //
					try {
						//Registro inicial de la compra						
						$id_compra = $this->registrar_orden_compra($id_cliente, $id_promocionIn, $tipo_pago);
						
						if (!$id_compra) {	//Si falla el registro inicial de la compra en CCTC
							redirect('mensaje/'.md5(3), 'refresh');
						}
						
						//petición de pago a través de la interfase, el resultado ya es un objeto
						$simple_result = $this->solicitar_pago_CCTC_ids($informacion_orden);
						
						#### Ajuste de Interfase, ya no se ocupará, 23.07.2012
						/*
						$cliente = new SoapClient("https://cctc.gee.com.mx/ServicioWebCCTC/ws_cms_cctc.asmx?WSDL");
						//$cliente = new SoapClient("http://localhost:11622/ServicioWebPago/ws_cms_cctc.asmx?WSDL");
						  
						$parameter = array('informacion_orden' => $informacion_orden);
						
						//Intento de cobro en CCTC
						$obj_result = $cliente->PagarTcUsandoId($parameter);
						
						//Resultado de la petición de cobro a CCTC
						$simple_result = $obj_result->PagarTcUsandoIdResult;
						*/
						//Registro del estatus de la respuesta de CCTC
						$this->registrar_estatus_compra($id_compra, (int)$id_cliente, self::$ESTATUS_COMPRA['RESPUESTA_CCTC']);
					
						//Registro de la respuesta de CCTC de la compra en ecommerce
						$info_detalle_pago_tc = array('id_compraIn'=> $id_compra, 'id_clienteIn' => $id_cliente, 'id_TCSi' => $consecutivo, 
														'id_transaccionBi' => $simple_result->id_transaccionNu, 'respuesta_bancoVc' => $simple_result->respuesta_banco,
														'codigo_autorizacionVc' => $simple_result->codigo_autorizacion, 'mensaje' => $simple_result->mensaje);

																				
						//Registro de la respuesta del pago en ecommerce
						$this->registrar_detalle_pago_tc($info_detalle_pago_tc);
						$this->registrar_estatus_compra($id_compra, (int)$id_cliente, self::$ESTATUS_COMPRA['REGISTRO_PAGO_ECOMMERCE']);
						
						
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
										               <th colspan='2' align='left'>Pago, envío y facturación
										               </th>
										           </tr>    
										       </thead>
										       <tbody>
											   	   <tr>
											   	       <td valign='top' style='width: 300px;'>
											   	           <b>Método de pago:</b><br />".$detalle_tarjeta->descripcionVc." con terminación ".$detalle_tarjeta->terminacion_tarjetaVc."<br />
											   	           <b>Código de autorización:</b>&nbsp;&nbsp;".$simple_result->codigo_autorizacion."<br />
											   	           <b>Fecha de autorización:</b>&nbsp;&nbsp;".mdate('%d-%m-%Y')."<br /><br />";
													   
											   	       $dir_envio = (int)$this->session->userdata('dir_envio');
											   	       if(!empty($dir_envio)){
											   	       	   $det_env = $this->direccion_envio_model->detalle_direccion($dir_envio, $id_cliente);
											   	       	   $mensaje.="<b>Dirección de envío:</b><br />";
											   	       	   $mensaje.=$det_env->address1. " " .$det_env->address2. " " . (isset($det_env->address4) ? ", Int. ".$det_env->address4 : "") . "<br/>".
											   	       	         $det_env->zip."<br/>".
																 $det_env->city."<br/>".
																 $det_env->state."<br/>".
																 $det_env->codigo_paisVc."<br/>".
																 $det_env->phone."&nbsp;";	
											   	       }
											   	       										   	       													
								$mensaje.=   	      "</td>
											   	       <td valign='top' style='width: 300px;'>
											   	           <b>Requiere factura:</b>&nbsp;".$this->session->userdata('requiere_factura')."<br /><br />";
											   	       
											   	       	   if($this->session->userdata('requiere_factura')=='si'){
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
										   	       				
													if ($this->session->userdata('promociones') && $this->session->userdata('promocion')) {			
														$articulos = $this->session->userdata('articulos');
														$total = 0;
														if (!empty($articulos)) {
															foreach ($articulos as $a) 
																$total += $a['tarifaDc'];
														}
												 
														if ($this->session->userdata('promocion')) 																	 
															if (!empty($articulos))
																foreach($articulos as $articulo) {
																	$desc_promo = '';
																	if( strstr($this->session->userdata('promocion')->descripcionVc, '|' )){
																		$mp=explode('|',$this->session->userdata('promocion')->descripcionVc);
																		$nmp=count($mp);
																		if($nmp==2){
																			$desc_promo = $mp[0];		
																		}	
																		else if($nmp==3){
																			$desc_promo = $mp[1];
																		}
																	}				
																	else{
																		$desc_promo = $this->session->userdata('promocion')->descripcionVc;
																	}
																																		
												  				    $mensaje.="<tr>
																		           <td colspan='2'>".$desc_promo."<br />".
																		           $articulo['tipo_productoVc'] . ", " . $articulo['medio_entregaVc']."
																		           </td>	
																				   <td>&nbsp;</td>				
																				   <td align='right'>$".	
																				       number_format($articulo['tarifaDc'],2,'.',',')."&nbsp;".$articulo['monedaVc']."
																				   </td>
																				</tr>";														
																}																																																																																	
														}
																										   	       										   	       
				   	       $mensaje.= 			  "<tr>
										   	           <td colspan='2'>&nbsp;
										   	           </td>
										   	           <td align='right'>Sub-total:
										   	           </td>
										   	           <td align='right'>$".number_format($total,2,'.',',')."&nbsp;".$articulo['monedaVc']."
										   	           </td>
										   	       </tr>
										   	       <tr>
										   	           <td colspan='2'>&nbsp;
										   	           </td>
										   	           <td align='right'>I.V.A
										   	           </td>
										   	           <td align='right'>$0.00&nbsp;".$articulo['monedaVc']."
										   	           </td>
										   	       </tr>
										   	       <tr>
										   	           <td colspan='2' width='325px'>&nbsp;
										   	           </td>
										   	           <td align='right' width='180px'><b>Total de la orden</b>
										   	           </td>
										   	           <td align='right' width='95px'><b>$".number_format($total,2,'.',',')."&nbsp;".$articulo['monedaVc']."</b>
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
												
						if(strtolower($simple_result->respuesta_banco)=="approved"){							
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
										
						$this->cargar_vista('', 'orden_compra', $data);
												
						if($data['url_back']['estatus']!=0){
							$this->session->sess_destroy();	
						}
						
						//return $simple_result;
					} catch (SoapFault $exception) {
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
	 * Función que realiza la petición a la interfase de cobro que enlaza con CCTC 
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
			curl_setopt($c, CURLOPT_URL, 'http://10.177.78.54/interfase_cctc/interfase.php');
			//curl_setopt($c, CURLOPT_URL, 'http://10.43.29.196/interface_cctc/solicitar_post.php');
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
			curl_setopt($c, CURLOPT_URL, 'http://10.177.78.54/interfase_cctc/interfase.php');
			//curl_setopt($c, CURLOPT_URL, 'http://10.43.29.196/interface_cctc/solicitar_post.php');
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
			/*
			echo "<pre>";
			print_r(json_decode($resultado));
			echo "</pre>";
			exit;
			*/
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
			
			$p = json_decode($param);
			$objetos = $this->ArrayToObject($p);
			echo "<pre>";
			print_r($objetos);
			echo "</pre>";
		    //return $obj;
			//exit;
			*/
			// Inicializamos el CURL / SI no funciona se puede habilitar en el php.ini //
			$c = curl_init();
			// CURL de la URL donde se haran las peticiones //
			curl_setopt($c, CURLOPT_URL, 'http://10.177.78.54/interfase_cctc/interfase.php');
			//curl_setopt($c, CURLOPT_URL, 'http://10.43.29.196/interface_cctc/solicitar_post.php');
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
		$datos['datos_login'] = $this->api->encrypt($id_compra."|".$this->api->decrypt($this->session->userdata('datos_login'),$this->api->key), $this->api->key);
		$datos['urlback'] = $this->session->userdata('sitio')->url_PostbackVc;	
		$datos['estatus']=$estatus_pago;
		$datos['id_compra']=$id_compra;
		/*echo "<pre>";
		print_r($datos);
		echo "</pre>";
		exit();*/
		
		return $datos;			
	} 
	 
	private function registrar_orden_compra($id_cliente, $id_promocion, $tipo_pago)
	{
		//Registrar eb la tabla de ordenes
		$id_compra = 0;
		$id_compra = $this->registrar_compra($id_cliente);
		
		//echo "<br/>cliente: ". $id_cliente ;
		
		if ($id_compra) {
			
			///artiulos de la promoción
			$articulos_compra = array();
			$articulos_compra = $this->orden_compra_model->obtener_articulos_promocion($id_promocion);
			
			foreach ($articulos_compra as $articulo) {
				 //preparar la información para insertar los artículos
				$info_articulos[] = array((int)$articulo->id_articulo, $id_compra, (int)$id_cliente, (int)$id_promocion);
			}
			
			///////forma pago///////
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
			
			$info_pago = array('id_compraIn' => $id_compra, 'id_clienteIn' => (int)$id_cliente, 'id_tipoPagoSi' => $tipo_pago, 'id_TCSi' => $id_TCSi);
			
			///////direccion(es)///////
			$info_direcciones = array();
			
			if ($this->session->userdata('requiere_envio')) {
				//echo "Sí requiere_envio: Si<br/>";
				if ($dir_envio = $this->session->userdata('dir_envio')) {
					//echo "direccion_envio: " . $dir_envio;
					
					$info_direcciones['envio'] = 
						array('id_compraIn' => $id_compra, 'id_clienteIn' => (int)$id_cliente, 'id_consecutivoSi' => (int)$dir_envio, 'address_type' => self::$TIPO_DIR['RESIDENCE']);
				} else {
					//No se efectúa la petición por que falta el dato de envío
					echo "Error: requiere dirección de envío";
					return FALSE;
				}
				
			} else {
				//si n orequiere se vacía
				$info_direcciones['envio'] = array();
			}
			
			if ($this->session->userdata('requiere_factura') !== "no") {
				//echo "Sí requiere factura: <br/>".$this->session->userdata('requiere_factura');
				$dir_facturacion = $this->session->userdata('direccion_f');
				$razon_social = $this->session->userdata('razon_social');
				
				if ($dir_facturacion && $razon_social) {
					$info_direcciones['facturacion'] = 
						array('id_compraIn' => $id_compra, 'id_clienteIn' => (int)$id_cliente, 'id_consecutivoSi' => (int)$dir_facturacion, 'id_razonSocialIn' => (int)$razon_social , 'address_type' => self::$TIPO_DIR['BUSINESS']);
				} else {
					echo "Error: falta la dirección de facturación";
					return FALSE;
				}
			} else {
				//si n orequiere se vacía
				$info_direcciones['facturacion'] = array();
			}
			
			///////estatus de registro de la compra///////
			$estatus = ($tipo_pago == self::$TIPO_PAGO['Deposito_Bancario']) ? self::$ESTATUS_COMPRA['PAGO_DEPOSITO_BANCARIO'] : self::$ESTATUS_COMPRA['SOLICITUD_CCTC'];
			$info_estatus = array('id_compraIn' => $id_compra, 'id_clienteIn' => (int)$id_cliente, 'id_estatusCompraSi' => $estatus);
			
			/////////////registrar compra inicial en BD/////// 
			$registro_orden = $this->orden_compra_model->registrar_compra_inicial($info_articulos, $info_pago, $info_direcciones, $info_estatus);
			//echo "compra: " . $id_compra;
			//exit();
			return $id_compra;
		} else {
			//Error en el registro de la compra
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
		/*
		echo "cvv ok: " . preg_match('/^[0-9]{3,4}$/', $_POST['txt_codigo']);
		var_dump($datos);
		var_dump($this->registro_errores);
		exit();
		*/
		return $datos;
	}
	
	/**
	 * Envía un correo 
	 */
	private function enviar_correo($asunto, $mensaje) {
		$headers = "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
	    $headers .= "From: GexWeb<soporte@expansion.com.mx>\r\n";
		
		$email = $this->session->userdata('email');
					
		//return ($email && mail($email, $asunto, $mensaje));
		return mail($email, "=?UTF-8?B?".base64_encode($asunto)."?=", $mensaje, $headers);
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
			$destino =  "forma_pago";
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
