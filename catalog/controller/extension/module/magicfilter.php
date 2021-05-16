<?php

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
require_once DIR_SYSTEM . 'library/plugins2cart_module/magicfilter.php';
class ControllerExtensionModuleMagicfilter extends Controller {
//this function get recored from language file , saving recored  in setting file and setoutput in view file. 
    public function index() {


        $this->load->language('extension/module/magicfilter');
        $data['heading_title'] = $this->language->get('heading_title');
        $this->load->model('extension/module/magicfilter');
        $this->load->model('catalog/product');
        $this->load->model('catalog/category');
        $data['categories'] = array();
        $data['magicfilter'] = array();
        $data['button_filter'] = $this->language->get('button_filter');
        $this->document->addScript('catalog/view/theme/default/template/extension/module/magicfilter/slider/jquery.slider.js');
        $this->document->addScript('catalog/view/theme/default/template/extension/module/magicfilter/slider/slider.js');
        $this->document->addStyle('catalog/view/theme/default/template/extension/module/magicfilter/slider/slider.css');
        $this->document->addStyle('catalog/view/theme/default/template/extension/module/magicfilter/css/coustom.css');
        $this->document->addStyle('//code.jquery.com/ui/1.10.4/themes/flick/jquery-ui.css');


        $magicfilter_library = new Magicfilter($this->registry);
        $this->registry->set('Magicfilter', $magicfilter_library);
        $data= $magicfilter_library->filter_data_info($this->request,$this->config);
        $data['heading_title'] = $this->language->get('heading_title');
        $data['button_filter'] = $this->language->get('button_filter');

 
        return $this->load->view('extension/module/magicfilter/magicfilter', $data);
            }
        }

