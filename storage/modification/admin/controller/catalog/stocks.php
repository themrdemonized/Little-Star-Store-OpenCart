<?php
class ControllerCatalogStocks extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('catalog/stocks');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('catalog/stocks');

		$this->getList();
	}

	public function settings() {
		$this->load->language('catalog/stocks');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('catalog/stocks');
		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('stocks', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('catalog/stocks', 'user_token=' . $this->session->data['user_token'], true));
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('catalog/stocks', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_settings'),
			'href' => $this->url->link('catalog/stocks/settings', 'user_token=' . $this->session->data['user_token'], true)
		);

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

		$data['action'] = $this->url->link('catalog/stocks/settings', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('catalog/stocks', 'user_token=' . $this->session->data['user_token'], true);

		$data['user_token'] = $this->session->data['user_token'];

		if (isset($this->request->post['stocks_heading_title'])) {
			$data['stocks_heading_title'] = $this->request->post['stocks_heading_title'];
		} else {
			$data['stocks_heading_title'] = $this->config->get('stocks_heading_title');
		}

		if (isset($this->request->post['stocks_meta_title'])) {
			$data['stocks_meta_title'] = $this->request->post['stocks_meta_title'];
		} else {
			$data['stocks_meta_title'] = $this->config->get('stocks_meta_title');
		}

		if (isset($this->request->post['stocks_meta_description'])) {
			$data['stocks_meta_description'] = $this->request->post['stocks_meta_description'];
		} else {
			$data['stocks_meta_description'] = $this->config->get('stocks_meta_description');
		}

		if (isset($this->request->post['stocks_meta_keyword'])) {
			$data['stocks_meta_keyword'] = $this->request->post['stocks_meta_keyword'];
		} else {
			$data['stocks_meta_keyword'] = $this->config->get('stocks_meta_keyword');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('catalog/stocks_settings', $data));

	}

	public function add() {
		$this->load->language('catalog/stocks');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('catalog/stocks');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_catalog_stocks->addStock($this->request->post);

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

			$this->response->redirect($this->url->link('catalog/stocks', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getForm();
	}

	public function edit() {
		$this->load->language('catalog/stocks');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('catalog/stocks');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_catalog_stocks->editStock($this->request->get['stocks_id'], $this->request->post);

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

			$this->response->redirect($this->url->link('catalog/stocks', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getForm();
	}

	public function delete() {
		$this->load->language('catalog/stocks');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('catalog/stocks');

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $stocks_id) {
				$this->model_catalog_stocks->deleteStock($stocks_id);
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

			$this->response->redirect($this->url->link('catalog/stocks', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getList();
	}

	protected function getList() {
		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 's.start_at';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'DESC';
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
			'href' => $this->url->link('catalog/stocks', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		$data['add'] = $this->url->link('catalog/stocks/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['delete'] = $this->url->link('catalog/stocks/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);

		$data['stockss'] = array();

		$filter_data = array(
			'sort'  => $sort,
			'order' => $order,
			'start' => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit' => $this->config->get('config_limit_admin')
		);

		$stocks_total = $this->model_catalog_stocks->getTotalStocks();

		$results = $this->model_catalog_stocks->getStocks($filter_data);

		foreach ($results as $result) {
			$data['stocks'][] = array(
				'stocks_id'  => $result['stocks_id'],
				'name'       => $result['name'],
				'start_at'	 => $result['start_at'],
				'end_at'	 => $result['end_at'],
				'discount'	 => $result['discount'],
				'sort_order' => $result['sort_order'],
				'edit'       => $this->url->link('catalog/stocks/edit', 'user_token=' . $this->session->data['user_token'] . '&stocks_id=' . $result['stocks_id'] . $url, true)
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

		$data['sort_name'] = $this->url->link('catalog/stocks', 'user_token=' . $this->session->data['user_token'] . '&sort=sd.name' . $url, true);
		$data['sort_start_at'] = $this->url->link('catalog/stocks', 'user_token=' . $this->session->data['user_token'] . '&sort=s.start_at' . $url, true);
		$data['sort_end_at'] = $this->url->link('catalog/stocks', 'user_token=' . $this->session->data['user_token'] . '&sort=s.end_at' . $url, true);
		$data['sort_discount'] = $this->url->link('catalog/stocks', 'user_token=' . $this->session->data['user_token'] . '&sort=s.discount' . $url, true);
		$data['sort_sort_order'] = $this->url->link('catalog/stocks', 'user_token=' . $this->session->data['user_token'] . '&sort=s.sort_order' . $url, true);

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new Pagination();
		$pagination->total = $stocks_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('catalog/stocks', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($stocks_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($stocks_total - $this->config->get('config_limit_admin'))) ? $stocks_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $stocks_total, ceil($stocks_total / $this->config->get('config_limit_admin')));

		$data['sort'] = $sort;
		$data['order'] = $order;
		$data['settings'] = $this->url->link('catalog/stocks/settings', 'user_token=' . $this->session->data['user_token'], true);

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('catalog/stocks_list', $data));
	}

	protected function getForm() {
		$data['text_form'] = !isset($this->request->get['stocks_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

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
			'href' => $this->url->link('catalog/stocks', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		if (!isset($this->request->get['stocks_id'])) {
			$data['action'] = $this->url->link('catalog/stocks/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		} else {
			$data['action'] = $this->url->link('catalog/stocks/edit', 'user_token=' . $this->session->data['user_token'] . '&stocks_id=' . $this->request->get['stocks_id'] . $url, true);
		}

		$data['cancel'] = $this->url->link('catalog/stocks', 'user_token=' . $this->session->data['user_token'] . $url, true);

		if (isset($this->request->get['stocks_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$stocks_info = $this->model_catalog_stocks->getStocks($this->request->get['stocks_id']);
		}

		$data['stocks_id'] = $this->request->get['stocks_id'];

		$data['user_token'] = $this->session->data['user_token'];

		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();

		if (isset($this->request->post['stocks_description'])) {
			$data['stocks_description'] = $this->request->post['stocks_description'];
		} elseif (isset($this->request->get['stocks_id'])) {
			$data['stocks_description'] = $this->model_catalog_stocks->getStockDescriptions($this->request->get['stocks_id']);
		} else {
			$data['stocks_description'] = array();
		}

		if (isset($this->request->post['sort_order'])) {
			$data['sort_order'] = $this->request->post['sort_order'];
		} elseif (!empty($stocks_info)) {
			$data['sort_order'] = $stocks_info['sort_order'];
		} else {
			$data['sort_order'] = '';
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('catalog/stocks_form', $data));
	}

	protected function validateForm() {
		if (!$this->user->hasPermission('modify', 'catalog/stocks')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		foreach ($this->request->post['stocks_description'] as $language_id => $value) {
			if ((utf8_strlen($value['name']) < 1) || (utf8_strlen($value['name']) > 255)) {
				$this->error['name'][$language_id] = $this->language->get('error_name');
			}
		}

		return !$this->error;
	}

	protected function validateDelete() {
		if (!$this->user->hasPermission('modify', 'catalog/stocks')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	public function getProducts() {
		$json = array();
		if ($this->request->post['stocks_id']) {
			
			$this->load->model('catalog/stocks');
			$products = $this->model_catalog_stocks->getStockProducts($this->request->post['stocks_id']);
			if ($products) {
				$json = $products;
			}
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'catalog/stocks')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
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
	// 			'limit'        => $this->config->get('config_autocomplete_limit')
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