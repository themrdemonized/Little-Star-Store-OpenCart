<?php
class ControllerEventCdekshipping extends Controller {
	private $api;
	private $log;
	private $error = array();
	private $time_execute;
	private $new_application;
	private $setting;

	private $api_url = "https://api.cdek.ru/v2";
	private $auth_url = "https://api.cdek.ru/v2/oauth/token?parameters";
	private $auth_token;
	private $order_url = "https://api.cdek.ru/v2/orders";

	public function addScripts() {
		$this->document->addStyle('catalog/view/theme/default/stylesheet/sdek.css');
	    $this->document->addScript('//api-maps.yandex.ru/2.1/?lang=ru_RU&ns=cdekymap');
	    $this->document->addScript('catalog/view/javascript/sdek.js');
	}

	private function getSetting() {
		return $this->config->get('cdek_integrator_setting');
	}

	public function createOrder($order_id) {

		if(!isset($this->session->data['cdek']) || !isset($this->session->data['cdek']['pvz'])) {
			return;
		}

		$this->load->model('checkout/order');

		$cdek_info = $this->session->data['cdek'];
		$cdek_setting = $this->getSetting();
		$order_info = $this->model_checkout_order->getOrder($order_id);

		$cdek_order = array(
			'number' => $order_id,
			'tariff_code' => str_replace("tariff_", "", str_replace("_MRG", "", $cdek_info['tariff'])),
			'comment' => $order_info['comment'],
			'shipment_point' => isset($cdek_info['shipment_point']) ? $cdek_info['shipment_point'] : NULL,
			'delivery_point' => isset($cdek_info['pvz']) ? $cdek_info['pvz'] : NULL,
			'date_invoice' => date("Y-m-d"),
			'delivery_recipient_cost' => !empty($cdek_setting['delivery_recipient_cost']) ? array(
				'value' => $cdek_setting['delivery_recipient_cost'],
			) : NULL,
			'recipient' => array(
				'company' => $order_info['payment_company'],
				'name' => $order_info['payment_firstname'],
				'email' => $order_info['email'],
				'phones' => array(
					0 => array(
						'number' => (function() use ($order_info) {
							$telephone = $order_info['telephone'];
							$telephone = trim($telephone);
							$telephone = preg_replace('/[^0-9+]/isu', '', $telephone);

							if (strpos($telephone, '8') !== 0) {
								$telephone = preg_replace('/^(?:\+7|7)/isu', '', $telephone);
								$telephone = '8' . $telephone;
							}
							return $telephone;
						})()
					)
				)
			),
			'from_location' => !isset($cdek_info['shipment_point']) ? array(
				'city' => $cdek_setting['city_name'],
				'address' => $this->config->get('config_address')
			) : NULL,
			'to_location' => !isset($cdek_info['pvz']) ? array(
				'address' => $order_info['payment_address_1']
			) : NULL,
			'packages' => array(
				0 => (function() use ($order_id, $order_info, $cdek_setting) {
					$order_products = $this->model_checkout_order->getOrderProducts($order_id);
					$width = $length = $height = $total_weight = 0;
					$items = array();
					foreach ($order_products as $product) {
						$width = $product['width'];
						$length = $product['length'];
						$height = $product['height'];
						$total_weight += $product['weight'] * 1000;
						$product_options = $this->model_checkout_order->getOrderOptions($order_id, $product['order_product_id'])[0];
						$items[] = array(
							'name' => "{$product['name']} ({$product_options['value']})",
							'ware_key' => $product['model'] ?: $product['name'],
							'payment' => array(
								'value' => $cdek_setting['cod'] ? $product['price'] : 0
							),
							'cost' => $product['price'],
							'weight' => $product['weight'] * 1000,
							'amount' => $product['quantity']
						);
					}
					return array(
						'number' => "{$order_id}-0",
						'weight' => (int) $total_weight,
						'length' => (int) $length,
						'width' => (int) $width,
						'height' => (int) $height,
						'items' => $items
					);
				})()
			) 
		);

		//print_r($cdek_order);

    	$ch = curl_init();
    	curl_setopt_array($ch, array(
    		CURLOPT_URL => $this->auth_url,
    		CURLOPT_POST => 1,
    		CURLOPT_POSTFIELDS => "grant_type=client_credentials&client_id={$cdek_setting['account']}&client_secret={$cdek_setting['secure_password']}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/x-www-form-urlencoded"
            ),
    	));
    	$response = curl_exec($ch);
    	if ($response === false) {
			// echo 'Curl error: ', curl_error($ch), "\n";
			file_put_contents("cdek/ERROR-CDEK-" . $order_id . ".log", "Curl error: " . curl_error($ch) . "\n" . $response);
        	curl_close($ch);
        	return 0;
        } else {
        	$response_decoded = json_decode($response, true);
            $this->auth_token = $response_decoded['access_token'];
            curl_close($ch);
            $data_string = json_encode($cdek_order);
            $ch = curl_init();
            $header = array();
            $header[] = "Content-Type: application/json";
            $header[] = "Authorization: Bearer " . $this->auth_token;
            curl_setopt($ch, CURLOPT_URL, $this->order_url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);
            file_put_contents("cdek/cdek-" . $order_id . ".log", $response);
        }
	}

	public function successOrder() {
		$this->load->model('checkout/order');
		if (isset($this->session->data['order_id'])) {
			$order = $this->model_checkout_order->getOrder($this->session->data['order_id']);
			if (strpos($order['shipping_code'], "cdek") !== false) {
				$this->createOrder($this->session->data['order_id']);
	        }
	        unset($this->session->data['cdek']);
	    }
	}

	public function orderCreate($route, $input_data, $order_id) {
		if($route = "checkout/order/addOrder" && (int)$order_id) {
			$this->rememberCdek($order_id);
		}
	}

	public function orderHistory($route, $input_data) {
		if($route = "checkout/order/addOrderHistory" && $input_data && isset($input_data[0])) {
			$order_id = (int)$input_data[0];
			if($order_id) {
				$this->rememberCdek($order_id);
			}
		}
	}

	private function rememberCdek($order_id) {
		if(!isset($this->session->data['cdek'])) {
			return;
		}

		if(!isset($this->session->data['cdek']['pvz'])) {
			return;
		}

		$data['pvz'] = $this->session->data['cdek']['pvz'];

		$data['city'] = 0;
		if(isset($this->session->data['cdek']['city']) && $this->session->data['cdek']['city']) {
			$data['city'] = (int)$this->session->data['cdek']['city'];
		}

		$sql = "INSERT INTO `" . DB_PREFIX . "order_to_sdek` (`order_to_sdek_id`, `order_id`, `cityId`, `pvz_code`)
		VALUES (NULL, '".(int)$order_id."', '".$data['city']."','".$this->db->escape($data['pvz'])."') ON DUPLICATE KEY UPDATE
		cityId = '".$data['city']."', pvz_code='".$this->db->escape($data['pvz'])."'";
		$this->db->query($sql);


		$comment = '';

        if(isset($this->session->data['cdek']['pvzinfo']) && $this->session->data['cdek']['pvzinfo']) {
            $pvz_comment = $this->session->data['cdek']['pvzinfo'];

            $queryComment = $this->db->query("SELECT comment FROM `" . DB_PREFIX . "order` WHERE order_id = '".$order_id."'");
            $comment = $queryComment->row['comment'];

            $comment = preg_replace("/\[CDEK\].*\[\/CDEK\]/m", "", $comment);

            if($comment) {
                $newComment = $comment . "\n" . '[CDEK]Выбранный ПВЗ: '.$pvz_comment . "[/CDEK]";
            } else {
                $newComment = '[CDEK]Выбранный ПВЗ: '.$pvz_comment . "[/CDEK]";
            }

            $this->db->query("UPDATE `" . DB_PREFIX . "order`
                SET comment = '" . $this->db->escape($newComment) ."'
                WHERE order_id = '".(int)$order_id."'");
        }
	}

	public function checkTariffPvz() {
		$json['error']['warning'] = false;

		$tariff = $this->request->post['shipping_method'];
		if(stripos($tariff, 'MRG') !== false)
		{
			if(!isset($this->session->data['cdek'])) {
				$json['error']['warning'] = 'Для выбранного тарифа нужно выбрать пвз';
			}

			if(!isset($this->session->data['cdek']['pvz']) || !$this->session->data['cdek']['pvz']) {
				$json['error']['warning'] = 'Для выбранного тарифа нужно выбрать пвз';
			}
		}

		if($json['error']['warning']) {
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			return false;
		}
	}
}