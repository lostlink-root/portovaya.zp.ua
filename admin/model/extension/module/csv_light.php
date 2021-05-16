<?php

class ModelExtensionModuleCsvLight extends Model {
	
	
	
	// возвращает ID одного товара
	public function getProductId() {
		$product_id = $this->db->query("SELECT `product_id` FROM `".DB_PREFIX."product` LIMIT 1")->row['product_id'];
		return $product_id;
	}
	
	// возвращает массив ID товаров
	public function getProducts() {
		$result = array();
		foreach ( $this->db->query("SELECT `product_id` FROM `".DB_PREFIX."product` ORDER BY `product_id` ASC")->rows as $product ) {
			$result[] = $product['product_id'];
		}
		return $result;
	}

	// Получает ID продукта по имени на языке по умолчанию
	public function getProductIdByName($product_name) {
		$row = $this->db->query("SELECT `product_id` FROM `".DB_PREFIX."product_description` WHERE `language_id`='".(int)$this->config->get('config_language_id')."' AND `name`='".$this->db->escape($product_name)."'")->row;
		return !empty($row['product_id']) ? $row['product_id'] : 0;
	}

	// Получает ID категории по имени на языке по умолчанию
	public function getCategoryIdByName($category_name) {
		$row = $this->db->query("SELECT `category_id` FROM `".DB_PREFIX."category_description` WHERE `language_id`='".(int)$this->config->get('config_language_id')."' AND `name`='".$this->db->escape($category_name)."'")->row;
		return (!empty($row['category_id'])) ? $row['category_id'] : 0;
	}
	
	// Получает ID категории по имени на языке по умолчанию
	public function getCategoryIdPathByName($category_name, $parent_id = 0) {
		$row = $this->db->query("SELECT cd.category_id FROM ".DB_PREFIX."category_description cd LEFT JOIN ".DB_PREFIX."category c ON (c.category_id = cd.category_id) WHERE `language_id`='".(int)$this->config->get('config_language_id')."' AND parent_id ='" . (int)$parent_id . "' AND `name`='".$this->db->escape($category_name)."'")->row;
		return (!empty($row['category_id'])) ? $row['category_id'] : 0;
	}

	// Получает ID группы атрибутов по имени на языке по умолчанию
	public function getAttributeGroupIdByName($attribute_group_name) {
		$row = $this->db->query("SELECT `attribute_group_id` FROM `".DB_PREFIX."attribute_group_description` WHERE `language_id`='".(int)$this->config->get('config_language_id')."' AND `name`='".$this->db->escape($attribute_group_name)."'")->row;
		return !empty($row['attribute_group_id']) ? $row['attribute_group_id'] : 0;
	}

	// Получает ID атрибута по имени на языке по умолчанию
	public function getAttributeIdByName($attribute_name) {
		$row = $this->db->query("SELECT `attribute_id` FROM `".DB_PREFIX."attribute_description` WHERE `language_id`='".(int)$this->config->get('config_language_id')."' AND `name`='".$this->db->escape($attribute_name)."'")->row;
		return !empty($row['attribute_id']) ? $row['attribute_id'] : 0;
	}

	// Получает ID производителя по имени на языке по умолчанию
	public function getManufacturerIdByName($manufacturer_name) {
		$row = $this->db->query("SELECT `manufacturer_id` FROM `".DB_PREFIX."manufacturer` WHERE `name`='".$this->db->escape($manufacturer_name)."'")->row;
		return ( !empty($row['manufacturer_id']) ) ? $row['manufacturer_id'] : 0;
	}

	// Получает ID опции по имени на языке по умолчанию
	public function getOptionIdByName($option_name) {
		$row = $this->db->query("SELECT `option_id` FROM `".DB_PREFIX."option_description` WHERE `language_id`='".(int)$this->config->get('config_language_id')."' AND `name`='".$this->db->escape($option_name)."'")->row;
		return ( !empty($row['option_id']) ) ? $row['option_id'] : 0;
	}

	// Получает ID значения опции по имени на языке по умолчанию
	public function getOptionValueIdByName($option_value_name) {
		$row = $this->db->query("SELECT `option_value_id` FROM `".DB_PREFIX."option_value_description` WHERE `language_id`='".(int)$this->config->get('config_language_id')."' AND `name`='".$this->db->escape($option_value_name)."'")->row;
		return ( !empty($row['option_value_id']) ) ? $row['option_value_id'] : 0;
	}


	// Cвой метод изменения опции, ибо стандартный затирает все значения кроме переданных.
	public function editOption($option_id, $data) {
		$this->db->query("UPDATE `" . DB_PREFIX . "option` SET type = '" . $this->db->escape($data['type']) . "', sort_order = '" . (int)$data['sort_order'] . "' WHERE option_id = '" . (int)$option_id . "'");

		$this->db->query("DELETE FROM " . DB_PREFIX . "option_description WHERE option_id = '" . (int)$option_id . "'");

		foreach ($data['option_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "option_description SET option_id = '" . (int)$option_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "'");
		}

		if (isset($data['option_value'])) {
			foreach ($data['option_value'] as $option_value) {
				if ($option_value['option_value_id']) {
					$this->db->query("DELETE FROM " . DB_PREFIX . "option_value WHERE option_value_id = '" . (int)$option_value['option_value_id'] . "'");
					$this->db->query("DELETE FROM " . DB_PREFIX . "option_value_description WHERE option_value_id = '" . (int)$option_value['option_value_id'] . "'");

					$this->db->query("INSERT INTO " . DB_PREFIX . "option_value SET option_value_id = '" . (int)$option_value['option_value_id'] . "', option_id = '" . (int)$option_id . "', image = '" . $this->db->escape(html_entity_decode($option_value['image'], ENT_QUOTES, 'UTF-8')) . "', sort_order = '" . (int)$option_value['sort_order'] . "'");
				} else {
					$this->db->query("INSERT INTO " . DB_PREFIX . "option_value SET option_id = '" . (int)$option_id . "', image = '" . $this->db->escape(html_entity_decode($option_value['image'], ENT_QUOTES, 'UTF-8')) . "', sort_order = '" . (int)$option_value['sort_order'] . "'");
				}

				$option_value_id = $this->db->getLastId();

				foreach ($option_value['option_value_description'] as $language_id => $option_value_description) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "option_value_description SET option_value_id = '" . (int)$option_value_id . "', language_id = '" . (int)$language_id . "', option_id = '" . (int)$option_id . "', name = '" . $this->db->escape($option_value_description['name']) . "'");
				}
			}

		}
	}


	public function getProductOptionValueId($product_id, $option_id, $option_value_id) {
		if ( !$product_id ) { return ''; }
		$row = $this->db->query("SELECT `product_option_value_id` FROM `".DB_PREFIX."product_option_value` WHERE `product_id`='".(int)$product_id."' AND `option_id`='".(int)$option_id."' AND `option_value_id`='".(int)$option_value_id."'")->row;
		return (!empty($row['product_option_value_id'])) ? $row['product_option_value_id'] : '';
	}

	// Получает путь категорий от дочки до главной
	public function getCategoryPath($category_id) {
		$sql = "SELECT cp.category_id AS category_id, GROUP_CONCAT(cd1.name ORDER BY cp.level SEPARATOR ' > ') AS name, c1.parent_id FROM " . DB_PREFIX . "category_path cp LEFT JOIN " . DB_PREFIX . "category c1 ON (cp.category_id = c1.category_id) LEFT JOIN " . DB_PREFIX . "category c2 ON (cp.path_id = c2.category_id) LEFT JOIN " . DB_PREFIX . "category_description cd1 ON (cp.path_id = cd1.category_id) LEFT JOIN " . DB_PREFIX . "category_description cd2 ON (cp.category_id = cd2.category_id) WHERE cd1.language_id = '" . (int)$this->config->get('config_language_id') . "' AND cd2.language_id = '" . (int)$this->config->get('config_language_id') . "'";
		$sql .= " AND cp.category_id = '" . $category_id . "'";

		$query = $this->db->query($sql);

		return $query->row['name'];
	}
	
	
	public function getDbTableColumns($tbName) {
		$result = array();
		foreach ( $this->db->query("SHOW COLUMNS FROM `".$this->db->escape($tbName)."`")->rows as $row ) {
			$result[] = $row['Field'];
		}
		return$result;
	}
	
	
}