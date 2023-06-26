<?php
class ModelExtensionModuleTradeImport extends Model {

    private function set_query(&$query, $data, $query_limit) {
        $key = $query['data_key'];
        $query['data'][$key] .= $data;
        if (strlen($query['data'][$key]) > $query_limit) {
            $query['data_key']++;
            $key = $query['data_key'];
            $query['data'][$key] = '';
        }
    }

    private function set_query_ready(&$query, $additional = '') {
        if ($query['data'] === array(0 => "")) {
            return;
        }

        foreach ($query['data'] as $q) {
            if (strlen($q)) {
                $query['query_ready'][] = substr($query['query'] . $q, 0, -1) . " " . $additional;
            }
        }
    }

    private function query($query, $debug) {
        if ($debug) {
            echo $query . "\n";
        }
        $this->db->query($query);
    } 

    private function exec_queries($queries, $debug = false) {
        foreach ($queries as $r) {
            if ($debug) {
                echo $r . "\n";
                echo "Size: " . strlen($r) . "\n";
            }
            $this->db->query($r);
        }
    }

    private function add_column($query) {
        $this->db->query("DROP PROCEDURE IF EXISTS `?`");
        $this->db->query("CREATE PROCEDURE `?`() BEGIN DECLARE CONTINUE HANDLER FOR SQLEXCEPTION BEGIN END; " . $query . "; END");
        $this->db->query("CALL `?`()");
        $this->db->query("DROP PROCEDURE `?`");
    }

    public function set_tables() {
        $success = false;
        $this->db->query('ALTER TABLE ' . DB_PREFIX . 'product ENGINE = InnoDB');
        $this->db->query('ALTER TABLE ' . DB_PREFIX . 'category ENGINE = InnoDB');
        $this->db->query('ALTER TABLE ' . DB_PREFIX . 'option ENGINE = InnoDB');
        $this->db->query('ALTER TABLE ' . DB_PREFIX . 'option_value ENGINE = InnoDB');
        $this->db->query('ALTER TABLE ' . DB_PREFIX . 'filter_group ENGINE = InnoDB');
        $this->db->query('CREATE TABLE IF NOT EXISTS ' . DB_PREFIX . 'trade_import (operation_id INT(11) NOT NULL AUTO_INCREMENT, timestamp TIMESTAMP, json_timestamp TIMESTAMP, success BOOLEAN, response TEXT, PRIMARY KEY (operation_id))');
        $this->db->query('CREATE TABLE IF NOT EXISTS ' . DB_PREFIX . 'trade_orders (operation_id INT(11) NOT NULL AUTO_INCREMENT, order_id INT(11), order_data TEXT, response TEXT, PRIMARY KEY (operation_id))');
        $this->db->query('CREATE TABLE IF NOT EXISTS ' . DB_PREFIX . 'trade_import_product_codes (`product_id` INT(11) NOT NULL, `nomenclature_uuid` VARCHAR(191) NOT NULL, `group_uuid` VARCHAR(191) NOT NULL, PRIMARY KEY (nomenclature_uuid), FOREIGN KEY (product_id) REFERENCES ' . DB_PREFIX . 'product (product_id) ON DELETE CASCADE)');
        $this->db->query('CREATE TABLE IF NOT EXISTS ' . DB_PREFIX . 'trade_import_category_codes (`category_id` INT(11) NOT NULL, `group_uuid` VARCHAR(191) NOT NULL, PRIMARY KEY (group_uuid), FOREIGN KEY (category_id) REFERENCES ' . DB_PREFIX . 'category (category_id) ON DELETE CASCADE)');
        $this->db->query('CREATE TABLE IF NOT EXISTS ' . DB_PREFIX . 'trade_import_option_codes (`option_id` INT(11) NOT NULL, `nomenclature_uuid` VARCHAR(191) NOT NULL, PRIMARY KEY (nomenclature_uuid), FOREIGN KEY (option_id) REFERENCES ' . DB_PREFIX . 'option (option_id) ON DELETE CASCADE)');
        $this->db->query('CREATE TABLE IF NOT EXISTS ' . DB_PREFIX . 'trade_import_option_value_codes (`option_value_id` INT(11) NOT NULL, `characteristic_uuid` VARCHAR(191) NOT NULL, `nomenclature_uuid` VARCHAR(191) NOT NULL, PRIMARY KEY (characteristic_uuid), FOREIGN KEY (option_value_id) REFERENCES ' . DB_PREFIX . 'option_value (option_value_id) ON DELETE CASCADE)');
        $this->db->query('CREATE TABLE IF NOT EXISTS ' . DB_PREFIX . 'trade_import_filter_group_codes (`filter_group_id` INT(11) NOT NULL, `filter_uuid` VARCHAR(191) NOT NULL, PRIMARY KEY (filter_uuid), FOREIGN KEY (filter_group_id) REFERENCES ' . DB_PREFIX . 'filter_group (filter_group_id) ON DELETE CASCADE)');
        $this->db->query('CREATE TABLE IF NOT EXISTS ' . DB_PREFIX . 'trade_import_warehouse_codes (`warehouse_id` INT(11) NOT NULL, `storage_uuid` VARCHAR(191) NOT NULL, PRIMARY KEY (storage_uuid), FOREIGN KEY (warehouse_id) REFERENCES ' . DB_PREFIX . 'warehouse (warehouse_id) ON DELETE CASCADE)');
        $this->db->query('CREATE TABLE IF NOT EXISTS ' . DB_PREFIX . 'trade_import_option_characteristic_codes (`characteristic_id` INT(11) NOT NULL, `property_uuid` VARCHAR(191) NOT NULL, PRIMARY KEY (property_uuid), FOREIGN KEY (characteristic_id) REFERENCES ' . DB_PREFIX . 'option_characteristic (characteristic_id) ON DELETE CASCADE)');
        $this->db->query('CREATE TABLE IF NOT EXISTS ' . DB_PREFIX . 'trade_import_stocks_codes (`stocks_id` INT(11) NOT NULL, `stocks_uuid` VARCHAR(191) NOT NULL, PRIMARY KEY (stocks_uuid), FOREIGN KEY (stocks_id) REFERENCES ' . DB_PREFIX . 'stocks (stocks_id) ON DELETE CASCADE)');
        $this->db->query('CREATE TABLE IF NOT EXISTS ' . DB_PREFIX . 'trade_import_stocks_banner (`banner_image_id` INT(11) NOT NULL, `banner_id` INT(11) NOT NULL, `stocks_id` INT(11) NOT NULL, `stocks_uuid` VARCHAR(191) NOT NULL, PRIMARY KEY (banner_image_id), FOREIGN KEY (stocks_id) REFERENCES ' . DB_PREFIX . 'stocks (stocks_id) ON DELETE CASCADE)');
        $this->db->query('CREATE TABLE IF NOT EXISTS ' . DB_PREFIX . 'trade_checks (check_id INT(11) NOT NULL AUTO_INCREMENT, order_id INT(11) NOT NULL, order_data TEXT, response TEXT, PRIMARY KEY (check_id))');
        $this->db->query('CREATE TABLE IF NOT EXISTS ' . DB_PREFIX . 'trade_images (id INT(11) NOT NULL AUTO_INCREMENT, image_id INT(11) NOT NULL, path VARCHAR(255) NOT NULL, hash VARCHAR(191) NOT NULL, size INT(10) NOT NULL, PRIMARY KEY (id))');
        $this->add_column("ALTER TABLE `" . DB_PREFIX . "trade_images` ADD `size` INT(10) NOT NULL");
        $this->db->query('CREATE TABLE IF NOT EXISTS ' . DB_PREFIX . 'trade_import_services (id INT(11) NOT NULL AUTO_INCREMENT, service_uuid VARCHAR(191) NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY (id))');
        $success = true;
        return $success;
    }

    public function clean_tables() {
        $this->db->query('DROP TABLE ' . DB_PREFIX . 'trade_import');
        $this->db->query('DROP TABLE ' . DB_PREFIX . 'trade_orders');
        $this->db->query('DROP TABLE ' . DB_PREFIX . 'trade_checks');
        //$this->db->query('DROP TABLE ' . DB_PREFIX . 'trade_images');
        $this->db->query('DROP TABLE ' . DB_PREFIX . 'trade_import_product_codes');
        $this->db->query('DROP TABLE ' . DB_PREFIX . 'trade_import_category_codes');
        $this->db->query('DROP TABLE ' . DB_PREFIX . 'trade_import_option_codes');
        $this->db->query('DROP TABLE ' . DB_PREFIX . 'trade_import_option_characteristic_codes');
        $this->db->query('DROP TABLE ' . DB_PREFIX . 'trade_import_option_value_codes');
        $this->db->query('DROP TABLE ' . DB_PREFIX . 'trade_import_filter_group_codes');
        $this->db->query('DROP TABLE ' . DB_PREFIX . 'trade_import_warehouse_codes');
        $this->db->query('DROP TABLE ' . DB_PREFIX . 'trade_import_stocks_codes');
        $this->db->query('DROP TABLE ' . DB_PREFIX . 'trade_import_stocks_banner');
        $this->db->query('DROP TABLE ' . DB_PREFIX . 'trade_import_services');
    }

    public function clean_orders() {
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'trade_orders');
    }

    public function clean_checks() {
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'trade_checks');
    }

    public function clean_images() {
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'trade_images');
    }

    public function clean_all($parent_id = NULL, $debug = false) {
        if (isset($parent_id)) {
            $parent_category = $this->get_parent_category($parent_id);
        }
        $banner_codes = $this->get_stocks_banner_ids();
        if (!empty($banner_codes)) {
            foreach ($banner_codes as $value) {
                if (!empty($value)) {
                    $this->db->query('DELETE FROM ' . DB_PREFIX . "banner_image WHERE banner_image_id IN (" . implode(",", $value) . ")");
                }
            }
        }
        $this->clean_tables();
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'category');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'category_description');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'category_path');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'category_filter');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'category_to_store');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'category_to_layout');
        $this->db->query('ALTER TABLE ' . DB_PREFIX . 'category AUTO_INCREMENT = 50');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'product');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'product_attribute');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'product_description');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'product_description_composition');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'product_discount');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'product_filter');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'product_image');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'product_option');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'product_option_value');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'product_recurring');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'product_related');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'product_related_article');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'product_related_mn');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'product_related_wb');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'product_reward');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'product_special');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'product_to_category');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'product_to_download');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'product_to_layout');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'product_to_store');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'product_city');
        $this->db->query('ALTER TABLE ' . DB_PREFIX . 'product AUTO_INCREMENT = 50');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'option');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'option_description');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'option_value');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'option_value_description');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'option_characteristic');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'option_characteristic_description');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'option_value_characteristic');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'filter');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'filter_description');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'filter_group');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'filter_group_description');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'ocfilter_option');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'ocfilter_option_description');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'ocfilter_option_to_category');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'ocfilter_option_to_store');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'ocfilter_option_value');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'ocfilter_option_value_description');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'ocfilter_option_value_to_product');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'ocfilter_option_value_to_product_description');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'ocfilter_page');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'ocfilter_page_description');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'warehouse');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'warehouse_description');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'warehouse_product');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'stocks');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'stocks_description');
        $this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'stocks_product');
        $this->db->query('DELETE FROM ' . DB_PREFIX . "seo_url WHERE query LIKE 'product_id=%' OR query LIKE 'category_id=%' OR query LIKE 'stocks_id=%'");
        $this->db->query('SET @var:=0');
        $this->db->query('UPDATE ' . DB_PREFIX . "seo_url SET seo_url_id=(@var:=@var+1)");
        $this->db->query('ALTER TABLE ' . DB_PREFIX . 'seo_url AUTO_INCREMENT = 1');
        $this->set_tables();

        if (isset($parent_category)) {
            if ($debug) {
                echo "Parent category {$parent_category['category_description']['category_id']} - {$parent_category['category_description']['name']} restored\n";
            }
            $this->set_parent_category($parent_category);
        }

        $this->cache->delete('category');
        $this->cache->delete('product');
        
        if ($this->config->get('config_seo_pro')){      
            $this->cache->delete('seopro');
        } 
    }

    public function add_indexes() {
        $this->db->query("ALTER TABLE " . DB_PREFIX . "category ADD INDEX `status` (`status`)");
        $this->db->query("ALTER TABLE " . DB_PREFIX . "category_path ADD INDEX `path_id` (`path_id`)");
        $this->db->query("ALTER TABLE " . DB_PREFIX . "filter ADD INDEX `filter_group_id` (`filter_group_id`)");
        $this->db->query("ALTER TABLE " . DB_PREFIX . "filter_description ADD INDEX `filter_group_id` (`filter_group_id`), ADD INDEX `name` (`name`)");
        $this->db->query("ALTER TABLE " . DB_PREFIX . "filter_group_description ADD INDEX `name` (`name`)");
        $this->db->query("ALTER TABLE " . DB_PREFIX . "option_description ADD INDEX `name` (`name`)");
        $this->db->query("ALTER TABLE " . DB_PREFIX . "option_value ADD INDEX `option_id` (`option_id`)");
        $this->db->query("ALTER TABLE " . DB_PREFIX . "option_value_description ADD INDEX `option_id` (`option_id`), ADD INDEX `name` (`name`)");
        $this->db->query("ALTER TABLE " . DB_PREFIX . "product ADD INDEX `model` (`model`), ADD INDEX `sku` (`sku`), ADD INDEX `upc` (`upc`), ADD INDEX `ean` (`ean`), ADD INDEX `jan` (`jan`), ADD INDEX `isbn` (`isbn`), ADD INDEX `mpn` (`mpn`), ADD INDEX `stock_status_id` (`stock_status_id`), ADD INDEX `manufacturer_id` (`manufacturer_id`), ADD INDEX `price` (`price`), ADD INDEX `quantity` (`quantity`), ADD INDEX `shipping` (`shipping`), ADD INDEX `date_available` (`date_available`), ADD INDEX `status` (`status`)");
        $this->db->query("ALTER TABLE " . DB_PREFIX . "product_attribute ADD INDEX `text` (`text`(10))");
        $this->db->query("ALTER TABLE " . DB_PREFIX . "product_option ADD INDEX `option_id` (`option_id`), ADD INDEX `product_id` (`product_id`), ADD INDEX `value` (`value`(10))");
        $this->db->query("ALTER TABLE " . DB_PREFIX . "product_option_value ADD INDEX `option_id` (`option_id`), ADD INDEX `product_id` (`product_id`), ADD INDEX `product_option_id` (`product_option_id`), ADD INDEX `option_value_id` (`option_value_id`), ADD INDEX `price` (`price`), ADD INDEX `quantity` (`quantity`)");
        $this->db->query("ALTER TABLE " . DB_PREFIX . "product_description ADD INDEX `tag` (`tag`(10))");

    }

    public function get_store_name() {
        $result = $this->db->query("SELECT value FROM " . DB_PREFIX . "setting WHERE `key` = 'config_name'");
        return $result->row['value'];
    }

    private function add_multiple_paths($arr) {
        $query_limit = 1000000;
        $category_codes = $this->get_category_codes();
        $queries = array(
            'add' => array(
                'category_path' => array(
                    'query' => "INSERT INTO " . DB_PREFIX . "category_path VALUES ",
                    'data' => array(0 => ""),
                    'data_key' => 0
                ) 
            )
        );
        foreach ($arr as $data) {
            $level = 0;
            $codes = explode("/", $data['path']);
            if ($data['parent_category_id']) {
                $this->set_query($queries['add']['category_path'], "('" . $category_codes[$data['code']] . "', '" . (int)$data['parent_category_id'] . "', '" . $level++ . "'),", $query_limit);
            }
            foreach ($codes as $key => $code) {
                if (!in_array($code, $data['parent_category_code'])) {
                    $this->set_query($queries['add']['category_path'], "('" . $category_codes[$data['code']] . "', '" . $category_codes[$code] . "', '" . $level++ . "'),", $query_limit);
                }
            }
        }

        foreach ($queries['add'] as $k => $q) {
            $this->set_query_ready($queries['add'][$k]);
            if (isset($queries['add'][$k]['query_ready'])) {
                $this->exec_queries($queries['add'][$k]['query_ready'], $this->debug);
            }
        }
    }

    public function add_multiple_categories($arr, $debug = false) {

        if (empty($arr)) {
            return;
        }

        $query_limit = 1000000;
        $start_id = $this->db->query("SELECT MAX(category_id) FROM " . DB_PREFIX . "category");
        $start_id = $start_id->row['MAX(category_id)'] == NULL ? 50 : (int)($start_id->row['MAX(category_id)'] + 1);
        $arr_codes = array();
        $category_codes = $this->get_category_codes();
        $seo_urls = array();
        $this->db->query('DELETE FROM ' . DB_PREFIX . 'trade_import_category_codes WHERE 1');
        $queries = array(
            'delete' => array(
                'category_to_store' => "DELETE FROM " . DB_PREFIX . "category_to_store WHERE category_id IN (",
                'category_path' => "DELETE FROM " . DB_PREFIX . "category_path WHERE category_id IN (",
                'seo_url' => "DELETE FROM " . DB_PREFIX . "seo_url WHERE query IN ('category_id=",    
            ),
            'add' => array(
                'category' => array(
                    'query' => "INSERT INTO " . DB_PREFIX . "category(`category_id`, `image`, `parent_id`, `top`, `column`, `sort_order`, `status`, `date_added`, `date_modified`, `noindex`) VALUES ",
                    'data' => array(0 => ""),
                    'data_key' => 0
                ),
                'category_description' => array(
                    'query' => "INSERT INTO " . DB_PREFIX . "category_description(`category_id`, `language_id`, `name`, `description`, `meta_title`, `meta_description`, `meta_keyword`, `meta_h1`) VALUES ",
                    'data' => array(0 => ""),
                    'data_key' => 0
                ), 
                'category_to_store' => array(
                    'query' => "INSERT INTO " . DB_PREFIX . "category_to_store VALUES ",
                    'data' => array(0 => ""),
                    'data_key' => 0
                ), 
                // 'category_path' => array(
                //     'query' => "INSERT INTO " . DB_PREFIX . "category_path VALUES ",
                //     'data' => array(0 => ""),
                //     'data_key' => 0
                // ), 
                'seo_url' => array(
                    'query' => "INSERT INTO " . DB_PREFIX . "seo_url(`store_id`, `language_id`, `query`, `keyword`) VALUES ",
                    'data' => array(0 => ""),
                    'data_key' => 0
                ), 
                'category_code' => array(
                    'query' => "INSERT INTO " . DB_PREFIX . "trade_import_category_codes(`category_id`, `group_uuid`) VALUES ",
                    'data' => array(0 => ""),
                    'data_key' => 0
                ) 
            )
        );

        foreach ($arr as $data) {
            if (!isset($category_codes[$data['code']])) {
                if ($debug) {
                    echo 'Adding category ' . $data['category_description'][1]['name'] . "\n";
                }
                $category_codes[$data['code']] = $start_id;
                $start_id++;
            } else {
                if ($debug) {
                    echo $data['category_description'][1]['name'] . " category exists. Editing\n";
                }
            }
            $level = 0;
            $codes = explode("/", $data['path']);
            foreach ($codes as $key => $code) {
                if (!in_array($code, $data['parent_category_code'])) {
                    //$this->set_query($queries['add']['category_path'], "('" . $category_codes[$data['code']] . "', '" . $category_codes[$code] . "', '" . $level . "'),", $query_limit);
                    $parent_code = isset($codes[$key-1]) ? $codes[$key-1] : $data['parent_category_id'];
                    $level++;
                }
            }
            $top = (int)!($level - 1);
            $arr_codes[$data['code']] = $start_id;

            $this->set_query($queries['add']['category'], "('" . $category_codes[$data['code']] . "', '" . $data['image'] . "', '" . (isset($category_codes[$parent_code]) ? $category_codes[$parent_code] : (int)$data['parent_category_id']) . "', '" . $top . "', '" . (int)$data['column'] . "', '" . (int)$data['sort_order'] . "', '" . (int)$data['status'] . "', NOW() - INTERVAL 1 DAY, NOW(), '" . (int)$data['noindex'] . "'),", $query_limit);

            foreach ($data['category_description'] as $language_id => $value) {
                $this->set_query($queries['add']['category_description'], "('" . $category_codes[$data['code']] . "', '" . (int)$language_id . "', '" . $this->db->escape($value['name']) . "', '" . $this->db->escape($value['description']) . "', '" . $this->db->escape($value['meta_title']) . "', '" . $this->db->escape($value['meta_description']) . "', '" . $this->db->escape($value['meta_keyword']) . "', '" . $this->db->escape($value['meta_h1']) . "'),", $query_limit);
            }

            foreach ($data['category_store'] as $store_id) {
                $this->set_query($queries['add']['category_to_store'], "('" . $category_codes[$data['code']] . "', '" . (int)$store_id . "'),", $query_limit);
            }

            foreach ($data['category_seo_url'] as $store_id => $language) {
                foreach ($language as $language_id => $keyword) {
                    if (!empty($keyword)) {
                        $k = $this->db->escape($keyword);
                        if (isset($seo_urls[$k])) {
                            $seo_urls[$k]++;
                            $k = "{$k}-{$seo_urls[$k]}";
                        } else {
                            $seo_urls[$k] = 0;
                        }
                        $this->set_query($queries['add']['seo_url'], "('" . (int)$store_id . "', '" . (int)$language_id . "', 'category_id=" . $category_codes[$data['code']] . "', '" . $k . "'),", $query_limit);
                    }
                }
            }

            $this->set_query($queries['add']['category_code'], "('" . $category_codes[$data['code']] . "', '" . $this->db->escape($data['code']) . "'),", $query_limit);
        }

        foreach ($queries['delete'] as $k => $q) {
            if ($k == 'seo_url') {
                $queries['delete'][$k] .= implode("','category_id=", $category_codes) . "')";
            } else {
                $queries['delete'][$k] .= implode(",", $category_codes) . ")";
            }
            if ($debug) {
                echo $queries['delete'][$k] . "\n";
                echo "Size: " . strlen($queries['delete'][$k]) . "\n";
            }
            $this->db->query($queries['delete'][$k]);
        }

        foreach ($queries['add'] as $k => $q) {
            switch ($k) {
                case 'category': $this->set_query_ready($queries['add'][$k], "ON DUPLICATE KEY UPDATE `image` = IF(VALUES(`image`) = '', `image`, VALUES(`image`)), `parent_id` = VALUES(`parent_id`), `top` = VALUES(`top`), `column` = VALUES(`column`), `sort_order` = `sort_order`, `status` = VALUES(`status`), `date_added` = `date_added`, `date_modified` = NOW(), `noindex` = VALUES(`noindex`)"); break;
                case 'category_description': $this->set_query_ready($queries['add'][$k], "ON DUPLICATE KEY UPDATE `category_id` = VALUES(`category_id`), `language_id` = VALUES(`language_id`), `name` = " . ($data['keep_names'] ? "`name`" : "VALUES(`name`)") . ", `description` = " . ($data['keep_category_description'] ? "`description`" : "VALUES(`description`)") . ", `meta_title` = " . ($data['keep_meta'] ? "`meta_title`" : "VALUES(`meta_title`)") . ", `meta_description` = " . ($data['keep_meta'] ? "`meta_description`" : "VALUES(`meta_description`)") . ", `meta_keyword` = " . ($data['keep_meta'] ? "`meta_keyword`" : "VALUES(`meta_keyword`)") . ", `meta_h1` = " . ($data['keep_names'] ? "`meta_h1`" : "VALUES(`meta_h1`)")); break;
                case 'category_code': $this->set_query_ready($queries['add'][$k], "ON DUPLICATE KEY UPDATE `category_id` = VALUES(`category_id`)"); break;
                default: $this->set_query_ready($queries['add'][$k]); break;
            }

            if (isset($queries['add'][$k]['query_ready'])) {
                $this->exec_queries($queries['add'][$k]['query_ready'], $debug);
            }
        }

        $this->add_multiple_paths($arr);

        $this->cache->delete('category');
        
        if ($this->config->get('config_seo_pro')){      
            $this->cache->delete('seopro');
        }
    }

    public function delete_multiple_categories($arr_codes, $debug = false) {
        $category_codes = $this->get_category_codes();
        $queries = array(
            'category_query' => "DELETE FROM " . DB_PREFIX . "category WHERE category_id IN (",
            'category_description_query' => "DELETE FROM " . DB_PREFIX . "category_description WHERE category_id IN (",
            'category_filter_query' => "DELETE FROM " . DB_PREFIX . "category_filter WHERE category_id IN (",
            'category_to_store_query' => "DELETE FROM " . DB_PREFIX . "category_to_store WHERE category_id IN (",
            'category_path_query' => "DELETE FROM " . DB_PREFIX . "category_path WHERE category_id IN (",
            'seo_url_query' => "DELETE FROM " . DB_PREFIX . "seo_url WHERE query IN ('category_id=",
        );
        
        $delete_codes = array_diff_key($category_codes, $arr_codes);
        $delete_codes_string = implode(',', $delete_codes) . ")";
        if (!empty($delete_codes)) {
            if ($debug) {
                echo "Categories (" . $delete_codes_string . "are not present in JSON. Removing\n";
            }
            foreach ($queries as $key => $query) {
                if ($key == 'seo_url_query') {
                    $this->db->query($query . implode("','category_id=", $delete_codes) . "')");
                } else {
                    $this->db->query($query . $delete_codes_string);
                }
            }
        }

        $this->cache->delete('category');
        
        if ($this->config->get('config_seo_pro')){      
            $this->cache->delete('seopro');
        }
    }

    public function add_multiple_products($arr, $separate = false, $debug = false) {
        $query_limit = 1000000;

        $arr_codes = array_keys($arr);
        $product_arr_codes = array_flip($arr_codes);

        $product_start_id = $this->db->query("SELECT MAX(product_id) FROM " . DB_PREFIX . "product");
        $product_start_id = $product_start_id->row['MAX(product_id)'] == NULL ? 50 : (int)($product_start_id->row['MAX(product_id)'] + 1);
        $product_codes = $this->get_product_codes($arr_codes);

        $option_start_id = $this->db->query("SELECT MAX(option_id) FROM " . DB_PREFIX . "option");
        $option_start_id = $option_start_id->row['MAX(option_id)'] == NULL ? 1 : (int)($option_start_id->row['MAX(option_id)'] + 1);
        $option_arr_codes = array();
        $option_codes = $this->get_option_codes($arr_codes);

        $option_value_start_id = $this->db->query("SELECT MAX(option_value_id) FROM " . DB_PREFIX . "option_value");
        $option_value_start_id = $option_value_start_id->row['MAX(option_value_id)'] == NULL ? 1 : (int)($option_value_start_id->row['MAX(option_value_id)'] + 1);
        $option_value_arr_codes = array();
        $option_value_codes = $this->get_option_value_codes($arr_codes);

        $product_option_start_id = $this->db->query("SELECT MAX(product_option_id) FROM " . DB_PREFIX . "product_option");
        $product_option_start_id = $product_option_start_id->row['MAX(product_option_id)'] == NULL ? 1 : (int)($product_option_start_id->row['MAX(product_option_id)'] + 1);

        $product_option_value_start_id = $this->db->query("SELECT MAX(product_option_value_id) FROM " . DB_PREFIX . "product_option_value");
        $product_option_value_start_id = $product_option_value_start_id->row['MAX(product_option_value_id)'] == NULL ? 1 : (int)($product_option_value_start_id->row['MAX(product_option_value_id)'] + 1);

        $category_codes = $this->get_category_codes();

        // $this->db->query('DELETE FROM ' . DB_PREFIX . 'trade_import_product_codes WHERE 1');
        // $this->db->query('DELETE FROM ' . DB_PREFIX . 'trade_import_option_value_codes WHERE 1');
        // $this->db->query('DELETE FROM ' . DB_PREFIX . 'trade_import_option_codes WHERE 1');

        $queries = array(
            'option' => array(
                'delete' => array(
                    'delete_option' => "DELETE FROM " . DB_PREFIX . "option WHERE option_id IN (",
                    'delete_option_value' => "DELETE FROM " . DB_PREFIX . "option_value WHERE option_id IN (",
                    'delete_option_description' => "DELETE FROM " . DB_PREFIX . "option_description WHERE option_id IN (",
                    'delete_option_value_description' => "DELETE FROM " . DB_PREFIX . "option_value_description WHERE option_id IN (",
                    'delete_option_codes' => "DELETE FROM " . DB_PREFIX . "trade_import_option_codes WHERE option_id IN (",
                    'delete_option_value_codes' => "DELETE FROM " . DB_PREFIX . "trade_import_option_value_codes WHERE nomenclature_uuid IN ('"
                ),
                'add' => array(
                    'option' => array(
                        'query' => "INSERT INTO " . DB_PREFIX . "option(`option_id`, `type`, `sort_order`) VALUES ",
                        'data' => array(0 => ""),
                        'data_key' => 0
                    ),
                    'option_description' => array(
                        'query' => "INSERT INTO " . DB_PREFIX . "option_description VALUES ",
                        'data' => array(0 => ""),
                        'data_key' => 0
                    ),
                    'option_value' => array(
                        'query' => "INSERT INTO " . DB_PREFIX . "option_value(`option_value_id`, `option_id`, `image`, `image_serialized`, `sort_order`) VALUES ",
                        'data' => array(0 => ""),
                        'data_key' => 0
                    ),
                    'option_value_description' => array(
                        'query' => "INSERT INTO " . DB_PREFIX . "option_value_description VALUES ",
                        'data' => array(0 => ""),
                        'data_key' => 0
                    ),
                    'option_codes' => array(
                        'query' => "INSERT INTO " . DB_PREFIX . "trade_import_option_codes VALUES ",
                        'data' => array(0 => ""),
                        'data_key' => 0
                    ),
                    'option_value_codes' => array(
                        'query' => "INSERT INTO " . DB_PREFIX . "trade_import_option_value_codes VALUES ",
                        'data' => array(0 => ""),
                        'data_key' => 0
                    )
                )
            ),
            'product' => array(
                'delete' => array(
                    'delete_product_description_composition' => "DELETE FROM " . DB_PREFIX . "product_description_composition WHERE product_id IN (",
                    'delete_product_image' => "DELETE FROM " . DB_PREFIX . "product_image WHERE product_id IN (",
                    'delete_product_option' => "DELETE FROM " . DB_PREFIX . "product_option WHERE product_id IN (",
                    'delete_product_option_value' => "DELETE FROM " . DB_PREFIX . "product_option_value WHERE product_id IN (",
                    'delete_product_special' => "DELETE FROM " . DB_PREFIX . "product_special WHERE product_id IN (",
                    'delete_product_discount' => "DELETE FROM " . DB_PREFIX . "product_discount WHERE product_id IN (",
                    'delete_product_city' => "DELETE FROM " . DB_PREFIX . "product_city WHERE product_id IN (",
                    'delete_product_to_category' => "DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id IN (",
                    'delete_product_to_store' => "DELETE FROM " . DB_PREFIX . "product_to_store WHERE product_id IN (",
                    'delete_product_codes' => "DELETE FROM " . DB_PREFIX . "trade_import_product_codes WHERE product_id IN (",
                    'delete_seo_url' => "DELETE FROM " . DB_PREFIX . "seo_url WHERE query IN ('product_id="
                ),
                'add' => array(
                    'product' => array(
                        'query' => "INSERT INTO " . DB_PREFIX . "product(`product_id`, `model`, `sku`, `ean`, `quantity`, `stock_status_id`, `image`, `shipping`, `price`, `date_available`, `weight`, `weight_class_id`, `length`, `width`, `height`, `length_class_id`, `measure_name`, `subtract`, `minimum`, `sort_order`, `status`, `date_added`, `date_modified`, `noindex`) VALUES ",
                        'data' => array(0 => ""),
                        'data_key' => 0
                    ),
                    'product_description' => array(
                        'query' => "INSERT INTO " . DB_PREFIX . "product_description(`product_id`, `language_id`, `name`, `description`, `tag`, `meta_title`, `meta_description`, `meta_keyword`, `meta_h1`) VALUES ",
                        'data' => array(0 => ""),
                        'data_key' => 0
                    ),
                    'product_description_composition' => array(
                        'query' => "INSERT INTO " . DB_PREFIX . "product_description_composition VALUES ",
                        'data' => array(0 => ""),
                        'data_key' => 0
                    ),
                    'product_image' => array(
                        'query' => "INSERT INTO " . DB_PREFIX . "product_image VALUES ",
                        'data' => array(0 => ""),
                        'data_key' => 0
                    ),
                    'product_option' => array(
                        'query' => "INSERT INTO " . DB_PREFIX . "product_option VALUES ",
                        'data' => array(0 => ""),
                        'data_key' => 0
                    ),
                    'product_option_value' => array(
                        'query' => "INSERT INTO " . DB_PREFIX . "product_option_value VALUES ",
                        'data' => array(0 => ""),
                        'data_key' => 0
                    ),
                    'product_special' => array(
                        'query' => "INSERT INTO " . DB_PREFIX . "product_special VALUES ",
                        'data' => array(0 => ""),
                        'data_key' => 0
                    ),
                    'product_discount' => array(
                        'query' => "INSERT INTO " . DB_PREFIX . "product_discount VALUES ",
                        'data' => array(0 => ""),
                        'data_key' => 0
                    ),
                    'product_city' => array(
                        'query' => "INSERT INTO " . DB_PREFIX . "product_city VALUES ",
                        'data' => array(0 => ""),
                        'data_key' => 0
                    ),
                    'product_to_category' => array(
                        'query' => "INSERT INTO " . DB_PREFIX . "product_to_category VALUES ",
                        'data' => array(0 => ""),
                        'data_key' => 0
                    ),
                    'product_to_store' => array(
                        'query' => "INSERT INTO " . DB_PREFIX . "product_to_store VALUES ",
                        'data' => array(0 => ""),
                        'data_key' => 0
                    ),
                    'product_codes' => array(
                        'query' => "INSERT INTO " . DB_PREFIX . "trade_import_product_codes VALUES ",
                        'data' => array(0 => ""),
                        'data_key' => 0
                    ),
                    'seo_url' => array(
                        'query' => "INSERT INTO " . DB_PREFIX . "seo_url(`store_id`, `language_id`, `query`, `keyword`) VALUES ",
                        'data' => array(0 => ""),
                        'data_key' => 0
                    )
                )
            )
        );

        foreach ($arr as $data) {
            if (!isset($product_codes[$data['code']])) {
                if ($debug) {
                    echo 'Adding product ' . $data['product_description'][1]['name'] . "\n";
                }
                $product_codes[$data['code']] = $product_start_id;
                $product_start_id++;
            } else {
                if ($debug) {
                    echo $data['product_description'][1]['name'] . " product exists. Editing\n";
                }
            }
            $product_arr_codes[$data['code']] = $product_start_id;

            if (!empty($this->db->escape($data['created_at']))) {
                $d = new DateTime($this->db->escape($data['created_at']));
                $date = $d->format('Y-m-d');
                $datetime = $d->format('Y-m-d H:i:s');
            } else {
                $date = $datetime = "NOW() - INTERVAL 1 DAY";
            }
            

            $this->set_query($queries['product']['add']['product'], "('" . $product_codes[$data['code']] . "', '" . $this->db->escape($data['model']) . "', '" . $this->db->escape($data['sku']) . "', '" . (isset($data['ean']) ? $this->db->escape($data['ean']) : "") . "', '" . (int)$data['quantity'] . "', '" . (int)$data['stock_status_id'] . "', " . (isset($data['image']) ? ("'" . $this->db->escape($data['image']) . "'") : "''") . ", '" . (int)$data['shipping'] . "', '" . (float)$data['price'] . "', '" . $date . "', '" . (float)$data['weight'] . "', '" . (int)$data['weight_class_id'] . "', '" . (int)$data['length'] . "', '" . (int)$data['width'] . "', '" . (int)$data['height'] . "', '" . (int)$data['length_class_id'] . "', '" . $this->db->escape($data['measure_name']) . "', '" . (int)$data['subtract'] . "', '" . (int)$data['minimum'] . "', '" . (int)$data['sort_order'] . "', '" . (int)$data['status'] . "', '" . $datetime . "', '" . $datetime . "', '" . (int)$data['noindex'] . "'),", $query_limit);

            foreach ($data['product_description'] as $language_id => $value) {
                $this->set_query($queries['product']['add']['product_description'], "('" . $product_codes[$data['code']] . "', '" . (int)$language_id . "', '" . $this->db->escape($value['name']) . "', '" . $this->db->escape($value['description']) . "', '" . $this->db->escape($value['tag']) . "', '" . $this->db->escape($value['meta_title']) . "', '" . $this->db->escape($value['meta_description']) . "', '" . $this->db->escape($value['meta_keyword']) . "', '" . $this->db->escape($value['meta_h1']) . "'),", $query_limit);
            }

            foreach ($data['product_description_composition'] as $language_id => $value) {
                $this->set_query($queries['product']['add']['product_description_composition'], "('" . $product_codes[$data['code']] . "', '" . (int)$language_id . "', '" . $this->db->escape($value['composition']) . "'),", $query_limit);
            }

            foreach ($data['additional_image'] as $image) {
                $this->set_query($queries['product']['add']['product_image'], "('', '" . $product_codes[$data['code']] . "', '" . $this->db->escape($image) . "', 0),", $query_limit);
            }


            if (isset($data['product_special'])) {
                foreach ($data['product_special'] as $product_special) {
                    $this->set_query($queries['product']['add']['product_special'], "('', '" . $product_codes[$data['code']] . "', '" . (int)$product_special['customer_group_id'] . "', '" . (int)$product_special['priority'] . "', '" . (float)$product_special['price'] . "', '" . (float)$product_special['discount'] . "', '" . $this->db->escape($product_special['date_start']) . "', '" . $this->db->escape($product_special['date_end']) . "' + INTERVAL 1 DAY),", $query_limit);
                }
            }

            foreach ($data['product_store'] as $store_id) {
                $this->set_query($queries['product']['add']['product_to_store'], "('" . $product_codes[$data['code']] . "', '" . (int)$store_id . "'),", $query_limit);
            }

            if (isset($data['product_city'])) {
                foreach ($data['product_city'] as $product_city) {
                    $this->set_query($queries['product']['add']['product_discount'], "('', '" . $product_codes[$data['code']] . "', '" . (int)$product_city['product_option_value_id'] . "', '" . (int)$product_city['city_id'] . "', '1', '1', '" . (float)$product_city['price'] . "', DEFAULT, DEFAULT),", $query_limit);
                }
            }

            if ($data['parent_category_id']) {
                $this->set_query($queries['product']['add']['product_to_category'], "('" . $product_codes[$data['code']] . "', '" . (int)$data['parent_category_id'] . "', '" . ($data['product_category'] ? 0 : 1) . "'),", $query_limit);
            }

            if ($data['product_category']) {
                $product_category = explode("/", $data['product_category']);
                foreach ($product_category as $category_key => $category_code) {
                    if (!in_array($category_code, $data['parent_category_code'])) {
                        $this->set_query($queries['product']['add']['product_to_category'], "('" . $product_codes[$data['code']] . "', '" . $category_codes[$category_code] . "', '" . (isset($product_category[$category_key + 1]) ? 0 : 1) . "'),", $query_limit);
                    }
                }
            }

            if (!$separate) {
                if (!empty($data['option_data'])) {
                    foreach ($data['option_data'] as $option_data) {
                        if (!isset($option_codes[$data['code']])) {
                            if ($debug) {
                                echo 'Adding options for product ' . $data['product_description'][1]['name'] . "\n";
                            }
                            $option_codes[$data['code']] = $option_start_id;
                            $option_start_id++;
                        } else {
                            if ($debug) {
                                echo 'Editing options for product ' . $data['product_description'][1]['name'] . "\n";
                            }
                        }
                        $option_arr_codes[$data['code']] = $option_start_id;

                        $this->set_query($queries['option']['add']['option'], "('" . $option_codes[$data['code']] . "', '" . $this->db->escape($option_data['type']) . "', '" . (int)$option_data['sort_order'] . "'),", $query_limit);

                        foreach ($option_data['option_description'] as $language_id => $value) {
                            $this->set_query($queries['option']['add']['option_description'], "('" . $option_codes[$data['code']] . "', '" . (int)$language_id . "', '" . $this->db->escape($value['name']) . "'),", $query_limit);
                        }

                        $this->set_query($queries['option']['add']['option_codes'], "('" . $option_codes[$data['code']] . "', '" . $data['code'] . "'),", $query_limit);

                        $this->set_query($queries['product']['add']['product_option'], "('" . $option_codes[$data['code']] . "', '" . $product_codes[$data['code']] . "', '" . $option_codes[$data['code']] . "', '', '" . $data['product_option'][0]['required'] . "'),", $query_limit);

                        foreach ($option_data['option_value'] as $option_key => $option_value) {
                            if (!isset($option_value_codes[$data['code']][$option_value['code']])) {
                                $option_value_codes[$data['code']][$option_value['code']] = $option_value_start_id;
                                $option_value_start_id++;
                            }

                            $this->set_query($queries['option']['add']['option_value'], "('" . $option_value_codes[$data['code']][$option_value['code']] . "', '" . $option_codes[$data['code']] . "', '" . (isset($option_value['image']) ? $this->db->escape($option_value['image']) : "") . "', '1', '" . (int)$option_value['sort_order'] . "'),", $query_limit);

                            foreach ($option_value['option_value_description'] as $language_id => $option_value_description) {
                                $this->set_query($queries['option']['add']['option_value_description'], "('" . $option_value_codes[$data['code']][$option_value['code']] . "', '" . (int)$language_id . "', '" . $option_codes[$data['code']] . "', '" . $this->db->escape($option_value_description['name']) . "'),", $query_limit);
                            }

                            $this->set_query($queries['option']['add']['option_value_codes'], "('" . $option_value_codes[$data['code']][$option_value['code']] . "', '" . $option_value['code'] . "', '" . $data['code'] . "'),", $query_limit);

                            $this->set_query($queries['product']['add']['product_option_value'], "('" . $option_value_codes[$data['code']][$option_value['code']] . "', '" . $option_codes[$data['code']] . "', '" . $product_codes[$data['code']] . "', '" . $option_codes[$data['code']] . "', '" . $option_value_codes[$data['code']][$option_value['code']] . "', '" . (int)$data['product_option'][0]['product_option_value'][$option_key]['quantity'] . "', '" . (int)$data['product_option'][0]['product_option_value'][$option_key]['subtract'] . "', '" . (float)$data['product_option'][0]['product_option_value'][$option_key]['price'] . "', '" . $this->db->escape($data['product_option'][0]['product_option_value'][$option_key]['price_prefix']) . "', '" . (float)$data['product_option'][0]['product_option_value'][$option_key]['discount'] . "', '" . (float)$data['product_option'][0]['product_option_value'][$option_key]['price_old'] . "', '', '" . $this->db->escape($data['product_option'][0]['product_option_value'][$option_key]['points_prefix']) . "', '', '" . $this->db->escape($data['product_option'][0]['product_option_value'][$option_key]['weight_prefix']) . "'),", $query_limit);
                            if (isset($data['product_option'][0]['product_option_value'][$option_key]['product_city'])) {
                                foreach ($data['product_city'] as $product_city) {
                                    $this->set_query($queries['product']['add']['product_discount'], "('', '" . $product_codes[$data['code']] . "', '" . (int)$option_value_codes[$data['code']][$option_value['code']] . "', '" . (int)$product_city['city_id'] . "', '1', '1', '" . (float)$product_city['price'] . "', DEFAULT, DEFAULT),", $query_limit);
                                }
                            }
                        }

                        // foreach ($data['product_option'] as $product_option) {
                        //     $this->set_query($queries['product']['add']['product_option'], "('" . $option_codes[$data['code']] . "', '" . $product_codes[$data['code']] . "', '" . $option_codes[$data['code']] . "', '', '" . $product_option['required'] . "'),", $query_limit);

                        //     foreach ($product_option['product_option_value'] as $product_option_value) {
                        //         $this->set_query($queries['product']['add']['product_option_value'], "('" . $product_option_value_start_id . "', '" . $product_option_start_id . "', '" . $product_codes[$data['code']] . "', '" . $option_codes[$data['code']] . "', '" . $option_value_codes[$data['code']][$this->db->escape($product_option_value['code'])] . "', '" . (int)$product_option_value['quantity'] . "', '" . (int)$product_option_value['subtract'] . "', '" . (float)$product_option_value['price'] . "', '" . $this->db->escape($product_option_value['price_prefix']) . "', '" . (float)$product_option_value['discount'] . "', '" . (float)$product_option_value['price_old'] . "', '', '" . $this->db->escape($product_option_value['points_prefix']) . "', '', '" . $this->db->escape($product_option_value['weight_prefix']) . "'),", $query_limit);
                        //         if (isset($product_option_value['product_city'])) {
                        //             foreach ($data['product_city'] as $product_city) {
                        //                 $this->set_query($queries['product']['add']['product_discount'], "('', '" . $product_codes[$data['code']] . "', '" . (int)$product_option_value_start_id . "', '" . (int)$product_city['city_id'] . "', '1', '1', '" . (float)$product_city['price'] . "', DEFAULT, DEFAULT),", $query_limit);
                        //             }
                        //         }
                        //         $product_option_value_start_id++;
                        //     }
                        //     $product_option_start_id++;
                        // }
                    }
                }
            } else {
                if (!empty($data['option_data'])) {
                    foreach ($data['option_data'][0]['option_value'] as $option_data) {
                        if (!isset($product_codes[$option_data['code']])) {
                            if ($debug) {
                                echo 'Adding product ' . $option_data['name'] . "\n";
                            }
                            $product_codes[$option_data['code']] = $product_start_id;
                            $product_start_id++;
                        } else {
                            if ($debug) {
                                echo $option_data['name'] . " product exists. Editing\n";
                            }
                        }
                        $product_arr_codes[$option_data['code']] = $product_start_id;

                        $this->set_query($queries['product']['add']['product'], "('" . $product_codes[$option_data['code']] . "', '" . $this->db->escape($data['model']) . "', '" . $this->db->escape($data['sku']) . "', '" . (isset($data['ean']) ? $this->db->escape($data['ean']) : "") . "', '" . (int)$option_data['quantity'] . "', '" . (int)$data['stock_status_id'] . "', " . (isset($data['image']) ? ("'" . $this->db->escape($data['image']) . "'") : "''") . ", '" . (int)$data['shipping'] . "', '" . (float)$option_data['price'] . "', '" . $date . "', '" . $this->db->escape($data['measure_name']) . "', '" . (int)$data['subtract'] . "', '" . (int)$data['minimum'] . "', '" . (int)$data['sort_order'] . "', '" . (int)$option_data['status'] . "', '" . $datetime . "', '" . $datetime . "',, '" . (int)$data['noindex'] . "'),", $query_limit);

                        foreach ($option_data['product_description'] as $language_id => $value) {
                           $this->set_query($queries['product']['add']['product_description'], "('" . $product_codes[$option_data['code']] . "', '" . (int)$language_id . "', '" . $this->db->escape($value['name']) . "', '" . $this->db->escape($value['description']) . "', '" . $this->db->escape($value['tag']) . "', '" . $this->db->escape($value['meta_title']) . "', '" . $this->db->escape($value['meta_description']) . "', '" . $this->db->escape($value['meta_keyword']) . "', '" . $this->db->escape($value['meta_h1']) . "'),", $query_limit);
                        }

                        if (isset($option_data['product_special'])) {
                            foreach ($option_data['product_special'] as $product_special) {
                               $this->set_query($queries['product']['add']['product_special'], "('', '" . $product_codes[$option_data['code']] . "', '" . (int)$product_special['customer_group_id'] . "', '" . (int)$product_special['priority'] . "', '" . (float)$product_special['price'] . "', '" . $this->db->escape($product_special['date_start']) . "', '" . $this->db->escape($product_special['date_end']) . "' + INTERVAL 1 DAY),", $query_limit);
                            }
                        }

                        foreach ($data['product_store'] as $store_id) {
                            $this->set_query($queries['product']['add']['product_to_store'], "('" . $product_codes[$option_data['code']] . "', '" . (int)$store_id . "'),", $query_limit);
                        }

                        foreach (explode("/", $data['product_category']) as $category_key => $category_code) {
                            if (!in_array($category_code, $data['parent_category_code'])) {
                                $this->set_query($queries['product']['add']['product_to_category'], "('" . $product_codes[$option_data['code']] . "', '" . $category_codes[$category_code] . "', '" . (int)!$category_key . "'),", $query_limit);
                            }
                        }

                        foreach ($option_data['product_seo_url'] as $store_id => $language) {
                            foreach ($language as $language_id => $keyword) {
                                if (!empty($keyword)) {
                                    $this->set_query($queries['product']['add']['seo_url'], "('" . (int)$store_id . "', '" . (int)$language_id . "', 'product_id=" . $product_codes[$option_data['code']] . "', '" . implode("-", array($this->db->escape($keyword), $product_codes[$option_data['code']])) . "'),", $query_limit);
                                }
                            }
                        }

                        $this->set_query($queries['product']['add']['product_codes'], "('" . $product_codes[$option_data['code']] . "', '" . $option_data['code'] . "', '" . $data['group_code'] . "'),", $query_limit);
                    }
                }
            }

            foreach ($data['product_seo_url'] as $store_id => $language) {
                foreach ($language as $language_id => $keyword) {
                    if (!empty($keyword)) {
                        $this->set_query($queries['product']['add']['seo_url'], "('" . (int)$store_id . "', '" . (int)$language_id . "', 'product_id=" . $product_codes[$data['code']] . "', '" . implode("-", array($this->db->escape($keyword), $product_codes[$data['code']])) . "'),", $query_limit);
                    }
                }
            }

            $this->set_query($queries['product']['add']['product_codes'], "('" . $product_codes[$data['code']] . "', '" . $data['code'] . "', '" . $data['group_code'] . "'),", $query_limit);
        }

        if (!empty($option_codes)) {
            $update_option_codes = $option_codes;
            $update_option_codes_string = implode(',', $update_option_codes) . ")";
            $update_option_value_codes_string = implode("','", array_keys($update_option_codes)) . "')";
            foreach ($queries['option']['delete'] as $key => $query) {
                $queries['option']['delete'][$key] .= $key == 'delete_option_value_codes' ? $update_option_value_codes_string : $update_option_codes_string;
            }
        } else {
            unset($queries['option']);
        }

        if (isset($queries['option']['add'])) {
            foreach ($queries['option']['add'] as $k => $q) {
                switch ($k) {
                    case 'option': $this->set_query_ready($queries['option']['add'][$k], "ON DUPLICATE KEY UPDATE `option_id` = VALUES(`option_id`), `type` = VALUES(`type`), `sort_order` = VALUES(`sort_order`)"); break;
                    case 'option_value': $this->set_query_ready($queries['option']['add'][$k], "ON DUPLICATE KEY UPDATE `option_value_id` = VALUES(`option_value_id`), `option_id` = VALUES(`option_id`), `image` = VALUES(`image`), `sort_order` = VALUES(`sort_order`)"); break;
                    default: $this->set_query_ready($queries['option']['add'][$k]); break;
                }
            }
        }

        $update_product_codes = $product_codes;
        $update_product_codes_string = implode(',', $update_product_codes) . ")";
        $update_product_codes_seo_string = implode("','product_id=", $update_product_codes) . "')";
        foreach ($queries['product']['delete'] as $key => $query) {
            $queries['product']['delete'][$key] .= $key == 'delete_seo_url' ? $update_product_codes_seo_string : $update_product_codes_string;
        }

        foreach ($queries['product']['add'] as $k => $q) {
            switch ($k) {
                case 'product': $this->set_query_ready($queries['product']['add'][$k], "ON DUPLICATE KEY UPDATE `product_id` = VALUES(`product_id`), `model` = VALUES(`model`), `sku` = VALUES(`sku`), `ean` = VALUES(`ean`), `quantity` = VALUES(`quantity`), `stock_status_id` = VALUES(`stock_status_id`), `image` = IF(VALUES(`image`) = '', `image`, VALUES(`image`)), `shipping` = VALUES(`shipping`), `price` = VALUES(`price`), `date_available` = `date_available`, `weight` = VALUES(`weight`), `weight_class_id` = VALUES(`weight_class_id`), `length` = VALUES(`length`), `width` = VALUES(`width`), `height` = VALUES(`height`), `length_class_id` = VALUES(`length_class_id`), `measure_name` = VALUES(`measure_name`), `subtract` = VALUES(`subtract`), `minimum` = VALUES(`minimum`), `sort_order` = VALUES(`sort_order`), `status` = VALUES(`status`), `date_added` = VALUES(`date_added`), `date_modified` = VALUES(`date_modified`), `noindex` = VALUES(`noindex`)"); break;
                case 'product_description': $this->set_query_ready($queries['product']['add'][$k], "ON DUPLICATE KEY UPDATE `product_id` = VALUES(`product_id`), `language_id` = VALUES(`language_id`), `name` = " . ($data['keep_names'] ? "`name`" : "VALUES(`name`)") . ", `description` = " . ($data['keep_product_description'] ? "`description`" : "VALUES(`description`)") . ", `tag` = " . ($data['keep_meta'] ? "`tag`" : "VALUES(`tag`)") . ", `meta_title` = " . ($data['keep_meta'] ? "`meta_title`" : "VALUES(`meta_title`)") . ", `meta_description` = " . ($data['keep_meta'] ? "`meta_description`" : "VALUES(`meta_description`)") . ", `meta_keyword` = " . ($data['keep_meta'] ? "`meta_keyword`" : "VALUES(`meta_keyword`)") . ", `meta_h1` = " . ($data['keep_names'] ? "`meta_h1`" : "VALUES(`meta_h1`)")); break;
                default: $this->set_query_ready($queries['product']['add'][$k]); break;
            }
        }

        foreach ($queries as $query) {
            foreach ($query as $k => $q) {
                if ($k == 'delete') {
                    $this->exec_queries($q, $debug);
                } else {
                    foreach ($q as $a) {
                        if (isset($a['query_ready'])) {
                            $this->exec_queries($a['query_ready'], $debug);
                        }
                    }
                }
            }
        }

        $this->cache->delete('product');
        $this->cache->delete('category');
    
        if($this->config->get('config_seo_pro')){       
            $this->cache->delete('seopro');
        }
    }

    public function delete_multiple_products($arr, $debug = false) {

        $product_codes = $this->get_product_codes();
        $queries = array(
            'option' => array(
                'delete' => array(
                    'delete_option' => "DELETE FROM " . DB_PREFIX . "option WHERE option_id IN (",
                    'delete_option_value' => "DELETE FROM " . DB_PREFIX . "option_value WHERE option_id IN (",
                    'delete_option_description' => "DELETE FROM " . DB_PREFIX . "option_description WHERE option_id IN (",
                    'delete_option_value_description' => "DELETE FROM " . DB_PREFIX . "option_value_description WHERE option_id IN (",
                )
            ),
            'product' => array(
                'delete' => array(
                    'delete_product' => "DELETE FROM " . DB_PREFIX . "product WHERE product_id IN (",
                    'delete_product_description' => "DELETE FROM " . DB_PREFIX . "product_description WHERE product_id IN (",
                    'delete_product_description_composition' => "DELETE FROM " . DB_PREFIX . "product_description_composition WHERE product_id IN (",
                    'delete_product_option' => "DELETE FROM " . DB_PREFIX . "product_option WHERE product_id IN (",
                    'delete_product_option_value' => "DELETE FROM " . DB_PREFIX . "product_option_value WHERE product_id IN (",
                    'delete_product_special' => "DELETE FROM " . DB_PREFIX . "product_special WHERE product_id IN (",
                    'delete_product_to_category' => "DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id IN (",
                    'delete_product_to_store' => "DELETE FROM " . DB_PREFIX . "product_to_store WHERE product_id IN (",
                    'delete_seo_url' => "DELETE FROM " . DB_PREFIX . "seo_url WHERE query IN ('product_id="
                )
            ),
        );

        $delete_product_codes = array_diff_key($product_codes, $arr);
        $delete_product_codes_string = implode(',', $delete_product_codes) . ")";
        $delete_product_codes_seo_string = implode("','product_id=", $delete_product_codes) . "')";
        if (!empty($delete_product_codes)) {
            if ($debug) {
                echo "Products (" . $delete_product_codes_string . "are not present in JSON. Removing\n"; 
            }
            foreach ($queries['product']['delete'] as $key => $query) {
                $query .= $key == 'delete_seo_url' ? $delete_product_codes_seo_string : $delete_product_codes_string;
                $this->db->query($query);
            }

            $delete_option_codes = $this->get_option_codes(array_keys($delete_product_codes));
            $delete_option_codes_string = implode(',', $delete_option_codes) . ")";
            if (!empty($delete_option_codes)) {
                foreach ($queries['option']['delete'] as $key => $query) {
                    $query .= $delete_option_codes_string;
                    $this->db->query($query);
                }
            }
        }
    }

    public function add_multiple_option_characteristic($arr, $debug = false) {
        $query_limit = 1000000;
        $option_characteristic_codes = $this->get_option_characteristic_codes();
        if (!empty($option_characteristic_codes)) {
            $this->db->query('DELETE FROM ' . DB_PREFIX . 'option_characteristic WHERE characteristic_id IN (' . implode(",", $option_characteristic_codes) . ')');
            $this->db->query('DELETE FROM ' . DB_PREFIX . 'option_characteristic_description WHERE characteristic_id IN (' . implode(",", $option_characteristic_codes) . ')');
            $this->db->query('DELETE FROM ' . DB_PREFIX . 'option_value_characteristic WHERE characteristic_id IN (' . implode(",", $option_characteristic_codes) . ')');
        }
        $option_characteristic_codes = array();

        $option_characteristic_start_id = $this->db->query("SELECT MAX(characteristic_id) FROM " . DB_PREFIX . "option_characteristic");
        $option_characteristic_start_id = $option_characteristic_start_id->row['MAX(characteristic_id)'] == NULL ? 1 : (int)($option_characteristic_start_id->row['MAX(characteristic_id)'] + 1);

        $option_value_characteristic_start_id = $this->db->query("SELECT MAX(option_value_characteristic_id) FROM " . DB_PREFIX . "option_value_characteristic");
        $option_value_characteristic_start_id = $option_value_characteristic_start_id->row['MAX(option_value_characteristic_id)'] == NULL ? 1 : (int)($option_value_characteristic_start_id->row['MAX(option_value_characteristic_id)'] + 1);

        $option_codes = $this->get_option_codes();
        $option_value_codes = $this->get_option_value_codes();

        $queries = array(
            'add' => array(
                'option_characteristic' => array(
                    'query' => "INSERT INTO " . DB_PREFIX . "option_characteristic(`characteristic_id`, `type`, `sort_order`) VALUES ",
                    'data' => array(0 => ""),
                    'data_key' => 0
                ),
                'option_characteristic_description' => array(
                    'query' => "INSERT INTO " . DB_PREFIX . "option_characteristic_description VALUES ",
                    'data' => array(0 => ""),
                    'data_key' => 0
                ),
                'option_characteristic_codes' => array(
                    'query' => "INSERT INTO " . DB_PREFIX . "trade_import_option_characteristic_codes VALUES ",
                    'data' => array(0 => ""),
                    'data_key' => 0
                ),
                'option_value_characteristic' => array(
                    'query' => "INSERT INTO " . DB_PREFIX . "option_value_characteristic VALUES ",
                    'data' => array(0 => ""),
                    'data_key' => 0
                )
            )
        );

        if (!empty($arr)) {
            foreach ($arr as $code => $data) {
                if (!isset($option_characteristic_codes[$code])) {
                    if ($debug) {
                        echo 'Adding option characteristic ' . $data['name'] . "\n";
                    }
                    $option_characteristic_codes[$code] = $option_characteristic_start_id;
                    $option_characteristic_start_id++;
                }

                $this->set_query($queries['add']['option_characteristic'], "('" . $option_characteristic_codes[$code] . "', '" . $this->db->escape($data['type']) . "', '0'),", $query_limit);
                $this->set_query($queries['add']['option_characteristic_description'], "('" . $option_characteristic_codes[$code] . "', '1', '" . $this->db->escape($data['name']) . "'),", $query_limit);
                $this->set_query($queries['add']['option_characteristic_codes'], "('" . $option_characteristic_codes[$code] . "', '" . $code . "'),", $query_limit);

                if (isset($data['option_value'])) {
                    foreach ($data['option_value'] as $nomenclature_uuid => $option_value) {
                        foreach ($option_value as $characteristic_uuid => $property) {
                            if ($data['type'] == 'colors') {
                                $this->set_query($queries['add']['option_value_characteristic'], "('" . $option_value_characteristic_start_id++ . "', '" . $option_value_codes[$nomenclature_uuid][$characteristic_uuid] . "', '" . $option_codes[$nomenclature_uuid] . "', '" . $option_characteristic_codes[$code] . "', '" . json_encode($property['properties'], JSON_UNESCAPED_UNICODE) . "', '1', '" . (!empty($property['description']) ? json_encode($property['description'], JSON_UNESCAPED_UNICODE) : NULL) . "'),", $query_limit);
                            } else {
                                $this->set_query($queries['add']['option_value_characteristic'], "('" . $option_value_characteristic_start_id++ . "', '" . $option_value_codes[$nomenclature_uuid][$characteristic_uuid] . "', '" . $option_codes[$nomenclature_uuid] . "', '" . $option_characteristic_codes[$code] . "', '" . $this->db->escape($property['properties']) . "', '0', '" . (!empty($property['description']) ? json_encode($property['description'], JSON_UNESCAPED_UNICODE) : NULL) . "'),", $query_limit);
                            }
                        }
                    }
                }
            }

            foreach ($queries['add'] as $k => $q) {
                $this->set_query_ready($queries['add'][$k]);
                if (isset($queries['add'][$k]['query_ready'])) {
                    $this->exec_queries($queries['add'][$k]['query_ready'], $debug);
                }
            }
        }
    }

    public function add_multiple_warehouses($arr, $debug = false) {
        $query_limit = 1000000;
        $warehouse_codes = $this->get_warehouse_codes();
        $product_codes = $this->get_product_codes();
        $option_value_codes = $this->get_option_value_codes(array_keys($product_codes));
        if (!empty($warehouse_codes)) {
             $this->db->query('DELETE FROM ' . DB_PREFIX . 'warehouse WHERE warehouse_id IN (' . implode(",", $warehouse_codes) . ')');
            $this->db->query('DELETE FROM ' . DB_PREFIX . 'warehouse_description WHERE warehouse_id IN (' . implode(",", $warehouse_codes) . ')');
            $this->db->query('DELETE FROM ' . DB_PREFIX . 'warehouse_product WHERE warehouse_id IN (' . implode(",", $warehouse_codes) . ')');
        }
        $warehouse_codes = array();
        $warehouse_start_id = $this->db->query("SELECT MAX(warehouse_id) FROM " . DB_PREFIX . "warehouse");
        $warehouse_start_id = $warehouse_start_id->row['MAX(warehouse_id)'] == NULL ? 1 : (int)($warehouse_start_id->row['MAX(warehouse_id)'] + 1);

        $warehouse_product_start_id = $this->db->query("SELECT MAX(warehouse_product_id) FROM " . DB_PREFIX . "warehouse_product");
        $warehouse_product_start_id = $warehouse_product_start_id->row['MAX(warehouse_product_id)'] == NULL ? 1 : (int)($warehouse_product_start_id->row['MAX(warehouse_product_id)'] + 1);

        $queries = array(
            'add' => array(
                'warehouse' => array(
                    'query' => "INSERT INTO " . DB_PREFIX . "warehouse(`warehouse_id`, `sort_order`, `working_hours`) VALUES ",
                    'data' => array(0 => ""),
                    'data_key' => 0
                ),
                'warehouse_description' => array(
                    'query' => "INSERT INTO " . DB_PREFIX . "warehouse_description VALUES ",
                    'data' => array(0 => ""),
                    'data_key' => 0
                ),
                'warehouse_codes' => array(
                    'query' => "INSERT INTO " . DB_PREFIX . "trade_import_warehouse_codes VALUES ",
                    'data' => array(0 => ""),
                    'data_key' => 0
                ),
                'warehouse_product' => array(
                    'query' => "INSERT INTO " . DB_PREFIX . "warehouse_product VALUES ",
                    'data' => array(0 => ""),
                    'data_key' => 0
                )
            )
        );

        if (!empty($arr)) {
            foreach ($arr as $code => $data) {
                if (!isset($warehouse_codes[$code])) {
                    if ($debug) {
                        echo 'Adding warehouse ' . $data['name'] . "\n";
                    }
                    $warehouse_codes[$code] = $warehouse_start_id;
                    $warehouse_start_id++;
                }

                $this->set_query($queries['add']['warehouse'], "('" . $warehouse_codes[$code] . "', '0', '" . $data['working_hours'] . "'),", $query_limit);
                $this->set_query($queries['add']['warehouse_description'], "('" . $warehouse_codes[$code] . "', '1', '" . $this->db->escape($data['name']) . "', '" . $this->db->escape($data['address']) . "'),", $query_limit);
                $this->set_query($queries['add']['warehouse_codes'], "('" . $warehouse_codes[$code] . "', '" . $code . "'),", $query_limit);

                if (isset($data['products'])) {
                    foreach ($data['products'] as $product) {
                        if ($debug) {
                            echo 'Adding product ' . $product['name'] . ' for warehouse ' . $data['name'] . "\n";
                        }
                        $this->set_query($queries['add']['warehouse_product'], "('" . $warehouse_product_start_id . "', '" . $warehouse_codes[$code] . "', '" . $product_codes[$product['nomenclature_uuid']] . "', '" . (isset($option_value_codes[$product['nomenclature_uuid']][$product['characteristic_uuid']]) ? $option_value_codes[$product['nomenclature_uuid']][$product['characteristic_uuid']] : 'NULL') . "', '" . $product['quantity'] . "'),", $query_limit);
                        $warehouse_product_start_id++;
                    }
                }
            }

            foreach ($queries['add'] as $k => $q) {
                $this->set_query_ready($queries['add'][$k]);
                if (isset($queries['add'][$k]['query_ready'])) {
                    $this->exec_queries($queries['add'][$k]['query_ready'], $debug);
                }
            }
        }
    }

    public function add_multiple_services($arr, $debug = false) {
        $query_limit = 1000000;
        $services_codes = $this->get_services_codes();
        if (!empty($services_codes)) {
            $this->db->query('DELETE FROM ' . DB_PREFIX . 'trade_import_services WHERE id IN (' . implode(",", $services_codes) . ')');
        }
        $services_codes = array();
        $services_start_id = $this->db->query("SELECT MAX(id) FROM " . DB_PREFIX . "trade_import_services");
        $services_start_id = $services_start_id->row['MAX(id)'] == NULL ? 1 : (int)($services_start_id->row['MAX(id)'] + 1);

        $queries = array(
            'add' => array(
                'services' => array(
                    'query' => "INSERT INTO " . DB_PREFIX . "trade_import_services(`id`, `service_uuid`, `name`) VALUES ",
                    'data' => array(0 => ""),
                    'data_key' => 0
                )
            )
        );

        if (!empty($arr)) {
            foreach ($arr as $code => $data) {
                if (!isset($services_codes[$code])) {
                    if ($debug) {
                        echo 'Adding service ' . $data['name'] . "\n";
                    }
                    $services_codes[$code] = $services_start_id++;
                }

                $this->set_query($queries['add']['services'], "('" . $services_codes[$code] . "', '" . $code . "', '" . $this->db->escape($data['name']) . "'),", $query_limit);
            }

            foreach ($queries['add'] as $k => $q) {
                $this->set_query_ready($queries['add'][$k]);
                if (isset($queries['add'][$k]['query_ready'])) {
                    $this->exec_queries($queries['add'][$k]['query_ready'], $debug);
                }
            }
        }
    }

    public function add_image($arr, $debug = false) {
        if ($debug) {
            echo "Adding image " . $arr['image_id'] . "\n";
        }
        $this->db->query("INSERT INTO " . DB_PREFIX . "trade_images (`id`, `image_id`, `path`, `hash`, `size`) VALUES ('" . (isset($arr['id']) ? $arr['id'] : NULL) . "', '" . (int) $arr['image_id'] . "', '" . $this->db->escape($arr['path']) . "', '" . $this->db->escape($arr['hash']) . "', '" . (int) $arr['size'] . "') ON DUPLICATE KEY UPDATE `id` = VALUES(`id`), `image_id` = VALUES(`image_id`), `path` = VALUES(`path`), `hash` = VALUES(`hash`), `size` = VALUES(`size`)");
    }

    public function add_multiple_images($arr, $debug = false) {
        $query_limit = 1000000;
        $images = $this->get_images();
        $start_id = $this->db->query("SELECT MAX(id) FROM " . DB_PREFIX . "trade_images");
        $start_id = $start_id->row['MAX(id)'] == NULL ? 1 : (int)($start_id->row['MAX(id)'] + 1);

        $queries = array(
            'add' => array(
                'image' => array(
                    'query' => "INSERT INTO " . DB_PREFIX . "trade_images (`id`, `image_id`, `path`, `hash`, `size`) VALUES ",
                    'data' => array(0 => ""),
                    'data_key' => 0
                )
            )
        );

        if (!empty($arr)) {
            if ($debug) {
                echo "Adding multiple images\n";
            }
            foreach ($arr as $code => $data) {
                $this->set_query($queries['add']['image'], "('" . (isset($images[$data['image_id']]) ? (int)$images[$data['image_id']]['id'] : $start_id++) . "', '" . (int)$data['image_id'] . "', '" . $this->db->escape($data['path']) . "', '" . $this->db->escape($data['hash']) . "', '" . (int)$data['size'] . "'),", $query_limit);
            }

            foreach ($queries['add'] as $k => $q) {
                switch ($k) {
                    case 'image': $this->set_query_ready($queries['add'][$k], "ON DUPLICATE KEY UPDATE `id` = VALUES(`id`), `image_id` = VALUES(`image_id`), `path` = VALUES(`path`), `hash` = VALUES(`hash`), `size` = VALUES(`size`)"); break;
                    default: $this->set_query_ready($queries['add'][$k]); break;
                }
                if (isset($queries['add'][$k]['query_ready'])) {
                    $this->exec_queries($queries['add'][$k]['query_ready'], $debug);
                }
            }
        }
    }

    public function add_multiple_stocks($arr, $debug = false) {
        $query_limit = 1000000;
        $stocks_start_id = $this->db->query("SELECT MAX(stocks_id) FROM " . DB_PREFIX . "stocks");
        $stocks_start_id = $stocks_start_id->row['MAX(stocks_id)'] == NULL ? 1 : (int)($stocks_start_id->row['MAX(stocks_id)'] + 1);
        $banner_codes = $this->get_stocks_banner_ids();
        if (!empty($banner_codes)) {
             foreach ($banner_codes as $codes) {
                $this->db->query('DELETE FROM ' . DB_PREFIX . 'banner_image WHERE banner_image_id IN (' . implode(",", $codes) . ')');
                $this->db->query('DELETE FROM ' . DB_PREFIX . 'trade_import_stocks_banner WHERE banner_image_id IN (' . implode(",", $codes) . ')');
             }
        }
        $banner_codes = array();
        $banner_start_id = $this->db->query("SELECT MAX(banner_image_id) FROM " . DB_PREFIX . "banner_image");
        $banner_start_id = $banner_start_id->row['MAX(banner_image_id)'] == NULL ? 1 : (int)($banner_start_id->row['MAX(banner_image_id)'] + 1);
        $arr_codes = array();
        $stocks_codes = $this->get_stocks_codes();
        $product_codes = $this->get_product_codes();
        $banner_codes = $this->get_stocks_banner_ids();
        $seo_urls = array();
        $arr = array_filter($arr, function($products) use ($product_codes) {
            foreach ($products['products'] as $code => $product) {
                if (isset($product_codes[$code])) {
                    return true;
                }
            }
            return false;
        });

        $this->db->query('DELETE FROM ' . DB_PREFIX . 'trade_import_stocks_codes WHERE 1');

        $queries = array(
            'delete' => array(
                'stocks_product' => 'DELETE FROM ' . DB_PREFIX . 'stocks_product WHERE stocks_id IN (',
                'seo_url' => "DELETE FROM " . DB_PREFIX . "seo_url WHERE query IN ('stocks_id="
            ),
            'add' => array(
                'stocks' => array(
                    'query' => "INSERT INTO " . DB_PREFIX . "stocks(`stocks_id`, `start_at`, `end_at`, `discount`, `image`, `sort_order`) VALUES ",
                    'data' => array(0 => ""),
                    'data_key' => 0
                ),
                'stocks_description' => array(
                    'query' => "INSERT INTO " . DB_PREFIX . "stocks_description VALUES ",
                    'data' => array(0 => ""),
                    'data_key' => 0
                ),
                'stocks_codes' => array(
                    'query' => "INSERT INTO " . DB_PREFIX . "trade_import_stocks_codes VALUES ",
                    'data' => array(0 => ""),
                    'data_key' => 0
                ),
                'stocks_product' => array(
                    'query' => "INSERT INTO " . DB_PREFIX . "stocks_product VALUES ",
                    'data' => array(0 => ""),
                    'data_key' => 0
                ),
                'seo_url' => array(
                    'query' => "INSERT INTO " . DB_PREFIX . "seo_url(`store_id`, `language_id`, `query`, `keyword`) VALUES ",
                    'data' => array(0 => ""),
                    'data_key' => 0
                ),
                'banner_image' => array(
                    'query' => "INSERT INTO " . DB_PREFIX . "banner_image VALUES ",
                    'data' => array(0 => ""),
                    'data_key' => 0
                ),
                'stocks_banner' => array(
                    'query' => "INSERT INTO " . DB_PREFIX . "trade_import_stocks_banner VALUES ",
                    'data' => array(0 => ""),
                    'data_key' => 0
                )
            )
        );

        $banner_sort_order = -1;

        if (!empty($arr)) {
            foreach ($arr as $code => $data) {
                if (!isset($stocks_codes[$code])) {
                    if ($debug) {
                        echo 'Adding stocks ' . $data['name'] . "\n";
                    }
                    $stocks_codes[$code] = $stocks_start_id;
                    $stocks_start_id++;
                }

                $arr_codes[$code] = $stocks_start_id;

                $this->set_query($queries['add']['stocks'], "('" . $stocks_codes[$code] . "', '" . $this->db->escape($data['start_at']) . "', '" . $this->db->escape($data['end_at']) . "', '" . (int)$data['discount'] . "', '" . $this->db->escape($data['image']) . "', '0'),", $query_limit);
                $this->set_query($queries['add']['stocks_description'], "('" . $stocks_codes[$code] . "', '1', '" . $this->db->escape($data['name']) . "', '" . $this->db->escape($data['description']) . "', '" . $this->db->escape($data['requirements']) . "', '" . $this->db->escape($data['meta_title']) . "', '" . $this->db->escape($data['meta_description']) . "', '" . $this->db->escape($data['meta_keyword']) . "', '" . $this->db->escape($data['name']) . "'),", $query_limit);
                $this->set_query($queries['add']['stocks_codes'], "('" . $stocks_codes[$code] . "', '" . $code . "'),", $query_limit);

                if (!empty($data['products'])) {
                    foreach ($data['products'] as $uuid => $product) {
                        if ($debug) {
                            echo 'Adding product ' . $uuid . ' for stocks ' . $data['name'] . "\n";
                        }
                        if (isset($product_codes[$uuid])) {
                            $this->set_query($queries['add']['stocks_product'], "('" . $stocks_codes[$code] . "', '" . $product_codes[$uuid] . "'),", $query_limit);
                        }
                    }
                }

                if (!empty($data['stocks_seo_url'])) {
                    $k = $this->db->escape($data['stocks_seo_url']);
                    if (isset($seo_urls[$k])) {
                        $seo_urls[$k]++;
                        $k = "{$k}-{$seo_urls[$k]}";
                    } else {
                        $seo_urls[$k] = 0;
                    }
                    $this->set_query($queries['add']['seo_url'], "('0', '1', 'stocks_id=" . $stocks_codes[$code] . "', '" . $k . "'),", $query_limit);
                }

                if (!empty($data['banner_id']) && !empty($data['image'])) {
                    foreach ($data['banner_id'] as $banner_id) {
                        $this->set_query($queries['add']['banner_image'], "('" . $banner_start_id . "', '" . $banner_id . "', '1', '" . $this->db->escape($data['name']) . "', '/{$k}', '" . $this->db->escape($data['image']) . "', '" . $banner_sort_order-- . "'),", $query_limit);
                        $this->set_query($queries['add']['stocks_banner'], "('" . $banner_start_id++ . "', '" . $banner_id . "', '" . $stocks_codes[$code] . "', '" . $code . "'),", $query_limit);
                    }
                }
            }
        }

        if (!empty($stocks_codes)) {
            foreach ($queries['delete'] as $k => $q) {
                if ($k == 'seo_url') {
                    $queries['delete'][$k] .= implode("','stocks_id=", $stocks_codes) . "')";
                } else {
                    $queries['delete'][$k] .= implode(",", $stocks_codes) . ")";
                }
                if ($debug) {
                    echo $queries['delete'][$k] . "\n";
                    echo "Size: " . strlen($queries['delete'][$k]) . "\n";
                }
                $this->db->query($queries['delete'][$k]);
            }
        }

        if (!empty($arr)) {
            foreach ($queries['add'] as $k => $q) {
                switch ($k) {
                    case 'stocks': $this->set_query_ready($queries['add'][$k], "ON DUPLICATE KEY UPDATE `start_at` = VALUES(`start_at`), `end_at` = VALUES(`end_at`), `discount` = VALUES(`discount`), `image` = VALUES(`image`), `sort_order` = `sort_order`"); break;
                    case 'stocks_description': $this->set_query_ready($queries['add'][$k], "ON DUPLICATE KEY UPDATE `name` = " . ($data['keep_names'] ? "`name`" : "VALUES(`name`)") . ", `description` = " . ($data['keep_names'] ? "`description`" : "VALUES(`description`)") . ", `requirements` = " . ($data['keep_names'] ? "`requirements`" : "VALUES(`requirements`)") . ", `meta_title` = " . ($data['keep_meta'] ? "`meta_title`" : "VALUES(`meta_title`)") . ", `meta_description` = " . ($data['keep_meta'] ? "`meta_description`" : "VALUES(`meta_description`)") . ", `meta_keyword` = " . ($data['keep_meta'] ? "`meta_keyword`" : "VALUES(`meta_keyword`)") . ", `meta_h1` = " . ($data['keep_meta'] ? "`meta_h1`" : "VALUES(`meta_h1`)")); break;
                    case 'stocks_codes': $this->set_query_ready($queries['add'][$k], "ON DUPLICATE KEY UPDATE `stocks_id` = VALUES(`stocks_id`)"); break;
                    default: $this->set_query_ready($queries['add'][$k]); break;
                }

                if (isset($queries['add'][$k]['query_ready'])) {
                    $this->exec_queries($queries['add'][$k]['query_ready'], $debug);
                }
            }
        }

        $queries = array(
            'stocks_query' => "DELETE FROM " . DB_PREFIX . "stocks WHERE stocks_id IN (",
            'stocks_description_query' => "DELETE FROM " . DB_PREFIX . "stocks_description WHERE stocks_id IN (",
            'stocks_product_query' => "DELETE FROM " . DB_PREFIX . "stocks_product WHERE stocks_id IN (",
            'seo_url_query' => "DELETE FROM " . DB_PREFIX . "seo_url WHERE query IN ('stocks_id=",
        );
        
        $delete_codes = empty($arr_codes) ? $stocks_codes : array_diff_key($stocks_codes, $arr_codes);
        $delete_codes_string = implode(',', $delete_codes) . ")";
        if (!empty($delete_codes)) {
            if ($debug) {
                echo "Stocks (" . $delete_codes_string . "are not present in JSON. Removing\n";
            }
            foreach ($queries as $key => $query) {
                if ($key == 'seo_url_query') {
                    $this->db->query($query . implode("','stocks_id=", $delete_codes) . "')");
                } else {
                    $this->db->query($query . $delete_codes_string);
                }
            }
        }

        $this->cache->delete('stocks');
        
        if ($this->config->get('config_seo_pro')){      
            $this->cache->delete('seopro');
        }
    }

    public function add_multiple_filters($arr, $parent_category_id = null, $debug = false) {
        $query_limit = 1000000;
        $filter_group_codes = $this->get_filter_group_codes();
        $product_codes = $this->get_product_codes();
        $category_codes = $this->get_category_codes();
        if (!empty($filter_group_codes)) {
            $this->db->query('DELETE FROM ' . DB_PREFIX . 'filter_group WHERE filter_group_id IN (' . implode(",", $filter_group_codes) . ')');
            $this->db->query('DELETE FROM ' . DB_PREFIX . 'filter_group_description WHERE filter_group_id IN (' . implode(",", $filter_group_codes) . ')');
            $this->db->query('DELETE FROM ' . DB_PREFIX . 'filter WHERE filter_group_id IN (' . implode(",", $filter_group_codes) . ')');
            $this->db->query('DELETE FROM ' . DB_PREFIX . 'filter_description WHERE filter_group_id IN (' . implode(",", $filter_group_codes) . ')');
            $this->db->query('DELETE FROM ' . DB_PREFIX . 'product_filter WHERE product_id IN (' . implode(",", $product_codes) . ')');
            $this->db->query('DELETE FROM ' . DB_PREFIX . 'category_filter WHERE category_id IN (' . implode(",", $category_codes) . ($parent_category_id ? (!empty($category_codes) ? ',' . $parent_category_id : $parent_category_id) : NULL) . ')');
        }
        $filter_group_codes = array();

        $filter_group_start_id = $this->db->query("SELECT MAX(filter_group_id) FROM " . DB_PREFIX . "filter_group");
        $filter_group_start_id = $filter_group_start_id->row['MAX(filter_group_id)'] == NULL ? 1 : (int)($filter_group_start_id->row['MAX(filter_group_id)'] + 1);

        $filter_start_id = $this->db->query("SELECT MAX(filter_id) FROM " . DB_PREFIX . "filter");
        $filter_start_id = $filter_start_id->row['MAX(filter_id)'] == NULL ? 1 : (int)($filter_start_id->row['MAX(filter_id)'] + 1);

        $queries = array(
            'add' => array(
                'filter_group' => array(
                    'query' => "INSERT INTO " . DB_PREFIX . "filter_group(`filter_group_id`, `sort_order`) VALUES ",
                    'data' => array(0 => ""),
                    'data_key' => 0
                ),
                'filter_group_description' => array(
                    'query' => "INSERT INTO " . DB_PREFIX . "filter_group_description VALUES ",
                    'data' => array(0 => ""),
                    'data_key' => 0
                ),
                'filter' => array(
                    'query' => "INSERT INTO " . DB_PREFIX . "filter(`filter_id`, `filter_group_id`, `sort_order`) VALUES ",
                    'data' => array(0 => ""),
                    'data_key' => 0
                ),
                'filter_description' => array(
                    'query' => "INSERT INTO " . DB_PREFIX . "filter_description VALUES ",
                    'data' => array(0 => ""),
                    'data_key' => 0
                ),
                'filter_group_codes' => array(
                    'query' => "INSERT INTO " . DB_PREFIX . "trade_import_filter_group_codes VALUES ",
                    'data' => array(0 => ""),
                    'data_key' => 0
                ),
                'product_filter' => array(
                    'query' => "INSERT INTO " . DB_PREFIX . "product_filter VALUES ",
                    'data' => array(0 => ""),
                    'data_key' => 0
                ),
                'category_filter' => array(
                    'query' => "INSERT INTO " . DB_PREFIX . "category_filter VALUES ",
                    'data' => array(0 => ""),
                    'data_key' => 0
                )
            )
        );

        if (!empty($arr)) {
            foreach ($arr as $code => $data) {
                if (!isset($filter_group_codes[$code])) {
                    if ($debug) {
                        echo 'Adding filter group ' . $data['filter_group_name'] . "\n";
                    }
                    $filter_group_codes[$code] = $filter_group_start_id;
                    $filter_group_start_id++;
                }

                $this->set_query($queries['add']['filter_group'], "('" . $filter_group_codes[$code] . "', '0'),", $query_limit);
                $this->set_query($queries['add']['filter_group_description'], "('" . $filter_group_codes[$code] . "', '1', '" . $this->db->escape($data['filter_group_name']) . "'),", $query_limit);
                $this->set_query($queries['add']['filter_group_codes'], "('" . $filter_group_codes[$code] . "', '" . $code . "'),", $query_limit);

                if (isset($data['filters'])) {
                    foreach ($data['filters'] as $filter_name => $filter) {
                        if ($debug) {
                            echo 'Adding filter ' . $filter_name . ' for group ' . $data['filter_group_name'] . "\n";
                        }
                        $this->set_query($queries['add']['filter'], "('" . $filter_start_id . "', '" . $filter_group_codes[$code] . "', '0'),", $query_limit);
                        $this->set_query($queries['add']['filter_description'], "('" . $filter_start_id . "', '1', '" . $filter_group_codes[$code] . "', '" . $this->db->escape($filter_name) . "'),", $query_limit);
                        foreach ($filter['categories'] as $k => $category) {
                            if ($debug) {
                                echo 'Adding filter ' . $filter_name . ' for category ' . $category . "\n";
                            }
                            $this->set_query($queries['add']['category_filter'], "('" . $category_codes[$k] . "', '" . $filter_start_id . "'),", $query_limit);
                        }
                        if ($parent_category_id) {
                            if ($debug) {
                                echo 'Adding filter ' . $filter_name . ' for category ' . $parent_category_id . "\n";
                            }
                            $this->set_query($queries['add']['category_filter'], "('" . $parent_category_id . "', '" . $filter_start_id . "'),", $query_limit);
                        }
                        foreach ($filter['products'] as $k => $category) {
                            if ($debug) {
                                echo 'Adding filter ' . $filter_name . ' for product ' . $category . "\n";
                            }
                            $this->set_query($queries['add']['product_filter'], "('" . $product_codes[$k] . "', '" . $filter_start_id . "'),", $query_limit);
                        }
                        $filter_start_id++;
                    }
                }
            }

            foreach ($queries['add'] as $k => $q) {
                $this->set_query_ready($queries['add'][$k]);
                if (isset($queries['add'][$k]['query_ready'])) {
                    $this->exec_queries($queries['add'][$k]['query_ready'], $debug);
                }
            }
        }
    }

    public function add_multiple_filters_color($arr, $parent_category_id = null, $debug = false) {
        if (empty($arr)) {
            return;
        }
        $query_limit = 1000000;
        $filter_group_codes = $this->get_filter_group_codes(array_keys($arr));
        $product_codes = $this->get_product_codes();
        $category_codes = $this->get_category_codes();
        if (!empty($filter_group_codes)) {
            $this->db->query('DELETE FROM ' . DB_PREFIX . 'filter_group WHERE filter_group_id IN (' . implode(",", $filter_group_codes) . ')');
            $this->db->query('DELETE FROM ' . DB_PREFIX . 'filter_group_description WHERE filter_group_id IN (' . implode(",", $filter_group_codes) . ')');
            $this->db->query('DELETE FROM ' . DB_PREFIX . 'filter WHERE filter_group_id IN (' . implode(",", $filter_group_codes) . ')');
            $this->db->query('DELETE FROM ' . DB_PREFIX . 'filter_description WHERE filter_group_id IN (' . implode(",", $filter_group_codes) . ')');
            $this->db->query('DELETE FROM ' . DB_PREFIX . 'product_filter WHERE product_id IN (' . implode(",", $product_codes) . ')');
            $this->db->query('DELETE FROM ' . DB_PREFIX . 'category_filter WHERE category_id IN (' . implode(",", $category_codes) . ($parent_category_id ? (!empty($category_codes) ? ',' . $parent_category_id : $parent_category_id) : NULL) . ')');
        }
        $filter_group_codes = array();

        $filter_group_start_id = $this->db->query("SELECT MAX(filter_group_id) FROM " . DB_PREFIX . "filter_group");
        $filter_group_start_id = $filter_group_start_id->row['MAX(filter_group_id)'] == NULL ? 1 : (int)($filter_group_start_id->row['MAX(filter_group_id)'] + 1);

        $filter_start_id = $this->db->query("SELECT MAX(filter_id) FROM " . DB_PREFIX . "filter");
        $filter_start_id = $filter_start_id->row['MAX(filter_id)'] == NULL ? 1 : (int)($filter_start_id->row['MAX(filter_id)'] + 1);

        $queries = array(
            'add' => array(
                'filter_group' => array(
                    'query' => "INSERT INTO " . DB_PREFIX . "filter_group(`filter_group_id`, `sort_order`) VALUES ",
                    'data' => array(0 => ""),
                    'data_key' => 0
                ),
                'filter_group_description' => array(
                    'query' => "INSERT INTO " . DB_PREFIX . "filter_group_description VALUES ",
                    'data' => array(0 => ""),
                    'data_key' => 0
                ),
                'filter' => array(
                    'query' => "INSERT INTO " . DB_PREFIX . "filter(`filter_id`, `filter_group_id`, `sort_order`) VALUES ",
                    'data' => array(0 => ""),
                    'data_key' => 0
                ),
                'filter_description' => array(
                    'query' => "INSERT INTO " . DB_PREFIX . "filter_description VALUES ",
                    'data' => array(0 => ""),
                    'data_key' => 0
                ),
                'filter_group_codes' => array(
                    'query' => "INSERT INTO " . DB_PREFIX . "trade_import_filter_group_codes VALUES ",
                    'data' => array(0 => ""),
                    'data_key' => 0
                ),
                'product_filter' => array(
                    'query' => "INSERT INTO " . DB_PREFIX . "product_filter VALUES ",
                    'data' => array(0 => ""),
                    'data_key' => 0
                ),
                'category_filter' => array(
                    'query' => "INSERT INTO " . DB_PREFIX . "category_filter VALUES ",
                    'data' => array(0 => ""),
                    'data_key' => 0
                )
            )
        );

        if (!empty($arr)) {
            foreach ($arr as $code => $data) {
                if (!isset($filter_group_codes[$code])) {
                    if ($debug) {
                        echo 'Adding filter group ' . $data['filter_group_name'] . "\n";
                    }
                    $filter_group_codes[$code] = $filter_group_start_id;
                    $filter_group_start_id++;
                }

                $this->set_query($queries['add']['filter_group'], "('" . $filter_group_codes[$code] . "', '0'),", $query_limit);
                $this->set_query($queries['add']['filter_group_description'], "('" . $filter_group_codes[$code] . "', '1', '" . $this->db->escape($data['filter_group_name']) . "'),", $query_limit);
                $this->set_query($queries['add']['filter_group_codes'], "('" . $filter_group_codes[$code] . "', '" . $code . "'),", $query_limit);

                if (isset($data['filters'])) {
                    foreach ($data['filters'] as $filter_name => $filter) {
                        $f = json_decode($filter_name, true);
                        foreach ($f as $color => $color_code) {
                            if ($debug) {
                                echo 'Adding color filter ' . $color . ' with code ' . $color_code . ' for group ' . $data['filter_group_name'] . "\n";
                            }
                            $this->set_query($queries['add']['filter'], "('" . $filter_start_id . "', '" . $filter_group_codes[$code] . "', '0'),", $query_limit);
                            $this->set_query($queries['add']['filter_description'], "('" . $filter_start_id . "', '1', '" . $filter_group_codes[$code] . "', '" . $this->db->escape($color) . "'),", $query_limit);
                            foreach ($filter['categories'] as $k => $category) {
                                if ($debug) {
                                    echo 'Adding color filter ' . $color . ' for category ' . $category . "\n";
                                }
                                $this->set_query($queries['add']['category_filter'], "('" . $category_codes[$k] . "', '" . $filter_start_id . "'),", $query_limit);
                            }
                            if ($parent_category_id) {
                                if ($debug) {
                                    echo 'Adding color filter ' . $color . ' for category ' . $parent_category_id . "\n";
                                }
                                $this->set_query($queries['add']['category_filter'], "('" . $parent_category_id . "', '" . $filter_start_id . "'),", $query_limit);
                            }
                            foreach ($filter['products'] as $k => $category) {
                                if ($debug) {
                                    echo 'Adding color filter ' . $color . ' for product ' . $category . "\n";
                                }
                                $this->set_query($queries['add']['product_filter'], "('" . $product_codes[$k] . "', '" . $filter_start_id . "'),", $query_limit);
                            }
                            $filter_start_id++;
                        }
                    }
                }
            }

            foreach ($queries['add'] as $k => $q) {
                $this->set_query_ready($queries['add'][$k]);
                if (isset($queries['add'][$k]['query_ready'])) {
                    $this->exec_queries($queries['add'][$k]['query_ready'], $debug);
                }
            }
        }
    }

    //OCFilter Integration
    public function utf8_ucfirst($str) {
        return utf8_strtoupper(utf8_substr($str, 0, 1)) . utf8_substr($str, 1);
    }

    public function translit($string) {
        $replace = array(
          'а' => 'a', 'б' => 'b',
          'в' => 'v', 'г' => 'g', 'ґ' => 'g', 'д' => 'd', 'е' => 'e',
          'є' => 'je', 'ё' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
          'і' => 'i', 'ї' => 'ji', 'й' => 'j', 'к' => 'k', 'л' => 'l',
          'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r',
          'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h',
          'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => '',
          'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'ju', 'я' => 'ja',

            ' ' => '-', '+' => 'plus'
        );

        $string = mb_strtolower($string, 'UTF-8');
        $string = strtr($string, $replace);
        $string = preg_replace('![^a-zа-яёйъ0-9]+!isu', '-', $string);
        $string = preg_replace('!\-{2,}!si', '-', $string);

        return $string;
    }

    public function OCFilterCopyFilters($data = array()) {
        $filter_types = array();
        $query = $this->db->query("SELECT ocf.option_id, octf.filter_uuid, ocf.type FROM `". DB_PREFIX . "ocfilter_option` ocf INNER JOIN `" . DB_PREFIX . "ocfilter_option_description` ocd ON (ocf.option_id = ocd.option_id AND ocd.language_id = 1) INNER JOIN `" . DB_PREFIX . "trade_import_filter_group_codes` octf ON ocd.name = octf.filter_uuid GROUP BY ocf.option_id");

        foreach ($query->rows as $filter) {
            $filter_types[$filter['filter_uuid']] = $filter['type'];
        }

        if (!empty($data['copy_truncate'])) {
          $this->db->query("DELETE FROM `" . DB_PREFIX . "ocfilter_option` WHERE 1");
          $this->db->query("DELETE FROM `" . DB_PREFIX . "ocfilter_option_description` WHERE 1");
          $this->db->query("DELETE FROM `" . DB_PREFIX . "ocfilter_option_to_category` WHERE 1");
          $this->db->query("DELETE FROM `" . DB_PREFIX . "ocfilter_option_to_store` WHERE 1");
          $this->db->query("DELETE FROM `" . DB_PREFIX . "ocfilter_option_value` WHERE 1");
          $this->db->query("DELETE FROM `" . DB_PREFIX . "ocfilter_option_value_to_product` WHERE 1");
          $this->db->query("DELETE FROM `" . DB_PREFIX . "ocfilter_option_value_description` WHERE 1");
        }

        $type = $data['copy_type'];
        $status = ($data['copy_status'] != 0);

        // Copy Product Options
        if (!empty($data['copy_option'])) {
          $this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "ocfilter_option                   (option_id, `type`, `status`, sort_order, image)  SELECT option_id, '" . $this->db->escape($type) . "', '" . (int)$status . "', sort_order, IF(`type` = 'image', '1', '0') FROM `" . DB_PREFIX . "option`");
          $this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "ocfilter_option_description       (option_id, language_id, name)                    SELECT option_id, language_id, name FROM " . DB_PREFIX . "option_description");

          $this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "ocfilter_option_value             (option_id, value_id, image, sort_order)          SELECT option_id, option_value_id, image, sort_order FROM " . DB_PREFIX . "option_value");
          $this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "ocfilter_option_value_description (option_id, value_id, language_id, name)          SELECT option_id, option_value_id, language_id, name FROM " . DB_PREFIX . "option_value_description");

          $this->db->query("DELETE FROM " . DB_PREFIX . "ocfilter_option_value_to_product WHERE option_id IN(SELECT option_id FROM " . DB_PREFIX . "product_option_value WHERE quantity < '1')");

          $this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "ocfilter_option_value_to_product  (option_id, value_id, product_id)                 SELECT option_id, option_value_id, product_id FROM " . DB_PREFIX . "product_option_value WHERE quantity > '0'");
        }

        // Copy Product Filters
        $o = 5000;
        $v = 10000;

        if (!empty($data['copy_filter'])) {
          $this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "ocfilter_option                   (option_id, `type`, `status`, sort_order) SELECT (filter_group_id + '" . (int)$o . "'), '" . $this->db->escape($type) . "', '" . (int)$status . "', sort_order FROM `" . DB_PREFIX . "filter_group`");

          $this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "ocfilter_option_description       (option_id, language_id, name)            SELECT (filter_group_id + '" . (int)$o . "'), language_id, name FROM " . DB_PREFIX . "filter_group_description");

          $this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "ocfilter_option_value             (option_id, value_id, sort_order)         SELECT (filter_group_id + '" . (int)$o . "'), (filter_id + '" . (int)$v . "'), sort_order FROM " . DB_PREFIX . "filter");
          $this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "ocfilter_option_value_description (option_id, value_id, language_id, name)  SELECT (filter_group_id + '" . (int)$o . "'), (filter_id + '" . (int)$v . "'), language_id, name FROM " . DB_PREFIX . "filter_description");

          $this->db->query("DELETE FROM " . DB_PREFIX . "ocfilter_option_value_to_product WHERE option_id IN(SELECT (filter_group_id + '" . (int)$o . "') FROM " . DB_PREFIX . "filter_group)");

          $this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "ocfilter_option_value_to_product  (option_id, value_id, product_id)         SELECT (SELECT oov.option_id FROM " . DB_PREFIX . "ocfilter_option_value oov WHERE oov.value_id = (pf.filter_id + '" . (int)$v . "')), (pf.filter_id + '" . (int)$v . "'), pf.product_id FROM " . DB_PREFIX . "product_filter pf");

          /*Trade Import Filter Types Preserve*/
            $filter_ids = array();
            $query = $this->db->query("SELECT ocf.option_id, octf.filter_uuid, ocf.type FROM `". DB_PREFIX . "ocfilter_option` ocf INNER JOIN `" . DB_PREFIX . "ocfilter_option_description` ocd ON (ocf.option_id = ocd.option_id AND ocd.language_id = 1) INNER JOIN `" . DB_PREFIX . "trade_import_filter_group_codes` octf ON ocd.name = octf.filter_uuid GROUP BY ocf.option_id");

            foreach ($query->rows as $filter) {
                $filter_ids[$filter['filter_uuid']] = $filter['option_id'];
            }

            foreach ($filter_ids as $key => $filter) {
                if (isset($filter_types[$key]) && ($filter_types[$key] != $type)) {
                    $this->db->query("UPDATE " . DB_PREFIX . "ocfilter_option SET `type` = '" . $filter_types[$key] . "' WHERE `option_id` = '" . $filter . "'");
                }
            }
        }

        if (!empty($data['copy_attribute'])) {
          $this->db->query("UPDATE " . DB_PREFIX . "product_attribute SET text = TRIM(REPLACE(REPLACE(REPLACE(text, '\t', ''), '\n', ''), '\r', ''))");

          $o *= 2;
          $v *= 4;

          $this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "ocfilter_option                   (option_id, status, type, sort_order)    SELECT (attribute_id + '" . (int)$o . "'), '" . (int)$status . "', '" . $this->db->escape($type) . "', sort_order FROM " . DB_PREFIX . "attribute");
          $this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "ocfilter_option_description       (option_id, language_id, name)           SELECT (attribute_id + '" . (int)$o . "'), language_id, name FROM " . DB_PREFIX . "attribute_description");

            $this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "ocfilter_option_value             (option_id, value_id)                    SELECT (attribute_id + '" . (int)$o . "'), (CRC32(CONCAT(attribute_id, text)) + '" . (int)$v . "') FROM " . DB_PREFIX . "product_attribute WHERE language_id = '" . (int)$this->config->get('config_language_id') . "' GROUP BY attribute_id, text");
            $this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "ocfilter_option_value_description (option_id, value_id, language_id, name) SELECT (attribute_id + '" . (int)$o . "'), (CRC32(CONCAT(attribute_id, text)) + '" . (int)$v . "'), language_id, text FROM " . DB_PREFIX . "product_attribute WHERE language_id = '" . (int)$this->config->get('config_language_id') . "' GROUP BY attribute_id, text");

          $this->db->query("DELETE FROM " . DB_PREFIX . "ocfilter_option_value_to_product WHERE option_id IN(SELECT (attribute_id + '" . (int)$o . "') FROM " . DB_PREFIX . "attribute)");

          $this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "ocfilter_option_value_to_product  (option_id, value_id, product_id)        SELECT (attribute_id + '" . (int)$o . "'), (CRC32(CONCAT(attribute_id, text)) + '" . (int)$v . "'), product_id FROM " . DB_PREFIX . "product_attribute WHERE language_id = '" . (int)$this->config->get('config_language_id') . "'");

          $this->load->model('localisation/language');

          $languages = $this->model_localisation_language->getLanguages();

          foreach ($languages as $language) {
            if ($language['language_id'] != $this->config->get('config_language_id')) {
                $this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "ocfilter_option_value_description (option_id, value_id, language_id, name)

              SELECT
                (pa.attribute_id + '" . (int)$o . "'),
                (SELECT
                  (CRC32(CONCAT(pa2.attribute_id, pa2.text)) + '" . (int)$v . "') FROM " . DB_PREFIX . "product_attribute pa2
                  WHERE pa2.language_id = '" . (int)$this->config->get('config_language_id') . "'
                  AND pa2.product_id = pa.product_id
                  AND pa2.attribute_id = pa.attribute_id LIMIT 1
                ),
                '" . (int)$language['language_id'] . "', pa.text
              FROM " . DB_PREFIX . "product_attribute pa WHERE pa.language_id = '" . (int)$language['language_id'] . "' GROUP BY pa.attribute_id, pa.text");
            }
          }

          // Separate
          if (!empty($data['attribute_separator'])) {
            $separator = (string)$data['attribute_separator'];

            $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "ocfilter_option_value_description WHERE language_id = '" . (int)$this->config->get('config_language_id') . "' AND name LIKE '%" . $this->db->escape($separator) . "%'");

            foreach ($query->rows as $result) {
                $values = explode($separator, $result['name']);

              foreach ($values as $value) {
                $value = $this->utf8_ucfirst(trim($value));

                if (!$value) {
                  continue;
                }

                $value_query = $this->db->query("SELECT value_id FROM " . DB_PREFIX . "ocfilter_option_value_description WHERE language_id = '" . (int)$this->config->get('config_language_id') . "' AND option_id = '" . (int)$result['option_id'] . "' AND LCASE(TRIM(name)) = '" . $this->db->escape(utf8_strtolower($value)) . "'");

                if ($value_query->num_rows) {
                  $value_id = $value_query->row['value_id'];
                } else {
                      $this->db->query("INSERT INTO " . DB_PREFIX . "ocfilter_option_value (option_id) VALUES ('" . (int)$result['option_id'] . "')");

                  $value_id = $this->db->getLastId();

                  $this->db->query("INSERT INTO " . DB_PREFIX . "ocfilter_option_value_description (option_id, value_id, language_id, name) VALUES ('" . (int)$result['option_id'] . "', '" . $this->db->escape($value_id) . "', '" . (int)$this->config->get('config_language_id') . "', '" . $this->db->escape($value) . "')");
                }

                $this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "ocfilter_option_value_to_product (product_id, option_id, value_id) SELECT oov2p.product_id, '" . (int)$result['option_id'] . "', '" . $this->db->escape($value_id) . "' FROM " . DB_PREFIX . "ocfilter_option_value_to_product oov2p WHERE oov2p.option_id = '" . (int)$result['option_id'] . "' AND oov2p.value_id = '" . $this->db->escape($result['value_id']) . "'");
              }

              if ($values) {
                $this->db->query("DELETE FROM `" . DB_PREFIX . "ocfilter_option_value` WHERE value_id = '" . $this->db->escape($result['value_id']) . "'");
                $this->db->query("DELETE FROM `" . DB_PREFIX . "ocfilter_option_value_description` WHERE language_id = '" . (int)$this->config->get('config_language_id') . "' AND value_id = '" . $this->db->escape($result['value_id']) . "'");
                $this->db->query("DELETE FROM `" . DB_PREFIX . "ocfilter_option_value_to_product` WHERE option_id = '" . (int)$result['option_id'] . "' AND value_id = '" . $this->db->escape($result['value_id']) . "'");
              }
            }
          }
        }

        if (!empty($data['copy_option']) || !empty($data['copy_filter']) || !empty($data['copy_attribute'])) {
          // Category
          if (!empty($data['copy_category'])) {
            $this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "ocfilter_option_to_category (option_id, category_id) SELECT oov2p.option_id, p2c.category_id FROM " . DB_PREFIX . "ocfilter_option_value_to_product oov2p LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p2c.product_id = oov2p.product_id) WHERE p2c.category_id != '0' GROUP BY oov2p.option_id, p2c.category_id");
          }

          // Store
          $this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "ocfilter_option_to_store (option_id, store_id) SELECT option_id, '0' FROM " . DB_PREFIX . "ocfilter_option");

            $this->load->model('setting/store');

            $results = $this->model_setting_store->getStores();

          foreach ($results as $result) {
            $this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "ocfilter_option_to_store (option_id, store_id) SELECT option_id, '" . (int)$result['store_id'] . "' FROM " . DB_PREFIX . "ocfilter_option");
          }

          // Set status = '0' (auto)
          if ($data['copy_status'] < 0) {
            $this->db->query("UPDATE " . DB_PREFIX . "ocfilter_option oo LEFT JOIN " . DB_PREFIX . "ocfilter_option_to_category oo2c ON (oo.option_id = oo2c.option_id) SET oo.status = '0' WHERE oo2c.category_id IS NULL");

            $this->db->query("UPDATE " . DB_PREFIX . "ocfilter_option oo LEFT JOIN " . DB_PREFIX . "ocfilter_option_value_to_product oov2p ON (oo.option_id = oov2p.option_id) SET oo.status = '0' WHERE oov2p.product_id IS NULL");

            $this->db->query("UPDATE " . DB_PREFIX . "ocfilter_option oo LEFT JOIN " . DB_PREFIX . "ocfilter_option_value oov ON (oo.option_id = oov.option_id) SET oo.status = '0' WHERE oov.value_id IS NULL");

            $this->db->query("UPDATE " . DB_PREFIX . "ocfilter_option oo SET oo.status = '0' WHERE (oo.type = 'slide_dual' OR oo.type = 'slide') AND (SELECT COUNT(*) FROM " . DB_PREFIX . "ocfilter_option_value oov WHERE oov.option_id = oo.option_id) > '100'");
          }

          // Convert to slide
          $option_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "ocfilter_option WHERE status = '1' AND (type = 'slide' OR type = 'slide_dual')");

          foreach ($option_query->rows as $option) {
            $value_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "ocfilter_option_value_description WHERE option_id = '" . (int)$option['option_id'] . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "'");

            foreach ($value_query->rows as $value) {
              $slide_value_min = (float)preg_replace('![^0-9\.\-]+!s', '', $value['name']);

              if ($slide_value_min) {
                    $this->db->query("UPDATE IGNORE " . DB_PREFIX . "ocfilter_option_value_to_product SET value_id = '0', slide_value_min = '" . (float)$slide_value_min . "', slide_value_max = '" . (float)$slide_value_min . "' WHERE option_id = '" . (int)$value['option_id'] . "' AND value_id = '" . (string)$value['value_id'] . "'");
              }
            }
          }

          $this->db->query("OPTIMIZE TABLE `" . DB_PREFIX . "ocfilter_option_value_to_product`");
        }

        // Set URL Aliases
        $query = $this->db->query("SELECT oo.option_id, ood.name FROM " . DB_PREFIX . "ocfilter_option oo LEFT JOIN " . DB_PREFIX . "ocfilter_option_description ood ON(oo.option_id = ood.option_id) WHERE ood.language_id = '" . (int)$this->config->get('config_language_id') . "' AND oo.`keyword` = ''");

        foreach ($query->rows as $row) {
            $this->db->query("UPDATE " . DB_PREFIX . "ocfilter_option SET `keyword` = '" . $this->db->escape($this->translit($row['name'])) . "' WHERE option_id = '" . (int)$row['option_id'] . "'");
        }

        $query = $this->db->query("SELECT oov.value_id, oovd.name FROM " . DB_PREFIX . "ocfilter_option_value oov LEFT JOIN " . DB_PREFIX . "ocfilter_option_value_description oovd ON(oov.value_id = oovd.value_id) WHERE oovd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND oov.`keyword` = ''");

        foreach ($query->rows as $row) {
            $this->db->query("UPDATE " . DB_PREFIX . "ocfilter_option_value SET `keyword` = '" . $this->db->escape($this->translit($row['name'])) . "' WHERE value_id = '" . $this->db->escape($row['value_id']) . "'");
        }

        $this->cache->delete('ocfilter');
        $this->cache->delete('product');
    }

    public function OCFilterSetFilterColors($arr, $debug = false) {
        if (empty($arr)) {
            return;
        }

        $query_limit = 1000000;
        $value_colors = array();
        $option_ids = array();
        $query = $this->db->query("SELECT ocfv.value_id, ocfv.option_id, ocfv.name FROM `" . DB_PREFIX . "ocfilter_option` ocf INNER JOIN `" . DB_PREFIX . "ocfilter_option_description` ocd ON (ocf.option_id = ocd.option_id AND ocd.language_id = 1) INNER JOIN `" . DB_PREFIX . "trade_import_filter_group_codes` octf ON ocd.name = octf.filter_uuid LEFT JOIN `" . DB_PREFIX . "ocfilter_option_value_description` ocfv ON (ocf.option_id = ocfv.option_id) WHERE octf.filter_uuid IN ('" . implode("','", array_keys($arr)) . "') ORDER BY ocfv.value_id ASC");

        foreach ($query->rows as $value) {
            $option_ids[$value['option_id']] = 1;
            $value_colors[$value['name']] = $value['value_id'];
        }

        foreach ($option_ids as $option_id => $v) {
            $this->db->query("UPDATE " . DB_PREFIX . "ocfilter_option SET color = '1' WHERE option_id = '" . $option_id . "'");
        }

        $queries = array(
            'add' => array(
                'ocfilter_option_value' => array(
                    'query' => "INSERT INTO " . DB_PREFIX . "ocfilter_option_value(`value_id`, `option_id`, `keyword`, `color`, `image`, `sort_order`) VALUES ",
                    'data' => array(0 => ""),
                    'data_key' => 0
                )
            )
        );

        foreach ($arr as $code => $data) {
            foreach ($data['filters'] as $filter_name => $filter) {
                $f = json_decode($filter_name, true);
                foreach ($f as $color => $color_code) {
                    $this->set_query($queries['add']['ocfilter_option_value'], "('" . $value_colors[$color] . "', '1', '1', '" . substr($color_code, 1) . "', '1', '0'),", $query_limit);
                }
            }
        }

        $this->set_query_ready($queries['add']['ocfilter_option_value'], "ON DUPLICATE KEY UPDATE `value_id` = `value_id`, `option_id` = `option_id`, `keyword` = `keyword`, `color` = VALUES(`color`), `image` = `image`, `sort_order` = `sort_order`");
        if (isset($queries['add']['ocfilter_option_value']['query_ready'])) {
            $this->exec_queries($queries['add']['ocfilter_option_value']['query_ready'], $debug);
        }
        
        $this->cache->delete('ocfilter');
        $this->cache->delete('product');
    }

    public function full_sync($debug = false) {
        $query = $this->db->query("SELECT p.product_id FROM " . DB_PREFIX . "product p WHERE p.product_id NOT IN (SELECT t.product_id FROM " . DB_PREFIX . "trade_import_product_codes t)");
        $product_codes = array_column($query->rows, 'product_id');
        if (empty($product_codes)) {
            if ($debug) {
                echo "Nothing to delete, already full synced" . "\n";
            }
            return 0;
        }
        $query = $this->db->query("SELECT option_id FROM " . DB_PREFIX . "product_option WHERE product_id IN (" . implode(",", $product_codes) . ")");
        $option_codes = array_column($query->rows, 'option_id');
        $queries = array(
            'option' => array(
                'delete' => array(
                    'delete_option' => "DELETE FROM " . DB_PREFIX . "option WHERE option_id IN (",
                    'delete_option_value' => "DELETE FROM " . DB_PREFIX . "option_value WHERE option_id IN (",
                    'delete_option_description' => "DELETE FROM " . DB_PREFIX . "option_description WHERE option_id IN (",
                    'delete_option_value_description' => "DELETE FROM " . DB_PREFIX . "option_value_description WHERE option_id IN (",
                )
            ),
            'product' => array(
                'delete' => array(
                    'delete_product' => "DELETE FROM " . DB_PREFIX . "product WHERE product_id IN (",
                    'delete_product_description' => "DELETE FROM " . DB_PREFIX . "product_description WHERE product_id IN (",
                    'delete_product_description_composition' => "DELETE FROM " . DB_PREFIX . "product_description_composition WHERE product_id IN (",
                    'delete_product_option' => "DELETE FROM " . DB_PREFIX . "product_option WHERE product_id IN (",
                    'delete_product_option_value' => "DELETE FROM " . DB_PREFIX . "product_option_value WHERE product_id IN (",
                    'delete_product_special' => "DELETE FROM " . DB_PREFIX . "product_special WHERE product_id IN (",
                    'delete_product_to_category' => "DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id IN (",
                    'delete_product_to_store' => "DELETE FROM " . DB_PREFIX . "product_to_store WHERE product_id IN (",
                    'delete_seo_url' => "DELETE FROM " . DB_PREFIX . "seo_url WHERE query IN ('product_id="
                )
            ),
        );

        $delete_product_codes = $product_codes;
        $delete_product_codes_string = implode(',', $delete_product_codes) . ")";
        $delete_product_codes_seo_string = implode("','product_id=", $delete_product_codes) . "')";
        if (!empty($delete_product_codes)) {
            if ($debug) {
                echo "Products (" . $delete_product_codes_string . "are not synced. Removing\n"; 
            }
            foreach ($queries['product']['delete'] as $key => $query) {
                $query .= $key == 'delete_seo_url' ? $delete_product_codes_seo_string : $delete_product_codes_string;
                $this->db->query($query);
            }

            $delete_option_codes = $option_codes;
            $delete_option_codes_string = implode(',', $delete_option_codes) . ")";
            if (!empty($delete_option_codes)) {
                foreach ($queries['option']['delete'] as $key => $query) {
                    $query .= $delete_option_codes_string;
                    $this->db->query($query);
                }
            }
        }
    }

    public function addCategory($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "category SET parent_id = '" . (int)$data['parent_id'] . "', `top` = '" . (isset($data['top']) ? (int)$data['top'] : 0) . "', `column` = '" . (int)$data['column'] . "', sort_order = '" . (int)$data['sort_order'] . "', status = '" . (int)$data['status'] . "', noindex = '" . (int)$data['noindex'] . "', date_modified = NOW(), date_added = NOW()");

        $category_id = $this->db->getLastId();

        if (isset($data['image'])) {
            $this->db->query("UPDATE " . DB_PREFIX . "category SET image = '" . $this->db->escape($data['image']) . "' WHERE category_id = '" . (int)$category_id . "'");
        }

        foreach ($data['category_description'] as $language_id => $value) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "category_description SET category_id = '" . (int)$category_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "', description = '" . $this->db->escape($value['description']) . "', meta_title = '" . $this->db->escape($value['meta_title']) . "', meta_h1 = '" . $this->db->escape($value['meta_h1']) . "', meta_description = '" . $this->db->escape($value['meta_description']) . "', meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "'");
        }

        // MySQL Hierarchical Data Closure Table Pattern
        $level = 0;

        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE category_id = '" . (int)$data['parent_id'] . "' ORDER BY `level` ASC");

        foreach ($query->rows as $result) {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "category_path` SET `category_id` = '" . (int)$category_id . "', `path_id` = '" . (int)$result['path_id'] . "', `level` = '" . (int)$level . "'");

            $level++;
        }

        $this->db->query("INSERT INTO `" . DB_PREFIX . "category_path` SET `category_id` = '" . (int)$category_id . "', `path_id` = '" . (int)$category_id . "', `level` = '" . (int)$level . "'");

        if (isset($data['category_filter'])) {
            foreach ($data['category_filter'] as $filter_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "category_filter SET category_id = '" . (int)$category_id . "', filter_id = '" . (int)$filter_id . "'");
            }
        }

        if (isset($data['category_store'])) {
            foreach ($data['category_store'] as $store_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "category_to_store SET category_id = '" . (int)$category_id . "', store_id = '" . (int)$store_id . "'");
            }
        }
        
        if (isset($data['category_seo_url'])) {
            foreach ($data['category_seo_url'] as $store_id => $language) {
                foreach ($language as $language_id => $keyword) {
                    if (!empty($keyword)) {
                        $this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET store_id = '" . (int)$store_id . "', language_id = '" . (int)$language_id . "', query = 'category_id=" . (int)$category_id . "', keyword = '" . $this->db->escape($keyword) . "'");
                    }
                }
            }
        }
        
        if (isset($data['product_related'])) {
            foreach ($data['product_related'] as $related_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_related_wb SET category_id = '" . (int)$category_id . "', product_id = '" . (int)$related_id . "'");
            }
        }
    
        if (isset($data['article_related'])) {
            foreach ($data['article_related'] as $related_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "article_related_wb SET category_id = '" . (int)$category_id . "', article_id = '" . (int)$related_id . "'");
            }
        }
        
        // Set which layout to use with this category
        if (isset($data['category_layout'])) {
            foreach ($data['category_layout'] as $store_id => $layout_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "category_to_layout SET category_id = '" . (int)$category_id . "', store_id = '" . (int)$store_id . "', layout_id = '" . (int)$layout_id . "'");
            }
        }

        $this->cache->delete('category');
        
        if($this->config->get('config_seo_pro')){       
        $this->cache->delete('seopro');
        }

        return $category_id;
    }

    public function editCategory($category_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "category SET parent_id = '" . (int)$data['parent_id'] . "', `top` = '" . (isset($data['top']) ? (int)$data['top'] : 0) . "', `column` = '" . (int)$data['column'] . "', sort_order = '" . (int)$data['sort_order'] . "', status = '" . (int)$data['status'] . "', noindex = '" . (int)$data['noindex'] . "', date_modified = NOW() WHERE category_id = '" . (int)$category_id . "'");

        if (isset($data['image'])) {
            $this->db->query("UPDATE " . DB_PREFIX . "category SET image = '" . $this->db->escape($data['image']) . "' WHERE category_id = '" . (int)$category_id . "'");
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "category_description WHERE category_id = '" . (int)$category_id . "'");

        foreach ($data['category_description'] as $language_id => $value) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "category_description SET category_id = '" . (int)$category_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "', description = '" . $this->db->escape($value['description']) . "', meta_title = '" . $this->db->escape($value['meta_title']) . "', meta_h1 = '" . $this->db->escape($value['meta_h1']) . "', meta_description = '" . $this->db->escape($value['meta_description']) . "', meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "'");
        }

        // MySQL Hierarchical Data Closure Table Pattern
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE path_id = '" . (int)$category_id . "' ORDER BY level ASC");

        if ($query->rows) {
            foreach ($query->rows as $category_path) {
                // Delete the path below the current one
                $this->db->query("DELETE FROM `" . DB_PREFIX . "category_path` WHERE category_id = '" . (int)$category_path['category_id'] . "' AND level < '" . (int)$category_path['level'] . "'");

                $path = array();

                // Get the nodes new parents
                $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE category_id = '" . (int)$data['parent_id'] . "' ORDER BY level ASC");

                foreach ($query->rows as $result) {
                    $path[] = $result['path_id'];
                }

                // Get whats left of the nodes current path
                $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE category_id = '" . (int)$category_path['category_id'] . "' ORDER BY level ASC");

                foreach ($query->rows as $result) {
                    $path[] = $result['path_id'];
                }

                // Combine the paths with a new level
                $level = 0;

                foreach ($path as $path_id) {
                    $this->db->query("REPLACE INTO `" . DB_PREFIX . "category_path` SET category_id = '" . (int)$category_path['category_id'] . "', `path_id` = '" . (int)$path_id . "', level = '" . (int)$level . "'");

                    $level++;
                }
            }
        } else {
            // Delete the path below the current one
            $this->db->query("DELETE FROM `" . DB_PREFIX . "category_path` WHERE category_id = '" . (int)$category_id . "'");

            // Fix for records with no paths
            $level = 0;

            $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE category_id = '" . (int)$data['parent_id'] . "' ORDER BY level ASC");

            foreach ($query->rows as $result) {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "category_path` SET category_id = '" . (int)$category_id . "', `path_id` = '" . (int)$result['path_id'] . "', level = '" . (int)$level . "'");

                $level++;
            }

            $this->db->query("REPLACE INTO `" . DB_PREFIX . "category_path` SET category_id = '" . (int)$category_id . "', `path_id` = '" . (int)$category_id . "', level = '" . (int)$level . "'");
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "category_filter WHERE category_id = '" . (int)$category_id . "'");

        if (isset($data['category_filter'])) {
            foreach ($data['category_filter'] as $filter_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "category_filter SET category_id = '" . (int)$category_id . "', filter_id = '" . (int)$filter_id . "'");
            }
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "category_to_store WHERE category_id = '" . (int)$category_id . "'");

        if (isset($data['category_store'])) {
            foreach ($data['category_store'] as $store_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "category_to_store SET category_id = '" . (int)$category_id . "', store_id = '" . (int)$store_id . "'");
            }
        }

        // SEO URL
        $this->db->query("DELETE FROM `" . DB_PREFIX . "seo_url` WHERE query = 'category_id=" . (int)$category_id . "'");

        if (isset($data['category_seo_url'])) {
            foreach ($data['category_seo_url'] as $store_id => $language) {
                foreach ($language as $language_id => $keyword) {
                    if (!empty($keyword)) {
                        $this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET store_id = '" . (int)$store_id . "', language_id = '" . (int)$language_id . "', query = 'category_id=" . (int)$category_id . "', keyword = '" . $this->db->escape($keyword) . "'");
                    }
                }
            }
        }
        
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_related_wb WHERE category_id = '" . (int)$category_id . "'");
    
        if (isset($data['product_related'])) {
            foreach ($data['product_related'] as $related_id) {
                $this->db->query("DELETE FROM " . DB_PREFIX . "product_related_wb WHERE category_id = '" . (int)$category_id . "' AND product_id = '" . (int)$related_id . "'");
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_related_wb SET category_id = '" . (int)$category_id . "', product_id = '" . (int)$related_id . "'");
                
    
            }
        }
        
        $this->db->query("DELETE FROM " . DB_PREFIX . "article_related_wb WHERE category_id = '" . (int)$category_id . "'");
    
        if (isset($data['article_related'])) {
            foreach ($data['article_related'] as $related_id) {
                $this->db->query("DELETE FROM " . DB_PREFIX . "article_related_wb WHERE category_id = '" . (int)$category_id . "' AND article_id = '" . (int)$related_id . "'");
                $this->db->query("INSERT INTO " . DB_PREFIX . "article_related_wb SET category_id = '" . (int)$category_id . "', article_id = '" . (int)$related_id . "'");
                
    
            }
        }
        
        $this->db->query("DELETE FROM " . DB_PREFIX . "category_to_layout WHERE category_id = '" . (int)$category_id . "'");

        if (isset($data['category_layout'])) {
            foreach ($data['category_layout'] as $store_id => $layout_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "category_to_layout SET category_id = '" . (int)$category_id . "', store_id = '" . (int)$store_id . "', layout_id = '" . (int)$layout_id . "'");
            }
        }

        $this->cache->delete('category');
        
        if($this->config->get('config_seo_pro')){       
        $this->cache->delete('seopro');
        }
    }

    public function deleteCategory($category_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "category_path WHERE category_id = '" . (int)$category_id . "'");

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "category_path WHERE path_id = '" . (int)$category_id . "'");

        foreach ($query->rows as $result) {
            $this->deleteCategory($result['category_id']);
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "category WHERE category_id = '" . (int)$category_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "category_description WHERE category_id = '" . (int)$category_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "category_filter WHERE category_id = '" . (int)$category_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "category_to_store WHERE category_id = '" . (int)$category_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "category_to_layout WHERE category_id = '" . (int)$category_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE category_id = '" . (int)$category_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'category_id=" . (int)$category_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_related_wb WHERE category_id = '" . (int)$category_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "article_related_wb WHERE category_id = '" . (int)$category_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "coupon_category WHERE category_id = '" . (int)$category_id . "'");

        $this->cache->delete('category');
        
        if($this->config->get('config_seo_pro')){       
        $this->cache->delete('seopro');
        }
    }

    public function get_parent_category($category_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "category WHERE category_id = " . (int) $category_id);
        if (!isset($query->row['category_id'])) {
            return NULL;
        }
        $tables = array();
        $tables['category'] = $query->row;
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "category_description WHERE category_id = " . (int) $category_id . " AND language_id = " . (int)$this->config->get('config_language_id'));
        $tables['category_description'] = $query->row;
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "category_path WHERE category_id = " . (int) $category_id . " AND level = 0");
        $tables['category_path'] = $query->row;
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "category_to_layout WHERE category_id = " . (int) $category_id);
        $tables['category_to_layout'] = $query->row;
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "category_to_store WHERE category_id = " . (int) $category_id);
        $tables['category_to_store'] = $query->row;
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE query = 'category_id=" . (int) $category_id . "' AND language_id = " . (int)$this->config->get('config_language_id'));
        $tables['seo_url'] = $query->row;
        return $tables;
    }

    public function set_parent_category($tables) {
        if (!isset($tables['category'])) {
            return;
        }
        $this->db->query("INSERT INTO " . DB_PREFIX . "category (`category_id`, `image`, `parent_id`, `top`, `column`, `sort_order`, `status`, `date_added`, `date_modified`, `noindex`) VALUES ('" . (int)$tables['category']['category_id'] . "', '" . $this->db->escape($tables['category']['image']) . "', '" . (int)$tables['category']['parent_id'] . "', '" . (int)$tables['category']['top'] . "', '" . (int)$tables['category']['column'] . "', '" . (int)$tables['category']['sort_order'] . "', '" . (int)$tables['category']['status'] . "', '" . $this->db->escape($tables['category']['date_added']) . "', '" . $this->db->escape($tables['category']['date_modified']) . "', '" . (int)$tables['category']['noindex'] . "')");
        $this->db->query("INSERT INTO " . DB_PREFIX . "category_description VALUES ('" . (int)$tables['category_description']['category_id'] . "', '" . (int)$tables['category_description']['language_id'] . "', '" . $this->db->escape($tables['category_description']['name']) . "', '" . $this->db->escape($tables['category_description']['description']) . "', '" . $this->db->escape($tables['category_description']['meta_title']) . "', '" . $this->db->escape($tables['category_description']['meta_description']) . "', '" . $this->db->escape($tables['category_description']['meta_keyword']) . "','')");
        $this->db->query("INSERT INTO " . DB_PREFIX . "category_path VALUES ('" . (int)$tables['category_path']['category_id'] . "', '" . (int)$tables['category_path']['path_id'] . "', '" . (int)$tables['category_path']['level'] . "')");
        $this->db->query("INSERT INTO " . DB_PREFIX . "category_to_layout VALUES ('" . (int)$tables['category_to_layout']['category_id'] . "', '" . (int)$tables['category_to_layout']['store_id'] . "', '" . (int)$tables['category_to_layout']['layout_id'] . "')");
        $this->db->query("INSERT INTO " . DB_PREFIX . "category_to_store VALUES ('" . (int)$tables['category_to_store']['category_id'] . "', '" . (int)$tables['category_to_store']['store_id'] . "')");
        $this->db->query("INSERT INTO " . DB_PREFIX . "seo_url(`store_id`, `language_id`, `query`, `keyword`) VALUES ('" . (int)$tables['seo_url']['store_id'] . "', '" . (int)$tables['seo_url']['language_id'] . "', '" . $this->db->escape($tables['seo_url']['query']) . "', '" . $this->db->escape($tables['seo_url']['keyword']) . "')");
    }

    public function getCategory($category_id) {
        $query = $this->db->query("SELECT DISTINCT *, (SELECT GROUP_CONCAT(cd1.name ORDER BY level SEPARATOR '&nbsp;&nbsp;&gt;&nbsp;&nbsp;') FROM " . DB_PREFIX . "category_path cp LEFT JOIN " . DB_PREFIX . "category_description cd1 ON (cp.path_id = cd1.category_id AND cp.category_id != cp.path_id) WHERE cp.category_id = c.category_id AND cd1.language_id = '" . (int)$this->config->get('config_language_id') . "' GROUP BY cp.category_id) AS path FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_description cd2 ON (c.category_id = cd2.category_id) WHERE c.category_id = '" . (int)$category_id . "' AND cd2.language_id = '" . (int)$this->config->get('config_language_id') . "'");
        
        return $query->row;
    }

    public function getCategoryPath($category_id, $parent_id = 0) {
        $query = $this->db->query("SELECT DISTINCT *, (SELECT GROUP_CONCAT(cd1.category_id ORDER BY level SEPARATOR '&nbsp;&nbsp;&gt;&nbsp;&nbsp;') FROM " . DB_PREFIX . "category_path cp LEFT JOIN " . DB_PREFIX . "category_description cd1 ON (cp.path_id = cd1.category_id AND cp.category_id != cp.path_id) WHERE cp.category_id = c.category_id AND cd1.language_id = '" . (int)$this->config->get('config_language_id') . "' GROUP BY cp.category_id) AS path FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_description cd2 ON (c.category_id = cd2.category_id) WHERE c.category_id = '" . (int)$category_id . "' AND cd2.language_id = '" . (int)$this->config->get('config_language_id') . "'");
        if (!empty($query->row)) {
            $path = explode("&nbsp;&nbsp;&gt;&nbsp;&nbsp;", $query->row['path']);
            $path[] = $category_id;
            return $path;
        } else {
            return array($parent_id);
        }
    }

    public function getCategories($data = array()) {
        $sql = "SELECT cp.category_id AS category_id, GROUP_CONCAT(cd1.name ORDER BY cp.level SEPARATOR '&nbsp;&nbsp;&gt;&nbsp;&nbsp;') AS name, c1.parent_id, c1.sort_order, c1.noindex FROM " . DB_PREFIX . "category_path cp LEFT JOIN " . DB_PREFIX . "category c1 ON (cp.category_id = c1.category_id) LEFT JOIN " . DB_PREFIX . "category c2 ON (cp.path_id = c2.category_id) LEFT JOIN " . DB_PREFIX . "category_description cd1 ON (cp.path_id = cd1.category_id) LEFT JOIN " . DB_PREFIX . "category_description cd2 ON (cp.category_id = cd2.category_id) WHERE cd1.language_id = '" . (int)$this->config->get('config_language_id') . "' AND cd2.language_id = '" . (int)$this->config->get('config_language_id') . "'";

        if (!empty($data['filter_name'])) {
            $sql .= " AND cd2.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        $sql .= " GROUP BY cp.category_id";

        $sort_data = array(
            'name',
            'sort_order',
            'noindex'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY sort_order";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function get_categoryid_by_code($code) {
        $result = $this->db->query('SELECT category_id FROM ' . DB_PREFIX . "trade_import_category_codes WHERE group_uuid = '" . $this->db->escape($code) . "'");
        if (isset($result->row['category_id'])) {
            return $result->row['category_id'];
        } else {
            return 0;
        }
    }

    public function get_category_by_code($code) {
        return $this->getCategory($this->get_categoryid_by_code($code));
    }

    public function get_category_codes() {
        $result = $this->db->query('SELECT category_id, group_uuid FROM ' . DB_PREFIX . 'trade_import_category_codes ORDER BY category_id ASC');
        $arr = array();
        foreach ($result->rows as $row) {
            $arr[$row['group_uuid']] = $row['category_id'];
        }
        return $arr;
    }

    public function add_category_code($id, $code) {
        $this->db->query('INSERT INTO ' . DB_PREFIX . 'trade_import_category_codes VALUES(' . (int)$id . ", '" . $this->db->escape($code) . "')");
    }

    public function edit_category_code($id, $code) {
        $this->db->query('UPDATE ' . DB_PREFIX . "trade_import_category_codes SET group_uuid = '" . $this->db->escape($code) . "' WHERE category_id = " . (int)$id);
    }

    public function delete_category_code($code) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "trade_import_category_codes WHERE group_uuid = '" . $this->db->escape($code) . "'");
    }
    
    public function hide_category($id) {
        $this->db->query('UPDATE ' . DB_PREFIX . 'category SET status = 0 WHERE category_id = ' . (int)$id);
    }

    public function show_category($id) {
        $this->db->query('UPDATE ' . DB_PREFIX . 'category SET status = 1 WHERE category_id = ' . (int)$id);
    }

    public function getCustomerGroups($data = array()) {
        $sql = "SELECT * FROM " . DB_PREFIX . "customer_group cg LEFT JOIN " . DB_PREFIX . "customer_group_description cgd ON (cg.customer_group_id = cgd.customer_group_id) WHERE cgd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

        $sort_data = array(
            'cgd.name',
            'cg.sort_order'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY cgd.name";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function addProduct($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "product SET model = '" . $this->db->escape($data['model']) . "', sku = '" . $this->db->escape($data['sku']) . "', upc = '" . $this->db->escape($data['upc']) . "', ean = '" . $this->db->escape($data['ean']) . "', jan = '" . $this->db->escape($data['jan']) . "', isbn = '" . $this->db->escape($data['isbn']) . "', mpn = '" . $this->db->escape($data['mpn']) . "', location = '" . $this->db->escape($data['location']) . "', quantity = '" . (int)$data['quantity'] . "', minimum = '" . (int)$data['minimum'] . "', subtract = '" . (int)$data['subtract'] . "', stock_status_id = '" . (int)$data['stock_status_id'] . "', date_available = '" . $this->db->escape($data['date_available']) . "', manufacturer_id = '" . (int)$data['manufacturer_id'] . "', shipping = '" . (int)$data['shipping'] . "', price = '" . (float)$data['price'] . "', points = '" . (int)$data['points'] . "', weight = '" . (float)$data['weight'] . "', weight_class_id = '" . (int)$data['weight_class_id'] . "', length = '" . (float)$data['length'] . "', width = '" . (float)$data['width'] . "', height = '" . (float)$data['height'] . "', length_class_id = '" . (int)$data['length_class_id'] . "', status = '" . (int)$data['status'] . "', noindex = '" . (int)$data['noindex'] . "', tax_class_id = '" . (int)$data['tax_class_id'] . "', sort_order = '" . (int)$data['sort_order'] . "', date_added = NOW(), date_modified = NOW()");

        $product_id = $this->db->getLastId();

        if (isset($data['image'])) {
            $this->db->query("UPDATE " . DB_PREFIX . "product SET image = '" . $this->db->escape($data['image']) . "' WHERE product_id = '" . (int)$product_id . "'");
        }

        foreach ($data['product_description'] as $language_id => $value) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "product_description SET product_id = '" . (int)$product_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "', description = '" . $this->db->escape($value['description']) . "', tag = '" . $this->db->escape($value['tag']) . "', meta_title = '" . $this->db->escape($value['meta_title']) . "', meta_h1 = '" . $this->db->escape($value['meta_h1']) . "', meta_description = '" . $this->db->escape($value['meta_description']) . "', meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "'");
        }

        if (isset($data['product_store'])) {
            foreach ($data['product_store'] as $store_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_store SET product_id = '" . (int)$product_id . "', store_id = '" . (int)$store_id . "'");
            }
        }

        if (isset($data['product_attribute'])) {
            foreach ($data['product_attribute'] as $product_attribute) {
                if ($product_attribute['attribute_id']) {
                    // Removes duplicates
                    $this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "' AND attribute_id = '" . (int)$product_attribute['attribute_id'] . "'");

                    foreach ($product_attribute['product_attribute_description'] as $language_id => $product_attribute_description) {
                        $this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "' AND attribute_id = '" . (int)$product_attribute['attribute_id'] . "' AND language_id = '" . (int)$language_id . "'");

                        $this->db->query("INSERT INTO " . DB_PREFIX . "product_attribute SET product_id = '" . (int)$product_id . "', attribute_id = '" . (int)$product_attribute['attribute_id'] . "', language_id = '" . (int)$language_id . "', text = '" .  $this->db->escape($product_attribute_description['text']) . "'");
                    }
                }
            }
        }

        if (isset($data['product_option'])) {
            foreach ($data['product_option'] as $product_option) {
                if ($product_option['type'] == 'select' || $product_option['type'] == 'radio' || $product_option['type'] == 'checkbox' || $product_option['type'] == 'image') {
                    if (isset($product_option['product_option_value'])) {
                        $this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', required = '" . (int)$product_option['required'] . "'");

                        $product_option_id = $this->db->getLastId();

                        foreach ($product_option['product_option_value'] as $product_option_value) {
                            $this->db->query("INSERT INTO " . DB_PREFIX . "product_option_value SET product_option_id = '" . (int)$product_option_id . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', option_value_id = '" . (int)$product_option_value['option_value_id'] . "', quantity = '" . (int)$product_option_value['quantity'] . "', subtract = '" . (int)$product_option_value['subtract'] . "', price = '" . (float)$product_option_value['price'] . "', price_prefix = '" . $this->db->escape($product_option_value['price_prefix']) . "', points = '" . (int)$product_option_value['points'] . "', points_prefix = '" . $this->db->escape($product_option_value['points_prefix']) . "', weight = '" . (float)$product_option_value['weight'] . "', weight_prefix = '" . $this->db->escape($product_option_value['weight_prefix']) . "'");
                        }
                    }
                } else {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', value = '" . $this->db->escape($product_option['value']) . "', required = '" . (int)$product_option['required'] . "'");
                }
            }
        }

        if (isset($data['product_recurring'])) {
            foreach ($data['product_recurring'] as $recurring) {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "product_recurring` SET `product_id` = " . (int)$product_id . ", customer_group_id = " . (int)$recurring['customer_group_id'] . ", `recurring_id` = " . (int)$recurring['recurring_id']);
            }
        }
        
        if (isset($data['product_discount'])) {
            foreach ($data['product_discount'] as $product_discount) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_discount SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$product_discount['customer_group_id'] . "', quantity = '" . (int)$product_discount['quantity'] . "', priority = '" . (int)$product_discount['priority'] . "', price = '" . (float)$product_discount['price'] . "', date_start = '" . $this->db->escape($product_discount['date_start']) . "', date_end = '" . $this->db->escape($product_discount['date_end']) . "'");
            }
        }

        if (isset($data['product_special'])) {
            foreach ($data['product_special'] as $product_special) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_special SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$product_special['customer_group_id'] . "', priority = '" . (int)$product_special['priority'] . "', price = '" . (float)$product_special['price'] . "', date_start = '" . $this->db->escape($product_special['date_start']) . "', date_end = '" . $this->db->escape($product_special['date_end']) . "'");
            }
        }

        if (isset($data['product_image'])) {
            foreach ($data['product_image'] as $product_image) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_image SET product_id = '" . (int)$product_id . "', image = '" . $this->db->escape($product_image['image']) . "', sort_order = '" . (int)$product_image['sort_order'] . "'");
            }
        }

        if (isset($data['product_download'])) {
            foreach ($data['product_download'] as $download_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_download SET product_id = '" . (int)$product_id . "', download_id = '" . (int)$download_id . "'");
            }
        }

        if (isset($data['product_category'])) {
            foreach ($data['product_category'] as $category_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int)$product_id . "', category_id = '" . (int)$category_id . "'");
            }
        }
        
        if (isset($data['main_category_id']) && $data['main_category_id'] > 0) {
            $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "' AND category_id = '" . (int)$data['main_category_id'] . "'");
            $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int)$product_id . "', category_id = '" . (int)$data['main_category_id'] . "', main_category = 1");
                } elseif (isset($data['product_category'][0])) {
            $this->db->query("UPDATE " . DB_PREFIX . "product_to_category SET main_category = 1 WHERE product_id = '" . (int)$product_id . "' AND category_id = '" . (int)$data['product_category'][0] . "'");
        }

        if (isset($data['product_filter'])) {
            foreach ($data['product_filter'] as $filter_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_filter SET product_id = '" . (int)$product_id . "', filter_id = '" . (int)$filter_id . "'");
            }
        }

        if (isset($data['product_related'])) {
            foreach ($data['product_related'] as $related_id) {
                $this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$product_id . "' AND related_id = '" . (int)$related_id . "'");
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_related SET product_id = '" . (int)$product_id . "', related_id = '" . (int)$related_id . "'");
                $this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$related_id . "' AND related_id = '" . (int)$product_id . "'");
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_related SET product_id = '" . (int)$related_id . "', related_id = '" . (int)$product_id . "'");
            }
        }
        
        if (isset($data['product_related_article'])) {
            foreach ($data['product_related_article'] as $article_id) {
                $this->db->query("DELETE FROM " . DB_PREFIX . "product_related_article WHERE product_id = '" . (int)$product_id . "' AND article_id = '" . (int)$article_id . "'");
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_related_article SET product_id = '" . (int)$product_id . "', article_id = '" . (int)$article_id . "'");
            }
        }

        if (isset($data['product_reward'])) {
            foreach ($data['product_reward'] as $customer_group_id => $product_reward) {
                if ((int)$product_reward['points'] > 0) {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "product_reward SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$customer_group_id . "', points = '" . (int)$product_reward['points'] . "'");
                }
            }
        }
        
        // SEO URL
        if (isset($data['product_seo_url'])) {
            foreach ($data['product_seo_url'] as $store_id => $language) {
                foreach ($language as $language_id => $keyword) {
                    if (!empty($keyword)) {
                        $this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET store_id = '" . (int)$store_id . "', language_id = '" . (int)$language_id . "', query = 'product_id=" . (int)$product_id . "', keyword = '" . $this->db->escape($keyword) . "'");
                    }
                }
            }
        }
        
        if (isset($data['product_layout'])) {
            foreach ($data['product_layout'] as $store_id => $layout_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_layout SET product_id = '" . (int)$product_id . "', store_id = '" . (int)$store_id . "', layout_id = '" . (int)$layout_id . "'");
            }
        }


        $this->cache->delete('product');
        
        if($this->config->get('config_seo_pro')){       
        $this->cache->delete('seopro');
        }

        return $product_id;
    }

    public function editProduct($product_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "product SET model = '" . $this->db->escape($data['model']) . "', sku = '" . $this->db->escape($data['sku']) . "', upc = '" . $this->db->escape($data['upc']) . "', ean = '" . $this->db->escape($data['ean']) . "', jan = '" . $this->db->escape($data['jan']) . "', isbn = '" . $this->db->escape($data['isbn']) . "', mpn = '" . $this->db->escape($data['mpn']) . "', location = '" . $this->db->escape($data['location']) . "', quantity = '" . (int)$data['quantity'] . "', minimum = '" . (int)$data['minimum'] . "', subtract = '" . (int)$data['subtract'] . "', stock_status_id = '" . (int)$data['stock_status_id'] . "', date_available = '" . $this->db->escape($data['date_available']) . "', manufacturer_id = '" . (int)$data['manufacturer_id'] . "', shipping = '" . (int)$data['shipping'] . "', price = '" . (float)$data['price'] . "', points = '" . (int)$data['points'] . "', weight = '" . (float)$data['weight'] . "', weight_class_id = '" . (int)$data['weight_class_id'] . "', length = '" . (float)$data['length'] . "', width = '" . (float)$data['width'] . "', height = '" . (float)$data['height'] . "', length_class_id = '" . (int)$data['length_class_id'] . "', status = '" . (int)$data['status'] . "', noindex = '" . (int)$data['noindex'] . "', tax_class_id = '" . (int)$data['tax_class_id'] . "', sort_order = '" . (int)$data['sort_order'] . "', date_modified = NOW() WHERE product_id = '" . (int)$product_id . "'");

        if (isset($data['image'])) {
            $this->db->query("UPDATE " . DB_PREFIX . "product SET image = '" . $this->db->escape($data['image']) . "' WHERE product_id = '" . (int)$product_id . "'");
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "product_description WHERE product_id = '" . (int)$product_id . "'");

        foreach ($data['product_description'] as $language_id => $value) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "product_description SET product_id = '" . (int)$product_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "', description = '" . $this->db->escape($value['description']) . "', tag = '" . $this->db->escape($value['tag']) . "', meta_title = '" . $this->db->escape($value['meta_title']) . "', meta_h1 = '" . $this->db->escape($value['meta_h1']) . "', meta_description = '" . $this->db->escape($value['meta_description']) . "', meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "'");
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_store WHERE product_id = '" . (int)$product_id . "'");

        if (isset($data['product_store'])) {
            foreach ($data['product_store'] as $store_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_store SET product_id = '" . (int)$product_id . "', store_id = '" . (int)$store_id . "'");
            }
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "'");

        if (!empty($data['product_attribute'])) {
            foreach ($data['product_attribute'] as $product_attribute) {
                if ($product_attribute['attribute_id']) {
                    // Removes duplicates
                    $this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "' AND attribute_id = '" . (int)$product_attribute['attribute_id'] . "'");

                    foreach ($product_attribute['product_attribute_description'] as $language_id => $product_attribute_description) {
                        $this->db->query("INSERT INTO " . DB_PREFIX . "product_attribute SET product_id = '" . (int)$product_id . "', attribute_id = '" . (int)$product_attribute['attribute_id'] . "', language_id = '" . (int)$language_id . "', text = '" .  $this->db->escape($product_attribute_description['text']) . "'");
                    }
                }
            }
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "product_option WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_option_value WHERE product_id = '" . (int)$product_id . "'");

        if (isset($data['product_option'])) {
            foreach ($data['product_option'] as $product_option) {
                if ($product_option['type'] == 'select' || $product_option['type'] == 'radio' || $product_option['type'] == 'checkbox' || $product_option['type'] == 'image') {
                    if (isset($product_option['product_option_value'])) {
                        $this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_option_id = '" . (int)$product_option['product_option_id'] . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', required = '" . (int)$product_option['required'] . "'");

                        $product_option_id = $this->db->getLastId();

                        foreach ($product_option['product_option_value'] as $product_option_value) {
                            $this->db->query("INSERT INTO " . DB_PREFIX . "product_option_value SET product_option_value_id = '" . (int)$product_option_value['product_option_value_id'] . "', product_option_id = '" . (int)$product_option_id . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', option_value_id = '" . (int)$product_option_value['option_value_id'] . "', quantity = '" . (int)$product_option_value['quantity'] . "', subtract = '" . (int)$product_option_value['subtract'] . "', price = '" . (float)$product_option_value['price'] . "', price_prefix = '" . $this->db->escape($product_option_value['price_prefix']) . "', points = '" . (int)$product_option_value['points'] . "', points_prefix = '" . $this->db->escape($product_option_value['points_prefix']) . "', weight = '" . (float)$product_option_value['weight'] . "', weight_prefix = '" . $this->db->escape($product_option_value['weight_prefix']) . "'");
                        }
                    }
                } else {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_option_id = '" . (int)$product_option['product_option_id'] . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', value = '" . $this->db->escape($product_option['value']) . "', required = '" . (int)$product_option['required'] . "'");
                }
            }
        }

        $this->db->query("DELETE FROM `" . DB_PREFIX . "product_recurring` WHERE product_id = " . (int)$product_id);

        if (isset($data['product_recurring'])) {
            foreach ($data['product_recurring'] as $product_recurring) {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "product_recurring` SET `product_id` = " . (int)$product_id . ", customer_group_id = " . (int)$product_recurring['customer_group_id'] . ", `recurring_id` = " . (int)$product_recurring['recurring_id']);
            }
        }
        
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_discount WHERE product_id = '" . (int)$product_id . "'");

        if (isset($data['product_discount'])) {
            foreach ($data['product_discount'] as $product_discount) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_discount SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$product_discount['customer_group_id'] . "', quantity = '" . (int)$product_discount['quantity'] . "', priority = '" . (int)$product_discount['priority'] . "', price = '" . (float)$product_discount['price'] . "', date_start = '" . $this->db->escape($product_discount['date_start']) . "', date_end = '" . $this->db->escape($product_discount['date_end']) . "'");
            }
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "product_special WHERE product_id = '" . (int)$product_id . "'");

        if (isset($data['product_special'])) {
            foreach ($data['product_special'] as $product_special) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_special SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$product_special['customer_group_id'] . "', priority = '" . (int)$product_special['priority'] . "', price = '" . (float)$product_special['price'] . "', date_start = '" . $this->db->escape($product_special['date_start']) . "', date_end = '" . $this->db->escape($product_special['date_end']) . "'");
            }
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "product_image WHERE product_id = '" . (int)$product_id . "'");

        if (isset($data['product_image'])) {
            foreach ($data['product_image'] as $product_image) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_image SET product_id = '" . (int)$product_id . "', image = '" . $this->db->escape($product_image['image']) . "', sort_order = '" . (int)$product_image['sort_order'] . "'");
            }
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_download WHERE product_id = '" . (int)$product_id . "'");

        if (isset($data['product_download'])) {
            foreach ($data['product_download'] as $download_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_download SET product_id = '" . (int)$product_id . "', download_id = '" . (int)$download_id . "'");
            }
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "'");

        if (isset($data['product_category'])) {
            foreach ($data['product_category'] as $category_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int)$product_id . "', category_id = '" . (int)$category_id . "'");
            }
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "product_filter WHERE product_id = '" . (int)$product_id . "'");

        if (isset($data['main_category_id']) && $data['main_category_id'] > 0) {
            $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "' AND category_id = '" . (int)$data['main_category_id'] . "'");
            $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int)$product_id . "', category_id = '" . (int)$data['main_category_id'] . "', main_category = 1");
        } elseif (isset($data['product_category'][0])) {
            $this->db->query("UPDATE " . DB_PREFIX . "product_to_category SET main_category = 1 WHERE product_id = '" . (int)$product_id . "' AND category_id = '" . (int)$data['product_category'][0] . "'");
        }
        
        if (isset($data['product_filter'])) {
            foreach ($data['product_filter'] as $filter_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_filter SET product_id = '" . (int)$product_id . "', filter_id = '" . (int)$filter_id . "'");
            }
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE related_id = '" . (int)$product_id . "'");

        if (isset($data['product_related'])) {
            foreach ($data['product_related'] as $related_id) {
                $this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$product_id . "' AND related_id = '" . (int)$related_id . "'");
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_related SET product_id = '" . (int)$product_id . "', related_id = '" . (int)$related_id . "'");
                $this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$related_id . "' AND related_id = '" . (int)$product_id . "'");
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_related SET product_id = '" . (int)$related_id . "', related_id = '" . (int)$product_id . "'");
            }
        }
        
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_related_article WHERE product_id = '" . (int)$product_id . "'");
        
        if (isset($data['product_related_article'])) {
            foreach ($data['product_related_article'] as $article_id) {
                $this->db->query("DELETE FROM " . DB_PREFIX . "product_related_article WHERE product_id = '" . (int)$product_id . "' AND article_id = '" . (int)$article_id . "'");
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_related_article SET product_id = '" . (int)$product_id . "', article_id = '" . (int)$article_id . "'");
            }
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "product_reward WHERE product_id = '" . (int)$product_id . "'");

        if (isset($data['product_reward'])) {
            foreach ($data['product_reward'] as $customer_group_id => $value) {
                if ((int)$value['points'] > 0) {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "product_reward SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$customer_group_id . "', points = '" . (int)$value['points'] . "'");
                }
            }
        }
        
        // SEO URL
        $this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'product_id=" . (int)$product_id . "'");
        
        if (isset($data['product_seo_url'])) {
            foreach ($data['product_seo_url']as $store_id => $language) {
                foreach ($language as $language_id => $keyword) {
                    if (!empty($keyword)) {
                        $this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET store_id = '" . (int)$store_id . "', language_id = '" . (int)$language_id . "', query = 'product_id=" . (int)$product_id . "', keyword = '" . $this->db->escape($keyword) . "'");
                    }
                }
            }
        }
        
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_layout WHERE product_id = '" . (int)$product_id . "'");

        if (isset($data['product_layout'])) {
            foreach ($data['product_layout'] as $store_id => $layout_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_layout SET product_id = '" . (int)$product_id . "', store_id = '" . (int)$store_id . "', layout_id = '" . (int)$layout_id . "'");
            }
        }

        $this->cache->delete('product');
        
        if($this->config->get('config_seo_pro')){       
        $this->cache->delete('seopro');
        }
    }

    public function deleteProduct($product_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "product WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_description WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_discount WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_filter WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_image WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_option WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_option_value WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE related_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_related_article WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_reward WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_special WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_download WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_layout WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_store WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_recurring WHERE product_id = " . (int)$product_id);
        $this->db->query("DELETE FROM " . DB_PREFIX . "review WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'product_id=" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "coupon_product WHERE product_id = '" . (int)$product_id . "'");

        $this->cache->delete('product');
        
        if($this->config->get('config_seo_pro')){       
        $this->cache->delete('seopro');
        }
    }

    public function getProduct($product_id) {
        $query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) WHERE p.product_id = '" . (int)$product_id . "' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

        return $query->row;
    }

    public function getProductsByCategoryId($category_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p2c.category_id = '" . (int)$category_id . "' ORDER BY pd.name ASC");

        return $query->rows;
    }

    public function getProductImages($product_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_image WHERE product_id = '" . (int)$product_id . "' ORDER BY sort_order ASC");

        return $query->rows;
    }

    public function addOption($data) {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "option` SET type = '" . $this->db->escape($data['type']) . "', sort_order = '" . (int)$data['sort_order'] . "'");

        $option_id = $this->db->getLastId();

        foreach ($data['option_description'] as $language_id => $value) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "option_description SET option_id = '" . (int)$option_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "'");
        }

        if (isset($data['option_value'])) {
            foreach ($data['option_value'] as $option_value) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "option_value SET option_id = '" . (int)$option_id . "', image = '" . $this->db->escape(html_entity_decode($option_value['image'], ENT_QUOTES, 'UTF-8')) . "', sort_order = '" . (int)$option_value['sort_order'] . "'");

                $option_value_id = $this->db->getLastId();

                foreach ($option_value['option_value_description'] as $language_id => $option_value_description) {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "option_value_description SET option_value_id = '" . (int)$option_value_id . "', language_id = '" . (int)$language_id . "', option_id = '" . (int)$option_id . "', name = '" . $this->db->escape($option_value_description['name']) . "'");
                }
            }
        }

        return $option_id;
    }

    public function editOption($option_id, $data) {
        $this->db->query("UPDATE `" . DB_PREFIX . "option` SET type = '" . $this->db->escape($data['type']) . "', sort_order = '" . (int)$data['sort_order'] . "' WHERE option_id = '" . (int)$option_id . "'");

        $this->db->query("DELETE FROM " . DB_PREFIX . "option_description WHERE option_id = '" . (int)$option_id . "'");

        foreach ($data['option_description'] as $language_id => $value) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "option_description SET option_id = '" . (int)$option_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "'");
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "option_value WHERE option_id = '" . (int)$option_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "option_value_description WHERE option_id = '" . (int)$option_id . "'");

        if (isset($data['option_value'])) {
            foreach ($data['option_value'] as $option_value) {
                if ($option_value['option_value_id']) {
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

    public function deleteOption($option_id) {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "option` WHERE option_id = '" . (int)$option_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "option_description WHERE option_id = '" . (int)$option_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "option_value WHERE option_id = '" . (int)$option_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "option_value_description WHERE option_id = '" . (int)$option_id . "'");
    }

    public function getOptionValues($option_id) {
        $option_value_data = array();

        $option_value_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "option_value ov LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) WHERE ov.option_id = '" . (int)$option_id . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY ov.sort_order, ovd.name");

        foreach ($option_value_query->rows as $option_value) {
            $option_value_data[] = array(
                'option_value_id' => $option_value['option_value_id'],
                'name'            => $option_value['name'],
                'image'           => $option_value['image'],
                'sort_order'      => $option_value['sort_order']
            );
        }

        return $option_value_data;
    }

    public function get_productid_by_code($code) {
        $result = $this->db->query('SELECT product_id FROM ' . DB_PREFIX . "trade_import_product_codes WHERE nomenclature_uuid = '" . $this->db->escape($code) . "'");
        if (isset($result->row['product_id'])) {
            return $result->row['product_id'];
        } else {
            return NULL;
        }
    }

    public function get_product_by_code($code) {
        return $this->getProduct($this->get_productid_by_code($code));
    }

    public function get_product_codes($data = array()) {
        $data = (array) $data;
        if (!empty($data)) {
            $result = $this->db->query("SELECT product_id, nomenclature_uuid FROM " . DB_PREFIX . "trade_import_product_codes WHERE nomenclature_uuid IN ('". implode("','", $data) . "') ORDER BY product_id ASC");
        } else {
            $result = $this->db->query('SELECT product_id, nomenclature_uuid FROM ' . DB_PREFIX . 'trade_import_product_codes ORDER BY product_id ASC');
        }
        $arr = array();
        foreach ($result->rows as $row) {
            $arr[$row['nomenclature_uuid']] = $row['product_id'];
        }
        return $arr;
    }

    public function get_products_by_categories_codes($data = array()) {
        $data = (array) $data;
        if (empty($data)) {
            return array();
        }

        $result = $this->db->query("SELECT nomenclature_uuid, group_uuid FROM " . DB_PREFIX . "trade_import_product_codes WHERE group_uuid IN ('". implode("','", $data) . "') ORDER BY nomenclature_uuid ASC");
        $arr = array();
        foreach ($result->rows as $row) {
            $arr[$row['nomenclature_uuid']] = $row['group_uuid'];
        }
        return $arr;
    }

    public function get_products_characteristics_by_categories_codes($data = array()) {
        $data = (array) $data;
        if (empty($data)) {
            return array();
        }

        $result = $this->db->query("SELECT p.*, ov.option_value_id, ov.characteristic_uuid, op.price, oov.price_old AS characteristic_price FROM " . DB_PREFIX . "trade_import_product_codes p LEFT JOIN " . DB_PREFIX . "trade_import_option_value_codes ov ON (p.nomenclature_uuid = ov.nomenclature_uuid) LEFT JOIN " . DB_PREFIX . "product op ON (p.product_id = op.product_id) LEFT JOIN " . DB_PREFIX . "product_option_value oov ON (ov.option_value_id = oov.option_value_id) WHERE p.group_uuid IN ('". implode("','", $data) . "') ORDER BY p.nomenclature_uuid ASC");
        $arr = array();
        foreach ($result->rows as $row) {
            $arr[$row['nomenclature_uuid']][] = array(
                'characteristic_uuid' => $row['characteristic_uuid'],
                'group_uuid' => $row['group_uuid'],
                'price' => $row['price'],
                'characteristic_price' => $row['characteristic_price']
            );
        }
        return $arr;
    }

    public function get_product_code_by_id($id) {
        $result = $this->db->query('SELECT nomenclature_uuid FROM ' . DB_PREFIX . "trade_import_product_codes WHERE product_id = '" . (int)$id . "'");
        if (isset($result->row['nomenclature_uuid'])) {
            return $result->row['nomenclature_uuid'];
        } else {
            return NULL;
        }
    }

    public function add_product_code($id, $code, $group_code) {
        $this->db->query('INSERT INTO ' . DB_PREFIX . 'trade_import_product_codes VALUES(' . (int)$id . ", '" . $this->db->escape($code) . "', '" . $this->db->escape($group_code) . "')");
    }

    public function edit_product_code($id, $code, $group_code) {
        $this->db->query('UPDATE ' . DB_PREFIX . "trade_import_product_codes SET nomenclature_uuid = '" . $this->db->escape($code) . "', group_uuid = '" . $this->db->escape($group_code) . "' WHERE product_id = " . (int)$id);
    }

    public function hide_products() {
        $this->db->query('UPDATE ' . DB_PREFIX . 'product SET status = 0 WHERE quantity < 1 OR price <= 0');
    }

    public function show_products() {
        $this->db->query('UPDATE ' . DB_PREFIX . 'product SET status = 1 WHERE 1');
    }

    public function get_optionid_by_code($code) {
        $result = $this->db->query('SELECT option_id FROM ' . DB_PREFIX . "trade_import_option_codes WHERE nomenclature_uuid = '" . $this->db->escape($code) . "'");
        if (isset($result->row['option_id'])) {
            return $result->row['option_id'];
        } else {
            return NULL;
        }
    }

    public function add_option_code($id, $code) {
        $this->db->query('INSERT INTO ' . DB_PREFIX . 'trade_import_option_codes VALUES(' . (int)$id . ", '" . $this->db->escape($code) . "')");
    }

    public function edit_option_code($id, $code) {
        $this->db->query('UPDATE ' . DB_PREFIX . "trade_import_option_codes SET nomenclature_uuid = '" . $this->db->escape($code) . "' WHERE option_id = " . (int)$id);
    }

    public function get_option_codes($data = array()) {
        $data = (array) $data;
        if (!empty($data)) {
            $result = $this->db->query("SELECT * FROM " . DB_PREFIX . "trade_import_option_codes WHERE nomenclature_uuid IN ('". implode("','", $data) . "')");
        } else {
            $result = $this->db->query('SELECT * FROM ' . DB_PREFIX . 'trade_import_option_codes');
        }
        $arr = array();
        foreach ($result->rows as $row) {
            $arr[$row['nomenclature_uuid']] = $row['option_id'];
        }
        return $arr;
    }

    public function get_option_value_codes($data = array()) {
        $data = (array) $data;
        if (!empty($data)) {
            $result = $this->db->query("SELECT * FROM " . DB_PREFIX . "trade_import_option_value_codes WHERE nomenclature_uuid IN ('". implode("','", $data) . "')");
        } else {
            $result = $this->db->query('SELECT * FROM ' . DB_PREFIX . 'trade_import_option_value_codes');
        }
        $arr = array();
        foreach ($result->rows as $row) {
            $arr[$row['nomenclature_uuid']][$row['characteristic_uuid']] = $row['option_value_id'];
        }
        return $arr;
    }

    public function get_option_valueid_by_code($code) {
        $result = $this->db->query('SELECT option_value_id FROM ' . DB_PREFIX . "trade_import_option_value_codes WHERE characteristic_uuid = '" . $this->db->escape($code) . "'");
        if (isset($result->row['option_value_id'])) {
            return $result->row['option_value_id'];
        } else {
            return NULL;
        }
    }

    public function get_option_value_code_by_id($id) {
        $result = $this->db->query('SELECT characteristic_uuid FROM ' . DB_PREFIX . "trade_import_option_value_codes WHERE option_value_id = '" . (int)$id . "'");
        if (isset($result->row['characteristic_uuid'])) {
            return $result->row['characteristic_uuid'];
        } else {
            return NULL;
        }
    }

    public function add_option_value_code($id, $code, $nomenclature_uuid) {
        $this->db->query('INSERT INTO ' . DB_PREFIX . 'trade_import_option_value_codes VALUES(' . (int)$id . ", '" . $this->db->escape($code) . "', '" . $this->db->escape($nomenclature_uuid) . "')");
    }

    public function edit_option_value_code($id, $code, $nomenclature_uuid) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "trade_import_option_value_codes WHERE characteristic_uuid = '" . $this->db->escape($code) . "'");
        $this->add_option_value_code($id, $code, $nomenclature_uuid);
    }

    public function get_warehouse_codes($data = array()) {
        $data = (array) $data;
        if (!empty($data)) {
            $result = $this->db->query('SELECT * FROM ' . DB_PREFIX . "trade_import_warehouse_codes WHERE storage_uuid IN ('". implode("','", $data) . "')");
        } else {
            $result = $this->db->query('SELECT * FROM ' . DB_PREFIX . 'trade_import_warehouse_codes');
        }
        $arr = array();
        foreach ($result->rows as $row) {
            $arr[$row['storage_uuid']] = $row['warehouse_id'];
        }
        return $arr;
    }

    public function get_services_codes($data = array()) {
        $data = (array) $data;
        if (!empty($data)) {
            $result = $this->db->query('SELECT * FROM ' . DB_PREFIX . "trade_import_services WHERE service_uuid IN ('". implode("','", $data) . "')");
        } else {
            $result = $this->db->query('SELECT * FROM ' . DB_PREFIX . 'trade_import_services');
        }
        $arr = array();
        foreach ($result->rows as $row) {
            $arr[$row['service_uuid']] = $row['id'];
        }
        return $arr;
    }

    public function get_stocks_codes($data = array()) {
        $data = (array) $data;
        if (!empty($data)) {
            $result = $this->db->query('SELECT * FROM ' . DB_PREFIX . "trade_import_stocks_codes WHERE stocks_uuid IN ('". implode("','", $data) . "')");
        } else {
            $result = $this->db->query('SELECT * FROM ' . DB_PREFIX . 'trade_import_stocks_codes');
        }
        $arr = array();
        foreach ($result->rows as $row) {
            $arr[$row['stocks_uuid']] = $row['stocks_id'];
        }
        return $arr;
    }

    public function get_option_characteristic_codes($data = array()) {
        $data = (array) $data;
        if (!empty($data)) {
            $result = $this->db->query('SELECT * FROM ' . DB_PREFIX . "trade_import_option_characteristic_codes WHERE property_uuid IN ('". implode("','", $data) . "')");
        } else {
            $result = $this->db->query('SELECT * FROM ' . DB_PREFIX . 'trade_import_option_characteristic_codes');
        }
        $arr = array();
        foreach ($result->rows as $row) {
            $arr[$row['property_uuid']] = $row['characteristic_id'];
        }
        return $arr;
    }

    public function get_filter_group_codes($data = array()) {
        $data = (array) $data;
        if (!empty($data)) {
            $result = $this->db->query('SELECT * FROM ' . DB_PREFIX . "trade_import_filter_group_codes WHERE filter_uuid IN ('". implode("','", $data) . "')");
        } else {
            $result = $this->db->query('SELECT * FROM ' . DB_PREFIX . 'trade_import_filter_group_codes');
        }
        $arr = array();
        foreach ($result->rows as $row) {
            $arr[$row['filter_uuid']] = $row['filter_group_id'];
        }
        return $arr;
    }

    public function get_filter_by_group_id($group_id) {
        $group_id = (array) $group_id;
        $result = $this->db->query('SELECT * FROM ' . DB_PREFIX . 'filter WHERE filter_group_id IN (' . implode(",", $group_id) . ')');
        $arr = array();
        foreach ($result->rows as $row) {
            $arr[$row['filter_group_id']][] = $row['filter_id'];
        }
        return $arr;
    }

    public function get_filter_by_name($name) {
        $name = (array) $name;
        if (empty($name)) {
            return NULL;
        }
        $result = $this->db->query('SELECT filter_id, filter_group_id FROM ' . DB_PREFIX . "filter_description WHERE name IN ('" . implode("','", $name) . "')");
        $arr = array();
        foreach ($result->rows as $row) {
            $arr[$row['filter_group_id']][] = $row['filter_id'];
        }
        return $arr;
    }

    public function get_stocks_banner_ids($data = array()) {
        $data = (array) $data;
        if (!empty($data)) {
            $result = $this->db->query('SELECT * FROM ' . DB_PREFIX . "trade_import_stocks_banner WHERE stocks_uuid IN ('". implode("','", $data) . "')");
        } else {
            $result = $this->db->query('SELECT * FROM ' . DB_PREFIX . 'trade_import_stocks_banner');
        }
        $arr = array();
        foreach ($result->rows as $row) {
            $arr[$row['stocks_uuid']][] = $row['banner_image_id'];
        }
        return $arr;
    }

    public function delete_product_code($code) {
        $this->deleteOption($this->get_optionid_by_code($code));
        $this->db->query("DELETE FROM " . DB_PREFIX . "trade_import_product_codes WHERE nomenclature_uuid = '" . $this->db->escape($code) . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "trade_import_option_codes WHERE nomenclature_uuid = '" . $this->db->escape($code) . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "trade_import_option_value_codes WHERE nomenclature_uuid = '" . $this->db->escape($code) . "'");
    }

    public function add_operation($json_timestamp, $success, $response = "") {
        $this->db->query('INSERT INTO ' . DB_PREFIX . "trade_import VALUES (NULL, CURRENT_TIMESTAMP, '" . $this->db->escape($json_timestamp) . "', '" . (bool)$success . "', '" . $this->db->escape($response) . "')");
    }

    public function get_latest_operation() {
        $result = $this->db->query("SHOW TABLES LIKE '%" . DB_PREFIX . "trade_import%'");
        $key = array_keys($result->rows[0]);
        $key = $key[0];
        if (array_search(DB_PREFIX . 'trade_import', array_column($result->rows, $key)) !== null) {
            $result = $this->db->query('SELECT MAX(operation_id) FROM ' . DB_PREFIX . "trade_import");
            $id = $result->row['MAX(operation_id)']; 
            $result = $this->db->query('SELECT * FROM ' . DB_PREFIX . "trade_import WHERE operation_id = '" . (int)$id . "'");
            return $result->row;
        }
    }

    public function add_order($order_data, $order_id, $response) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "trade_orders VALUES (NULL, '" . (int)$order_id . "', '" . $this->db->escape($order_data) . "', '" . $this->db->escape($response) . "')");
    }

    public function get_orders() {
        $result = $this->db->query("SELECT * FROM " . DB_PREFIX . "trade_orders ORDER BY operation_id DESC");
        return $result->rows;
    }

    public function get_order_address($order_id) {
        $result = $this->db->query("SELECT payment_address_1 FROM " . DB_PREFIX . "order WHERE order_id = '" . (int)$order_id . "'");
        if (isset($result->row['payment_address_1'])) {
            return $result->row['payment_address_1'];
        } else {
            return NULL;
        }
    }

     public function add_check($order_id, $order_data, $response) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "trade_checks VALUES ('', '" . (int)$order_id . "', '" . $this->db->escape($order_data) . "', '" . $this->db->escape($response) . "')");
    }

    public function get_checks() {
        $result = $this->db->query("SELECT * FROM " . DB_PREFIX . "trade_checks ORDER BY check_id DESC");
        return $result->rows;
    }

    public function editSettingValue($code = '', $key = '', $value = '', $store_id = 0) {
        if (!is_array($value)) {
            $this->db->query("UPDATE " . DB_PREFIX . "setting SET `value` = '" . $this->db->escape($value) . "', serialized = '0'  WHERE `code` = '" . $this->db->escape($code) . "' AND `key` = '" . $this->db->escape($key) . "' AND store_id = '" . (int)$store_id . "'");
        } else {
            $this->db->query("UPDATE " . DB_PREFIX . "setting SET `value` = '" . $this->db->escape(json_encode($value)) . "', serialized = '1' WHERE `code` = '" . $this->db->escape($code) . "' AND `key` = '" . $this->db->escape($key) . "' AND store_id = '" . (int)$store_id . "'");
        }
    }

    //Image Caching
    public function get_all_images() {
        $query = $this->db->query("SELECT `image` FROM " . DB_PREFIX . "product WHERE `image` <> '' UNION ALL SELECT `image` FROM " . DB_PREFIX . "product_image WHERE `image` <> '' GROUP BY `image`");
        return array_column($query->rows, 'image');
    }

    public function resize($filename, $width, $height) {
        if (!is_file(DIR_IMAGE . $filename) || substr(str_replace('\\', '/', realpath(DIR_IMAGE . $filename)), 0, strlen(DIR_IMAGE)) != str_replace('\\', '/', DIR_IMAGE)) {
            return;
        }

        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        $image_old = $filename;
        $image_new = 'cache/' . utf8_substr($filename, 0, utf8_strrpos($filename, '.')) . '-' . (int)$width . 'x' . (int)$height . '.' . $extension;

        if (!is_file(DIR_IMAGE . $image_new) || (filemtime(DIR_IMAGE . $image_old) > filemtime(DIR_IMAGE . $image_new))) {
            list($width_orig, $height_orig, $image_type) = getimagesize(DIR_IMAGE . $image_old);
                 
            if (!in_array($image_type, array(IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF))) { 
                return DIR_IMAGE . $image_old;
            }
                        
            $path = '';

            $directories = explode('/', dirname($image_new));

            foreach ($directories as $directory) {
                $path = $path . '/' . $directory;

                if (!is_dir(DIR_IMAGE . $path)) {
                    @mkdir(DIR_IMAGE . $path, 0777);
                }
            }

            if ($width_orig != $width || $height_orig != $height) {
                $image = new Image(DIR_IMAGE . $image_old);
                $image->resize($width, $height);
                $image->save(DIR_IMAGE . $image_new);
            } else {
                copy(DIR_IMAGE . $image_old, DIR_IMAGE . $image_new);
            }
        }
        
        $image_new = str_replace(' ', '%20', $image_new);  // fix bug when attach image on email (gmail.com). it is automatic changing space " " to +
        
        if ($this->request->server['HTTPS']) {
            return $this->config->get('config_ssl') . 'image/' . $image_new;
        } else {
            return $this->config->get('config_url') . 'image/' . $image_new;
        }
    }

    public function cache_images($debug = false) {
        $images = $this->get_all_images();
        $resizes = array(
            'popup' => array(
                'width' => $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_width'),
                'height' => $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_height')
            ),
            'thumb' => array(
                'width' => $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_width'),
                'height' => $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_height')
            ),
            'additional' => array(
                'width' => $this->config->get('theme_' . $this->config->get('config_theme') . '_image_additional_width'),
                'height' => $this->config->get('theme_' . $this->config->get('config_theme') . '_image_additional_height')
            ),
            'related' => array(
                'width' => $this->config->get('theme_' . $this->config->get('config_theme') . '_image_related_width'),
                'height' => $this->config->get('theme_' . $this->config->get('config_theme') . '_image_related_height')
            ),
            'option_value' => array(
                'width' => 50,
                'height' => 50
            )
        );
        foreach ($images as $image) {
            if ($debug) {
                echo "{$image} cached\n";
            }
            foreach ($resizes as $resize) {
                $this->resize($image, $resize['width'], $resize['height']);
            }
        }
    }

    public function get_images($data = array()) {
        $query = $this->db->query('SELECT * FROM ' . DB_PREFIX . 'trade_images' . (
            !empty($data['group']) ? (" GROUP BY `" . $this->db->escape($data['group']) . "`") : NULL));
        $result = array();
        foreach ($query->rows as $row) {
            $result[$row['image_id']] = array(
                'id' => $row['id'],
                'image_id' => $row['image_id'],
                'path' => $row['path'],
                'hash' => $row['hash'],
                'size' => $row['size']
            );
        }
        return $result;
    }

    public function get_image_hashes() {
        $query = $this->db->query('SELECT * FROM ' . DB_PREFIX . 'trade_images GROUP BY `hash`');
        $result = array();
        foreach ($query->rows as $row) {
            $result[$row['hash']] = $row['image_id'];
        }
        return $result;
    }

    public function get_image_filesizes() {
        $query = $this->db->query('SELECT * FROM ' . DB_PREFIX . 'trade_images GROUP BY `size`');
        $result = array();
        foreach ($query->rows as $row) {
            $result[$row['size']] = $row['image_id'];
        }
        return $result;
    }
}