<?php

require_once( DIR_SYSTEM . "/engine/neoseo_model.php");

class ModelExtensionShippingNeoseoNovaposhta extends NeoSeoModel
{

	public function __construct($registry)
	{
		parent::__construct($registry);
		$this->_moduleSysName = 'neoseo_novaposhta';
		$this->_modulePostfix = ""; // Постфикс для разных типов модуля, поэтому переходим на испольлзование $this->_moduleSysName()()
		$this->_logFile = $this->_moduleSysName() . '.log';
		$this->debug = $this->config->get($this->_moduleSysName() . '_debug') == 1;
		$this->language->load('extension/' . $this->_route . '/' . $this->_moduleSysName);
		$this->load->model('localisation/language');
		$text_courier_delivery_description = array();
		$text_warehouse_description = array();
		foreach ($this->model_localisation_language->getLanguages() as $language){
			$text_courier_delivery_description[$language['language_id']]= $this->language->get('text_courier_shipping');
			$text_warehouse_description[$language['language_id']]= $this->language->get('text_warehouse_shipping');
		}
		$this->params = array(
			'status' => 0,
			'debug' => 0,
			'api_key' => "",
			'api_url' => "https://api.novaposhta.ua/v2.0/",
			'min_weight' => 0.5,
			'sort_order' => 0,
			'min_width' => 0.1,
			'min_height' => 0.1,
			'min_depth' => 0.1,
			'cod_status' => 1,
			'cod_order_status_id' => '',
            'city_list_lang' => '',
            'order_status' => array(),
			'courier_delivery_status' => 0,
			'courier_delivery_name' => $text_courier_delivery_description,
			'courier_delivery_send_type' => '',
			'courier_delivery_pay_type' => '',
			'warehouse_delivery_status' => 0,
			'warehouse_delivery_name' => $text_warehouse_description,
			'warehouse_delivery_send_type' => '',
			'warehouse_delivery_pay_type' => '',
			'sender_city' => '',
			'sender_warehouse' => '',
			'package_type' => '',
			'use_custom_cost' => 0,
			'custom_costs' => array(),
			'add_tax' => 0,
			'np_sender_ref' => '',
			'np_sender_contact' => '',
			'np_sender_phone' => '',
			'payer' => '',
			'description' => $this->language->get('text_default_description'),
			'weight_class_id' => '',
			'length_class_id' => '',
			'delivery_day' => 0,
			'custom_cost_type' => 0,
			'custom_costs_total' => array(),
			'before' => 1,
		);
	}

	public function install()
	{
		// Значения параметров по умолчанию
		$this->initParams($this->params);

		// Создаем новые и недостающие таблицы в актуальной структуре
		$this->installTables();

		// Добавляем права на нестандартные контроллеры, если они используются
		$this->load->model('user/user_group');
		$this->addPermission($this->user->getGroupId(), 'access', 'sale/' . $this->_moduleSysName());
		$this->addPermission($this->user->getGroupId(), 'modify', 'sale/' . $this->_moduleSysName());

		return TRUE;
	}

	public function installTables(){
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "novaposhta_zones` (
			`ref` varchar(36) NOT NULL,
			`zone_id` int(11) NOT NULL AUTO_INCREMENT,
			`areascenter` varchar(36) NOT NULL,
			`description` varchar(255) NOT NULL,
			PRIMARY KEY (`ref`),
			UNIQUE KEY `zone_id` (`zone_id`) USING BTREE
		) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;");

		$this->db->query("CREATE TABLE IF NOT EXISTS`" . DB_PREFIX . "novaposhta_cities` ( 
			`ref` VARCHAR(36) NOT NULL , 
			`description` VARCHAR(100) NOT NULL , 
			`descriptionru` VARCHAR(100) NOT NULL , 
			`area` VARCHAR(36) NOT NULL , 
			`settlementtype` VARCHAR(36) NOT NULL , 
			`cityid` INT NOT NULL , 
			`settlementtypedescription` VARCHAR(100) NOT NULL , 
			`settlementtypedescriptionru` VARCHAR(100) NOT NULL , 
			PRIMARY KEY (`ref`), 
			INDEX (`cityid`),
			INDEX (`description`),
			INDEX (`descriptionru`)
		) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;");

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "novaposhta_warehouses` (
			`sitekey` int(11) NOT NULL,
			`description` varchar(100) NOT NULL,
			`descriptionru` varchar(100) NOT NULL,
			`shortaddress` varchar(100) NOT NULL,
			`shortaddressru` varchar(100) NOT NULL,
			`phone` varchar(100) NOT NULL,
			`typeofwarehouse` varchar(36) NOT NULL,
			`ref` varchar(36) NOT NULL,
			`number` int(11) NOT NULL,
			`cityref` varchar(36) NOT NULL,
			`citydescription` varchar(100) NOT NULL,
			`citydescriptionru` varchar(100) NOT NULL,
			`longitude` varchar(25) NOT NULL,
			`latitude` varchar(25) NOT NULL,
			PRIMARY KEY (`sitekey`),
			KEY `cityref` (`cityref`),
			INDEX(`number`)
		) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;");

		$this->db->query("CREATE TABLE IF NOT EXISTS`" . DB_PREFIX . "novaposhta_update_status` (
			`id` varchar(10) NOT NULL,
			`up_date` datetime NOT NULL,
			`count` int(11) NOT NULL,
			PRIMARY KEY (`id`)
		) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;");

		$this->db->query("CREATE TABLE  IF NOT EXISTS`" . DB_PREFIX . "novaposhta_payer_type` ( 
			`ref` VARCHAR(36) NOT NULL , 
			`description` VARCHAR(36) NOT NULL , 
			PRIMARY KEY (`ref`)) 
		ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;");

		$this->db->query("CREATE TABLE  IF NOT EXISTS`" . DB_PREFIX . "novaposhta_payment_type` ( 
			`ref` VARCHAR(36) NOT NULL , 
			`description` VARCHAR(36) NOT NULL , 
			PRIMARY KEY (`ref`)) 
		ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;");

		$this->db->query("CREATE TABLE  IF NOT EXISTS`" . DB_PREFIX . "novaposhta_cargo_type` ( 
			`ref` VARCHAR(36) NOT NULL , 
			`description` VARCHAR(36) NOT NULL , 
			PRIMARY KEY (`ref`)) 
		ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;");

		$this->db->query("CREATE TABLE  IF NOT EXISTS`" . DB_PREFIX . "novaposhta_service_type` ( 
			`ref` VARCHAR(36) NOT NULL , 
			`description` VARCHAR(36) NOT NULL , 
			PRIMARY KEY (`ref`)) 
		ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;");

		$this->db->query("CREATE TABLE IF NOT EXISTS`" . DB_PREFIX . "novaposhta_documents` ( 
			`order_id` INT NOT NULL , 
			`ref` VARCHAR(36) NOT NULL , 
			`ttn` VARCHAR(36) NOT NULL , 
			`json_data` LONGTEXT NOT NULL , 
			`cost` VARCHAR(35) NOT NULL,
			`estimated_delivery_date` VARCHAR(35) NOT NULL,
			`status` VARCHAR(128) NOT NULL , 
			`status_int` INT NOT NULL,
			PRIMARY KEY (`order_id`), 
			UNIQUE (`ref`), 
			INDEX (`status_int`)
			 ) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;");
		//$this->db->query("");
	}

	public function upgrade(){

		// Добавляем недостающие новые параметры
		$this->initParams($this->params);

		// Создаем недостающие таблицы в актуальной структуре
		$this->installTables();


	}

	public function uninstall()
	{
		// Удаляем таблицы модуля
		$this->db->query("DROP TABLE IF EXISTS " . DB_PREFIX . "novaposhta_zones");
		$this->db->query("DROP TABLE IF EXISTS " . DB_PREFIX . "novaposhta_cities");
		$this->db->query("DROP TABLE IF EXISTS " . DB_PREFIX . "novaposhta_warehouses");
		$this->db->query("DROP TABLE IF EXISTS " . DB_PREFIX . "novaposhta_update_status");
		$this->db->query("DROP TABLE IF EXISTS " . DB_PREFIX . "novaposhta_payer_type");
		$this->db->query("DROP TABLE IF EXISTS " . DB_PREFIX . "novaposhta_payment_type");
		$this->db->query("DROP TABLE IF EXISTS " . DB_PREFIX . "novaposhta_cargo_type");
		$this->db->query("DROP TABLE IF EXISTS " . DB_PREFIX . "novaposhta_service_type");
		$this->db->query("DROP TABLE IF EXISTS " . DB_PREFIX . "novaposhta_documents");
		//$this->db->query("DROP TABLE IF EXISTS " . DB_PREFIX . "neoseo_module");

		// Удаляем права на нестандартные контроллеры, если они используются
		$this->load->model('user/user_group');
		$this->model_user_user_group->removePermission($this->user->getGroupId(), 'access', 'sale/' . $this->_moduleSysName());
		$this->model_user_user_group->removePermission($this->user->getGroupId(), 'modify', 'sale/' . $this->_moduleSysName());
		return TRUE;
	}

}

