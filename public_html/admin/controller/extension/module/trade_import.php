<?php
class ControllerExtensionModuleTradeImport extends Controller {

    private function getData(&$data, $data_array) {
        $data_array = (array) $data_array;
        foreach ($data_array as $data_string) {
            $data[$data_string] = isset($this->request->post[$data_string]) ? $this->request->post[$data_string] : $this->config->get($data_string);
        }
    }

    public function index() {
        $this->load->language('extension/module/trade_import');
        $this->load->model('setting/setting');
        $this->load->model('extension/module/trade_import');
        $this->document->setTitle($this->model_extension_module_trade_import->get_store_name() . " - " . $this->language->get('heading_title'));
        if ($this->config->get('module_trade_import_time_zone') !== null) {
           date_default_timezone_set($this->config->get('module_trade_import_time_zone'));
        }

        $this->save();

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['code'])) {
            $data['error_code'] = $this->error['code'];
        } else {
            $data['error_code'] = '';
        }
    
        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];

            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/trade_import', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('extension/module/trade_import', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

        $fields = array(
            'module_trade_import_server',
            'module_trade_import_code',
            'module_trade_import_nomenclature',
            'module_trade_import_token',
            'module_trade_import_enable_old_api',
            'module_trade_import_old_api_address',
            'module_trade_import_old_api_token',
            'module_trade_import_enable_order',
            'module_trade_import_v2_api_order',
            'module_trade_import_order_address',
            'module_trade_import_order_token',
            'module_trade_import_order_cashbox_id',
            'module_trade_import_order_employee_id',
            'module_trade_import_order_payment_type_id',
            'module_trade_import_order_storage_id',
            'module_trade_import_price',
            'module_trade_import_price_city',
            'module_trade_import_price_map',
            'module_trade_import_parent_id',
            'module_trade_import_top_category',
            'module_trade_import_ignore_category',
            'module_trade_import_ignore_filter',
            'module_trade_import_ignore_property',
            'module_trade_import_add_properties_to_filters',
            'module_trade_import_banner_id',
            'module_trade_import_properties_as_description',
            'module_trade_import_default_weight',
            'module_trade_import_default_size',
            'module_trade_import_delivery_uuid',
            'module_trade_import_delivery_storage_id',
            'module_trade_import_enable_sync',
            'module_trade_import_sync_period',
            'module_trade_import_time_zone',
            'module_trade_import_local_json',
            'module_trade_import_save_json',
            'module_trade_import_sync_time',
            'module_trade_import_add_category',
            'module_trade_import_delete_category',
            'module_trade_import_hide_category',
            'module_trade_import_sub_filters',
            'module_trade_import_add_product',
            'module_trade_import_delete_product',
            'module_trade_import_hide_product',
            'module_trade_import_hide_empty_product',
            'module_trade_import_add_separate_products',
            'module_trade_import_round_price',
            'module_trade_import_ocfilter',
            'module_trade_import_keep_category_names',
            'module_trade_import_keep_category_description',
            'module_trade_import_keep_category_meta',
            'module_trade_import_keep_product_names',
            'module_trade_import_keep_product_description',
            'module_trade_import_keep_product_meta',
            'module_trade_import_short_url',
            'module_trade_import_full_path_url',
            'module_trade_import_full_sync',
            'module_trade_import_names_as_uuid',
            'module_trade_import_names_product_image_jpeg',
            'module_trade_import_banner_image_jpeg',
            'module_trade_import_image_ignore_same_size',
            'module_trade_import_ignore_noname_characteristics',
            'module_trade_import_add_one_product',
        );

        $this->getData($data, $fields);
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $data['userToken'] = $this->session->data['user_token'];
        if (!isset($data['next_schedule'])) {
            $data['next_schedule'] = $this->config->get('module_trade_import_sync_schedule');
        }
        $latest_operation = $this->model_extension_module_trade_import->get_latest_operation();
        if ($latest_operation) {
            $data['latest_id'] = $latest_operation['operation_id'];
            $data['latest_timestamp'] = $latest_operation['timestamp'];
            $data['latest_json_timestamp'] = $latest_operation['json_timestamp'];
            $data['latest_success'] = $latest_operation['success'];
        }
        $data['sync_options'] = array(
            array('text' => $this->language->get('text_2_min'), 'value' => '2_minutes'),
            array('text' => $this->language->get('text_10_min'), 'value' => '10_minutes'),
            array('text' => $this->language->get('text_30_min'), 'value' => '30_minutes'),
            array('text' => $this->language->get('text_1_hour'), 'value' => '1_hour'),
            array('text' => $this->language->get('text_2_hour'), 'value' => '2_hours'),
            array('text' => $this->language->get('text_3_hour'), 'value' => '3_hours'),
            array('text' => $this->language->get('text_6_hour'), 'value' => '6_hours'),
            array('text' => $this->language->get('text_12_hour'), 'value' => '12_hours'),
            array('text' => $this->language->get('text_1_day'), 'value' => '1_day'),
            array('text' => $this->language->get('text_2_days'), 'value' => '2_days'),
            array('text' => $this->language->get('text_3_days'), 'value' => '3_days'),
            array('text' => $this->language->get('text_1_week'), 'value' => '1_week')
        );
        $data['timezone_options'] = \DateTimeZone::listIdentifiers(DateTimeZone::ALL);
        $data['round_price_options'] = array(
            array('text' => $this->language->get('text_round_price_off'), 'value' => 'off'),
            array('text' => $this->language->get('text_round_price_normal'), 'value' => 'normal'),
            array('text' => $this->language->get('text_round_price_up'), 'value' => 'up'),
            array('text' => $this->language->get('text_round_price_down'), 'value' => 'down'),
            array('text' => $this->language->get('text_round_price_middle'), 'value' => 'middle'),
        );
    
        $this->response->setOutput($this->load->view('extension/module/trade_import', $data));
    }

    protected function cron_setting($cron_action = 'add')
    {
        $trade_import_dir = DIR_CONFIG . 'trade_import' . '/';
        $command = '* * * * * php ' . $trade_import_dir . 'trade_import.php > /dev/null 2>&1';
        $jobs = explode(PHP_EOL, shell_exec('crontab -l'));
        foreach ($jobs as $key => $job) {
            if (($job == $command) || empty($job)) {
                unset($jobs[$key]);
            }
        }
        $jobs = implode(PHP_EOL, $jobs);
        $command = ($cron_action == 'add') ? $command . PHP_EOL : '';
        file_put_contents($trade_import_dir . 'cron.txt', $jobs . PHP_EOL . $command);
        exec('crontab -r');
        exec('crontab ' . $trade_import_dir . 'cron.txt');
    }

    public function get_json() {
        $this->trade_import->get_json(); 
    }

//Debug functions
    public function show_groups() {
        $this->trade_import->show_groups();
    }

    public function show_nomenclatures() {
        $this->trade_import->show_nomenclatures();
    }

    public function show_option_characteristic() {
        $this->trade_import->show_option_characteristic();
    }

    public function show_stocks() {
        $this->trade_import->show_stocks();
    }

    public function show_filters() {
        $this->trade_import->show_filters();
    }

    public function show_prices() {
        $this->trade_import->show_prices();
    }

    public function show_warehouses() {
        $this->trade_import->show_warehouses();
    }

    public function show_services() {
        $this->trade_import->show_services();
    }

    public function add_indexes() {
        $this->trade_import->add_indexes();
    }

    public function cache_images() {
        $this->trade_import->cache_images();
    }

    public function add_one_product() {
        $this->trade_import->add_one_product();
    }

    public function add_one_separate_product() {
        $this->trade_import->add_one_separate_product();
    }

    public function clean_orders() {
        $this->trade_import->clean_orders();
    }

    public function clean_checks() {
        $this->trade_import->clean_checks();
    }

    public function clean_tables() {
        $this->trade_import->clean_tables();
    }

    public function clean_all() {
        $this->trade_import->clean_all();
    }

    public function get_customer_groups() {
        $this->trade_import->get_customer_groups();
    }

    public function get_orders() {
        $this->trade_import->get_orders();
    }

    public function get_checks() {
        $this->trade_import->get_checks();
    }

    public function get_images() {
        $this->trade_import->get_images();
    }

    public function delete_images() {
        $this->trade_import->delete_images();
    }

    public function delete_images_not_in_table() {
        $this->trade_import->delete_images_not_in_table();
    }

    public function delete_images_table() {
        $this->trade_import->delete_images_table();
    }

    private function save() {
        $json = array();
        $this->load->language('extension/module/trade_import');
        $this->load->model('setting/setting');
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            date_default_timezone_set($this->request->post['module_trade_import_time_zone']);
            $sp = $this->request->post['module_trade_import_sync_period'];
            $st = $this->request->post['module_trade_import_sync_time'];
            $sync_period = strtotime("+" . str_replace("_", " ", $sp), 0) < 86400 ? strtotime("+" . str_replace("_", " ", $sp)) : strtotime("+" . str_replace("_", " ", $sp) . $st ?: NULL);
            $json['next_schedule'] = $this->request->post['module_trade_import_sync_schedule'] = date("Y-m-d H:i:00", $sync_period);
            $cron_action = $this->request->post['module_trade_import_enable_sync'] ? 'add' : 'remove';
            $this->cron_setting($cron_action);
            $this->model_setting_setting->editSetting('module_trade_import', $this->request->post);
            $this->session->data['success'] = $json['success'] = $this->language->get('text_success');
        } else {
            $json['error_code'] = $this->error['code'];
        }
        return $json;
    }

    public function save_settings() {
        $json = $this->save();
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

//Validation
    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/trade_import')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->request->post['module_trade_import_server']) {
            $this->error['code'] = $this->language->get('error_code');
        }

        if (!$this->request->post['module_trade_import_code']) {
            $this->error['code'] = $this->language->get('error_code');
        }

        if (!$this->request->post['module_trade_import_nomenclature']) {
            $this->error['code'] = $this->language->get('error_code');
        }

        return !$this->error;
    }

//Set tables
    public function set_tables() {
        $json = array();
        $this->load->model('extension/module/trade_import');

        $json['success'] = $this->model_extension_module_trade_import->set_tables();
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

}