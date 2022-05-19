<?php
class ModelExtensionPaymentAtome extends Model {

    protected $atome_config = array(
        'sg' => array(
            'currency_code' => 'SGD',
            'minimum_spend' => 10,
            'int_factor'    => 100,
            'checkout_logo' => 'logo-checkout.png',
        ),
        'hk' => array(
            'currency_code' => 'HKD',
            'minimum_spend' => 100,
            'int_factor'    => 100,
            'checkout_logo' => 'logo-checkout.png',
        ),
        'my' => array(
            'currency_code' => 'MYR',
            'minimum_spend' => 50,
            'int_factor'    => 100,
            'checkout_logo' => 'logo-checkout.png',
        ),
        'id' => array(
            'currency_code' => 'IDR',
            'minimum_spend' => 200000,
            'int_factor'    => 1,
            'checkout_logo' => 'logo-checkout.png',
        ),

    );

    protected $locale_config = array();

    public function getApiBaseUrl()
    {
        $apiBaseUrls = array(
            'staging'    => 'https://api.apaylater.net/',
            'production' => 'https://api.apaylater.com/',
        );

        $env     = $this->config->get('payment_atome_test') ? 'staging' : 'production';
        $country = $this->config->get('payment_atome_country') ?: 'sg';

        if(isset($this->atome_config[$country]['api_base_urls']) && is_array($this->atome_config[$country]['api_base_urls'])){
            $apiBaseUrls = $this->atome_config[$country]['api_base_urls'];
        }

        return isset($apiBaseUrls[$env]) ? $apiBaseUrls[$env] : 'https://api.apaylater.com/';
    }

    public function getApiUrl($path)
    {
        return $this->getApiBaseUrl() . $path;
    }

    public function checkSupportedCurrency($currency = null){
        $currency = $currency ?: strtoupper($this->session->data['currency']);

        if(empty($currency)){
            $this->log('[checkSupportedCurrency]', 'undefined currency, currency => '. $currency);
            return false;
        }

        if( !in_array($currency, array_column($this->atome_config, 'currency_code')) ){
            $this->log('[checkSupportedCurrency]', 'currency is not supported, currency => '. $currency);
            return false;
        }

        return true;
    }

    public function checkMinimumSpend($total_amount){
        $country = $this->config->get('payment_atome_country') ?: 'sg';

        if(!isset($this->atome_config[$country])){
            $this->log('[checkMinimumSpend]', 'undefined country => '. $country);
            return false;
        }

        $minimum_spend = $this->getLocaleConfig('minimum_spend', 0);

        $send_minimum_sepnd = $this->getSendAmount($minimum_spend);
        $send_total_amount  = $this->getSendAmount($total_amount);

        if($send_minimum_sepnd > $send_total_amount){
            $this->log('[checkMinimumSpend]', 'total amount is lower than minimum spend, total => '. $total_amount .', minimum spend => '.$minimum_spend);
            return false;
        }

        $this->log('[checkMinimumSpend('. $minimum_spend .')]', 'passed');

        return true;
    }

    public function getSendAmount($amount){
        $country = $this->config->get('payment_atome_country') ?: 'sg';
        $intFactor = $this->getLocaleConfig('int_factor', 100);
        $this->log('[getSendAmount]', 'int_factor => ' .$intFactor);

        $sendAmount = $amount * $intFactor;
        $sendAmount = ('id' == $country) ? ceil($sendAmount) : round($sendAmount);

        return intval($sendAmount);
    }

    public function getOriginAmount($sendAmount){
        $intFactor = $this->getLocaleConfig('int_factor', 100);
        $this->log('[getOriginAmount]', 'int_factor => ' .$intFactor);

        $amount = $sendAmount / $intFactor;

        return intval($amount);
    }

    private function getLocaleConfigFromDb()
    {
        $localeConfigStr = $this->config->get('payment_atome_locale_config');
        if ( empty($localeConfigStr) ) {
            $this->log('get payment_atome_locale_config empty: '. $localeConfigStr);
            return array();
        }

        $localeConfigArr = json_decode($localeConfigStr, true);
        if ( empty($localeConfigStr) ) {
            $this->log('get payment_atome_locale_config str: '. $localeConfigStr);
            $this->log('get payment_atome_locale_config arr: '. json_encode($localeConfigArr));
            return array();
        }

        return $localeConfigArr;
    }

    private function editAtomeSettingValue($key = '', $value = '', $store_id = 0)
    {
        $query = $this->db->query("SELECT value FROM " . DB_PREFIX . "setting WHERE store_id = '" . (int)$store_id . "' AND `key` = '" . $this->db->escape($key) . "'");

        if ($query->num_rows) {
            if (!is_array($value)) {
            $this->db->query("UPDATE " . DB_PREFIX . "setting SET `value` = '" . $this->db->escape($value) . "', serialized = '0'  WHERE `code` = 'payment_atome' AND `key` = '" . $this->db->escape($key) . "' AND store_id = '" . (int)$store_id . "'");
            } else {
            $this->db->query("UPDATE " . DB_PREFIX . "setting SET `value` = '" . $this->db->escape(json_encode($value)) . "', serialized = '1' WHERE `code` = 'payment_atome' AND `key` = '" . $this->db->escape($key) . "' AND store_id = '" . (int)$store_id . "'");
            }
        } else {
            if (!is_array($value)) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "setting SET store_id = '" . (int)$store_id . "', `code` = 'payment_atome', `key` = '" . $this->db->escape($key) . "', `value` = '" . $this->db->escape($value) . "'");
            } else {
            $this->db->query("INSERT INTO " . DB_PREFIX . "setting SET store_id = '" . (int)$store_id . "', `code` = 'payment_atome', `key` = '" . $this->db->escape($key) . "', `value` = '" . $this->db->escape(json_encode($value, true)) . "', serialized = '1'");
            }
        }
    }

    private function setLocaleConfig($value)
    {
        $this->log('setting locale_config: '. $value);
        $this->editAtomeSettingValue('payment_atome_locale_config', $value);
        $this->editAtomeSettingValue('payment_atome_last_updated_time', time());
    }

    private function initLocaleConfig()
    {
        $configFromDb = $this->getLocaleConfigFromDb();
        $lastUpdatedTime = $this->config->get('payment_atome_last_updated_time');
        $country = $this->config->get('payment_atome_country') ?: 'sg';

        if (empty($lastUpdatedTime) || (time() - 86400) > $lastUpdatedTime || empty($configFromDb) || !isset($configFromDb['country']) || strtolower($configFromDb['country']) != $country) {
            $configFromAtome = $this->getConfigFromAtome();
            if (!empty($configFromAtome) && is_array($configFromAtome)) {

                $intFactor = $this->atome_config[$country] && $this->atome_config[$country]['int_factor'] ? $this->atome_config[$country]['int_factor'] : 100;

                foreach ($configFromAtome as $key => $value) {
                    if( 'minSpend' == $key ) {
                        $configFromAtome['minimum_spend'] = intval($value / $intFactor);
                        unset($configFromAtome[$key]);
                    }
                }

                $configFromDb = array_merge($configFromDb, $configFromAtome);
                $this->setLocaleConfig(json_encode($configFromDb));
            }
        }

        $this->locale_config = array_merge($this->atome_config[$country] ?: [], $configFromDb);
    }

    public function getLocaleConfig($key, $default = '') {
        if(empty($this->locale_config)){
            $this->initLocaleConfig();
        }

        return isset($this->locale_config[$key]) ? $this->locale_config[$key] : $default;
    }

    public function getConfigFromAtome()
    {
        try {
            $country = $this->config->get('payment_atome_country') ?: 'sg';
            $configUrl = $this->getApiUrl('v1/variables/' . strtoupper($country));

            $curl = curl_init($configUrl);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_TIMEOUT, 10);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            $this->log('apiurl : '. $this->getApiUrl('v1/variables/' . strtoupper($country)));
            $response = curl_exec($curl);
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            
            $this->log('getting atome config response http_code => '. $http_code);
            $this->log('getting atome config response => '. $response);
            
            if (!$response) {
              $this->log('DEBUG_LOGS', 'CURL failed in getting atome config!');
              return [];
            }

            $response = json_decode($response, true);
            
            if ($http_code != '200') {
                $this->log($response['code'] . ': ' . $response['message']);
                return [];
            }

            return is_array($response) && !empty($response) ? $response : [];

        } catch (Exception $e) {
            $this->log('getting atome config exception: code => '. $e->getCode() . ', msg => '. $e->getMessage());
            return [];
        }
    }

    public function getMethod($address, $total) {
        $this->load->language('extension/payment/atome');

        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone_to_geo_zone` WHERE `geo_zone_id` = '" . (int)$this->config->get('payment_atome_geo_zone_id') . "' AND `country_id` = '" . (int)$address['country_id'] . "' AND (`zone_id` = '" . (int)$address['zone_id'] . "' OR `zone_id` = '0')");

        if(!$this->checkMinimumSpend($total)){
            $status = false;
        } elseif (!$this->config->get('payment_atome_geo_zone_id')) {
            $status = true;
        } elseif ($query->num_rows) {
            $status = true;
        } else {
            $status = false;
        }

        if( !$this->checkSupportedCurrency(strtoupper($this->session->data['currency'])) ){
            $status = false;
        }

        $checkoutLogo = $this->getLocaleConfig('checkout_logo', 'logo-checkout.png');

        $method_data = array();

        if ($status) {
            $method_data = array(
                'code'       => 'atome',
                'title'      => str_ireplace('{{checkout_logo}}', $checkoutLogo, $this->language->get('text_title') ),
                'terms'      => '',
                'sort_order' => $this->config->get('payment_atome_sort_order')
            );
        }

        return $method_data;
    }

    public function addOrder($order_info) {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "atome_order` SET `order_id` = '" . (int)$order_info['order_id'] . "', atome_reference = '" . $this->db->escape($order_info['atome_reference']) . "', `currency_code` = '" . $this->db->escape($order_info['currency_code']) . "', `total` = '" . $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false) . "', `date_added` = now(), `date_modified` = now()");
    
        return $this->db->getLastId();
    }

    public function addTransaction($atome_order_id, $type, $amount) {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "atome_order_transaction` SET `atome_order_id` = '" . (int)$atome_order_id . "', `date_added` = now(), `type` = '" . $this->db->escape($type) . "', `amount` = '" . (float)$amount . "'");
    }

    public function log($code = '', $message = '') {
        if ($this->config->get('payment_atome_debug')) {
            $this->log->write('ATOME APAYLATER :: debug :: (' . $code . ': ' . $message . ')');
        }
    }

    public function getProducts() {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product ORDER BY product_id ASC");
        
        return $query->rows;
    }

    public function check_auth()
    {
        $authorization = getallheaders()['Authorization'];
        if(empty($authorization)){
            $authorization = $this->request->get['Authorization'];
        }
    
        $this->log('check_auth_str', $authorization);
        try {
            $authorization = base64_decode(substr($authorization, 5));
            $arr = explode(':', $authorization);
            $is_auth = $arr[0] === $this->config->get('payment_atome_api_key') && $arr[1] === $this->config->get('payment_atome_password');
            if (!$is_auth) {
                header("HTTP/1.1 401 Unauthorized");
                die(json_encode(array('code' => '401', 'message' => 'You are unauthorized to access')));
            }
            $this->log('check_auth_result', 'success');
        } catch (Exception $e) {
            $this->log('check_auth_exception', 'Exception: '. $e->getCode() . ' | '. $e->getMessage());
            header("HTTP/1.1 401 Unauthorized");
            die(json_encode(array('code' => '401', 'message' => $e->getMessage() )));
        }
    }
}