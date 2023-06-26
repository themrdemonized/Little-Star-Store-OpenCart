<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerExtensionExtensionFraud extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/extension/fraud');

		$this->load->model('setting/extension');

		$this->getList();
	}

	public function install() {
		$this->load->language('extension/extension/fraud');

		$this->load->model('setting/extension');

		if ($this->validate()) {
			$this->model_setting_extension->install('fraud', $this->request->get['extension']);

			$this->load->model('user/user_group');

			$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/fraud/' . $this->request->get['extension']);
			$this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/fraud/' . $this->request->get['extension']);

			// Call install method if it exsits

			
			if (!empty($this->request->get['extension'])) {
				$type = strtolower(str_replace('ControllerExtensionExtension', '', __CLASS__));

				if (__FUNCTION__ == 'install') {
					$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', $type.'/'.$this->request->get['extension']);
					$this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', $type.'/'.$this->request->get['extension']);
				}

				$this->load->controller($type.'/'.$this->request->get['extension'].'/'.__FUNCTION__);
			}
			
			
			$this->load->controller('extension/fraud/' . $this->request->get['extension'] . '/install');

			$this->session->data['success'] = $this->language->get('text_success');
		}

		$this->getList();
	}

	public function uninstall() {
		$this->load->language('extension/extension/fraud');

		$this->load->model('setting/extension');

		if ($this->validate()) {
			$this->model_setting_extension->uninstall('fraud', $this->request->get['extension']);

			// Call uninstall method if it exsits

			
			if (!empty($this->request->get['extension'])) {
				$type = strtolower(str_replace('ControllerExtensionExtension', '', __CLASS__));

				if (__FUNCTION__ == 'install') {
					$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', $type.'/'.$this->request->get['extension']);
					$this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', $type.'/'.$this->request->get['extension']);
				}

				$this->load->controller($type.'/'.$this->request->get['extension'].'/'.__FUNCTION__);
			}
			
			
			$this->load->controller('extension/fraud/' . $this->request->get['extension'] . '/uninstall');

			$this->session->data['success'] = $this->language->get('text_success');
		}
		
		$this->getList();
	}

	protected function getList() {
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

		$extensions = $this->model_setting_extension->getInstalled('fraud');

		foreach ($extensions as $key => $value) {
			if (!is_file(DIR_APPLICATION . 'controller/extension/fraud/' . $value . '.php') && !is_file(DIR_APPLICATION . 'controller/fraud/' . $value . '.php')) {
				$this->model_setting_extension->uninstall('fraud', $value);

				unset($extensions[$key]);
			}
		}

		$data['extensions'] = array();
		
		// Compatibility code for old extension folders
		$files = glob(DIR_APPLICATION . 'controller/extension/fraud/*.php');


			
			$type = strtolower(str_replace('ControllerExtensionExtension', '', __CLASS__));
			
			$files = glob(DIR_APPLICATION . 'controller/{extension/'.$type.','.$type.'}/*.php', GLOB_BRACE);
			
			
		if ($files) {
			foreach ($files as $file) {
				$extension = basename($file, '.php');

				$this->load->language('extension/fraud/' . $extension, 'extension');

				$data['extensions'][] = array(
					'name'      => $this->language->get('extension')->get('heading_title'),
					'status'    => $this->config->get('fraud_' . $extension . '_status') ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
					'install'   => $this->url->link('extension/extension/fraud/install', 'user_token=' . $this->session->data['user_token'] . '&extension=' . $extension, true),
					'uninstall' => $this->url->link('extension/extension/fraud/uninstall', 'user_token=' . $this->session->data['user_token'] . '&extension=' . $extension, true),
					'installed' => in_array($extension, $extensions),
					'edit'      => $this->url->link('extension/fraud/' . $extension, 'user_token=' . $this->session->data['user_token'], true)
				);
			}
		}
		
		$sort_order = array();
		foreach ($data['extensions'] as $key => $value) {
			if($value['installed']){
				$add = '0';
			}else{
				$add = '1';
			}
				$sort_order[$key] = $add.$value['name'];
		}
		array_multisort($sort_order, SORT_ASC, $data['extensions']);
		

			
			if (!empty($data['extensions'])) {
				$data['extensions'] = array_unique($data['extensions'], SORT_REGULAR);
			
				usort($data['extensions'], function($a, $b){ return strcmp($a["name"], $b["name"]); });
			}
			
			
		$this->response->setOutput($this->load->view('extension/extension/fraud', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/extension/fraud')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}