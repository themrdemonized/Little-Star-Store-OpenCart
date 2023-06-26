<?php
#######################################################
#    ExtendedSearch 1.04 for Opencart 3x by AlexDW    #
#######################################################
class ControllerExtensionModuleExtendedsearch extends Controller {

	private $error = array(); 

	public function index() {   
		$this->load->language('extension/module/extendedsearch');

		$this->document->setTitle(strip_tags($this->language->get('heading_title')));
		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('module_extendedsearch', $this->request->post);		

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}

		$adw_es = 'module_extendedsearch_';

		$config_data = array(
				'status',
				'tag',
				'model',
				'sku',
				'upc',
				'ean',
				'jan',
				'isbn',
				'mpn',
				'location',
				'attr'
		);

		foreach ($config_data as $conf) {
			if (isset($this->request->post[$adw_es.$conf])) {
				$data[$adw_es.$conf] = $this->request->post[$adw_es.$conf];
			} else {
				$data[$adw_es.$conf] = $this->config->get($adw_es.$conf);
			}
		}

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
			'text' => $this->language->get('text_module'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/extendedsearch', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/module/extendedsearch', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/extendedsearch', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/extendedsearch')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		return !$this->error;
	}
}
?>