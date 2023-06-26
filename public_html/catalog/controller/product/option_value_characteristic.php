<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerProductOptionValueCharacteristic extends Controller {
	public function index() {
		$this->load->model('catalog/option_value_characteristic');

		$option_value_id = isset($this->request->post['option_value_id']) ? $this->request->post['option_value_id'] : 0;

		$option_value_characteristic = $this->model_catalog_option_value_characteristic->getOptionValueCharacteristic($option_value_id);
		$json = array();
		if ($option_value_characteristic) {
			$json = $option_value_characteristic;
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function getOptionValueCharacteristics() {
		$this->load->model('catalog/option_value_characteristic');

		$option_value_ids = isset($this->request->post['option_value_ids']) ? $this->request->post['option_value_ids'] : 0;

		$json = array();

		if (!empty($option_value_ids)) {
			$characteristics = $this->model_catalog_option_value_characteristic->getOptionValueCharacteristics($option_value_ids);
			if ($characteristics) {
				$json = $characteristics;
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function getOptionCharacteristics() {
		$this->load->model('catalog/option_value_characteristic');

		$option_value_ids = isset($this->request->post['option_value_ids']) ? $this->request->post['option_value_ids'] : 0;

		$json = array();

		if (!empty($option_value_ids)) {
			$characteristics = $this->model_catalog_option_value_characteristic->getOptionCharacteristics($option_value_ids);
			if ($characteristics) {
				$json = $characteristics;
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function getAllOptionCharacteristics() {
		$this->load->model('catalog/option_value_characteristic');

		$option_value_ids = isset($this->request->post['option_value_ids']) ? $this->request->post['option_value_ids'] : 0;

		$json = array();

		if (!empty($option_value_ids)) {
			$characteristics = $this->model_catalog_option_value_characteristic->getAllOptionCharacteristics($option_value_ids);
			if ($characteristics) {
				$json = $characteristics;
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function getOptionValueByCharacteristic() {
		$this->load->model('catalog/option_value_characteristic');

		$option_value_id = isset($this->request->post['option_value_ids']) ? $this->request->post['option_value_ids'] : 0;

		$json = array();

		if (!empty($option_value_ids)) {
			$characteristics = $this->model_catalog_option_value_characteristic->getOptionCharacteristics($option_value_ids);
			if ($characteristics) {
				$json = $characteristics;
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
