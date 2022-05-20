<?php
class ModelExtensionShippingEasyparcel extends Model {
	function getQuote($address) {
		$this->load->language('extension/shipping/easyparcel');
		$method_data = array();

		$rate_type = $this->config->get('shipping_easyparcel_show_rate_type');

		if ($rate_type != 0 && (int)$address['country_id'] == 129) {
			$weight = $this->weight->convert($this->cart->getWeight(), $this->config->get('config_weight_class_id'), $this->config->get('shipping_easyparcel_weight_class_id'));
			$weight = ($weight < 0.1 ? 0.1 : $weight);
			$weight_code = strtoupper($this->weight->getUnit($this->config->get('shipping_easyparcel_weight_class_id')));
			
			//public rate or account rate - START
			if($rate_type == 1){
				$integration_id = $this->config->get('shipping_easyparcel_integration');
				$email = $this->config->get('shipping_easyparcel_email');
			}else{
				$integration_id = "";
				$email = "";
			}
			//public rate or account rate - END
			if ($weight_code == 'KG') {
				$weight_code = 'KGS';
			} elseif ($weight_code == 'LB') {
				$weight_code = 'LBS';
			}
			//increase or decrease rate - START
			$rate_adjustment_type = $this->config->get('shipping_easyparcel_rate_adjustment_type');
			$rate_adjustment_amount = 0;
			if($rate_adjustment_type != 0){
				$rate_adjustment_amount = $this->config->get('shipping_easyparcel_rate_adjustment_amount');
			}
			//increase or decrease rate - END
			$date = time();
			$products = $this->cart->getProducts();
			$i = 0;
			$length = "";
            $width = "";
            $height = "";
		   foreach ($products as $item) {
               
                        $length += $products[$i]['length'];
                        $width += $products[$i]['width'];
                        $height += $products[$i]['height'];
                        $i++;
                    
                }
			$this->load->model('localisation/country');
			$country_info = $this->model_localisation_country->getCountry($this->config->get('config_country_id'));
			$this->load->model('localisation/zone');
			$zone_info = $this->model_localisation_zone->getZone($this->config->get('config_zone_id'));
			$pv = ''; 
			$f = array(
				'integration_id'=>$integration_id, # required
				'user_email'=>$email,# required
				'pick_code'=> $this->config->get('shipping_easyparcel_postcode'), # required
				'pick_state'=> ($zone_info ? $zone_info['name'] : ''), # required
				'pick_country'=> $country_info['name'], # required
				'send_code'=> $address['postcode'], # required
				'send_state'=>$address['zone'], # required
				'send_country'=>$address['country'], # required
				'weight'=>$this->weight->convert($weight, $this->config->get('shipping_easyparcel_weight_class_id'), 1), // always convert to KG when passing to easyparcel
				'length'=>$length,
				'height'=>$height,
				'width'=>$width
			);
			foreach($f as $k => $v){ $pv .= $k . "=" . $v . "&"; }
			$url = "https://easyparcel.com/my/en/?ac=GetEPRate";
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
			
			$quote_data = array();
			if(count($json->rates) > 0){
				foreach($json->rates as $option=>$value){
					$title = $value->Service_Name." ".$value->Service_ID;
					$cost = $value->Price;
					if ((float)$cost) {
						if ($this->config->get('shipping_easyparcel_display_weight')) {
							$title .= ' (' . $this->language->get('text_weight') . ' ' . $this->weight->format($weight, $this->config->get('shipping_easyparcel_weight_class_id')) . ')';
						}
					}
					#do - / + rate - Start
					if($rate_adjustment_type == 1){ #increase
						$cost = ($cost + $rate_adjustment_amount);
					}
					if($rate_adjustment_type == 2){ #decrease
						if($cost < $rate_adjustment_amount){
							$cost = 0;
						}else{
							$cost = ($cost - $rate_adjustment_amount);
						}
					}
					#do - / + rate - END
					if($value->Service_Type == 'parcel'){
						$quote_data[$value->Service_ID] = array(
							'code'         => 'easyparcel.'.$value->Service_ID,
							'title'        => $title,
							'cost'         => $cost,
							'tax_class_id' => $this->config->get('shipping_easyparcel_tax_class_id'),
							'text'         => $this->currency->format($this->tax->calculate($cost, $this->config->get('shipping_easyparcel_tax_class_id'), $this->config->get('config_tax')), $this->session->data['currency'])
						);	
					}
					
				}
				
				$method_data = array(
					'code'       => 'easyparcel',
					'title'      => $this->language->get('text_title'),
					'quote'      => $quote_data,
					'sort_order' => $this->config->get('shipping_easyparcel_sort_order'),
					'error'      => false
				);
			}
		}
		return $method_data;
	}	
}