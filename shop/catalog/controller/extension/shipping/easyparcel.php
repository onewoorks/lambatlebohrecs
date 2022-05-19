<?php
class ControllerExtensionShippingEasyparcel extends Controller {
	public function index() {
		$integration_id = $this->config->get('shipping_easyparcel_integration');
		
		$req_integration_id = $this->request->get['integration_id'];
		$req_order_id = $this->request->post['order_id'];
		
		if (empty($req_integration_id) || $integration_id != $req_integration_id) {
			die();
		}

		$orders_table = DB_PREFIX . "order";
		$orderproducts_table = DB_PREFIX . "order_product";
		$products_table = DB_PREFIX . "product";

		$message = "";
		$xml = array();
		$xml[] = "<?xml version=\"1.0\" encoding=\"utf-8\"?>";
		$xml[] = "<shop>";
		$xml[] = "<id>".$integration_id."</id>";

		$seven_days_ago = time()-(60*60*24*7);
		$orders_sql_extra = isset($req_order_id) && !empty($req_order_id) ?
			"AND order_id=".$req_order_id." " :
			"AND (order_status_id=\"2\" OR order_status_id=\"15\") AND date_added > ".$seven_days_ago." ";

		$orders_sql = "SELECT * FROM ". $orders_table ." WHERE 1 ". $orders_sql_extra ."AND shipping_country = \"Malaysia\"; ";
		$orders_rs = $this->db->query($orders_sql);

		if ($orders_rs) {
			foreach ($orders_rs->rows as $orders_row) {
				$orderno = $orders_row["order_id"];
				$order_status = $orders_row["order_status_id"];
				$date_added = $orders_row["date_added"];
				$deli_name = $orders_row["shipping_firstname"];
				$deli_name .= " ".$orders_row["shipping_lastname"];
				$deli_company = $orders_row["shipping_company"];
				$deli_email = $orders_row["email"];
				$deli_addr1 = $orders_row["shipping_address_1"];
				$deli_addr2 = $orders_row["shipping_address_2"];
				$deli_addr3 = "";
				$deli_town = $orders_row["shipping_city"];
				$deli_postcode = $orders_row["shipping_postcode"];
				$deli_state = $orders_row["shipping_zone"];
				$deli_country = $orders_row["shipping_country"];
				$deli_contact = $orders_row["telephone"];
				$shipping_code = $orders_row["shipping_code"];
				$comments = $orders_row["comment"];
				//end declare variable using easyparcel variable
				$xml[] = "<order>";
				$xml[] = "<itemid>".$orderno."</itemid>";
				$xml[] = "<status>".$order_status."</status>";
				$xml[] = "<date_added>".$date_added."</date_added>";
                
				$orderproducts_sql = "SELECT * FROM ".$orderproducts_table." WHERE order_id = \"".$orderno."\"; ";
				$orderproducts_rs = $this->db->query($orderproducts_sql);
				foreach ($orderproducts_rs->rows as $orderproducts_row) {
					$parcel_content = $orderproducts_row["name"];
					$products_id = $orderproducts_row["product_id"];
					$product_quantity = $orderproducts_row["quantity"];
					$weight = "";

					$products_sql = "SELECT price, weight_class_id, weight, length_class_id, width, height, length FROM ".$products_table." WHERE product_id=\"".$products_id."\"; ";
					$products_rs = $this->db->query($products_sql);
					if ($products_rs) {
						if ($product_row = $products_rs->rows[0]) {
							$weight = $this->weight->convert($product_row["weight"], $product_row["weight_class_id"], 1); // always convert to KG when passing to easyparcel
							$goods_value = $product_row["price"];
							$width = $product_row["width"];
							$height = $product_row["height"];
							$length = $product_row["length"];
						}
					} else {
						$weight = $width = $height = $length = 0;
					}

					$xml[] = "<item>";
					$xml[] = "<parcel_content>".htmlspecialchars($parcel_content)."</parcel_content>";
					$xml[] = "<goods_value>".htmlspecialchars($goods_value)."</goods_value>";
					$xml[] = "<weight>".htmlspecialchars($weight)."</weight>";
					$xml[] = "<height>".htmlspecialchars($height)."</height>";
					$xml[] = "<width>".htmlspecialchars($width)."</width>";
					$xml[] = "<length>".htmlspecialchars($length)."</length>";
					$xml[] = "<quantity>".htmlspecialchars($product_quantity)."</quantity>";
					$xml[] = "</item>";
				}

				$xml[] = "<deli_name>".htmlspecialchars($deli_name)."</deli_name>";
				$xml[] = "<deli_company>".htmlspecialchars($deli_company)."</deli_company>";
				$xml[] = "<deli_email>".htmlspecialchars($deli_email)."</deli_email>";
				$xml[] = "<deli_addr1>".htmlspecialchars($deli_addr1)."</deli_addr1>";
				$xml[] = "<deli_addr2>".htmlspecialchars($deli_addr2)."</deli_addr2>";
				$xml[] = "<deli_addr3>".htmlspecialchars($deli_addr3)."</deli_addr3>";
				$xml[] = "<deli_town>".htmlspecialchars($deli_town)."</deli_town>";
				$xml[] = "<deli_state>".htmlspecialchars($deli_state)."</deli_state>";
				$xml[] = "<deli_postcode>".htmlspecialchars($deli_postcode)."</deli_postcode>";
				$xml[] = "<deli_country>".htmlspecialchars($deli_country)."</deli_country>";
				$xml[] = "<deli_contact>".htmlspecialchars($deli_contact)."</deli_contact>";
				$xml[] = "<shipping_code>".htmlspecialchars($shipping_code)."</shipping_code>";
				$xml[] = "<comments>".htmlspecialchars($comments)."</comments>";
				$xml[] = "</order><p></p>";
			}
		} else {
			$message .=  "Table ".$orders_table. " Or It's Content Is Not Accessible.";
		}

		if($message != ""){
			$xml[] = "<message>".htmlspecialchars($message)."</message>";
		}
		$xml[] = "</shop>";
		$xml = @implode("\n", $xml);
		echo $xml;
	}
}