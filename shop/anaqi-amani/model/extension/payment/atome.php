<?php
class ModelExtensionPaymentAtome extends Model {
    protected $atome_config = array(
        'sg' => array(
            'int_factor'    => 100,
        ),
        'hk' => array(
            'int_factor'    => 100,
        ),
        'my' => array(
            'int_factor'    => 100,
        ),
        'id' => array(
            'int_factor'    => 1,
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

    public function getSendAmount($amount){
        $country   = $this->config->get('payment_atome_country') ?: 'sg';
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

    private function setLocaleConfig($value)
    {
        $this->log('setting locale_config: '. $value);
        $this->load->model('setting/setting');
        $this->model_setting_setting->editSettingValue('payment_atome', 'payment_atome_locale_config', $value);
        $this->model_setting_setting->editSettingValue('payment_atome', 'payment_atome_last_updated_time', time());
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

    public function install() {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "atome_order` (
              `atome_order_id` int(11) NOT NULL AUTO_INCREMENT,
              `order_id` int(11) NOT NULL,
              `atome_reference` varchar(40) NOT NULL,
              `currency_code` CHAR(3) NOT NULL,
              `total` DECIMAL( 10, 2 ) NOT NULL,
              `date_added` DATETIME NOT NULL,
              `date_modified` DATETIME NOT NULL,
              `cancelled_status` INT(1) DEFAULT NULL,
              PRIMARY KEY (`atome_order_id`)
            ) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;");
        
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "atome_order_transaction` (
              `atome_order_transaction_id` INT(11) NOT NULL AUTO_INCREMENT,
              `atome_order_id` INT(11) NOT NULL,
              `date_added` DATETIME NOT NULL,
              `type` ENUM('processing', 'paid', 'failed', 'refunded', 'cancelled') DEFAULT NULL,
              `amount` DECIMAL( 10, 2 ) NOT NULL,
              PRIMARY KEY (`atome_order_transaction_id`)
            ) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;");
    }

    public function uninstall() {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "atome_order`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "atome_order_transaction`");
    }

    public function getAtomeOrder($order_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "atome_order` WHERE `order_id` = '" . (int)$order_id . "'");

        return $query->row;
    }

    public function setAtomeOrderCancelStatus($order_id) {
        $this->db->query("UPDATE `" . DB_PREFIX . "atome_order` SET `cancelled_status` = '1', `date_modified` = NOW() WHERE `order_id` = '" . (int)$order_id . "'");
    }

    public function getOrder($order_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "atome_order` WHERE `order_id` = '" . (int)$order_id . "' LIMIT 1");

        if ($query->num_rows) {
            $order = $query->row;
            $order['transactions'] = $this->getTransactions($order['atome_order_id']);
            return $order;
        } else {
            return false;
        }
    }

    private function getTransactions($atome_order_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "atome_order_transaction` WHERE `atome_order_id` = '" . (int)$atome_order_id . "'");
    
        if ($query->num_rows) {
            return $query->rows;
        }   else {
            return false;
        }
    }
  
    public function addTransaction($atome_order_id, $type, $total) {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "atome_order_transaction` SET `atome_order_id` = '" . (int)$atome_order_id . "', `date_added` = now(), `type` = '" . $this->db->escape($type) . "', `amount` = '" . (float)$total . "'");
    }

    public function log($code = '', $message = '') {
        if ($this->config->get('payment_atome_debug')) {
            $this->log->write('ATOME APAYLATER :: debug :: (' . $code . ': ' . $message . ')');
        }
  }
}