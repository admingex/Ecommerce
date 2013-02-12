<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include('api.php');
class Suscripcion_Express extends CI_Controller {
	
	var $title = 'Reporte de Usuarios'; 		// Capitalize the first letter
	var $subtitle = 'Reporte de Usuarios'; 	// Capitalize the first letter
	
	public static $FORMA_PAGO = array(
		1 =>	"Prosa", 
		2 =>	"American Express", 
		3 =>	"Deposito Bancario",
		4 =>	"Otro"
	);
	
	public static $TIPO_DIR = array(
		"RESIDENCE"	=> 0, 
		"BUSINESS"	=> 1, 
		"OTHER"		=> 2
	);
	
		
	function __construct(){
        parent::__construct();						
		$this->load->model('direccion_facturacion_model', 'direccion_facturacion_model', true);
		$this->load->model('direccion_envio_model', 'direccion_envio_model', true);
		$this->load->model('forma_pago_model', 'forma_pago_model', true);
		$this->load->model('suscripcion_express_model', 'suscripcion_express_model', true);		
				
		$this->load->helper('date');
		$this->api = New Api();										
    }
	
	public function index(){
		$this->datos();									
	}	
	
	public function datos($sitio = "", $canal = "", $promocion = ""){
		$data['title']='Suscripción Express';
		
		if(is_numeric($sitio) && is_numeric($canal) && is_numeric($promocion)){
			
			$lista_paises_think = $this->direccion_facturacion_model->listar_paises_think();
			$data['lista_paises_think'] = $lista_paises_think;
			
			
			$this->session->set_userdata('id_sitio', $sitio);
			$this->session->set_userdata('id_canal', $canal);
			$this->session->set_userdata('id_promocion', $promocion);
			$this->session->set_userdata('promociones', array(array('id_sitio'=>$sitio, 'id_canal'=>$canal, 'id_promocion'=>$promocion)));
			$promoexp= array(array('id_sitio'=>$sitio, 'id_canal'=>$canal, 'id_promocion'=>$promocion));					
			$data['detalle_promociones']=$this->api->obtiene_articulos_y_promociones($promoexp);						
			
			if($_POST){
				
				$datos = $this->get_datos_registro();
				
				if(empty($this->registro_errores)){
										
					$this->session->set_userdata('email', $datos['email']);					
					$cte = $this->suscripcion_express_model->verifica_registro_email($datos['email']);
					
					if($cte->num_rows() > 0){							
						$ctereg=end($cte->result_object());												
						$this->session->set_userdata('id_cliente', $ctereg->id_clienteIn);
						
						$datos['direccion']['address_type'] = self::$TIPO_DIR['RESIDENCE'];		//address_type
						$datos['direccion']['id_clienteIn']= $ctereg->id_clienteIn;
						$dirreg = $this->suscripcion_express_model->existe_direccion($datos['direccion']);
						
						
						if($dirreg->num_rows() > 0){
							$dir=end($dirreg->result_object());							
							$this->session->set_userdata('consecutivo', $dir->id_consecutivoSi);
							$pago = site_url('suscripcion_express/pago');
							header("Location: $pago");
								
						}
						else{
							$consecutivo = $this->suscripcion_express_model->get_consecutivo_dir($ctereg->id_clienteIn);	
							$datos['direccion']['id_consecutivoSi']= $consecutivo;
							$datos['direccion']['address_type'] = self::$TIPO_DIR['RESIDENCE'];		//address_type
							$datos['direccion']['id_clienteIn']= $ctereg->id_clienteIn;
								
							$regdir = $this->suscripcion_express_model->insertar_direccion($datos['direccion']);
							if($regdir === 1){
								$this->session->set_userdata('consecutivo', $consecutivo);	
								$pago = site_url('suscripcion_express/pago');
								header("Location: $pago");
							}	
							else{
								$data['mensaje']="Ocurrio un problema al hacer el registro de dirección, intentelo nuevamente";
								$this->load->view('templates/header', $data);
								$this->load->view('mensaje', $data);	
							}		
														
						}																		  							
														
					}
					else{
						
						$id_cliente = $this->suscripcion_express_model->next_cliente_id();	//id del cliente
						$datos['id_clienteIn'] = $id_cliente;
						####
						####
						#### falta armar el formato del correo para enviar el password y notificar el registro
						####
						####
						//paswwor a enviar
						$pass = $this->genera_pass();
						$datos['password'] = md5($datos['email']."|".$pass);
						
						$regcte = $this->suscripcion_express_model->registro_cliente($datos); 
						if($regcte === 1){
							$this->session->set_userdata('id_cliente', $id_cliente);
								$consecutivo = $this->suscripcion_express_model->get_consecutivo_dir($id_cliente);	
								$datos['direccion']['id_consecutivoSi']= $consecutivo;
								$datos['direccion']['address_type'] = self::$TIPO_DIR['RESIDENCE'];		//address_type
								$datos['direccion']['id_clienteIn']= $id_cliente;
								
								$regdir = $this->suscripcion_express_model->insertar_direccion($datos['direccion']);
								if($regdir === 1){
									$this->session->set_userdata('consecutivo', $consecutivo);	
									$pago = site_url('suscripcion_express/pago');
									header("Location: $pago");
								}	
								else{
									$data['mensaje']="Ocurrio un problema al hacer el registro de dirección, intentelo nuevamente";
									$this->load->view('templates/header', $data);
									$this->load->view('mensaje', $data);	
								}													
														
						}						 
						else{
							$data['mensaje']="Ocurrio un problema al hacer el registro, intentelo nuevamente";
							$this->load->view('templates/header', $data);
							$this->load->view('mensaje', $data);	
						}						
					}						 
					 
				}
				else{
					$data['registro_errores']=$this->registro_errores;
					$this->cargar_vista('', 'suscripcion_express/registro_cliente' , $data);																
				}
						
			}
			else{
				$this->cargar_vista('', 'suscripcion_express/registro_cliente' , $data);								
			}
						
		}	
		else{
			#promocion inexistente				
			$data['mensaje']="Información insuficiente para completar la orden";
			$this->load->view('templates/header', $data);
			$this->load->view('mensaje', $data); 			
		}						
		
	}
	
	public function pago(){
		$data['title']='Suscripción express';			
		
		$sitio = $this->session->userdata('id_sitio');
		$canal = $this->session->userdata('id_canal');
		$promocion = $this->session->userdata('id_promocion'); 
		
		$promoexp= array(array('id_sitio'=>$sitio, 'id_canal'=>$canal, 'id_promocion'=>$promocion));							
		$data['detalle_promociones']=$this->api->obtiene_articulos_y_promociones($promoexp);
		
		echo "<pre>";
			print_r($data['detalle_promociones']);
		echo "</pre>";	
																			  
		$lista_paises_amex = $this->forma_pago_model->listar_paises_amex();
		$data['lista_paises_amex'] = $lista_paises_amex;
		
		$data['promo'] = $this->api->obtener_detalle_promo($sitio, $canal, $promocion );
		$data['lista_tipo_tarjeta'] = $this->forma_pago_model->listar_tipos_tarjeta();													
													
		if($_POST){
			
			$tarjeta = $this->get_datos_tarjeta();
			
			if(empty($this->reg_errores)){
				echo "<pre>";							
					print_r($_POST);
				echo "</pre>";
				echo "<pre>";	
					print_r($this->session->all_userdata());
				echo "</pre>";
				echo "<pre>";	
					print_r($tarjeta);
				echo "</pre>";
					
			}
			else{
				$data['reg_errores'] = $this->reg_errores;					
				$this->cargar_vista('', 'suscripcion_express/pago' , $data);				
			}												 												
		}		
		else{			
			$this->cargar_vista('', 'suscripcion_express/pago' , $data);						 
		}
		
	}

	public function get_info_sepomex($cp = 0)
	{
		
		
		if (!$cp)
			$cp = $this->input->post('codigo_postal');
		
		
		$resultado = $this->consulta_sepomex($cp);
		
		header('Content-Type: application/json',true);
		echo json_encode($resultado);
	}		
	
	private function consulta_sepomex($codigo_postal)
	{
		$resultado = array();
		
		try
		{
			$resultado['sepomex'] = $this->direccion_envio_model->obtener_direccion_sepomex($codigo_postal)->result();
			$resultado['success'] = true;
			$resultado['msg'] = "Ok";
			
			return $resultado;
		}
		catch (Exception $e)
		{
			$resultado['exception'] =  $exception;
			$resultado['msg'] = $exception->getMessage();
			$resultado['error'] = true;
			return $resultado;	
		}
				
	}	

	private function get_datos_registro()
	{
		$datos = array();
		
		if(array_key_exists('txt_nombre', $_POST)) {
			if(preg_match('/^[A-Z \'.-áéíóúÁÉÍÓÚÑñ]{2,30}$/i', $_POST['txt_nombre'])) { 
				$datos['salutation'] = $_POST['txt_nombre'];
			} else {
				$this->registro_errores['txt_nombre'] = '<div class="error">Por favor ingresa tu nombre</div>';
			}
		}
		if(array_key_exists('txt_apellidoPaterno', $_POST)) {
			if(preg_match('/^[A-Z \'.-áéíóúÁÉÍÓÚÑñ]{1,30}$/i', $_POST['txt_apellidoPaterno'])) { 
				$datos['fname'] = $_POST['txt_apellidoPaterno'];
			} else {
				$this->registro_errores['txt_apellidoPaterno'] = '<div class="error">Por favor ingresa tu apellido paterno</div>';
			}
		}
		if(array_key_exists('txt_apellidoMaterno', $_POST)) {
			if(preg_match('/^[A-Z \'.-áéíóúÁÉÍÓÚÑñ]{1,30}$/i', $_POST['txt_apellidoMaterno'])) { 
				$datos['lname'] = $_POST['txt_apellidoMaterno'];
			}
		}
		if (filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {			
			$datos['email'] = htmlspecialchars(trim($_POST['email']));
		} else {			
			$this->registro_errores['email'] = '<div class="error2">Por favor ingresa un correo electrónico <br />válido. Ejemplo: nombre@dominio.mx</div>';
		}	
		if (array_key_exists('calle', $_POST)) {
			if(preg_match('/^[A-Z0-9áéíóúÁÉÍÓÚÑñ \'.-]{1,50}$/i', $_POST['calle'])) {
				$datos['direccion']['address1'] = $_POST['calle'];
			}
			else {
				$this->registro_errores['calle'] = '<span class="error">Por favor ingresa una calle</span>';
			}
		}	
		if (array_key_exists('num_ext', $_POST)) {
			if(preg_match('/^[A-Z0-9 -]{1,50}$/i', $_POST['num_ext'])) {
				$datos['direccion']['address2'] = $_POST['num_ext'];
			}
			else {
				$this->registro_errores['num_ext'] = '<span class="error">Por favor ingresa el número exterior</span>';
			}
		}	
		
		if (!empty($_POST['num_int'])) {
			if(preg_match('/^[A-Z0-9 -]{1,50}$/i', $_POST['num_int'])) {
				$datos['direccion']['address4'] = $_POST['num_int'];
			} 
			else {
				$this->registro_errores['num_int'] = '<span class="error">Por favor ingresa el número interior</span>';
			}
		} 
		else {
				$datos['direccion']['address4'] = NULL;
		}
		
		if (!empty($_POST['pais'])) {
			$datos['direccion']['codigo_paisVc'] = $_POST['pais'];
		}
		else {
				$this->reg_errores['sel_pais'] = '<span class="error">Por favor selecciona el pa&iacute;s</span>';
		}
		
		if (array_key_exists('cp', $_POST)) {
			//regex usada en js
			if (preg_match('/^([1-9]{2}|[0-9][1-9]|[1-9][0-9])[0-9]{3}$/', $_POST['cp'])) {
				$datos['direccion']['zip'] = $_POST['cp'];
			} 
			else {
				$this->registro_errores['cp'] = '<span class="error2">Por favor ingresa un código postal de 5 dígitos</span>';
			}
		}	
		
		if (array_key_exists('colonia', $_POST) && trim($_POST['colonia']) != ""){
			$datos['direccion']['address3'] = $_POST['colonia'];
		}
		else {
			$this->registro_errores['colonia'] = '<span class="error">Por favor ingresa la colonia</span>';
		}
		
		if (array_key_exists('ciudad', $_POST) && !empty($_POST['ciudad'])) {
			$datos['direccion']['city'] = $_POST['ciudad'];
		}
		else {
			$this->registro_errores['ciudad'] = '<span class="error">Por favor ingresa la ciudad</span>';
		}
		if (array_key_exists('estado', $_POST) && !empty($_POST['estado'])) {
			$datos['direccion']['state'] = $_POST['estado'];
		}
		else {
			$this->registro_errores['estado'] = '<span class="error">Por favor ingresa el estado</span>';
		}
	
		return $datos;
	}

	private function get_datos_tarjeta(){
		$datos = array();
		$tipo = '';
		//echo "tipo : ". $tipo;
		//no se usa la funcion de escape '$this->db->escape()', por que en la inserción ya se incluye 
		
			if (array_key_exists('sel_tipo_tarjeta', $_POST)) {
				$datos['tc']['id_tipo_tarjetaSi'] = $_POST['sel_tipo_tarjeta'];
				$tipo = $_POST['sel_tipo_tarjeta'];
			}
			
			if (array_key_exists('txt_numeroTarjeta', $_POST)) {
				if ($this->validar_tarjeta($datos['tc']['id_tipo_tarjetaSi'], trim($_POST['txt_numeroTarjeta']))) {					 
					$datos['tc']['terminacion_tarjetaVc'] = trim($_POST['txt_numeroTarjeta']);	//substr($_POST['txt_numeroTarjeta'], strlen($_POST['txt_numeroTarjeta']) - 4);
				} else {
					$this->reg_errores['txt_numeroTarjeta'] = 'Por favor ingrese un numero de tarjeta v&aacute;lido';
				}
			}
			
			if (array_key_exists('txt_nombre', $_POST)) {
				if(preg_match('/^[A-ZáéíóúÁÉÍÓÚÑñ \'.-]{1,30}$/i', $_POST['txt_nombre'])) { 
					$datos['tc']['nombre_titularVc'] = $_POST['txt_nombre'];
					if ($tipo == 1) {
						$datos['amex']['nombre_titularVc'] = $_POST['txt_nombre'];
					}
				} else {
					$this->reg_errores['txt_nombre'] = 'Ingresa tu nombre correctamente';
				}
			}
			if (array_key_exists('txt_apellidoPaterno', $_POST)) {
				if(preg_match('/^[A-ZáéíóúÁÉÍÓÚÑñ \'.-]{1,30}$/i', $_POST['txt_apellidoPaterno'])) { 
					$datos['tc']['apellidoP_titularVc'] = $_POST['txt_apellidoPaterno'];
					if ($tipo == 1) {
						$datos['amex']['apellidoP_titularVc'] = $_POST['txt_apellidoPaterno'];
					}
				} else {
					$this->reg_errores['txt_apellidoPaterno'] = 'Ingresa tu apellido correctamente';
				}
			}
			if (array_key_exists('txt_apellidoMaterno', $_POST) && !empty($_POST['txt_apellidoMaterno'])) {
				if(preg_match('/^[A-ZáéíóúÁÉÍÓÚÑñ \'.-]{1,30}$/i', $_POST['txt_apellidoMaterno'])) {
					$datos['tc']['apellidoM_titularVc'] = $_POST['txt_apellidoMaterno'];
					if ($tipo == 1) {	//Amex
						$datos['amex']['apellidoM_titularVc'] = $_POST['txt_apellidoMaterno'];
					}
				} else {
					$this->reg_errores['txt_apellidoMaterno'] = 'Ingresa tu apellido correctamente';
				}
			} else {
				$datos['tc']['apellidoM_titularVc'] = "";
					if ($tipo == 1) {
						$datos['amex']['apellidoM_titularVc'] = "";
					}
			}
			
			if (array_key_exists('sel_mes_expira', $_POST)) {
				$datos['tc']['mes_expiracionVc'] = $_POST['sel_mes_expira']; 
			}
			if (array_key_exists('sel_anio_expira', $_POST)) { 
				$datos['tc']['anio_expiracionVc'] = $_POST['sel_anio_expira'];  
			}
			
			//AMEX
		if($tipo ==1){	
			if (array_key_exists('txt_calle', $_POST)) {
				if(preg_match('/^[A-Z0-9 \'.-áéíóúÁÉÍÓÚÑñ]{2,40}$/i', $_POST['txt_calle'])) {
					$datos['amex']['calle'] = $_POST['txt_calle'];
				} else {
					$this->reg_errores['txt_calle'] = 'Ingresa tu calle y n&uacute;mero correctamente';
				}
			} 
			
			if (array_key_exists('txt_cp', $_POST)) {
				//regex usada en js
				if(preg_match('/^([1-9]{2}|[0-9][1-9]|[1-9][0-9])[0-9]{3}$/', $_POST['txt_cp'])) {
					$datos['amex']['codigo_postal'] = $_POST['txt_cp'];
				} else {
					$this->reg_errores['txt_cp'] = 'Ingresa tu c&oacute;digo postal correctamente';
				}
			} 
			
			if (array_key_exists('txt_ciudad', $_POST)) {
				if(preg_match('/^[A-Z0-9 \'.,-áéíóúÁÉÍÓÚÑñ]{2,40}$/i', $_POST['txt_ciudad'])) {
					$datos['amex']['ciudad'] = $_POST['txt_ciudad'];
				} else {
					$this->reg_errores['txt_ciudad'] = 'Ingresa tu ciudad correctamente';
				}
			} 
			
			if (array_key_exists('txt_estado', $_POST)) {
				if(preg_match('/^[A-Z \'.-áéíóúÁÉÍÓÚÑñ]{2,40}$/i', $_POST['txt_estado'])) {
					$datos['amex']['estado'] = $_POST['txt_estado'];
				} else {
					$this->reg_errores['txt_estado'] = 'Ingresa tu estado correctamente';
				}
			}
			if (array_key_exists('sel_pais', $_POST)) {
				if ($_POST['sel_pais'] != "") {
					$datos['amex']['pais'] = $_POST['sel_pais'];
				} else {
					$this->reg_errores['sel_pais'] = 'Selecciona tu pa&iacute;s';
				}
			}
			else {
				$datos['amex']['pais'] = '';
			}
			
			if (array_key_exists('txt_email', $_POST) && trim($_POST['txt_email']) != "") {
				if(filter_var($_POST['txt_email'], FILTER_VALIDATE_EMAIL)) {
					$datos['amex']['mail'] = $_POST['txt_email'];
				} else {
					$this->reg_errores['txt_email'] = 'Ingresa tu email correctamente (opcional)';
				}
			} else {
				$datos['amex']['mail'] = '';
			}
			
			if (array_key_exists('txt_telefono', $_POST)) {
				if(preg_match('/^[0-9 -]{8,20}$/i', $_POST['txt_telefono'])) {
					$datos['amex']['telefono'] = $_POST['txt_telefono'];
				} else {
					$this->reg_errores['txt_telefono'] = 'Ingresa tu tel&eacute;fono correctamente';
				}
			} /*else {
				$datos['amex']['telefono'] = '';
			}*/
		}	
		
		
		//echo 'si no hay errores, $reg_errores esta vacio? '.empty($this->reg_errores).'<br/>';
		return $datos;
	}

	private function validar_tarjeta($tipo_tarjeta, $num_tarjeta) {
		$reg_visa 			= '/^4[0-9]{12}(?:[0-9]{3})?$/';
		$reg_master_card 	= '/^5[1-5][0-9]{14}$/';
		$reg_amex			= '/^3[47][0-9]{13}$/';
		//1 => Aamex, >1 => PROSA
		
		if ($tipo_tarjeta > 1 && !preg_match($reg_visa, $num_tarjeta) && !preg_match($reg_master_card, $num_tarjeta)) {
			return false;
		} else if ($tipo_tarjeta == 0 && !preg_match($reg_amex, $num_tarjeta)) {
			return false;
		} else if (!$this->validarLuhn($num_tarjeta)) {
			return false;
		} 
		
		return true;		//tarjeta válida
	}
	
	private function validarLuhn($num_tarjeta) {
		$num_card = array(16);
		$len = 0;
		$tarjeta_valida = false;
	
		//Obtener los dígitos de la tarjeta
		for ($i = 0; $i < strlen($num_tarjeta); $i++) {
			$num_card[$len++] = (int)($num_tarjeta[$i]);
		}
			
		//algoritmo Luhn
		$checksum = 0;
		for ($i = $len - 1; $i >= 0; $i--) {
			if ($i % 2 == $len % 2) {
				$n = $num_card[$i] * 2;
				$checksum += (int)($n / 10) + ($n % 10);
			} else {
				$checksum += $num_card[$i];
			}
		}
		
		$tarjeta_valida = ($checksum % 10 == 0);
		
		return $tarjeta_valida;
	}
	
	private function cargar_vista($folder, $page, $data){	
		//Para automatizar un poco el desplieguee
		$this->load->view('templates/header', $data);							
		$this->load->view('templates/promocion.html', $data);																					
		$this->load->view($folder.'/'.$page, $data);
		$this->load->view('templates/footer', $data);
	}
	
	private function genera_pass(){
		$str = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890";
        $contra = "";
        for($i=1;$i<=8;$i++) {
        	$contra .= substr($str,rand(0,62),1);
        }
		return $contra;
	}
	
}

/* End of file reporte.php */
/* Location: ./application/controllers/reporte.php */