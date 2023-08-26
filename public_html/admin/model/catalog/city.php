<?php
class ModelCatalogCity extends Model {
	public function addCity($data) {

		if (isset($data['default_city']) && (bool)$data['default_city']) {
			$this->db->query("UPDATE `" . DB_PREFIX . "city` SET default_city = '0'");
		} else {
			$data['default_city'] = false;
		}

		$this->db->query("INSERT INTO `" . DB_PREFIX . "city` SET name = '" . $this->db->escape($data['name']) . "', default_city = '" . (bool)$data['default_city'] . "'");

		$city_id = $this->db->getLastId();

		return $city_id;
	}

	public function editCity($city_id, $data) {

		if (isset($data['default_city']) && (bool)$data['default_city']) {
			$this->db->query("UPDATE `" . DB_PREFIX . "city` SET default_city = '0'");
		} else {
			$data['default_city'] = false;
		}

		$this->db->query("UPDATE `" . DB_PREFIX . "city` SET name = '" . $this->db->escape($data['name']) . "', default_city = '" . (bool)$data['default_city'] . "' WHERE city_id = '" . (int)$city_id . "'");
	}

	public function deleteCity($city_id) {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "city` WHERE city_id = '" . (int)$city_id . "'");
	}

	public function getCity($city_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "city` WHERE city_id = '" . (int)$city_id . "'");

		return $query->row;
	}

	public function getCities($data = array()) {
		$sql = "SELECT * FROM `" . DB_PREFIX . "city`";

		$sort_data = array(
			'name',
		);

		$sql .= " ORDER BY default_city DESC, ";

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= $data['sort'];
		} else {
			$sql .= "name";
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

	public function getTotalCities() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "city`");

		return $query->row['total'];
	} 
}