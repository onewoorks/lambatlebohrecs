<?php
class ControllerExtensionShippingEasyparcel extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/shipping/easyparcel');
		
		$this->document->setTitle($this->language->get('heading_title'));
		
		$this->load->model('setting/setting');
		
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('shipping_easyparcel', $this->request->post);
			
			$this->session->data['success'] = $this->language->get('text_success');
			
			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true));
		}

		$data['heading_title'] = $this->language->get('heading_title');
		
		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_all_zones'] = $this->language->get('text_all_zones');
		$data['text_none'] = $this->language->get('text_none');
		$data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');
		$data['text_no_rate_d'] = $this->language->get('text_no_rate_d');
		$data['text_account_rate_d'] = $this->language->get('text_account_rate_d');
		$data['text_public_rate_d'] = $this->language->get('text_public_rate_d');
		$data['text_rate_increase'] = $this->language->get('text_rate_increase');
		$data['text_rate_decrease'] = $this->language->get('text_rate_decrease');
		$data['text_rate_none'] = $this->language->get('text_rate_none');

		$data['entry_email'] = $this->language->get('entry_email');
		$data['entry_integration'] = $this->language->get('entry_integration');
		$data['entry_address'] = $this->language->get('entry_address');
		$data['entry_postcode'] = $this->language->get('entry_postcode');
		$data['entry_state'] = $this->language->get('entry_state');
		$data['entry_country'] = $this->language->get('entry_country');
		$data['entry_display_rate'] = $this->language->get('entry_display_rate');
		$data['entry_display_rate_option'] = $this->language->get('entry_display_rate_option');
		$data['entry_display_weight'] = $this->language->get('entry_display_weight');
		$data['entry_weight_class'] = $this->language->get('entry_weight_class');
		$data['entry_tax_class'] = $this->language->get('entry_tax_class');
		$data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_sort_order'] = $this->language->get('entry_sort_order');
	
		$data['help_display_rate'] = $this->language->get('help_display_rate');
		$data['help_display_rate_option'] = $this->language->get('help_display_rate_option');
		$data['help_display_weight'] = $this->language->get('help_display_weight');
		$data['help_weight_class'] = $this->language->get('help_weight_class');
		$data['help_display_comment'] = $this->language->get('help_display_comment');
		$data['help_display_rate_comment'] = $this->language->get('help_display_rate_comment');
		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}
		
		if (isset($this->error['email'])) {
			$data['error_email'] = $this->error['email'];
		} else {
			$data['error_email'] = '';
		}
		
		if (isset($this->error['integration'])) {
			$data['error_integration'] = $this->error['integration'];
		} else {
			$data['error_integration'] = '';
		}
		
		if (isset($this->error['address'])) {
			$data['error_address'] = $this->error['address'];
		} else {
			$data['error_address'] = '';
		}
		
		if (isset($this->error['postcode'])) {
			$data['error_postcode'] = $this->error['postcode'];
		} else {
			$data['error_postcode'] = '';
		}
		
		if (isset($this->error['country'])) {
			$data['error_country'] = $this->error['country'];
		} else {
			$data['error_country'] = '';
		}
		
		if (isset($this->error['rate_value'])) {
			$data['error_rate_value'] = $this->error['rate_value'];
		} else {
			$data['error_rate_value'] = '';
		}
		
		if (isset($this->error['invalid'])) {
			$data['error_invalid'] = $this->error['invalid'];
		} else {
			$data['error_invalid'] = '';
		}
		
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_shipping'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/shipping/easyparcel', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/shipping/easyparcel', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true);
		
		if (isset($this->request->post['shipping_easyparcel_email'])) {
			$data['shipping_easyparcel_email'] = $this->request->post['shipping_easyparcel_email'];
		} else {
			$data['shipping_easyparcel_email'] = $this->config->get('shipping_easyparcel_email');
		}
		
		if (isset($this->request->post['shipping_easyparcel_integration'])) {
			$data['shipping_easyparcel_integration'] = $this->request->post['shipping_easyparcel_integration'];
		} else {
			$data['shipping_easyparcel_integration'] = $this->config->get('shipping_easyparcel_integration');
		}
		
		if (isset($this->request->post['shipping_easyparcel_address'])) {
			$data['shipping_easyparcel_address'] = $this->request->post['shipping_easyparcel_address'];
		} else {
			$data['shipping_easyparcel_address'] = $this->config->get('shipping_easyparcel_address');
		}
		
		if (isset($this->request->post['shipping_easyparcel_postcode'])) {
			$data['shipping_easyparcel_postcode'] = $this->request->post['shipping_easyparcel_postcode'];
		} else {
			$data['shipping_easyparcel_postcode'] = $this->config->get('shipping_easyparcel_postcode');
		}
		
		if (isset($this->request->post['shipping_easyparcel_state'])) {
			$data['shipping_easyparcel_state'] = $this->request->post['shipping_easyparcel_state'];
		} else {
			$data['shipping_easyparcel_state'] = $this->config->get('shipping_easyparcel_state');
		}
		
		if (isset($this->request->post['shipping_easyparcel_country'])) {
			$data['shipping_easyparcel_country'] = $this->request->post['shipping_easyparcel_country'];
		} else {
			$data['shipping_easyparcel_country'] = $this->config->get('shipping_easyparcel_country');
		}
		
		if (isset($this->request->post['shipping_easyparcel_show_rate_type'])) {
			$data['shipping_easyparcel_show_rate_type'] = $this->request->post['shipping_easyparcel_show_rate_type'];
		} else {
			$data['shipping_easyparcel_show_rate_type'] = $this->config->get('shipping_easyparcel_show_rate_type');
		}
		
		if (isset($this->request->post['shipping_easyparcel_rate_adjustment_type'])) {
			$data['shipping_easyparcel_rate_adjustment_type'] = $this->request->post['shipping_easyparcel_rate_adjustment_type'];
		} else {
			$data['shipping_easyparcel_rate_adjustment_type'] = $this->config->get('shipping_easyparcel_rate_adjustment_type');
		}
		
		if (isset($this->request->post['shipping_easyparcel_rate_adjustment_amount'])) {
			$data['shipping_easyparcel_rate_adjustment_amount'] = $this->request->post['shipping_easyparcel_rate_adjustment_amount'];
		} else {
			$data['shipping_easyparcel_rate_adjustment_amount'] = $this->config->get('shipping_easyparcel_rate_adjustment_amount');
		}
		
		if (isset($this->request->post['shipping_easyparcel_display_weight'])) {
			$data['shipping_easyparcel_display_weight'] = $this->request->post['shipping_easyparcel_display_weight'];
		} else {
			$data['shipping_easyparcel_display_weight'] = $this->config->get('shipping_easyparcel_display_weight');
		}
		
		if (isset($this->request->post['shipping_easyparcel_weight_class_id'])) {
			$data['shipping_easyparcel_weight_class_id'] = $this->request->post['shipping_easyparcel_weight_class_id'];
		} else {
			$data['shipping_easyparcel_weight_class_id'] = $this->config->get('shipping_easyparcel_weight_class_id');
		}
		
		$this->load->model('localisation/weight_class');

		$data['weight_classes'] = $this->model_localisation_weight_class->getWeightClasses();
		
		$this->load->model('localisation/country');
		
		$data['country_list'] = $this->model_localisation_country->getCountries($data);
		
		$this->load->model('localisation/zone');

		$data['state_list'] = $this->model_localisation_zone->getZonesByCountryId(129);
		
		if (isset($this->request->post['shipping_easyparcel_tax_class_id'])) {
			$data['shipping_easyparcel_tax_class_id'] = $this->request->post['shipping_easyparcel_tax_class_id'];
		} else {
			$data['shipping_easyparcel_tax_class_id'] = $this->config->get('shipping_easyparcel_tax_class_id');
		}

		$this->load->model('localisation/tax_class');

		$data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();

		if (isset($this->request->post['shipping_easyparcel_geo_zone_id'])) {
			$data['shipping_easyparcel_geo_zone_id'] = $this->request->post['shipping_easyparcel_geo_zone_id'];
		} else {
			$data['shipping_easyparcel_geo_zone_id'] = $this->config->get('shipping_easyparcel_geo_zone_id');
		}

		$this->load->model('localisation/geo_zone');

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();
		
		if (isset($this->request->post['shipping_easyparcel_status'])) {
			$data['shipping_easyparcel_status'] = $this->request->post['shipping_easyparcel_status'];
		} else {
			$data['shipping_easyparcel_status'] = $this->config->get('shipping_easyparcel_status');
		}

		if (isset($this->request->post['shipping_easyparcel_sort_order'])) {
			$data['shipping_easyparcel_sort_order'] = $this->request->post['shipping_easyparcel_sort_order'];
		} else {
			$data['shipping_easyparcel_sort_order'] = $this->config->get('shipping_easyparcel_sort_order');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/shipping/easyparcel', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/shipping/easyparcel')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		
		if (!$this->request->post['shipping_easyparcel_email']) {
			$this->error['email'] = $this->language->get('error_email');
		}
		
		if (!$this->request->post['shipping_easyparcel_integration']) {
			$this->error['integration'] = $this->language->get('error_integration');
		}
		
		if (!$this->request->post['shipping_easyparcel_address']) {
			$this->error['address'] = $this->language->get('error_address');
		}
		
		if (!$this->request->post['shipping_easyparcel_postcode']) {
			$this->error['postcode'] = $this->language->get('error_postcode');
		}
		
		if (!$this->request->post['shipping_easyparcel_country']) {
			$this->error['country'] = $this->language->get('error_country');
		}
		
		if ($this->request->post['shipping_easyparcel_rate_adjustment_type'] != '0') {
			if(!$this->request->post['shipping_easyparcel_rate_adjustment_amount']){
				$this->error['rate_value'] = $this->language->get('error_rate_value_1');
			}
			else{
				if(!is_numeric($this->request->post['shipping_easyparcel_rate_adjustment_amount'])){
					if($this->request->post['shipping_easyparcel_rate_adjustment_amount'] != '0'){
						$this->error['rate_value'] = $this->language->get('error_rate_value');
					}
				}
			}
		}
		
		if (($this->request->post['shipping_easyparcel_email'])&&($this->request->post['shipping_easyparcel_integration'])) {
			$pv = ''; 
			$f = array(
				'integration_id'=> $this->request->post['shipping_easyparcel_integration'],
				'user_email'=> $this->request->post['shipping_easyparcel_email']
			);
			foreach($f as $k => $v){ $pv .= $k . "=" . $v . "&"; }
			$url = "https://easyparcel.com/my/en/?ac=CheckValidUser";
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $pv);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			ob_start();
			$r = curl_exec($ch);
			ob_end_clean();
			curl_close ($ch);
			$json = json_decode($r);
			if (!isset($json->message) || $json->message != "Success.") {
				$this->error['invalid'] = $this->language->get('error_invalid');
			}
		}
		return !$this->error;
	}
}