<?php
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

//this function get manufacturer_id,name,and image path from database.
class ModelExtensionModuleMagicfilter extends Model {

     public function getmanufacture_by_catagory_id($data) {
        $magicfilter_library = new Magicfilter($this->registry);
        $this->registry->set('Magicfilter', $magicfilter_library);
       $manufacturer_data= $magicfilter_library->getmanufacture_by_catagory_id_INFO($data);
    return $manufacturer_data;  
       
    }


}
