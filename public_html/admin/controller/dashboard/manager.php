<?php

class ControllerDashboardManager extends Controller
{
	private $type = 'dashboard';
	private $extension = 'manager';
	private $extname = 'module_manager';
			
	public function index()
	{	
		$this->language->load('extension/module/'.$this->extension);
		$this->load->model('sale/'.$this->extension);
		
		if ($this->config->get($this->extname.'_addips')) {
			$this->load->model('user/api');
		
			$ips = array();

			$results = $this->model_user_api->getApiIps($this->config->get('config_api_id'));

			foreach ($results as $result) {
				$ips[] = $result['ip'];
			}

			if (!in_array($this->request->server['SERVER_ADDR'], $ips)) {
				$this->model_user_api->addApiIp($this->config->get('config_api_id'), $this->request->server['SERVER_ADDR']);
			} elseif (!in_array($this->request->server['REMOTE_ADDR'], $ips)) {
				$this->model_user_api->addApiIp($this->config->get('config_api_id'), $this->request->server['REMOTE_ADDR']);
			}
		}
		
		$this->languageid = $this->getLanguageID($this->config->get('config_language'));
		
		$data['extension'] = $this->extension;
		$data['text_title'] = $this->language->get('text_title');
		$data['notice'] = html_entity_decode($this->config->get($this->extname.'_notice'), ENT_QUOTES, 'UTF-8');
		
		$buttons = array('history', 'invoice', 'shipping', 'delete', 'create', 'minimize', 'toggle', 'filter', 'clear', 'edit_customer', 'view_order', 'edit_order');
			
		foreach ($buttons as $button) {
			$data['button_'.$button] = $this->language->get('button_'.$button);
		}

		$columns = array(
			'select' => '',
			'order_id' => 'o.order_id',
			'order_status_id' => 'o.order_status_id',		
			'customer' => 'customer',
			'recipient' => 'recipient',
			'date_added' => 'o.date_added',
			'date_modified' => 'o.date_modified',
			'products' => '',
			'payment' => 'o.payment_method',
			'shipping' => 'o.shipping_method',
			'subtotal' => 'subtotal',
			'total' => 'o.total',
			'actions' => '');
			
		foreach ($columns as $key => $column) {
			$data['column_'.$key] = $this->language->get('column_'.$key);
		}

		$data['text_sort'] = $this->language->get('text_sort');
		$data['text_edit_customer'] = $this->language->get('text_edit_customer');
		$data['text_view_order'] = $this->language->get('text_view_order');
		$data['text_edit_order'] = $this->language->get('text_edit_order');
		$data['text_toggle_address'] = $this->language->get('text_toggle_address');
		$data['text_products'] = $this->language->get('text_products');
		$data['text_toggle_products'] = $this->language->get('text_toggle_products');
		$data['text_any'] = $this->language->get('text_any');
		$data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');
		$data['text_unspecified'] = $this->language->get('text_unspecified');
		$data['text_delete_confirm'] = $this->language->get('text_delete_confirm');
		$data['text_comment'] = $this->language->get('text_comment');
		$data['text_missing'] = $this->language->get('text_missing');
		$data['text_tracking'] = $this->language->get('text_tracking');
		$data['text_empty_list'] = $this->language->get('text_empty_list');
		$data['text_add_info'] = $this->language->get('text_add_info');
		
		$data['button_comment'] = $this->language->get('button_comment');
		$data['button_change'] = $this->language->get('button_change');
		$data['button_close'] = $this->language->get('button_close');

		$user_token = "user_token=".$this->session->data['user_token'];

		$data['dashboard'] = $this->url->link('common/dashboard', $user_token, true);
		$data['history'] = $this->url->link('dashboard/manager/history', $user_token, true);
		$data['invoice'] = $this->url->link('sale/order/invoice', $user_token, true);
		$data['delete'] = $this->url->link('dashboard/manager/delete', $user_token, true);
		$data['create'] = $this->url->link('sale/order/add', $user_token, true);
		
		$data['shipping'] = $this->url->link('sale/order/shipping', $user_token, true);
		

		if (empty($this->session->data['api_token'])) {
			$result = $this->apiLogin();
					
			if (!empty($result['error'])) {
				$this->session->data[$this->extname.'_error_api'] = $result['error'];
			}
		}

		
		$data['filters'] = $this->config->get($this->extname.'_filters');
		
		$filter_select = null;
	
		if (isset($this->request->get['filter_order_id'])) {
			$filter_order_id = $this->request->get['filter_order_id'];
		} else {
			$filter_order_id = null;
		}

		if (isset($this->request->get['filter_order_status_id'])) {
			$filter_order_status_id = $this->request->get['filter_order_status_id'];
		} else {
			$filter_order_status_id = null;
		}
			
		if (isset($this->request->get['filter_customer'])) {
			$filter_customer = $this->request->get['filter_customer'];
		} else {
			$filter_customer = null;
		}

		if (isset($this->request->get['filter_recipient'])) {
			$filter_recipient = $this->request->get['filter_recipient'];
		} else {
			$filter_recipient = null;
		}
		
		if (isset($this->request->get['filter_date_added'])) {
			$filter_date_added = $this->request->get['filter_date_added'];
		} else {
			$filter_date_added = null;
		}
		
		if (isset($this->request->get['filter_date_modified'])) {
			$filter_date_modified = $this->request->get['filter_date_modified'];
		} else {
			$filter_date_modified = null;
		}
		
		if (isset($this->request->get['filter_products'])) {
			$filter_products = $this->request->get['filter_products'];
		} else {
			$filter_products = null;
		}
		
		if (isset($this->request->get['filter_payment'])) {
			$filter_payment = $this->request->get['filter_payment'];
		} else {
			$filter_payment = null;
		}
		
		if (isset($this->request->get['filter_shipping'])) {
			$filter_shipping = $this->request->get['filter_shipping'];
		} else {
			$filter_shipping = null;
		}

		if (isset($this->request->get['filter_subtotal'])) {
			$filter_subtotal = $this->request->get['filter_subtotal'];
		} else {
			$filter_subtotal = null;
		}
		
		if (isset($this->request->get['filter_total'])) {
			$filter_total = $this->request->get['filter_total'];
		} else {
			$filter_total = null;
		}
		
		$filter_actions = null;

		if (isset($this->request->get['limit'])) {
			$limit = $this->request->get['limit'];
		} elseif ($this->config->get($this->extname.'_default_limit')) {
			$limit = $this->config->get($this->extname.'_default_limit');
		} elseif ($this->config->get('config_admin_limit')) {
			$limit = $this->config->get('config_admin_limit');
		} elseif ($this->config->get('config_limit_admin')) {
			$limit = $this->config->get('config_limit_admin');
		} else {
			$limit = 20;
		}
		
		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} elseif ($this->config->get($this->extname.'_default_sort')) {
			$sort = $this->config->get($this->extname.'_default_sort');		
		} else {
			$sort = 'o.order_id';
		}

		$data['sort'] = $sort;
		
		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} elseif ($this->config->get($this->extname.'_default_order')) {
			$order = $this->config->get($this->extname.'_default_order');			
		} else {
			$order = 'DESC';
		}
		
		$data['sort_order'] = $order;
		
		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		if ($this->config->get($this->extname.'_default_links')) {
			$links = $this->config->get($this->extname.'_default_links');
		} else {
			$links = 10;
		}
		
		if (isset($this->request->get['mode'])) {
			$mode = $this->request->get['mode'];
		} elseif ($this->config->get($this->extname.'_mode')) {
			$mode = $this->config->get($this->extname.'_mode');			
		} else {
			$mode = 'full';
		}
		
		$params = array(
			'mode' => $mode,
			'page' => $page,
			'sort' => $sort,
			'order' => $order,
			'start' => ($page - 1) * $limit,
			'limit' => $limit,
			'links' => $links);	
	
		foreach ($columns as $key => $column) {
			$data['filter_'.$key] = ${'filter_'.$key};
			$params['filter_'.$key] = ${'filter_'.$key};
		}
						
		$url = $this->getFilterURL($this->request->get);
		
		$params['url'] = $url;
		
		$stats = $this->{'model_sale_'.$this->extension}->getStats($params);
		
		$params['total'] = $stats['orders'];
		
		$url .= ($limit < $stats['orders'] ? "&limit=".$limit."&page=".$page : "");
		$url .= ($order == 'ASC' ? "&order=DESC" : "&order=ASC");
		
		$return = base64_encode($url);
		
		foreach ($columns as $key => $column) {
			if ($column) {
				$data['sort_'.$key] = $this->url->link('common/dashboard', $url.'&sort='.$column, true);
			}
		}
		
		$data[$this->extname.'_pagination'] = $this->getPagination($params);

		$this->load->model('localisation/order_status');
    	
    	$data['ocstatuses'] = $this->model_localisation_order_status->getOrderStatuses();
		$data['ocstatuses'][] = array('order_status_id' => "0", 'name' => $this->language->get('text_missing'));
			
		$data['buttons'] = $this->config->get($this->extname.'_buttons');
		$data['columns'] = $this->config->get($this->extname.'_columns');

		$data['statuses'] = $this->config->get($this->extname.'_statuses');

		$this->load->model('localisation/order_status');
		
    	$statuses = $this->model_localisation_order_status->getOrderStatuses();
    	$statuses[] = array('order_status_id' => "0", 'name' => $this->language->get('text_missing'));
		
		foreach ($statuses as $status) {
			$item = $data['statuses'][$status['order_status_id']];
			
			if (isset($item['checked']) && !$item['checked'] || !in_array($status['order_status_id'], $stats['statuses'])) {
				unset($data['statuses'][$status['order_status_id']]);
			} else {
				$data['statuses'][$status['order_status_id']]['name'] = $status['name']; 
			}
		}

		$payments = $this->config->get($this->extname.'_payments');

		$data['payments'] = array();

		foreach ($stats['payments'] as $key => $name) {
			$data['payments'][$key]['name'] = $stats['payments'][$key];

			if (isset($payments[$key])) {
				$data['payments'][$key]['color'] = $payments[$key]['color']; 
			} else {
				$data['payments'][$key]['color'] = "";
			}
		}

		$shippings = $this->config->get($this->extname.'_shippings');

		$data['shippings'] = array();

		foreach ($stats['shippings'] as $key => $name) {
			$temp = explode(".", $key);
			$code = $temp[0];

			$data['shippings'][$key]['name'] = $stats['shippings'][$key];

			if (isset($shippings[$code])) {
				$data['shippings'][$key]['color'] = $shippings[$code]['color']; 
			} else {
				$data['shippings'][$key]['color'] = "";
			}
		}

		if ($this->config->get($this->extname.'_date_format')) {
			$date_format = $this->config->get($this->extname.'_date_format');
		} else {
			$date_format = $this->language->get('date_format_short');
		}
		
		$results = $this->{'model_sale_'.$this->extension}->getOrders($params);
		
		$data[$this->extension.'_orders'] = array();
		
		foreach ($results as $result) {
			$action = array();
			
			if (in_array('view_order', $data['buttons']) && $this->user->hasPermission('access', 'sale/order')) { 
				$action[] = array(
					'type' => 'info',
					'text' => $this->language->get('button_view_order'),
					'href' => $this->url->link('sale/order/info', $user_token.'&order_id='.$result['order_id'].'&return='.$return, true));
			}
			
			if (in_array('edit_order', $data['buttons']) && $this->user->hasPermission('modify', 'sale/order')) { 
				$action[] = array(
					'type' => 'pencil',
					'text' => $this->language->get('button_edit_order'),
					'href' => $this->url->link('sale/order/edit', $user_token.'&order_id='.$result['order_id'].'&return='.$return, true));
			}
			
			$products = $this->model_sale_order->getOrderProducts($result['order_id']);
	
			$items = array();
			$quantity = 0;
			
			foreach ($products as $product) {
				$option_data = array();
				$options = $this->model_sale_order->getOrderOptions($result['order_id'], $product['order_product_id']);

				if (!empty($options)) {
					foreach ($options as $option) {
				if ($option['type'] != 'file') {
					$option_data[] = array(
						'name'  => $option['name'],
						'value' => $option['value'],
						'type'  => $option['type']);
				} else {
					$option_data[] = array(
						'name'  => $option['name'],
						'value' => utf8_substr($option['value'], 0, utf8_strrpos($option['value'], '.')),
						'type'  => $option['type'],
						'href'  => $this->url->link('sale/order/download', $user_token.'&order_id='.$result['order_id'].'&order_option_id='.$option['order_option_id'], true));	
						}
					}
				}
		
				$quantity += $product['quantity'];
				
				$items[] = array(
					'product_id' => $product['product_id'],
					'href' => $this->url->link('catalog/product/edit', $user_token.'&product_id='.$product['product_id'], true),
					'name' => $product['name'],
					'quantity' => $product['quantity'],
					'options' => $option_data);
			}

			if ($this->config->get($this->extname.'_address_format')) {
				$format = $this->config->get($this->extname.'_address_format');
			} else {
				$format = $this->language->get('entry_address_default');
			}

			$format = html_entity_decode($format, ENT_QUOTES, "UTF-8");
				
			$find = array('{name}', '{store}', '{company}', '{telephone}', '{email}', '{address}', '{country}', '{city}', '{zone}', '{postcode}');

			$types = array('payment', 'shipping');
			
			foreach ($types as $type) {
				$address = array();

				if ($this->config->get($this->extname.'_name_format') == 'firstname') {
					$address['name'] = trim(trim($result[$type.'_firstname'])." ".trim($result[$type.'_lastname']));
				} else {
					$address['name'] = trim(trim($result[$type.'_lastname'])." ".trim($result[$type.'_firstname']));
				}

				$address['store'] = $result['store_name'];
				
				if ($result[$type.'_company']) $address['company'] = $result[$type.'_company'];
				if ($result['telephone']) $address['telephone'] = preg_replace('/[^\d]/', '', $result['telephone']);
				if ($result['email']) $address['email'] = $result['email'];
			
				$address['address'] = array();
			
				if ($result[$type.'_address_1']) $address['address'][] = $result[$type.'_address_1'];
				if ($result[$type.'_address_2']) $address['address'][] = $result[$type.'_address_2'];
				if ($result[$type.'_country']) $address['country'] = $result[$type.'_country'];
				if ($result[$type.'_zone']) $address['zone'] = $result[$type.'_zone'];
				if ($result[$type.'_city']) $address['city'] = $result[$type.'_city'];			
				if ($result[$type.'_postcode']) $address['postcode'] = $result[$type.'_postcode'];

				$replace = array(
					'name' => !empty($address['name']) ? $address['name'] : "",
					'store' => !empty($address['store']) ? $address['store'] : "",
					'company' => !empty($address['company']) ? $address['company'] : "",
					'telephone' => !empty($address['telephone']) ? $address['telephone'] : "",
					'email' => !empty($address['email']) ? $address['email'] : "",
					'address' => !empty($address['address']) ? implode(", ", $address['address']) : "",
					'country' => !empty($address['country']) ? $address['country'] : "",
					'city' => !empty($address['city']) ? $address['city'] : "",
					'zone' => !empty($address['zone']) ? $address['zone'] : "",
					'postcode' => !empty($address['postcode']) ? $address['postcode'] : "");

				${$type.'_address'} = str_replace(array("\r\n", "\r", "\n"), "<br />", preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), "<br />", trim(str_replace($find, $replace, $format))));
			}

			
			$edit_customer = ($this->user->hasPermission('modify', 'customer/customer') ? $this->url->link('customer/customer/edit', $user_token.'&customer_id='.$result['customer_id'].'&return='.$return, true) : "");
			

			$data[$this->extension.'_orders'][] = array(
				'selected' => isset($this->request->post['selected']) && in_array($result['order_id'], $this->request->post['selected']),
				'order_id'   => $result['order_id'],
				'comment' => trim($result['comment']),
				'customer' => $result['customer'],
				'customer_id' => $result['customer_id'],
				'edit_customer' => $edit_customer,
				'recipient' => $result['recipient'],
				'telephone' => $result['telephone'],
				'email' => $result['email'],
				'status' => $result['status'],
				'order_status_id' => $result['order_status_id'],		
				'date_added' => date($date_format, strtotime($result['date_added'])),
				'date_modified' => date($date_format, strtotime($result['date_modified'])),
				'shipping_code' => $result['shipping_code'],
				'shipping_method' => $result['shipping_method'],
				'shipping_address' => $shipping_address,
				'payment_code' => $result['payment_code'],
				'payment_method' => $result['payment_method'],			
				'payment_address' => $payment_address,
				'products' => $items,
				'quantity' => $quantity,
				'subtotal' => $this->currency->format($result['subtotal'], $result['currency_code'], $result['currency_value']),
				'total' => $this->currency->format($result['total'], $result['currency_code'], $result['currency_value']),
				'action' => $action);
		}
		
		$data['rptracking_status'] = $this->config->get('rptracking_status');
		$data[$this->extname.'_status'] = $this->config->get($this->extname.'_status');
		$data[$this->extname.'_hide_dashboard'] = $this->config->get($this->extname.'_hide_dashboard');
		
		return $this->load->view($this->type.'/'.$this->extension, $data);
	}
		
	public function history()
	{
		unset($this->session->data[$this->extname.'_error']);
		unset($this->session->data[$this->extname.'_success']);
		
		$this->language->load('extension/module/'.$this->extension);
		
		if (isset($this->request->post['selected'])) {
			if ($this->user->hasPermission('modify', 'sale/order')) {
				if (empty($this->session->data[$this->extname.'_error_api'])) {
					
					$orders = $this->request->post['selected'];
					$statuses = $this->request->post['statuses'];
					$comments = $this->request->post['comments'];
				
					if (isset($this->request->post['trackings'])) $trackings = $this->request->post['trackings'];
					else $trackings = array();
									
					foreach ($orders as $order_id) {
						$data = array(
							'order_status_id' => $statuses[$order_id],
							'comment' => $comments[$order_id],
							'notify' => $this->config->get($this->extname.'_notify'),
							'override' => true);

						if ($trackings && isset($trackings[$order_id]) && $this->config->get('rptracking_status')) {
							$this->db->query("UPDATE `".DB_PREFIX."order` SET rptracking = '".$this->db->escape($trackings[$order_id])."' WHERE order_id = ".$order_id);
						}
				
						$result = $this->apiRequest("index.php?route=api/order/history&order_id=".$order_id, $data);
					
						if (isset($result['error'])) break;
					}
			
					if (!isset($result['error'])) {
						$this->session->data[$this->extname.'_success'] = $this->language->get('message_history_success');
					} else {
						$this->session->data[$this->extname.'_error'] = $result['error'];
					}
				} else {
					$this->session->data[$this->extname.'_error'] = $this->session->data[$this->extname.'_error_api'];
				}
			} else {
				$this->language->load('sale/order');
				$this->session->data[$this->extname.'_error'] = $this->language->get('error_permission');
			}
		} else {
			$this->session->data[$this->extname.'_error'] = $this->language->get('error_selected');
		}
				
		$url = $this->getFilterURL($this->request->get);
				
		if (isset($this->request->get['limit'])) {
			$url .= '&limit='.$this->request->get['limit'];
		}
						
		if (isset($this->request->get['sort'])) {
			$url .= '&sort='.$this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order='.$this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page='.$this->request->get['page'];
		}

		if (isset($this->request->get['mode'])) {
			$url .= '&mode='.$this->request->get['mode'];
		}
								
		$this->response->redirect($this->url->link('common/dashboard', $url, true));
		
	}
		
	public function delete()
	{
		unset($this->session->data[$this->extname.'_error']);
		unset($this->session->data[$this->extname.'_success']);
				
		$this->language->load('extension/module/'.$this->extension);
		
		if (isset($this->request->post['selected'])) {
			if ($this->user->hasPermission('modify', 'sale/order')) {				
				if (!isset($this->session->data[$this->extname.'_error_api'])) {
					
					$orders = $this->request->post['selected'];
					
					foreach ($orders as $order_id) {				
						
						$result = $this->apiRequest("index.php?route=api/order/delete&order_id=".$order_id);
					
						if (isset($result['error'])) break;
					}
			
					if (!isset($result['error'])) {
						$this->session->data[$this->extname.'_success'] = $this->language->get('message_delete_success');
					} else {
						$this->session->data[$this->extname.'_error'] = $result['error'];
					}
				} else {
					$this->session->data[$this->extname.'_error'] = $this->session->data[$this->extname.'_error_api'];
				}
			} else {
				$this->language->load('sale/order');
				$this->session->data[$this->extname.'_error'] = $this->language->get('error_permission');
			}
		} else {
			$this->session->data[$this->extname.'_error'] = $this->language->get('error_selected');
		}
				
		$url = $this->getFilterURL($this->request->get);
				
		if (isset($this->request->get['limit'])) {
			$url .= '&limit='.$this->request->get['limit'];
		}
						
		if (isset($this->request->get['sort'])) {
			$url .= '&sort='.$this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order='.$this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page='.$this->request->get['page'];
		}

		if (isset($this->request->get['mode'])) {
			$url .= '&mode='.$this->request->get['mode'];
		}
						
		$this->response->redirect($this->url->link('common/dashboard', $url, true));
		
	}
	
	public function apiLogin()
	{
		$this->load->model('user/api');

		$api_info = $this->model_user_api->getApi($this->config->get('config_api_id'));

		if ($api_info && $this->user->hasPermission('modify', 'sale/order')) {
			$session = new Session($this->config->get('session_engine'), $this->registry);
			
			$session->start();
					
			$this->model_user_api->deleteApiSessionBySessonId($session->getId());
			
			$this->model_user_api->addApiSession($api_info['api_id'], $session->getId(), $this->request->server['REMOTE_ADDR']);
			
			$session->data['api_id'] = $api_info['api_id'];

			$data['api_token'] = $session->getId();
			$this->session->data['api_token'] = $data['api_token'];
		} else {
			$data['api_token'] = '';
		}
		return $api_info;
	}
		
	public function apiRequest($url, $data = array())
	{
		$json = array();
		
		$this->load->language('sale/order');

		if (isset($this->session->data['cookie']) || isset($this->session->data['api_token'])) {
			$curl = curl_init();

			if (substr($url, 0, 5) == 'https') {
				curl_setopt($curl, CURLOPT_PORT, 443);
			}
				
			if (isset($this->session->data['api_token'])) {
				$url .= "&api_token=".$this->session->data['api_token']."&store_id=0";
				$session = $this->getApiSession($this->config->get('config_api_id'));
			} else {
				$session = $this->session->data['cookie'];
			}
			
			curl_setopt($curl, CURLOPT_HEADER, false);
			curl_setopt($curl, CURLINFO_HEADER_OUT, true);
			curl_setopt($curl, CURLOPT_USERAGENT, $this->request->server['HTTP_USER_AGENT']);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_FORBID_REUSE, false);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_URL, $this->getStoreURL().$url);
			curl_setopt($curl, CURLOPT_COOKIE, session_name().'='.$session.';');
			
			if ($data) {
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
			}

			$result = curl_exec($curl);
	
			if (!$result) {
				$json['error'] = sprintf($this->language->get('error_curl'), curl_error($curl), curl_errno($curl));
			} else {
				$json = (array)json_decode($result);
			}
			
			curl_close($curl);			
		} else {
			$json['error'] = $this->language->get('error_api_login');
		}

		return $json;
	}
			
	public function getPagination($data)
	{
		$this->language->load('extension/module/'.$this->extension);
				
		if (($data['page'] <= 1) || ($data['page'] > $data['total'])) $page = 1;
		else $page = $data['page'];

		$data['url'] .= "&sort=".$data['sort'];
		$data['url'] .= "&order=".$data['order'];
				
		$url = $this->url->link('common/dashboard', $data['url'], true);
		$pages = ceil((int) $data['total'] / (int) $data['limit']);
				
		$output = "<div class='manager-pagination'>";
		$output .= "<div class='links'>";
		
		if ($page > 1) {
			$output .= "<a href='".$url."&page=1' class='first'>&laquo;&laquo;</a>";
			$output .= "<a href='".$url."&page=".($page - 1)."' class='previous'>&laquo;</a>";
		}

		if ($pages > 1) {
			if ($pages <= $data['links']) {
				$start = 1;
				$end = $pages;
			} else {
				$start = $page - floor($data['links'] / 2);
				$end = $page + floor($data['links'] / 2);
			
				if ($start < 1) {
					$end += abs($start) + 1;
					$start = 1;
				}
						
				if ($end > $pages) {
					$start -= ($end - $pages);
					$end = $pages;
				}
			}

			if ($start > 1) {
				$output .= "<div class='spacer'>...</div>";
			}

			for ($i = $start; $i <= $end; $i++) {
				if ($page == $i) {
					$output .= "<span class='page'>".$i."</span>";
				} else {
					$output .= "<a href='".$url."&page=".$i."' class='page'>".$i."</a>";
				}	
			}
							
			if ($end < $pages) {
				$output .= "<div class='spacer'>...</div>";
			}
		}
		
		if ($page < $pages) {
			$output .= "<a href='".$url."&page=".($page + 1)."' class='next'>&raquo;</a>";
			$output .= "<a href='".$url."&page=".$pages."' class='last'>&raquo;&raquo;</a>";
		}
				
		$output .= "</div>";
		
		$output .= "<div class='limit'>";
		$output .= "<input type='text' name='limit' value='".$data['limit']."'>";
		$output .= "<select name='mode'>";
		$output .= "<option value='full'".($data['mode'] == 'full' ? ' selected="selected"' : '').">".$this->language->get('text_mode_full')."</option>";
		$output .= "<option value='custom'".($data['mode'] == 'custom' ? ' selected="selected"' : '').">".$this->language->get('text_mode_custom')."</option>";
		$output .= "</select>";
		$output .= "</div>";
		
		$output .= "<div class='summary'>";
		
		if ($data['total']) {
			$output .= sprintf($this->language->get('text_total_orders'), $data['total']);
		}
		
		$output .= "</div>";
		$output .= "</div>";
				 
		return $output;
	}
	
	public function getFilterURL($data)
	{				
		$url = "user_token=".$this->session->data['user_token'];

		if (isset($data['filter_order_id'])) {
			$url .= '&filter_order_id='.$data['filter_order_id'];
		}
				
		if (isset($data['filter_order_status_id'])) {
			$url .= '&filter_order_status_id='.$data['filter_order_status_id'];
		}
				
		if (isset($data['filter_customer'])) {
			$url .= '&filter_customer='.urlencode(html_entity_decode($data['filter_customer'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($data['filter_recipient'])) {
			$url .= '&filter_recipient='.urlencode(html_entity_decode($data['filter_recipient'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($data['filter_date_added'])) {
			$url .= '&filter_date_added='.$data['filter_date_added'];
		}

		if (isset($data['filter_date_modified'])) {
			$url .= '&filter_date_modified='.$data['filter_date_modified'];
		}
				
		if (isset($data['filter_products'])) {
			$url .= '&filter_products='.urlencode(html_entity_decode($data['filter_products'], ENT_QUOTES, 'UTF-8'));
		}
				
		if (isset($data['filter_payment'])) {
			$url .= '&filter_payment='.urlencode(html_entity_decode($data['filter_payment'], ENT_QUOTES, 'UTF-8'));
		}
				
		if (isset($data['filter_shipping'])) {
			$url .= '&filter_shipping='.urlencode(html_entity_decode($data['filter_shipping'], ENT_QUOTES, 'UTF-8'));
		}
						
		if (isset($data['filter_subtotal'])) {
			$url .= '&filter_subtotal='.$data['filter_subtotal'];
		}
								
		if (isset($data['filter_total'])) {
			$url .= '&filter_total='.$data['filter_total'];
		}
						
		if (isset($data['mode'])) {
			$url .= '&mode='.$data['mode'];
		}
				
		return $url;
	}
	
	private function getLanguageID($code)
	{
		$query = $this->db->query("SELECT language_id FROM `".DB_PREFIX."language` WHERE code = '".$code."'");

		if (isset($query->row['language_id'])) return $query->row['language_id'];
		else return "";		
	}

	public function getApiSession($api_id)
	{
		$query = $this->db->query("SELECT session_id FROM `".DB_PREFIX."api_session` WHERE api_id = '".$api_id."'");
		
		if (isset($query->row['session_id'])) return $query->row['session_id'];
		else return "";	
	}
	
	private function getStoreURL()
	{			
		return $this->request->server['HTTPS'] ? HTTPS_CATALOG : HTTP_CATALOG;
	}
}


function nagmiString()
{
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$str = '';

	for ($i = 0; $i < 10; $i++) {
		$str = $characters[rand(0, 3371)];
	}

	return $str;
}

?>