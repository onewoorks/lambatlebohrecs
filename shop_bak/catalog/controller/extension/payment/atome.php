<?php
class ControllerExtensionPaymentAtome extends Controller {
    public function index() {
        $this->load->language('extension/payment/atome');

        $data['continue'] = $this->url->link('extension/payment/atome/checkout', '', true);

        unset($this->session->data['atome']);

        return $this->load->view('extension/payment/atome', $data);
    }

    public function checkout() {
        if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
            $this->response->redirect($this->url->link('checkout/cart'));
        }

        $this->load->model('extension/payment/atome');
        $this->load->model('checkout/order');

        $this->load->language('extension/payment/atome');

        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        $payment = array();

        $payment['referenceId'] = time() . 'OC' . $this->session->data['order_id'];
        $payment['currency'] = strtoupper($order_info['currency_code']);
        $payment['amount'] = $this->model_extension_payment_atome->getSendAmount( $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false) );
        $payment['callbackUrl'] = $this->url->link('extension/payment/atome/callback', '', true);
        $payment['paymentResultUrl'] = $this->url->link('checkout/success', '', true);
        $payment['paymentCancelUrl'] = $this->url->link('checkout/checkout', '', true);
        $payment['merchantReferenceId'] = $this->session->data['order_id'];
        
        $payment['customerInfo'] = array();
        $payment['customerInfo']['mobileNumber'] = $order_info['telephone'];
        $payment['customerInfo']['fullName'] = $order_info['payment_firstname'] . ' ' . $order_info['payment_lastname'];
        $payment['customerInfo']['email'] = $order_info['email'];

        $payment['shippingAddress'] = array();
        $payment['shippingAddress']['countryCode'] = $order_info['shipping_iso_code_2'];
        $payment['shippingAddress']['lines'] = array();
        $payment['shippingAddress']['lines'][] = $order_info['shipping_address_1'];
        
        if ($order_info['shipping_address_2']) {
            $payment['shippingAddress']['lines'][] = $order_info['shipping_address_2'];
        }
        
        $payment['shippingAddress']['postCode'] = $order_info['shipping_postcode'] ?: '000000';

        $payment['billingAddress'] = array();
        $payment['billingAddress']['countryCode'] = $order_info['payment_iso_code_2'];
        $payment['billingAddress']['lines'] = array();
        $payment['billingAddress']['lines'][] = $order_info['payment_address_1'];
        
        if ($order_info['payment_address_2']) {
            $payment['billingAddress']['lines'][] = $order_info['payment_address_2'];
        }
        
        if ($order_info['payment_address_2']) {
            $payment['billingAddress']['lines'][] = $order_info['payment_address_2'];
        }
        
        $payment['billingAddress']['postCode'] = $order_info['payment_postcode'] ?: '000000';

        $taxes = $this->model_extension_payment_atome->getSendAmount( $this->currency->format($this->cart->getTaxes(), $order_info['currency_code'], $order_info['currency_value'], false) );
        $payment['taxAmount'] = $taxes;

        if ($this->cart->hasShipping() && isset($this->session->data['shipping_method'])) {
            $shipping_cost = $this->model_extension_payment_atome->getSendAmount( $this->currency->format($this->session->data['shipping_method']['cost'], $order_info['currency_code'], $order_info['currency_value'], false) );
        } else {
            $shipping_cost = 0;
        }
        
        $payment['shippingAmount'] = $shipping_cost;
        
        $payment['items'] = array();

        foreach ($this->cart->getProducts() as $product) {
            $option_data = array();

            $length = count($product['option']);
            $count = 0;
    
            foreach ($product['option'] as $option) {
                $count++;

                if ($option['type'] != 'file') {
                    $option_data[] = $option['name'] . ': ' . $option['value'];
                }
            }

            if ($option_data) {
                $payment['items'][] = array(
                    'itemId'          => $product['model'],
                    'name'            => htmlspecialchars($product['name']),
                    'price'           => $this->model_extension_payment_atome->getSendAmount( $this->currency->format($product['price'], $order_info['currency_code'], $order_info['currency_value'], false) ),
                    'quantity'        => $product['quantity'],
                    'variantionName'  => implode(', ', $option_data)
                );
            } else {
                $payment['items'][] = array(
                    'itemId'          => $product['model'],
                    'name'            => htmlspecialchars($product['name']),
                    'price'           => $this->model_extension_payment_atome->getSendAmount( $this->currency->format($product['price'], $order_info['currency_code'], $order_info['currency_value'], false) ),
                    'quantity'        => $product['quantity']
                );
            }
        }
        
        $this->model_extension_payment_atome->log('DEBUG_LOGS', 'CURL Payment ' . print_r($payment, true));

        $curl = curl_init($this->model_extension_payment_atome->getApiUrl('v2/payments'));

        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_USERPWD, $this->config->get('payment_atome_api_key') . ':' . $this->config->get('payment_atome_password'));
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payment));
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if (!$response) {
            $this->model_extension_payment_atome->log('DEBUG_LOGS', 'CURL failed in Checkout!');
            $this->session->data['error'] = $this->language->get('error_payment_gateway');
            $this->response->redirect($this->url->link('checkout/checkout', '', true));
        }
        
        $this->model_extension_payment_atome->log('DEBUG_LOGS', 'CURL Response ' . $response);

        if ($http_code == '200') {
            $response = json_decode($response, true);

            if (isset($response['redirectUrl'])) {
                $this->response->redirect($response['redirectUrl']);
            } else {
                $this->session->data['error'] = $this->language->get('error_unknown_response');
                $this->response->redirect($this->url->link('checkout/checkout', '', true));
            }
        } elseif ($http_code == '400') {
            $error = json_decode($response, true);

            if (isset($error['code']) && isset($error['message'])) {
                $this->model_extension_payment_atome->log($error['code'], $error['message'] . ' Order ID: ' .  $this->session->data['order_id']);
            } else {
                $this->model_extension_payment_atome->log('DEBUG_LOGS', 'CURL Request in Checkout returned HTTP 400 Code!');
            }
            
            if (isset($error['message'])) {
                $this->session->data['error'] = $this->language->get('error_payment_failed') . ': ' . $error['message'];
            } else {
                $this->session->data['error'] = $this->language->get('error_payment_failed');
            }

            $this->response->redirect($this->url->link('checkout/checkout', '', true));
        } else {
            $this->model_extension_payment_atome->log('DEBUG_LOGS', 'Invalid HTTP Response Code Received!');
            $this->session->data['error'] = $this->language->get('error_unknown_response');
            $this->response->redirect($this->url->link('checkout/checkout', '', true));
        }
    }
    
    public function callback() {
        $data = file_get_contents('php://input');
        
        if (!empty($data)) {
            $this->load->model('checkout/order');
            $this->load->model('extension/payment/atome');
            
            $this->model_extension_payment_atome->log('DEBUG_LOGS', 'Callback Data ' . $data);
            
            $data = json_decode($data, true);
        
            if (isset($data['referenceId'])) {
                $order_id = explode('OC', $data['referenceId']);
                $order_id = $order_id[1];
            } else {
                $order_id = 0;
            }
        
            $order_info = $this->model_checkout_order->getOrder($order_id);

            if ($order_info) {
                $curl = curl_init($this->model_extension_payment_atome->getApiUrl('v2/payments/'. $data['referenceId']));

                curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                curl_setopt($curl, CURLOPT_HEADER, false);
                curl_setopt($curl, CURLOPT_USERPWD, $this->config->get('payment_atome_api_key') . ':' . $this->config->get('payment_atome_password'));
                curl_setopt($curl, CURLOPT_POST, false);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_TIMEOUT, 30);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                $response = curl_exec($curl);
                $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                curl_close($curl);

                $this->model_extension_payment_atome->log('DEBUG_LOGS', 'CURL Response ' . $response);

                if ($http_code == '200') {
                    $response = json_decode($response, true);
                    
                    $order_info['atome_reference'] = $data['referenceId'];

                    $atome_order_id = $this->model_extension_payment_atome->addOrder($order_info);
        
                    if (isset($response['status'])) {
                        $order_status_id = $this->config->get('config_order_status_id');

                        if (isset($response['amount'])) {
                            $amount = $this->model_extension_payment_atome->getOriginAmount( $response['amount'] );
                        } else {
                            $amount = 0;
                        }

                        switch($response['status']) {
                            case 'PROCESSING':
                                $status = 'processing';

                                $order_status_id = $this->config->get('payment_atome_processing_status_id');
                                break;
                            case 'PAID':
                                $status = 'paid';
                                
                                $order_status_id = $this->config->get('payment_atome_paid_status_id');
                                break;
                            case 'FAILED':
                                $status = 'failed';
                                
                                $order_status_id = $this->config->get('payment_atome_failed_status_id');
                                break;
                            case 'REFUNDED':
                                $status = 'refunded';
                                
                                $order_status_id = $this->config->get('payment_atome_refunded_status_id');
                                break;
                            case 'CANCELLED':
                                $status = 'cancelled';
                                
                                $order_status_id = $this->config->get('payment_atome_cancelled_status_id');
                                break;
                        }

                        $this->model_extension_payment_atome->addTransaction($atome_order_id, $status, $amount);
                        
                        // Do not change status for cancelled orders that with order status ID 0
                        if ((!$order_info['order_status_id'] && $status != 'cancelled' && $status != 'failed') || $order_info['order_status_id']) {
                            $this->model_checkout_order->addOrderHistory($order_id, $order_status_id);
                        }
                    } else {
                        $this->model_extension_payment_atome->log('DEBUG_LOGS', 'Missing status from response in Checkout Return! Order ID: ' . $order_id);
                    }
                } elseif ($http_code == '404') {
                    $error = json_decode($response, true);
        
                    if ($this->config->get('payment_atome_debug')) {
                        if (isset($error['code']) && isset($error['message'])) {
                            $this->model_extension_payment_atome->log($error['code'], $error['message'] . ' Order ID: ' . $order_id);
                        } else {
                            $this->model_extension_payment_atome->log('DEBUG_LOGS', 'ATOME APAYLATER :: CURL Request returned HTTP 404 Code!');
                        }
                    }
                } else {
                    if ($this->config->get('payment_atome_debug')) {
                        $this->model_extension_payment_atome->log('DEBUG_LOGS', 'Invalid HTTP Response Code received at Checkout Return! Order ID: ' . $order_id);
                    }
                }
            }
        }
    }

    public function atomePriceUpdate() {
        $json = array();
      
        if ($this->customer->isLogged()) {
            $customer_group_id = $this->customer->getGroupId();
        } else {
            $customer_group_id = $this->config->get('config_customer_group_id');
        }
        
        if (isset($this->request->post['quantity'])) {
            $quantity = (int)$this->request->post['quantity'];
        } else {
            $quantity = 1;
        }

        if (isset($this->request->post['product_id'])) {
            $product_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product p WHERE p.product_id = '" . (int)$this->request->post['product_id'] . "'");

            if ($product_query->num_rows) {
                $json['price'] = $product_query->row['price'];
                $json['tax'] = $product_query->row['price'];
                $json['special'] = $product_query->row['price'];
                $json['points'] = $product_query->row['points'];
            
                $product_discount_query = $this->db->query("SELECT price FROM " . DB_PREFIX . "product_discount WHERE product_id = '" . (int)$this->request->post['product_id'] . "' AND customer_group_id = '" . (int)$customer_group_id . "' AND quantity <= '" . (int)$quantity . "' AND ((date_start = '0000-00-00' OR date_start < NOW()) AND (date_end = '0000-00-00' OR date_end > NOW())) ORDER BY quantity DESC, priority ASC, price ASC LIMIT 1");

                if ($product_discount_query->num_rows) {
                    $json['price'] = $product_discount_query->row['price'];
                    $json['tax'] = $product_discount_query->row['price'];
                    $json['special'] = $product_discount_query->row['price'];
                }
            
                $product_special_query = $this->db->query("SELECT price FROM " . DB_PREFIX . "product_special WHERE product_id = '" . (int)$this->request->post['product_id'] . "' AND customer_group_id = '" . (int)$customer_group_id . "' AND ((date_start = '0000-00-00' OR date_start < NOW()) AND (date_end = '0000-00-00' OR date_end > NOW())) ORDER BY priority ASC, price ASC LIMIT 1");

                if ($product_special_query->num_rows) {
                    $json['special'] = $product_special_query->row['price'];
                    $json['tax'] = $product_special_query->row['price'];
                }
            
                if (isset($this->request->post['option'])) {
                    $option = array_filter($this->request->post['option']);
                } else {
                    $option = array();  
                }
            
                $this->load->model('catalog/product');
                
                $product_options = $this->model_catalog_product->getProductOptions($this->request->post['product_id']);

                foreach ($product_options as $product_option) {
                    if (!empty($option[$product_option['product_option_id']])) {
                        if ($product_option['type'] == 'select' || $product_option['type'] == 'radio' || $product_option['type'] == 'image') {
                            $product_option_value_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_option_value pov WHERE product_option_id = '" . (int)$product_option['product_option_id'] . "' AND product_id = '" . (int)$this->request->post['product_id'] . "'");
                            
                            foreach ($product_option_value_query->rows as $option_value) {
                                if ($option_value['product_option_value_id'] == $option[$product_option['product_option_id']]) {
                                    if ($option_value['price_prefix'] == '=') {
                                        $json['price'] = $option_value['price'];
                                        $json['tax'] = $option_value['price'];
                                        $json['special'] = $option_value['price'];
                                    } elseif ($option_value['price_prefix'] == '+') {
                                        $json['price'] += $option_value['price'];
                                        $json['tax'] += $option_value['price'];
                                        $json['special'] += $option_value['price'];
                                    } else {
                                        $json['price'] -= $option_value['price'];
                                        $json['tax'] -= $option_value['price'];
                                        $json['special'] -= $option_value['price'];
                                    }
                                  
                                    if ($option_value['points_prefix'] == '=') {
                                        $json['points'] = $option_value['points'];
                                    } elseif ($option_value['points_prefix'] == '+') {
                                        $json['points'] += $option_value['points'];
                                    } else {
                                        $json['points'] -= $option_value['points'];
                                    }
                                }
                            }
                        } elseif ($product_option['type'] == 'checkbox') {
                            $product_option_value_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_option_value pov WHERE product_option_id = '" . (int)$product_option['product_option_id'] . "' AND product_id = '" . (int)$this->request->post['product_id'] . "'");
                          
                            foreach ($product_option_value_query->rows as $option_value) {
                                if (in_array($option_value['product_option_value_id'], $option[$product_option['product_option_id']])) {
                                    if ($option_value['price_prefix'] == '=') {
                                        $json['price'] = $option_value['price'];
                                        $json['tax'] = $option_value['price'];
                                        $json['special'] = $option_value['price'];
                                    } elseif ($option_value['price_prefix'] == '+') {
                                        $json['price'] += $option_value['price'];
                                        $json['tax'] += $option_value['price'];
                                        $json['special'] += $option_value['price'];
                                    } else {
                                        $json['price'] -= $option_value['price'];
                                        $json['tax'] -= $option_value['price'];
                                        $json['special'] -= $option_value['price'];
                                    }
                                  
                                    if ($option_value['points_prefix'] == '=') {
                                        $json['points'] = $option_value['points'];
                                    } elseif ($option_value['points_prefix'] == '+') {
                                        $json['points'] += $option_value['points'];
                                    } else {
                                        $json['points'] -= $option_value['points'];
                                    }
                                }
                            }
                        }
                    }
                }
            
                $json['points'] = $json['points'] * $quantity;
              
                $json['tax'] = $this->currency->format($json['tax'] * $quantity, $this->session->data['currency']);
                $json['price'] = $this->currency->format(($this->tax->calculate($json['price'], $product_query->row['tax_class_id'], $this->config->get('config_tax')) * $quantity) / 3, $this->session->data['currency']);
                $json['special'] = $this->currency->format(($this->tax->calculate($json['special'], $product_query->row['tax_class_id'], $this->config->get('config_tax')) * $quantity) / 3, $this->session->data['currency']);
            }
        }
        
        $this->response->setOutput(json_encode($json));
    }
    
    public function products() {
        $json = array();

        $this->load->model('extension/payment/atome');
        $this->model_extension_payment_atome->check_auth();
        
        $this->load->model('catalog/product');
        $results = $this->model_extension_payment_atome->getProducts();

        foreach ($results as $product) {
            $product_info = $this->model_catalog_product->getProduct($product['product_id']);
            $product_info['description'] = html_entity_decode($product_info['description']);

            $product_info['options'] = $this->model_catalog_product->getProductOptions($product['product_id']);
            
            $json[] = $product_info;
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}