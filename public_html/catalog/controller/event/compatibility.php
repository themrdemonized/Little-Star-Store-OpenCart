<?php
/*------------------------------------------------------------------------
# OpenCart 3 Compatibility Fixes
# ------------------------------------------------------------------------
# The Krotek
# Copyright (C) 2011-2019 The Krotek. All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Website: https://thekrotek.com
# Support: support@thekrotek.com
-------------------------------------------------------------------------*/

class ControllerEventCompatibility extends Controller
{
	public function controller(&$route)
	{
		$route = preg_replace('/[^a-zA-Z0-9_\/]/', '', (string)$route);
		
		list($base, $folder, $extension) = array_pad(explode('/', $route), 3, NULL);
		
		if (($base == 'extension') && !is_file(DIR_APPLICATION.'controller/'.$route.'.php') && (!is_null($folder) && !is_null($extension) && is_file(DIR_APPLICATION.'controller/'.$folder.'/'.$extension.'.php'))) {
			$route = $folder.'/'.$extension;
		}
	}
		
	public function beforeModel(&$route)
	{
		$route = preg_replace('/[^a-zA-Z0-9_\/]/', '', (string)$route);
		
		list($base, $folder, $extension) = array_pad(explode('/', $route), 3, NULL);

		if (($base == 'extension') && !is_file(DIR_APPLICATION.'model/'.$route.'.php') && (!is_null($folder) && !is_null($extension) && is_file(DIR_APPLICATION.'model/'.$folder.'/'.$extension.'.php'))) {
			$route = $folder.'/'.$extension;
		}
	}
	
	public function afterModel(&$route)
	{
		$route = preg_replace('/[^a-zA-Z0-9_\/]/', '', (string)$route);
		
		list($base, $folder, $extension) = array_pad(explode('/', $route), -3, NULL);

		if (!is_file(DIR_APPLICATION.'model/extension/'.$route.'.php') && (!is_null($folder) && !is_null($extension) && is_file(DIR_APPLICATION.'model/'.$folder.'/'.$extension.'.php'))) {	
			$this->{'model_extension_'.$folder.'_'.$extension} = $this->{'model_'.$folder.'_'.$extension};
		}
	}
		
	public function language(&$route)
	{
		$route = preg_replace('/[^a-zA-Z0-9_\/]/', '', (string)$route);
				
		list($base, $folder, $extension) = array_pad(explode('/', $route), 3, NULL);
		
		$directories = array($this->config->get('config_admin_language'), $this->language->default, 'english');
		
		foreach ($directories as $directory) {
			if (!is_file(DIR_LANGUAGE.$directory.'/'.$route.'.php') && (!is_null($folder) && !is_null($extension) && is_file(DIR_LANGUAGE.$directory.'/'.$folder.'/'.$extension.'.php'))) {
				$route = $folder.'/'.$extension;
				return;
			}
 		}
 							
		if (($base == 'extension') && !is_file(DIR_LANGUAGE.$this->config->get('config_language').'/'.$route.'.php') && (!is_null($folder) && !is_null($extension) && is_file(DIR_LANGUAGE.$this->config->get('config_language').'/'.$folder.'/'.$extension.'.php'))) {
			$route = $folder.'/'.$extension;
		}
	}		
}

?>