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
	
	public function language(&$route)
	{
		$route = preg_replace('/[^a-zA-Z0-9_\/]/', '', (string)$route);
		
		list($base, $folder, $extension) = array_pad(explode('/', $route), 3, NULL);
		
		if (($base == 'extension') && !is_file(DIR_LANGUAGE.$this->config->get('config_language').'/'.$route.'.php') && (!is_null($folder) && !is_null($extension) && is_file(DIR_LANGUAGE.$this->config->get('config_language').'/'.$folder.'/'.$extension.'.php'))) {
			$route = $folder.'/'.$extension;
		}
	}
	
	public function view(&$route, &$data)
	{
		list($base, $folder, $extension) = array_pad(explode('/', $route), 3, NULL);
			
		if (($base == 'extension') && isset($data['back'])) {
			$data['back'] = $this->url->link('marketplace/extension', 'user_token='.$this->session->data['user_token'].'&type='.$folder, true);
		}
	}
}

?>