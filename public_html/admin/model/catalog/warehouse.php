<?php
class ModelCatalogWarehouse extends Model {
	public function addWarehouse($data) {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "warehouse` SET sort_order = '" . (int)$data['sort_order'] . "'");

		$warehouse_id = $this->db->getLastId();

		foreach ($data['warehouse_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "warehouse_description SET warehouse_id = '" . (int)$warehouse_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "', address = '" . $this->db->escape($value['address']) . "'");
		}

		return $warehouse_id;
	}

	public function editWarehouse($warehouse_id, $data) {
		$this->db->query("UPDATE `" . DB_PREFIX . "warehouse` SET sort_order = '" . (int)$data['sort_order'] . "' WHERE warehouse_id = '" . (int)$warehouse_id . "'");

		$this->db->query("DELETE FROM " . DB_PREFIX . "warehouse_description WHERE warehouse_id = '" . (int)$warehouse_id . "'");

		foreach ($data['warehouse_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "warehouse_description SET warehouse_id = '" . (int)$warehouse_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "', address = '" . $this->db->escape($value['address']) . "'");
		}
	}

	public function deleteWarehouse($warehouse_id) {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "warehouse` WHERE warehouse_id = '" . (int)$warehouse_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "warehouse_description WHERE warehouse_id = '" . (int)$warehouse_id . "'");
	}

	public function getWarehouse($warehouse_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "warehouse` w LEFT JOIN " . DB_PREFIX . "warehouse_description wd ON (w.warehouse_id = wd.warehouse_id) WHERE w.warehouse_id = '" . (int)$warehouse_id . "' AND wd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

		return $query->row;
	}

	public function getWarehouses($data = array()) {
		$sql = "SELECT * FROM `" . DB_PREFIX . "warehouse` w LEFT JOIN " . DB_PREFIX . "warehouse_description wd ON (w.warehouse_id = wd.warehouse_id) WHERE wd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

		if (!empty($data['filter_name'])) {
			$sql .= " AND wd.name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
		}

		$sort_data = array(
			'wd.name',
			'w.sort_order'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY wd.name";
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

	public function getWarehouseDescriptions($warehouse_id) {
		$warehouse_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "warehouse_description WHERE warehouse_id = '" . (int)$warehouse_id . "'");

		foreach ($query->rows as $result) {
			$warehouse_data[$result['language_id']] = array(
				'name' => $result['name'],
				'address' => $result['address']
			);
		}

		return $warehouse_data;
	}

	public function getTotalWarehouses() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "warehouse`");

		return $query->row['total'];
	}

	public function getWarehouseProducts($warehouse_id) {

		if (!$this->getWarehouses()) {
			return NULL;
		}

		$query = $this->db->query("SELECT wp.warehouse_id, wp.product_id, wp.option_value_id, pd.name AS product_name, ovd.name AS option_value_name, wp.quantity FROM `" . DB_PREFIX ."warehouse_product` wp LEFT JOIN `" . DB_PREFIX . "product_description` pd ON (wp.product_id = pd.product_id) AND (pd.language_id = " . (int)$this->config->get('config_language_id') . ") LEFT JOIN `" . DB_PREFIX . "option_value_description` ovd ON (wp.option_value_id = ovd.option_value_id) AND (ovd.language_id = " . (int)$this->config->get('config_language_id') . ") WHERE wp.warehouse_id = " . (int)$warehouse_id . " ORDER BY product_name");

		if (!empty($query->rows)) {
			return $query->rows;
		} else {
			return NULL;
		}
	} 
}