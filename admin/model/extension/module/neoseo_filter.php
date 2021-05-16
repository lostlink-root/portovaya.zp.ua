<?php

require_once( DIR_SYSTEM . "/engine/neoseo_model.php");

class ModelExtensionModuleNeoSeoFilter extends NeoSeoModel
{

	public function __construct($registry)
	{
		parent::__construct($registry);
		$this->_module_code = "neoseo_filter";
		$this->_moduleSysName = "neoseo_filter";
		$this->_logFile = $this->_moduleSysName() . ".log";
		$this->debug = $this->config->get($this->_moduleSysName() . "_debug");

		$this->params = array(
			'module_key' => '',
			"status" => 1,
			"name" => "",
			"title" => "",
			"manufacturer_title" => "",
			"manufacturer" => "",
			"use_price" => 0,
			"ignore_seo_url_filter" => 0,
			"template" => "",
			"debug" => 0,
			'attribute_values_sort_order_direction' => 'asc',
		);

		$this->options_levels = array(
			'module_key' => 0,
			"status" => 0,
			"name" => 0,
			"title" => 0,
			"manufacturer_title" => 0,
			"manufacturer" => 0,
			"use_price" => 0,
			"template" => 0,
			"debug" => 0,
			'attribute_values_sort_order_direction' => 1,
		);

		$this->addAccessLevels();
	}

	public function install()
	{
		if (!$this->isAccesible(__FUNCTION__, true)) {
			return "";
		}

		// Значения параметров по умолчанию
		$this->initParams($this->params);

		$this->createTables();

		// Недостающие права
		$this->load->model('user/user_group');
		$this->addPermission($this->user->getGroupId(), 'access', 'catalog/' . $this->_moduleSysName());
		$this->addPermission($this->user->getGroupId(), 'modify', 'catalog/' . $this->_moduleSysName());
		$this->addPermission($this->user->getGroupId(), 'access', 'catalog/' . $this->_moduleSysName() . '_pages');
		$this->addPermission($this->user->getGroupId(), 'modify', 'catalog/' . $this->_moduleSysName() . '_pages');

		//Устанавливаем компоненты модуля
		$this->installComponent($this->_moduleSysName() . '_settings');
		$this->installComponent($this->_moduleSysName() . '_tag');

		return TRUE;
	}

	public function upgrade()
	{
		if (!$this->isAccesible(__FUNCTION__, true)) {
			return "";
		}

		// Добавляем недостающие новые параметры
		$this->initParams($this->params);

		$this->createTables();
		$sql = "SHOW COLUMNS FROM `" . DB_PREFIX . "filter_module_cache` LIKE 'language_id';";
		$query = $this->db->query($sql);
		if (!$query->num_rows) {
			$sql = "ALTER TABLE `" . DB_PREFIX . "filter_module_cache`  ADD COLUMN `language_id` int(11) NOT NULL  AFTER `quantity`;";
			$this->db->query($sql);
		}

		$sql = "SHOW COLUMNS FROM `" . DB_PREFIX . "filter_option_description` LIKE 'keyword';";
		$query = $this->db->query($sql);
		if (!$query->num_rows) {
			$sql = "ALTER TABLE `" . DB_PREFIX . "filter_option_description`  ADD COLUMN `keyword` varchar(255) NOT NULL;";
			$this->db->query($sql);
		}

		$sql = "SHOW COLUMNS FROM `" . DB_PREFIX . "filter_option_value_description` LIKE 'keyword';";
		$query = $this->db->query($sql);
		if (!$query->num_rows) {
			$sql = "ALTER TABLE `" . DB_PREFIX . "filter_option_value_description`  ADD COLUMN `keyword` varchar(255) NOT NULL;";
			$this->db->query($sql);
		}

		$sql = "SHOW COLUMNS FROM `" . DB_PREFIX . "filter_page_description` LIKE 'keyword';";
		$query = $this->db->query($sql);
		if (!$query->num_rows) {
			$sql = "ALTER TABLE `" . DB_PREFIX . "filter_page_description`  ADD COLUMN `keyword` varchar(255) NOT NULL;";
			$this->db->query($sql);
		}

		$sql = "SHOW COLUMNS FROM `" . DB_PREFIX . "filter_page_description` LIKE 'name';";
		$query = $this->db->query($sql);
		if (!$query->num_rows) {
			$sql = "ALTER TABLE `" . DB_PREFIX . "filter_page_description`  ADD COLUMN `name` varchar(255) NOT NULL;";
			$this->db->query($sql);
		}

		$sql = "SHOW COLUMNS FROM `" . DB_PREFIX . "filter_page_description` LIKE 'tag_name';";
		$query = $this->db->query($sql);
		if (!$query->num_rows) {
			$sql = "ALTER TABLE `" . DB_PREFIX . "filter_page_description`  ADD COLUMN `tag_name` varchar(255) NOT NULL;";
			$this->db->query($sql);
		}

		$sql = "SHOW COLUMNS FROM `" . DB_PREFIX . "filter_page` LIKE 'is_tag';";
		$query = $this->db->query($sql);
		if (!$query->num_rows) {
			$sql = "ALTER TABLE `" . DB_PREFIX . "filter_page`  ADD COLUMN `is_tag` int(11) NOT NULL;";
			$this->db->query($sql);
		}

		$sql = "SHOW COLUMNS FROM `" . DB_PREFIX . "filter_page` LIKE 'tags';";
		$query = $this->db->query($sql);
		if (!$query->num_rows) {
			$sql = "ALTER TABLE `" . DB_PREFIX . "filter_page`  ADD COLUMN `tags` text NOT NULL;";
			$this->db->query($sql);
		}

		$sql = "SHOW COLUMNS FROM `" . DB_PREFIX . "filter_option` LIKE 'after_manufacturer';";
		$query = $this->db->query($sql);
		if (!$query->num_rows) {
			$sql = "ALTER TABLE `" . DB_PREFIX . "filter_option`  ADD COLUMN `after_manufacturer` int(11) NOT NULL;";
			$this->db->query($sql);
		}

		$sql = "SHOW COLUMNS FROM `" . DB_PREFIX . "filter_page` LIKE 'use_direct_link';";
		$query = $this->db->query($sql);
		if (!$query->num_rows) {
			$sql = "ALTER TABLE `" . DB_PREFIX . "filter_page`  ADD COLUMN `use_direct_link` int(11) NOT NULL DEFAULT '0';";
			$this->db->query($sql);
		}

		$sql = "SHOW COLUMNS FROM `" . DB_PREFIX . "filter_option` LIKE 'sort_order_direction';";
		$query = $this->db->query($sql);
		if (!$query->num_rows) {
			$sql = "ALTER TABLE `" . DB_PREFIX . "filter_option`  ADD COLUMN `sort_order_direction` int(3) NOT NULL  DEFAULT '0';";
			$this->db->query($sql);
		}

		$sql = "SHOW COLUMNS FROM `" . DB_PREFIX . "filter_page` LIKE 'use_end_slash';";
		$query = $this->db->query($sql);
		if (!$query->num_rows) {
			$sql = "ALTER TABLE `" . DB_PREFIX . "filter_page`  ADD COLUMN `use_end_slash` int(11) NOT NULL  DEFAULT '0';";
			$this->db->query($sql);
		}

		return TRUE;
	}

	public function uninstall()
	{
		if (!$this->isAccesible(__FUNCTION__, true)) {
			return "";
		}

		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "filter_option`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "filter_option_description`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "filter_option_value`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "filter_option_value_description`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "filter_option_value_to_product`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "filter_option_to_category`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "filter_page`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "filter_page_description`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "filter_cache`");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE `code` = '" . $this->_moduleSysName() . "'");

		$this->load->model('user/user_group');
		$this->model_user_user_group->removePermission($this->user->getGroupId(), 'access', 'catalog/' . $this->_moduleSysName());
		$this->model_user_user_group->removePermission($this->user->getGroupId(), 'modify', 'catalog/' . $this->_moduleSysName());
		$this->model_user_user_group->removePermission($this->user->getGroupId(), 'access', 'catalog/' . $this->_moduleSysName() . '_pages');
		$this->model_user_user_group->removePermission($this->user->getGroupId(), 'modify', 'catalog/' . $this->_moduleSysName() . '_pages');

		//Удаляем компоненты модуля
		$this->uninstallComponent($this->_moduleSysName() . '_settings');
		$this->uninstallComponent($this->_moduleSysName() . '_tag');

		return TRUE;
	}

	private function createTables()
	{
		if (!$this->isAccesible(__FUNCTION__, true)) {
			return "";
		}

		//Опции фильтра
		$sql = "
		CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "filter_option` (
			`option_id` int(11) NOT NULL AUTO_INCREMENT,
			`keyword` varchar(255) NOT NULL,
			`sort_order` int(11) NOT NULL DEFAULT '0',
			`type` varchar(255) NOT NULL,
			`status` int(11) NOT NULL,
			`style`  varchar(255) NOT NULL,
			`open` int(11) NOT NULL,
			`after_manufacturer` int(11) NOT NULL,
			`sort_order_direction` int(3) NOT NULL,
			PRIMARY KEY (`option_id`)
		) DEFAULT CHARSET=utf8;
		";
		$this->db->query($sql);

		$sql = "
		CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "filter_option_description` (
			`option_id` int(11) NOT NULL AUTO_INCREMENT,
			`language_id` int(11) NOT NULL,
			`name` varchar(255) NOT NULL,
			`keyword` varchar(255) NOT NULL,
			PRIMARY KEY (`option_id`,`language_id`)
		) DEFAULT CHARSET=utf8;
		";
		$this->db->query($sql);

		$sql = "
		CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "filter_option_to_category` (
			`option_id` int(11) NOT NULL AUTO_INCREMENT,
			`category_id` int(11) NOT NULL,
			PRIMARY KEY (`option_id`,`category_id`)
		) DEFAULT CHARSET=utf8;
		";
		$this->db->query($sql);

		$sql = "
		CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "filter_option_to_manufacturer` (
			`option_id` int(11) NOT NULL AUTO_INCREMENT,
			`manufacturer_id` int(11) NOT NULL,
			PRIMARY KEY (`option_id`,`manufacturer_id`)
		) DEFAULT CHARSET=utf8;
		";
		$this->db->query($sql);

		//Значения опций фильтра
		$sql = "
		CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "filter_option_value` (
			`option_value_id` int(11) NOT NULL AUTO_INCREMENT,
			`option_id` int(11) NOT NULL,
			`keyword` varchar(255) NOT NULL,
			`sort_order` int(11) NOT NULL DEFAULT '0',
			`color` varchar(255) NOT NULL,
			`image` varchar(255) NOT NULL,
			`position`  varchar(255) NOT NULL,
			PRIMARY KEY (`option_value_id`)
		) DEFAULT CHARSET=utf8; 
		";
		$this->db->query($sql);

		$sql = "
		CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "filter_option_value_description` (
			`option_value_id` int(11) NOT NULL AUTO_INCREMENT,
			`option_id` int(11) NOT NULL,
			`language_id` int(11) NOT NULL,
			`name` varchar(255) NOT NULL,
			`keyword` varchar(255) NOT NULL,
			PRIMARY KEY (option_value_id, `option_id`, `language_id`) 
		) DEFAULT CHARSET=utf8;
		";
		$this->db->query($sql);

		$sql = "
		CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "filter_option_value_to_product` (
			`product_id` int(11) NOT NULL,
			`option_id` int(11) NOT NULL,
			`option_value_id` int(11) NOT NULL,
			KEY (`product_id`,`option_id`,`option_value_id`),
			KEY (`product_id`,`option_value_id`),
			KEY (`option_value_id`,`product_id`)
		) DEFAULT CHARSET=utf8;
		";
		$this->db->query($sql);

		//Посадочные страницы
		$sql = "
		CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "filter_page` (
			`page_id` int(11) NOT NULL AUTO_INCREMENT,
			`category_id` int(11) NOT NULL,
			`keyword` varchar(255) NOT NULL,
			`status` int(11) NOT NULL,
			`options` varchar(255) NOT NULL,
			`is_tag` int(11) NOT NULL,
			`tags` text NOT NULL,
			`use_direct_link` int(11) NOT NULL DEFAULT '0',
			PRIMARY KEY (`page_id`)
		) DEFAULT CHARSET=utf8;
		";
		$this->db->query($sql);

		$sql = "
		CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "filter_page_description` (
			`page_id` int(11) NOT NULL AUTO_INCREMENT,
			`language_id` int(11) NOT NULL,
			`h1` varchar(255) NOT NULL,
			`description` TEXT NOT NULL,
			`title` varchar(255) NOT NULL,
			`meta_keywords` varchar(255) NOT NULL,
			`meta_description` varchar(255) NOT NULL,
			`keyword` varchar(255) NOT NULL,
			`tag_name` varchar(255) NOT NULL,
			PRIMARY KEY (`page_id`,`language_id`)
		) DEFAULT CHARSET=utf8;
		";
		$this->db->query($sql);

		// Закешированные данные по опциям фильтра
		$sql = "
		CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "filter_cache` (
			`cache_id` int(11) NOT NULL AUTO_INCREMENT,
			`category_id` int(11) NOT NULL,
			`options` text NULL,
			`products` text NULL,
			`quantity` int(11) NOT NULL,
			PRIMARY KEY (`cache_id`),
			KEY `options` (`options`(255)),
			KEY `products` (`options`(1024))
		) DEFAULT CHARSET=utf8;
		";
		$this->db->query($sql);

		// Закешированные данные по категориям
		$sql = "
		CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "filter_category_cache` (
			`filter_category_id` int(11) NOT NULL AUTO_INCREMENT,
			`category_id` int(11) NOT NULL,
			`min_price` decimal(15,4) NULL,
			`max_price` decimal(15,4) NULL,			
			PRIMARY KEY (`filter_category_id`),
			KEY `category_id` (`category_id`)
		) DEFAULT CHARSET=utf8;
		";
		$this->db->query($sql);

		// Закешированные данные по модулю фильтра в категории
		$sql = "
		CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "filter_module_cache` (
			`cache_id` int(11) NOT NULL AUTO_INCREMENT,
			`category_id` int(11) NOT NULL,
			`selected` text NULL,
			`quantity` text NULL,
			`language_id` int(11) NOT NULL,			
			PRIMARY KEY (`cache_id`),
			KEY `selected` (category_id,`selected`(255))			
		) DEFAULT CHARSET=utf8;
		";
		$this->db->query($sql);

		//параметр сопоставления номера скдада => опции фильтра
		$query = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "warehouse'");
		if ($query->num_rows) {
			$sql = "SHOW COLUMNS FROM `" . DB_PREFIX . "warehouse` LIKE 'option_value_id';";
			$query = $this->db->query($sql);
			if (!$query->num_rows) {
				$sql = "ALTER TABLE `" . DB_PREFIX . "warehouse`  ADD COLUMN `option_value_id` int(11) NOT NULL DEFAULT '0';";
				$this->db->query($sql);
			}
		}
	}

	public function onWarehouse()
	{
		if (!$this->isAccesible(__FUNCTION__, true)) {
			return "";
		}

		$query = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "warehouse'");
		if ($query->num_rows) {
			return false;
		}
		return true;
	}

	private function installComponent($extension)
	{
		$this->load->model('setting/extension');

		$this->model_setting_extension->install('module', $extension);

		$this->load->controller('extension/module/' . $extension . '/install');

		$this->addPermission($this->user->getGroupId(), 'access', 'extension/module/' . $extension);
		$this->addPermission($this->user->getGroupId(), 'modify', 'extension/module/' . $extension);
	}

	private function uninstallComponent($extension)
	{
		$this->load->model('setting/extension');
		$this->load->model('setting/module');
		$this->load->model('user/user_group');

		$this->model_setting_extension->uninstall('module', $extension);
		$this->model_setting_module->deleteModulesByCode($extension);

		$this->load->controller('extension/module/' . $extension . '/uninstall');

		$this->model_user_user_group->removePermission($this->user->getGroupId(), 'access', 'extension/module/' . $extension);
		$this->model_user_user_group->removePermission($this->user->getGroupId(), 'modify', 'extension/module/' . $extension);
	}

	private function addAccessLevels()
	{
		$this->setAccessLevels(
				[
					'index' => 0,
					'install' => 0,
					'uninstall' => 0,
					'upgrade' => 0,
					'createTables' => 0,
					'onWarehouse' => 0,
					'installComponent' => 0,
					'uninstallComponent' => 0,
				]
		);
	}

}

?>