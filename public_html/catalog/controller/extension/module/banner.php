<?php
class ControllerExtensionModuleBanner extends Controller {
	public function index($setting) {
		static $module = 0;

		$this->load->model('design/banner');
		$this->load->model('tool/image');

		$this->document->addStyle('catalog/view/javascript/swiper/swiper-bundle.min.css');
		$this->document->addScript('catalog/view/javascript/swiper/swiper-bundle.min.js');

		$data['banners'] = array();

		$results = $this->model_design_banner->getBanner($setting['banner_id']);

		$data['count'] = 0;

		foreach ($results as $key => $result) {
			if (!$key) {
				$data['slides_per_view'] = $result['slides_per_view'];
				$data['space_between'] = $result['space_between'];
			}
			if (is_file(DIR_IMAGE . $result['image'])) {
				$data['banners'][] = array(
					'title' => $result['title'],
					'link'  => $result['link'],
					'image' => $this->model_tool_image->get_image($result['image'])
				);
				$data['count']++;
			}
		}

		$data['module'] = $module++;

		return $this->load->view('extension/module/banner', $data);
	}
}