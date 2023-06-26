<?php
class ModelCatalogOptionValueCharacteristic extends Model {

	private function sanitizeValue($str) {
		return str_replace(['{', '}', '"'], '', $str);
	}

	public function getOptionValueCharacteristic($option_value_id) {
		$data = array();

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "option_value_characteristic` ovc LEFT JOIN " . DB_PREFIX . "option_characteristic_description ocd ON (ovc.characteristic_id = ocd.characteristic_id) WHERE ocd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND ovc.option_value_id = '" . (int)$option_value_id . "' ORDER BY ocd.name ASC, ovc.value ASC");

		foreach ($query->rows as $result) {
			$data[$result['name']][] = $result['value'];
		}

		return $data;
	}

	public function getOptionValueCharacteristics($option_value_ids = array()) {
		$data = array();

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "option_value_characteristic` " . (!empty($option_value_ids) ? ("WHERE option_value_id IN (" . $this->db->escape(implode(",", $option_value_ids)) . ")") : NULL));

		foreach ($query->rows as $result) {
			$data[$result['option_value_id']]["{$result['characteristic_id']}-{$this->sanitizeValue($result['value'])}"] = $this->sanitizeValue($result['value']);
		}

		return $data;
	}

	private function mbUppercase($str, $encoding = 'UTF-8') {
        if (is_null($encoding)) {
            $encoding = mb_internal_encoding();
        }

        return mb_strtoupper($str, $encoding);
    }

	public function getOptionCharacteristics($option_value_ids = array()) {
		$data = array();

		$sizes = array(
			'XXXXS' => 10,
			'4XS' => 10,
			'XXXS' => 20,
			'3XS' => 20,
			'XXS' => 30,
			'2XS' => 30,
			'XS' => 40,
			'S' => 50,
			'M' => 60,
			'М' => 60,
			'L' => 70,
			'XL' => 80,
			'XXL' => 90,
			'2XL' => 90,
			'XXXL' => 100,
			'3XL' => 100,
			'XXXXL' => 110,
			'4XL' => 110,
			'5XL' => 120,
			'6XL' => 130,
			'7XL' => 140,
			'8XL' => 150,
			'9XL' => 160,
			'10XL' => 170,
			'11XL' => 180,
			'12XL' => 190
		);

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "option_characteristic` oc LEFT JOIN " . DB_PREFIX . "option_value_characteristic ovc ON (oc.characteristic_id = ovc.characteristic_id) LEFT JOIN " . DB_PREFIX . "option_characteristic_description ocd ON (oc.characteristic_id = ocd.characteristic_id) LEFT JOIN " . DB_PREFIX . "product_option_value pov ON (ovc.option_value_id = pov.option_value_id) WHERE ocd.language_id = '" . (int)$this->config->get('config_language_id') . "'" . (!empty($option_value_ids) ? (" AND ovc.option_value_id IN (" . $this->db->escape(implode(",", $option_value_ids)) . ")") : NULL) . " GROUP BY oc.characteristic_id, ovc.value ORDER BY ocd.name ASC, pov.price ASC, ovc.value ASC");

		$characteristics = array();
		foreach ($query->rows as $result) {
			$data[$result['characteristic_id']]['characteristic_id'] = $result['characteristic_id'];
			$data[$result['characteristic_id']]['name'] = $result['name'];
			$data[$result['characteristic_id']]['type'] = $result['type'];
			$data[$result['characteristic_id']]['characteristic'][] = array(
				'option_value_characteristic_id' => $result['option_value_characteristic_id'],
				'option_value_id' => $result['option_value_id'],
				'value' => $result['serialized'] ? json_decode($result['value'], true) : $result['value'],
				'key' => "{$result['characteristic_id']}-{$this->sanitizeValue($result['value'])}",
			);
			$characteristics[$result['characteristic_id']][$this->mbUppercase($result['value'])] = $result['value'];
		}

		foreach ($characteristics as $key => $characteristic) {
			if (empty(array_diff_key($characteristic, $sizes))) {
				usort($data[$key]['characteristic'], function($a, $b) use ($sizes) {
					$asize = $sizes[$this->mbUppercase($a['value'])];
					$bsize = $sizes[$this->mbUppercase($b['value'])];
					if ($asize == $bsize) {
					    return 0;
					}
					return ($asize > $bsize) ? 1 : -1;
				});
			}
		}

		usort($data, function($a, $b) {
			return $a['type'] === 'colors' ? -1 : 1;
		});

		usort($data, function($a, $b) {
			return strcmp($a['type'], $b['type']) * 10 + strcmp($a['name'], $b['name']);
		});

		return $data;
	}

	public function getAllOptionCharacteristics($option_value_ids = array()) {
		$data = array();

		$data['grouped'] = $this->getOptionCharacteristics($option_value_ids);

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "option_characteristic` oc LEFT JOIN " . DB_PREFIX . "option_value_characteristic ovc ON (oc.characteristic_id = ovc.characteristic_id) LEFT JOIN " . DB_PREFIX . "option_characteristic_description ocd ON (oc.characteristic_id = ocd.characteristic_id) LEFT JOIN " . DB_PREFIX . "product_option_value pov ON (ovc.option_value_id = pov.option_value_id) WHERE ocd.language_id = '" . (int)$this->config->get('config_language_id') . "'" . (!empty($option_value_ids) ? (" AND ovc.option_value_id IN (" . $this->db->escape(implode(",", $option_value_ids)) . ")") : NULL) . " ORDER BY ocd.name ASC, pov.price ASC, ovc.value ASC");

		$data['raw'] = $query->rows;

		foreach ($data['raw'] as $key => $value) {
			$data['raw'][$key]['key'] = "{$value['characteristic_id']}-{$this->sanitizeValue($value['value'])}";
		}

		return $data;
	}
}