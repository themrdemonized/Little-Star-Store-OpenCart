<?php
class ControllerExtensionModuleProductsByCategories extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/module/products_by_categories');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		$this->save(true);

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/products_by_categories', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/module/products_by_categories', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		if (isset($this->request->post['module_products_by_categories_status'])) {
			$data['module_products_by_categories_status'] = $this->request->post['module_products_by_categories_status'];
		} else {
			$data['module_products_by_categories_status'] = $this->config->get('module_products_by_categories_status');
		}

		if (isset($this->request->post['module_products_by_categories_limit'])) {
			$data['module_products_by_categories_limit'] = $this->request->post['module_products_by_categories_limit'];
		} else {
			$data['module_products_by_categories_limit'] = $this->config->get('module_products_by_categories_limit') ?: 8;
		}

		$data['module_products_by_categories_categories'] = array();
		if (isset($this->request->post['module_products_by_categories_categories'])) {
			$data['module_products_by_categories_categories'] = $this->request->post['module_products_by_categories_categories'];
		} else {
			$data['module_products_by_categories_categories'] = $this->config->get('module_products_by_categories_categories');
		}

		$data['sort_methods'] = array(
            array('text' => $this->language->get('text_sort_methods_list'), 'value' => 'list'),
            array('text' => $this->language->get('text_sort_methods_name_asc'), 'value' => 'name_asc'),
            array('text' => $this->language->get('text_sort_methods_name_desc'), 'value' => 'name_desc'),
            array('text' => $this->language->get('text_sort_methods_sort_order'), 'value' => 'sort_order')
        );

        if (isset($this->request->post['module_products_by_categories_sort_method'])) {
            $data['module_products_by_categories_sort_method'] = $this->request->post['module_products_by_categories_sort_method'];
        } else {
            $data['module_products_by_categories_sort_method'] = $this->config->get('module_products_by_categories_sort_method');
        }
		
		$this->load->model('catalog/category');
		if (!empty($data['module_products_by_categories_categories'])) {
			$filter = array_flip($data['module_products_by_categories_categories']);
			$data['categories'] = $this->model_catalog_category->getCategories();
			$data['module_products_by_categories_categories'] = array_filter($data['categories'], function($a) use ($filter) {
				return isset($filter[$a['category_id']]);
			});
			usort($data['module_products_by_categories_categories'], function($a, $b) use ($filter) {
				if ($filter[$a['category_id']] == $filter[$b['category_id']]) {
				    return 0;
				}
				return ($filter[$a['category_id']] > $filter[$b['category_id']]) ? 1 : -1;
			});
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$data['user_token'] = $this->session->data['user_token'];

		$this->response->setOutput($this->load->view('extension/module/products_by_categories', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/products_by_categories')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	public function saveSettings() {
        $json = $this->save();
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

	private function save($redirect = false) {
		$json = array();
		$this->load->language('extension/module/products_by_categories');
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('module_products_by_categories', $this->request->post);

			$json['success'] = $this->session->data['success'] = $this->language->get('text_success');

			if ($redirect) {
				$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
			}
		} else {
            $json['error_code'] = htmlentities(implode("\n", $this->error));
        }
        return $json;
	}
}
