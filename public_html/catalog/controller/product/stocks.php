<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerProductStocks extends Controller {
	public function index() {
		$this->load->language('product/stocks');

		$this->load->model('catalog/stocks');

		$this->load->model('tool/image');

		$this->load->model('localisation/language');
		
		$data['text_empty'] = $this->language->get('text_empty');

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
			$this->document->setRobots('noindex,follow');
		} else {
			$sort = 's.start_at';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
			$this->document->setRobots('noindex,follow');
		} else {
			$order = 'DESC';
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
			$this->document->setRobots('noindex,follow');
		} else {
			$page = 1;
		}

		if (isset($this->request->get['limit'])) {
			$limit = (int)$this->request->get['limit'];
			$this->document->setRobots('noindex,follow');
		} else {
			$limit = 8;
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->config->get('stocks_heading_title') ?: $this->language->get('text_stocks'),
			'href' => $this->url->link('product/stocks')
		);

		$category_info = $this->model_catalog_stocks->getStocks(array('sort' => $sort, 'order' => $order, 'start' => ($page - 1) * $limit, 'limit' => $limit));

		if ($category_info) {

			$this->document->setTitle($this->config->get('stocks_meta_title') ?: $this->language->get('text_title'));			
			$this->document->setDescription($this->config->get('stocks_meta_description') ?: $this->language->get('text_description'));
			$this->document->setKeywords($this->config->get('stocks_meta_keyword') ?: $this->language->get('text_keyword'));

			$data['stocks'] = array();

			$locales = array_flip(explode(",", $this->model_localisation_language->getLanguage($this->config->get('config_language_id'))['locale']));

			if (isset($locales['ru_RU'])) {
				$month = array('нулября', 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря');
			}

			foreach ($category_info as $stock) {
				$start_at = new DateTime($stock['start_at']);
				$end_at = new DateTime($stock['end_at']);
				if ($start_at->format('Y') == $end_at->format('Y')) {
					if ($start_at->format('n') == $end_at->format('n')) {
						$date = sprintf($this->language->get('text_date_same_month'), $start_at->format('j'), $end_at->format('j'), isset($month) ? $month[(int)$start_at->format('n')] : $start_at->format('F'), $start_at->format('Y'));
					} else {
						$date = sprintf($this->language->get('text_date_same_year'), $start_at->format('j'), isset($month) ? $month[(int)$start_at->format('n')] : $start_at->format('F'), $end_at->format('j'), isset($month) ? $month[(int)$end_at->format('n')] : $end_at->format('F'), $start_at->format('Y'));
					}
				} else {
					$date = sprintf($this->language->get('text_date'), $start_at->format('j'), isset($month) ? $month[(int)$start_at->format('n')] : $start_at->format('F'), $start_at->format('Y'), $end_at->format('j'), isset($month) ? $month[(int)$end_at->format('n')] : $end_at->format('F'), $end_at->format('Y'));
				}
				$data['stocks'][] = array(
					'stocks_id' => $stock['stocks_id'],
					'date' => $date,
					'start_at' => $stock['start_at'],
					'end_at' => $stock['end_at'],
					'name' => $stock['name'],
					'discount' => $stock['discount'],
					'image' =>  $stock['image'] ? $this->model_tool_image->get_image($stock['image']) : '',
					'href' => $this->url->link('product/stocks/stocks', 'stocks_id=' . $stock['stocks_id'])
				);
			}

			$product_total = $this->model_catalog_stocks->getTotalStocks();

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$data['sorts'] = array();

			// $data['sorts']['p.viewed-DESC'] = array(
			// 	'text'  => $this->language->get('text_viewed'),
			// 	'value' => 'p.viewed-DESC',
			// 	'href'  => $this->url->link('product/stocks', 'path=' . $this->request->get['path'] . '&sort=p.viewed&order=DESC' . $url)
			// );

			// $data['sorts']['p.price-ASC'] = array(
			// 	'text'  => $this->language->get('text_price_asc'),
			// 	'value' => 'p.price-ASC',
			// 	'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=p.price&order=ASC' . $url)
			// );

			// $data['sorts']['p.price-DESC'] = array(
			// 	'text'  => $this->language->get('text_price_desc'),
			// 	'value' => 'p.price-DESC',
			// 	'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=p.price&order=DESC' . $url)
			// );

			// $data['sorts']['pd.name-ASC'] = array(
			// 	'text'  => $this->language->get('text_name_asc'),
			// 	'value' => 'pd.name-ASC',
			// 	'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=pd.name&order=ASC' . $url)
			// );

			// if ($this->config->get('config_review_status')) {
			// 	$data['sorts']['rating-DESC'] = array(
			// 		'text'  => $this->language->get('text_rating_desc'),
			// 		'value' => 'rating-DESC',
			// 		'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=rating&order=DESC' . $url)
			// 	);

			// 	$data['sorts']['rating-ASC'] = array(
			// 		'text'  => $this->language->get('text_rating_asc'),
			// 		'value' => 'rating-ASC',
			// 		'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=rating&order=ASC' . $url)
			// 	);
			// }

			$pagination = new Pagination();
			$pagination->total = $product_total;
			$pagination->page = $page;
			$pagination->limit = $limit;
			$pagination->url = $this->url->link('product/stocks', $url . '&page={page}');

			$data['pagination'] = $pagination->render();

			$data['results'] = sprintf($this->language->get('text_pagination'), ($product_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($product_total - $limit)) ? $product_total : ((($page - 1) * $limit) + $limit), $product_total, ceil($product_total / $limit));

			// http://googlewebmastercentral.blogspot.com/2011/09/pagination-with-relnext-and-relprev.html
			if ($page == 1) {
			    $this->document->addLink($this->url->link('product/stocks'), 'canonical');
			} else {
				$this->document->addLink($this->url->link('product/stocks', 'page='. $page), 'canonical');
			}
			
			if ($page > 1) {
			    $this->document->addLink($this->url->link('product/stocks', (($page - 2) ? 'page='. ($page - 1) : '')), 'prev');
			}

			if ($limit && ceil($product_total / $limit) > $page) {
			    $this->document->addLink($this->url->link('product/stocks', 'page='. ($page + 1)), 'next');
			}

			$this->document->addScript('catalog/view/javascript/moment.min.js');

			$data['sort'] = $sort;
			$data['order'] = $order;
			$data['limit'] = $limit;

			//$data['current_sort'] = $data['sorts']["{$data['sort']}-{$data['order']}"]['text'];

			$data['continue'] = $this->url->link('common/home');

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');
			

			$this->response->setOutput($this->load->view('product/stocks_list', $data));
		} else {
			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			// $data['breadcrumbs'][] = array(
			// 	'text' => $this->language->get('text_error'),
			// 	'href' => $this->url->link('product/stocks', $url)
			// );

			$this->document->setTitle($this->language->get('text_stocks'));
			$data['heading_title'] = $this->language->get('text_stocks');
			$data['text_error'] = $this->language->get('text_empty');

			$data['continue'] = $this->url->link('common/home');

			$this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('error/not_found', $data));
		}
	}

	public function stocks() {
		$this->load->language('product/stocks');

		$this->load->model('catalog/stocks');

		$this->load->model('tool/image');
		
		$data['text_empty'] = $this->language->get('text_empty');

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
			$this->document->setRobots('noindex,follow');
		} else {
			$sort = 'p.viewed';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
			$this->document->setRobots('noindex,follow');
		} else {
			$order = 'DESC';
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
			$this->document->setRobots('noindex,follow');
		} else {
			$page = 1;
		}

		if (isset($this->request->get['limit'])) {
			$limit = (int)$this->request->get['limit'];
			$this->document->setRobots('noindex,follow');
		} else {
			$limit = 8;
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->config->get('stocks_heading_title') ?: $this->language->get('text_stocks'),
			'href' => $this->url->link('product/stocks')
		);

		$cart = array_flip(array_column($this->cart->getProducts(), 'product_id'));

		if (isset($this->request->get['stocks_id'])) {

			$category_info = $this->model_catalog_stocks->getStock($this->request->get['stocks_id']);
			
			if ($category_info['meta_title']) {
				$this->document->setTitle($category_info['meta_title']);
			} else {
				$this->document->setTitle($category_info['name']);
			}
			
			if ($category_info['meta_h1']) {
				$data['heading_title'] = $category_info['meta_h1'];
			} else {
				$data['heading_title'] = $category_info['name'];
			}
			
			$this->document->setDescription($category_info['meta_description']);
			$this->document->setKeywords($category_info['meta_keyword']);

			$data['text_compare'] = sprintf($this->language->get('text_compare'), (isset($this->session->data['compare']) ? count($this->session->data['compare']) : 0));

			// Set the last category breadcrumb
			$data['breadcrumbs'][] = array(
				'text' => $category_info['name'],
				'href' => $this->url->link('product/stocks/stocks', 'stocks_id=' . $this->request->get['stocks_id'])
			);

			if ($category_info['image']) {
				$data['image'] = $this->model_tool_image->get_image($category_info['image']);
			} else {
				$data['image'] = '';
			}

			$data['description'] = nl2br(html_entity_decode($category_info['description'], ENT_QUOTES, 'UTF-8'));
			$data['requirements'] = nl2br(html_entity_decode($category_info['requirements'], ENT_QUOTES, 'UTF-8'));
			$data['compare'] = $this->url->link('product/compare');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$data['products'] = array();

			$filter_data = array(
				'filter_stocks_id' => $this->request->get['stocks_id'],
				'sort'               => $sort,
				'order'              => $order,
				'start'              => ($page - 1) * $limit,
				'limit'              => $limit
			);

			$product_total = $this->model_catalog_stocks->getTotalStockProducts($this->request->get['stocks_id']);
			$data['count'] = $product_total;

			$results = $this->model_catalog_stocks->getStockProducts($filter_data);

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
					'measure_name'=> $result['measure_name'],
					'description' => utf8_substr(trim(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8'))), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
					'price_raw'	  => $result['special'] ?: $result['price'],
					'in_cart'	  => isset($cart[$result['product_id']]),
					'price'       => $price,
					'special'     => $special,
					'special_discount' => $result['special_discount'],
					'tax'         => $tax,
					'minimum'     => $result['minimum'] > 0 ? $result['minimum'] : 1,
					'rating'      => $result['rating'],
					'href'        => $this->url->link('product/product', 'product_id=' . $result['product_id'])
				);
			}

			$url = '';

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$data['sorts'] = array();

			// $data['sorts']['p.viewed-DESC'] = array(
			// 	'text'  => $this->language->get('text_viewed'),
			// 	'value' => 'p.viewed-DESC',
			// 	'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=p.viewed&order=DESC' . $url)
			// );

			// $data['sorts']['p.price-ASC'] = array(
			// 	'text'  => $this->language->get('text_price_asc'),
			// 	'value' => 'p.price-ASC',
			// 	'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=p.price&order=ASC' . $url)
			// );

			// $data['sorts']['p.price-DESC'] = array(
			// 	'text'  => $this->language->get('text_price_desc'),
			// 	'value' => 'p.price-DESC',
			// 	'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=p.price&order=DESC' . $url)
			// );

			// $data['sorts']['pd.name-ASC'] = array(
			// 	'text'  => $this->language->get('text_name_asc'),
			// 	'value' => 'pd.name-ASC',
			// 	'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=pd.name&order=ASC' . $url)
			// );

			// if ($this->config->get('config_review_status')) {
			// 	$data['sorts']['rating-DESC'] = array(
			// 		'text'  => $this->language->get('text_rating_desc'),
			// 		'value' => 'rating-DESC',
			// 		'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=rating&order=DESC' . $url)
			// 	);

			// 	$data['sorts']['rating-ASC'] = array(
			// 		'text'  => $this->language->get('text_rating_asc'),
			// 		'value' => 'rating-ASC',
			// 		'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=rating&order=ASC' . $url)
			// 	);
			// }

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			$data['limits'] = array();

			$limits = array_unique(array($this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit'), 25, 50, 75, 100));

			sort($limits);

			foreach($limits as $value) {
				$data['limits'][] = array(
					'text'  => $value,
					'value' => $value,
					'href'  => $this->url->link('product/stocks/stocks', 'stocks_is=' . $this->request->get['stocks_id'] . $url . '&limit=' . $value)
				);
			}

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$pagination = new Pagination();
			$pagination->total = $product_total;
			$pagination->page = $page;
			$pagination->limit = $limit;
			$pagination->url = $this->url->link('product/stocks/stocks', 'stocks_id=' . $this->request->get['stocks_id'] . $url . '&page={page}');

			$data['pagination'] = $pagination->render();

			$data['results'] = sprintf($this->language->get('text_pagination'), ($product_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($product_total - $limit)) ? $product_total : ((($page - 1) * $limit) + $limit), $product_total, ceil($product_total / $limit));

			// http://googlewebmastercentral.blogspot.com/2011/09/pagination-with-relnext-and-relprev.html
			if ($page == 1) {
			    $this->document->addLink($this->url->link('product/stocks/stocks', 'stocks_id=' . $this->request->get['stocks_id']), 'canonical');
			} else {
				$this->document->addLink($this->url->link('product/stocks/stocks', 'stocks_id=' . $this->request->get['stocks_id'] . '&page='. $page), 'canonical');
			}
			
			if ($page > 1) {
			    $this->document->addLink($this->url->link('product/stocks/stocks', 'stocks_id=' . $this->request->get['stocks_id'] . (($page - 2) ? '&page='. ($page - 1) : '')), 'prev');
			}

			if ($limit && ceil($product_total / $limit) > $page) {
			    $this->document->addLink($this->url->link('product/stocks/stocks', 'stocks_id=' . $this->request->get['stocks_id'] . '&page='. ($page + 1)), 'next');
			}

			$data['sort'] = $sort;
			$data['order'] = $order;
			$data['limit'] = $limit;

			//$data['current_sort'] = $data['sorts']["{$data['sort']}-{$data['order']}"]['text'];

			$data['continue'] = $this->url->link('common/home');

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');
			

			$this->response->setOutput($this->load->view('product/stocks_products', $data));
		} else {
			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_error'),
				'href' => $this->url->link('product/stocks', $url)
			);

			$this->document->setTitle($this->language->get('text_error'));

			$data['continue'] = $this->url->link('common/home');

			$this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('error/not_found', $data));
		}
	}
}
