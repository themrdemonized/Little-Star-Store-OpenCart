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

	public function index(&$route, &$args, &$template)
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
					
				return $twig->createTemplate($template)->render($args);
			}

			$template = new Template($this->registry->get('config')->get('template_engine'));
			
			foreach ($data as $key => $value) {
				$template->set($key, $value);
			}
			
			return $template->render($this->registry->get('config')->get('template_directory').$route);		
		}

		return null;
	}

	public function override(&$route, &$args, &$template)
	{
		if (!$this->config->get('module_template_switcher_status')) {
			return null;
		}

		if (!$this->config->get('theme_'.$this->config->get('config_theme').'_status')) {
			exit('Error: A theme has not been assigned to this store!');
		}

		if ($this->config->get('config_theme') == 'default') {
			$theme = $this->config->get('theme_default_directory');
		} else {
			$theme = $this->config->get('config_theme');
		}
			
		$this->load->model('design/theme');
		
		$theme_info = $this->model_design_theme->getTheme($route, $theme);
		
		if ($theme_info) {
			$this->config->set('template_engine', 'twig');
			
			$template = html_entity_decode($theme_info['code'], ENT_QUOTES, 'UTF-8');
			
			return null;
		}

		if ($this->config->get('config_theme') == 'default') {
			$theme = $this->config->get('theme_default_directory');
		} else {
			$theme = $this->config->get('config_theme');
		}
		
		foreach ($this->template_engines as $template_engine) {
			$ext = ($template_engine == 'template') ? '.tpl' : '.'.$template_engine;
			
			if (is_file(DIR_TEMPLATE.$theme.'/template/'.$route.$ext)) {
				$this->config->set('template_engine', $template_engine);
				$this->config->set('template_directory', $theme.'/template/');
				
				return null;
			} 
		}
		
		foreach ($this->template_engines as $template_engine) {
			$ext = ($template_engine == 'template') ? '.tpl' : '.'.$template_engine;
			
			if (is_file(DIR_TEMPLATE.'default/template/'.$route.$ext)) {
				$this->config->set('template_engine', 'twig');
				$this->config->set('template_directory', 'default/template/');
				
				return null;
			}
		}
		
		trigger_error("Cannot find template file for route ".$route);
		
		exit;
	}

	public function before(&$route, &$data)
	{
		if (!$this->config->get('module_template_switcher_status')) {
			return null;
		}

		$this->event->unregister('view/*/before', 'event/theme');
		$this->event->unregister('view/*/before', 'event/theme/override');
		
		return null;
	}
}

?>