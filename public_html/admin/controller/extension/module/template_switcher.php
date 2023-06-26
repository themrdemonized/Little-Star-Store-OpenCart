<?php
/*------------------------------------------------------------------------
# Template Switcher
# ------------------------------------------------------------------------
# The Krotek
# Copyright (C) 2011-2019 The Krotek. All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Website: https://thekrotek.com
# Support: support@thekrotek.com
-------------------------------------------------------------------------*/

class ControllerExtensionModuleTemplateSwitcher extends Controller
{
	private $error = array();
	protected $template_engines = array();

	public function __construct($registry)
	{
		parent::__construct($registry);

		if (!$this->config->get('module_template_switcher_status')) {
			return;
		}
		
		$template_engines = array();
		
		$files = glob(DIR_SYSTEM.'library/template/*.php');
		
		if ($files) {
			foreach ($files as $file) {
				if (is_file($file)) {
					$template_engine = basename($file, '.php');
					$template_engines[] = $template_engine;
				}
			}
		}
		
		$this->template_engines = $template_engines;
	}

	public function index()
	{
		$this->load->language('extension/module/template_switcher');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('module_template_switcher', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token='.$this->session->data['user_token'].'&type=module', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token='.$this->session->data['user_token'], true));

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token='.$this->session->data['user_token'].'&type=module', true));

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/template_switcher', 'user_token='.$this->session->data['user_token'], true));

		$data['action'] = $this->url->link('extension/module/template_switcher', 'user_token='.$this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token='.$this->session->data['user_token'].'&type=module', true);

		$data['text_explain'] = $this->language->get('text_explain');
		
		if (isset($this->request->post['module_template_switcher_status'])) {
			$data['module_template_switcher_status'] = $this->request->post['module_template_switcher_status'];
		} else {
			$data['module_template_switcher_status'] = $this->config->get('module_template_switcher_status');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/template_switcher', $data));
	}

	protected function validate()
	{
		if (!$this->user->hasPermission('modify', 'extension/module/template_switcher')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	public function install()
	{
		$this->load->model('setting/event');
		
		$this->model_setting_event->addEvent('module_template_switcher', 'catalog/view/*/before', 'extension/module/template_switcher/override', 1, 499);		
		$this->model_setting_event->addEvent('module_template_switcher', 'catalog/view/*/before', 'extension/module/template_switcher/render', 1, 999);		
		$this->model_setting_event->addEvent('module_template_switcher', 'catalog/controller/*/before', 'extension/module/template_switcher/before', 1, 0);		
		$this->model_setting_event->addEvent('module_template_switcher', 'admin/view/*/before', 'extension/module/template_switcher/override', 1, 0);
		$this->model_setting_event->addEvent('module_template_switcher', 'admin/view/design/layout_form/before', 'extension/module/template_switcher/eventViewDesignLayoutFormBefore', 1, 0);
	}

	public function uninstall()
	{
		$this->load->model('setting/event');
		$this->model_setting_event->deleteEventByCode('module_template_switcher');
	}
 
	public function override(&$route, &$data, &$template)
	{
		if (!$this->config->get('module_template_switcher_status')) {
			return null;
		}

		foreach ($this->template_engines as $template_engine) {
			$ext = ($template_engine == 'template' ? '.tpl' : '.'.$template_engine);
			
			if (is_file(DIR_TEMPLATE.$route.$ext)) {
				$this->config->set('template_engine', $template_engine);
				$this->config->set('template_directory', '');
				
				return null;
			} 
		}

		trigger_error("Cannot find template file for route ".$route);
		
		exit;
	}

	public function render(&$route, &$data, &$template)
	{
		if (!$this->config->get('module_template_switcher_status')) {
			return null;
		}
		
		if ($template) {
			$template_engine = $this->config->get('template_engine');

			if ($template_engine == 'twig') {
				include_once(DIR_SYSTEM.'library/template/Twig/Autoloader.php');
				
				Twig_Autoloader::register();

				$loader = new \Twig_Loader_Filesystem(DIR_TEMPLATE);		
				
				$config = array('autoescape' => false);
				
				if ($this->config->get('template_cache')) {
					$config['cache'] = DIR_CACHE;
				}

				$twig = new \Twig_Environment($loader, $config);
					
				return $twig->createTemplate($template)->render($data);
			}

			$template = new Template($this->registry->get('config')->get('template_engine'));
			
			foreach ($data as $key => $value) {
				$template->set($key, $value);
			}
			
			return $template->render($this->registry->get('config')->get('template_directory').$route);		
		}
	
	}

	public function eventViewDesignLayoutFormBefore(&$route, &$data, &$template)
	{
		foreach ($data['extensions'] as $key => $extension) {
			if ($extension['code'] == 'template_switcher') {
				unset($data['extensions'][$key]);
			}
		}
		
		return null;
	}
}
?>