<?php
//==============================================================================
// Distance-Based Shipping v303.1
// 
// Author: Clear Thinking, LLC
// E-mail: johnathan@getclearthinking.com
// Website: http://www.getclearthinking.com
// 
// All code within this file is copyright Clear Thinking, LLC.
// You may not copy or reuse code within this file without written permission.
//==============================================================================

class ModelExtensionShippingDistanceBased extends Model {
	private $type = 'shipping';
	private $name = 'distance_based';
	private $testing_mode;
	private $charge;
	
	public function getQuote($address) {
		$settings = $this->getSettings();
		$this->testing_mode = $settings['testing_mode'];
		
		$this->logMessage("\n" . '------------------------------ Starting Test ' . date('Y-m-d G:i:s') . ' ------------------------------');
		
		if (empty($settings['status'])) {
			$this->logMessage('Extension is disabled');
			return;
		}
		
		// Set address info
		$addresses = array();
		$this->load->model('account/address');
		foreach (array('shipping', 'payment', 'geoiptools') as $address_type) {
			if ($address_type == 'geoiptools' && !empty($this->session->data['geoip_data']['location'])) {
				$address = $this->session->data['geoip_data']['location'];
			} elseif (($address_type == 'shipping' && empty($address)) || $address_type == 'payment') {
				$address = array();
				
				if ($this->customer->isLogged()) 										$address = $this->model_account_address->getAddress($this->customer->getAddressId());
				if (!empty($this->session->data['country_id']))							$address['country_id'] = $this->session->data['country_id'];
				if (!empty($this->session->data['zone_id']))							$address['zone_id'] = $this->session->data['zone_id'];
				if (!empty($this->session->data['postcode']))							$address['postcode'] = $this->session->data['postcode'];
				if (!empty($this->session->data['city']))								$address['city'] = $this->session->data['city'];
				
				if (!empty($this->session->data[$address_type . '_country_id']))		$address['country_id'] = $this->session->data[$address_type . '_country_id'];
				if (!empty($this->session->data[$address_type . '_zone_id']))			$address['zone_id'] = $this->session->data[$address_type . '_zone_id'];
				if (!empty($this->session->data[$address_type . '_postcode']))			$address['postcode'] = $this->session->data[$address_type . '_postcode'];
				if (!empty($this->session->data[$address_type . '_city']))				$address['city'] = $this->session->data[$address_type . '_city'];
				
				if (!empty($this->session->data['guest'][$address_type]))				$address = $this->session->data['guest'][$address_type];
				if (!empty($this->session->data[$address_type . '_address_id']))		$address = $this->model_account_address->getAddress($this->session->data[$address_type . '_address_id']);
				if (!empty($this->session->data[$address_type . '_address']))			$address = $this->session->data[$address_type . '_address'];
			}

			if (empty($address['address_1']))	$address['address_1'] = '';
			if (empty($address['address_2']))	$address['address_2'] = '';
			if (empty($address['city']))		$address['city'] = '';
			if (empty($address['postcode']))	$address['postcode'] = '';
			if (empty($address['country_id']))	$address['country_id'] = $this->config->get('config_country_id');
			if (empty($address['zone_id']))		$address['zone_id'] =  $this->config->get('config_zone_id');
			
			$country_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "country WHERE country_id = " . (int)$address['country_id']);
			$address['country'] = (isset($country_query->row['name'])) ? $country_query->row['name'] : '';
			$address['iso_code_2'] = (isset($country_query->row['iso_code_2'])) ? $country_query->row['iso_code_2'] : '';
			
			$zone_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone WHERE zone_id = " . (int)$address['zone_id']);
			$address['zone'] = (isset($zone_query->row['name'])) ? $zone_query->row['name'] : '';
			$address['zone_code'] = (isset($zone_query->row['code'])) ? $zone_query->row['code'] : '';
			
			$addresses[$address_type] = $address;
			
			$addresses[$address_type]['geo_zones'] = array();
			$geo_zones_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE country_id = " . (int)$address['country_id'] . " AND (zone_id = 0 OR zone_id = " . (int)$address['zone_id'] . ")");
			if ($geo_zones_query->num_rows) {
				foreach ($geo_zones_query->rows as $geo_zone) {
					$addresses[$address_type]['geo_zones'][] = $geo_zone['geo_zone_id'];
				}
			} else {
				$addresses[$address_type]['geo_zones'] = array(0);
			}
		}
		
		// Record testing mode info
		if ($this->customer->isLogged()) {
			$this->logMessage('CUSTOMER: ' . $this->customer->getFirstName() . ' ' . $this->customer->getLastName() . ' (customer_id: ' . $this->customer->getId() . ', ip: ' . $this->request->server['REMOTE_ADDR'] . ')');
		} else {
			$this->logMessage('CUSTOMER: Guest (' . $this->request->server['REMOTE_ADDR'] . ')');
		}
		
		if ($this->type != 'shipping') {
			$billing_address = array(
				$addresses['payment']['address_1'],
				$addresses['payment']['address_2'],
				$addresses['payment']['city'],
				$addresses['payment']['zone'],
				$addresses['payment']['postcode'],
				$addresses['payment']['country'],
			);
			$this->logMessage('BILLING ADDRESS: ' . implode(', ', array_filter($billing_address)));
		}
		
		$shipping_address = array(
			$addresses['shipping']['address_1'],
			$addresses['shipping']['address_2'],
			$addresses['shipping']['city'],
			$addresses['shipping']['zone'],
			$addresses['shipping']['postcode'],
			$addresses['shipping']['country'],
		);
		$this->logMessage('SHIPPING ADDRESS: ' . implode(', ', array_filter($shipping_address)));
		
		$this->logMessage('EVALUATING RULES:');
		
		// Set order totals if necessary
		if ($this->type != 'total') {
			$prefix = (version_compare(VERSION, '3.0', '<')) ? '' : 'total_';
			
			$order_totals_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "extension WHERE `type` = 'total' ORDER BY `code` ASC");
			$order_totals = $order_totals_query->rows;
			
			$sort_order = array();
			foreach ($order_totals as $key => $value) {
				$sort_order[$key] = $this->config->get($prefix . $value['code'] . '_sort_order');
			}
			array_multisort($sort_order, SORT_ASC, $order_totals);
			
			$total_data = array();
			$order_total = 0;
			$taxes = $this->cart->getTaxes();
			$total_array = array('totals' => &$total_data, 'total' => &$order_total, 'taxes' => &$taxes);
			
			foreach ($order_totals as $ot) {
				if ($ot['code'] == 'shipping' && $this->type == 'shipping') break;
				if (!$this->config->get($prefix . $ot['code'] . '_status') || $ot['code'] == 'intermediate_order_total') continue;
				if (version_compare(VERSION, '2.2', '<')) {
					$this->load->model('total/' . $ot['code']);
					$this->{'model_total_' . $ot['code']}->getTotal($total_data, $order_total, $taxes);
				} elseif (version_compare(VERSION, '2.3', '<')) {
					$this->load->model('total/' . $ot['code']);
					$this->{'model_total_' . $ot['code']}->getTotal($total_array);
				} else {
					$this->load->model('extension/total/' . $ot['code']);
					$this->{'model_extension_total_' . $ot['code']}->getTotal($total_array);
				}
			}
		}
		
		// Set cart and order data
		$this->load->model('catalog/product');
		
		$cart_products = $this->cart->getProducts();
		if (version_compare(VERSION, '2.1', '>=')) {
			foreach ($cart_products as &$cart_product) {
				$cart_product['key'] = $cart_product['product_id'] . json_encode($cart_product['option']);
			}
		}
		
		$cumulative_total_value = $order_total;
		$currency = $this->session->data['currency'];
		$customer_id = (int)$this->customer->getId();
		$customer_group_id = (int)$this->customer->getGroupId();
		$distance = 0;
		$language = (isset($this->session->data['language'])) ? $this->session->data['language'] : $this->config->get('config_language');
		$main_currency = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE `key` = 'config_currency' AND store_id = 0")->row['value'];
		$store_id = (isset($this->session->data['store_id'])) ? (int)$this->session->data['store_id'] : (int)$this->config->get('config_store_id');
		
		// Loop through charges
		$sort_order = array();
		foreach ($settings['charge'] as $key => $value) {
			$sort_order[$key] = (empty($value['group'])) ? 0 : $value['group'];
		}
		array_multisort($sort_order, SORT_ASC, $settings['charge']);
		
		$charges = array();
		
		foreach ($settings['charge'] as $charge) {
			// Set up basic charge data
			if (!empty($charge['title_admin'])) {
				$charge['title'] = $charge['title_admin'];
			} elseif (!empty($charge['title_' . $language])) {
				$charge['title'] = $charge['title_' . $language];
			} elseif (!empty($charge['group'])) {
				$charge['title'] = '(Group ' . $charge['group'] . ')';
			} else {
				$charge['title'] = '';
			}
			
			if (empty($charge['group'])) {
				$charge['group'] = 0;
			}
			if ((int)$charge['group'] < 0) {
				$this->logMessage('"' . $charge['title'] . '" disabled because it has a negative Group value');
				continue;
			}
			
			if (empty($charge['type'])) {
				$charge['type'] = str_replace(array('_based', '_fee'), '', $this->name);
			}
			
			$this->charge = $charge;
			
			// Compile rules and rule sets
			$rule_list = (!empty($charge['rule'])) ? $charge['rule'] : array();
			$rule_sets = array();
			
			foreach ($rule_list as $rule) {
				if (isset($rule['type']) && $rule['type'] == 'rule_set') {
					$rule_sets[] = $settings['rule_set'][$rule['value']]['rule'];
				}
			}
			
			foreach ($rule_sets as $rule_set) {
				$rule_list = array_merge($rule_list, $rule_set);
			}
			
			$rules = array();
			foreach ($rule_list as $rule) {
				if (empty($rule['type'])) continue;
				
				if (isset($rule['value'])) {
					if (in_array($rule['type'], array('attribute_group', 'category', 'manufacturer', 'product', 'zone'))) {
						$value = substr($rule['value'], strrpos($rule['value'], '[') + 1, -1);
					} else {
						$value = $rule['value'];
					}
				} else {
					$value = 1;
				}
				
				if (!isset($rule['comparison'])) $rule['comparison'] = '';
				if (in_array($rule['type'], array('attribute', 'custom_field', 'option'))) {
					$comparison = substr($rule['comparison'], strrpos($rule['comparison'], '[') + 1, -1);
				} else {
					$comparison = $rule['comparison'];
				}
				$rules[$rule['type']][$comparison][] = $value;
			}
			$this->charge['rules'] = $rules;
			
			// Perform settings overrides
			if (!empty($defaults)) {
				foreach ($defaults as $key => $value) {
					$this->config->set($key, $value);
				}
			}
			
			$defaults = array();
			
			if (isset($rules['setting_override'])) {
				foreach ($rules['setting_override'] as $setting => $override) {
					$defaults[$setting] = $this->config->get($setting);
					$this->config->set($setting, $override[0]);
					
					if ($setting == 'config_address') {
						$distance = 0;
					}
				}
			}
			
			// Check location criteria
			if (isset($rules['location_comparison'])) {
				$location_comparison = $rules['location_comparison'][''][0];
			} else {
				$location_comparison = ($this->type == 'shipping' || empty($addresses['payment']['city'])) ? 'shipping' : 'payment';
			}
			$address = $addresses[$location_comparison];
			$postcode = $address['postcode'];
			
			if ($this->ruleViolation('country', $address['country_id']) ||
				$this->ruleViolation('geo_zone', $address['geo_zones']) ||
				$this->ruleViolation('zone', $address['zone_id'])
			) {
				continue;
			}
			
			// Check order criteria
			if ($this->ruleViolation('currency', $currency) ||
				$this->ruleViolation('customer_group', $customer_group_id) ||
				$this->ruleViolation('language', $language) ||
				$this->ruleViolation('store', $store_id)
			) {
				continue;
			}
			
			// Calculate distance
			if ((isset($rules['distance']) || $charge['type'] == 'distance') && !$distance && !empty($address["address_1"])) {
				$store_address = html_entity_decode(preg_replace('/\s+/', '+', $this->config->get('config_address')), ENT_QUOTES, 'UTF-8');
				$settings['google_apikey'] = trim($settings['google_apikey']);
				
				if (!empty($address['geocode'])) {
					$customer_address = $address['geocode'];
				} else {
					$customer_address = $address['address_1'] . ' ' . $address['address_2'] . ' ' . $address['city'] . ' ' . $address['zone'] . ' ' . $address['country'] . ' ' . $address['postcode'];
					$customer_address = html_entity_decode(preg_replace('/\s+/', '+', $customer_address), ENT_QUOTES, 'UTF-8');
				}
				
				if (isset($settings['distance_calculation']) && $settings['distance_calculation'] == 'driving') {
					$directions = $this->curlRequest('https://maps.googleapis.com/maps/api/directions/json?key=' . $settings['google_apikey'] . '&origin=' . $store_address . '&destination=' . $customer_address);
					if (empty($directions['routes'])) {
						sleep(1);
						$directions = $this->curlRequest('https://maps.googleapis.com/maps/api/directions/json?key=' . $settings['google_apikey'] . '&origin=' . $store_address . '&destination=' . $customer_address);
						if (empty($directions['routes'])) {
							$this->logMessage('The Google directions service returned the error "' . $directions['status'] . '" for origin "' . $store_address . '" and destination "' . $customer_address . '"');
							continue;
						}
					}
					$distance = $directions['routes'][0]['legs'][0]['distance']['value'] / 1609.344;
				} else {
					if ($this->config->get('config_geocode')) {
						$xy = explode(',', $this->config->get('config_geocode'));
						$x1 = $xy[0];
						$y1 = $xy[1];
					} else {
						$geocode = $this->curlRequest('https://maps.googleapis.com/maps/api/geocode/json?key=' . $settings['google_apikey'] . '&address=' . $store_address);
						if (empty($geocode['results'])) {
							sleep(1);
							$geocode = $this->curlRequest('https://maps.googleapis.com/maps/api/geocode/json?key=' . $settings['google_apikey'] . '&address=' . $store_address);
							if (empty($geocode['results'])) {
								$this->logMessage('The Google geocoding service returned the error "' . $geocode['status'] . '" for address "' . $store_address . '"');
								continue;
							}
						}
						$x1 = $geocode['results'][0]['geometry']['location']['lat'];
						$y1 = $geocode['results'][0]['geometry']['location']['lng'];
					}
					
					if (!empty($address['geocode'])) {
						$xy = explode(',', $address['geocode']);
						$x2 = $xy[0];
						$y2 = $xy[1];
					} else {
						$geocode = $this->curlRequest('https://maps.googleapis.com/maps/api/geocode/json?key=' . $settings['google_apikey'] . '&address=' . $customer_address);
						if (empty($geocode['results'])) {
							sleep(1);
							$geocode = $this->curlRequest('https://maps.googleapis.com/maps/api/geocode/json?key=' . $settings['google_apikey'] . '&address=' . $customer_address);
							if (empty($geocode['results'])) {
								$this->logMessage('The Google geocoding service returned the error "' . $geocode['status'] . '" for address "' . $customer_address . '"');
								continue;
							}
						}
						$x2 = $geocode['results'][0]['geometry']['location']['lat'];
						$y2 = $geocode['results'][0]['geometry']['location']['lng'];
					}
					
					$distance = rad2deg(acos(sin(deg2rad($x1)) * sin(deg2rad($x2)) + cos(deg2rad($x1)) * cos(deg2rad($x2)) * cos(deg2rad($y1 - $y2)))) * 60 * 114 / 99;
				}
				
				if (isset($settings['distance_units']) && $settings['distance_units'] == 'km') {
					$distance *= 1.609344;
				}
				$this->logMessage('Calculated distance between ' . $store_address . ' and ' . $customer_address . ' = ' . round($distance, 3) . ' ' . $settings['distance_units']);
			}
			
			// Generate comparison values
			$quantity = 0;
			$cart_criteria = array(
				'total',
			);
			
			foreach ($cart_criteria as $spec) {
				${$spec.'s'} = array();
				if (isset($rules[$spec])) {
					$this->commaMerge($rules[$spec]);
				}
			}
			
			$product_keys = array();
			$total_value = $cumulative_total_value;
			
			foreach ($cart_products as $product) {
				if ($this->type == 'shipping' && !$product['shipping']) {
					$total_value -= $product['total'];
					$this->logMessage($product['name'] . ' (product_id: ' . $product['product_id'] . ') does not require shipping and was ignored');
					continue;
				}
				
				$product_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product WHERE product_id = " . (int)$product['product_id']);
				
				// total
				if (isset($rules['total_value'])) {
					$product_info = $this->model_catalog_product->getProduct($product['product_id']);
					$product_price = ($product_info['special']) ? $product_info['special'] : $product_info['price'];
					
					if (in_array('prediscounted', $rules['total_value'][''])) {
						$totals[$product['key']] = $product['total'] + ($product['quantity'] * ($product_query->row['price'] - $product_price));
					} elseif (in_array('nondiscounted', $rules['total_value'][''])) {
						$product_discount_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_discount WHERE product_id = " . (int)$product['product_id'] . " AND customer_group_id = " . (int)($customer_group_id ? $customer_group_id : $this->config->get('config_customer_group_id')) . " AND quantity <= " . (int)$product['quantity'] . " AND ((date_start = '0000-00-00' OR date_start < NOW()) AND (date_end = '0000-00-00' OR date_end > NOW())) ORDER BY quantity DESC, priority ASC, price ASC LIMIT 1");
						$totals[$product['key']] = ($product_info['special'] || $product_discount_query->num_rows) ? 0 : $product['total'];
					} elseif (in_array('taxed', $rules['total_value'][''])) {
						$totals[$product['key']] = $this->tax->calculate($product['total'], $product['tax_class_id']);
					}
				}
				if (!isset($totals[$product['key']])) {
					$totals[$product['key']] = $product['total'];
				}
				
				// product passed all rules and is eligible for charge
				$product_keys[] = $product['key'];
			}
			
			$row_disabled_text = '"' . $this->charge['title'] . '" disabled';
			
			if (empty($product_keys)) {
				$disable_charge = true;
				
				if (!empty($this->session->data['vouchers'])) {
					$disable_charge = false;
					foreach ($rules as $type => $value) {
						if (in_array($type, array('attribute', 'attribute_group', 'category', 'manufacturer', 'option', 'product', 'product_group', 'other_product_data'))) {
							$disable_charge = true;
						}
					}
				}
				
				if ($disable_charge) {
					$this->logMessage($row_disabled_text . ' for having no eligible products');
					continue;
				}
			}
			
			// Check cart criteria and generate total comparison values
			$single_foreign_currency = (isset($rules['currency']['is']) && count($rules['currency']['is']) == 1 && $main_currency != $currency) ? $rules['currency']['is'][0] : '';
			
			foreach ($cart_criteria as $spec) {
				if ($spec == 'total' && isset($rules['total_value']) && in_array('total', $rules['total_value'][''])) {
					$total = $total_value;
					$cart_total = $total_value;
				} else {
					${$spec} = 0;
					foreach ($product_keys as $product_key) {
						${$spec} += ${$spec.'s'}[$product_key];
					}
					${'cart_'.$spec} = array_sum(${$spec.'s'});
				}
				if ($spec == 'total' && $single_foreign_currency) {
					$total = $this->currency->convert($total, $main_currency, $single_foreign_currency);
				}
			}
			
			// Calculate the charge
			$brackets = (!empty($charge['charges'])) ? array_filter(explode(',', str_replace(array("\n", ',,'), ',', $charge['charges']))) : array(0);
			
			$cost = $this->calculateBrackets($brackets, $charge['type'], ${$charge['type']}, $quantity, $total);
			
			if ($cost === false) {
				$this->logMessage('"' . $this->charge['title'] . '" disabled because the value "' . (isset(${$charge['type']}) ? ${$charge['type']} : '') . '" does not match any of the brackets "' . implode(', ', $brackets) . '"');
				continue;
			}
			// Adjust charge
			if (isset($rules['adjust']['charge']) && !empty($address["address_1"])) {
				foreach ($rules['adjust']['charge'] as $adjustment) {
					$cost += (strpos($adjustment, '%')) ? $cost * (float)$adjustment / 100 : (float)$adjustment;
				}
			}
			if (isset($rules['round'])) {
				foreach ($rules['round'] as $comparison => $values) {
					$round = $values[0];
					if ($comparison == 'nearest') {
						$cost = round($cost / $round) * $round;
					} elseif ($comparison == 'up') {
						$cost = ceil($cost / $round) * $round;
					} elseif ($comparison == 'down') {
						$cost = floor($cost / $round) * $round;
					}
				}
			}
			if (isset($rules['min'])) {
				$cost = max($cost, $rules['min'][''][0]);
			}
			if (isset($rules['max'])) {
				$cost = min($cost, $rules['max'][''][0]);
			}
			if ($single_foreign_currency) {
				$cost = $this->currency->convert($cost, $single_foreign_currency, $main_currency);
			}
			
			// Add to charge array
			$this->logMessage('ENABLED "' . $this->charge['title'] . '" with cost ' . (float)$cost);
			
			$charges[strtolower($charge['group'])][] = array(
				'title'			=> str_replace('[' . $charge['type'] . ']', round(${$charge['type']}, 2), html_entity_decode($charge['title_' . $language], ENT_QUOTES, 'UTF-8')),
				'charge'		=> (float)$cost,
				'tax_class_id'	=> isset($rules['tax_class']) ? $rules['tax_class'][''][0] : $settings['tax_class_id'],
			);
			
			if ($this->type != 'shipping') {
				$cumulative_total_value += (float)$cost;
			}
			
			// Restore setting defaults
			foreach ($defaults as $key => $value) {
				$this->config->set($key, $value);
			}
			
		} // end charge loop
		
		$quote_data = array();
		
		foreach ($charges as $group_value => $group) {
			foreach ($group as $rate) {
				if (($this->type == 'shipping' && $rate['charge'] < 0) || ($this->type == 'total' && $rate['charge'] == 0)) continue;
				
				$taxed_charge = $this->tax->calculate($rate['charge'], $rate['tax_class_id'], $this->config->get('config_tax'));
				
				$quote_data[$this->name . '_' . count($quote_data)] = array(
					'code'			=> $this->name . '.' . $this->name . '_' . count($quote_data),
					'sort_order'	=> $group_value,
					'title'			=> $rate['title'],
					'cost'			=> $rate['charge'],
					'value'			=> $rate['charge'],
					'tax_class_id'	=> $rate['tax_class_id'],
					'text'			=> $this->currency->format($this->type == 'total' ? $rate['charge'] : $taxed_charge, $currency),
				);
			}
		}
		
		$sort_order = array();
		foreach ($quote_data as $key => $value) $sort_order[$key] = $value['sort_order'];
		array_multisort($sort_order, SORT_ASC, $quote_data);
		
		foreach ($quote_data as $quote) {
			$quote['code'] = $this->name;
			$quote['sort_order'] = $settings['sort_order'] . '.' . $quote['sort_order'];
			
			$total_data[] = $quote;
			
			if ($quote['tax_class_id']) {
				foreach ($this->tax->getRates($quote['cost'], $quote['tax_class_id']) as $tax_rate) {
					$taxes[$tax_rate['tax_rate_id']] = (isset($taxes[$tax_rate['tax_rate_id']])) ? $taxes[$tax_rate['tax_rate_id']] + $tax_rate['amount'] : $tax_rate['amount'];
				}
			}
			
			$order_total += $quote['cost'];
		}
		
		if ($this->type == 'shipping' && $quote_data) {
			return array(
				'code'			=> $this->name,
				'title'			=> str_replace('[' . $charge['type'] . ']', round(${$charge['type']}, 2), html_entity_decode($settings['heading_' . $language], ENT_QUOTES, 'UTF-8')),
				'quote'			=> $quote_data,
				'sort_order'	=> $settings['sort_order'],
				'error'			=> false
			);
		} else {
			return array();
		}
	}
	
	//------------------------------------------------------------------------------
	// Private functions
	//------------------------------------------------------------------------------
	private function getSettings() {
		$code = (version_compare(VERSION, '3.0', '<') ? '' : $this->type . '_') . $this->name;
		
		$settings = array();
		$settings_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE `code` = '" . $this->db->escape($code) . "' ORDER BY `key` ASC");
		
		foreach ($settings_query->rows as $setting) {
			$value = $setting['value'];
			if ($setting['serialized']) {
				$value = (version_compare(VERSION, '2.1', '<')) ? unserialize($setting['value']) : json_decode($setting['value'], true);
			}
			$split_key = preg_split('/_(\d+)_?/', str_replace($code . '_', '', $setting['key']), -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
			
				if (count($split_key) == 1)	$settings[$split_key[0]] = $value;
			elseif (count($split_key) == 2)	$settings[$split_key[0]][$split_key[1]] = $value;
			elseif (count($split_key) == 3)	$settings[$split_key[0]][$split_key[1]][$split_key[2]] = $value;
			elseif (count($split_key) == 4)	$settings[$split_key[0]][$split_key[1]][$split_key[2]][$split_key[3]] = $value;
			else 							$settings[$split_key[0]][$split_key[1]][$split_key[2]][$split_key[3]][$split_key[4]] = $value;
		}
		
		return $settings;
	}
	
	private function curlRequest($url) {
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_TIMEOUT, 3);
		$response = json_decode(curl_exec($curl), true);
		curl_close($curl);
		return $response;
	}
	
	private function logMessage($message) {
		if ($this->testing_mode) {
			file_put_contents(DIR_LOGS . $this->name . '.messages', print_r($message, true) . "\n", FILE_APPEND|LOCK_EX);
		}
	}
	
	private function commaMerge(&$rule) {
		$merged_rule = array();
		foreach ($rule as $comparison => $values) {
			$merged_rule[$comparison] = array();
			foreach ($values as $value) {
				$merged_rule[$comparison] = array_merge($merged_rule[$comparison], array_map('trim', explode(',', strtolower($value))));
			}
		}
		$rule = $merged_rule;
	}
	
	private function ruleViolation($rule, $value) {
		$violation = false;
		$rules = $this->charge['rules'];
		$function = (is_array($value)) ? 'array_intersect' : 'in_array';
		
		if (isset($rules[$rule]['after']) && strtotime($value) < min(array_map('strtotime', $rules[$rule]['after']))) {
			$violation = true;
			$comparison = 'after';
		}
		if (isset($rules[$rule]['before']) && strtotime($value) > max(array_map('strtotime', $rules[$rule]['before']))) {
			$violation = true;
			$comparison = 'before';
		}
		if (isset($rules[$rule]['is']) && !$function($value, $rules[$rule]['is'])) {
			$violation = true;
			$comparison = 'is';
		}
		if (isset($rules[$rule]['not']) && $function($value, $rules[$rule]['not'])) {
			$violation = true;
			$comparison = 'not';
		}
		
		if ($violation) {
			$this->logMessage('"' . $this->charge['title'] . '" disabled for violating rule "' . $rule . ' ' . $comparison . ' ' . implode(', ', $rules[$rule][$comparison]) . '" with value "' . (is_array($value) ? implode(',', $value) : $value) . '"');
		}
		
		return $violation;
	}
	
	private function inRange($value, $range_list, $charge_type = '', $skip_testing = false) {
		$in_range = false;
		
		foreach ($range_list as $range) {
			if ($range == '') continue;
			
			$range = (strpos($range, '::')) ? explode('::', $range) : explode('-', $range);
			
			if (strpos($charge_type, 'distance') === 0) {
				if (empty($range[1])) {
					array_unshift($range, 0);
				}
				if ($value >= (float)$range[0] && $value <= (float)$range[1]) {
					$in_range = true;
				}
			} elseif (strpos($charge_type, 'postcode') === 0) {
				$postcode = preg_replace('/[^A-Z0-9]/', '', strtoupper($value));
				$from = preg_replace('/[^A-Z0-9]/', '', strtoupper($range[0]));
				$to = (isset($range[1])) ? preg_replace('/[^A-Z0-9]/', '', strtoupper($range[1])) : $from;
				
				if (strlen($from) < 3 && !preg_match('/[0-9]/', $from)) $from .= '1';
				if (strlen($to) < 3 && !preg_match('/[0-9]/', $to)) $to .= '99';
				
				if (strlen($from) < strlen($postcode)) $from = str_pad($from, max(strlen($postcode), strlen($from) + 3), ' ');
				if (strlen($to) < strlen($postcode)) $to = str_pad($to, max(strlen($postcode), strlen($to) + 3), preg_match('/[A-Z]/', $postcode) ? 'Z' : '9');
				
				$postcode = substr_replace(substr_replace($postcode, ' ', -3, 0), ' ', -2, 0);
				$from = substr_replace(substr_replace($from, ' ', -3, 0), ' ', -2, 0);
				$to = substr_replace(substr_replace($to, ' ', -3, 0), ' ', -2, 0);
				
				if (strnatcasecmp($postcode, $from) >= 0 && strnatcasecmp($postcode, $to) <= 0) {
					$in_range = true;
				}
			} else {
				if ($charge_type != 'attribute' && $charge_type != 'option' && $charge_type != 'other product data' && !isset($range[1])) {
					$range[1] = 999999999;
				}
				
				if ((count($range) > 1 && $value >= $range[0] && $value <= $range[1]) || (count($range) == 1 && $value == $range[0])) {
					$in_range = true;
				}
			}
		}
		
		if (!$skip_testing) {
			if (strpos($charge_type, ' not') ? $in_range : !$in_range) {
				$this->logMessage('"' . $this->charge['title'] . '" disabled for violating rule "' . $charge_type . (strpos($charge_type, ' not') ? ' ' : ' is ') . implode(', ', $range_list) . '" with value "' . $value . '"');
			}
		}
		
		return $in_range;
	}
	
	private function calculateBrackets($brackets, $charge_type, $comparison_value, $quantity, $total) {
		$to = 0;
		
		foreach ($brackets as $bracket) {
			$bracket = str_replace(array('::', ':'), array('~', '='), $bracket);
			
			$bracket_pieces = explode('=', $bracket);
			if (count($bracket_pieces) == 1) {
				array_unshift($bracket_pieces, ($charge_type == 'postcode') ? '0-ZZZZ' : '0-999999');
			}
			
			$from_and_to = (strpos($bracket_pieces[0], '~')) ? explode('~', $bracket_pieces[0]) : explode('-', $bracket_pieces[0]);
			if (count($from_and_to) == 1) {
				array_unshift($from_and_to, ($charge_type == 'postcode') ? $from_and_to[0] : $to);
			}
			$from = trim($from_and_to[0]);
			$to = trim($from_and_to[1]);
			
			$cost_and_per = explode('/', $bracket_pieces[1]);
			$per = (isset($cost_and_per[1])) ? (float)$cost_and_per[1] : 0;
			
			$top = min($to, $comparison_value);
			$bottom = (isset($this->charge['rules']['cumulative'])) ? $from : 0;
			$difference = ($charge_type == 'postcode' || $charge_type == 'price') ? $quantity : $top - $bottom;
			$multiplier = ($per) ? ceil($difference / $per) : 1;
			
			if (!isset($cost) || !isset($this->charge['rules']['cumulative'])) {
				$cost = 0;
			}
			$cost += (strpos($cost_and_per[0], '%')) ? (float)$cost_and_per[0] * $multiplier * $total / 100 : (float)$cost_and_per[0] * $multiplier;
			
			$in_range = $this->inRange($comparison_value, array($from . '::' . $to), $charge_type, true);
			if ($in_range) {
				return $cost;
			}
		}
		
		return false;
	}
}
?>