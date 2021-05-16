<?php

require_once(DIR_SYSTEM . "/engine/neoseo_controller.php");
require_once(DIR_SYSTEM . "/engine/neoseo_view.php");

class ControllerExtensionModuleNeoSeoFilter extends NeoSeoController
{

	private $error = array();

	public function __construct($registry)
	{
		parent::__construct($registry);
		$this->_module_code = "neoseo_filter";
		$this->_moduleSysName = "neoseo_filter";
		$this->_logFile = $this->_moduleSysName() . ".log";
		$this->debug = $this->config->get($this->_moduleSysName() . "_debug");
	}

	public function index()
	{
		$this->upgrade();

		$data = $this->language->load('extension/module/' . $this->_moduleSysName());

		$this->document->setTitle($this->language->get('heading_title_raw'));

		$this->load->model('setting/module');
		$this->load->model('extension/module/' . $this->_moduleSysName());

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && ($this->validate())) {
			if (isset($this->request->post[$this->_moduleSysName() . '_status'])) {
				$globalModuleData[$this->_moduleSysName() . '_status'] = $this->request->post[$this->_moduleSysName() . '_status'];
			}
			if (isset($this->request->post[$this->_moduleSysName() . '_module_key'])) {
				$globalModuleData[$this->_moduleSysName() . '_module_key'] = $this->request->post[$this->_moduleSysName() . '_module_key'];
			}

			$this->model_setting_setting->editSetting($this->_moduleSysName(), $globalModuleData);

			$moduleData = array();
			foreach ($this->request->post as $key => $value) {
				$shortKey = str_replace($this->_moduleSysName() . "_", "", $key);
				$moduleData[$shortKey] = $value;
			}
			if (!isset($this->request->get['module_id'])) {
				$this->model_setting_module->addModule($this->_moduleSysName(), $moduleData);
				$module_id = $this->db->getLastId();
			} else {
				$this->model_setting_module->editModule($this->request->get['module_id'], $moduleData);
				$module_id = $this->request->get['module_id'];
			}

			$this->session->data['success'] = $this->language->get('text_success');

			if ($this->request->post['action'] == "save") {
				$this->response->redirect($this->url->link('extension/module/' . $this->_moduleSysName(), "module_id=" . $module_id . '&user_token=' . $this->session->data['user_token'], 'SSL'));
			} else {
				$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], 'SSL'));
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
			array("extension/module" . $this->_moduleSysName(), "heading_title_raw")
				), $data);

		$data = $this->initButtons($data);

		if (!isset($this->request->get['module_id'])) {
			$data['save'] = $this->url->link('extension/module/' . $this->_moduleSysName(), 'user_token=' . $this->session->data['user_token'], 'SSL');
			$data['save_and_close'] = $this->url->link('extension/module/' . $this->_moduleSysName(), 'user_token=' . $this->session->data['user_token'] . "&close=1", 'SSL');
			$data['exist_module'] = false;
		} else {
			$data['save'] = $this->url->link('extension/module/' . $this->_moduleSysName(), 'module_id=' . $this->request->get['module_id'] . '&user_token=' . $this->session->data['user_token'], 'SSL');
			$data['save_and_close'] = $this->url->link('extension/module/' . $this->_moduleSysName(), 'module_id=' . $this->request->get['module_id'] . '&user_token=' . $this->session->data['user_token'] . "&close=1", 'SSL');
			$data['exist_module'] = true;
		}

		$data['modules'] = array();

		$this->load->model('localisation/language');
		$languages = array();
		foreach ($this->model_localisation_language->getLanguages() as $language) {
			$languages[$language['language_id']] = $language['name'];
		}
		$data['languages'] = $languages;
		$data['full_languages'] = $this->model_localisation_language->getLanguages();

		$defaultTitle = array();
		foreach ($data['languages'] as $language_id => $name) {
			$defaultTitle[$language_id] = $this->language->get('text_default_title');
		}

		$defaultManTitle = array();
		foreach ($data['languages'] as $language_id => $name) {
			$defaultManTitle[$language_id] = $this->language->get('text_default_manufacturer_title');
		}

		$data = $this->initParamsListEx($this->{"model_extension_" . $this->_route . "_" . $this->_moduleSysName}->getParams(), $data);

		$data = $this->initModuleParams(array(
			array($this->_moduleSysName() . '_status', 1),
			array($this->_moduleSysName() . '_name', ''),
			array($this->_moduleSysName() . '_manufacturer', 1),
			array($this->_moduleSysName() . '_ignore_seo_url_filter', 0),
			array($this->_moduleSysName() . '_manufacturer_title', $defaultManTitle),
			array($this->_moduleSysName() . '_title', $defaultTitle),
			array($this->_moduleSysName() . '_template', 'vertical'),
			array($this->_moduleSysName() . '_use_price', 1),
				), $data, $this->_moduleSysName());

		if ($this->config->get($this->_moduleSysName() . '_debug')) {
			$data[$this->_moduleSysName() . '_debug'] = 1;
		}

		$data['templates'] = array();
		$template_files = glob(DIR_CATALOG . 'view/theme/' . $this->config->get('config_template') . '/template/extension/module/' . $this->_moduleSysName() . '_*.twig');
		if (count($template_files) == 0) {
			$template_files = glob(DIR_CATALOG . 'view/theme/default/template/extension/module/' . $this->_moduleSysName() . '_*.twig');
		}
		if ($template_files) {
			foreach ($template_files as $template_file) {
				$template_file_name = str_replace($this->_moduleSysName() . '_', '', basename($template_file, '.twig'));
				if ($template_file_name == 'tag')
					continue;
				$data['templates'][$template_file_name] = $this->language->get('text_template') . " - " . $template_file_name;
			}
		}

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

		$this->response->setOutput($this->load->view('extension/module/' . $this->_moduleSysName(), $data));
	}

	private function validate()
	{
		if (!$this->user->hasPermission('modify', 'extension/module/' . $this->_moduleSysName())) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->error) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

}

?>
