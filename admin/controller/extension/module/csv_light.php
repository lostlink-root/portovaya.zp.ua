<?php
class ControllerExtensionModuleCsvLight extends Controller {

	private $languages = array();
	private $csv_columns = array();
	private $csv_columns_ex = array();
	private $csv_file = 'csv_lite.csv';
	private $sec_limit = 5;
	private $logs = array();
	private $works = true;
	private $error = false;
	

	public function index(){
		$data['vers'] = 180419.1;
		
		$this->load->language('extension/module/csv_light');
		$this->document->setTitle($this->language->get('heading_title'));

		$this->document->addStyle('view/stylesheet/csv_light.css');
		$this->document->addScript('view/javascript/csv_light.js');

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/csv_light', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		
		$data['backup_link'] = $this->url->link('tool/backup', 'user_token=' . $this->session->data['user_token'], true);
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/csv_light', $data));
	}

	
	// Создаёт CSV файл
	public function create_csv() {
		$this->load->model('catalog/product');
		$this->load->model('catalog/attribute');
		$this->load->model('catalog/attribute_group');
		$this->load->model('catalog/option');
		$this->load->model('localisation/language');
		$this->load->model('catalog/manufacturer');
		$this->load->model('catalog/category');
		$this->load->model('extension/module/csv_light');

		$this->init('export');
		
		$file = fopen(DIR_UPLOAD . $this->csv_file, 'a');
		
		if ( !empty( $this->request->get['processed'] ) ) {
			$processed = $this->request->get['processed'];
		} else {
			$processed = 0;
			$this->fputcsv($file, $this->csv_columns_ex);
		}

		$start_time = time();

		$products = $this->model_extension_module_csv_light->getProducts();

		$products_count = count($products);

		$break = false;

		foreach ( $products as $key => $product_id ) {
			if ($key >= $processed) {
				$product_data = $this->getProductData($product_id);
				$csv_row = $this->csvEncode($product_data);
				$this->fputcsv($file, $csv_row);

				$processed++;
				if ( time() - $start_time > $this->sec_limit ) {
					$break = true;
					break;
				}
			}
		}

		$this->works = $break;

		fclose($file);

		$this->logs[] = '<p>Обработано '.$processed.' из '.$products_count.'</p>';

		$json = array(
			'products_count' => $products_count,
			'processed'      => $processed,
			'logs'           => $this->logs,
			'works'          => $this->works
		);
		

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput( json_encode( $json ) );
	}

	// Читает CSV файл
	public function read_csv() {
		$this->load->model('catalog/product');
		$this->load->model('catalog/attribute');
		$this->load->model('catalog/attribute_group');
		$this->load->model('catalog/option');
		$this->load->model('localisation/language');
		$this->load->model('catalog/manufacturer');
		$this->load->model('catalog/category');
		$this->load->model('extension/module/csv_light');

		$this->init( 'import' );
		$this->validateFile();

		if ( !empty( $this->request->get['processed'] ) ) {
			$processed = $this->request->get['processed'];
			$products_count = $this->session->data['products_count'];
			//$products_count = $this->getProductsCountFromCSV();
		} else {
			$processed = 0;

			// Посчитаем строки один раз и сохраним в сессию, чтобы не драконить зря файл при последующих запросах
			$this->session->data['products_count'] = $this->getProductsCountFromCSV();
			$products_count = $this->session->data['products_count'];

		}

		$start_time = time();

		$file = fopen(DIR_UPLOAD . $this->csv_file, 'r+');

		$csv_sting = 0;
		while ( $csv_row = $this->fgetcsv($file) ) {

			if ( ($processed >= $products_count + 1) ) {
				break;
			}

			if ($csv_sting > $processed) {
				if ( count($csv_row) != count( $this->csv_columns ) ) {
					
					//$this->logs[] = '<pre>'.print_r($product_data, true).'</pre>';
					//$this->logs[] = '<p>count($product_data) = '.count($product_data).' count( $this->csv_columns ) = '.count( $this->csv_columns ).'</p>';
					//$this->logs[] = '<pre>'.print_r($this->csv_columns, true).'</pre>';

					$this->logs[] = '<b style="color: #ff0000">Файл поломан в строке <b>'.($csv_sting + 1).'</b></p>';
					$this->works = false;
					$this->error = true;
					break;
				}

				$product_data = $this->csvDecode($csv_row);
				$this->insertProduct($product_data);
				
				$processed++;
			}

			if ( time() - $start_time > $this->sec_limit ) {
				break;
			}

			$csv_sting++;

		}
		fclose($file);

		$this->logs[] = '<p>Обработано '.$processed.' из '.$products_count.'</p>';

		if ($processed >= $products_count) {
			$this->works = false;
			$this->logs[] = '<b>Импорт закончен!</b>';
		} else {
			$this->works = true;
		}

		
		$json = array(
			'products_count' => $products_count,
			'processed'      => $processed,
			'logs'           => $this->logs,
			'works'          => $this->works,
			'error'          => $this->error
		);

		// D E B U G
		//$this->debug($product_data);

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput( json_encode( $json ) );
	}

	// Отдаёт на скачивание созданный CSV файл
	public function download_csv() {
		$file = DIR_UPLOAD . $this->csv_file;
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename=csv_lite.csv');
		header('Content-Length: ' . filesize($file));
		readfile($file);
		unlink($file);	
		exit;
	}

	// Принимает и проверяет CSV файл (по возможности)
	public function upload_csv() {

		if ( 0 < $_FILES['csv_file']['error'] ) {
			$success = false;
			$this->logs[] = '<p style="color: #f00"><b>Не удалось загрузить файл!</b></p>';
		} else {
			$file = fopen($_FILES['csv_file']['tmp_name'], 'r+');
			if ( $this->fgetcsv($file) ) {
				move_uploaded_file($_FILES['csv_file']['tmp_name'], DIR_UPLOAD . $this->csv_file);
				$success = true;
				$this->logs[] = '<p><b>Файл успешно загружен.</b></p>';
			} else {
				$success = false;
				$this->logs[] = '<p style="color: #f00"><b>Не верный тип файла!</b></p>';
			}
			fclose($file);
		}

		$json = array(
			'success' => $success,
			'logs' => $this->logs
		);

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput( json_encode( $json ) );
	}

	// Считает количество товаров в CSV файле
	private function getProductsCountFromCSV(){
		$file = fopen(DIR_UPLOAD . $this->csv_file, 'r+');

		$csv_sting = 0;
		while ( $csv_row = $this->fgetcsv($file) ) {

			if ( count($csv_row) == count($this->csv_columns) ) {
				$csv_sting++;
			} else {
				break;
			}
		}
		fclose($file);

		return $csv_sting - 1; // Потому, что первая строка - это заголовок
	}
	
	// Колонки CSV файле
	private function getCSVcolumn(){
		$file = fopen(DIR_UPLOAD . $this->csv_file, 'r+');

		while ( $row = $this->fgetcsv($file) ) {
			break;
		}
		
		fclose($file);

		return $row;
	}
	
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	private function productCategoriesEncode($product_categories) {
		$result = array();

		foreach ($product_categories as $category_id) {
			$result[] = $this->model_extension_module_csv_light->getCategoryPath($category_id);
		}
		
		$encode_cat = implode("\n", $result);
		return $encode_cat;
	}
	
	private function productAttributesEncode( $productAttributes ) {
		$attribute_data = array();
		$attribute_name_data = array();
		$attribute_value_data = array();
		foreach ( $productAttributes as $k => $productAttribute ) {
			$attribute_data[] = $productAttribute['name'] . ' : ' . $productAttribute['product_attribute_description'];
		}
		
		$attribute_value_data = implode("\n", $attribute_data);
		return $attribute_value_data;
	}

	private function productOptionsEncode( $productOptions ) {
		$options_data = array();
		foreach ( $productOptions as $productOption ) {
			$option_values_name = array();
			
			foreach ( $productOption['product_option_value'] as $option_value ) {
				$option_values_name[] = $option_value['option_value_description']['name'];
			}
			
			$options_data[] = $productOption['name'] . ' : ' . implode(" # ", $option_values_name);
		}
		
		$options_value_data = implode("\n", $options_data);
		
		return $options_value_data;

	}

	// Получает данные товара из БД
	private function getProductData($product_id) {
		$product_data = array();

		$product_description = $this->model_catalog_product->getProductDescriptions($product_id);
		$product_field_data = $this->model_catalog_product->getProduct($product_id);

		$config_language_id = $this->config->get('config_language_id');

		// product name, model, price
		$product_data['name'] = $product_description[$config_language_id]['name'];
		$product_data['model'] = $product_field_data['model'];
		$product_data['price'] = $product_field_data['price'];
		
		
		// product categories
		$product_categories = array();
		$product_categories = $this->model_catalog_product->getProductCategories($product_id);
		$product_categories_encoded = $this->productCategoriesEncode($product_categories);
		$product_data['categories'] = $product_categories_encoded;
		
		
		// product quantity
		$product_data['quantity'] = $product_field_data['quantity'];
		
		
		// product manufacturer
		$manufacturer = $this->model_catalog_manufacturer->getManufacturer($product_field_data['manufacturer_id']);
		$product_data['manufacturer'] = !empty($manufacturer['name']) ? $manufacturer['name'] : 'NoName' ;
		
		
		// product description
		$product_data['description'] = html_entity_decode($product_field_data['description'], ENT_QUOTES, 'UTF-8');
		
		
		// product_attributes		
		$product_attributes = $this->model_catalog_product->getProductAttributes($product_id);
		$attributes_data = array();
		$attributes_group = array();
		
		foreach ( $product_attributes as $product_attribute ) {
			$attribute_info = $this->model_catalog_attribute->getAttribute($product_attribute['attribute_id']);
			if ($attribute_info) {
				$attributes_data[] = array(
					'attribute_id'                  => $product_attribute['attribute_id'],
					'name'                          => $attribute_info['name'],
					'product_attribute_description' => $product_attribute['product_attribute_description'][$config_language_id]['text'],
				//	'attribute_description'         => $this->model_catalog_attribute->getAttributeDescriptions($product_attribute['attribute_id']),
					'attribute_group_description'   => $this->model_catalog_attribute_group->getAttributeGroupDescriptions($attribute_info['attribute_group_id'])[$config_language_id]['name']
				);
			
				$attributes_group[] = $this->model_catalog_attribute_group->getAttributeGroupDescriptions($attribute_info['attribute_group_id'])[$config_language_id]['name'];
			}
		}

		$attributes_data_encode = $this->productAttributesEncode($attributes_data);
		$product_data['attributes'] = $attributes_data_encode;
		$product_data['attributes_group'] = implode("\n", $attributes_group);

		
		//product options
		$product_options = $this->model_catalog_product->getProductOptions($product_id);
		$options_data = array();
		$options_type = array();
		
		foreach ( $product_options as $product_option ) {
			$product_option_value_data = array();

			if (isset($product_option['product_option_value'])) {
				foreach ($product_option['product_option_value'] as $product_option_value) {
					$product_option_value_data[] = array(
						//'product_option_value_id' => $product_option_value['product_option_value_id'],
						//'option_value_id'         => $product_option_value['option_value_id'],
						'quantity'                => $product_option_value['quantity'],
						'subtract'                => $product_option_value['subtract'],
						'price'                   => $product_option_value['price'],
						'price_prefix'            => $product_option_value['price_prefix'],
						'points'                  => $product_option_value['points'],
						'points_prefix'           => $product_option_value['points_prefix'],
						'weight'                  => $product_option_value['weight'],
						'weight_prefix'           => $product_option_value['weight_prefix'],
						'option_value_description'=> $this->model_catalog_option->getOptionValue($product_option_value['option_value_id'])
					);
				}
			}

			$options_data[] = array(
				//'product_option_id'    => $product_option['product_option_id'],
				'product_option_value' => $product_option_value_data,
				//'option_id'            => $product_option['option_id'],
				'name'                 => $product_option['name'],
				'type'                 => $product_option['type'],
				'value'                => isset($product_option['value']) ? $product_option['value'] : '',
				'required'             => $product_option['required'],
				//'option_description'   => $this->model_catalog_option->getOptionDescriptions($product_option['option_id'])
			);
		
			$options_type[] = $product_option['type'];
		}
		
		$options_data_encode = $this->productOptionsEncode($options_data);
		$product_data['options'] = $options_data_encode;
		
		$product_data['option_type'] = implode("\n", $options_type);

		
		// product images
		$images = array();
		$images[] = $product_field_data['image'];		
		foreach ($this->model_catalog_product->getProductImages($product_id) as $image) {
			$images[] = $image['image'];
		}

		$product_data['images'] = implode(', ', $images);

		return $product_data;
	}
	
	
	// Записываю товар в БД
	private function insertProduct($product_data) {

		$config_language_id = $this->config->get('config_language_id');
		$product_id = $this->model_extension_module_csv_light->getProductIdByName( $product_data['name'] );
		
		$product = array();
		
		$product['sku'] = '';
		$product['upc'] = '';
		$product['ean'] = '';
		$product['jan'] = '';
		$product['isbn'] = '';
		$product['mpn'] = '';
		$product['location'] = '';
		
		if (isset($product_data['quantity'])) {
			if ($product_data['quantity'] == 'true') {
				$product['quantity'] = 100;
			} elseif ($product_data['quantity'] == 'false') {
				$product['quantity'] = 0;
			} elseif ($product_data['quantity'] == '') {
				$product['quantity'] = 98 ;
			}
		} else {
			$product['quantity'] = 99;
		}
		
		$product['stock_status_id'] = 5;
		$product['shipping'] = 1;
		$product['points'] = 0;
		$product['tax_class_id'] = 9;
		$product['date_available'] = date('Y-m-d');
		$product['weight'] = 0;
		$product['weight_class_id'] = 1;
		$product['length'] = 0;
		$product['width'] = 0;
		$product['height'] = 0;
		$product['length_class_id'] = 1;
		$product['subtract'] = 1;
		$product['minimum'] = 1;
		$product['sort_order'] = 0;
		$product['status'] = 1;
		$product['date_added'] = date("Y-m-d H:i:s");
		$product['date_modified'] = date("Y-m-d H:i:s");
		
		$test_html1 = strpos($product_data['description'], '<p>');
		$test_html2 = strpos($product_data['description'], '<br />');
		
		if ($test_html1 === false && $test_html2 === false) {
			$product_description['description'] = nl2br($product_data['description']);
		} else {
			$product_description['description'] = htmlentities($product_data['description'], ENT_QUOTES, 'UTF-8');
		}
		
		$product_description['name'] = $product_data['name'];
		$product_description['meta_title'] = $product_data['name'];
		$product_description['meta_description'] = '';
		$product_description['meta_keyword'] = '';
		$product_description['tag'] = '';		
		$product['product_description'][$config_language_id] = $product_description;

		$product['product_category'] = array();

		if ( is_array($product_data['product_categories']) && $product_data['product_categories'] ) {
			foreach ($product_data['product_categories'] as $k => $product_category) {
				$cat_id = $this->insertCategories($product_category);
				$product['product_category'][$k] = $cat_id;
			}
		}
		
		$product['product_attribute'] = array();
		if ( is_array($product_data['product_attributes']) && $product_data['product_attributes'] ) {
			foreach ( $product_data['product_attributes'] as $k => $product_attribute ) {
				
				$group = isset($product_data['product_attributes_group']) ? $product_data['product_attributes_group'][$k] : 'Характеристики' ;
				
				$attribute_id = $this->insertAttribute($product_attribute, $group);
				
				$product_attributes['attribute_id'] = $attribute_id;
				$product_attributes['product_attribute_description'][$config_language_id] = array(
					'text' => trim($product_attribute['text'])
				);
				$product['product_attribute'][] = $product_attributes;
			}
		}


		$product['product_option'] = array();
		if ( is_array($product_data['product_options']) && $product_data['product_options'] ) {
			foreach ( $product_data['product_options'] as $k => $product_option ) {

				$type = isset($product_data['product_options_type']) ? $product_data['product_options_type'][$k] : 'select' ;
				$option_id = $this->insertOption($product_option, $type);

				$option_values = array();

				if ( is_array( $product_option['values'] ) && $product_option['values'] ) {
					foreach ($product_option['values'] as $value) {
						$option_value['option_value_description'][$config_language_id]['name'] = $value;
						$option_value_id = $this->model_extension_module_csv_light->getOptionValueIdByName($option_value['option_value_description'][$config_language_id]['name']);
						$product_option_value_id = $this->model_extension_module_csv_light->getProductOptionValueId($product_id, $option_id, $option_value_id);
						$option_values[] = array(
							'product_option_value_id' => $product_option_value_id,
							'option_id'       => $option_id,
							'option_value_id' => $option_value_id,
							'quantity'        => 99,
							'subtract'        => 1,
							'price'           => 0,
							'price_prefix'    => '+',
							'points'          => 0,
							'points_prefix'   => '+',
							'weight'          => 0,
							'weight_prefix'   => '+'
						);
					}
				}

				$product['product_option'][] = array(
					'option_id'    => $option_id,
					'product_option_id' => '',
					'product_option_value' => $option_values,
					'required'     => 0,
					'value'        => '',
					'type'         => 'select'
				);
			}
		}

		$product['manufacturer_id'] = 0;
		if ( $product_data['manufacturer'] ) {
			$product['manufacturer_id'] = $this->insertManufacturer($product_data['manufacturer']);
		}


		if (is_array($product_data['product_images']) && $product_data['product_images'] ) {
			//$dir_images = 'catalog/';
			$dir_images = '';
			
			$product['image'] = $dir_images . $product_data['product_images'][0];
			unset($product_data['product_images'][0]);
			
			foreach ( $product_data['product_images'] as $product_image ) {
				$product['product_image'][] = array(
					'image'      => $dir_images . $product_image,
					'sort_order' => 0
				);
			}

		} else {
			$product['product_image'] = array();
			$product['image'] = '';
		}
		

		$product['product_store'] = array('0');

		foreach ($product_data as $key => $value) {
			if ( !isset( $product[$key] ) ) {
				$product[$key] = $value;
			}
		}


		if ($product_id) {
			$this->model_catalog_product->editProduct($product_id, $product);
		} else {
			$this->model_catalog_product->addProduct($product);
		}

	}
	
	
	// Немного фиксит файл. 
	private function validateFile() {
		// C комбинацией 1" (если 1 в конце экранированной строки) проиходит какая то магия и файл ломается. Хз почему.
		// Еще меняем экранированный символ \" на нормальный ""
		$csv_content = file_get_contents( DIR_UPLOAD . $this->csv_file );
		$csv_content = str_replace('1"', '1 "', $csv_content);
		$csv_content = str_replace('\""', '""', $csv_content); 
		$csv_content = str_replace('\"', '""', $csv_content); 
		file_put_contents(DIR_UPLOAD . $this->csv_file, $csv_content);
	}

	// Подкатавливается к работе с загрузкой/выгрузкой CSV файла
	private function init($flag) {
		$this->load->model('extension/module/csv_light');
		
		if ($flag == 'export') {
			$product_id = $this->model_extension_module_csv_light->getProductId();
			$product_data = $this->getProductData($product_id);

			foreach ( $product_data as $column => $value ) {
				$this->csv_columns_ex[] = $column;
			}
		}
		
		if ($flag == 'import') {
			$csv_column = $this->getCSVcolumn();

			foreach ( $csv_column as $column => $value ) {
				$this->csv_columns[] = $value;
			}
		}
		
	}

	// Превращает данные товара в строку CSV
	private function csvEncode($product_data) {
		$csv_row = array();

		foreach ( $product_data as $column => $data ) {
			$csv_row[] = ( is_array($data) ) ? $this->csvArrayEncode($data) : $data;
		}

		return $csv_row;
	}


	// Превращает CSV строку в данные товара
	private function csvDecode($row) {

		$product_data = array();
		$product_data['product_attributes'] = array();
		$product_data['product_options'] = array();
		$product_data['product_categories'] = array();
		$product_data['product_images'] = array();

		foreach ( $row as $key => $cell ) {
			$column = $this->csv_columns[$key];
	
			if ( $column == 'categories' ) {
				foreach ( explode("\n", $cell) as $productCategory ) {
					$data = explode('>', trim($productCategory));
					$product_categories[] = $data;
				}

			} elseif ( $column == 'attributes_group' ) {
				if ($cell) {
					foreach (explode("\n", $cell) as $k => $attribute_group) {
						$attributes_group[] = trim($attribute_group);
					}
					
					$product_data['product_attributes_group'] = $attributes_group;
				}
				
			} elseif ( $column == 'attributes' ) {
				if ($cell) {
					foreach ( explode( "\n",  $cell ) as $k => $attribute ) {
						$data = explode(':', trim($attribute));
						$attributes_data[$k] = array (
							'name' => trim($data[0]),
							'text' => trim($data[1])
						);
					}
					
					$product_data['product_attributes'] = $attributes_data;
				}
				
			} elseif ($column == 'options') {
				if ($cell) {
					foreach (explode("\n", $cell) as $k => $productOptionItem){

						$option_data = array();
						$productOption = array_map('trim', explode(':', trim($productOptionItem)));

						$option_data['name'] = trim($productOption[0]);
						$option_data['values'] = array_map('trim', explode('#', trim($productOption[1])));

						$product_options[] = $option_data;
					}
					
					$product_data['product_options'] = $product_options;
					
				}

			} elseif ($column == 'option_type') {
				foreach (explode("\n", $cell) as $k => $options_type) {
					$options_types[] = trim($options_type);
				}
				
				$product_data['product_options_type'] = $options_types;
				
			} elseif ( $column == 'categories' ) {
				foreach ( explode("\n", $cell) as $productCategory ) {
					$data = explode('>', trim($productCategory));
					$product_categories[] = $data;
				}

			} elseif ( $column == 'images' ) {
				foreach (explode(',', $cell) as $product_image) {
					$product_images[] = trim($product_image);
				}
			} else {
				$product_data[$this->csv_columns[$key]] = trim($cell);
			}
		}
		


		$product_data['product_categories'] = $product_categories;
		$product_data['product_images'] = $product_images;

		return $product_data;
	}

	
	// Записывает категорию в БД перед записью товара
	private function insertCategories($categories) {
		
		$config_language_id = $this->config->get('config_language_id');

		$category_id = false;

		if ( is_array($categories) && $categories) {

			foreach ($categories as $val) {
				
				$category['parent_id'] = ($category_id) ? $category_id : 0;
				$category['top'] = ($category_id) ? 0 : 1;
				$category['column'] = 0;
				$category['sort_order'] = 0;
				$category['status'] = 1;
				$category['date_modified'] = date("Y-m-d H:i:s");
				$category['date_added'] = date("Y-m-d H:i:s");
				$category['category_store'] = array(0);
				
				$category['category_description'][$config_language_id] = array(
					'name' => trim($val),
					'meta_title' => '',
					'meta_h1' => '',
					'meta_description' => '',
					'meta_keyword' => '',
					'description' => ''
				);
				
				$category_ids = $this->model_extension_module_csv_light->getCategoryIdPathByName(trim($val), $category['parent_id']);

				if ($category_ids) {
					$this->model_catalog_category->editCategory($category_ids, $category);
					$category_id = $category_ids;
					$data_return = $category_ids;
				} else {
					$category_id = $this->model_catalog_category->addCategory($category);
					$data_return = $category_id;
				}
			}
			
			return $data_return;
		}
	}

	// Записывает атрибут  в БД перед записью товара
	private function insertAttribute( $product_attribute, $group ) {
		$config_language_id = $this->config->get('config_language_id');
		
		
		$attribute_group['attribute_group_description'][$config_language_id]['name'] = $group;
		$attribute_group['sort_order'] = 0;

		$attribute_group_name = $attribute_group['attribute_group_description'][$config_language_id]['name'];
		$attribute_group_id = $this->model_extension_module_csv_light->getAttributeGroupIdByName($attribute_group_name);

		
		if ( $attribute_group_id ) {
			$this->model_catalog_attribute_group->editAttributeGroup($attribute_group_id, $attribute_group);
		} else {
			$attribute_group_id = $this->model_catalog_attribute_group->addAttributeGroup($attribute_group);
		}
		

		$attribute['attribute_description'][$config_language_id] = array(
			'name' => trim($product_attribute['name']),
		);

		 $attribute['attribute_group_id'] = $attribute_group_id;
		 $attribute['sort_order'] = 0;

		$attribute_name = $attribute['attribute_description'][$config_language_id]['name'];

		$attribute_id = $this->model_extension_module_csv_light->getAttributeIdByName($attribute_name);

		if ( $attribute_id ) {
			$this->model_catalog_attribute->editAttribute($attribute_id, $attribute);
		} else {
			$attribute_id = $this->model_catalog_attribute->addAttribute($attribute);
		}
		return $attribute_id;
	}

	// Записывает опцию в БД перез записью товара
	private function insertOption($product_option, $type) {
		$config_language_id = $this->config->get('config_language_id');
		
		$option_description[$config_language_id]['name'] = $product_option['name'];
		
		$option_data = array(
			'option_description' => $option_description,
			'sort_order'         => 0,
			'type'               => $type,
			'option_value'       => ( is_array($product_option['values']) && $product_option['values'] ) ? array() : null
		);

		$option_name = $option_data['option_description'][$config_language_id]['name'];

		$option_id = $this->model_extension_module_csv_light->getOptionIdByName($option_name);

		if ( isset( $option_data['option_value'] ) ) {
			foreach ( $product_option['values'] as $product_option_value ) {
				$option_value_description[$config_language_id]['name'] = $product_option_value;
				$option_value_name = $option_value_description[$config_language_id]['name'];
				$option_data['option_value'][] = array(
					'option_value_id'          => $this->model_extension_module_csv_light->getOptionValueIdByName($option_value_name),
					'option_value_description' => $option_value_description,
					'image'                    => '',
					'sort_order'               => 0
				);
			}
		}

		if ( $option_id ) {
			$this->model_extension_module_csv_light->editOption($option_id, $option_data);
		} else {
			$option_id = $this->model_catalog_option->addOption($option_data);
		}
		return $option_id;
	}

	// Записывает производителя  в БД перед записью товара
	private function insertManufacturer($name, $manufacturer_description = '') {
		
		$manufacturer_id = $this->model_extension_module_csv_light->getManufacturerIdByName($name);
		$product_manufacturer['name'] = $name;
		$product_manufacturer['sort_order'] = 0;		
		$product_manufacturer['manufacturer_store'] = array(0);
		
		if ( $manufacturer_id ) {
			$this->model_catalog_manufacturer->editManufacturer($manufacturer_id, $product_manufacturer);
		} else {
			$product_manufacturer['name'] = $name;
			$product_manufacturer['sort_order'] = 0;
			$product_manufacturer['manufacturer_store'] = array(0);
			
			$manufacturer_id = $this->model_catalog_manufacturer->addManufacturer($product_manufacturer);
		}
		return $manufacturer_id;
	}


	
	// Дебаг
	private function debug( $data ){
		exit( '<pre>'.print_r( $data, true ).'</pre>' );
	}

	private function  fputcsv($fp, $csv_arr, $delimiter = ';', $enclosure = '"') {

		return fputcsv($fp, $csv_arr, $delimiter, $enclosure);
		// проверим, что на  входе массив
		if (!is_array($csv_arr))
		{
			return(false);
		}
		// обойдем все  элемены массива
		for ($i = 0, $n = count($csv_arr); $i < $n;  $i ++)
		{
			// если это не  число
			if (!is_numeric($csv_arr[$i]))
			{
				// вставим символ  ограничения и продублируем его в теле элемента
				$csv_arr[$i] =  $enclosure.str_replace($enclosure, $enclosure.$enclosure,  $csv_arr[$i]).$enclosure;
			}
			// если  разделитель - точка, то числа тоже экранируем
			if (($delimiter == '.') && (is_numeric($csv_arr[$i])))
			{
				$csv_arr[$i] =  $enclosure.$csv_arr[$i].$enclosure;
			}
		}
		// сольем массив в строку, соединив разделителем
		$str = implode($delimiter,  $csv_arr)."\n";
		fwrite($fp, $str);
		// возвращаем  количество записанных данных
		return strlen($str);
	}

	private function  fgetcsv($fp, $length=0, $delimiter = ';', $enclosure = '"',  $escape=true) {
		return fgetcsv($fp, $length, $delimiter,  $enclosure, $escape);
	}
}