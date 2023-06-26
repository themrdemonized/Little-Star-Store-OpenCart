<?php
class ControllerCatalogWarehouse extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('catalog/warehouse');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('catalog/warehouse');

		$this->getList();
	}

	public function add() {
		$this->load->language('catalog/warehouse');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('catalog/warehouse');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_catalog_warehouse->addWarehouse($this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('catalog/warehouse', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getForm();
	}

	public function edit() {
		$this->load->language('catalog/warehouse');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('catalog/warehouse');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_catalog_warehouse->editWarehouse($this->request->get['warehouse_id'], $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('catalog/warehouse', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getForm();
	}

	public function delete() {
		$this->load->language('catalog/warehouse');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('catalog/warehouse');

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $warehouse_id) {
				$this->model_catalog_warehouse->deleteWarehouse($warehouse_id);
			}

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('catalog/warehouse', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getList();
	}

	protected function getList() {
		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'wd.name';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'ASC';
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('catalog/warehouse', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		$data['add'] = $this->url->link('catalog/warehouse/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['delete'] = $this->url->link('catalog/warehouse/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);

		$data['warehouses'] = array();

		$filter_data = array(
			'sort'  => $sort,
			'order' => $order,
			'start' => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit' => $this->config->get('config_limit_admin')
		);

		$warehouse_total = $this->model_catalog_warehouse->getTotalWarehouses();

		$results = $this->model_catalog_warehouse->getWarehouses($filter_data);

		foreach ($results as $result) {
			$data['warehouses'][] = array(
				'warehouse_id'  => $result['warehouse_id'],
				'name'       => $result['name'],
				'sort_order' => $result['sort_order'],
				'edit'       => $this->url->link('catalog/warehouse/edit', 'user_token=' . $this->session->data['user_token'] . '&warehouse_id=' . $result['warehouse_id'] . $url, true)
			);
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->request->post['selected'])) {
			$data['selected'] = (array)$this->request->post['selected'];
		} else {
			$data['selected'] = array();
		}

		$url = '';

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['sort_name'] = $this->url->link('catalog/warehouse', 'user_token=' . $this->session->data['user_token'] . '&sort=wd.name' . $url, true);
		$data['sort_sort_order'] = $this->url->link('catalog/warehouse', 'user_token=' . $this->session->data['user_token'] . '&sort=w.sort_order' . $url, true);

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new Pagination();
		$pagination->total = $warehouse_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('catalog/warehouse', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($warehouse_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($warehouse_total - $this->config->get('config_limit_admin'))) ? $warehouse_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $warehouse_total, ceil($warehouse_total / $this->config->get('config_limit_admin')));

		$data['sort'] = $sort;
		$data['order'] = $order;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('catalog/warehouse_list', $data));
	}

	protected function getForm() {
		$data['text_form'] = !isset($this->request->get['warehouse_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['name'])) {
			$data['error_name'] = $this->error['name'];
		} else {
			$data['error_name'] = array();
		}


		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('catalog/warehouse', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		if (!isset($this->request->get['warehouse_id'])) {
			$data['action'] = $this->url->link('catalog/warehouse/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		} else {
			$data['action'] = $this->url->link('catalog/warehouse/edit', 'user_token=' . $this->session->data['user_token'] . '&warehouse_id=' . $this->request->get['warehouse_id'] . $url, true);
		}

		$data['cancel'] = $this->url->link('catalog/warehouse', 'user_token=' . $this->session->data['user_token'] . $url, true);

		if (isset($this->request->get['warehouse_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$warehouse_info = $this->model_catalog_warehouse->getWarehouse($this->request->get['warehouse_id']);
		}

		$data['warehouse_id'] = $this->request->get['warehouse_id'];

		$data['user_token'] = $this->session->data['user_token'];

		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();

		if (isset($this->request->post['warehouse_description'])) {
			$data['warehouse_description'] = $this->request->post['warehouse_description'];
		} elseif (isset($this->request->get['warehouse_id'])) {
			$data['warehouse_description'] = $this->model_catalog_warehouse->getWarehouseDescriptions($this->request->get['warehouse_id']);
		} else {
			$data['warehouse_description'] = array();
		}

		if (isset($this->request->post['sort_order'])) {
			$data['sort_order'] = $this->request->post['sort_order'];
		} elseif (!empty($warehouse_info)) {
			$data['sort_order'] = $warehouse_info['sort_order'];
		} else {
			$data['sort_order'] = '';
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('catalog/warehouse_form', $data));
	}

	protected function validateForm() {
		if (!$this->user->hasPermission('modify', 'catalog/warehouse')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		foreach ($this->request->post['warehouse_description'] as $language_id => $value) {
			if ((utf8_strlen($value['name']) < 1) || (utf8_strlen($value['name']) > 255)) {
				$this->error['name'][$language_id] = $this->language->get('error_name');
			}
		}

		return !$this->error;
	}

	protected function validateDelete() {
		if (!$this->user->hasPermission('modify', 'catalog/warehouse')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	public function getProducts() {
		$json = array();
		if ($this->request->post['warehouse_id']) {
			
			$this->load->model('catalog/warehouse');
			$products = $this->model_catalog_warehouse->getWarehouseProducts($this->request->post['warehouse_id']);
			if ($products) {
				$json = $products;
			}
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function autocomplete() {
	// 	$json = array();

	// 	if (isset($this->request->get['filter_name'])) {
	// 		$this->load->language('catalog/warehouse');

	// 		$this->load->model('catalog/warehouse');

	// 		$this->load->model('tool/image');

	// 		$filter_data = array(
	// 			'filter_name' => $this->request->get['filter_name'],
	// 			'start'       => 0,
	// 			'limit'       => 5
	// 		);

	// 		$options = $this->model_catalog_warehouse->getOptions($filter_data);

	// 		foreach ($options as $option) {
	// 			$option_value_data = array();

	// 			if ($option['type'] == 'select' || $option['type'] == 'radio' || $option['type'] == 'checkbox' || $option['type'] == 'image') {
	// 				$option_values = $this->model_catalog_warehouse->getOptionValues($option['option_id']);

	// 				foreach ($option_values as $option_value) {
	// 					if (is_file(DIR_IMAGE . $option_value['image'])) {
	// 						$image = $this->model_tool_image->resize($option_value['image'], 50, 50);
	// 					} else {
	// 						$image = $this->model_tool_image->resize('no_image.png', 50, 50);
	// 					}

	// 					$option_value_data[] = array(
	// 						'option_value_id' => $option_value['option_value_id'],
	// 						'name'            => strip_tags(html_entity_decode($option_value['name'], ENT_QUOTES, 'UTF-8')),
	// 						'image'           => $image
	// 					);
	// 				}

	// 				$sort_order = array();

	// 				foreach ($option_value_data as $key => $value) {
	// 					$sort_order[$key] = $value['name'];
	// 				}

	// 				array_multisort($sort_order, SORT_ASC, $option_value_data);
	// 			}

	// 			$type = '';

	// 			if ($option['type'] == 'select' || $option['type'] == 'radio' || $option['type'] == 'checkbox') {
	// 				$type = $this->language->get('text_choose');
	// 			}

	// 			if ($option['type'] == 'text' || $option['type'] == 'textarea') {
	// 				$type = $this->language->get('text_input');
	// 			}

	// 			if ($option['type'] == 'file') {
	// 				$type = $this->language->get('text_file');
	// 			}

	// 			if ($option['type'] == 'date' || $option['type'] == 'datetime' || $option['type'] == 'time') {
	// 				$type = $this->language->get('text_date');
	// 			}

	// 			$json[] = array(
	// 				'option_id'    => $option['option_id'],
	// 				'name'         => strip_tags(html_entity_decode($option['name'], ENT_QUOTES, 'UTF-8')),
	// 				'category'     => $type,
	// 				'type'         => $option['type'],
	// 				'option_value' => $option_value_data
	// 			);
	// 		}
	// 	}

	// 	$sort_order = array();

	// 	foreach ($json as $key => $value) {
	// 		$sort_order[$key] = $value['name'];
	// 	}

	// 	array_multisort($sort_order, SORT_ASC, $json);

	// 	$this->response->addHeader('Content-Type: application/json');
	// 	$this->response->setOutput(json_encode($json));
	}
}