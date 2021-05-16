<?php

require_once( DIR_SYSTEM . "/engine/neoseo_model.php");

class ModelExtensionModuleNeoSeoFilterSettings extends NeoSeoModel
{

	public function __construct($registry)
	{
		parent::__construct($registry);
		$this->_module_code = "neoseo_filter";
		$this->_moduleSysName = "neoseo_filter";
		$this->_modulePostfix = "_settings"; // Постфикс для разных типов модуля, поэтому переходим на испольлзование $this->_moduleSysName()
		$this->_logFile = $this->_moduleSysName . ".log";
		$this->debug = $this->config->get($this->_moduleSysName . "_debug");

		// Значения параметров по умолчанию
		$this->load->model('localisation/language');
		$this->language->load('extension/module/' . $this->_moduleSysName());

		$languages = array();
		foreach ($this->model_localisation_language->getLanguages() as $language) {
			$languages[$language['language_id']] = $language['name'];
		}
		$defaultManUrl = array();
		foreach ($languages as $language_id => $name) {
			$defaultManUrl[$language_id] = $this->language->get('text_default_manufacturer_url');
		}

		$defaultPriceUrl = array();
		foreach ($languages as $language_id => $name) {
			$defaultPriceUrl[$language_id] = $this->language->get('text_default_price_url');
		}

		$this->params = array(
			"settings_status" => 1,
			'use_cache' => 1,
			'not_flush_filter_module_cache' => 0,
			'use_discount' => 0,
			'use_special' => 0,
			'show_attributes' => 0,
			'add_filters_to_h1' => 1,
			'attributes_group' => 0,
			'manufacturer_sort_order' => 'default',
			'manufacturer_url' => $defaultManUrl,
			'price_url' => $defaultPriceUrl,
			'option_for_warehouse' => 0,
			'import_filter_option' => 1,
			'import_product_field' => 'model',
		);

		$this->options_levels = array(
			"settings_status" => 0,
			'use_cache' => 1,
			'not_flush_filter_module_cache' => 1,
			'use_discount' => 1,
			'use_special' => 1,
			'show_attributes' => 1,
			'add_filters_to_h1' => 1,
			'attributes_group' => 1,
			'manufacturer_sort_order' => 1,
			'manufacturer_url' => 1,
			'price_url' => 1,
			'option_for_warehouse' => 1,
			'import_filter_option' => 1,
			'import_product_field' => 1,
			"cron_copy_attributes" => 1,
			"cron_copy_options" => 1,
			"cron_copy_product_data" => 1,
			"cron_copy_from_ocfilter" => 1,
			"cron_clear_cache" => 1,
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

		return TRUE;
	}

	public function upgrade()
	{
		if (!$this->isAccesible(__FUNCTION__, true)) {
			return "";
		}

		// Добавляем недостающие новые параметры
		$this->initParams($this->params);

		return TRUE;
	}

	public function uninstall()
	{
		if (!$this->isAccesible(__FUNCTION__, true)) {
			return "";
		}

		$this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE `code` = '" . $this->_moduleSysName()."'");

		return TRUE;
	}

	private function addAccessLevels()
	{
		$this->setAccessLevels(
				[
					'index' => 0,
					'install' => 0,
					'upgrade' => 0,
					'uninstall' => 0,
				]
		);
	}

}

?>