<?php
class ControllerExtensionModuleProductsByCategories extends Controller {
	private $error = array();

	public function index() {
		
		$categories = $this->config->get('module_products_by_categories_categories');
		if (empty($categories)) {
			return;
		}

		if ($this->config->get('module_products_by_categories_swiper')) {
			$data['swiper'] = true;
			$this->document->addStyle('catalog/view/javascript/swiper/swiper-bundle.min.css');
			$this->document->addScript('catalog/view/javascript/swiper/swiper-bundle.min.js');
		}

		$this->load->model('catalog/product');
		$this->load->model('tool/image');

		$results = $this->model_catalog_product->getProductsByCategories(array(
			'return_raw' => true,
			'category_id' => $categories,
			'sort_order' => 'product_date_added DESC',
		));

		//print_r($results);

		$cart = array_flip(array_column($this->cart->getProducts(), 'product_id'));
		$data['categories'] = array();
		$product_limit = $this->config->get('module_products_by_categories_limit');
		$i = 0;
		$last_category_id = 0;
		foreach ($results as $result) {
			if ($i >= $product_limit && $last_category_id == $result['category_id']) {
				continue;
			}
			if ($last_category_id != $result['category_id']) {
				$i = 0;
				$last_category_id = $result['category_id'];
			}
			$data['categories'][$result['category_id']]['category_id'] = $result['category_id'];
			$data['categories'][$result['category_id']]['name'] = $result['category_name'];
			$data['categories'][$result['category_id']]['href'] = $this->url->link('product/category', 'path=' . $result['category_id']);

			if ($result['category_image']) {
				$data['categories'][$result['category_id']]['image'] = $this->model_tool_image->resize($result['category_image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
			} else {
				$data['categories'][$result['category_id']]['image'] = $this->model_tool_image->resize('placeholder.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
			}

			if ($result['product_image']) {
				$image = $this->model_tool_image->resize($result['product_image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
			} else {
				$image = $this->model_tool_image->resize('placeholder.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
			}

			if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
				$price = $this->currency->format($this->tax->calculate($result['discount'] ?: $result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
			} else {
				$price = false;
			}

			if ((float)$result['special']) {
				$special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
			} else {
				$special = false;
			}

			if ($this->config->get('config_tax')) {
				$tax = $this->currency->format((float)$result['special'] ? $result['special'] : $result['price'], $this->session->data['currency']);
			} else {
				$tax = false;
			}

			if ($this->config->get('config_review_status')) {
				$rating = (int)$result['rating'];
			} else {
				$rating = false;
			}

			$data['categories'][$result['category_id']]['products'][$result['product_id']]['product_id'] = $result['product_id'];
			$data['categories'][$result['category_id']]['products'][$result['product_id']]['thumb'] = $image;
			$data['categories'][$result['category_id']]['products'][$result['product_id']]['name'] = $result['product_name'];
			$data['categories'][$result['category_id']]['products'][$result['product_id']]['description'] = utf8_substr(trim(strip_tags(html_entity_decode($result['product_description'], ENT_QUOTES, 'UTF-8'))), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..';
			$data['categories'][$result['category_id']]['products'][$result['product_id']]['price_raw'] = $result['special'] ?: $result['discount'] ?: $result['price'];
			$data['categories'][$result['category_id']]['products'][$result['product_id']]['price'] = $price;
			$data['categories'][$result['category_id']]['products'][$result['product_id']]['in_cart'] = isset($cart[$result['product_id']]);
			$data['categories'][$result['category_id']]['products'][$result['product_id']]['special'] = $special;
			$data['categories'][$result['category_id']]['products'][$result['product_id']]['special_discount'] = $result['special_discount'];
			$data['categories'][$result['category_id']]['products'][$result['product_id']]['measure_name'] = $result['measure_name'];
			$data['categories'][$result['category_id']]['products'][$result['product_id']]['tax'] = $tax;
			$data['categories'][$result['category_id']]['products'][$result['product_id']]['minimum'] = $result['minimum'] > 0 ? $result['minimum'] : 1;
			$data['categories'][$result['category_id']]['products'][$result['product_id']]['rating'] = $result['rating'];
			$data['categories'][$result['category_id']]['products'][$result['product_id']]['href'] = $this->url->link('product/product', 'product_id=' . $result['product_id']);
			$i++;
		}

		$filter = array_flip($categories);
		usort($data['categories'], function($a, $b) use ($filter) {
			if ($filter[$a['category_id']] == $filter[$b['category_id']]) {
			    return 0;
			}
			return ($filter[$a['category_id']] > $filter[$b['category_id']]) ? 1 : -1;
		});

		//print_r($data['categories']);
		return $this->load->view('extension/module/products_by_categories', $data);
	}

}
