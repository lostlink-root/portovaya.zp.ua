<?php

require_once( DIR_SYSTEM . '/engine/neoseo_controller.php');
require_once( DIR_SYSTEM . '/engine/neoseo_view.php' );

class ControllerExtensionShippingNeoseoNovaposhta extends NeoSeoController
{

	private $error = array();

	public function __construct($registry)
	{
		parent::__construct($registry);
		$this->_moduleSysName = "neoseo_novaposhta";
		$this->_modulePostfix = ""; // Постфикс для разных типов модуля, поэтому переходим на испольлзование $this->_moduleSysName()
		$this->_logFile = $this->_moduleSysName() . ".log";
		$this->debug = $this->config->get($this->_moduleSysName() . "_debug") == 1;
	}

	public function index()
	{
		$this->checkLicense();
		$this->upgrade();

		$data = $this->language->load('extension/' . $this->_route . '/' . $this->_moduleSysName());

		$this->document->setTitle($this->language->get('heading_title_raw'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && ($this->validate())) {

			$this->model_setting_setting->editSetting($this->_moduleSysName(), $this->request->post);

			$this->{'model_extension_'. $this->_route . "_" . $this->_moduleSysName()}->setModuleStatus($this->request->post[$this->_moduleSysName() . "_status"]);

			$this->session->data['success'] = $this->language->get('text_success');

			if ($this->request->post['action'] == "save") {
				$this->response->redirect($this->url->link('extension/' . $this->_route . '/' . $this->_moduleSysName(), 'user_token=' . $this->session->data['user_token'], true));
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
			array('marketplace/extension', 'text_module'),
			array('extension/' . $this->_route . '/' . $this->_moduleSysName(), "heading_title_raw")
		    ), $data);

		$data = $this->initButtons($data);

		$this->load->model('extension/' . $this->_route . "/" . $this->_moduleSysName());
		$data = $this->initParamsListEx($this->{"model_extension_" . $this->_route . "_" . $this->_moduleSysName()}->getParams(), $data);

		$data["user_token"] = $this->session->data['user_token'];
		$data['config_language_id'] = $this->config->get('config_language_id');
		$data['params'] = $data;

		$data["logs"] = $this->getLogs();
		$data['full_languages'] = $this->model_localisation_language->getLanguages();
		// Справочники НП
		$this->load->model("tool/" . $this->_moduleSysName());
		$data['handbook_data'] = $this->{'model_tool_'.$this->_moduleSysName()}->getHandbookUpdates();
		$data['handbook_handbooks'] = $this->{'model_tool_'.$this->_moduleSysName()}->getCommonHandbooks();

		if(count($data['handbook_data']) == 0) {
			$data['error_warning'] = $data['text_need_updates'];
		}
		if($this->config->get($this->_moduleSysName().'_api_key') == ''){
			$data['error_warning'] = $data['text_no_api_key'];
		}

		$data['sender_city'] = $this->config->get($this->_moduleSysName().'_sender_city');
		$data['cityname'] =  $this->{'model_tool_'.$this->_moduleSysName()}->getCityBuId($data['sender_city']);
		if(isset($data['cityname']['descriptionru'])) {
			$data['cityname'] = $data['cityname']['descriptionru'];
			$data['warehouses'] = $this->{'model_tool_' . $this->_moduleSysName()}->getWarehouses($data['sender_city']);
		} else {
			$data['cityname'] = "";
			$data['warehouses'] = array();
		}
		$data['sender_warehouse'] = $this->config->get($this->_moduleSysName().'_sender_warehouse');
		$data['weigh_table'] = $this->config->get($this->_moduleSysName().'_custom_costs');
		$data['totals_table'] = $this->config->get($this->_moduleSysName().'_custom_costs_total');
		$data['current_cost_type'] = $this->config->get($this->_moduleSysName().'_custom_cost_type');
		if(!is_array($data['weigh_table'] )){
			$data['weigh_table']  = array();
		}
		$this->load->model('localisation/order_status');
		$data['order_statuses'] = array();
		foreach($this->model_localisation_order_status->getOrderStatuses() as $status){
			$data['order_statuses'][$status['order_status_id']] = $status['name'];
		}
		$data['api_key'] = $this->config->get($this->_moduleSysName().'_api_key');
		$data['sender'] =  $this->{'model_tool_' . $this->_moduleSysName()}->getSender();
		if(isset($data['sender']['addressess'])){
			$data['warehouses'] = array_merge($data['sender']['addressess'],$data['warehouses']);
		}

		$this->load->model('localisation/weight_class');
		foreach($this->model_localisation_weight_class->getWeightClasses() as $status){
			$data['weight_classes'][$status['weight_class_id']] = $status['title'];
		}

		$this->load->model('localisation/length_class');
		foreach($this->model_localisation_length_class->getLengthClasses() as $status){
			$data['length_classes'][$status['length_class_id']] = $status['title'];
		}

		$data[$this->_moduleSysName() . "_cron"] = "php " . realpath(DIR_SYSTEM . "../cron/" . $this->_moduleSysName() . ".php");

		$widgets = new NeoSeoWidgets($this->_moduleSysName() . '_', $data);
		$widgets->text_select_all = $this->language->get('text_select_all');
		$widgets->text_unselect_all = $this->language->get('text_unselect_all');
		$data['widgets'] = $widgets;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$data['sysname'] = $this->_moduleSysName();
		$data['uphblink'] = $this->url->link('extension/' . $this->_route . '/' . $this->_moduleSysName() . '/updatehandbook', 'user_token=' . $this->session->data['user_token'], 'SSL');

		$this->response->setOutput($this->load->view('extension/' . $this->_route . '/' . $this->_moduleSysName(), $data));
	}

	public function updateHandbook()
	{
		$this->language->load('extension/' . $this->_route . '/' . $this->_moduleSysName);
		$this->load->model("tool/" . $this->_moduleSysName());
		if($this->{'model_tool_'.$this->_moduleSysName()}->updateHandbook())
		{
			$this->session->data['success'] = $this->language->get('text_success_handbook_update');
		} else {
			$this->error['warning'] = $this->language->get('text_error_handbook_update');
		}
		$this->response->redirect($this->url->link('extension/' . $this->_route . '/' . $this->_moduleSysName(), 'user_token=' . $this->session->data['user_token'], 'SSL'));
	}

	public function cityAutocomplete()
	{
		$this->load->model("tool/" . $this->_moduleSysName);
		$results = $this->{'model_tool_' . $this->_moduleSysName()}->getCity($this->request->get['filter_name']);
		$json = array();
		foreach ($results as $id=>$row){
			$json[] = array(
				'id'    => $id,
				'name'  => $row,
			);
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function warehouseAutocomplete()
	{
		$this->load->model("tool/" . $this->_moduleSysName);
		$results = $this->{'model_tool_' . $this->_moduleSysName()}->getWarehouses($this->request->get['city_ref']);
		$output = '';
		foreach ($results as $result) {
			$output .= '<option value="' . $result['ref'] . '"';
			$output .= '>' . $result['descriptionru'] . '</option>';
		}
		$this->response->setOutput($output);
	}

	private function validate()
	{
		if (!$this->user->hasPermission('modify', 'extension/' . $this->_route . '/' . $this->_moduleSysName())) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->error) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

}
