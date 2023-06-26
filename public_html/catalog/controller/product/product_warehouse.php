<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerProductProductWarehouse extends Controller {
	public function index() {
		if ($this->config->get('config_stock_display')) {
			$this->load->model('catalog/warehouse');

			$this->load->model('catalog/product');

			$product_id = isset($this->request->post['product_id']) ? $this->request->post['product_id'] : 0;
			$option_value_id = isset($this->request->post['option_value_id']) ? $this->request->post['option_value_id'] : 0;

			$warehouse_info = $this->model_catalog_warehouse->getWarehouseByProduct($product_id, $option_value_id);

			$json = array();
			
			if (isset($warehouse_info)) {
				$json = array(
					'count' => 0,
					'warehouses' => array()
				);
				foreach ($warehouse_info as $key => $warehouse) {
					$working_hours = array();
					foreach (json_decode($warehouse['working_hours'], true) as $wh) {
						$working_hours[] = array(
							'starting_time' => $wh['starting_time'],
							'ending_time' => $wh['ending_time'],
							'starting_day' => $wh['days'][0],
							'ending_day' => end($wh['days'])
						);
					}
					$json['warehouses'][$key] = $warehouse;
					$json['warehouses'][$key]['working_hours'] = $working_hours;
					if ($json['warehouses'][$key]['quantity'] > 0) {
						$json['count']++;
					} else {
						$json['warehouses'][$key]['quantity'] = 0; //fix for negative quantity
					}
				}
			} else {
				if ($option_value_id) {
					$warehouse_info = $this->model_catalog_warehouse->getQuantityByOptionValueId($product_id, $option_value_id);
				} else {
					$warehouse_info = $this->model_catalog_warehouse->getQuantityByProductId($product_id);
				}
				$json = array(
					'quantity' => $warehouse_info
				);
			}
		} else {
			$json = NULL;
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
