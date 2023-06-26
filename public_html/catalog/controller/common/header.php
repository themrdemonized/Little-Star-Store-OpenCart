<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerCommonHeader extends Controller {
	public function index() {
		// Analytics
		$this->load->model('setting/extension');

		$this->load->model('catalog/category');

		$data['categories'] = array();

		$categories = $this->cache->get('trade_import_common_header');
		if ($categories) {
			$data['categories'] = $categories;
		} else {
			$categories = $this->model_catalog_category->getCategories();
			foreach ($categories as $key => $category) {
				$sub_categories = array();
				foreach ($this->model_catalog_category->getCategories($category['category_id']) as $sub) {
					$sub_categories[] = array(
						'category_id' => $sub['category_id'],
						'name' => $sub['name'],
						'href' => $this->url->link('product/category', 'path=' . $sub['category_id']),
					);
				}

				$data['categories'][] = array(
					'category_id' => $category['category_id'],
					'name'        => $category['name'],
					'href'        => $this->url->link('product/category', 'path=' . $category['category_id']),
					'sub_categories' => $sub_categories
				);
			}
			$this->cache->set('trade_import_common_header', $data['categories']);
		}

		$data['analytics'] = array();

		$analytics = $this->model_setting_extension->getExtensions('analytics');

		foreach ($analytics as $analytic) {
			if ($this->config->get('analytics_' . $analytic['code'] . '_status')) {
				$data['analytics'][] = $this->load->controller('extension/analytics/' . $analytic['code'], $this->config->get('analytics_' . $analytic['code'] . '_status'));
			}
		}

		if ($this->request->server['HTTPS']) {
			$server = $this->config->get('config_ssl');
		} else {
			$server = $this->config->get('config_url');
		}

		if (is_file(DIR_IMAGE . $this->config->get('config_icon'))) {
			$this->document->addLink($server . 'image/' . $this->config->get('config_icon'), 'icon');
		}

		$data['title'] = empty($this->document->getTitle()) ? $this->config->get('config_meta_title') : $this->document->getTitle() . " | " . $this->config->get('config_meta_title');

		$data['base'] = $server;
		$data['description'] = empty($this->document->getDescription()) ? $this->config->get('config_meta_description') : $this->document->getDescription() . " | " . $this->config->get('config_meta_description');
		$data['keywords'] = empty($this->document->getKeywords()) ? $this->config->get('config_meta_keywords') : $this->document->getKeywords() . " | " . $this->config->get('config_meta_keywords');
		$data['links'] = $this->document->getLinks();
		$data['robots'] = $this->document->getRobots();
		$data['styles'] = $this->document->getStyles();
		$data['scripts'] = $this->document->getScripts('header');
		$data['lang'] = $this->language->get('code');
		$data['direction'] = $this->language->get('direction');

		$data['name'] = $this->config->get('config_name');

		if (is_file(DIR_IMAGE . $this->config->get('config_logo'))) {
			$data['logo'] = $server . 'image/' . $this->config->get('config_logo');
		} else {
			$data['logo'] = '';
		}

		$this->load->language('common/header');
		
		
		$host = isset($this->request->server['HTTPS']) && (($this->request->server['HTTPS'] == 'on') || ($this->request->server['HTTPS'] == '1')) ? HTTPS_SERVER : HTTP_SERVER;
		if ($this->request->server['REQUEST_URI'] == '/') {
			$data['og_url'] = $this->url->link('common/home');
		} else {
			$data['og_url'] = $host . substr($this->request->server['REQUEST_URI'], 1, (strlen($this->request->server['REQUEST_URI'])-1));
		}
		
		$data['og_image'] = $this->document->getOgImage();
		


		// Wishlist
		if ($this->customer->isLogged()) {
			$this->load->model('account/wishlist');

			$data['customer_name'] = $this->customer->getFirstName() . ' ' . $this->customer->getLastName();
			$data['text_wishlist'] = sprintf($this->language->get('text_wishlist'), $this->model_account_wishlist->getTotalWishlist());
		} else {
			$data['text_wishlist'] = sprintf($this->language->get('text_wishlist'), (isset($this->session->data['wishlist']) ? count($this->session->data['wishlist']) : 0));
		}

		$data['text_logged'] = sprintf($this->language->get('text_logged'), $this->url->link('account/account', '', true), $this->customer->getFirstName(), $this->url->link('account/logout', '', true));
		
		$data['home'] = $this->url->link('common/home');
		$data['wishlist'] = $this->url->link('account/wishlist', '', true);
		$data['logged'] = $this->customer->isLogged();
		$data['account'] = $this->url->link('account/account', '', true);
		$data['register'] = $this->url->link('account/register', '', true);
		$data['login'] = $this->url->link('account/login', '', true);
		$data['order'] = $this->url->link('account/order', '', true);
		$data['transaction'] = $this->url->link('account/transaction', '', true);
		$data['download'] = $this->url->link('account/download', '', true);
		$data['logout'] = $this->url->link('account/logout', '', true);
		$data['shopping_cart'] = $this->url->link('checkout/cart');
		$data['checkout'] = $this->url->link('checkout/checkout', '', true);
		$data['contact'] = $this->url->link('information/contact');
		$data['telephone'] = $this->config->get('config_telephone');
		$data['telephone2'] = $this->config->get('config_telephone2');
		$data['address'] = $this->config->get('config_address');
		$data['link_search'] = $this->url->link('product/search');
		$data['email'] = $this->config->get('config_email');
		$data['schema_address'] = explode(";", $this->config->get('config_schema_address'));
		
		$data['language'] = $this->load->controller('common/language');
		$data['currency'] = $this->load->controller('common/currency');
		if ($this->config->get('configblog_blog_menu')) {
			$data['blog_menu'] = $this->load->controller('blog/menu');
		} else {
			$data['blog_menu'] = '';
		}
		$data['search'] = $this->load->controller('common/search');
		$data['cart'] = $this->load->controller('common/cart');
		//$data['menu'] = $this->load->controller('common/menu');

		return $this->load->view('common/header', $data);
	}
}
