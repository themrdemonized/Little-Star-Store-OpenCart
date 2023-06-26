<?php
class ModelCatalogStocks extends Model {

	public function getStock($stocks_id = 0) {
		if (!$stocks_id) {
			return NULL;
		}

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "stocks s LEFT JOIN " . DB_PREFIX . "stocks_description sd ON (s.stocks_id = sd.stocks_id) WHERE s.stocks_id = '" . (int)$stocks_id . "' AND sd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

		return $query->row;
	}

	public function getStocks($data = array()) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "stocks s LEFT JOIN " . DB_PREFIX . "stocks_description sd ON (s.stocks_id = sd.stocks_id) WHERE sd.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY " . (isset($data['sort']) ? $this->db->escape($data['sort']) : "sd.name") . " " . (isset($data['order']) ? $this->db->escape($data['order']) : "ASC") . (isset($data['limit']) ? " LIMIT " . implode(",", array((int)$data['start'], (int)$data['limit'])) : NULL));

		return $query->rows;
	}

	public function getStockProducts($data = array()) {
		$sql = "SELECT p.product_id, (SELECT AVG(rating) AS total FROM " . DB_PREFIX . "review r1 WHERE r1.product_id = p.product_id AND r1.status = '1' GROUP BY r1.product_id) AS rating, (SELECT price FROM " . DB_PREFIX . "product_discount pd2 WHERE pd2.product_id = p.product_id AND pd2.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND pd2.quantity = '1' AND ((pd2.date_start = '0000-00-00' OR pd2.date_start < NOW()) AND (pd2.date_end = '0000-00-00' OR pd2.date_end > NOW())) ORDER BY pd2.priority ASC, pd2.price ASC LIMIT 1) AS discount, (SELECT price FROM " . DB_PREFIX . "product_special ps WHERE ps.product_id = p.product_id AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())) ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) AS special";

		if (!empty($data['filter_stocks_id'])) {
			$sql .= " FROM " . DB_PREFIX . "stocks_product p2c";
			$sql .= " LEFT JOIN " . DB_PREFIX . "product p ON (p2c.product_id = p.product_id)";
		} else {
			$sql .= " FROM " . DB_PREFIX . "product p";
		}

		$sql .= " LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'";

		if (!empty($data['filter_stocks_id'])) {
			$sql .= " AND p2c.stocks_id = '" . (int)$data['filter_stocks_id'] . "'";
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
				$data['limit'] = 8;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$product_data = array();

		$query = $this->db->query($sql);

		$this->load->model('catalog/product');

		foreach ($query->rows as $result) {
			$product_data[$result['product_id']] = $this->model_catalog_product->getProduct($result['product_id']);
		}

		return $product_data;
	}

	public function getTotalStocks() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "stocks");

		return $query->row['total'];
	}

	public function getTotalStockProducts($stocks_id = 0) {
		if (!$stocks_id) {
			return NULL;
		}

		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "stocks_product sp LEFT JOIN " . DB_PREFIX . "product p ON (sp.product_id = p.product_id) WHERE sp.stocks_id = '" . (int)$stocks_id . "' AND p.status='1'");

		return $query->row['total'];
	}
}