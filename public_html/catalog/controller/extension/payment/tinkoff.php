<?php

class ControllerExtensionPaymentTinkoff extends Controller
{
    public function index()
    {
        $this->load->language('extension/payment/tinkoff');
        $this->load->model('extension/payment/tinkoff');
        $attempt = 1;
        do {
            $url = $this->model_extension_payment_tinkoff->getPaymentUrl();
            $attempt++;
        } while (!($url || $attempt > 3));
        // $url = $this->model_extension_payment_tinkoff->getPaymentUrl();
        $data = [
            'status' => 'error',
        ];

        if ($url) {
            $data = [
                'status' => 'success',
                'url' => $url,
            ];
        }

        return $this->load->view('extension/payment/tinkoff', $data);
    }

    public function callback()
    {
        $request = json_decode(file_get_contents("php://input"));
        $request->Success = $request->Success ? 'true' : 'false';
        $request = (array)$request;
        $this->load->model('extension/payment/tinkoff');

        if ($request['Token'] !== $this->model_extension_payment_tinkoff->getToken($request)) {
            die('NOTOK');
        }

        $this->load->model('checkout/order');
        $order = $this->model_checkout_order->getOrder($request['OrderId']);

        if (!$order) {
            die('NOTOK');
        }

        if ($request['Amount'] < (float)$order['total'] * 100) {
            die('NOTOK');
        }

        switch ($request['Status']) {
            case 'AUTHORIZED':
                $status = $this->config->get('payment_tinkoff_authorized');
                break;
            case 'CONFIRMED':
                $status = $this->config->get('payment_tinkoff_confirmed');
                break;
            case 'CANCELED':
                $status = $this->config->get('payment_tinkoff_cancelled');
                break;
            case 'REJECTED':
                $status = $this->config->get('payment_tinkoff_rejected');
                break;
            case 'REVERSED':
                $status = $this->config->get('payment_tinkoff_reversed');
                break;
            case 'REFUNDED':
                $status = $this->config->get('payment_tinkoff_refunded');
                break;
            default:
                $status = null;
                break;
        }

        if (!$status) {
            die('NOTOK');
        }

        $this->load->model('checkout/order');
        $this->model_checkout_order->addOrderHistory((int)$request['OrderId'], $status);

        die('OK');
    }

    public function failure()
    {
        $this->load->language('extension/payment/tinkoff');
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        return $this->response->setOutput($this->load->view('extension/payment/tinkoff_failure', $data));
    }

    private function guidv4() {
        if (function_exists('com_create_guid') === true)
            return trim(com_create_guid(), '{}');

        $data = openssl_random_pseudo_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private function sendToTrade($request_info) {
        $ch = curl_init();
        $header = array();
        $header[] = "Content-Type: application/json";
        if (isset($request_info['access_token'])) {
            $header[] = "Authorization: AccessToken " . $request_info['access_token'];
        }
        curl_setopt($ch, CURLOPT_URL, $request_info['url']);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request_info['data']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        if ($request_info['is_auth']) {
            $request_info['data'] = "Authentification";
        }
        if (curl_errno($ch)) {
            file_put_contents("trade_import/error_" . $request_info['date'] . ".log", curl_error($ch) . "\n" . $response);
            $this->model_extension_module_trade_import->add_order($request_info['data'], $this->session->data['order_id'], $response . "\nERROR: " . $request_info['date'] . ".log");
            curl_close($ch);
            return false;
        } else {
            switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
                case 200:  # OK
                  $this->model_extension_module_trade_import->add_order($request_info['data'], $this->session->data['order_id'], $response);
                  break;
                default:
                    file_put_contents("trade_import/error_" . $request_info['date'] . ".log", $http_code . "\n" . $response);
                    $this->model_extension_module_trade_import->add_order($request_info['data'], $this->session->data['order_id'], $http_code . "\n" . $response . "\nERROR: " . $request_info['date'] . ".log");
                    curl_close($ch);
                    return false;
            }
        }
        curl_close($ch);
        return json_decode($response, true);
    }

    private function sendCheck($info) {
        $server_url = rtrim($this->config->get('module_trade_import_server'), "/");
        $request_info = array(
            'date' => $info['date'],
            'is_auth' => true
        );
        $request_info['data'] =  json_encode(array(
            'login' => 'demonized',
            'password' => 'killbill'
        ));
        $request_info['url'] = "{$server_url}/api/v2/auth";
        $response = $this->sendToTrade($request_info);
        if ($response === false) {
            return false;
        }
        $request_info['is_auth'] = false;
        $request_info['access_token'] = $response['access_token'];

        $order_data = $info['order_data'];
        $contractor_info = array(
            'name' => $order_data['firstname'] !== 'null' ? $order_data['firstname'] : NULL,
            'phone' => $order_data['telephone'] !== 'null' ? $order_data['telephone'] : NULL,
            'email' => $order_data['email'] !== 'null' ? $order_data['email'] : NULL,
            'address' => $order_data['address'] !== 'null' ? $order_data['address'] : NULL
        );
        $request_info['url'] = "{$server_url}/api/v2/goods/contractors";
        $request_info['data'] = json_encode(array(
            "filters_query" => array(
                //"email" => "={$contractor_info['email']}",
                "phone" => "=" . substr($contractor_info['phone'], 1)
            ),
            "limit" => 1
        ));
        $response = $this->sendToTrade($request_info);
        if ($response === false) {
            return false;
        }

        if (!empty($response['items'])) {
            $contractor_id = $response['items'][0]['model']['id'];
        } else {
            $request_info['url'] = "{$server_url}/api/v2/model/apply";
            $request_info['data'] = json_encode(array(
                'type' => 'single',
                'entity' => array(
                    'meta' => array(
                        'type' => 'model',
                        'name' => 'Contractor'
                    ),
                    'model' => array(
                        'name' => $contractor_info['name'],
                        'phone' => $contractor_info['phone'],
                        'email' => $contractor_info['email'],
                        'address' => $contractor_info['address']
                    )
                ),
                'returns' => array()
            ));
            $response = $this->sendToTrade($request_info);
            if ($response === false) {
                return false;
            }
            $contractor_id = $response['model']['id'];
        }

        $product_quantity = $info['product_quantity'];
        $product_total = $info['product_total'];
        $product_data = $info['product_data'];

        $delivery_uuid = $this->config->get('module_trade_import_delivery_uuid');
        $delivery_total_price = 0;
        if ($delivery_uuid) {
            $totals = $this->model_checkout_order->getOrderTotalShipping($this->session->data['order_id']);
            foreach ($totals as $total) {
                $delivery_total_price = $total['value'];
            }
        }
        $product_total += $delivery_total_price;

        $request_info['url'] = "{$server_url}/api/v2/model/apply";
        $request_info['data'] = json_encode(array(
            'type' => 'single',
            'entity' => array(
                'meta' => array(
                    'type' => 'model',
                    'name' => 'Check'
                ),
                'model' => array(
                    "uuid" => $this->guidv4(),
                    "code" => "closed", // closed, aside, canceled
                    "check_type" => "sale", // sale, sale_return, buy, buy_return, defer, defer_return
                    "num" => $this->session->data['order_id'], // номер чека
                    "quantity" => $product_quantity,
                    "discount" => 0.0,
                    "total" => $product_total,
                    "total_payments" => $product_total, // всего оплачено
                    "payment_source" => "electron", // cash, cashback, electron, combined, other
                    "cashbox" => array( // касса
                        "meta" => array(
                            "type" => "model_ref",
                            "name" => "Cashbox"
                        ),
                        "ref" => array(
                            "id" => (int) $this->config->get('module_trade_import_order_cashbox_id')
                        )
                    ),
                    "cashier" => array( //кассир
                        "meta" => array(
                            "type" => "model_ref",
                            "name" => "Employee"
                        ),
                        "ref" => array(
                            "id" => (int) $this->config->get('module_trade_import_order_employee_id')
                        )
                    ),
                    "customer" => array( // покупатель
                        "meta" => array(
                            "type" => "model_ref",
                            "name" => "Contractor"
                        ),
                        "ref" => array(
                            "id" => $contractor_id
                        )
                    ),
                    "goods" => array(
                        "meta" => array(
                            "type" => "list"
                        ),
                        "items" => (function() use ($product_data, $delivery_uuid, $delivery_total_price) {
                            $items = array();
                            foreach ($product_data as $product) {
                                $characteristic_uuid = $characteristic_price = $characterictic_discount = NULL;
                                foreach ($product['option'] as $option) {
                                    if ($option['option_id'] == $this->model_extension_module_trade_import->get_optionid_by_code($this->model_extension_module_trade_import->get_product_code_by_id($product['product_id']))) {
                                        $characteristic_uuid = $this->model_extension_module_trade_import->get_option_value_code_by_id($option['option_value_id']);
                                        $characteristic_price = $option['price_old'];
                                        $characterictic_discount = $option['price_old'] - $option['price'];
                                        break;
                                    }
                                }
                                $items[] = array(
                                    'meta' => array(
                                        'type' => 'model',
                                        'name' => 'Good'
                                    ),
                                    'model' => array(
                                        'quantity' => $product['quantity'],
                                        'price' => isset($characteristic_price) ? $characteristic_price : $product['price_old'],
                                        'discount' => isset($characterictic_discount) ? $characterictic_discount : ($product['price_old'] - $product['price']),
                                        'total' => $product['total'],
                                        "enable_setup_unsetted_first_storage_with_remainders" =>true,
                                        'nomenclature' => array(
                                            "meta" => array(
                                                "type" => "model_ref",
                                                "name" => "Nomenclature"
                                            ),
                                            "ref" => array(
                                                "uuid" => $this->model_extension_module_trade_import->get_product_code_by_id($product['product_id'])
                                            )
                                        ),
                                        "characteristic" => array(
                                            "meta" => array(
                                                "type" => "model_ref",
                                                "name" => "Characteristic"
                                            ),
                                            "ref" => array(
                                                "uuid" => isset($characteristic_uuid) ? $characteristic_uuid : NULL
                                            )
                                        ),
                                        "storage" => array(
                                            "meta" => array(
                                                "type" => "model_ref",
                                                "name" => "Storage"
                                            ),
                                            "ref" => array(
                                                "id" => (int) $this->config->get('module_trade_import_order_storage_id')
                                            )
                                        )
                                    )
                                );
                            }
                            if ($delivery_uuid) {
                                $items[] = array(
                                    'meta' => array(
                                        'type' => 'model',
                                        'name' => 'Good'
                                    ),
                                    'model' => array(
                                        'quantity' => 1,
                                        'price' => $delivery_total_price,
                                        'discount' => 0,
                                        'total' => $delivery_total_price,
                                        'nomenclature' => array(
                                            "meta" => array(
                                                "type" => "model_ref",
                                                "name" => "Nomenclature"
                                            ),
                                            "ref" => array(
                                                "uuid" => $delivery_uuid
                                            )
                                        ),
                                        "storage" => array(
                                            "meta" => array(
                                                "type" => "model_ref",
                                                "name" => "Storage"
                                            ),
                                            "ref" => array(
                                                "id" => (int) $this->config->get('module_trade_import_delivery_storage_id')
                                            )
                                        )
                                    )
                                );
                            }
                            return $items;
                        })()
                    ),
                    "payments" => array(
                        "meta" => array(
                            "type" => "list"
                        ),
                        "items" => (function() use ($product_total) {
                            $items = array();
                            $items[] = array(
                                'meta' => array(
                                    'type' => 'model',
                                    'name' => 'Payment'
                                ),
                                'model' => array(
                                    "total" => $product_total,
                                    "payment_type" => array(
                                        "meta" => array(
                                            "type" => "model_ref",
                                            "name" => "PaymentType"
                                        ),
                                        "ref" => array(
                                            "id" => (int) $this->config->get('module_trade_import_order_payment_type_id')
                                        )
                                    )
                                )
                            );
                            return $items;
                        })()
                    )
                )
            ),
            "returns" => array()
        ));

        $response = $this->sendToTrade($request_info);
        if ($response === false) {
            return false;
        }

        $check_id = $response['model']['id'];
        $check_info = $response['model'];

        $request_info['url'] = "{$server_url}/api/v2/fiscalization/fiscalize_check";
        $request_info['data'] = json_encode(array(
            'check' => array(
                'meta' => array(
                    'type' => 'model_ref',
                    'name' => 'Check'
                ),
                'ref' => array(
                    'id' => $check_id
                )
            )
        ));

        $response = $this->sendToTrade($request_info);
        if ($response === false) {
            return false;
        }

        $this->model_extension_module_trade_import->add_check($this->session->data['order_id'], json_encode($check_info), json_encode($response));

    }

    public function success()
    {
        $this->load->language('extension/payment/tinkoff');
        $this->load->language('checkout/success');
        
        if ( isset($this->session->data['order_id']) && ( ! empty($this->session->data['order_id']))  ) {
            $this->session->data['last_order_id'] = $this->session->data['order_id'];
        }

        if (isset($this->session->data['order_id'])) {
            $product_data = $this->cart->getProducts();
            if ($this->config->get('module_trade_import_enable_order')) {
                //Connect to Trade and send order
                $time = time();
                $date = date('c', $time);
                $this->load->model('extension/module/trade_import');
                $this->load->model('account/customer');
                $this->load->model('checkout/order');
                $order_data = array();
                if ($this->customer->isLogged()) {
                    $customer_info = $this->model_account_customer->getCustomer($this->customer->getId());
                    $order_data['firstname'] = empty($customer_info['firstname']) ? 'null' : $customer_info['firstname'];
                    $order_data['email'] = empty($customer_info['email']) ? 'null' : $customer_info['email'];
                    $order_data['telephone'] = empty($customer_info['telephone']) ? 'null' : $customer_info['telephone'];
                } elseif (isset($this->session->data['guest'])) {
                    $order_data['firstname'] = empty($this->session->data['guest']['firstname']) ? 'null' : $this->session->data['guest']['firstname'];
                    $order_data['email'] = empty($this->session->data['guest']['email']) ? 'null' : $this->session->data['guest']['email'];
                    $order_data['telephone'] = empty($this->session->data['guest']['telephone']) ? 'null' : $this->session->data['guest']['telephone'];
                }
                $order_data['address'] = $this->model_extension_module_trade_import->get_order_address($this->session->data['order_id']);
                $order_data['address'] = empty($order_data['address']) ? 'null' : $order_data['address'];
                $order_data['invoice_prefix'] = $this->config->get('config_invoice_prefix');
                $order_data['store_id'] = $this->config->get('config_store_id');
                if ($order_data['store_id']) {
                    $order_data['store_url'] = parse_url($this->config->get('config_url'), PHP_URL_HOST);
                } else {
                    $order_data['store_url'] = parse_url(HTTP_SERVER, PHP_URL_HOST);
                }

                $data = array();
                $data['orders'] = array();
                $data['orders'][] = array(
                    'contractor'    => array(
                        'phone'     => $order_data['telephone'],
                        'email'     => $order_data['email'],
                        'name'      => $order_data['firstname'],
                        'address'   => $order_data['address'],
                    ),
                    'date'          => $date,
                    'description'   => $order_data['store_url'] . " - " . $order_data['invoice_prefix'] . "-" . $this->session->data['order_id'],
                );
                $data['orders'][0]['goods'] = array();
                $product_quantity = $product_total = 0;
                foreach ($product_data as $product) {
                    $characteristic_uuid = $characteristic_price = $characterictic_discount = NULL;
                    foreach ($product['option'] as $option) {
                        if ($option['option_id'] == $this->model_extension_module_trade_import->get_optionid_by_code($this->model_extension_module_trade_import->get_product_code_by_id($product['product_id']))) {
                            $characteristic_uuid = $this->model_extension_module_trade_import->get_option_value_code_by_id($option['option_value_id']);
                            $characteristic_price = $option['price_old'];
                            $characterictic_discount = $option['price_old'] - $option['price'];
                            break;
                        }
                    }

                    $product_quantity += (int) $product['quantity'];
                    $product_total += (double) $product['total'];
                    $data['orders'][0]['goods'][] = array(
                        'nomenclature_uuid'     => $this->model_extension_module_trade_import->get_product_code_by_id($product['product_id']),
                        'characteristic_uuid'   => isset($characteristic_uuid) ? $characteristic_uuid : NULL,
                        'shipment_uuid'         => NULL,
                        'quantity'              => (double)$product['quantity'],
                        'price'                 => isset($characteristic_price) ? (double)$characteristic_price : (double)$product['price_old'],
                        'discount'              => isset($characterictic_discount) ? (double)$characterictic_discount : (double) ($product['price_old'] - $product['price']),
                        'total'                 => (double)$product['total']
                    );
                }

                $delivery_uuid = $this->config->get('module_trade_import_delivery_uuid');
                if ($delivery_uuid) {
                    $totals = $this->model_checkout_order->getOrderTotalShipping($this->session->data['order_id']);
                    $total_price = 0;
                    foreach ($totals as $total) {
                        $total_price = $total['value'];
                    }
                    $data['orders'][0]['goods'][] = array(
                        'nomenclature_uuid' => $delivery_uuid,
                        'characteristic_uuid' => NULL,
                        'shipment_uuid' => NULL,
                        'quantity' => 1,
                        'price' => (double) $total_price,
                        'discount' => 0,
                        'total' => (double) $total_price
                    );
                }

                if (!file_exists("trade_import")) {
                    mkdir("trade_import", 0777, true);
                }
                $order_url = $this->config->get('module_trade_import_order_address');
                $url = $this->config->get('module_trade_import_code');
                $token = $this->config->get('module_trade_import_token');
                $old_api_token = $this->config->get('module_trade_import_order_token');
                if (!$this->config->get('module_trade_import_enable_old_api')) {
                    $data_string = json_encode(array('token' => $token));
                    $ch = curl_init();
                    $header = array();
                    $header[] = "Content-Type: application/json";
                    $header[] = "UUID: " . $token;
                    $header[] = "Timestamp: " . $date;
                    $header[] = "Authorization: " . hash("sha512", $token . $time);
                    $header[] = "Content-Length: " . strlen($data_string);
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    $response = curl_exec($ch);
                    if ($response === false) {
                        echo 'Curl error: ', curl_error($ch), "\n";
                        file_put_contents("trade_import/error_" . $date . ".log", curl_error($ch) . "\n" . $response);
                        $this->model_extension_module_trade_import->add_order($data_string, $this->session->data['order_id'], 'ERROR: ' . $date . ".log");
                        curl_close($ch);
                        return 0;
                    } else {
                        $response_decoded = json_decode($response, true);
                        $access_token = $response_decoded['access_token'];
                        curl_close($ch);
                        $data_string = json_encode($data);
                        $ch = curl_init();
                        $header = array();
                        $header[] = "Content-Type: application/json";
                        $header[] = "Authorization: Bearer " . $access_token;
                        curl_setopt($ch, CURLOPT_URL, $order_url);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                        $response = curl_exec($ch);
                        curl_close($ch);
                    }
                } else {
                    $data_string = json_encode($data);
                    $ch = curl_init();
                    $header = array();
                    $header[] = "Content-Type: application/json";
                    $header[] = "UUID: " . $old_api_token;
                    $header[] = "Timestamp: " . $date;
                    $header[] = "Authorization: " . hash("sha512", $old_api_token . $time);
                    $header[] = "Content-Length: " . strlen($data_string);
                    curl_setopt($ch, CURLOPT_URL, $order_url);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    $response = curl_exec($ch);
                    curl_close($ch);
                }
                if (json_decode($response) !== NULL) {
                    $this->model_extension_module_trade_import->add_order($data_string, $this->session->data['order_id'], $response);
                } else {
                    $this->model_extension_module_trade_import->add_order($data_string, $this->session->data['order_id'], 'ERROR: ' . $date . ".log");
                    file_put_contents("trade_import/error_" . $date . ".log", $response);
                }

                $check_response = $this->sendCheck(array(
                    'date' => $date,
                    'order_data' => $order_data,
                    'product_quantity' => $product_quantity,
                    'product_total' => $product_total,
                    'product_data' => $product_data,
                ));

                if ($check_response) {
                    //
                }
            }

            $data['metrika']["actionField"]['id'] = $this->config->get('config_invoice_prefix') . "-" . $this->session->data['order_id'];
            $data['metrika']['products'] = array();
            $this->load->model('catalog/product');
            foreach ($product_data as $product) {
                $category = $this->model_catalog_product->getCategoriesPath($product['product_id'], 5);
                if (!empty($product['option'])) {
                    foreach ($product['option'] as $option) {
                        $data['metrika']['products'][] = array(
                            "id" => str_replace('"', "", $product['model']),
                            "name" => str_replace('"', "", $product['name']),
                            "price" => $option['price'],
                            "category" => str_replace('"', "", $category),
                            "quantity" => $product['quantity'],
                            "variant" => str_replace('"', "", $option['name'])
                        );
                    }
                } else {
                    $data['metrika']['products'][] = array(
                        "id" => str_replace('"', "", $product['model']),
                        "name" => str_replace('"', "", $product['name']),
                        "price" => $product['price'],
                        "category" => str_replace('"', "", $category),
                        "quantity" => $product['quantity']
                    );
                }
            }

            $this->cart->clear();

            unset($this->session->data['shipping_method']);
            unset($this->session->data['shipping_methods']);
            unset($this->session->data['payment_method']);
            unset($this->session->data['payment_methods']);
            $customer_group_id = $this->session->data['guest']['customer_group_id'];
            unset($this->session->data['guest']);
            $this->session->data['guest']['customer_group_id'] = $customer_group_id;
            unset($this->session->data['comment']);
            unset($this->session->data['order_id']);
            unset($this->session->data['coupon']);
            unset($this->session->data['reward']);
            unset($this->session->data['voucher']);
            unset($this->session->data['vouchers']);
            unset($this->session->data['totals']);
        }
        
        if (! empty($this->session->data['last_order_id']) ) {
            $this->document->setTitle(sprintf($this->language->get('heading_title_customer'), $this->session->data['last_order_id']));
            $this->document->setRobots('noindex,follow');
        } else {
            $this->document->setTitle($this->language->get('heading_title'));
            $this->document->setRobots('noindex,follow');
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_basket'),
            'href' => $this->url->link('checkout/cart')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_checkout'),
            'href' => $this->url->link('checkout/checkout', '', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_success'),
            'href' => $this->url->link('checkout/success')
        );
        
        if (! empty($this->session->data['last_order_id']) ) {
            $data['heading_title'] = sprintf($this->language->get('heading_title_customer'), $this->session->data['last_order_id']);
        } else {
            $data['heading_title'] = $this->language->get('heading_title');
        }

        if ($this->customer->isLogged()) {
            $data['text_message'] = sprintf($this->language->get('text_customer'), $this->url->link('account/order/info&order_id=' . $this->session->data['last_order_id'], '', true), $this->url->link('account/account', '', true), $this->url->link('account/order', '', true), $this->url->link('information/contact'), $this->url->link('product/special'), $this->session->data['last_order_id'], $this->url->link('account/download', '', true));
        } else {
            $data['text_message'] = sprintf($this->language->get('text_guest'), $this->url->link('information/contact'), $this->session->data['last_order_id']);
        }

        $data['continue'] = $this->url->link('common/home');

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $this->response->setOutput($this->load->view('common/success', $data));
    }
}
