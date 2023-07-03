<?php 
class ControllerExtensionModuleUniLoginRegister extends Controller {
	private function index() {
		
		$this->load->language('account/register');
		$this->load->language('extension/module/uni_login_register');

		$data['login_link'] = $this->url->link('account/account', '', true);
		$data['register_link'] = $this->url->link('account/register', '', true);
		
		$uniset = $this->config->get('config_unishop2');
		$lang_id = $this->config->get('config_language_id');
		
		$log_setting = $uniset['login_form'];
		
		$data['show_login'] = isset($log_setting['popup']) ? true : '';
		$data['login_mail_text'] = $log_setting['mail_text'][$lang_id];
		$data['login_password_text'] = $log_setting['password_text'][$lang_id];
		$data['show_login_forgotten'] = isset($log_setting['forgotten']) ? true : '';
		$data['show_login_register'] = isset($log_setting['register']) ? true : '';
		
		$reg_setting = $uniset['register_form'];
		
		$data['show_register'] = isset($reg_setting['popup']) ? $reg_setting['popup'] : '';
		
		$data['mask_telephone'] = isset($reg_setting['telephone']['mask'][$lang_id]) ? $reg_setting['telephone']['mask'][$lang_id] : '';
		
		$data['inputs'] = [
			'firstname'	=> [
				'placeholder'	=> isset($reg_setting['name']['text'][$lang_id]) ? $reg_setting['name']['text'][$lang_id] : '',
				'value'			=> '',
				'type'			=> 'text',
				'status' 		=> 1,
				'required' 		=> 1
			],
			'lastname'	=> [
				'placeholder'	=> isset($reg_setting['lastname']['text'][$lang_id]) ? $reg_setting['lastname']['text'][$lang_id] : '',
				'value'			=> '',
				'type'			=> 'text',
				'status' 		=> isset($reg_setting['lastname']['status']) ? true : false,
				'required' 		=> isset($reg_setting['lastname']['required']) ? true : false
			],
			'telephone'	=> [
				'placeholder'	=> isset($reg_setting['telephone']['text'][$lang_id]) ? $reg_setting['telephone']['text'][$lang_id] : '',
				'value'			=> '',
				'type'			=> 'tel',
				'status' 		=> isset($reg_setting['telephone']['status']) ? true : false,
				'required' 		=> isset($reg_setting['telephone']['required']) ? true : false
			],
			'email'	=> [
				'placeholder'	=> isset($reg_setting['email']['text'][$lang_id]) ? $reg_setting['email']['text'][$lang_id] : '',
				'value'			=> '',
				'type'			=> 'email',
				'status' 		=> 1,
				'required' 		=> 1
			],
			'password'	=> [
				'placeholder'	=> isset($reg_setting['password']['text'][$lang_id]) ? $reg_setting['password']['text'][$lang_id] : '',
				'value'			=> '',
				'type'			=> 'password',
				'status' 		=> 1,
				'required' 		=> 1
			],
			'confirm'	=> [
				'placeholder'	=>isset($reg_setting['password_confirm']['text'][$lang_id]) ? $reg_setting['password_confirm']['text'][$lang_id] : '',
				'value'			=> '',
				'type'			=> 'password',
				'status' 		=> isset($reg_setting['password_confirm']['status']) ? true : false,
				'required' 		=> 1
			]
		];
		
		$data['show_login_link'] = isset($reg_setting['login']) ? true : '';
		
		$data['show_newsletter'] = isset($reg_setting['newsletter']) ? true : '';
		
		$data['register_link'] = $this->url->link('account/register', '', true);
		
		$data['logged'] = $this->customer->isLogged();
		
		$this->load->model('account/customer_group');
		$data['customer_groups'] = [];
			
		if (is_array($this->config->get('config_customer_group_display'))) {
			$customer_groups = $this->model_account_customer_group->getCustomerGroups();
			
			foreach ($customer_groups as $customer_group) {
				if (in_array($customer_group['customer_group_id'], $this->config->get('config_customer_group_display'))) {
					$data['customer_groups'][] = $customer_group;
				}
			}
		}
		
		$data['customer_group_id'] = $this->config->get('config_customer_group_id');
		
		$data['custom_fields'] = [];
		
		$this->load->model('account/custom_field');
		
		$custom_fields = $this->model_account_custom_field->getCustomFields();
		
		foreach ($custom_fields as $custom_field) {
			if ($custom_field['location'] == 'account') {
				$data['custom_fields'][] = $custom_field;
			}
		}		
		
		if (isset($reg_setting['captcha']) && $this->config->get('captcha_'.$this->config->get('config_captcha').'_status')) {
			$data['captcha'] = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha'));
		} else {
			$data['captcha'] = '';
		}
		
		if (isset($reg_setting['agree']) && $this->config->get('config_account_id')) {
			$this->load->model('catalog/information');
			
			$information_info = $this->model_catalog_information->getInformation($this->config->get('config_account_id'));
			$data['text_agree'] = $information_info ? sprintf($this->language->get('text_agree'), $this->url->link('information/information/agree', 'information_id=' . $this->config->get('config_account_id'), true), $information_info['title'], $information_info['title']) : '';
		} else {
			$data['text_agree'] = '';
		}
		
		$data['error_firstname'] = '';
		$data['error_lastname'] = '';
		$data['error_email'] = '';
		$data['error_telephone'] = '';
		$data['error_password'] = '';
		$data['error_confirm'] = '';
	
		return $data;
	}
	
	public function modal() {
		if (!$this->validateRequest()) {
			return;
		}
		
		$type = isset($this->request->post['type']) ? $this->request->post['type'] : '';
		
		if($type == 'login' || $type == 'register') {
			$template = $type;
		} else {
			return false;
		}
		
		$data = $this->index();
		
		$this->response->setOutput($this->load->view('extension/module/uni_'.$type, $data));
	}
	
	public function page() {
		if ($this->customer->isLogged()) {
			$this->response->redirect($this->url->link('account/account', '', true));
		}
		
		$data = $this->index();
		
		$this->document->addStyle('catalog/view/theme/unishop2/stylesheet/login_register.css');
		$this->document->addStyle('catalog/view/theme/unishop2/stylesheet/account.css');
		$this->document->addScript('catalog/view/theme/unishop2/js/login-register.js');
		$this->document->addScript('catalog/view/theme/unishop2/js/jquery.maskedinput.min.js');
		
		$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment.min.js');
		$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment-with-locales.min.js');
		$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.js');
		$this->document->addStyle('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.css');
		
		$this->document->setTitle($this->language->get('heading_title'));
		
		$data['heading_title'] = $this->language->get('heading_title');
		
		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		];
		
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_account'),
			'href' => $this->url->link('account/account', '', true)
		];
		
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/uni_login_register/page')
		];
		
		$data['uni_register'] = true;
		
		$data['text_account_already'] = sprintf($this->language->get('text_account_already'), $this->url->link('account/login', '', true));
		
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');
		
		$this->response->setOutput($this->load->view('account/register', $data));
	}
	
	public function login() {
		$this->load->model('account/customer');
		$this->load->language('extension/module/uni_login_register');
		
		$json = [];
		
		$email = isset($this->request->post['email']) ? htmlspecialchars(strip_tags($this->request->post['email']), ENT_QUOTES, 'UTF-8') : '';
		$password = isset($this->request->post['password']) ? htmlspecialchars(strip_tags($this->request->post['password']), ENT_QUOTES, 'UTF-8') : '';
	
		if (!$this->customer->login($email, $password)) {
			$json['error'] = $this->language->get('error_popup_login');
		} else {
			$json['redirect'] = $this->url->link('account/account', '', true);
		}
		
		if (!$json) {
			unset($this->session->data['guest']);
			unset($this->session->data['shipping_country_id']);	
			unset($this->session->data['shipping_zone_id']);	
			unset($this->session->data['shipping_postcode']);
			unset($this->session->data['payment_country_id']);	
			unset($this->session->data['payment_zone_id']);	
		}
		
		$this->response->setOutput(json_encode($json));	
	}
	
	public function register() {
		
		if (!$this->validateRequest()) {
			return;
		}
		
		$this->load->language('account/register');
		$this->load->language('extension/module/uni_login_register');
		
		$this->load->model('account/customer');
		
		$uniset = $this->config->get('config_unishop2');
		$lang_id = $this->config->get('config_language_id');
		
		$reg_setting = $uniset['register_form'];
		
		$firstname = isset($this->request->post['firstname']) ? trim($this->request->post['firstname']) : '';
		$lastname = isset($this->request->post['lastname']) ? trim($this->request->post['lastname']) : '';
		$telephone = isset($this->request->post['telephone']) ? trim($this->request->post['telephone']) : '';
		$email = isset($this->request->post['email']) ? trim($this->request->post['email']) : '';
		$password = isset($this->request->post['password']) ? trim($this->request->post['password']) : '';
		$confirm = isset($this->request->post['confirm']) ? trim($this->request->post['confirm']) : '';
		
		if(isset($reg_setting['page']) || isset($reg_setting['popup'])) {
		
			$json = [];
						
			if ((mb_strlen($firstname) < 3) || (mb_strlen($firstname) > 32)) {
				$json['error']['firstname'] = $this->language->get('error_firstname');
			}
			
			if (isset($reg_setting['lastname']['status'])) {
				if ((isset($reg_setting['lastname']['required']) || $lastname) && (mb_strlen($lastname) < 3) || (mb_strlen($lastname) > 32)){
					$json['error']['lastname'] = $this->language->get('error_lastname');
				}
			} else {
				$lastname = '';
			}
			
			if (isset($reg_setting['telephone']['status'])) {
				if ((isset($reg_setting['telephone']['required']) || $telephone) && (mb_strlen($telephone) < 3) || (mb_strlen($telephone) > 32)) {
					$json['error']['telephone'] = $this->language->get('error_telephone');
				}
			} else {
				$telephone = '';
			}
		
			if ((mb_strlen($email) > 96) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
				$json['error']['email'] = $this->language->get('error_email');
			}
	
			if ($this->model_account_customer->getTotalCustomersByEmail($email)) {
				$json['error']['email'] = $this->language->get('error_exists');
			}
			
			$this->load->model('account/customer_group');
			
			if (isset($this->request->post['customer_group_id']) && is_array($this->config->get('config_customer_group_display')) && in_array($this->request->post['customer_group_id'], $this->config->get('config_customer_group_display'))) {
				$customer_group_id = $this->request->post['customer_group_id'];
			} else {
				$customer_group_id = $this->config->get('config_customer_group_id');
			}
	
			if ((mb_strlen($password) < 4) || (mb_strlen($password) > 20)) {
				$json['error']['password'] = $this->language->get('error_password');
			}
		
			if (isset($reg_setting['password_confirm']['status'])&& ($confirm != $password || isset($json['error']['password']))) {
				$json['error']['confirm'] = $this->language->get('error_confirm');
			}
		
			$this->load->model('account/custom_field');

			$custom_fields = $this->model_account_custom_field->getCustomFields($customer_group_id);

			foreach ($custom_fields as $custom_field) {
				if ($custom_field['location'] == 'account') {
					if ($custom_field['required'] && empty($this->request->post['custom_field'][$custom_field['location']][$custom_field['custom_field_id']])) {
						$json['error']['custom_field'] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
					} elseif (($custom_field['type'] == 'text') && !empty($custom_field['validation']) && !filter_var($this->request->post['custom_field'][$custom_field['location']][$custom_field['custom_field_id']], FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => $custom_field['validation'])))) {
						$json['error']['custom_field'] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
					}
				}
			}
			
			if (isset($reg_setting['captcha']) && $this->config->get('captcha_'.$this->config->get('config_captcha').'_status')) {
				$captcha = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha') . '/validate');

				if ($captcha) {
					$json['error']['captcha'] = $captcha;
				}
			}
		
			if(isset($reg_setting['agree'])) {
				if ($this->config->get('config_account_id')) {
					$this->load->model('catalog/information');
				
					$information_info = $this->model_catalog_information->getInformation($this->config->get('config_account_id'));
					
					if ($information_info && !isset($this->request->post['agree'])) {
						$json['error']['agree'] = sprintf($this->language->get('error_agree'), $information_info['title']);
					}
				}
			}
		
			if (!isset($this->request->post['pass-c']) || (isset($this->request->post['pass-c']) && ($this->request->post['pass-c'] != ''))) {
				$json['error']['password'] = $this->language->get('error_password');
			}
		
			if(!$json) {
				$data['firstname'] = $firstname;
				$data['lastname'] = $lastname;
				$data['email'] = $email;
				$data['telephone'] = $telephone;
				$data['password'] = $password;
				$data['customer_group_id'] = $customer_group_id;
				$data['newsletter'] = isset($this->request->post['newsletter']) ? $this->request->post['newsletter'] : 0;
				$data['fax'] = '';
				$data['company'] = '';
				$data['address_1'] = '';
				$data['address_2'] = '';
				$data['postcode'] = '';
				$data['city'] = '';
				$data['custom_field'] = isset($this->request->post['custom_field']) ? $this->request->post['custom_field'] :[];
				$data['country_id'] = $this->config->get('config_country_id');
				$data['zone_id'] = $this->config->get('config_zone_id');
		
				$customer_id = $this->model_account_customer->addCustomer($data);
		
				$this->session->data['account'] = 'register';
				$this->session->data['customer_id'] = $customer_id;
							  	  
				if ($this->customer->login($email, $password)) {
					$json['redirect'] = $this->url->link('account/account', '', true);
				}
			
				$this->load->model('account/customer_group');
			
				$customer_group_info = $this->model_account_customer_group->getCustomerGroup($customer_group_id);
			
				if ($customer_group_info['approval']) {
					$json['appruv'] = $this->language->get('text_appruv');
				}
			
				unset($this->session->data['guest']);
				unset($this->session->data['shipping_method']);
				unset($this->session->data['shipping_methods']);
				unset($this->session->data['payment_method']);	
				unset($this->session->data['payment_methods']);
			}
		
			$this->response->setOutput(json_encode($json));	
		}
	}
	
	private function validateRequest() {
		if (!isset($this->request->server['HTTP_X_REQUESTED_WITH']) || strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
			return false;
		} else {
			return true;
		}
	}
}
?>