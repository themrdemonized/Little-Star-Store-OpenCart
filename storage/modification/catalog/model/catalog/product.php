<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ModelCatalogProduct extends Model {
	public function updateViewed($product_id) {
		$this->db->query("UPDATE " . DB_PREFIX . "product SET viewed = (viewed + 1) WHERE product_id = '" . (int)$product_id . "'");
	}

	public function getProduct($product_id) {
		$query = $this->db->query("SELECT DISTINCT *, pd.name AS name, p.image, p.noindex AS noindex, m.name AS manufacturer, (SELECT price FROM " . DB_PREFIX . "product_discount pd2 WHERE pd2.product_id = p.product_id AND pd2.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND pd2.quantity = '1' AND ((pd2.date_start = '0000-00-00' OR pd2.date_start < NOW()) AND (pd2.date_end = '0000-00-00' OR pd2.date_end > NOW())) ORDER BY pd2.priority ASC, pd2.price ASC LIMIT 1) AS discount, (SELECT price FROM " . DB_PREFIX . "product_special ps WHERE ps.product_id = p.product_id AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())) ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) AS special, (SELECT discount FROM " . DB_PREFIX . "product_special ps WHERE ps.product_id = p.product_id AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())) ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) AS special_discount, (SELECT points FROM " . DB_PREFIX . "product_reward pr WHERE pr.product_id = p.product_id AND pr.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "') AS reward, (SELECT ss.name FROM " . DB_PREFIX . "stock_status ss WHERE ss.stock_status_id = p.stock_status_id AND ss.language_id = '" . (int)$this->config->get('config_language_id') . "') AS stock_status, (SELECT wcd.unit FROM " . DB_PREFIX . "weight_class_description wcd WHERE p.weight_class_id = wcd.weight_class_id AND wcd.language_id = '" . (int)$this->config->get('config_language_id') . "') AS weight_class, (SELECT lcd.unit FROM " . DB_PREFIX . "length_class_description lcd WHERE p.length_class_id = lcd.length_class_id AND lcd.language_id = '" . (int)$this->config->get('config_language_id') . "') AS length_class, (SELECT AVG(rating) AS total FROM " . DB_PREFIX . "review r1 WHERE r1.product_id = p.product_id AND r1.status = '1' GROUP BY r1.product_id) AS rating, (SELECT COUNT(*) AS total FROM " . DB_PREFIX . "review r2 WHERE r2.product_id = p.product_id AND r2.status = '1' GROUP BY r2.product_id) AS reviews, p.sort_order FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_description_composition pdc ON (p.product_id = pdc.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) LEFT JOIN " . DB_PREFIX . "manufacturer m ON (p.manufacturer_id = m.manufacturer_id) WHERE p.product_id = '" . (int)$product_id . "' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'");

		if ($query->num_rows) {
			return array(
				'product_id'       => $query->row['product_id'],
				'name'             => $query->row['name'],
				'measure_name'	   => $query->row['measure_name'],
				'description'      => $query->row['description'],
				'meta_title'       => $query->row['meta_title'],
				'noindex'          => $query->row['noindex'],
				'meta_h1'	       => $query->row['meta_h1'],
				'meta_description' => $query->row['meta_description'],
				'meta_keyword'     => $query->row['meta_keyword'],
				'composition'	   => $query->row['composition'],
				'tag'              => $query->row['tag'],
				'model'            => $query->row['model'],
				'sku'              => $query->row['sku'],
				'upc'              => $query->row['upc'],
				'ean'              => $query->row['ean'],
				'jan'              => $query->row['jan'],
				'isbn'             => $query->row['isbn'],
				'mpn'              => $query->row['mpn'],
				'location'         => $query->row['location'],
				'quantity'         => $query->row['quantity'],
				'stock_status'     => $query->row['stock_status'],
				'image'            => $query->row['image'],
				'manufacturer_id'  => $query->row['manufacturer_id'],
				'manufacturer'     => $query->row['manufacturer'],
				'price'            => ($query->row['discount'] ? $query->row['discount'] : $query->row['price']),
				'special'          => $query->row['special'],
				'special_discount' => $query->row['special_discount'],
				'reward'           => $query->row['reward'],
				'points'           => $query->row['points'],
				'tax_class_id'     => $query->row['tax_class_id'],
				'date_available'   => $query->row['date_available'],
				'weight'           => $query->row['weight'],
				'weight_class_id'  => $query->row['weight_class_id'],
				'length'           => $query->row['length'],
				'width'            => $query->row['width'],
				'height'           => $query->row['height'],
				'length_class_id'  => $query->row['length_class_id'],
				'subtract'         => $query->row['subtract'],
				'rating'           => round($query->row['rating']),
				'reviews'          => $query->row['reviews'] ? $query->row['reviews'] : 0,
				'minimum'          => $query->row['minimum'],
				'sort_order'       => $query->row['sort_order'],
				'status'           => $query->row['status'],
				'date_added'       => $query->row['date_added'],
				'date_modified'    => $query->row['date_modified'],
				'viewed'           => $query->row['viewed']
			);
		} else {
			return false;
		}
	}

	public function getProducts($data = array()) {
		$sql = "SELECT p.product_id, (SELECT AVG(rating) AS total FROM " . DB_PREFIX . "review r1 WHERE r1.product_id = p.product_id AND r1.status = '1' GROUP BY r1.product_id) AS rating, (SELECT price FROM " . DB_PREFIX . "product_discount pd2 WHERE pd2.product_id = p.product_id AND pd2.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND pd2.quantity = '1' AND ((pd2.date_start = '0000-00-00' OR pd2.date_start < NOW()) AND (pd2.date_end = '0000-00-00' OR pd2.date_end > NOW())) ORDER BY pd2.priority ASC, pd2.price ASC LIMIT 1) AS discount, (SELECT price FROM " . DB_PREFIX . "product_special ps WHERE ps.product_id = p.product_id AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())) ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) AS special";

		if (!empty($data['filter_category_id'])) {
			if (!empty($data['filter_sub_category'])) {
				$sql .= " FROM " . DB_PREFIX . "category_path cp LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (cp.category_id = p2c.category_id)";
			} else {
				$sql .= " FROM " . DB_PREFIX . "product_to_category p2c";
			}

			if (!empty($data['filter_filter'])) {
				$sql .= " LEFT JOIN " . DB_PREFIX . "product_filter pf ON (p2c.product_id = pf.product_id) LEFT JOIN " . DB_PREFIX . "product p ON (pf.product_id = p.product_id)";
			} else {
				$sql .= " LEFT JOIN " . DB_PREFIX . "product p ON (p2c.product_id = p.product_id)";
			}
		} else {
			$sql .= " FROM " . DB_PREFIX . "product p";
		}


// ExtendedSearch
		if ((!empty($data['filter_name'])) && $this->config->get('module_extendedsearch_status') && $this->config->get('module_extendedsearch_attr')) $sql .= " LEFT JOIN " . DB_PREFIX . "product_attribute pa ON (p.product_id = pa.product_id) ";
// ExtendedSearch END
			

		// OCFilter start
		if (!empty($data['filter_ocfilter'])) {
    	$this->load->model('extension/module/ocfilter');

      $ocfilter_product_sql = $this->model_extension_module_ocfilter->getSearchSQL($data['filter_ocfilter']);
		} else {
      $ocfilter_product_sql = false;
    }

    if ($ocfilter_product_sql && $ocfilter_product_sql->join) {
    	$sql .= $ocfilter_product_sql->join;
    }
    // OCFilter end
      
		$sql .= " LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'";

		if (!empty($data['filter_category_id'])) {
			if (!empty($data['filter_sub_category'])) {
				$sql .= " AND cp.path_id = '" . (int)$data['filter_category_id'] . "'";
			} else {
				$sql .= " AND p2c.category_id = '" . (int)$data['filter_category_id'] . "'";
			}

			if (!empty($data['filter_filter'])) {
				$implode = array();

				$filters = explode(',', $data['filter_filter']);

				foreach ($filters as $filter_id) {
					$implode[] = (int)$filter_id;
				}

				$sql .= " AND pf.filter_id IN (" . implode(',', $implode) . ")";
			}
		}

		if (!empty($data['filter_name']) || !empty($data['filter_tag'])) {
			$sql .= " AND (";

			if (!empty($data['filter_name'])) {
				$implode = array();

				$words = explode(' ', trim(preg_replace('/\s+/', ' ', $data['filter_name'])));

				foreach ($words as $word) {
					
// ExtendedSearch
					$adw_es = 'module_extendedsearch_';
					$es = " (LCASE(pd.name) LIKE '%" . $this->db->escape(utf8_strtolower($word)) . "%'";
				if ($this->config->get($adw_es.'status') && $this->config->get($adw_es.'model')) $es .= " OR LCASE(p.model) LIKE '%" . $this->db->escape(utf8_strtolower($word)) . "%'";
				if ($this->config->get($adw_es.'status') && $this->config->get($adw_es.'sku'))$es .= " OR LCASE(p.sku) LIKE '%" . $this->db->escape(utf8_strtolower($word)) . "%'";
				if ($this->config->get($adw_es.'status') && $this->config->get($adw_es.'upc'))$es .= " OR LCASE(p.upc) LIKE '%" . $this->db->escape(utf8_strtolower($word)) . "%'";
				if ($this->config->get($adw_es.'status') && $this->config->get($adw_es.'ean'))$es .= " OR LCASE(p.ean) LIKE '%" . $this->db->escape(utf8_strtolower($word)) . "%'";
				if ($this->config->get($adw_es.'status') && $this->config->get($adw_es.'jan'))$es .= " OR LCASE(p.jan) LIKE '%" . $this->db->escape(utf8_strtolower($word)) . "%'";
				if ($this->config->get($adw_es.'status') && $this->config->get($adw_es.'isbn'))$es .= " OR LCASE(p.isbn) LIKE '%" . $this->db->escape(utf8_strtolower($word)) . "%'";
				if ($this->config->get($adw_es.'status') && $this->config->get($adw_es.'mpn'))$es .= " OR LCASE(p.mpn) LIKE '%" . $this->db->escape(utf8_strtolower($word)) . "%'";
				if ($this->config->get($adw_es.'status') && $this->config->get($adw_es.'location'))$es .= " OR LCASE(p.location) LIKE '%" . $this->db->escape(utf8_strtolower($word)) . "%'";
				if ($this->config->get($adw_es.'status') && $this->config->get($adw_es.'attr'))$es .= " OR LCASE(pa.text) LIKE '%" . $this->db->escape(utf8_strtolower($word)) . "%'";
					$es .= ") ";
					$implode[] = $es;
// ExtendedSearch END
			
				}

				if ($implode) {
					$sql .= " " . implode(" AND ", $implode) . "";
				}

				if (!empty($data['filter_description'])) {
					$sql .= " OR pd.description LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
				}
			}

			if (!empty($data['filter_name']) && !empty($data['filter_tag'])) {
				$sql .= " OR ";
			}

			if (!empty($data['filter_tag'])) {
				$implode = array();

				$words = explode(' ', trim(preg_replace('/\s+/', ' ', $data['filter_tag'])));

				foreach ($words as $word) {
					$implode[] = "pd.tag LIKE '%" . $this->db->escape($word) . "%'";
				}

				if ($implode) {
					$sql .= " " . implode(" AND ", $implode) . "";
				}
			}

			if (!empty($data['filter_name'])) {
				
// ExtendedSearch
			if (!$this->config->get('module_extendedsearch_status') && !$this->config->get('module_extendedsearch_model')) {
				$sql .= " OR LCASE(p.model) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
			}
// ExtendedSearch END
			
				
// ExtendedSearch
			if (!$this->config->get('module_extendedsearch_status') && !$this->config->get('module_extendedsearch_sku')) {
				$sql .= " OR LCASE(p.sku) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
			}
// ExtendedSearch END
			
				
// ExtendedSearch
			if (!$this->config->get('module_extendedsearch_status') && !$this->config->get('module_extendedsearch_upc')) {
				$sql .= " OR LCASE(p.upc) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
			}
// ExtendedSearch END
			
				
// ExtendedSearch
			if (!$this->config->get('module_extendedsearch_status') && !$this->config->get('module_extendedsearch_ean')) {
				$sql .= " OR LCASE(p.ean) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
			}
// ExtendedSearch END
			
				
// ExtendedSearch
			if (!$this->config->get('module_extendedsearch_status') && !$this->config->get('module_extendedsearch_jan')) {
				$sql .= " OR LCASE(p.jan) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
			}
// ExtendedSearch END
			
				
// ExtendedSearch
			if (!$this->config->get('module_extendedsearch_status') && !$this->config->get('module_extendedsearch_isbn')) {
				$sql .= " OR LCASE(p.isbn) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
			}
// ExtendedSearch END
			
				
// ExtendedSearch
			if (!$this->config->get('module_extendedsearch_status') && !$this->config->get('module_extendedsearch_mpn')) {
				$sql .= " OR LCASE(p.mpn) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
			}
// ExtendedSearch END
			
			}

			$sql .= ")";
		}


    // OCFilter start
    if (!empty($ocfilter_product_sql) && $ocfilter_product_sql->where) {
    	$sql .= $ocfilter_product_sql->where;
    }
    // OCFilter end
      
		if (!empty($data['filter_manufacturer_id'])) {
			$sql .= " AND p.manufacturer_id = '" . (int)$data['filter_manufacturer_id'] . "'";
		}

		$sql .= " GROUP BY p.product_id";

		$sort_data = array(
			'pd.name',
			'p.model',
			'p.quantity',
			'p.price',
			'rating',
			'p.sort_order',
			'p.date_added',
			'p.viewed',
			"RAND()"
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			if ($data['sort'] == 'pd.name' || $data['sort'] == 'p.model') {
				$sql .= " ORDER BY LCASE(" . $data['sort'] . ")";
			} elseif ($data['sort'] == 'p.price') {
				$sql .= " ORDER BY (CASE WHEN special IS NOT NULL THEN special WHEN discount IS NOT NULL THEN discount ELSE p.price END)";
			} else {
				$sql .= " ORDER BY " . $data['sort'];
			}
		} else {
			$sql .= " ORDER BY p.sort_order";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC, LCASE(pd.name) DESC";
		} else {
			$sql .= " ASC, LCASE(pd.name) ASC";
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

		$product_data = array();

		$query = $this->db->query($sql);

		foreach ($query->rows as $result) {
			$product_data[$result['product_id']] = $this->getProduct($result['product_id']);
		}

		return $product_data;
	}

	public function getProductsByCategories($data = array()) {
		if (!isset($data['parent_id'])) {
			$data['parent_id'] = 0;
		}

		if (!isset($data['sort_order'])) {
			$data['sort_order'] = "product_name ASC";
		}

		$query_category_where_info = "c.parent_id = {$data['parent_id']}";
		if (isset($data['category_id'])) {
			$query_category_where_info = "c.category_id IN (" . implode(",", (array) $data['category_id']) . ")";
		}

		$db_prefix = DB_PREFIX;
		if (isset($data['query_product_limit'])) {
			$product_limit = <<<EOD
			(SELECT pc.product_id, pc.category_id FROM (
				        SELECT
				            pc2.product_id,
				            pc2.category_id,
				            @rn := IF(@prev = pc2.category_id, @rn + 1, 1) AS rn,
				            @prev := pc2.category_id
				        FROM `{$db_prefix}product_to_category` pc2
				        JOIN (SELECT @prev := NULL, @rn := 0) AS vars
				        ORDER BY pc2.category_id ASC
				    ) pc WHERE rn <= {$data['query_product_limit']})
EOD;
		} else {
			$product_limit = "`{$db_prefix}product_to_category`";
		}

		$sql = <<<EOD
			SELECT * FROM (
				SELECT
				ptc.category_id,
				ptc.product_id,
				c.image AS category_image,
				c.parent_id,
				c.top,
				c.column,
				c.sort_order AS category_sort_order,
				c.status AS category_status,
				c.date_added AS category_date_added,
				c.date_modified AS category_date_modified,
				c.noindex AS category_noindex,
				cd.name AS category_name,
				cd.description AS category_description,
				cd.meta_title AS category_meta_title,
				cd.meta_description AS category_meta_description,
				cd.meta_keyword AS category_meta_keyword,
				cd.meta_h1 AS category_meta_h1,
				p.model,
				p.sku,
				p.upc,
				p.ean,
				p.jan,
				p.isbn,
				p.mpn,
				p.location,
				p.quantity,
				p.stock_status_id,
				p.image AS product_image,
				p.manufacturer_id,
				p.shipping,
				p.price,
				p.points,
				p.tax_class_id,
				p.date_available AS product_date_available, 
				p.weight,
				p.weight_class_id,
				p.length,
				p.width,
				p.height,
				p.length_class_id,
				p.measure_name,
				p.subtract,
				p.minimum,
				p.sort_order AS product_sort_order,
				p.status AS product_status,
				p.viewed,
				p.date_added AS product_date_added,
				p.date_modified AS product_date_modified,
				p.noindex AS product_noindex,
				pd.name AS product_name,
				pd.description AS product_description,
				pd.tag AS product_tag,
				pd.meta_title AS product_meta_title,
				pd.meta_description AS product_meta_description,
				pd.meta_keyword AS product_meta_keyword,
				pd.meta_h1 AS product_meta_h1,
				pdd.discount,
				pss.special,
				pss.special_discount AS special_discount,
				prr.reward,
				ss.name AS stock_status_name,
				wcd.title AS weight_class_title,
				wcd.unit AS weight_class_unit,
				lcd.title AS length_class_title,
				lcd.unit AS length_class_unit,
				r1r.rating,
				r2r.reviews,
				pdc.composition,
				p2s.store_id,
				m.name AS manufacturer_name,
				m.image AS manufacturer_image,
				m.sort_order AS manufacturer_sort_order,
				m.noindex AS manufacturer_noindex
				FROM {$db_prefix}category c
				LEFT JOIN `{$db_prefix}category_description` cd ON (c.category_id = cd.category_id AND cd.language_id = '{$this->config->get('config_language_id')}')
				RIGHT JOIN {$product_limit} ptc ON (c.category_id = ptc.category_id)
				LEFT JOIN `{$db_prefix}product` p ON (ptc.product_id = p.product_id) 
				LEFT JOIN `{$db_prefix}product_description` pd ON (ptc.product_id = pd.product_id AND pd.language_id = '{$this->config->get('config_language_id')}')
				LEFT JOIN (
				    SELECT pd2.price AS discount, pd2.product_id FROM {$db_prefix}product_discount pd2 WHERE pd2.customer_group_id = '{$this->config->get('config_customer_group_id')}' AND pd2.quantity = '1' AND ((pd2.date_start = '0000-00-00' OR pd2.date_start < NOW()) AND (pd2.date_end = '0000-00-00' OR pd2.date_end > NOW())) ORDER BY pd2.product_id, pd2.priority ASC, pd2.price ASC
				) pdd ON (ptc.product_id = pdd.product_id)
				LEFT JOIN (
				    SELECT ps.price AS special, ps.discount AS special_discount, ps.product_id FROM {$db_prefix}product_special ps WHERE ps.customer_group_id = '{$this->config->get('config_customer_group_id')}' AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())) ORDER BY ps.product_id, ps.priority ASC, ps.price ASC
				) pss ON (ptc.product_id = pss.product_id)
				LEFT JOIN (
				    SELECT pr.points AS reward, pr.product_id FROM {$db_prefix}product_reward pr WHERE pr.customer_group_id = '{$this->config->get('config_customer_group_id')}' ORDER BY pr.product_id
				) prr ON (ptc.product_id = pss.product_id)
				LEFT JOIN {$db_prefix}stock_status ss ON (p.stock_status_id = ss.stock_status_id AND ss.language_id = '{$this->config->get('config_language_id')}')
				LEFT JOIN {$db_prefix}weight_class_description wcd ON (wcd.weight_class_id = p.weight_class_id AND wcd.language_id = '{$this->config->get('config_language_id')}')
				LEFT JOIN {$db_prefix}length_class_description lcd ON (lcd.length_class_id = p.length_class_id AND lcd.language_id = '{$this->config->get('config_language_id')}')
				LEFT JOIN (
				    SELECT AVG(r1.rating) AS rating, r1.product_id FROM {$db_prefix}review r1 WHERE r1.status = '1' GROUP BY r1.product_id
				) r1r ON (r1r.product_id = p.product_id)
				LEFT JOIN (
				    SELECT COUNT(*) AS reviews, r2.product_id FROM {$db_prefix}review r2 WHERE r2.status = '1' GROUP BY r2.product_id
				) r2r ON (r2r.product_id = p.product_id)
				LEFT JOIN {$db_prefix}product_description_composition pdc ON (p.product_id = pdc.product_id AND pdc.language_id = '{$this->config->get('config_language_id')}')
				LEFT JOIN {$db_prefix}product_to_store p2s ON (p.product_id = p2s.product_id)
				LEFT JOIN {$db_prefix}manufacturer m ON (p.manufacturer_id = m.manufacturer_id)
				WHERE {$query_category_where_info} AND c.status = 1 AND p.status = 1
				ORDER BY ptc.category_id ASC, ptc.product_id ASC
			) t1 ORDER BY category_sort_order DESC, category_name ASC, product_sort_order DESC, {$data['sort_order']}
EOD;

		$query = $this->db->query($sql);

		if (!isset($data['return_raw'])) {
			$data['return_raw'] = false;
		}

		if ($data['return_raw']) {
			return $query->rows;
		} else {
			$product_data = array();

			$i = 0;
			$last_category_id = 0;
			$product_limit = 8;
			if (isset($data['product_limit'])) {
				$product_limit = $data['product_limit'];
			}

			foreach ($query->rows as $result) {
				if ($i >= $product_limit && $last_category_id == $result['category_id']) {
					continue;
				}
				if ($last_category_id != $result['category_id']) {
					$i = 0;
					$last_category_id = $result['category_id'];
				}
				$product_data[$result['category_id']]['category_id'] = $result['category_id'];
				$product_data[$result['category_id']]['category_image'] = $result['category_image'];
				$product_data[$result['category_id']]['parent_id'] = $result['parent_id'];
				$product_data[$result['category_id']]['top'] = $result['top'];
				$product_data[$result['category_id']]['column'] = $result['column'];
				$product_data[$result['category_id']]['category_sort_order'] = $result['category_sort_order'];
				$product_data[$result['category_id']]['category_status'] = $result['category_status'];
				$product_data[$result['category_id']]['category_date_added'] = $result['category_date_added'];
				$product_data[$result['category_id']]['category_date_modified'] = $result['category_date_modified'];
				$product_data[$result['category_id']]['category_noindex'] = $result['category_noindex'];
				$product_data[$result['category_id']]['category_name'] = $result['category_name'];
				$product_data[$result['category_id']]['category_description'] = $result['category_description'];
				$product_data[$result['category_id']]['category_meta_title'] = $result['category_meta_title'];
				$product_data[$result['category_id']]['category_meta_description'] = $result['category_meta_description'];
				$product_data[$result['category_id']]['category_meta_keyword'] = $result['category_meta_keyword'];
				$product_data[$result['category_id']]['category_meta_h1'] = $result['category_meta_h1'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['product_id'] = $result['product_id'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['model'] = $result['model'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['sku'] = $result['sku'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['upc'] = $result['upc'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['ean'] = $result['ean'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['jan'] = $result['jan'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['isbn'] = $result['isbn'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['mpn'] = $result['mpn'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['location'] = $result['location'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['quantity'] = $result['quantity'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['stock_status_id'] = $result['stock_status_id'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['product_image'] = $result['product_image'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['manufacturer_id'] = $result['manufacturer_id'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['shipping'] = $result['shipping'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['price'] = $result['price'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['points'] = $result['points'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['tax_class_id'] = $result['tax_class_id'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['product_date_available'] = $result['product_date_available'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['weight'] = $result['weight'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['weight_class_id'] = $result['weight_class_id'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['length'] = $result['length'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['width'] = $result['width'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['height'] = $result['height'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['length_class_id'] = $result['length_class_id'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['measure_name'] = $result['measure_name'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['subtract'] = $result['subtract'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['minimum'] = $result['minimum'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['product_sort_order'] = $result['product_sort_order'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['product_status'] = $result['product_status'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['viewed'] = $result['viewed'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['product_date_added'] = $result['product_date_added'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['product_date_modified'] = $result['product_date_modified'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['product_noindex'] = $result['product_noindex'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['product_name'] = $result['product_name'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['product_description'] = $result['product_description'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['product_tag'] = $result['product_tag'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['product_meta_title'] = $result['product_meta_title'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['product_meta_description'] = $result['product_meta_description'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['product_meta_keyword'] = $result['product_meta_keyword'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['product_meta_h1'] = $result['product_meta_h1'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['discount'] = $result['discount'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['special'] = $result['special'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['special_discount'] = $result['special_discount'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['reward'] = $result['reward'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['stock_status_name'] = $result['stock_status_name'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['weight_class_title'] = $result['weight_class_title'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['weight_class_unit'] = $result['weight_class_unit'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['length_class_title'] = $result['length_class_title'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['length_class_unit'] = $result['length_class_unit'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['rating'] = $result['rating'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['reviews'] = $result['reviews'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['composition'] = $result['composition'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['store_id'] = $result['store_id'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['manufacturer_name'] = $result['manufacturer_name'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['manufacturer_image'] = $result['manufacturer_image'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['manufacturer_sort_order'] = $result['manufacturer_sort_order'];
				$product_data[$result['category_id']]['products'][$result['product_id']]['manufacturer_noindex'] = $result['manufacturer_noindex'];
				$i++;

			}
			return $product_data;
		}
	}

	public function getProductSpecials($data = array()) {
		$sql = "SELECT DISTINCT ps.product_id, (SELECT AVG(rating) FROM " . DB_PREFIX . "review r1 WHERE r1.product_id = ps.product_id AND r1.status = '1' GROUP BY r1.product_id) AS rating FROM " . DB_PREFIX . "product_special ps LEFT JOIN " . DB_PREFIX . "product p ON (ps.product_id = p.product_id) LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())) GROUP BY ps.product_id";

		$sort_data = array(
			'pd.name',
			'p.model',
			'ps.price',
			'rating',
			'p.sort_order'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			if ($data['sort'] == 'pd.name' || $data['sort'] == 'p.model') {
				$sql .= " ORDER BY LCASE(" . $data['sort'] . ")";
			} else {
				$sql .= " ORDER BY " . $data['sort'];
			}
		} else {
			$sql .= " ORDER BY p.sort_order";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC, LCASE(pd.name) DESC";
		} else {
			$sql .= " ASC, LCASE(pd.name) ASC";
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

		$product_data = array();

		$query = $this->db->query($sql);

		foreach ($query->rows as $result) {
			$product_data[$result['product_id']] = $this->getProduct($result['product_id']);
		}

		return $product_data;
	}

	public function getLatestProducts($limit) {
		$product_data = $this->cache->get('product.latest.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . (int)$limit);

		if (!$product_data) {
			$query = $this->db->query("SELECT p.product_id FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' ORDER BY p.date_added DESC LIMIT " . (int)$limit);

			foreach ($query->rows as $result) {
				$product_data[$result['product_id']] = $this->getProduct($result['product_id']);
			}

			$this->cache->set('product.latest.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . (int)$limit, $product_data);
		}

		return $product_data;
	}

	public function getPopularProducts($limit) {
		$product_data = $this->cache->get('product.popular.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . (int)$limit);
	
		if (!$product_data) {
			$query = $this->db->query("SELECT p.product_id FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' ORDER BY p.viewed DESC, p.date_added DESC LIMIT " . (int)$limit);
	
			foreach ($query->rows as $result) {
				$product_data[$result['product_id']] = $this->getProduct($result['product_id']);
			}
			
			$this->cache->set('product.popular.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . (int)$limit, $product_data);
		}
		
		return $product_data;
	}

	public function getBestSellerProducts($limit) {
		$product_data = $this->cache->get('product.bestseller.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . (int)$limit);

		if (!$product_data) {
			$product_data = array();

			$query = $this->db->query("SELECT op.product_id, SUM(op.quantity) AS total FROM " . DB_PREFIX . "order_product op LEFT JOIN `" . DB_PREFIX . "order` o ON (op.order_id = o.order_id) LEFT JOIN `" . DB_PREFIX . "product` p ON (op.product_id = p.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE o.order_status_id > '0' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' GROUP BY op.product_id ORDER BY total DESC LIMIT " . (int)$limit);

			foreach ($query->rows as $result) {
				$product_data[$result['product_id']] = $this->getProduct($result['product_id']);
			}

			$this->cache->set('product.bestseller.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . (int)$limit, $product_data);
		}

		return $product_data;
	}

	public function getProductFilters($product_id) {
		$query = $this->db->query("SELECT ocpf.product_id, ocfd.name AS filter_name, ocfgd.name AS filter_group_name FROM `" . DB_PREFIX . "product_filter` ocpf LEFT JOIN `" . DB_PREFIX . "filter` ocf ON (ocpf.filter_id = ocf.filter_id) LEFT JOIN `" . DB_PREFIX . "filter_description` ocfd ON (ocpf.filter_id = ocfd.filter_id AND ocfd.language_id = " . (int)$this->config->get('config_language_id') . ") LEFT JOIN `" . DB_PREFIX . "filter_group_description` ocfgd ON (ocf.filter_group_id = ocfgd.filter_group_id AND ocfgd.language_id = " . (int)$this->config->get('config_language_id') . ") WHERE ocpf.product_id = " . (int)$product_id);
		return $query->rows;
	}

	public function getProductAttributes($product_id) {
		$product_attribute_group_data = array();

		$product_attribute_group_query = $this->db->query("SELECT ag.attribute_group_id, agd.name FROM " . DB_PREFIX . "product_attribute pa LEFT JOIN " . DB_PREFIX . "attribute a ON (pa.attribute_id = a.attribute_id) LEFT JOIN " . DB_PREFIX . "attribute_group ag ON (a.attribute_group_id = ag.attribute_group_id) LEFT JOIN " . DB_PREFIX . "attribute_group_description agd ON (ag.attribute_group_id = agd.attribute_group_id) WHERE pa.product_id = '" . (int)$product_id . "' AND agd.language_id = '" . (int)$this->config->get('config_language_id') . "' GROUP BY ag.attribute_group_id ORDER BY ag.sort_order, agd.name");

		foreach ($product_attribute_group_query->rows as $product_attribute_group) {
			$product_attribute_data = array();

			$product_attribute_query = $this->db->query("SELECT a.attribute_id, ad.name, pa.text FROM " . DB_PREFIX . "product_attribute pa LEFT JOIN " . DB_PREFIX . "attribute a ON (pa.attribute_id = a.attribute_id) LEFT JOIN " . DB_PREFIX . "attribute_description ad ON (a.attribute_id = ad.attribute_id) WHERE pa.product_id = '" . (int)$product_id . "' AND a.attribute_group_id = '" . (int)$product_attribute_group['attribute_group_id'] . "' AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "' AND pa.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY a.sort_order, ad.name");

			foreach ($product_attribute_query->rows as $product_attribute) {
				$product_attribute_data[] = array(
					'attribute_id' => $product_attribute['attribute_id'],
					'name'         => $product_attribute['name'],
					'text'         => $product_attribute['text']
				);
			}

			$product_attribute_group_data[] = array(
				'attribute_group_id' => $product_attribute_group['attribute_group_id'],
				'name'               => $product_attribute_group['name'],
				'attribute'          => $product_attribute_data
			);
		}

		return $product_attribute_group_data;
	}

	public function getProductOptions($product_id, $data = array()) {
		$product_option_data = array();

		$product_option_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_option po LEFT JOIN `" . DB_PREFIX . "option` o ON (po.option_id = o.option_id) LEFT JOIN " . DB_PREFIX . "option_description od ON (o.option_id = od.option_id) WHERE po.product_id = '" . (int)$product_id . "' AND od.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY o.sort_order, od.name ASC");

		foreach ($product_option_query->rows as $product_option) {
			$product_option_value_data = array();

			$product_option_value_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "option_value ov ON (pov.option_value_id = ov.option_value_id) LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) WHERE pov.product_id = '" . (int)$product_id . "' AND pov.product_option_id = '" . (int)$product_option['product_option_id'] . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY " . (!empty($data['option_sort_price']) ? "pov.price, " : NULL) . "ovd.name ASC, ov.sort_order");

			foreach ($product_option_value_query->rows as $product_option_value) {
				$product_option_value_data[] = array(
					'product_option_value_id' => $product_option_value['product_option_value_id'],
					'option_value_id'         => $product_option_value['option_value_id'],
					'name'                    => $product_option_value['name'],
					'image'                   => $product_option_value['image'],
					'quantity'                => $product_option_value['quantity'],
					'subtract'                => $product_option_value['subtract'],
					'price'                   => $product_option_value['price'],
					'price_prefix'            => $product_option_value['price_prefix'],
					'price_old'				  => $product_option_value['price_old'],
					'weight'                  => $product_option_value['weight'],
					'weight_prefix'           => $product_option_value['weight_prefix']
				);
			}

			$product_option_data[] = array(
				'product_option_id'    => $product_option['product_option_id'],
				'product_option_value' => $product_option_value_data,
				'option_id'            => $product_option['option_id'],
				'name'                 => $product_option['name'],
				'type'                 => $product_option['type'],
				'value'                => $product_option['value'],
				'required'             => $product_option['required']
			);
		}

		return $product_option_data;
	}

	public function getProductDiscounts($product_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_discount WHERE product_id = '" . (int)$product_id . "' AND customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND quantity > 1 AND ((date_start = '0000-00-00' OR date_start < NOW()) AND (date_end = '0000-00-00' OR date_end > NOW())) ORDER BY quantity ASC, priority ASC, price ASC");

		return $query->rows;
	}

	public function getProductImages($product_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_image WHERE product_id = '" . (int)$product_id . "' ORDER BY sort_order ASC");

		return $query->rows;
	}

	public function getProductRelated($product_id) {
		$product_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_related pr LEFT JOIN " . DB_PREFIX . "product p ON (pr.related_id = p.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE pr.product_id = '" . (int)$product_id . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'");

		foreach ($query->rows as $result) {
			$product_data[$result['related_id']] = $this->getProduct($result['related_id']);
		}

		return $product_data;
	}

	public function getProductLayoutId($product_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_layout WHERE product_id = '" . (int)$product_id . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "'");

		if ($query->num_rows) {
			return (int)$query->row['layout_id'];
		} else {
			return 0;
		}
	}

	public function getCategories($product_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "'");

		return $query->rows;
	}

	public function getCategoriesPath($product_id, $limit = 0) {
		$query = $this->db->query("SELECT opc.category_id, ocd.name, ocp.level FROM `" . DB_PREFIX . "product_to_category` opc LEFT JOIN `" . DB_PREFIX . "category_path` ocp ON (opc.category_id = ocp.category_id AND ocp.category_id = ocp.path_id) LEFT JOIN `" . DB_PREFIX . "category_description` ocd ON (opc.category_id = ocd.category_id AND ocd.language_id = " . $this->config->get('config_language_id') . ") WHERE opc.product_id = " . (int)$product_id . " ORDER BY ocp.level ASC" . ($limit ? (" LIMIT " . (int)$limit) : NULL));

		return implode("/", array_column($query->rows, 'name'));
	}

	public function getTotalProducts($data = array()) {
		$sql = "SELECT COUNT(DISTINCT p.product_id) AS total";

		if (!empty($data['filter_category_id'])) {
			if (!empty($data['filter_sub_category'])) {
				$sql .= " FROM " . DB_PREFIX . "category_path cp LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (cp.category_id = p2c.category_id)";
			} else {
				$sql .= " FROM " . DB_PREFIX . "product_to_category p2c";
			}

			if (!empty($data['filter_filter'])) {
				$sql .= " LEFT JOIN " . DB_PREFIX . "product_filter pf ON (p2c.product_id = pf.product_id) LEFT JOIN " . DB_PREFIX . "product p ON (pf.product_id = p.product_id)";
			} else {
				$sql .= " LEFT JOIN " . DB_PREFIX . "product p ON (p2c.product_id = p.product_id)";
			}
		} else {
			$sql .= " FROM " . DB_PREFIX . "product p";
		}


// ExtendedSearch
		if ((!empty($data['filter_name'])) && $this->config->get('module_extendedsearch_status') && $this->config->get('module_extendedsearch_attr')) $sql .= " LEFT JOIN " . DB_PREFIX . "product_attribute pa ON (p.product_id = pa.product_id) ";
// ExtendedSearch END
			

		// OCFilter start
		if (!empty($data['filter_ocfilter'])) {
    	$this->load->model('extension/module/ocfilter');

      $ocfilter_product_sql = $this->model_extension_module_ocfilter->getSearchSQL($data['filter_ocfilter']);
		} else {
      $ocfilter_product_sql = false;
    }

    if ($ocfilter_product_sql && $ocfilter_product_sql->join) {
    	$sql .= $ocfilter_product_sql->join;
    }
    // OCFilter end
      
		$sql .= " LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'";

		if (!empty($data['filter_category_id'])) {
			if (!empty($data['filter_sub_category'])) {
				$sql .= " AND cp.path_id = '" . (int)$data['filter_category_id'] . "'";
			} else {
				$sql .= " AND p2c.category_id = '" . (int)$data['filter_category_id'] . "'";
			}

			if (!empty($data['filter_filter'])) {
				$implode = array();

				$filters = explode(',', $data['filter_filter']);

				foreach ($filters as $filter_id) {
					$implode[] = (int)$filter_id;
				}

				$sql .= " AND pf.filter_id IN (" . implode(',', $implode) . ")";
			}
		}

		if (!empty($data['filter_name']) || !empty($data['filter_tag'])) {
			$sql .= " AND (";

			if (!empty($data['filter_name'])) {
				$implode = array();

				$words = explode(' ', trim(preg_replace('/\s+/', ' ', $data['filter_name'])));

				foreach ($words as $word) {
					
// ExtendedSearch
					$adw_es = 'module_extendedsearch_';
					$es = " (LCASE(pd.name) LIKE '%" . $this->db->escape(utf8_strtolower($word)) . "%'";
				if ($this->config->get($adw_es.'status') && $this->config->get($adw_es.'model')) $es .= " OR LCASE(p.model) LIKE '%" . $this->db->escape(utf8_strtolower($word)) . "%'";
				if ($this->config->get($adw_es.'status') && $this->config->get($adw_es.'sku'))$es .= " OR LCASE(p.sku) LIKE '%" . $this->db->escape(utf8_strtolower($word)) . "%'";
				if ($this->config->get($adw_es.'status') && $this->config->get($adw_es.'upc'))$es .= " OR LCASE(p.upc) LIKE '%" . $this->db->escape(utf8_strtolower($word)) . "%'";
				if ($this->config->get($adw_es.'status') && $this->config->get($adw_es.'ean'))$es .= " OR LCASE(p.ean) LIKE '%" . $this->db->escape(utf8_strtolower($word)) . "%'";
				if ($this->config->get($adw_es.'status') && $this->config->get($adw_es.'jan'))$es .= " OR LCASE(p.jan) LIKE '%" . $this->db->escape(utf8_strtolower($word)) . "%'";
				if ($this->config->get($adw_es.'status') && $this->config->get($adw_es.'isbn'))$es .= " OR LCASE(p.isbn) LIKE '%" . $this->db->escape(utf8_strtolower($word)) . "%'";
				if ($this->config->get($adw_es.'status') && $this->config->get($adw_es.'mpn'))$es .= " OR LCASE(p.mpn) LIKE '%" . $this->db->escape(utf8_strtolower($word)) . "%'";
				if ($this->config->get($adw_es.'status') && $this->config->get($adw_es.'location'))$es .= " OR LCASE(p.location) LIKE '%" . $this->db->escape(utf8_strtolower($word)) . "%'";
				if ($this->config->get($adw_es.'status') && $this->config->get($adw_es.'attr'))$es .= " OR LCASE(pa.text) LIKE '%" . $this->db->escape(utf8_strtolower($word)) . "%'";
					$es .= ") ";
					$implode[] = $es;
// ExtendedSearch END
			
				}

				if ($implode) {
					$sql .= " " . implode(" AND ", $implode) . "";
				}

				if (!empty($data['filter_description'])) {
					$sql .= " OR pd.description LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
				}
			}

			if (!empty($data['filter_name']) && !empty($data['filter_tag'])) {
				$sql .= " OR ";
			}

			if (!empty($data['filter_tag'])) {
				$implode = array();

				$words = explode(' ', trim(preg_replace('/\s+/', ' ', $data['filter_tag'])));

				foreach ($words as $word) {
					$implode[] = "pd.tag LIKE '%" . $this->db->escape($word) . "%'";
				}

				if ($implode) {
					$sql .= " " . implode(" AND ", $implode) . "";
				}
			}

			if (!empty($data['filter_name'])) {
				
// ExtendedSearch
			if (!$this->config->get('module_extendedsearch_status') && !$this->config->get('module_extendedsearch_model')) {
				$sql .= " OR LCASE(p.model) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
			}
// ExtendedSearch END
			
				
// ExtendedSearch
			if (!$this->config->get('module_extendedsearch_status') && !$this->config->get('module_extendedsearch_sku')) {
				$sql .= " OR LCASE(p.sku) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
			}
// ExtendedSearch END
			
				
// ExtendedSearch
			if (!$this->config->get('module_extendedsearch_status') && !$this->config->get('module_extendedsearch_upc')) {
				$sql .= " OR LCASE(p.upc) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
			}
// ExtendedSearch END
			
				
// ExtendedSearch
			if (!$this->config->get('module_extendedsearch_status') && !$this->config->get('module_extendedsearch_ean')) {
				$sql .= " OR LCASE(p.ean) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
			}
// ExtendedSearch END
			
				
// ExtendedSearch
			if (!$this->config->get('module_extendedsearch_status') && !$this->config->get('module_extendedsearch_jan')) {
				$sql .= " OR LCASE(p.jan) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
			}
// ExtendedSearch END
			
				
// ExtendedSearch
			if (!$this->config->get('module_extendedsearch_status') && !$this->config->get('module_extendedsearch_isbn')) {
				$sql .= " OR LCASE(p.isbn) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
			}
// ExtendedSearch END
			
				
// ExtendedSearch
			if (!$this->config->get('module_extendedsearch_status') && !$this->config->get('module_extendedsearch_mpn')) {
				$sql .= " OR LCASE(p.mpn) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
			}
// ExtendedSearch END
			
			}

			$sql .= ")";
		}


    // OCFilter start
    if (!empty($ocfilter_product_sql) && $ocfilter_product_sql->where) {
    	$sql .= $ocfilter_product_sql->where;
    }
    // OCFilter end
      
		if (!empty($data['filter_manufacturer_id'])) {
			$sql .= " AND p.manufacturer_id = '" . (int)$data['filter_manufacturer_id'] . "'";
		}

		$query = $this->db->query($sql);

		return $query->row['total'];
	}

	public function getProfile($product_id, $recurring_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "recurring r JOIN " . DB_PREFIX . "product_recurring pr ON (pr.recurring_id = r.recurring_id AND pr.product_id = '" . (int)$product_id . "') WHERE pr.recurring_id = '" . (int)$recurring_id . "' AND status = '1' AND pr.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "'");

		return $query->row;
	}

	public function getProfiles($product_id) {
		$query = $this->db->query("SELECT rd.* FROM " . DB_PREFIX . "product_recurring pr JOIN " . DB_PREFIX . "recurring_description rd ON (rd.language_id = " . (int)$this->config->get('config_language_id') . " AND rd.recurring_id = pr.recurring_id) JOIN " . DB_PREFIX . "recurring r ON r.recurring_id = rd.recurring_id WHERE pr.product_id = " . (int)$product_id . " AND status = '1' AND pr.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' ORDER BY sort_order ASC");

		return $query->rows;
	}

	public function getTotalProductSpecials() {
		$query = $this->db->query("SELECT COUNT(DISTINCT ps.product_id) AS total FROM " . DB_PREFIX . "product_special ps LEFT JOIN " . DB_PREFIX . "product p ON (ps.product_id = p.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW()))");

		if (isset($query->row['total'])) {
			return $query->row['total'];
		} else {
			return 0;
		}
	}

	public function getOptionValueIdImages($option_value_id) {
		$query = $this->db->query("SELECT option_value_id, image, image_serialized FROM " . DB_PREFIX . "option_value WHERE option_value_id = " . (int) $option_value_id);

		return $query->row['image_serialized'] ? json_decode($query->row['image']) : $query->row['image'];
	}
}
