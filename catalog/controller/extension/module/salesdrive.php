<?php

/* OpenCart 2.3, 3.0 */

class ControllerExtensionModuleSalesdrive extends Controller
{
	public function eventAddOrderHistory($route, $event_data) {
		if($this->config->get('module_salesdrive_status') != 1){
			return;
		}
		if(!isset($event_data[0])){
			return;
		}

		$this->load->model('account/order');
		$this->load->model('checkout/order');
		$this->load->model('catalog/product');

		$order_id = $event_data[0];

		if (count($this->model_account_order->getOrderHistories($order_id)) > 1) {
			return;
		}

		$order = $this->model_checkout_order->getOrder($order_id);
		$order_products = $this->model_account_order->getOrderProducts($order_id);
		$order_totals = $this->model_account_order->getOrderTotals($order_id);
		$order_custom_field = $order['custom_field'];

		$data = array();
		$data['externalId'] = $order_id;
		$data['fName'] = htmlspecialchars_decode($order['firstname']);
		$data['lName'] = htmlspecialchars_decode($order['lastname']);
		$data['phone'] = $order['telephone'];
		$email = $order['email'];
		if(strpos($email,'no-reply@portovaya.zp.ua')!==false){
			$email = '';
		}
		$data['email'] = $email;
		$data['company'] = htmlspecialchars_decode($order['shipping_company']);
		$data['products'] = array();

		foreach ($order_products as $product) {
			$options = $this->model_account_order->getOrderOptions($order_id, $product['order_product_id']);

			// product data init
			$product_data = array(
				'id' => $product['product_id'],
				'name' => htmlspecialchars_decode($product['name']),
			);

			//generate virtual products by options
			$product_data = $this->getVirtualProductData($product_data, $options);

			$description = '';
			if($options){
				foreach($options as $option){
					if($option['product_option_value_id'] === '0'){
						$description .= htmlspecialchars_decode($option['name']).': '.htmlspecialchars_decode($option['value']).";\n";
					}
				}
			}
			$data['products'][] = array(
				'id' => $product_data['id'],
				'name' => htmlspecialchars_decode($product_data['name']),
				'costPerItem' => $product['price'] * $order['currency_value'],
				'amount' => $product['quantity'],
				'description' => $description,
			);
		}

		foreach($order_totals as $order_total){
			if($order_total['code']=='coupon'){
				$coupon_discount = (int)($order_total['value']);
				$coupon_discount = -$coupon_discount;
				if($coupon_discount!=0){
					$coupon_title = htmlspecialchars_decode($order_total['title']);
					$data['products'][] = array(
						'id' => 'COUPON',
						'name' => 'КУПОН',
						'costPerItem' => '0',
						'discount' => $coupon_discount,
						'amount' => 1,
						'description' => $coupon_title,
					);
				}
			}
			if($order_total['code']=='voucher'){
				$voucher_discount = (int)($order_total['value']);
				$voucher_discount = -$voucher_discount;
				if($voucher_discount!=0){
					$voucher_title = htmlspecialchars_decode($order_total['title']);
					$data['products'][] = array(
						'id' => 'VOUCHER',
						'name' => 'ПОДАРОЧНЫЙ СЕРТИФИКАТ',
						'costPerItem' => '0',
						'discount' => $voucher_discount,
						'amount' => 1,
						'description' => $voucher_title,
					);
				}
			}
			if($order_total['code']=='shipping'){
				/*
				$shipping_price = (int)($order_total['value']);
				if($shipping_price!=0){
					$shipping_title = htmlspecialchars_decode($order_total['title']);
					$data['products'][] = array(
						'id' => 'DELIVERY',
						'name' => 'ДОСТАВКА',
						'costPerItem' => $shipping_price,
						'discount' => 0,
						'amount' => 1,
						'description' => $shipping_title,
					);
				}
				*/
			}
		}

		$shipping_method = trim(htmlspecialchars_decode($order['shipping_method']));
		$shipping_code = trim(htmlspecialchars_decode($order['shipping_code']));
		$shipping_method = $shipping_code;
		
		$payment_method = trim(htmlspecialchars_decode($order['payment_method']));
		$payment_code = trim(htmlspecialchars_decode($order['payment_code']));
		$payment_method = $payment_code;
		
		$comment = trim(htmlspecialchars_decode($order['comment']));

		$shipping_country = trim(htmlspecialchars_decode($order['shipping_country']));
		$shipping_postcode = trim(htmlspecialchars_decode($order['shipping_postcode']));
		$shipping_zone = trim(htmlspecialchars_decode($order['shipping_zone']));
		$shipping_city = trim(htmlspecialchars_decode($order['shipping_city']));
		$shipping_address_1 = trim(htmlspecialchars_decode($order['shipping_address_1']));
		$shipping_address_2 = trim(htmlspecialchars_decode($order['shipping_address_2']));
		/*
		$shipping_address = $shipping_city;
		if($shipping_address_1){
			$shipping_address .= ', '.$shipping_address_1;
		}
		if($shipping_address_2){
			$shipping_address .= ', '.$shipping_address_2;
		}
		if($shipping_zone){
			$shipping_address = $shipping_zone.', '.$shipping_address;
		}
		*/
		$shipping_address = $shipping_address_1;

		$data['novaposhta']['ServiceType'] = 'WarehouseWarehouse'; // возможные значения: DoorsDoors, DoorsWarehouse, WarehouseWarehouse, WarehouseDoors
		$data['novaposhta']['city'] = $shipping_city;
		$data['novaposhta']['WarehouseNumber'] = $shipping_address_1;
		//$data['novaposhta']['area'] = ''; // область на русском или украинском языке, или Ref области в системе Новой Почты
		//$data['novaposhta']['cityNameFormat'] = ''; // возможные значения: full (по умолчанию), short
		//$data['novaposhta']['backwardDeliveryCargoType'] = ''; // возможные значения: None - без наложенного платежа, Money - с наложенным платежом

		$data['shipping_address'] = $shipping_address;

		$data['shipping_method'] = $shipping_method;
		$data['payment_method'] = $payment_method;
		$data['comment'] = $comment;
		
		// DEBUG
		/*
		$handle = fopen(dirname(__FILE__).'/salesdrive_log.txt', "a");
		$date = date('m/d/Y h:i:s a', time());
		ob_start();
		print($date.". ".$_SERVER['REMOTE_ADDR']."\n");

		print("ORDER:\n");
		print_r($order);

		$htmlStr = ob_get_contents()."\n";
		ob_end_clean(); 
		fwrite($handle,$htmlStr);		
		*/

		$data['prodex24page'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
		
		// если указаны utm-cookies, то использовать cookies. Иначе использовать $order['custom_field']
		if(
			isset($_COOKIE["prodex24source_full"]) || 
			isset($_COOKIE["prodex24source"]) ||
			isset($_COOKIE["prodex24medium"]) ||
			isset($_COOKIE["prodex24campaign"]) ||
			isset($_COOKIE["prodex24content"]) ||
			isset($_COOKIE["prodex24term"])
		){
			$data['prodex24source_full'] = isset($_COOKIE['prodex24source_full']) ? $_COOKIE['prodex24source_full'] : '';
			$data['prodex24source'] = isset($_COOKIE['prodex24source']) ? $_COOKIE['prodex24source'] : '';
			$data['prodex24medium'] = isset($_COOKIE['prodex24medium']) ? $_COOKIE['prodex24medium'] : '';
			$data['prodex24campaign'] = isset($_COOKIE['prodex24campaign']) ? $_COOKIE['prodex24campaign'] : '';
			$data['prodex24content'] = isset($_COOKIE['prodex24content']) ? $_COOKIE['prodex24content'] : '';
			$data['prodex24term'] = isset($_COOKIE['prodex24term']) ? $_COOKIE['prodex24term'] : '';
		}
		else{
			$data['prodex24source_full'] = isset($order_custom_field['prodex24source_full']) ? $order_custom_field['prodex24source_full'] : '';
			$data['prodex24source'] = isset($order_custom_field['prodex24source']) ? $order_custom_field['prodex24source'] : '';
			$data['prodex24medium'] = isset($order_custom_field['prodex24medium']) ? $order_custom_field['prodex24medium'] : '';
			$data['prodex24campaign'] = isset($order_custom_field['prodex24campaign']) ? $order_custom_field['prodex24campaign'] : '';
			$data['prodex24content'] = isset($order_custom_field['prodex24content']) ? $order_custom_field['prodex24content'] : '';
			$data['prodex24term'] = isset($order_custom_field['prodex24term']) ? $order_custom_field['prodex24term'] : '';
		}

		$this->load->library('salesdrive');
		$salesdrive = new Salesdrive($this->config->get('module_salesdrive_domain'), $this->config->get('module_salesdrive_key'));

		$salesdrive->addOrder($data);
	}

	private function getVirtualProductData($product_data, $options) {
		// product options
		$product_options = $this->model_catalog_product->getProductOptions($product_data['id']);
		usort(
			$product_options, 
			function ($a, $b){
			if ($a["option_id"] == $b["option_id"]) {
				return 0;
			}
			return ($a["option_id"] < $b["option_id"]) ? -1 : 1;
			}
		);

		foreach ($product_options as $option) {
			//if ($option['required']) {                
			if(count($option['product_option_value']) > 0) {
				$id = $product_data['id'].'_'.$option['option_id'];

				foreach ($option['product_option_value'] as $k => $option_value) {
					if (in_array($option_value['product_option_value_id'], array_column($options, 'product_option_value_id'))) {
						$product_data = array(
							'id' => $id.'-'.$option_value['option_value_id'],
							'product_id' => $product_data['id'],
							'name' => $product_data['name'].' '.$option_value['name'],
						);
					}
				}
			}
			//}
		}

		return $product_data;
	}

	public function update() {
		$feed = $this->config->get('module_salesdrive_feed');
		$xml = file_get_contents($feed);
		$xml = new SimpleXMLElement($xml);
		$offers = $xml->shop->offers;
		$n = count($offers->offer);
		for ($i = 0; $i < $n; $i++) {
			$offer = $offers->offer[ $i ];
			$id = (string)$offer['id'];
			$qty = (int)$offer->quantity_in_stock;
			$ids = explode('_', $id);
			$product_id = (int)$ids[0];
			if(count($ids) == 1){
				$this->db->query("UPDATE `" . DB_PREFIX . "product` SET `quantity` = '" . $qty . "' WHERE `product_id` = '" . $product_id . "'");
			}
			elseif(count($ids) == 2){
				$options = explode('-', $ids[1]);
				if(count($options) == 2){
					$this->db->query("UPDATE `" . DB_PREFIX . "product_option_value` SET `quantity` = '" . $qty . "' WHERE `product_id` = '" . $product_id . "' AND `option_id` = '" . (int)$options[0] . "' AND `option_value_id` = '" . (int)$options[1] . "'");
				}
				if(!isset($product_qty[$product_id])){
					$product_qty[$product_id] = $qty;
				}
				else{
					$product_qty[$product_id]+=$qty;
				}
			}
		}
		// Update product quantity as a sum of options' quantity
		foreach($product_qty as $product_id => $qty){
			$this->db->query("UPDATE `" . DB_PREFIX . "product` SET `quantity` = '" . $qty . "' WHERE `product_id` = '" . $product_id . "'");
		}
		echo 'Total products in YML feed: ' . $n . '. Quantity successfully updated!';
	}
	
	// Save utm data to $order['custom_field']
	public function eventAddOrder($route = false, $order_info = false, $order_id = false) {
		if(isset($order_info[0]['custom_field'])){
			$data['custom_field'] = $order_info[0]['custom_field'];
		}
		else{
			$data['custom_field'] = [];
		}
		if(isset($_COOKIE["prodex24source_full"]) && strpos($_COOKIE["prodex24source_full"],'secure.wayforpay.com')===false){
			$data['custom_field']['prodex24source_full'] = $_COOKIE["prodex24source_full"];
		}
		if(isset($_COOKIE["prodex24source"])){
			$data['custom_field']['prodex24source'] = $_COOKIE["prodex24source"];
		}
		if(isset($_COOKIE["prodex24medium"])){
			$data['custom_field']['prodex24medium'] = $_COOKIE["prodex24medium"];
		}
		if(isset($_COOKIE["prodex24campaign"])){
			$data['custom_field']['prodex24campaign'] = $_COOKIE["prodex24campaign"];
		}
		if(isset($_COOKIE["prodex24content"])){
			$data['custom_field']['prodex24content'] = $_COOKIE["prodex24content"];
		}
		if(isset($_COOKIE["prodex24term"])){
			$data['custom_field']['prodex24term'] = $_COOKIE["prodex24term"];
		}
		$this->db->query("UPDATE `" . DB_PREFIX . "order` SET custom_field = '" . $this->db->escape(json_encode($data['custom_field'])) . "' WHERE order_id = '" . (int)$order_id . "'");		
	}
	
}