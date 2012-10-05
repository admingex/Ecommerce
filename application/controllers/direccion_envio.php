<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include ('api.php');

class Direccion_Envio extends CI_Controller {
	var $title = 'Direcci&oacute;n de Env&iacute;o'; 		// Capitalize the first letter
	var $subtitle = 'Selecciona una direcci&oacute;n de env&iacute;o'; 	// Capitalize the first letter
	var $reg_errores = array();		//validación para los errores
	
	private $id_cliente;
	
	const CODIGO_MEXICO = "MX";		//constante para verificar el código del país en el efecto del JS.
	
	public static $TIPO_DIR = array(
		"RESIDENCE"	=> 0, 
		"BUSINESS"	=> 1, 
		"OTHER"		=> 2
	);
	
	//protected $lista_bancos = array();
	 
	function __construct()
    {
        // Call the Model constructor
        parent::__construct();
		
		$this->output->nocache();
				
		//si no hay sesión
		//manda al usuario a la... página de login
		$this->redirect_cliente_invalido('id_cliente', 'login');
		
		//cargar el modelo en el constructor
		$this->load->model('direccion_envio_model', 'direccion_envio_model', true);
		$this->load->model('forma_pago_model', 'forma_pago_model', true);
		//la sesion se carga automáticamente
		
		//toma el valor del id cliente de la sesión creada en el login/registro
		$this->id_cliente = $this->session->userdata('id_cliente');
		
		$this->api= new Api();
		
		//echo "requiere_envio: " . $this->session->userdata('requiere_envio');
		/*echo "sesion<pre>";
		print_r($this->session->all_userdata());
		echo "</pre>";*/
    }

	/**
	 * Se encarga de listar las direcciones de envío
	 */
	public function index($id_promocion = NULL)
	{
		$this->listar();
	}
	
	/**
	 * Se encarga de asosciar una promoción a la direcciones de envío
	 * @param $id_promocion Si se quiere cambiar la dirección de una promoción cuando hay más de una dirección para la compra
	 */
	public function direccion_adicional($id_promocion = NULL)
	{
		##Test
		//$this->session->unset_userdata('dse');
		//$this->session->unset_userdata('promo_por_asociar');
		//si viene el número de la promoción
		if ($id_promocion) {
			//se necesitará el detalle de las promociones...en teoría no debería estar vacias
			if ($this->session->userdata('promociones') && $this->session->userdata('promocion')) {
				$detalle_promociones = $this->api->obtiene_articulos_y_promociones();
			}
			
			//si ya había una dirección de envío para la promoción 
			if ($this->session->userdata('dir_envio')) {
				
				
				//revisar si ya existe el arreglo de promociones y que no esté vacío "dese" => direcciones envio
				$direcciones = $this->session->userdata('dse');
				//se desmarca la promoción por asociar antes de marcar otra
				$this->session->unset_userdata('ppa');
				
				//si aún no existe en arreglo para las direcciones 
				if (!$direcciones) {
					//la dirección de envío que tiene inicialmente la promoción
					$dir_general = $this->session->userdata('dir_envio');
					
					$direcciones = array();
					//colocar en el arreglo de direcciones todas las promociones que puedan tener envío
					foreach ($detalle_promociones['descripciones_promocion'] as $p) {
						//si requiere dirección de envío se mete al arreglo
						if ($p['promocion']->requiere_envio) {
							$direcciones[$p['promocion']->id_promocionIn] = $dir_general;
						}
					}
					
					//colocar en el arreglo de direcciones el id de la promoción que se quiere asociar con alguna dirección antes de ponerlo en sesión
					$this->session->set_userdata('dse', $direcciones);
					
					//bandera de estatus de asociación	ppa => promocion por asociar
					$this->session->set_userdata('ppa', $id_promocion);
					
				} else if ($direcciones) {	//si ya se asoció alguna otra dirección o se intentó... ya existe el arreglo, sólo se marca a nueva dirección
					/*
					//revisar en la lista de direcciones
					foreach ($detalle_promociones['descripciones_promocion'] as $p) {
						//si requiere dirección de envío se mete al arreglo
						if ($p['promocion']->requiere_envio) {
							//si hay alguna promoción que sea la que está marcada por asociar, se le coloca la general antes de colocarle una nueva, si no se queda tal cual
							$direcciones[$p['promocion']->id_promocionIn] = ($direcciones[$p['promocion']->id_promocionIn] == $promo_por_asociar) ? $dir_general : $direcciones[$p['promocion']->id_promocionIn];
							
							#### Puede no ir... :
							//se pone sin dirección para identificar que esta promoción quiere asociarse con una dirección diferente
							//if ($p['promocion']->id_promocionIn == $id_promocion) {
							//	$direcciones[$p['promocion']->id_promocionIn] = 0;
							//}
						}
					}
					*/
					//colocar en el arreglo de direcciones el id de la promoción que se quiere asociar con alguna dirección antes de ponerlo en sesión
					//$this->session->set_userdata('dse', $direcciones);
					
					//bandera de estatus de asociación	ppa => promocion por asociar se desactiva por que ya se asoció con alguna dirección
					$this->session->set_userdata('ppa', $id_promocion);
				}
				
				//$direcciones[$id_promocion] = $dir_general;
				/*echo "detalle_promos<pre>";
				print_r($detalle_promociones);
				echo "</pre>";
				
				echo "direcciones<pre>";
				print_r($direcciones);
				echo "</pre>";
				
				echo "sesion<pre>";
				print_r($this->session->all_userdata());
				echo "</pre>";*/
				//exit;
			} else if ($this->session->userdata('requiere_envio')) {	//si no trae una promoción válida y requiere envío
				//ir al listado
				redirect("direccion_envio/", "refresh");
				$this->session->unset_userdata('ppa');
				exit;
			}
		} //Si no  no 
		//si se solicita agregar otra dirección a la promoción
		//ir al listado para que el proceso continúe...
		redirect("direccion_envio/", "refresh");
		exit;
		
		//$this->listar();
	}
	
	/**
	 * Coloca la dirección seleccionada del listado en session
	 * 
	 */
	public function seleccionar() {
		## Test
		//$this->session->unset_userdata('ppa');
		if ($_POST) {
			if (array_key_exists('direccion_selecionada', $_POST)) {
				$dir_seleccionada = $_POST['direccion_selecionada'];
				
				#### Para varias direciones
								
				//revisar si ya existe el arreglo de promociones y que no esté vacío "dse"
				$direcciones = $this->session->userdata('dse');
				
				//revisar si la promoción por asociar también está en sesión "ppa"
				$promo_por_asociar = $this->session->userdata('ppa');
				
				//si hay una promoción en espera de la asociación de dirección está en "ppa"
				if ($direcciones  && $promo_por_asociar) {
					//buscar la promoción que esté en "promoción por asociar"
					foreach ($direcciones as $id_promo => $id_dir_envio) {
						if ($id_promo == $promo_por_asociar) {
							$direcciones[$id_promo] = $dir_seleccionada; 
						}
					}
					//colocar de nuevo las asociaciones de dirección en sesión
					$this->session->set_userdata('dse', $direcciones);
					
					//y se desmarca la promoción por asociar
					$this->session->unset_userdata('ppa');
					//echo "se puso el arreglo en sesión";
					
				} else {	//si es la primera vez que se selecciona la dirección de envío, se coloca en la sesión en la variable global
					$this->session->set_userdata('dir_envio', $dir_seleccionada);
					//echo "se puso el dir_envio en sesión";
				}
			}
			/*echo "sesion<pre>";
			print_r($this->session->all_userdata());
			echo "</pre>";
			exit;*/
			
			//Para calcular destino siguiente y actualizxarlo en sesión
			$destino = $this->obtener_destino();
			
			redirect($destino, "refresh");
		} else {
			//ir al listado
			redirect("forma_pago/listar", "refresh");
		}		
	}
	
	/**
	 * Lista las direcciones registradas, si hay un mensaje, lo despliega y 
	 * si debe haber redirección la aplica. 
	 */
	public function listar($msg = '', $redirect = TRUE) 
	{	
		$data['title'] = $this->title;
		$data['subtitle'] = $this->subtitle;		
		$data['mensaje'] = $msg;
		$data['redirect'] = $redirect;
		
		//Para calcular destino siguiente y actualizxarlo en sesión
		$data['destino'] = $this->session->userdata('destino');
		 
		if ($this->input->is_ajax_request()) {
			$direcciones = $this->direccion_envio_model->listar_direcciones($this->id_cliente);
			
			header('Content-Type: application/json',true);
			echo json_encode($direcciones->result());
		} else {
			//listar por default las direcciones del cliente
			$data['lista_direcciones'] = $this->direccion_envio_model->listar_direcciones($this->id_cliente);
			//cargar vista	
			$this->cargar_vista('', 'direccion_envio', $data);
			
			//se puede editar
			$this->session->set_userdata('ed', hash("sha256", "editar_direccion".$this->session->userdata('email')));
			//.strlen($this->session->userdata('ed')) //son 64
			//echo "edit: ".$this->session->userdata('ed');
		}
	}
	
	public function registrar() 
	{
		$id_cliente = $this->id_cliente;
		/*agregar el script para este formulario*/
		$script_file = "<script type='text/javascript' src='". base_url() ."js/dir_envio.js'></script>";
		$data['script'] = $script_file;
		
		$data['title'] = $this->title;
		$data['subtitle'] = $this->subtitle;
		
		//catálogo de paises de think
		$lista_paises_think = $this->direccion_envio_model->listar_paises_think();
		$data['lista_paises_think'] = $lista_paises_think;
				
		//recuperar el listado de las direcciones del cliente
		$data['lista_direcciones'] = $this->direccion_envio_model->listar_direcciones($id_cliente);
		
		//catálogo de estados
		$lista_estados = $this->consulta_estados();
		$data['lista_estados_sepomex'] = $lista_estados['estados'];
		
		$data['registrar'] = TRUE;		//se debe mostrar formulario de registro
				
		if ($_POST)	{	
			//Petición de registro
			$consecutivo = $this->direccion_envio_model->get_consecutivo($id_cliente);			
			$consecutivo = $consecutivo + 1;
			
			$form_values = array();		//alojará los datos ingresados previos a la inserción	
			$form_values = $this->get_datos_direccion();			
			
			$form_values['direccion']['id_clienteIn'] = $id_cliente;
			$form_values['direccion']['id_consecutivoSi'] = $consecutivo;					//el id de la dirección
			$form_values['direccion']['address_type'] = self::$TIPO_DIR['RESIDENCE'];		//address_type
			
			/*echo "sesion<pre>";
			$this->session->unset_userdata('dse');
			print_r($this->session->all_userdata());
			echo "</pre>";
			exit;*/
					
			if (empty($this->reg_errores)) {
				//si no hay errores en el formulario y se solicita registrar la dirección
				
				if (isset($form_values['direccion']['id_estatusSi'])) {	//Ya no se revisa isset($form_values['guardar']) , siempre se guarda
					//verificar que no exista la direccion activa en la BD
					if ($this->direccion_envio_model->existe_direccion($form_values['direccion'])) {	
						//redirect al listado por que ya existe
						$this->listar("La dirección ingresada ya existe en tu cuenta. Para usarla, por favor selecciónala arriba en la lista de direcciones guardadas.", FALSE);
						//echo "La direcci&oacute;n ya está registrada.";
					} else {
						//verifica si hay o no dirección activa predeterminada
						$existe_predeterminada = $this->existe_predetereminada($id_cliente);
						
						//sólo la primera dirección activa que se registra se predetermina
						if (isset($form_values['predeterminar']) || $consecutivo == 0 || !$existe_predeterminada) {
							$this->direccion_envio_model->quitar_predeterminado($id_cliente);
							$form_values['direccion']['id_estatusSi'] = 3;
						}
						//si no hay predeterminada activa, la actual lo será
						
						//registrar en BD
						if ($this->direccion_envio_model->insertar_direccion($form_values['direccion'])) {
							#### cargar en sesion la asociación de la nueva dirección registrada
							
							//revisar si ya existe el arreglo de promociones y que no esté vacío "dse"
							$direcciones = $this->session->userdata('dse');
							
							//revisar si la promoción por asociar también está en sesión "ppa"
							$promo_por_asociar = $this->session->userdata('ppa');
							/**
							 * Para los casos en que venga de la orden de cmpra... y se registre una nueva dirección
							 */
							//sólo si existen ambos en sesión, se asocia la dirección registrada con la promoción en espera ("ppa")
							if ($direcciones && $promo_por_asociar) {
								//colocar en la promoción que espera asociación "ppa"
								foreach ($direcciones as $id_promo => $id_dir_envio) {
									if ($id_promo == $promo_por_asociar) {
										$direcciones[$id_promo] = $consecutivo;		//Es el id de la dirección que se acaba de registrar
									}
								}
								//colocar de nuevo las asociaciones de dirección en sesión
								$this->session->set_userdata('dse', $direcciones);
								
								//y se desmarca la promoción por asociar
								$this->session->unset_userdata('ppa');
								
							} else {	//si no existe el arreglo de direcciones, se registra en sesión la nueva dirección para todas las promociones que lo requieran
								##legacy
								//$this->cargar_en_session($form_values['direccion']['id_consecutivoSi']);
								$this->cargar_en_session($consecutivo);
								
								/**
								 * Para los casos en que venga de la orden de compra... y se registre una nueva dirección
								 */
								
								//recuperar el detalle de las promociones para saber si requieren dirección de ennvío
								if ($this->session->userdata('promociones') && $this->session->userdata('promocion')) {
									$detalle_promociones = NULL;
									$detalle_promociones = $this->api->obtiene_articulos_y_promociones();
									
									//si se tiene la información...
									if ($detalle_promociones) {
																				
										//id de la dirección que se acaba de registrar
										$dir_general = $consecutivo;
										
										//direcciones que se vana aponer en sesión
										$direcciones = array();
							
										//revisión de las promociones para asociar todas las promociones que requieran dirección con la que se está registrando
										foreach ($detalle_promociones['descripciones_promocion'] as $p) {
											//si requiere dirección de envío se mete al arreglo
											if ($p['promocion']->requiere_envio) {
												$direcciones[$p['promocion']->id_promocionIn] = $dir_general;
											}
										}
										
										//colocar en el arreglo de direcciones el id de la promoción que se quiere asociar con alguna dirección antes de ponerlo en sesión
										$this->session->set_userdata('dse', $direcciones);
										
									
										//desmarcer la bandera de estatus de asociación	ppa => promocion por asociar -si existe-
										if ($promo_por_asociar) {
											$this->session->unset_userdata('ppa');
										}
									}
								}
							}
							
							/*echo "sesion, destino: ".$this->obtener_destino()."<pre>";
							print_r($this->session->all_userdata());
							echo "</pre>";
							exit;*/
							
							//Para calcular destino siguiente y actualizxarlo en sesión
							$destino = $this->obtener_destino();
							
							//cargar la vista de las direcciones
							$this->listar("Tu dirección ha sido guardada exitosamente");
							
						} else {
							$this->listar("Hubo un error al guardar tu dirección. Por favor intenta de nuevo.", FALSE);
							//echo "<br/>Hubo un error en el registro en CMS";
						}
					}
				}
			} else {	//Si hubo errores en la captura
				//carga de catálogos de sepomex si ya se hizo la seleccion de estado, ciudad, colonia
				if (!empty($_POST['sel_ciudades']))
				{
					//catálogo de ciudades
					$lista_ciudades = $this->consulta_ciudades($_POST['sel_estados']);
					$data['lista_ciudades_sepomex'] = $lista_ciudades['ciudades'];
				}
				
				if (!empty($_POST['sel_estados']) && !empty($_POST['sel_ciudades']))
				{
					//catálogo de colonias
					$lista_colonias = $this->consulta_colonias($_POST['sel_estados'], $_POST['sel_ciudades']);
					$data['lista_colonias_sepomex'] = $lista_colonias['colonias'];
				}
				
				//vuelve a mostrar la información en el formulario
				$data['reg_errores'] = $this->reg_errores;
				$this->cargar_vista('', 'direccion_envio' , $data);
			}
		} else {
			//muestra la lista de direcciones sólamente
			$this->cargar_vista('', 'direccion_envio' , $data);
		}
	}

	/**
	 * Edición de la dirección seleccionada
	 */
	public function editar($consecutivo = 0)	//el consecutivo de la direccion
	{
		$id_cliente = $this->id_cliente;
		//inclusión de Scripts
		$script_file = "<script type='text/javascript' src='". base_url() ."js/dir_envio.js'></script>";
		$data['script'] = $script_file;
		
		$data['title'] = $this->title;
		$data['subtitle'] = "Edita los campos que quieras modificar";
		
		//recuperar la información de la dirección
		$detalle_direccion = $this->direccion_envio_model->detalle_direccion($consecutivo, $id_cliente);
		
		
		//se pasa la información de la dirección a la vista
		$data['direccion'] = $detalle_direccion;
		
		//catálogo de países de think
		$lista_paises_think = $this->direccion_envio_model->listar_paises_think();
		$data['lista_paises_think'] = $lista_paises_think;
		
		/*muestra lo de sepomex*/
		//catálogo de estados
		$lista_estados = $this->consulta_estados();		
		$data['lista_estados_sepomex'] = $lista_estados['estados'];
		//ciudades		
		$lista_ciudades = $this->consulta_ciudades($detalle_direccion->state);		
		$data['lista_ciudades_sepomex'] = $lista_ciudades['ciudades'];
		//colonias
		$lista_colonias = $this->consulta_colonias($detalle_direccion->state, $detalle_direccion->city);		
		$data['lista_colonias_sepomex'] = $lista_colonias['colonias'];
		
		//Se intentará actualizar la información
		if ($_POST) {			
			//array para la nueva información
			$nueva_info = array();
			//trae datos del formulario para actualizar
			$nueva_info = $this->get_datos_direccion();
			//guardar y usar otra?
			$redirect = $nueva_info['redirect'];
			
			if (empty($this->reg_errores)) {	//si no hubo errores
				$nueva_info['direccion']['id_consecutivoSi'] = $consecutivo;
			
				if (isset($nueva_info['predeterminar'])) {
					$this->direccion_envio_model->quitar_predeterminado($id_cliente);
				} else {	//si no es predeterminado se quda sólo como "activa"habilitado"
					$nueva_info['direccion']['id_estatusSi'] = 1;
				}
				
				//actualizar la información en BD
				$msg_actualizacion = 
					$this->direccion_envio_model->actualizar_direccion($consecutivo, $id_cliente, $nueva_info['direccion']);
				
				$data['msg_actualizacion'] = $msg_actualizacion;
				
				#### cargar en sesion la asociación de la nueva dirección registrada
							
				//revisar si ya existe el arreglo de promociones y que no esté vacío "dse"
				$direcciones = $this->session->userdata('dse');
				
				//revisar si la promoción por asociar también está en sesión "ppa"
				$promo_por_asociar = $this->session->userdata('ppa');
				
				/**
				 * Para los casos en que venga de la orden de cmpra... y se registre una nueva dirección
				 */
				//sólo si existen ambos en sesión, se asocia la dirección registrada con la promoción en espera ("ppa")
				if ($direcciones && $promo_por_asociar) {
					//colocar en la promoción que espera asociación "ppa"
					foreach ($direcciones as $id_promo => $id_dir_envio) {
						if ($id_promo == $promo_por_asociar) {
							$direcciones[$id_promo] = $consecutivo;		//Es el id de la dirección que se acaba de registrar
						}
					}
					//colocar de nuevo las asociaciones de dirección en sesión
					$this->session->set_userdata('dse', $direcciones);
					
					//y se desmarca la promoción por asociar
					$this->session->unset_userdata('ppa');
					
				}
				##legacy
				//cargar en sesión la dirección mmodificada
				$this->cargar_en_session($consecutivo);
				
				//para calcular destino siguiente y actualizxarlo en sesión
				$destino = $this->obtener_destino();
				
				$this->listar($msg_actualizacion, $redirect);
				
				
			} else {	//ERRORES FORMULARIO
				$data['msg_actualizacion'] = "Hubo un error al actualizar tu dirección. Por favor intenta de nuevo.";
				$data['reg_errores'] = $this->reg_errores;
				$this->cargar_vista('', 'direccion_envio' , $data);
			}	//ERRORES FORMULARIO
		} else {	//If POST
			$this->cargar_vista('', 'direccion_envio' , $data);
		}
	}
	
	/**
	 * Eliminación lógica de la dirección en la BD
	 */
	public function eliminar($consecutivo = 0)
	{
		$id_cliente = $this->id_cliente;
		$data['title'] = $this->title;
		$data['subtitle'] = 'Eliminar Direcci&oacute;n';
		
		$msg_eliminacion =
			$this->direccion_envio_model->eliminar_direccion($id_cliente, $consecutivo);
		
		//Por si se les ocurre eliminar la dirección que se estaba ocupando para realizar el pago.
		if ($dir = $this->session->userdata("dir_envio")) {
			if ((int)$dir == (int)$consecutivo) {
				$this->session->unset_userdata("dir_envio");
			}
		}
		
		/*Pendiente el Redirect hacia la dirección de Facturación*/
		//echo $data['msg_eliminacion´];
		
		//cargar la lista de direeciones
		$this->listar($msg_eliminacion, FALSE);
	}
	
	/**
	 * Verifica si existe o no alguna dirección predeterminada para pago express del cliente.
	 * Retuen True/False
	 */
	private function existe_predetereminada($id_cliente)
	{
		return $this->direccion_envio_model->existe_predetereminada($id_cliente);
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
				//Si hay dirección de envío seleccionada y no hay alguna por asociar
				if ($this->session->userdata('dir_envio') && !$this->session->userdata('ppa')) {
					//Si hay dirección de facturación Y razón social
					if ($this->session->userdata('direccion_f') && $this->session->userdata('razon_social')) {
						$destino = "orden_compra";
					} else {	//NO dir. facturación
						if ($this->direccion_envio_model->existe_compra($this->id_cliente) || $this->session->userdata('paso_orden_compra')) {	//compra
							$destino = "orden_compra";
						
						} else if (!$this->session->userdata('paso_orden_compra')) {	//si no ha pasado por orden de compra...
							$destino = "direccion_facturacion";
						}
					}
				} else {
					$destino = "direccion_envio";
					echo "aquí";
					exit;
				}
			} else {
				//Si hay dirección de facturación Y razón social
				if ($this->session->userdata('direccion_f') && $this->session->userdata('razon_social')) {
					$destino = "orden_compra";
				} else {	//NO hay dir. facturación
					if ($this->forma_pago_model->existe_compra($this->id_cliente) || $this->session->userdata('paso_orden_compra')) {	//compra
						$destino = "orden_compra";
					} else if (!$this->session->userdata('paso_orden_compra')) {	//si no ha pasado por orden de compra...
						$destino = "direccion_facturacion";
					}						
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
	 * Verifica si el código de país corresponde con el de México o no
	 */
	public function es_mexico($codigo_pais="") {
		//$codigo_pais = ['codigo_pais'];
		$r = ($codigo_pais == self::CODIGO_MEXICO) ? TRUE : FALSE;
		$es_mexico = array('result' => $r, 'param' => $codigo_pais);
		
		header('Content-Type: application/json',true);
		echo json_encode($es_mexico);
	}
	
	/**
	 * Regresa el listado de estados para poblar el select correspondiente
	 * en formato JSON
	 */
	public function get_estados()
	{
		//echo json_encode($this->consulta_estados());
		header('Content-Type: application/json',true);
		echo json_encode($this->direccion_envio_model->listar_estados_sepomex()->result_array());
	}
	
	/*
	 * Regresa un array con los resultados
	 */
	private function consulta_estados()
	{
		$resultado = array();
		
		try
		{
			$resultado['estados'] = $this->direccion_envio_model->listar_estados_sepomex()->result();
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
	
	public function get_info_sepomex($cp = 0)
	{
		//echo "cp: ". $cp;
		
		/*if (array_key_exists('codigo_postal', $_POST) && isset($_POST['codigo_postal']))
			$cp = $_POST['codigo_postal'];*/
		
		//$cp = $this->input->post('codigo_postal');
		
		//echo "<br/>Es llamada ajax?: ". $this->input->is_ajax_request() . "<br/>";
		//echo "<script>alert('Peticion Ajax'); </script>";
		//echo json_encode($this->consulta_sepomex($cp));
		
		if (!$cp)
			$cp = $this->input->post('codigo_postal');
		//$cp = $this->input->post('codigo_postal');
		
		//$resultado = array();
		//$resultado->sepomex = $this->direccion_envio_model->obtener_direccion_sepomex($cp)->result();
		$resultado = $this->consulta_sepomex($cp);
		//$this->output->set_content_type("content-type: application/json")->set_output(json_encode($resultado));
		header('Content-Type: application/json',true);
		echo json_encode($resultado);
	}
	
	/*
	 * Regresa un array con los resultados: cp, CIUDAD, Clave estado, ESTADO
	 */
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
		
		/*
		$resultado = array();
		
		try {
			$cliente = new SoapClient("https://cctc.gee.com.mx/ServicioWebCCTC/ws_cms_cctc.asmx?WSDL");
		
			$parameter = array( "codigo_postal" => $codigo_postal );	
			
			$obj_result = $cliente->ObtenerEstadoCiudad($parameter);
			//por si no regresa ningún resultado
			$simple_result = isset($obj_result->ObtenerEstadoCiudadResult) ?
				 $obj_result->ObtenerEstadoCiudadResult : null;
			
			//var_dump($obj_result);
			$resultado['sepomex'] = $simple_result;
			
			$resultado['success'] = true;
			$resultado['msg'] = "Sepomex Ok";
			
			return $resultado;
			
		} catch (Exception $e)	{
			$resultado['exception'] =  $exception;
			$resultado['msg'] = $exception->getMessage();
			$resultado['error'] = true;
			//echo "No se pudo recuperar el catálogo de SEPOMEX.<br/>";
			//echo $e->getMessage();
			//exit();
			return $resultado;	
		}
		*/
	}
	
	/**
	 * Regresa el listado de estados para poblar el select correspondiente
	 * en formato JSON
	 */
	public function get_ciudades($estado = "")
	{
		$estado = $this->input->post('estado');
		$resultado = array();
		$resultado['ciudades'] = $this->direccion_envio_model->listar_ciudades_sepomex($estado)->result_array();
		header('Content-Type: application/json',true);
		echo json_encode($resultado);
		//$estado = $this->input->post('estado');	// ? $this->input->post('estado') : "" ;
		//echo json_encode($this->consulta_ciudades($estado));
	}
	
	private function consulta_ciudades($estado)
	{
		$resultado = array();			
		try {
			$resultado['ciudades'] = $this->direccion_envio_model->listar_ciudades_sepomex($estado)->result();
			$resultado['success'] = true;
			$resultado['msg'] = "Ciudades Resultados";
			return $resultado;
		} catch (Exception $e) {
			$resultado['exception'] =  $exception;
			$resultado['msg'] = $exception->getMessage();
			$resultado['error'] = true;
			return $resultado;
		}
		/*
		$resultado = array();	
		try {
			//URL del WS debe estar en archivo protegido
			$cliente = new SoapClient("https://cctc.gee.com.mx/ServicioWebCCTC/ws_cms_cctc.asmx?WSDL");	
			$parameter = array(	'estado' => $estado);
			
			$obj_result = $cliente->ObtenerCiudad($parameter);			
			$simple_result = $obj_result->ObtenerCiudadResult;
			
			
			if (isset($simple_result->InformacionCiudad)) {	//por si no regresa ningún resultado
				$resultado['ciudades'] = $simple_result->InformacionCiudad;	//es un array de objects	
			} else {
				$resultado['ciudades'] = NULL;
			}
			
			$resultado['success'] = true;
			$resultado['msg'] = "Ciudades Resultados";
			//var_dump($resultado);
			
			return $resultado;
			
		} catch (Exception $e) {
			$resultado['exception'] =  $exception;
			$resultado['msg'] = $exception->getMessage();
			$resultado['error'] = true;
			//echo "No se pudo recuperar el catálogo de SEPOMEX.<br/>";
			//echo $e->getMessage();
			//exit();
			return $resultado;
		}
		*/
	}

	/*
	 * Regresa la lista de colonias correspondientes
	 * params:
	 * $estado:string = clave del estado en cuestión
	 * $estado:string = clave del estado en cuestión
	 * 
	 * return:
	 * $resuktado:json object = listado de colonias en formato JSON
	 * */
	
	public function get_colonias($estado = "", $ciudad = "")
	{
		$estado = $this->input->post('estado');
		$ciudad = $this->input->post('ciudad');

		$resultado = array();
		$resultado['colonias'] = $this->direccion_envio_model->listar_colonias_sepomex($estado, $ciudad)->result_array();
		
		header('Content-Type: application/json',true);
		echo json_encode($resultado);
		//$estado = $this->input->post('estado');	// ? $this->input->post('estado') : "" ;
		//$ciudad = $this->input->post('ciudad');
		//echo "edo: ".$edo;
		//echo json_encode($this->consulta_colonias($estado, $ciudad));
	}
	
	private function consulta_colonias($estado, $ciudad)
	{
		$resultado = array();
		try {
			$resultado['colonias'] = $this->direccion_envio_model->listar_colonias_sepomex($estado, $ciudad)->result();
			$resultado['success'] = true;
			$resultado['msg'] = "Colonias Resultados";
			return $resultado;
		} catch (Exception $e) {
			$resultado['exception'] =  $exception;
			$resultado['msg'] = $exception->getMessage();
			$resultado['error'] = true;
			return $resultado;
		}
		/*
		$resultado = array();
		try {
			//URL del WS debe estar en archivo protegido
			$cliente = new SoapClient("https://cctc.gee.com.mx/ServicioWebCCTC/ws_cms_cctc.asmx?WSDL");	
			$parameter = array(	'estado' => $estado, 'ciudad' => $ciudad);
			
			$obj_result = $cliente->ObtenerColonia($parameter);
			$simple_result = $obj_result->ObtenerColoniaResult;
			
			
			if (isset($simple_result->InformacionColonia)) {	//por si no regresa ningún resultado
				$resultado['colonias'] = $simple_result->InformacionColonia;	//es un array de objects	
			} else {
				$resultado['colonias'] = NULL;
			}
			
			$resultado['success'] = true;
			$resultado['msg'] = "Colonias Resultados";
			//var_dump($resultado);
			
			return $resultado;
			
		} catch (Exception $e) {
			$resultado['exception'] =  $exception;
			$resultado['msg'] = $exception->getMessage();
			$resultado['error'] = true;
			//echo "No se pudo recuperar el catálogo de SEPOMEX.<br/>";
			//echo $e->getMessage();
			//exit();
			return $resultado;
		}
		*/ 
	}
	
	/**
	 * Recoge los valores del formulario de registro y edición
	 * 
	 */
	private function get_datos_direccion()
	{
		$datos = array();
		//no se usa la funcion de escape '$this->db->escape()', por que en la inserción ya se incluye 
		if($_POST) {
			if (array_key_exists('txt_calle', $_POST)) {
				if(preg_match('/^[A-Z0-9áéíóúÁÉÍÓÚÑñ \'.-]{1,50}$/i', $_POST['txt_calle'])) {
					$datos['direccion']['address1'] = $_POST['txt_calle'];
				} else {
					$this->reg_errores['txt_calle'] = '<span class="error">Por favor ingresa una calle</span>';
				}
			}
			if (array_key_exists('txt_numero', $_POST)) {
				if(preg_match('/^[A-Z0-9 -]{1,50}$/i', $_POST['txt_numero'])) {
					$datos['direccion']['address2'] = $_POST['txt_numero'];
				} else {
					$this->reg_errores['txt_numero'] = '<span class="error">Por favor ingresa el número exterior</span>';
				}
			}
			if (!empty($_POST['txt_num_int'])) {
				if(preg_match('/^[A-Z0-9 -]{1,50}$/i', $_POST['txt_num_int'])) {
					$datos['direccion']['address4'] = $_POST['txt_num_int'];
				} else {
					$this->reg_errores['txt_numero'] = '<span class="error">Por favor ingresa el número interior</span>';
				}
			} else {
				$datos['direccion']['address4'] = NULL;
			}
			if (array_key_exists('txt_cp', $_POST)) {
				//regex usada en js
				if (preg_match('/^([1-9]{2}|[0-9][1-9]|[1-9][0-9])[0-9]{3}$/', $_POST['txt_cp'])) {
					$datos['direccion']['zip'] = $_POST['txt_cp'];
				} else {
					$this->reg_errores['txt_cp'] = '<span class="error2">Por favor ingresa un código postal de 5 dígitos</span>';
				}
			}
						
			if (!empty($_POST['sel_pais'])) {
			//if(preg_match('/^[A-Z]{2}$/i', $_POST['sel_pais'])) {
				$datos['direccion']['codigo_paisVc'] = $_POST['sel_pais'];
			} else {
				$this->reg_errores['sel_pais'] = '<span class="error">Por favor selecciona el pa&iacute;s</span>';
			}
			
			/*Mexico*/
			if (!empty($_POST['sel_pais']) && $_POST['sel_pais'] == self::CODIGO_MEXICO)
			{
				if (!empty($_POST['sel_estados'])) {
					$datos['direccion']['state'] = $_POST['sel_estados'];
				} else {
					$this->reg_errores['sel_estados'] = '<span class="error">Por favor selecciona el estado</span>';
				}
				if (!empty($_POST['sel_ciudades'])) {
				//if(preg_match('/^[A-Z ()\'.-áéíóúÁÉÍÓÚÑñ]{2, 30}$/i', $_POST['sel_ciudades'])) {
					$datos['direccion']['city'] = $_POST['sel_ciudades'];
				} else {
					$this->reg_errores['sel_ciudades'] = '<span class="error">Por favor selecciona la ciudad</span>';
				}
				if (!empty($_POST['sel_colonias'])) {
				//if(preg_match('/^[A-Z0-9  \'.-áéíóúÁÉÍÓÚÑñ]{2, 30}$/i', $_POST['sel_colonias'])) {
					$datos['direccion']['address3'] = $_POST['sel_colonias'];
				} else {
					$this->reg_errores['sel_colonias'] = '<span class="error">Por favor selecciona la colonia</span>';
				}
			} else {
			/*otros países*/
				if (array_key_exists('txt_colonia', $_POST) && trim($_POST['txt_colonia']) != ""){
					$datos['direccion']['address3'] = $_POST['txt_colonia'];
				}
				else {
					$this->reg_errores['txt_colonia'] = '<span class="error">Por favor ingresa la colonia</span>';
				}
				if (array_key_exists('txt_ciudad', $_POST) && !empty($_POST['txt_ciudad'])) {
					$datos['direccion']['city'] = $_POST['txt_ciudad'];
				}
				else {
					$this->reg_errores['txt_ciudad'] = '<span class="error">Por favor ingresa la ciudad</span>';
				}
				if (array_key_exists('txt_estado', $_POST) && !empty($_POST['txt_estado'])) {
					$datos['direccion']['state'] = $_POST['txt_estado'];
				}
				else {
					$this->reg_errores['txt_estado'] = '<span class="error">Por favor ingresa el estado</span>';
				}	
			}
			
			if (array_key_exists('txt_telefono', $_POST)) {
				if(preg_match('/^[0-9 ()+-]{10,20}$/i', $_POST['txt_telefono'])) {
					$datos['direccion']['phone'] = $_POST['txt_telefono'];
				} else {
					$this->reg_errores['txt_telefono'] = '<span class="error">Por favor ingresa un tel&eacute;fono</span>';
				}
			}
			
			if (array_key_exists('txt_referencia', $_POST)) {
				$datos['direccion']['referenciaVc'] = trim($_POST['txt_referencia']);
			}
			
			//Innecesario, siempre se guardará la dirección
			if (array_key_exists('chk_guardar', $_POST)) {
				$datos['guardar'] = $_POST['chk_guardar'];
				$datos['direccion']['id_estatusSi'] = 1;
			}

			if (array_key_exists('chk_default', $_POST)) {
				$datos['direccion']['id_estatusSi'] = 3;	//dirección predeterminada?
				$datos['predeterminar'] = true;
				//$_POST['chk_default'];
				//en la edicion, si no se cambia, que se quede como está, activa!! VERIFICARLO on CCTC
			} else {
				//siempre se guarda la dirección
				$datos['direccion']['id_estatusSi'] = 1;
			}
			
			if (array_key_exists('guardar_y_usar_otra', $_POST)) {
				$datos['redirect'] = FALSE;
			} else {
				$datos['redirect'] = TRUE;
			}
			
		} 
		//var_dump($datos);
		//var_dump($this->reg_errores);
		//exit();
		return $datos;
	}

	/**
	 * cargará en sesión la dirección de envío para todas las promociones que así lo requieran
	 */
	private function cargar_en_session($id_direccion = 0)
	{
		//// legacy
		if ( ((int)$id_direccion) != 0 && is_int((int)$id_direccion)) {	//si ya está regiustrada la direccion en BD sólo sube el consecutivo
			$this->session->set_userdata('dir_envio', $id_direccion);
		} else {	//si no es ninguno de los dos, elimina el elemento de la sesión
			$this->session->unset_userdata('dir_envio');
		}
	}

	private function cargar_vista($folder, $page, $data)
	{	
		//Para automatizar un poco el desplieguee
		$this->load->view('templates/header', $data);
		$this->load->view('templates/menu.html', $data);
		if ($this->session->userdata('promociones') && $this->session->userdata('promocion')) {
			$data['detalle_promociones']=$this->api->obtiene_articulos_y_promociones();					
			$this->load->view('templates/promocion.html', $data);															
		}				
		$this->load->view($folder.'/'.$page, $data);
		$this->load->view('templates/footer', $data);
	}
	
	private function redirect_cliente_invalido($revisar = 'id_cliente', $destino = 'login', $protocolo = 'http://') 
	{
		if (!$this->session->userdata($revisar)) {
			//$url = $protocolo . BASE_URL . $destination; // Define the URL.
			$url = site_url($destino); // Define the URL.
			header("Location: $url");
			exit(); // Quit the script.
		}
	}
}

/* End of file direccion_envio.php */
/* Location: ./application/controllers/direccion_envio.php */