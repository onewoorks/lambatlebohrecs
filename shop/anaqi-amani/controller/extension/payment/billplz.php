<?php

class ControllerExtensionPaymentBillplz extends Controller
{
    private $error = array();

    public function index()
    {
        $this->load->language('extension/payment/billplz');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('payment_billplz', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
        }

        $data['heading_title'] = $this->language->get('heading_title');

        $data['text_edit'] = $this->language->get('text_edit');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['text_all_zones'] = $this->language->get('text_all_zones');
        $data['text_yes'] = $this->language->get('text_yes');
        $data['text_no'] = $this->language->get('text_no');

        $data['billplz_is_sandbox'] = $this->language->get('billplz_is_sandbox');
        $data['billplz_api_key'] = $this->language->get('billplz_api_key');
        $data['billplz_collection_id'] = $this->language->get('billplz_collection_id');
        $data['billplz_x_signature'] = $this->language->get('billplz_x_signature');

        $data['entry_total'] = $this->language->get('entry_total');
        $data['entry_completed_status'] = $this->language->get('entry_completed_status');
        $data['entry_pending_status'] = $this->language->get('entry_pending_status');
        $data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
        $data['entry_sort_order'] = $this->language->get('entry_sort_order');
        $data['entry_status'] = $this->language->get('entry_status');

        $data['help_is_sandbox'] = $this->language->get('help_is_sandbox');
        $data['help_api_key'] = $this->language->get('help_api_key');
        $data['help_collection_id'] = $this->language->get('help_collection_id');
        $data['help_x_signature'] = $this->language->get('help_x_signature');
        $data['help_total'] = $this->language->get('help_total');

        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

        $data['tab_api_details'] = $this->language->get('tab_api_details');
        $data['tab_general'] = $this->language->get('tab_general');
        $data['tab_order_status'] = $this->language->get('tab_order_status');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['api_key'])) {
            $data['error_api_key'] = $this->error['api_key'];
        } else {
            $data['error_api_key'] = '';
        }

        if (isset($this->error['collection_id'])) {
            $data['error_collection_id'] = $this->error['collection_id'];
        } else {
            $data['error_collection_id'] = '';
        }

        if (isset($this->error['x_signature'])) {
            $data['error_x_signature'] = $this->error['x_signature'];
        } else {
            $data['error_x_signature'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/billplz', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('extension/payment/billplz', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

        if (isset($this->request->post['payment_billplz_is_sandbox_value'])) {
            $data['billplz_is_sandbox_value'] = $this->request->post['payment_billplz_is_sandbox_value'];
        } else {
            $data['billplz_is_sandbox_value'] = $this->config->get('payment_billplz_is_sandbox_value');
        }

        if (isset($this->request->post['payment_billplz_api_key_value'])) {
            $data['billplz_api_key_value'] = $this->request->post['payment_billplz_api_key_value'];
        } else {
            $data['billplz_api_key_value'] = $this->config->get('payment_billplz_api_key_value');
        }

        if (isset($this->request->post['payment_billplz_collection_id_value'])) {
            $data['billplz_collection_id_value'] = $this->request->post['payment_billplz_collection_id_value'];
        } else {
            $data['billplz_collection_id_value'] = $this->config->get('payment_billplz_collection_id_value');
        }

        if (isset($this->request->post['payment_billplz_x_signature_value'])) {
            $data['billplz_x_signature_value'] = $this->request->post['payment_billplz_x_signature_value'];
        } else {
            $data['billplz_x_signature_value'] = $this->config->get('payment_billplz_x_signature_value');
        }

        if (isset($this->request->post['payment_billplz_total'])) {
            $data['billplz_total'] = $this->request->post['payment_billplz_total'];
        } else {
            $data['billplz_total'] = $this->config->get('payment_billplz_total');
        }

        if (isset($this->request->post['payment_billplz_completed_status_id'])) {
            $data['billplz_completed_status_id'] = $this->request->post['payment_billplz_completed_status_id'];
        } else {
            $data['billplz_completed_status_id'] = $this->config->get('payment_billplz_completed_status_id');
        }

        if (isset($this->request->post['billplz_pending_status_id'])) {
            $data['billplz_pending_status_id'] = $this->request->post['payment_billplz_pending_status_id'];
        } else {
            $data['billplz_pending_status_id'] = $this->config->get('payment_billplz_pending_status_id');
        }

        $this->load->model('localisation/order_status');

        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        if (isset($this->request->post['payment_billplz_geo_zone_id'])) {
            $data['billplz_geo_zone_id'] = $this->request->post['payment_billplz_geo_zone_id'];
        } else {
            $data['billplz_geo_zone_id'] = $this->config->get('payment_billplz_geo_zone_id');
        }

        $this->load->model('localisation/geo_zone');

        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        if (isset($this->request->post['payment_billplz_status'])) {
            $data['billplz_status'] = $this->request->post['payment_billplz_status'];
        } else {
            $data['billplz_status'] = $this->config->get('payment_billplz_status');
        }

        if (isset($this->request->post['payment_billplz_sort_order'])) {
            $data['billplz_sort_order'] = $this->request->post['payment_billplz_sort_order'];
        } else {
            $data['billplz_sort_order'] = $this->config->get('payment_billplz_sort_order');
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/billplz', $data));
    }

    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/payment/billplz')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->request->post['payment_billplz_api_key_value']) {
            $this->error['api_key'] = $this->language->get('error_api_key');
        }

        if (!$this->request->post['payment_billplz_collection_id_value']) {
            $this->error['collection_id'] = $this->language->get('error_collection_id');
        }

        if (!$this->request->post['payment_billplz_x_signature_value']) {
            $this->error['x_signature'] = $this->language->get('error_x_signature');
        }

        return !$this->error;
    }

    public function install() {
        $this->load->model('extension/payment/billplz');
        $this->model_extension_payment_billplz->install();
    }

    public function uninstall() {
        $this->load->model('extension/payment/billplz');
        $this->model_extension_payment_billplz->uninstall();
    }
}
