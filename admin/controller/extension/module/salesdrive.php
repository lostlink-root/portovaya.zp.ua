<?php

/* OpenCart v. 2.3, 3.0 */

// raise time limit, if there are many products to export to SalesDrive
// set_time_limit(300);

// Fix for function array_column for PHP <=5.4
if (! function_exists('array_column')) {
    function array_column(array $input, $columnKey, $indexKey = null) {
        $array = array();
        foreach ($input as $value) {
            if ( !array_key_exists($columnKey, $value)) {
                trigger_error("Key \"$columnKey\" does not exist in array");
                return false;
            }
            if (is_null($indexKey)) {
                $array[] = $value[$columnKey];
            }
            else {
                if ( !array_key_exists($indexKey, $value)) {
                    trigger_error("Key \"$indexKey\" does not exist in array");
                    return false;
                }
                if ( ! is_scalar($value[$indexKey])) {
                    trigger_error("Key \"$indexKey\" does not contain scalar value");
                    return false;
                }
                $array[$value[$indexKey]] = $value[$columnKey];
            }
        }
        return $array;
    }
}

class ControllerExtensionModuleSalesdrive extends Controller
{
    private $error = array();
    private $token = 'token';
    private $_salesdrive;

    public function __construct($registry)
    {
		parent::__construct($registry);
		$this->token = (defined('VERSION') && version_compare(VERSION,'3.0.0.0','>=')) ? 'user_token' : $this->token;
		if($this->config->get('module_salesdrive_status') == 1) {
			$this->load->library('salesdrive');
			$this->_salesdrive = new Salesdrive($this->config->get('module_salesdrive_domain'), $this->config->get('module_salesdrive_key'));
		}
		$this->load->model('catalog/attribute');
		$this->load->model('catalog/product');
		$this->load->language('extension/module/salesdrive');
        $this->load->model('catalog/category');
		$this->load->model('catalog/manufacturer');
		$this->load->model('localisation/length_class');
    }

    public function install()
    {
        $this->load->model('extension/module/salesdrive');
        if (version_compare(VERSION,'3.0.0.0','>=')) {
            $this->load->model('setting/extension');
        } else {
            $this->load->model('extension/extension');
        }
        $this->load->model('user/user_group');

        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/module/salesdrive');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/module/salesdrive');

        $this->model_extension_module_salesdrive->install();
    }

    public function uninstall()
    {
        $this->load->model('extension/module/salesdrive');
        $this->load->model('setting/setting');
        if (version_compare(VERSION,'3.0.0.0','>=')) {
            $this->load->model('setting/extension');
        } else {
            $this->load->model('extension/extension');
        }

        $this->model_extension_module_salesdrive->uninstall();
        if (version_compare(VERSION,'3.0.0.0','>=')) {
            $this->model_setting_extension->uninstall('salesdrive', $this->request->get['extension']);
        } else {
            $this->model_extension_extension->uninstall('salesdrive', $this->request->get['extension']);
        }
        $this->model_setting_setting->deleteSetting($this->request->get['module_salesdrive']);
    }

    public function index()
    {
        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('module_salesdrive', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            //$this->response->redirect($this->url->link('extension/extension', $this->token.'=' . $this->session->data[$this->token] . '&type=module', true));
        }

        $data['heading_title'] = $this->language->get('heading_title');
        $data['heading_stock_import'] = $this->language->get('heading_stock_import');

        $data['text_edit'] = $this->language->get('text_edit');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');

        $data['entry_domain'] = $this->language->get('entry_domain');
        $data['entry_key'] = $this->language->get('entry_key');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['entry_feed'] = $this->language->get('entry_feed');
        $data['entry_gen'] = $this->language->get('entry_gen');
        $data['entry_cron'] = $this->language->get('entry_cron');

        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');
        $data['button_synchronize'] = $this->language->get('button_synchronize');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else if (isset($this->session->data['error'])) {
            $data['error_warning'] = $this->session->data['error'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['domain'])) {
            $data['error_domain'] = $this->error['domain'];
        } else {
            $data['error_domain'] = '';
        }

        if (isset($this->error['key'])) {
            $data['error_key'] = $this->error['key'];
        } else {
            $data['error_key'] = '';
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
        } else {
            $data['success'] = '';
        }
         
        
        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', $this->token.'=' . $this->session->data[$this->token], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('extension/extension', $this->token.'=' . $this->session->data[$this->token] . '&type=module', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/salesdrive', $this->token.'=' . $this->session->data[$this->token], true)
        );

        $data['action'] = $this->url->link('extension/module/salesdrive', $this->token.'=' . $this->session->data[$this->token], true);
        
        if (version_compare(VERSION,'3.0.0.0','>=')) {
            $data['cancel'] = $this->url->link('marketplace/extension', $this->token.'=' . $this->session->data[$this->token] . '&type=module', true);
        } else {
            $data['cancel'] = $this->url->link('extension/extension', $this->token.'=' . $this->session->data[$this->token] . '&type=module', true);
        }
        $data['synchronize'] = $this->url->link('extension/module/salesdrive/sync', $this->token.'=' . $this->session->data[$this->token] . '&type=module', true);

        if (isset($this->request->post['module_salesdrive_domain'])) {
            $data['module_salesdrive_domain'] = $this->request->post['module_salesdrive_domain'];
        } else {
            $data['module_salesdrive_domain'] = $this->config->get('module_salesdrive_domain');
        }

        if (isset($this->request->post['module_salesdrive_key'])) {
            $data['module_salesdrive_key'] = $this->request->post['module_salesdrive_key'];
        } else {
            $data['module_salesdrive_key'] = $this->config->get('module_salesdrive_key');
        }

        if (isset($this->request->post['module_salesdrive_status'])) {
            $data['module_salesdrive_status'] = $this->request->post['module_salesdrive_status'];
        } else {
            $data['module_salesdrive_status'] = $this->config->get('module_salesdrive_status') ? $this->config->get('module_salesdrive_status') : 1;
        }

        if (isset($this->request->post['module_salesdrive_feed'])) {
            $data['module_salesdrive_feed'] = $this->request->post['module_salesdrive_feed'];
        } else {
            $data['module_salesdrive_feed'] = $this->config->get('module_salesdrive_feed');
        }

        if (isset($this->request->post['module_salesdrive_cron'])) {
            $data['module_salesdrive_cron'] = $this->request->post['module_salesdrive_cron'];
        } else {
            $data['module_salesdrive_cron'] = $this->config->get('module_salesdrive_cron');
        }

        $data['module_salesdrive_gen'] = str_replace('admin/', '', $this->url->link('extension/module/salesdrive/update'));

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/salesdrive', $data));
    }

    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/module/salesdrive')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->request->post['module_salesdrive_domain']) {
            $this->error['domain'] = $this->language->get('error_domain');
        }

        if (!$this->request->post['module_salesdrive_key']) {
            $this->error['key'] = $this->language->get('error_key');
        }

        return !$this->error;
    }
    
    public function sync()
    {
		$limit = 100;
        $offset = isset($_POST['offset']) ? $_POST['offset'] : 0;
		$time_start = time();
		$category_count = 0;
		if($offset){
			$sync_categories = 0;
		}
		else{
			$start = 0;
			$sync_categories = 1;
		}
			
		// category data
		if($sync_categories){
			$data = array();
			$categories = $this->model_catalog_category->getCategories();
			$category_count = count($categories);
			foreach($categories as $k => $category_info) {
				$desc = $this->model_catalog_category->getCategoryDescriptions($category_info['category_id']);
				$name = '';
				$item = array_shift($desc);
				$name = $item['name'];
				$data[] = array(
				   'id' => $category_info['category_id'],
				   'name' => htmlspecialchars_decode($name),
				   'parentId' => $category_info['parent_id'],
				);
			}
			$this->_salesdrive->saveCategories($data);
			//second save categories for correct category nesting
			$this->_salesdrive->saveCategories($data);
		}
        
        // product data
        $data = array();
        $products = $this->model_catalog_product->getProducts(['start'=>$offset, 'limit'=>$limit, 'filter_status'=>1]);
		$product_count = count($products);
		$products_with_variations = 0;
        foreach ($products as $k => $product_info) {
            $data = array_merge($data, $this->createData($product_info));
			/*
            $i = $k+1;
            if($i % 50 == 0) {
                $this->_salesdrive->saveProduct($data);
                $data = array();
            }
			*/
        }
		$products_with_variations += count($data);
        if (!empty($data)) {
            $this->_salesdrive->saveProduct($data);
        }

        if ($this->_salesdrive->hasErrors()) {
            $this->session->data['error'] = $this->_salesdrive->getErrors();
        } else {
            $this->session->data['success'] = $this->language->get('text_sync_success');
        }
		
		// Generate result json
		$time_finish = time();
		$execution_time = $time_finish - $time_start;
		$timeElapsed = isset($_POST['timeElapsed']) ? $_POST['timeElapsed'] : 0;
		$timeElapsed += $execution_time;
		if($product_count==0){
			$finish=1;
		}
		else{
			$finish=0;
		}
		$exported = $product_count+$offset;
		$variationCount = isset($_POST['variationCount']) ? $_POST['variationCount'] : 0;
		$variationCount += $products_with_variations;
		
		$result = [
			'product_count' => $product_count,
			'products_with_variations' => $products_with_variations,
			'variationCount' => $variationCount,
			'category_count' => $category_count,
			'execution_time' => $execution_time,
			'finish' => $finish,
			'timeElapsed' => $timeElapsed,
			'exported' => $exported
		];
		echo json_encode($result);
		//$this->response->redirect($this->url->link('extension/module/salesdrive/sync', $this->token.'=' . $this->session->data[$this->token] . '&type=module' . '&start='.($start+$limit), true));
        //$this->response->redirect($this->url->link('extension/module/salesdrive', $this->token.'=' . $this->session->data[$this->token] . '&type=module', true));
    }

    public function eventPreEditProduct($route, $vdata) {
		if ($this->config->get('module_salesdrive_status') == 1) {
			if (isset($vdata[1])) {
                $vdata[1]['product_id'] = $vdata[0];
                $vdata[1]['id'] = $vdata[1]['product_id'];

                $new_data = array($vdata[1]);
				if(isset($vdata[1]['product_option'])){
					foreach ($vdata[1]['product_option'] as $option) {
						//if ($option['required']) {
							if(!empty($option['product_option_value'])) {
								$new_data = $this->generateOptionProductIds($new_data, $option);
							}
						//}
					}
				}
                
                $product_data = array(
                    'product_id' => $vdata[1]['product_id'],
                    'sku' => $vdata[1]['sku'],
                    'model' => $vdata[1]['model'],
                );
                $product_data = $this->getVirtualProductData($product_data, true);
                $result = array();
				
                foreach($product_data as $k=>$item) {
					if(!in_array($item['id'], array_column($new_data, 'id'))) {
                        $result[] = $item;
                    }
                }
               if(!empty($result)) {
				   $this->_salesdrive->deleteProduct($result);
               }
            }
        }
    }
    public function eventEditProduct($route, $vdata, $product_id)
    {
        if ($this->config->get('module_salesdrive_status') == 1) {
           
           $product_id = $product_id ? $product_id : $vdata[0];
           
           $product_info = $this->model_catalog_product->getProduct($product_id);

            if ($product_info['status'] == 0) {
                $product_data = array(
                    'product_id' => $product_id,
					'sku' => $product_info['sku'],
                    'model' => $product_info['model'],
                );
                $product_data = $this->getVirtualProductData($product_data, true);
            
				$this->_salesdrive->deleteProduct($product_data);
            } else {
               $data = $this->createData($product_info);
               
               $this->_salesdrive->saveProduct($data);
            }
        }
    }
    
    private function createData($product_info) {
        
		// product category
        $category_id = '';
	    $category_name = '';
		$product_main_category_id = '';
        $product_cats = $this->model_catalog_product->getProductCategories($product_info['product_id']);
		if(method_exists($this->model_catalog_product, 'getProductMainCategoryId')){
			$product_main_category_id = $this->model_catalog_product->getProductMainCategoryId($product_info['product_id']);
		}
        if($product_main_category_id){
 		   $category_id = $product_main_category_id;
        }
 	    elseif($product_cats){
		   $category_id = $product_cats[0];
        }
	    if($category_id){
		   $category_info = $this->model_catalog_category->getCategory($category_id);
           $category_name = htmlspecialchars_decode($category_info['name']);
	    }

        // product manufacturer
        $manufacturer_name = '';
        if ($product_info['manufacturer_id']) {
           $manufacturer = $this->model_catalog_manufacturer->getManufacturer($product_info['manufacturer_id']);
           $manufacturer_name = $manufacturer['name'];
        }

        //generate virtual products by options
        $product_data = $this->getVirtualProductData($product_info);
		
		// product volume
		$product_length_class_id = $product_info['length_class_id'];
		$product_length_class_unit = '';
		$length_classes = $this->model_localisation_length_class->getLengthClasses();
		foreach($length_classes as $length_class){
			if($length_class['length_class_id']==$product_length_class_id){
				$product_length_class_unit = $length_class['unit'];
			}
		}
		$product_length_multiplier = 1;
		if($product_length_class_unit){
			if($product_length_class_unit == 'mm' || $product_length_class_unit == 'мм'){
				$product_length_multiplier= 0.001;
			}
			if($product_length_class_unit == 'cm' || $product_length_class_unit == 'см'){
				$product_length_multiplier= 0.01;
			}
		}
		$product_length_multiplier_cubic = pow($product_length_multiplier, 3);
		$product_volume = $product_info['length'] * $product_info['width'] * $product_info['height'] * $product_length_multiplier_cubic;
        

		// product specials
		$product_specials = $this->model_catalog_product->getProductSpecials($product_info['product_id']);
		$price_difference = 0;
		$discount = [];
		$date_now = strtotime(date("Y-m-d"));
		if(count($product_specials)==1){
			$product_special = $product_specials[0];
			$date_start = $product_special['date_start'];
			$date_end = $product_special['date_end'];
			$discount['value'] = $product_info['price'] - $product_special['price'];
			if($product_special['date_start'] != '0000-00-00'){
				$date_start = strtotime($product_special['date_start']);
				$discount['date_start'] = date("d.m.Y", $date_start);
			}
			if($product_special['date_end'] != '0000-00-00'){
				$date_end = strtotime($product_special['date_end']);
				$discount['date_end'] = date("d.m.Y", $date_end);
			}
		}
		else{
			foreach($product_specials as $product_special){
				$date_start = '';
				$date_end = '';
				if($product_special['date_start']!='0000-00-00'){
					$date_start = strtotime($product_special['date_start']);
				}
				if($product_special['date_end']!='0000-00-00'){
					$date_end = strtotime($product_special['date_end']);
				}
				if(!$date_start || $date_now >= $date_start){
					if(!$date_end || $date_now <= $date_end){
						$discount['value'] = $product_info['price'] - $product_special['price'];
						if($date_end){
							$discount['date_end'] = date("d.m.Y", $date_end);
						}
						if($date_start){
							$discount['date_start'] = date("d.m.Y", $date_start);
						}
					}
				}
			}
		}
		
		//site url, images
		if(isset($_SERVER['REQUEST_SCHEME'])){
			$request_scheme = $_SERVER['REQUEST_SCHEME'];
		}
		elseif(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==1){
			$request_scheme = 'https';
		}
		else{
			$request_scheme = 'http';
		}
		$site_url = $request_scheme.'://'.$_SERVER['HTTP_HOST'];
		$site_folder = preg_replace("/^([^\?]*)\/admin\/.*$/","$1",$_SERVER['REQUEST_URI']);
		$site_url .= $site_folder;
		
		$product_gallery_images = $this->model_catalog_product->getProductImages($product_info['product_id']);
		$product_images = array();
		$i=0;
		if($product_info['image']){
			$product_images[$i]['fullsize'] = $site_url.'/image/'.$product_info['image'];
			$i++;
		}
		foreach($product_gallery_images as $product_gallery_image){
			$product_images[$i]['fullsize'] = $site_url.'/image/'.$product_gallery_image['image'];
			$i++;
		}
		
		// product attributes
		$product_attributes = $this->model_catalog_product->getProductAttributes($product_info['product_id']);
		$product_attributes_salesdrive = [];
		$ai = 0;
		foreach($product_attributes as $product_attribute){
			$attribute_id = $product_attribute['attribute_id'];
			$attribute = $this->model_catalog_attribute->getAttribute($attribute_id);
			$attribute_description = '';
			foreach($product_attribute['product_attribute_description'] as $product_attribute_description){
				if(isset($product_attribute_description['text'])){
					$attribute_description = $product_attribute_description['text'];
				}
			};
			if($attribute_id && $attribute_description){
				$product_attributes_salesdrive[$ai]['name'] = htmlspecialchars_decode($attribute['name']);
				$product_attributes_salesdrive[$ai]['value'] = htmlspecialchars_decode($attribute_description);
				$ai++;
			}
		}

		// build result data array
		$data = array();
		$i = 0;
		foreach ($product_data as $od) {
			$data[$i] = array(
				'id' => $od['id'],
				'name' => htmlspecialchars_decode($od['name']),
				'sku' => htmlspecialchars_decode($product_info['sku']),
				//'sku' => htmlspecialchars_decode($product_info['model']),
				'stockBalance' => $product_info['quantity'],
				'manufacturer' => htmlspecialchars_decode($manufacturer_name),
				'costPerItem' => $od['price'],
				'discount' => $discount,
				'category' => [
					'id' => $category_id,
					'name' => htmlspecialchars_decode($category_name),
				],
				'description' => htmlspecialchars_decode($product_info['description']),
				'images' => $product_images,
				'params' => $product_attributes_salesdrive,
				'url' => $site_url.'/index.php?route=product/product&product_id='.$product_info['product_id'],
			);
			if($product_info['weight']>0){
				$data[$i]['weight'] = $product_info['weight'];
			}
			if($product_volume){
				$data[$i]['volume'] = $product_volume;
			}
			
			$i++;
		}
		
		return $data;
	}
    
    public function eventDeleteProduct($route, $data)
    {
        if ($this->config->get('module_salesdrive_status') == 1) {
			$product = $this->model_catalog_product->getProduct($data[0]);
			// product data init
            $product_data = array(
                'product_id' => $product['product_id'],
                'sku' => $product['sku'],
                'model' => $product['model'],
            );
            $product_data = $this->getVirtualProductData($product_data, true);
            $this->_salesdrive->deleteProduct($product_data);
        }
    }
    
    private function getVirtualProductData($product_data, $id_only = false) {
        // product options
        $product_options = $this->model_catalog_product->getProductOptions($product_data['product_id']);
        usort($product_options, function ($a, $b)
        {
            if ($a["option_id"] == $b["option_id"]) {
                return 0;
            }
            return ($a["option_id"] < $b["option_id"]) ? -1 : 1;
        });
        
        $product_data['id'] = $product_data['product_id'];

        $product_data = array($product_data);
        foreach ($product_options as $option) {
            //if ($option['required']) {
                if(!empty($option['product_option_value'])) {
                    $product_data = $id_only ? $this->generateOptionProductIds($product_data, $option) : $this->generateOptionProduct($product_data, $option);
                }
            //}
        }
        return $product_data;
    }
    private function generateOptionProduct($products, $option) {
        $result = array();
        
        foreach($products as $product) {
            $id = (isset($product['id']) ? $product['id'] : $product['product_id']).'_'.$option['option_id'];
			$sku = $product['sku'].'_'.$option['option_id'];;
			$model = $product['model'].'_'.$option['option_id'];;

            foreach ($option['product_option_value'] as $k => $option_value) {
                $product_option_value = $this->model_catalog_product->getProductOptionValue($product['product_id'], $option_value['product_option_value_id']);
				$product_price = $product['price'];
				if($option_value['price_prefix'] == "+"){
					$product_price = $product['price'] + $product_option_value['price'];
				}
				if($option_value['price_prefix'] == "-"){
					$product_price = $product['price'] - $product_option_value['price'];
				}
				if($option_value['price_prefix'] == "*"){
					$product_price = $product['price'] * $product_option_value['price'];
				}
				if($option_value['price_prefix'] == "/" && $product_option_value['price']!=0){
					$product_price = $product['price'] / $product_option_value['price'];
				}
				if($option_value['price_prefix'] == "="){
					$product_price = $product_option_value['price'];
				}
				if($option_value['price_prefix'] == "u"){
					// +%
					$product_price = $product['price'] * (1 + $product_option_value['price']);
				}
				if($option_value['price_prefix'] == "d"){
					// -%
					$product_price = $product['price'] * (1 - $product_option_value['price']);
				}
				
                $result[] = array(
                    'id' => $id.'-'.$option_value['option_value_id'],
                    'sku' => $sku.'-'.$option_value['option_value_id'],
                    'model' => $model.'-'.$option_value['option_value_id'],
                    'product_id' => $product['product_id'],
                    'name' => $product['name'].' '.$product_option_value['name'],
                    'price' => $product_price,
                );
            }
        }
        $result = !empty($result) ? $result : $products;
        
       return $result;
    }

    private function generateOptionProductIds($products, $option) {

		$result = array();
        foreach($products as $product) {
            $id = (isset($product['id']) ? $product['id'] : $product['product_id']).'_'.$option['option_id'];
			$sku = $product['sku'].'_'.$option['option_id'];;
			$model = $product['model'].'_'.$option['option_id'];;
            
            foreach ($option['product_option_value'] as $k => $option_value) {
                $result[] = array(
                    'id' => $id.'-'.$option_value['option_value_id'],
                    'sku' => $sku.'-'.$option_value['option_value_id'],
                    'model' => $model.'-'.$option_value['option_value_id'],
                );
            }
        }
        $result = !empty($result) ? $result : $products;
        
        return $result;
    }
}
