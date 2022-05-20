<?php

// DIR_APPLICATION to ensure vqmod cache find the correct path
require_once DIR_APPLICATION .'/controller/extension/payment/billplz-api.php';
require_once DIR_APPLICATION .'/controller/extension/payment/billplz-connect.php';

class ControllerExtensionPaymentBillplz extends Controller
{
    public function index()
    {
        $this->load->language('extension/payment/billplz');

        $data = array(
            'button_confirm' => $this->language->get('button_confirm'),
            'is_sandbox' => $this->config->get('payment_billplz_is_sandbox_value'),
            'text_is_sandbox' => $this->language->get('text_is_sandbox'),
            'action' => $this->url->link('extension/payment/billplz/proceed', '', true)
        );

        return $this->load->view('extension/payment/billplz', $data);
    }

    public function proceed()
    {
        $is_sandbox = $this->config->get('payment_billplz_is_sandbox_value');
        $api_key = $this->config->get('payment_billplz_api_key_value');
        $collection_id = $this->config->get('payment_billplz_collection_id_value');

        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        $amount = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);

        $products = $this->cart->getProducts();
        foreach ($products as $product) {
            $data['prod_desc'][] = $product['name'] . " x " . $product['quantity'];
        }

        $parameter = array(
            'collection_id' => trim($collection_id),
            'email' => trim($order_info['email']),
            'mobile'=> trim($order_info['telephone']),
            'name' => trim($order_info['firstname'] . ' ' . $order_info['lastname']),
            'amount' => strval($amount * 100),
            'callback_url' => $this->url->link('extension/payment/billplz/callback_url', '', true),
            'description' => mb_substr("Order " . $this->session->data['order_id'] . " - " . implode($data['prod_desc']), 0, 200)
        );

        if (empty($parameter['mobile']) && empty($parameter['email'])) {
            $parameter['email'] = 'noreply@billplz.com';
        }

        if (empty($parameter['name'])) {
            $parameter['name'] =  'Payer Name Unavailable';
        }

        $optional = array(
            'redirect_url' => $this->url->link('extension/payment/billplz/redirect_url', '', true),
            'reference_1_label' => 'ID',
            'reference_1' => $order_info['order_id']
        );

        $connect = new BillplzConnect($api_key);
        $connect->setStaging($is_sandbox);
        $billplz = new BillplzApi($connect);

        list($rheader, $rbody) = $billplz->toArray($billplz->createBill($parameter, $optional));

        $this->load->model('extension/payment/billplz');

        /*
        Display error if bills failed to create
        */

        if ($rheader !== 200) {
            $this->model_extension_payment_billplz->logger($rbody);
            exit(print_r($rbody, true));
        }

        $bill_id = $rbody['id'];

        if (!$this->model_extension_payment_billplz->insertBill($order_info['order_id'], $bill_id)){
            $this->model_extension_payment_billplz->logger('Unexpected error. Duplicate bill id.');
            exit('Unexpected error. Duplicate bill id.');
        }

        $this->model_checkout_order->addOrderHistory($order_info['order_id'], $this->config->get('payment_billplz_pending_status_id'), "Status: Pending. Bill ID: $bill_id" , false, true);

        $this->cart->clear();
        
        header('Location: ' . $rbody['url']);
    }

    public function redirect_url()
    {
        $this->load->model('extension/payment/billplz');

        try {
           $data = BillplzConnect::getXSignature($this->config->get('payment_billplz_x_signature_value'));
        } catch (Exception $e){
            $this->model_extension_payment_billplz->logger('XSignature redirect error. ' . $e->getMessage());
            exit($e->getMessage());
        }

        if (!$data['paid']){
            $this->redirect($this->url->link('checkout/checkout'));
        }

        $bill_id = $data['id'];
        $bill_info = $this->model_extension_payment_billplz->getBill($bill_id);
        $order_id = $bill_info['order_id'];

        $this->load->model('checkout/order');
        
        $billplz_completed_status_id = $this->config->get('payment_billplz_completed_status_id');
        $billplz_pending_status_id = $this->config->get('payment_billplz_pending_status_id');

        $order_info = $this->model_checkout_order->getOrder($order_id);

        if ($order_info['order_status_id'] == $billplz_pending_status_id && !$bill_info['paid']) {
            if ($this->model_extension_payment_billplz->markBillPaid($order_id, $bill_id)){
                $this->model_checkout_order->addOrderHistory($order_id, $billplz_completed_status_id, "Status: Paid. Bill ID: $bill_id. Method: Redirect " , true, true);
            }
        }

        $this->redirect($this->url->link('checkout/success'));        
    }

    public function callback_url()
    {
        if ($_POST['paid'] === 'false'){
            exit;
        }

        $this->load->model('extension/payment/billplz');

        try {
           $data = BillplzConnect::getXSignature($this->config->get('payment_billplz_x_signature_value'));
        } catch (Exception $e){
            $this->model_extension_payment_billplz->logger('XSignature callback error. ' . $e->getMessage());
            exit;
        }

        $bill_id = $data['id'];
        $bill_info = $this->model_extension_payment_billplz->getBill($bill_id);
        $order_id = $bill_info['order_id'];

        $this->load->model('checkout/order');
        
        $billplz_completed_status_id = $this->config->get('payment_billplz_completed_status_id');
        $billplz_pending_status_id = $this->config->get('payment_billplz_pending_status_id');

        $order_info = $this->model_checkout_order->getOrder($order_id);

        if ($order_info['order_status_id'] == $billplz_pending_status_id && !$bill_info['paid']) {
            if ($this->model_extension_payment_billplz->markBillPaid($order_id, $bill_id)){
                $this->model_checkout_order->addOrderHistory($order_id, $billplz_completed_status_id, "Status: Paid. Bill ID: $bill_id. Method: Callback " , true, true);
            }
        }

        exit('Callback Success');
    }

    public function redirect($location){
        if (!headers_sent()) {
            header('Location: ' . $location);
        } else {
            echo "If you are not redirected, please click <a href=" . '"' . $location . '"' . " target='_self'>Here</a><br />"
            . "<script>location.href = '" . $location . "'</script>";
        }

        exit();
    }
}
