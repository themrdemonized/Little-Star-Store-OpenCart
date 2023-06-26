<?php
class ControllerCommonHeaderSellers extends Controller {
	public function index() {
		if (isset($this->request->get['category_id'])) {
			$this->load->language('product/category');
			$this->load->model('catalog/category');
			$this->load->model('catalog/product');
			$this->load->model('tool/image');
			$filter = '';
			$sort = 'p.viewed';
			$order = 'DESC';
			$limit = 4;
			$filter_data = array(
				'filter_category_id' => $this->request->get['category_id'],
				'filter_filter'      => $filter,
				'sort'               => $sort,
				'order'              => $order,
				'start'              => 0,
				'limit'              => $this->request->get['limit']
			);

			$data['limit'] = $this->request->get['limit'];

			$cart = array_flip(array_column($this->cart->getProducts(), 'product_id'));

			$data['products'] = array();

			$results = $this->model_catalog_product->getProducts($filter_data);

			if (empty($results)) {
				return NULL;
			}

			foreach ($results as $result) {
				if ($result['image']) {
					$image = $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'), 2);
				} else {
					$image = $this->model_tool_image->resize('placeholder.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'), 2);
				}

				if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
					$price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
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

				$data['products'][] = array(
					'product_id'  => $result['product_id'],
					'thumb'       => $image,
					'name'        => $result['name'],
					'description' => utf8_substr(trim(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8'))), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
					'price_raw'	  => $result['special'] ?: $result['price'],
					'in_cart'	  => isset($cart[$result['product_id']]),
					'price'       => $price,
					'special'     => $special,
					'tax'         => $tax,
					'minimum'     => $result['minimum'] > 0 ? $result['minimum'] : 1,
					'rating'      => $result['rating'],
					'href'        => $this->url->link('product/product', 'product_id=' . $result['product_id'])
				);
			}	

			$this->response->setOutput($this->load->view('common/header_sellers', $data));
		} else {
			$this->response->setOutput($this->load->view('common/header_sellers', array()));
		}
	}
}
