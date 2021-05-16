<?php

require_once(DIR_SYSTEM . "/engine/neoseo_controller.php");
require_once(DIR_SYSTEM . '/engine/neoseo_view.php');

class ControllerExtensionModuleNeoSeoFilterSettings extends NeoSeoController
{

	private $error = array();

	public function __construct($registry)
	{
		parent::__construct($registry);
		$this->_module_code = "neoseo_filter";
		$this->_moduleSysName = "neoseo_filter";
		$this->_modulePostfix = "_settings"; // Постфикс для разных типов модуля, поэтому переходим на испольлзование $this->_moduleSysName()
		$this->_logFile = $this->_moduleSysName . ".log";
		$this->debug = $this->config->get($this->_moduleSysName . "_debug");
	}

	public function index()
	{
		$this->upgrade();

		$data = $this->language->load('extension/module/' . $this->_moduleSysName() );

		$this->document->setTitle($this->language->get('heading_title_raw'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && ($this->validate())) {

			if (!isset($this->request->post[$this->_moduleSysName() . '_module_key'])) {
				$module_key = $this->config->get($this->_moduleSysName() . "_module_key");
				$this->request->post[$this->_moduleSysName() . '_module_key'] = $module_key;
			}
			$this->model_setting_setting->editSetting($this->_moduleSysName(), $this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->load->model('extension/module/' . $this->_moduleSysName());
			$this->model_extension_module_neoseo_filter_settings->setModuleStatus($this->request->post[$this->_moduleSysName() . "_settings_status"]);

			if ($this->request->post['action'] == "save") {
				$this->response->redirect($this->url->link('extension/' . $this->_route . '/' . $this->_moduleSysName() , 'user_token=' . $this->session->data['user_token'], true));
			} else {
				$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], true));
			}
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else if (isset($this->session->data['error_warning'])) {
			$data['error_warning'] = $this->session->data['error_warning'];
			unset($this->session->data['error_warning']);
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		}
		$data = $this->initBreadcrumbs(array(
			array("marketplace/extension", "text_module"),
			array("extension/module/" . $this->_moduleSysName() , "heading_title_raw")
				), $data);

		$data = $this->initButtons($data);
		$data['save'] = $this->url->link('extension/module/' . $this->_moduleSysName() , 'user_token=' . $this->session->data['user_token'], 'SSL');
		$data['clear_cache'] = $this->url->link('extension/module/' . $this->_moduleSysName()  . '/clear_cache', 'user_token=' . $this->session->data['user_token'], 'SSL');
		$data['update_warehouse'] = $this->url->link('extension/module/' . $this->_moduleSysName()  . '/update_warehouse', 'user_token=' . $this->session->data['user_token'], 'SSL');
		$data['save_and_close'] = $this->url->link('extension/module/' . $this->_moduleSysName() , 'user_token=' . $this->session->data['user_token'] . "&close=1", 'SSL');
		$data['clear'] = $this->url->link('extension/module/' . $this->_moduleSysName()  . '/clear', 'user_token=' . $this->session->data['user_token'], 'SSL');
		$data['copy_attributes'] = $this->url->link('extension/module/' . $this->_moduleSysName()  . '/copy_attributes', 'user_token=' . $this->session->data['user_token'], 'SSL');
		$data['copy_options'] = $this->url->link('extension/module/' . $this->_moduleSysName()  . '/copy_options', 'user_token=' . $this->session->data['user_token'], 'SSL');
		$data['clear_filter_options'] = $this->url->link('extension/module/' . $this->_moduleSysName()  . '/clear_filter_options', 'user_token=' . $this->session->data['user_token'], 'SSL');
		$data['copy_product_data'] = $this->url->link('extension/module/' . $this->_moduleSysName()  . '/copy_product_data', 'user_token=' . $this->session->data['user_token'], 'SSL');

		$data['copy_from_ocfilter'] = $this->url->link('extension/module/' . $this->_moduleSysName()  . '/copy_from_ocfilter', 'user_token=' . $this->session->data['user_token'], 'SSL');
		$data['copy_from_default_filter'] = $this->url->link('extension/module/' . $this->_moduleSysName()  . '/copy_from_default_filter', 'user_token=' . $this->session->data['user_token'], 'SSL');

		$this->load->model('localisation/language');
		$languages = array();
		foreach ($this->model_localisation_language->getLanguages() as $language) {
			$languages[$language['language_id']] = $language['name'];
		}
		$data['languages'] = $languages;
		$data['full_languages'] = $this->model_localisation_language->getLanguages();

		$data = $this->initParamsList(array(
			"settings_status",
			"debug",
			'show_attributes',
			'use_cache',
			'attributes_group',
			'use_discount',
			'use_special',
			'manufacturer_url',
			'price_url',
			'add_filters_to_h1',
			'import_filter_option',
			'import_product_field',
			'manufacturer_sort_order',
			'not_flush_filter_module_cache',
			'option_for_warehouse',
				), $data);

		//Cron
		$data[$this->_moduleSysName() . '_cron_copy_attributes'] = "php " . realpath(DIR_SYSTEM . "../cron/" . $this->_moduleSysName() . ".php") . " copy_attributes";
		$data[$this->_moduleSysName() . '_cron_copy_options'] = "php " . realpath(DIR_SYSTEM . "../cron/" . $this->_moduleSysName() . ".php") . " copy_options";
		$data[$this->_moduleSysName() . '_cron_copy_product_data'] = "php " . realpath(DIR_SYSTEM . "../cron/" . $this->_moduleSysName() . ".php") . " copy_product_data";
		$data[$this->_moduleSysName() . '_cron_copy_from_ocfilter'] = "php " . realpath(DIR_SYSTEM . "../cron/" . $this->_moduleSysName() . ".php") . " copy_from_ocfilter";
		$data[$this->_moduleSysName() . '_cron_clear_cache'] = "php " . realpath(DIR_SYSTEM . "../cron/" . $this->_moduleSysName() . ".php") . " clear_cache";

		$data['attribute_groups'] = array();
		$this->load->model('catalog/attribute_group');
		$attribute_groups = $this->model_catalog_attribute_group->getAttributeGroups();

		if ($attribute_groups) {
			foreach ($attribute_groups as $attribute_group) {
				$data['attribute_groups'][$attribute_group['attribute_group_id']] = $attribute_group['name'];
			}
		}

		$data['manufacturer_sorting_options'] = array(
			'default' => $this->language->get('param_manufacturer_default'),
			'sort_order' => $this->language->get('param_manufacturer_sort_order'),
			'name' => $this->language->get('param_manufacturer_name'),
		);

		$data['attribute_values_direction_sorting_options'] = array(
			'asc' => $this->language->get('param_attribute_values_direction_asc'),
			'desc' => $this->language->get('param_attribute_values_direction_desc'),
		);

		if ($this->config->get($this->_moduleSysName() . '_debug')) {
			$data[$this->_moduleSysName() . '_debug'] = 1;
		}

		$data['filter_options'] = array();
		$this->load->model('catalog/' . $this->_moduleSysName);
		$filter_data = array(
			'sort' => 'fod.name',
		);
		$filter_options = $this->model_catalog_neoseo_filter->getFilterOptions($filter_data);

		if ($filter_options) {
			foreach ($filter_options as $option) {
				$data['filter_options'][$option['option_id']] = $option['name'];
			}
		}

		$data['neoseo_exchange1c_status'] = false;
		if ($this->config->get('neoseo_exchange1c_status') == 1) {
			$data['neoseo_exchange1c_status'] = true;
		}

		$this->load->model('extension/' . $this->_route . '/' . $this->_moduleSysName);
		$data['use_warehouse_show'] = array(
			'disabled' => $this->{'model_extension_' . $this->_route . '_' . $this->_moduleSysName}->onWarehouse()
		);

		$widgets = new NeoSeoWidgets($this->_moduleSysName() . '_', $data);
		$widgets->text_select_all = $this->language->get('text_select_all');
		$widgets->text_unselect_all = $this->language->get('text_unselect_all');
		$data['widgets'] = $widgets;

		$data['params'] = $data;
		$data['user_token'] = $this->session->data['user_token'];
		$data['logs'] = $this->getLogs();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/' . $this->_moduleSysName() , $data));
	}

	public function clear_cache()
	{
		$this->language->load('extension/module/' . $this->_moduleSysName());
		$this->db->query("DELETE FROM " . DB_PREFIX . "filter_cache");
		$this->db->query("DELETE FROM " . DB_PREFIX . "filter_category_cache");
		$this->db->query("DELETE FROM " . DB_PREFIX . "filter_module_cache");
		$this->session->data['success'] = $this->language->get('text_success_cache_clear');

		$this->response->redirect($this->url->link("extension/" . $this->_route . '/' . $this->_moduleSysName() , 'user_token=' . $this->session->data['user_token'] . '#tab-logs', 'SSL'));
	}

	public function copy_from_ocfilter()
	{
		$this->load->model('catalog/' . $this->_moduleSysName);
		$this->{'model_catalog_' . $this->_moduleSysName}->copyFromOcFilter();

		$this->response->redirect($this->url->link("extension/" . $this->_route . '/' . $this->_moduleSysName() , 'user_token=' . $this->session->data['user_token'] . '#tab-logs', 'SSL'));
	}

	public function copy_from_default_filter()
	{
		$this->load->model('catalog/' . $this->_moduleSysName);
		$this->{'model_catalog_' . $this->_moduleSysName}->copyFromDefaultFilter();

		$this->language->load('extension/module/' . $this->_moduleSysName() );
		$this->session->data['success'] = $this->language->get('text_success');

		$this->response->redirect($this->url->link("extension/" . $this->_route . '/' . $this->_moduleSysName() , 'user_token=' . $this->session->data['user_token'] . '#tab-logs', 'SSL'));
	}

	public function copy_attributes()
	{
		$this->load->model('catalog/' . $this->_moduleSysName);
		$this->{'model_catalog_' . $this->_moduleSysName}->copyAttributes(isset($this->request->get['use_delimiter']) ? $this->request->get['delimiter'] : null);

		$this->response->redirect($this->url->link("extension/" . $this->_route . '/' . $this->_moduleSysName . '_settings', 'user_token=' . $this->session->data['user_token'] . '#tab-logs', 'SSL'));
	}
	public function copy_options()
	{
		$this->load->model('catalog/' . $this->_moduleSysName);
		$this->{'model_catalog_' . $this->_moduleSysName}->copyOptions();

		$this->response->redirect($this->url->link("extension/" . $this->_route . '/' . $this->_moduleSysName() , 'user_token=' . $this->session->data['user_token'] . '#tab-logs', 'SSL'));
	}

	public function clear_filter_options()
	{
		$this->load->model('catalog/' . $this->_moduleSysName);
		$this->{'model_catalog_' . $this->_moduleSysName}->clearFilterOptions();

		$this->response->redirect($this->url->link("extension/" . $this->_route . '/' . $this->_moduleSysName() , 'user_token=' . $this->session->data['user_token'] . '#tab-logs', 'SSL'));
	}

	public function copy_product_data()
	{
		$this->load->model('catalog/' . $this->_moduleSysName);
		$this->{'model_catalog_' . $this->_moduleSysName}->copyProductData();

		$this->response->redirect($this->url->link("extension/" . $this->_route . '/' . $this->_moduleSysName() , 'user_token=' . $this->session->data['user_token'] . '#tab-logs', 'SSL'));
	}

	private function validate()
	{
		if (!$this->user->hasPermission('modify', 'extension/module/' . $this->_moduleSysName() )) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->error) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	public function clear()
	{
		$this->language->load("extension/" . $this->_route . '/' . $this->_moduleSysName() );

		if (is_file(DIR_LOGS . $this->_logFile)) {
			$f = fopen(DIR_LOGS . $this->_logFile, "w");
			fclose($f);
		}

		$this->session->data['success'] = $this->language->get('text_success_clear');

		$this->response->redirect($this->url->link('extension/module/' . $this->_moduleSysName() , 'user_token=' . $this->session->data['user_token'] . '#tab-logs', 'SSL'));
	}

	public function update_warehouse()
	{
		$this->load->model('catalog/' . $this->_moduleSysName);
		$this->{'model_catalog_' . $this->_moduleSysName}->updateWarehouse();
		$this->response->redirect($this->url->link('extension/module/' . $this->_moduleSysName() , 'user_token=' . $this->session->data['user_token'] . '#tab-logs', 'SSL'));
	}

}

?>
