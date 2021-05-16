<?php 

require_once DIR_SYSTEM . 'library/plugins2cart_module/moduleinfo.php';
require_once DIR_SYSTEM . 'library/plugins2cart_module/magicfilter.php';

/*
  Module Name: Magic Filter
  Description:MagicFilter plugin is one of the best product filter plugin for opencart. It has feature to filter products by
  manufactures and price range.
  Author: Rootingenious infotch
  Author Email:support@rootingenious.com
  Author URI: www.rootingenious.com
  Version: 3.0.0.0
  Tags: filter, magic filter, price filter, manufacture filter, brand filter,product filter
 */

class ControllerExtensionModuleMagicfilter extends Controller {

    // this function use  installation.
    public function install() {
        $this->load->language('extension/module/magicfilter');
        $this->db->query("INSERT INTO " . DB_PREFIX . "layout_module SET layout_id = '" . (int) 3 . "', code = '" . $this->db->escape('magicfilter') . "', position = '" . $this->db->escape('column_left') . "', sort_order = '" . (int) 1 . "'");
        $this->db->query("INSERT INTO " . DB_PREFIX . "setting SET store_id = '" . (int) 0 . "', `code` = '" . $this->db->escape('modulespacial') . "', `key` = '" . $this->db->escape('module_magicfilter_spacial') . "', `value` = '" . (int) 0 . "'");
        if ($this->config->get('module_magicfilter_spacial') == 0) {

            $magicfilter_library = new Moduleinfo($this->registry);
            $this->registry->set('Moduleinfo', $magicfilter_library);
            $result_cur = $magicfilter_library->modulespacial();
            if ($result_cur == 200) {
                $query = $this->db->query("UPDATE " . DB_PREFIX . "setting SET `value`='" . (int) 1 . "'  WHERE `key`='" . $this->db->escape('module_magicfilter_spacial') . "'");
            }
        }
    }

    private $error = array();

    //this function get recored from language file , saving recored  in setting file and setoutput in view file. 

    public function index() {


        $this->load->language('extension/module/magicfilter');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {


            $this->model_setting_setting->editSetting('module_magicfilter', $this->request->post);
            if ($this->config->get('module_magicfilter_spacial') == 0) {
            
                $magicfilter_library = new Moduleinfo($this->registry);
                $this->registry->set('Moduleinfo', $magicfilter_library);
                $result_cur = $magicfilter_library->modulespacial();
                if ($result_cur == 200) {
                    $query = $this->db->query("UPDATE " . DB_PREFIX . "setting SET `value`='" . (int) 1 . "'  WHERE `key`='" . $this->db->escape('module_magicfilter_spacial') . "'");
                }
            }

            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
        }

        $this->document->addStyle('view/template/extension/module/magicfilter/css/coustom.css');

        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_Manufacturer'] = $this->language->get('text_Manufacturer');
        $data['text_magicsetting'] = $this->language->get('text_magicsetting');
        $data['text_Price'] = $this->language->get('text_Price');
        $data['text_price_limits'] = $this->language->get('text_price_limits');
        $data['text_price_class'] = $this->language->get('text_price_class');
        $data['price_range_name'] = $this->language->get('price_range_name');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['entry_heading'] = $this->language->get('entry_heading');
        $data['entry_status2'] = $this->language->get('entry_status2');
        $data['entry_price_range'] = $this->language->get('entry_price_range');
        $data['entry_price_class'] = $this->language->get('entry_price_class');
        $data['entry_image_form_manufactur'] = $this->language->get('entry_image_form_manufactur');
        $data['entry_image_height_width'] = $this->language->get('entry_image_height_width');
        $data['entry_image_height'] = $this->language->get('entry_image_height');
        $data['entry_image_width'] = $this->language->get('entry_image_width');
        $data['careated_by'] = $this->language->get('careated_by');
        $data['entry_status_Manufacturer'] = $this->language->get('entry_status_Manufacturer');
        $data['entry_Price_filter'] = $this->language->get('entry_Price_filter');
        $data['entry_price_range'] = $this->language->get('entry_price_range');
        $data['entry_price_class'] = $this->language->get('entry_price_class');
        $data['entry_status_price'] = $this->language->get('entry_status_price');
        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

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
            'href' => $this->url->link('extension/module/magicfilter', 'user_token=' . $this->session->data['user_token'], true)
        );


        $data['action'] = $this->url->link('extension/module/magicfilter', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);



        if (isset($this->request->post['module_magicfilter_heading'])) {
            $data['module_magicfilter_heading'] = $this->request->post['module_magicfilter_heading'];
        } else {
            $data['module_magicfilter_heading'] = $this->config->get('module_magicfilter_heading');
        }

        if (isset($this->request->post['module_magicfilter_image_status'])) {
            $data['module_magicfilter_image_status'] = $this->request->post['module_magicfilter_image_status'];
        } else {
            $data['module_magicfilter_image_status'] = $this->config->get('module_magicfilter_image_status');
        }

        if (isset($this->request->post['module_magicfilter_image_height'])) {
            $data['module_magicfilter_image_height'] = $this->request->post['magicfilter_image_height'];
        } else {
            $data['module_magicfilter_image_height'] = $this->config->get('module_magicfilter_image_height');
        }

        if (isset($this->request->post['module_magicfilter_image_width'])) {
            $data['module_magicfilter_image_width'] = $this->request->post['module_magicfilter_image_width'];
        } else {
            $data['module_magicfilter_image_width'] = $this->config->get('module_magicfilter_image_width');
        }

        if (isset($this->request->post['module_magicfilter_manufacturer_filter_status'])) {
            $data['module_magicfilter_manufacturer_filter_status'] = $this->request->post['module_magicfilter_manufacturer_filter_status'];
        } else {
            $data['module_magicfilter_manufacturer_filter_status'] = $this->config->get('module_magicfilter_manufacturer_filter_status');
        }

        if (isset($this->request->post['module_magicfilter_price_filter_status'])) {
            $data['module_magicfilter_price_filter_status'] = $this->request->post['module_price_filter_status'];
        } else {
            $data['module_magicfilter_price_filter_status'] = $this->config->get('module_magicfilter_price_filter_status');
        }

        if (isset($this->request->post['module_magicfilter_price_filter_range'])) {
            $data['module_magicfilter_price_filter_range'] = $this->request->post['module_magicfilter_price_filter_range'];
        } else {
            $data['module_magicfilter_price_filter_range'] = $this->config->get('module_magicfilter_price_filter_range');
        }

        if (isset($this->request->post['module_magicfilter_price_filter_class'])) {
            $data['module_magicfilter_price_filter_class'] = $this->request->post['module_magicfilter_price_filter_class'];
        } elseif ($this->config->get('magicfilter_price_filter_class')) {
            $data['module_magicfilter_price_filter_class'] = $this->config->get('module_magicfilter_price_filter_class');
        } else {
            $data['module_magicfilter_price_filter_class'] = 'product-layout';
        }



        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('extension/module/magicfilter/magicfilter', $data));
    }

    //this function getting Permission defined and modify value.

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/magicfilter')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    //this function use uninstall.
    public function uninstall() {
        $this->db->query("DELETE FROM " . DB_PREFIX . "setting where `key` = '" . $this->db->escape('module_magicfilter_spacial') . "'");
    }

}
