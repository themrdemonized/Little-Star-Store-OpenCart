<?php
class ModelSettingEcos extends Model {

	private function add_column($query) {
		$this->db->query("DROP PROCEDURE IF EXISTS `?`");
		$this->db->query("CREATE PROCEDURE `?`() BEGIN DECLARE CONTINUE HANDLER FOR SQLEXCEPTION BEGIN END; " . $query . "; END");
		$this->db->query("CALL `?`()");
		$this->db->query("DROP PROCEDURE `?`");
	}

	public function update_tables() {
		$this->add_column("ALTER TABLE `" . DB_PREFIX . "custom_field` ADD `dadata_field` VARCHAR(255) NULL DEFAULT NULL AFTER `sort_order`");

		$this->add_column("ALTER TABLE `" . DB_PREFIX . "product_option_value` ADD `price_old` DECIMAL(15, 4) NOT NULL DEFAULT 0 AFTER `price_prefix`");
        $this->add_column("ALTER TABLE `" . DB_PREFIX . "product_option_value` ADD `discount` DECIMAL(6, 2) NOT NULL DEFAULT 0 AFTER `price_prefix`");
        $this->add_column("ALTER TABLE `" . DB_PREFIX . "product_special` ADD `discount` DECIMAL(6, 2) NOT NULL DEFAULT 0 AFTER `price`");

		$this->add_column("ALTER TABLE `" . DB_PREFIX . "banner` ADD `slides_per_view` INT(10) NOT NULL DEFAULT 1 AFTER `name`");
		$this->add_column("ALTER TABLE `" . DB_PREFIX . "banner` ADD `space_between` INT(10) NOT NULL DEFAULT 0 AFTER `name`");

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "warehouse` ( `warehouse_id` INT NOT NULL AUTO_INCREMENT , `sort_order` INT NOT NULL DEFAULT '0' , PRIMARY KEY (`warehouse_id`)) ENGINE = InnoDB");
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "warehouse_description` ( `warehouse_id` INT NOT NULL , `language_id` INT NOT NULL , `name` VARCHAR(255) NOT NULL , `address` VARCHAR(255) NULL DEFAULT NULL , PRIMARY KEY (`warehouse_id`, `language_id`), INDEX `name` (`name`)) ENGINE = InnoDB");
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "warehouse_product` ( `warehouse_product_id` INT NOT NULL AUTO_INCREMENT , `warehouse_id` INT NOT NULL , `product_id` INT NOT NULL , `option_value_id` INT NULL DEFAULT NULL , `quantity` INT NOT NULL DEFAULT '0' , PRIMARY KEY (`warehouse_product_id`), UNIQUE `unique_warehouse_product` (`warehouse_id`, `product_id`, `option_value_id`)) ENGINE = InnoDB");
		$this->add_column("ALTER TABLE `" . DB_PREFIX . "warehouse` ADD `working_hours` TEXT NULL DEFAULT NULL AFTER `sort_order`");
		
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "product_description_composition` ( `product_id` INT NOT NULL , `language_id` INT NOT NULL , `composition` TEXT NOT NULL , PRIMARY KEY (`product_id`, `language_id`) ) ENGINE = InnoDB");
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "option_characteristic` ( `characteristic_id` INT NOT NULL AUTO_INCREMENT , `type` VARCHAR(255) NOT NULL , `sort_order` INT NOT NULL , PRIMARY KEY (`characteristic_id`)) ENGINE = InnoDB");
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "option_characteristic_description` ( `characteristic_id` INT NOT NULL , `language_id` INT NOT NULL , `name` VARCHAR(255) NOT NULL , PRIMARY KEY (`characteristic_id`, `language_id`)) ENGINE = InnoDB");
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "option_value_characteristic` ( `option_value_id` INT NOT NULL , `option_id` INT NOT NULL , `characteristic_id` INT NOT NULL , `value` TEXT NOT NULL , `serialized` BOOLEAN NOT NULL , PRIMARY KEY (`option_value_id`, `option_id`, `characteristic_id`)) ENGINE = InnoDB");

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "stocks` (`stocks_id` INT NOT NULL AUTO_INCREMENT, `start_at` DATE NOT NULL, `end_at` DATE NOT NULL, `discount` INT NOT NULL, `image` VARCHAR(255) NOT NULL, `sort_order` INT NOT NULL, PRIMARY KEY (`stocks_id`)) ENGINE=InnoDB");
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "stocks_description` ( `stocks_id` INT NOT NULL , `language_id` INT NOT NULL , `name` VARCHAR(255) NOT NULL , `description` TEXT NOT NULL , `requirements` TEXT NOT NULL , `meta_title` VARCHAR(255) NOT NULL , `meta_description` TEXT NOT NULL , `meta_keyword` VARCHAR(255) NOT NULL , `meta_h1` VARCHAR(255) NOT NULL , PRIMARY KEY (`stocks_id`, `language_id`), INDEX (`name`)) ENGINE = InnoDB;");
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "stocks_product` ( `stocks_id` INT NOT NULL , `product_id` INT NOT NULL , PRIMARY KEY (`stocks_id`, `product_id`)) ENGINE = InnoDB;");

		$this->add_column("ALTER TABLE `" . DB_PREFIX . "product` ADD `measure_name` VARCHAR(255) NOT NULL DEFAULT '' AFTER `length_class_id`");

        $this->add_column("ALTER TABLE `" . DB_PREFIX . "option_value_characteristic` ADD `description` TEXT NULL DEFAULT NULL AFTER `serialized`");

        $this->db->query("ALTER TABLE `" . DB_PREFIX . "address` CHANGE `address_1` `address_1` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "city` ( `city_id` INT NOT NULL AUTO_INCREMENT , `name` VARCHAR(255) NOT NULL , `default_city` BOOLEAN NOT NULL DEFAULT FALSE , PRIMARY KEY (`city_id`)) ENGINE = InnoDB");
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "product_city` ( `product_id` INT NOT NULL , `product_option_value_id` INT NOT NULL DEFAULT '0' , `city_id` INT NOT NULL , `price` DECIMAL(15,4) NOT NULL , `price_old` DECIMAL(15,4) NOT NULL , PRIMARY KEY (`product_id`, `product_option_value_id`, `city_id`), FOREIGN KEY (`city_id`) REFERENCES `" . DB_PREFIX . "city` (`city_id`) ON DELETE CASCADE) ENGINE = InnoDB;");

        $this->add_column("ALTER TABLE `" . DB_PREFIX . "product_discount` ADD `product_option_value_id` INT(11) NOT NULL DEFAULT 0 AFTER `product_id`");
        $this->add_column("ALTER TABLE `" . DB_PREFIX . "product_discount` ALTER `date_start` SET DEFAULT '1970-01-01'");
        $this->add_column("ALTER TABLE `" . DB_PREFIX . "product_discount` ALTER `date_end` SET DEFAULT '2099-01-01'");

        $this->add_column("ALTER TABLE `" . DB_PREFIX . "option_value_characteristic` DROP PRIMARY KEY");
        $this->add_column("ALTER TABLE `" . DB_PREFIX . "option_value_characteristic` ADD `option_value_characteristic_id` INT PRIMARY KEY AUTO_INCREMENT FIRST");

        $this->db->query("ALTER TABLE `" . DB_PREFIX . "option_value` CHANGE `image` `image` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL");
        $this->add_column("ALTER TABLE `" . DB_PREFIX . "option_value` ADD `image_serialized` BOOLEAN NOT NULL DEFAULT 0 AFTER `image`");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "information_to_customer_group` ( `information_id` INT(11) NOT NULL , `language_id` INT(11) NOT NULL , `customer_group_id` INT(11) NOT NULL , `title` VARCHAR(255) NOT NULL , `description` TEXT NOT NULL , `meta_title` VARCHAR(255) NOT NULL , `meta_description` VARCHAR(255) NOT NULL , `meta_keyword` VARCHAR(255) NOT NULL , `meta_h1` VARCHAR(255) NOT NULL , PRIMARY KEY (`information_id`, `language_id`, `customer_group_id`), INDEX (`title`), FOREIGN KEY (`customer_group_id`) REFERENCES `" . DB_PREFIX . "customer_group` (`customer_group_id`) ON DELETE CASCADE) ENGINE = InnoDB;");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "article_to_customer_group` ( `article_id` INT(11) NOT NULL , `language_id` INT(11) NOT NULL , `customer_group_id` INT(11) NOT NULL , `name` VARCHAR(255) NOT NULL , `description` TEXT NOT NULL , `meta_title` VARCHAR(255) NOT NULL , `meta_description` VARCHAR(255) NOT NULL , `meta_keyword` VARCHAR(255) NOT NULL , `meta_h1` VARCHAR(255) NOT NULL , `tag` TEXT NOT NULL , PRIMARY KEY (`article_id`, `language_id`, `customer_group_id`), INDEX (`name`), FOREIGN KEY (`customer_group_id`) REFERENCES `" . DB_PREFIX . "customer_group` (`customer_group_id`) ON DELETE CASCADE) ENGINE = InnoDB;");
	}

	//Watermark
	public function watermark_install($store_id = 0) {
        $settings = array(
            'config_watermark_status'                => 0,
            'config_watermark_hide_real_path'        => 0,
            'config_watermark_image'                 => 'catalog/opencart-logo.png',
            'config_watermark_size_x'                => 280,
            'config_watermark_size_y'                => 20,
            'config_watermark_zoom'                  => 0.5,
            'config_watermark_pos_x'                 => -20,
            'config_watermark_pos_x_center'          => 0,
            'config_watermark_pos_y'                 => -20,
            'config_watermark_pos_y_center'          => 0,
            'config_watermark_opacity'               => 0.8,
            'config_watermark_resize_first'          => 1,
            'config_watermark_category_image'        => 0,
            'config_watermark_product_thumb'         => 0,
            'config_watermark_product_popup'         => 1,
            'config_watermark_product_list'          => 0,
            'config_watermark_product_additional'    => 0,
            'config_watermark_product_related'       => 0,
            'config_watermark_product_in_compare'    => 0,
            'config_watermark_product_in_wish_list'  => 0,
            'config_watermark_product_in_cart'       => 0,
            );

        $this->db->query("DELETE FROM " . DB_PREFIX . "setting WHERE store_id = '" . (int)$store_id . "' AND `code` = 'config' AND `key` LIKE 'config_watermark%'");
        foreach ($settings as $key => $value) {
        	$this->db->query("INSERT INTO " . DB_PREFIX . "setting SET store_id = '" . (int)$store_id . "', `code` = 'config', `key` = '" . $this->db->escape($key) . "', `value` = '" . $this->db->escape($value) . "', serialized = '0'");
        }
        
    }

    public function watermark_check($store_id = 0) {
    	$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE store_id = '" . (int)$store_id . "' AND `code` = 'config' AND `key` LIKE 'config_watermark%'");

  		foreach ($query->rows as $result) {
  			return true;
  		}

		return false;
    }

    public function watermark_get_setting($store_id = 0) {
  		$setting_data = array();

  		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE store_id = '" . (int)$store_id . "' AND `code` = 'config' AND `key` LIKE 'config_watermark%'");

  		foreach ($query->rows as $result) {
  			$setting_data[$result['key']] = $result['value'];
  		}

		return $setting_data;
	}

}
