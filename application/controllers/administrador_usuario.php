<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include('api.php');
class Administrador_usuario extends CI_Controller {
		
	function __construct()
    {
        // Call the Model constructor
        parent::__construct();						

		
		// incluye el modelo de las direcciones de facturacion		
		$this->load->model('direccion_facturacion_model', 'direccion_facturacion_model', true);				
		$this->load->model('direccion_envio_model', 'direccion_envio_model', true);
		$this->load->model('forma_pago_model', 'forma_pago_model', true);
		$this->load->model('login_registro_model', 'login_registro_model', true);
		
		$this->api = new Api();
    }
	
	public function index()
	{
	}
	
	public function cliente_id($id_cliente = ""){
		
		$cliente = $this->login_registro_model->obtener_cliente_id($id_cliente);
		if($cliente->num_rows() > 0){
			$data['cliente'] = $cliente->row();			
			echo json_encode($data);
		} else{
			echo json_encode($data);
		}				
	}
	
	public function actualizar_cliente($id_cliente = ""){		
		if($_POST){
			
			$cliente_info =	$this->valida_datos_update();
			$cliente_info['id_clienteIn'] = $id_cliente;
				
			if(!empty($this->login_errores)){					
					echo "<pre>";
						print_r($this->login_errores);
					echo "</pre>";	
			}
			else{				
				if($this->login_registro_model->actualizar_cliente($cliente_info)){
					echo "actualizacion correcta";
				}	
				else{
					echo "problema al actualizar";
				}				
			}
		}
			
	}
	
	public function valida_datos_update(){
		$datos = array();
		
		if(!empty($_POST['log_data'])){
			$login_data = $this->api->decrypt($_POST['log_data'], $this->api->key);
			$login_data = explode('|',$login_data);
			$mail_registrado = 	$login_data[0]; 												   	
			$pass_registrado = 	$login_data[1];						
		} else {
			$this->login_errores['email'] = '<div class="error2">Información incompleta.</div>';
			$this->login_errores['password'] = '<div class="error2">Información incompleta.</div>';
		}	
				
				
		if (filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)){
			if($mail_registrado == $_POST['email']){
				$datos['email'] = htmlspecialchars(trim($_POST['email']));
			} else{
				$datos['email'] = htmlspecialchars(trim($_POST['email']));
				$datos['password'] = $pass_registrado;
			}
		} else {
			$this->login_errores['email'] = '<div class="error2">Por favor ingresa una dirección de correo válida. Ejemplo: nombre@dominio.mx</div>';		
		}
		
		
		if(array_key_exists('nombre', $_POST)){
			if(preg_match('/^[A-Z \'.-áéíóúÁÉÍÓÚÑñ]{1,30}$/i', $_POST['nombre'])) { 
				$datos['salutation'] = $_POST['nombre'];
			} else{
				$this->login_errores['nombre'] = '<div class="error2">Por favor ingresa tu nombre correctamente</div>';
			}
		} 
		
		if(array_key_exists('apellido_paterno', $_POST)){
			if(preg_match('/^[A-Z \'.-áéíóúÁÉÍÓÚÑñ]{1,30}$/i', $_POST['apellido_paterno'])) { 
				$datos['fname'] = $_POST['apellido_paterno'];
			} else{
				$this->login_errores['apellido_paterno'] = '<div class="error2">Por favor ingresa tu apellido paterno</div>';
			}
		}
		
		if(array_key_exists('apellido_materno', $_POST)){
			if(preg_match('/^[A-Z \'.-áéíóúÁÉÍÓÚÑñ]{1,30}$/i', $_POST['apellido_materno'])) { 
				$datos['lname'] = $_POST['apellido_materno'];
			}
		}
					
		if(!empty($_POST['password_actual'])){
						
			if($pass_registrado == $_POST['password_actual']){
				if(!empty($_POST['nuevo_password'])){
					$datos['password'] = $_POST['nuevo_password'];
				} else{
					$datos['password'] = $_POST['password_actual'];
				}																								
			} else{
				$this->login_errores['password'] = '<div class="error2">Password actual incorrecto</div>';
			}												
		} else{
			
		}	
		
											
		return $datos;				
		
	}
	
	public function listar_razon_social($id_cliente = ""){		
		$data['rs'] = $this->direccion_facturacion_model->listar_razon_social($id_cliente);						
		echo json_encode($data);
	}
	
	public function listar_direccion_envio($id_cliente = ""){
		$data['direccion_envio'] = $this->direccion_envio_model->listar_direcciones($id_cliente)->result_array();							
		echo json_encode($data);
	}
	
	public function listar_tarjetas($id_cliente = ""){
		$data['tarjetas'] = $this->forma_pago_model->listar_tarjetas($id_cliente)->result_array();							
		echo json_encode($data);
	}
	
}

/* End of file administrador_usuario.php */
/* Location: ./application/controllers/administrador_usuario.php */