<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerProductProductImage extends Controller {
	public function index() {
		if (!(isset($this->request->get['p_id']) || isset($this->request->get['filename']))) {
			return;
		}

		$this->load->model('catalog/product');
		$this->load->model('tool/image');

		if (isset($this->request->get['p_id'])) {
			$filename = $this->model_catalog_product->getProduct($this->request->get['p_id'])['image'];
		} elseif (isset($this->request->get['filename'])) {
			$filename = $this->request->get['filename'];
		}

		if (!$filename) {
			return;
		} 

		if (isset($this->request->get['width'])) {
			$width = $this->request->get['width'];
		} else {
			$width = $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_width');
		}

		if (isset($this->request->get['height'])) {
			$height = $this->request->get['height'];
		} else {
			$height = $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_height');
		}

		$image = $this->model_tool_image->resize($filename, $width, $height);

		switch (pathinfo($image, PATHINFO_EXTENSION)) {
			case "gif": $ctype = "image/gif"; break;
		    case "png": $ctype = "image/png"; break;
		    case "jpeg":
		    case "jpg": $ctype = "image/jpeg"; break;
		    case "svg": $ctype = "image/svg+xml"; break;
		    case "webp": $ctype = "image/webp"; break;
		    default: $ctype = false; break;
		}
		if (!$ctype) {
			return;
		}
		
		$this->response->addHeader("Content-type: {$ctype}");
		$this->response->setOutput(file_get_contents($image));
	}

	public function getColorImage() {
		if (!isset($this->request->post['option_value_id']) && !isset($this->request->post['main_colors'])) {
			return;
		}

		$this->load->model('catalog/product');
		$this->load->model('tool/image');
		$json = array();
		$option_value_id = $this->request->post['option_value_id'];

		$results = array();
		$main_colors = $this->request->post['main_colors'] == 'true';

		if ($main_colors) {
			$main_image = (array) $this->model_catalog_product->getProduct($this->request->post['product_id'])['image'];
			$additional_images = $this->model_catalog_product->getProductImages($this->request->post['product_id']);
			$results = array_merge($main_image, array_column($additional_images, 'image'));
		} else {
			$results = (array) $this->model_catalog_product->getOptionValueIdImages($option_value_id);
		}

		foreach ($results as $result) {
			$json[] = $this->model_tool_image->resize($result, $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_height'), 2);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

}
