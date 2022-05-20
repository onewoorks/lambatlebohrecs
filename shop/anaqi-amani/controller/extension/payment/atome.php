<?php
class ControllerExtensionPaymentAtome extends Controller {
    private $version = '2.1.1';
    private $error = array();

    public function index() {
        $this->load->language('extension/payment/atome');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('payment_atome', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
        }

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
    
        if (isset($this->error['password'])) {
            $data['error_password'] = $this->error['password'];
        } else {
            $data['error_password'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true),
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/atome', 'user_token=' . $this->session->data['user_token'], true),
        );

        $data['action'] = $this->url->link('extension/payment/atome', 'user_token=' . $this->session->data['user_token'], true);
        
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);
        
        $this->load->model('localisation/country');

        $country_info = $this->model_localisation_country->getCountry($this->config->get('config_country_id'));

        if (isset($this->request->post['payment_atome_country'])) {
            $data['payment_atome_country'] = $this->request->post['payment_atome_country'];
        } else {
            $data['payment_atome_country'] = $this->config->get('payment_atome_country');
        }

        if (isset($this->request->post['payment_atome_language'])) {
            $data['payment_atome_language'] = $this->request->post['payment_atome_language'];
        } else {
            $data['payment_atome_language'] = $this->config->get('payment_atome_language');
        }

        if (isset($this->request->post['payment_atome_api_key'])) {
            $data['payment_atome_api_key'] = $this->request->post['payment_atome_api_key'];
        } else {
            $data['payment_atome_api_key'] = $this->config->get('payment_atome_api_key');
        }
    
        if (isset($this->request->post['payment_atome_password'])) {
            $data['payment_atome_password'] = $this->request->post['payment_atome_password'];
        } else {
            $data['payment_atome_password'] = $this->config->get('payment_atome_password');
        }

        if (isset($this->request->post['payment_atome_test'])) {
            $data['payment_atome_test'] = $this->request->post['payment_atome_test'];
        } else {
            $data['payment_atome_test'] = $this->config->get('payment_atome_test');
        }

        if (isset($this->request->post['payment_atome_debug'])) {
            $data['payment_atome_debug'] = $this->request->post['payment_atome_debug'];
        } else {
            $data['payment_atome_debug'] = $this->config->get('payment_atome_debug');
        }

        $this->load->model('extension/payment/atome');

        if (isset($this->request->post['payment_atome_geo_zone_id'])) {
            $data['payment_atome_geo_zone_id'] = $this->request->post['payment_atome_geo_zone_id'];
        } else {
            $data['payment_atome_geo_zone_id'] = $this->config->get('payment_atome_geo_zone_id');
        }

        $this->load->model('localisation/geo_zone');

        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        if (isset($this->request->post['payment_atome_product_listing'])) {
            $data['payment_atome_product_listing'] = $this->request->post['payment_atome_product_listing'];
        } else {
            $data['payment_atome_product_listing'] = $this->config->get('payment_atome_product_listing');
        }

        if (isset($this->request->post['payment_atome_product_page'])) {
            $data['payment_atome_product_page'] = $this->request->post['payment_atome_product_page'];
        } else {
            $data['payment_atome_product_page'] = $this->config->get('payment_atome_product_page');
        }

        if (isset($this->request->post['payment_atome_status'])) {
            $data['payment_atome_status'] = $this->request->post['payment_atome_status'];
        } else {
            $data['payment_atome_status'] = $this->config->get('payment_atome_status');
        }

        if (isset($this->request->post['payment_atome_sort_order'])) {
            $data['payment_atome_sort_order'] = $this->request->post['payment_atome_sort_order'];
        } else {
            $data['payment_atome_sort_order'] = $this->config->get('payment_atome_sort_order');
        }

        if (isset($this->request->post['payment_atome_processing_status_id'])) {
            $data['payment_atome_processing_status_id'] = $this->request->post['payment_atome_processing_status_id'];
        } else {
            $data['payment_atome_processing_status_id'] = $this->config->get('payment_atome_processing_status_id');
        }

        if (isset($this->request->post['payment_atome_paid_status_id'])) {
            $data['payment_atome_paid_status_id'] = $this->request->post['payment_atome_paid_status_id'];
        } else {
            $data['payment_atome_paid_status_id'] = $this->config->get('payment_atome_paid_status_id');
        }

        if (isset($this->request->post['payment_atome_failed_status_id'])) {
            $data['payment_atome_failed_status_id'] = $this->request->post['payment_atome_failed_status_id'];
        } else {
            $data['payment_atome_failed_status_id'] = $this->config->get('payment_atome_failed_status_id');
        }

        if (isset($this->request->post['payment_atome_refunded_status_id'])) {
            $data['payment_atome_refunded_status_id'] = $this->request->post['payment_atome_refunded_status_id'];
        } else {
            $data['payment_atome_refunded_status_id'] = $this->config->get('payment_atome_refunded_status_id');
        }

        if (isset($this->request->post['payment_atome_cancelled_status_id'])) {
            $data['payment_atome_cancelled_status_id'] = $this->request->post['payment_atome_cancelled_status_id'];
        } else {
            $data['payment_atome_cancelled_status_id'] = $this->config->get('payment_atome_cancelled_status_id');
        }

        $this->load->model('localisation/order_status');

        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/atome', $data));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/payment/atome')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->request->post['payment_atome_api_key']) {
            $this->error['api_key'] = $this->language->get('error_api_key');
        }
  
        if (!$this->request->post['payment_atome_password']) {
            $this->error['password'] = $this->language->get('error_password');
        }

        return !$this->error;
    }

    public function install() {
        $this->load->model('extension/payment/atome');

        $this->model_extension_payment_atome->install();
    }

    public function uninstall() {
        $this->load->model('extension/payment/atome');

        $this->model_extension_payment_atome->uninstall();
    }
    
    public function order() {
        if ($this->config->get('payment_atome_status')) {
            $this->load->model('extension/payment/atome');
      
            $atome_order = $this->model_extension_payment_atome->getOrder($this->request->get['order_id']);
      
            if (!empty($atome_order)) {
                $this->load->language('extension/payment/atome');

                $data['atome_order'] = $atome_order;
                
                $data['text_payment_info'] = $this->language->get('text_payment_info');
                $data['text_order_ref'] = $this->language->get('text_order_ref');
                $data['text_order_total'] = $this->language->get('text_order_total');
                $data['text_cancelled_status'] = $this->language->get('text_cancelled_status');
                $data['text_refund_status'] = $this->language->get('text_refund_status');
                $data['text_transactions'] = $this->language->get('text_transactions');
                $data['text_yes'] = $this->language->get('text_yes');
                $data['text_no'] = $this->language->get('text_no');
                $data['text_column_amount'] = $this->language->get('text_column_amount');
                $data['text_column_type'] = $this->language->get('text_column_type');
                $data['text_column_date_added'] = $this->language->get('text_column_date_added');
                $data['button_cancel'] = $this->language->get('button_cancel');
                $data['button_refund'] = $this->language->get('button_refund');
                $data['text_confirm_cancel'] = $this->language->get('text_confirm_cancel');
                $data['text_confirm_refund'] = $this->language->get('text_confirm_refund');
                $data['text_refund_amount'] = $this->language->get('text_refund_amount');
        
                $data['order_id'] = $this->request->get['order_id'];
                $data['user_token'] = $this->request->get['user_token'];
        
                return $this->load->view('extension/payment/atome_order', $data);
            }
        }
    }

    public function refund() {
        $json = array();

        $this->load->language('extension/payment/atome');

        if (isset($this->request->get['order_id'])) {
            $order_id = $this->request->get['order_id'];
        } else {
            $order_id = 0;
        }

        $this->load->model('extension/payment/atome');

        if (isset($this->request->post['refund_amount'])) {
            $refund_amount = array(
                'refundAmount' => $this->model_extension_payment_atome->getSendAmount( (float)$this->request->post['refund_amount'] ),
            );
        } else {
            $refund_amount = array();
        }

        $this->model_extension_payment_atome->log('refund_amount => ', json_encode($refund_amount));

        $atome_info = $this->model_extension_payment_atome->getOrder($order_id);

        if ($atome_info) {
            $curl = curl_init($this->model_extension_payment_atome->getApiUrl('v2/payments/' . $atome_info['atome_reference'] . '/refund'));

            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_USERPWD, $this->config->get('payment_atome_api_key') . ':' . $this->config->get('payment_atome_password'));
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($refund_amount));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_TIMEOUT, 30);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($curl);
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if (!$response) {
                $this->model_extension_payment_atome->log('DEBUG_LOGS', 'CURL failed in Order Cancel!');
                $json['error'] = $this->language->get('error_payment_gateway');
            } else {
                $response = json_decode($response, true);

                if ($http_code == '200') {
                    $this->model_extension_payment_atome->addTransaction($atome_info['atome_order_id'], 'refunded', $this->model_extension_payment_atome->getOriginAmount($response['refundableAmount']));

                    $json['success']= sprintf($this->language->get('text_refunded'), $order_id);
                } else if ($http_code == '400' || $http_code == '404') {
                    $this->model_extension_payment_atome->log($response['code'] . ': ' . $response['message']);
                    $json['error'] = $response['code'] . ': ' . $response['message'];
                } else {
                    $this->model_extension_payment_atome->log('DEBUG_LOGS', $this->language->get('error_unknown'));
                    $json['error'] = $this->language->get('error_unknown');
                }
            }
        } else {
            $json['error'] = $this->language->get('error_not_found');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function cancel() {
        $json = array();

        $this->load->language('extension/payment/atome');

        if (isset($this->request->get['order_id'])) {
            $order_id = $this->request->get['order_id'];
        } else {
            $order_id = 0;
        }

        $this->load->model('extension/payment/atome');

        $atome_info = $this->model_extension_payment_atome->getOrder($order_id);

        if ($atome_info) {
            $curl = curl_init($this->model_extension_payment_atome->getApiUrl('v2/payments/' . $atome_info['atome_reference'] . '/cancel'));

            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_USERPWD, $this->config->get('payment_atome_api_key') . ':' . $this->config->get('payment_atome_password'));
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_TIMEOUT, 30);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($curl);
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if (!$response) {
                $this->model_extension_payment_atome->log('DEBUG_LOGS', 'CURL failed in Order Cancel!');
                $json['error'] = $this->language->get('error_payment_gateway');
            } else {
                $response = json_decode($response, true);

                if ($http_code == '200') {
                    $this->model_extension_payment_atome->addTransaction($atome_info['atome_order_id'], 'cancelled', $response['amount']);
                    $this->model_extension_payment_atome->setAtomeOrderCancelStatus($order_id);

                    $json['success'] = sprintf($this->language->get('text_cancelled'), $order_id);
                } else if ($http_code == '400' || $http_code == '404') {
                    $this->model_extension_payment_atome->log($response['code'] . ': ' . $response['message']);
                    $json['error'] = $response['code'] . ': ' . $response['message'];
                } else {
                    $this->model_extension_payment_atome->log('DEBUG_LOGS', $this->language->get('error_unknown'));
                    $json['error'] = $this->language->get('error_unknown');
                }
            }
        } else {
            $json['error'] = $this->language->get('error_not_found');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
