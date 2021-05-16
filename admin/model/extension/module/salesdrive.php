<?php

/* OpenCart 2.3, 3.0 */

class ModelExtensionModuleSalesdrive extends Model
{
    public function install() {
        if (version_compare(VERSION,'3.0.0.0','>=')) {
            $this->load->model('setting/event');
            $this->model_setting_event->addEvent('salesdrive_add_order_history', 'catalog/model/checkout/order/addOrderHistory/after', 'extension/module/salesdrive/eventAddOrderHistory');
            $this->model_setting_event->addEvent('salesdrive_add_product', 'admin/model/catalog/product/addProduct/after', 'extension/module/salesdrive/eventEditProduct');
            $this->model_setting_event->addEvent('salesdrive_pre_edit_product', 'admin/model/catalog/product/editProduct/before', 'extension/module/salesdrive/eventPreEditProduct');
            $this->model_setting_event->addEvent('salesdrive_edit_product', 'admin/model/catalog/product/editProduct/after', 'extension/module/salesdrive/eventEditProduct');
            $this->model_setting_event->addEvent('salesdrive_delete_product', 'admin/model/catalog/product/deleteProduct/before', 'extension/module/salesdrive/eventDeleteProduct');
            $this->model_setting_event->addEvent('salesdrive_add_order', 'catalog/model/checkout/order/addOrder/after', 'extension/module/salesdrive/eventAddOrder');
        } else {
            $this->load->model('extension/event');

            $this->model_extension_event->addEvent('salesdrive_add_order_history', 'catalog/model/checkout/order/addOrderHistory/after', 'extension/module/salesdrive/eventAddOrderHistory');
            $this->model_extension_event->addEvent('salesdrive_add_product', 'admin/model/catalog/product/addProduct/after', 'extension/module/salesdrive/eventEditProduct');
            $this->model_extension_event->addEvent('salesdrive_pre_edit_product', 'admin/model/catalog/product/editProduct/before', 'extension/module/salesdrive/eventPreEditProduct');
            $this->model_extension_event->addEvent('salesdrive_edit_product', 'admin/model/catalog/product/editProduct/after', 'extension/module/salesdrive/eventEditProduct');
            $this->model_extension_event->addEvent('salesdrive_delete_product', 'admin/model/catalog/product/deleteProduct/before', 'extension/module/salesdrive/eventDeleteProduct');
            $this->model_extension_event->addEvent('salesdrive_add_order', 'catalog/model/checkout/order/addOrder/after', 'extension/module/salesdrive/eventAddOrder');
        }
    }

    public function uninstall() {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE `code` = 'salesdrive'");
        if (version_compare(VERSION,'3.0.0.0','>=')) {
            $this->load->model('setting/event');
            $this->model_setting_event->deleteEventByCode('salesdrive_add_order_history');
            $this->model_setting_event->deleteEventByCode('salesdrive_add_product');
            $this->model_setting_event->deleteEventByCode('salesdrive_edit_product');
            $this->model_setting_event->deleteEventByCode('salesdrive_pre_edit_product');
            $this->model_setting_event->deleteEventByCode('salesdrive_delete_product');
            $this->model_setting_event->deleteEventByCode('salesdrive_add_order');
        } else {
            $this->load->model('extension/event');
            $this->model_extension_event->deleteEvent('salesdrive_add_order_history');
            $this->model_extension_event->deleteEvent('salesdrive_add_product');
            $this->model_extension_event->deleteEvent('salesdrive_edit_product');
            $this->model_extension_event->deleteEvent('salesdrive_pre_edit_product');
            $this->model_extension_event->deleteEvent('salesdrive_delete_product');
            $this->model_extension_event->deleteEvent('salesdrive_add_order');
        }
    }
}