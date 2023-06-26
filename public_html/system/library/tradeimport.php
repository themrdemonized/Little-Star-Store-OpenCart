<?php

class TradeImport {

    private $registry;

    private $categories = array();
    private $products = array();
    private $filters = array();
    private $filters_color = array();
    private $discounts = array();
    private $stocks = array();
    private $warehouses = array();
    private $option_characteristic = array();
    private $color_codes = array();
    private $images = array();
    private $image_hashes = array();
    private $image_filesizes = array();
    private $services = array();
    private $stock_checkout;
    private $access_token;
    private $sync;
    private $sync_schedule;
    private $debug = false;

    private static $instance;
   
    /**
    * @param  object  $registry  Registry Object
    */
    public static function get_instance($registry) {

        if (is_null(static::$instance)) {
          static::$instance = new static($registry);
        }

        return static::$instance;
    }

    public function __construct($registry, $debug = false) {
        ini_set('max_execution_time', 7200);
        ini_set('memory_limit', '1024M');
        ini_set('mysql.connect_timeout', 7200);
        ini_set('default_socket_timeout', 7200);
        $this->registry = $registry;
        $this->debug = $debug;
        $this->load->model('setting/setting');
        $this->load->model('extension/module/trade_import');
        $this->load->language('extension/module/trade_import');
    }

    public function __get($name) {
        return $this->registry->get($name);
    }

    //JSON Process
    protected function open_path($path) {
        $folder_path = dirname($path);
        if (!is_dir($folder_path)) {
            mkdir($folder_path, 0777, true);
        }
        return fopen($path, 'w+');
    }

    protected function replace_between($str, $needle_start, $needle_end, $replacement) {
        $pos = strpos($str, $needle_start);
        $start = $pos === false ? 0 : $pos + strlen($needle_start);
        $pos = strpos($str, $needle_end, $start);
        $end = $pos === false ? strlen($str) : $pos;
        return substr_replace($str, $replacement, $start, $end - $start);
    }

    protected function array_flatten($array) {
        $flat = array(); // initialize return array
        $stack = array_values($array); // initialize stack
        while($stack) // process stack until done
        {
            $value = array_shift($stack);
            if (is_array($value)) // a value to further process
            {
                $stack = array_merge(array_values($value), $stack);
            }
            else // a value to take
            {
               $flat[] = $value;
            }
        }
        return $flat;
    }

//Fields
    protected function combine_fields($fields, $arr) {
        if (!isset($arr)) {
            return NULL;
        }

        $result = array();
        foreach ($arr as $k => $a) {
            foreach ($a as $key => $v) {
                if (is_array($fields[$key])) {
                    $result[$k][$fields[$key]['table']] = $this->combine_fields($fields[$key]['fields'], $v);
                } else {
                   $result[$k][$fields[$key]] = $v; 
                }
            }
        }
        return $result;
    }

    protected function get_groups($response) {
        $string = $this->replace_between($response, '{', '"groups"', '');
        $string = $this->replace_between($string, '],"deleted"', chr(NULL), '}}');
        $string = str_replace('],"deleted"', ']', $string);
        return $string;
    }

    protected function build_group_tree($arr, $parent_key = '', $path = '', $real_path = '') {
        $branch = array();
        foreach ($arr as $element) {
            $p = $path . "/" . $element['uuid'];
            $rp = $real_path . "/" . $element['name'];
            if ($element['group_uuid'] == $parent_key) {
                $element['path'] = $p;
                $element['real_path'] = $rp;
                $children = $this->build_group_tree($arr, $element['uuid'], $p, $rp);
                if ($children) {
                    $element['children'] = $children;
                }
                $branch[] = $element;
            }
        }
        return $branch;
    }   

    protected function organize_groups($fields, $arr) {
        return $this->build_group_tree($this->combine_fields($fields, $arr));
    }   

    protected function get_nomenclatures_fields($response) {
        $string = $this->replace_between($response, '{', '"nomenclatures"', '');
        $string = $this->replace_between($string, '],"deleted"', chr(NULL), '}}');
        $string = str_replace('],"deleted"', ']', $string);
        $string = rtrim(substr_replace($string, "", strrpos($string, '"values"')), ",") . "}}";
        return json_decode($string, true)['nomenclatures']['fields'];
    }

    protected function get_nomenclatures($response) {
        $strings = array();
        $string = $this->replace_between($response, '{', '"nomenclatures"', '');
        $string = $this->replace_between($string, '"deleted"', chr(NULL), '');
        $string = str_replace('],"deleted"', ']', $string);
        $string = substr_replace($string, "", 0, strrpos($string, '"values":[[') + strlen('"values":'));
        //return [$string];
        $string = substr($string, 1, -1);
        $level = 0;
        $key = 0;
        $size = 0;
        for ($pos = 0; $pos < strlen($string); $pos++) {
            $size++;
            if ($string[$pos] === '[') {
                $level++;
            } else if ($string[$pos] === ']') {
                $level--;
            }
            if (($size > 1000000) && ($level == 0)) {
                if ($string[$pos] === ",") {
                    $pos--;
                }
                $strings[] = "[" . substr($string, $key, $pos - $key + 1) . "]";
                $size = 0;
                if (isset($string[$pos + 1]) && ($string[$pos + 1] === ',')) {
                    $pos++;
                }
                $key = $pos + 1;
            }
        }
        if (empty($strings)) {
            $strings[] = "[" . $string . "]";
        } else {
            if ($key < strlen($string)) {
                $strings[] = "[" . substr($string, $key) . "]";
            }
        }
        return $strings;
    }

    protected function get_prices($response) {
        $string = $this->replace_between($response, '{', '"price_types"', '');
        $string = $this->replace_between($string, '],"deleted"', chr(NULL), '}}');
        $string = str_replace('],"deleted"', ']', $string);
        return $string;
    }

    protected function get_stocks($response) {
        $string = $this->replace_between($response, '{', '"stocks"', '');
        $string = $this->replace_between($string, '],"deleted"', chr(NULL), '}}');
        $string = str_replace('],"deleted"', ']', $string);
        return $string;
    }

    protected function get_warehouses($response) {
        $string = $this->replace_between($response, '{', '"storages"', '');
        $string = $this->replace_between($string, '],"deleted"', chr(NULL), '}}');
        $string = str_replace('],"deleted"', ']', $string);
        return $string;
    }

    protected function get_property_types($response) {
        $string = $this->replace_between($response, '{', '"property_types"', '');
        $string = $this->replace_between($string, '],"deleted"', chr(NULL), '}}');
        $string = str_replace('],"deleted"', ']', $string);
        return $string;
    }

    protected function get_timestamp($response) {
        $string = $this->replace_between($response, '{', '"timestamp"', '');
        $timestamp = json_decode($string, true);
        return $timestamp['timestamp'];
    }

//Sanitize names
    protected function mbUcFirst($str, $encoding = null) {
        if (is_null($encoding)) {
            $encoding = mb_internal_encoding();
        }

        return mb_strtoupper(mb_substr($str, 0, 1, $encoding), $encoding) .
            mb_substr($str, 1, null, $encoding);
    }

    protected function sanitize($str) {
        return str_replace('ё', 'е', $this->mbUcFirst($str, 'UTF-8'));
    }

//SEO URL
    protected function get_seo($string, $full_path_url = false) {
        $cyr = ['а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п','р','с','т','у','ф','х','ц','ч','ш','щ','ъ','ы','ь','э','ю','я','А','Б','В','Г','Д','Е','Ё','Ж','З','И','Й','К','Л','М','Н','О','П','Р','С','Т','У','Ф','Х','Ц','Ч','Ш','Щ','Ъ','Ы','Ь','Э','Ю','Я'];
        $lat = ['a','b','v','g','d','e','io','zh','z','i','j','k','l','m','n','o','p','r','s','t','u','f','h','c','ch','sh','sch','','y','','e','yu','ya','a','b','v','g','d','e','io','zh','z','i','j','k','l','m','n','o','p','r','s','t','u','f','h','c','ch','sh','sch','','y','','e','yu','ya'];
        $symb = ['_','(',')','\\',' ','.',',','<','>','"',"'",'«','»','+',"&nbsp;&nbsp;&gt;&nbsp;&nbsp;","&quot;", "*", ";", "?", "!", "@", "&", "#", "$", "%", "^", "=", "|", "~", "№", ":", "[", "]"];
        if (!$full_path_url) {
            $symb[] = '/';
        }
        $seo = strtolower(str_replace($cyr, $lat, $string));
        $seo = str_replace($symb, '-', $seo);
        $seo = preg_replace('/^\-+|\-+$|\-+(?=\-)/', '', $seo);
        return trim($seo);
    }

    protected function get_category_name($name) {
        $category_name = explode('&nbsp;&nbsp;&gt;&nbsp;&nbsp;', $name);
        return end($category_name);
    }

    protected function get_category_path($name) {
        $category_path = str_replace('&nbsp;&nbsp;&gt;&nbsp;&nbsp;', '/', $name); 
        return $category_path;
    }

    protected function get_categories() {
        $categories = array();
        foreach ($this->model_extension_module_trade_import->getCategories(array()) as $category) {
            $categories[] = array(
                'category_id'   => $category['category_id'],
                'name'          => $this->get_category_name($category['name']),
                'parent_id'     => $category['parent_id'],
                'path'          => $this->get_category_path($category['name']),
                'original_path' => $category['name'],
            );
        }
        return $categories;
    }

    protected function organize_categories($arr, $parent_key = 0) {
        $branch = array();
        foreach ($arr as $element) {
            if ($element['parent_id'] == $parent_key) {
                $children = $this->organize_categories($arr, $element['category_id']);
                if ($children) {
                    $element['children'] = $children;
                }
                $branch[] = $element;
            }
        }
        return $branch;
    }
//Images
    protected function get_time_sync_limit() {
        return 120;
    }

    protected function postpone_sync($time_added = 600) {
        if (!$this->sync_schedule) {
            $this->sync_schedule = new DateTime($this->config->get('module_trade_import_sync_schedule'));
        }
        if ($this->debug) {
            echo "postponing sync by {$time_added}\n";
        }
        $this->model_extension_module_trade_import->editSettingValue('module_trade_import', 'module_trade_import_sync_schedule', $this->sync_schedule->modify("+{$time_added}")->format("Y-m-d H:i:00"));
    }

    protected function get_images_from_folder() {
        foreach (glob(DIR_IMAGE . 'catalog/trade_import/*') as $image) {
            $info = pathinfo($image);
            $id = $info['filename'];
            if (!isset($this->images[$id])) {
                if ($this->debug) {
                    echo "getting image from folder " . $info['basename'] . "\n";
                }
                $this->images[$id]['image_id'] = $id;
                $this->images[$id]['path'] = 'catalog/trade_import/' . $info['basename'];
                $image_hash = md5_file($image);
                $this->images[$id]['hash'] = $image_hash;
                if (!isset($this->image_hashes[$image_hash])) {
                    $this->image_hashes[$image_hash] = $id;
                }
                $image_filesize = filesize($image);
                $this->images[$id]['size'] = $image_filesize;
                if (!isset($this->image_filesizes[$image_filesize])) {
                    $this->image_filesizes[$image_filesize] = $id;
                }
            }
        }
        return $this->images;
    }

    protected function get_image($data, $id, $rewrite = false, $format = "png", $debug = false) {
        $image_file = "catalog/trade_import/" . $id . ".{$format}";
        if ($this->debug) {
            echo str_pad("", 500, " ") . "\n";
        }
        if ($this->sync && $this->sync_schedule && ($this->sync_schedule->getTimestamp() - time()) < $this->get_time_sync_limit()) {
            $this->postpone_sync(str_replace("_", " ", $this->config->get('module_trade_import_sync_period')));
        }
        if (!isset($this->images[$id]) || $rewrite) {
            if ($debug) {
                echo "downloading image " . $id . "\n";
            }
            if ($rewrite) {
                @unlink(DIR_IMAGE . $image_file);
            }
            $image_server = $data['server'] . '/api/v1/attachments/' . $id;
            $ch = curl_init();
            $header = array();
            $header[] = "Authorization: Bearer " . $this->access_token;
            curl_setopt($ch, CURLOPT_URL, $image_server);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            $r = curl_exec($ch);
            if (isset(json_decode($r, true)['error'])) {
                echo "error downloading: " . json_decode($r, true)['error'] . ", retrying\n";
                $url = $this->config->get('module_trade_import_code');
                $token = $this->config->get('module_trade_import_token');
                $time = time();
                $date = date('c', $time);
                $ch = curl_init();
                $header = array();
                $data_string = json_encode(array('token' => $token));
                $header[] = "Content-Type: application/json";
                $header[] = "UUID: " . $token;
                $header[] = "Timestamp: " . $date;
                $header[] = "Authorization: " . hash("sha512", $token . $time);
                $header[] = "Content-Length: " . strlen($data_string);
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
                $response = curl_exec($ch);
                if ($response === false) {
                    if ($this->debug) {
                        echo 'Curl error: ', curl_error($ch), "\n";
                        echo $response;
                    }
                    curl_close($ch);
                    $this->model_extension_module_trade_import->add_operation(NULL, 0, $response);
                    return NULL;
                }
                if ($this->debug) {
                    echo $url, " connection successful.", "\n";
                }
                $response_decoded = json_decode($response, true);
                $this->access_token = $response_decoded['access_token'];
                curl_close($ch);
                flush();
                return $this->get_image($data, $id, true, $format);
            }

            if (curl_errno($ch)) {
                if ($debug) {
                    echo "error downloading: curl error " . curl_errno($ch) . "\n";
                    flush();
                }
                curl_close($ch);
                return NULL;
            } else {
                switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
                    case 200:  # OK
                        curl_close($ch);
                        break;
                    case 404:
                        if ($debug) {
                            echo "error downloading: http code " . $http_code . ". Marking as ignored\n";
                            flush();
                        }
                        $this->images[$id]['image_id'] = $id;
                        $this->images[$id]['path'] = NULL;
                        $this->images[$id]['hash'] = 0;
                        $this->images[$id]['size'] = 0;
                        $this->model_extension_module_trade_import->add_image($this->images[$id], $this->debug);
                        curl_close($ch);
                        return NULL;
                    default:
                        if ($debug) {
                            echo "error downloading: http code " . $http_code . "\n";
                            flush();
                        }
                        curl_close($ch);
                        return NULL;
                }
            }

            $image_hash = md5($r);
            $image_filesize = strlen($r);
            if ((isset($this->image_hashes[$image_hash]) || ($data['image_ignore_same_size'] && isset($this->image_filesizes[$image_filesize]))) && !$rewrite) {
                if ($debug && isset($this->image_hashes[$image_hash])) {
                    echo "image " . $id . " has same hash as " . $this->images[$this->image_hashes[$image_hash]]['image_id'] . ", ignoring \n";
                }
                if ($debug && $data['image_ignore_same_size'] && isset($this->image_filesizes[$image_filesize])) {
                    echo "image " . $id . " has same filesize as " . $this->images[$this->image_filesizes[$image_filesize]]['image_id'] . ", ignoring \n";
                }
                $image_file = $this->images[($data['image_ignore_same_size'] && isset($this->image_filesizes[$image_filesize])) ? $this->image_filesizes[$image_filesize] : $this->image_hashes[$image_hash]]['path'];
            } else {
                if ($debug) {
                    echo "saving image " . $id . "\n";
                }
                $this->image_hashes[$image_hash] = $id;
                $this->image_filesizes[$image_filesize] = $id;
                $fp = $this->open_path(DIR_IMAGE . $image_file);
                fwrite($fp, $r);
                fclose($fp);
            }

            $this->images[$id]['image_id'] = $id;
            $this->images[$id]['path'] = $image_file;
            $this->images[$id]['hash'] = $image_hash;
            $this->images[$id]['size'] = $image_filesize;
            $this->model_extension_module_trade_import->add_image($this->images[$id], $this->debug);
        } else {
            if ($debug) {
                if (!$this->images[$id]['path']) {
                    echo "image " . $id . " is already ignored\n";
                } else {
                    echo "image " . $this->images[$id]['path'] . " is already downloaded\n";
                }
            }
        }
        flush();
        return $this->images[$id]['path'];
    }

//Categories
    protected function set_category_template() {
        $data = array(
            'server'    => $this->config->get('module_trade_import_server'),
            'parent_category_id' => $this->config->get('module_trade_import_parent_id') !== NULL ? $this->config->get('module_trade_import_parent_id') : 0,
            'parent_category_code' => array_filter(explode(",", $this->config->get('module_trade_import_top_category'))),
            'product_image_jpeg' => $this->config->get('module_trade_import_product_image_jpeg') ? 'jpg' : 'png',
            'image_ignore_same_size' => $this->config->get('module_trade_import_image_ignore_same_size'),
            'store' => $this->model_extension_module_trade_import->get_store_name(),
            'keep_names' => $this->config->get('module_trade_import_keep_category_names'),
            'keep_meta' => $this->config->get('module_trade_import_keep_category_meta'),
            'keep_category_description' => $this->config->get('module_trade_import_keep_category_description'),
            'short_url' => $this->config->get('module_trade_import_short_url'),
            'full_path_url' => $this->config->get('module_trade_import_full_path_url'),
            'image' => NULL,
            'category_description' => array(
                1 => array(
                    'name'              => '',
                    'meta_h1'           => '',
                    'description'       => '',
                    'meta_title'        => '',
                    'meta_description'  => '',
                    'meta_keyword'      => ''
                )
            ),
            'category_store' => array(
                0 => 0
            ),
            'column'        => 1,
            'sort_order'    => 0,
            'status'        => 1,
            'noindex'       => 1,
            'category_seo_url' => array(
                0 => array(
                    1 => ''
                )
            ),
            'parent_id' => 0,
            'top' => 0,
            'code' => '',
            'path' => ''
        );
        return $data;
    }

    protected function set_category($arr, &$data) {
        $data['category_description'][1]['name'] = $arr['name'];
        $data['category_description'][1]['meta_h1'] = $arr['name'];
        $data['category_description'][1]['description'] = $arr['name'];
        $data['category_description'][1]['meta_title'] = 'Купить ' . $arr['name'] . ' в ' . $data['store'] . ' по лучшей цене';
        $data['category_description'][1]['meta_description'] = 'Покупайте ' . $arr['name'] . ' в магазине ' . $data['store'] . ' по лучшей цене';
        $data['category_description'][1]['meta_keyword'] = str_replace('/',',',$arr['path_name']) . ','. $data['store'];
        if ($data['full_path_url']) {
            $data['category_seo_url'][0][1] = $this->get_seo(substr($arr['real_path'], 1), true);
        } else {
            $data['category_seo_url'][0][1] = $data['short_url'] ? $this->get_seo($arr['name']) : $this->get_seo(substr($arr['real_path'], 1));
        }
        $data['code'] = $arr['uuid'];
        $data['path'] = substr($arr['path'], 1);
        $data['raw_path'] = substr($arr['real_path'], 1);
        $data['image'] = !empty($arr['images']) ? $this->get_image($data, $arr['images'][0], false, $data['product_image_jpeg'], $this->debug) : NULL;
        $this->categories[$arr['uuid']] = $data;
        if (isset($arr['children'])) {
            foreach ($arr['children'] as $sub_category) {
                $this->set_category($sub_category, $data);
            }
        }
    }

    protected function hide_category($arr) {
        if (isset($arr['children'])) {
            foreach ($arr['children'] as $children) {
                if ($this->hide_category($children) == 1) {
                    $not_empty = 1;
                }
            }
        }
        $data = $this->model_extension_module_trade_import->getProductsByCategoryId($arr['category_id']);
        foreach ($data as $product) {
            if ($product['status']) {
                $this->model_extension_module_trade_import->show_category($arr['category_id']);
                return 1;
            }
        }
        if (!isset($not_empty)) {
            if ($this->debug) {
                echo 'Category ', $arr['path'], ' is empty. Hiding.', "\n";
            }
            $this->model_extension_module_trade_import->hide_category($arr['category_id']);
        } else {
            $this->model_extension_module_trade_import->show_category($arr['category_id']);
            return 1;
        }
    }

    protected function hide_categories() {
        foreach ($this->organize_categories($this->get_categories()) as $category) {
            $this->hide_category($category);
        }
    }

//Products
    protected function set_price($method, $price) {
        switch ($method) {
            case 'normal': return round($price);
            case 'up': return ceil($price);
            case 'down': return floor($price);
            case 'middle': return round($price * 2, 0) / 2;
            case 'off':
            default: return $price;
        }
    }

    protected function set_product_template() {
        $price_map = array();
        $maps = array_filter(explode(";", $this->config->get('module_trade_import_price_map')));
        if (!empty($maps)) {
            foreach ($maps as $map) {
                $t = explode(':', $map);
                $price_map[$t[0]] = explode(',', $t[1]);
            }
        }

        $properties_map = array();
        $ignore_properties = array();
        $maps = array_filter(explode(";", $this->config->get('module_trade_import_properties_as_description')));
        if (!empty($maps)) {
            foreach ($maps as $map) {
                $t = explode(':', $map);
                $properties_map[mb_strtolower($t[0], 'UTF-8')] = explode('|', mb_strtolower($t[1], 'UTF-8'));
                $ignore_properties += explode('|', mb_strtolower($t[1], 'UTF-8'));
            }
        }
        $ignore_properties = array_merge(array_flip($ignore_properties), array_flip(array_filter(explode(",", mb_strtolower($this->config->get('module_trade_import_ignore_property'), 'UTF-8')))));

        $price_city = array();
        $maps = array_filter(explode(";", $this->config->get('module_trade_import_price_city')));
        if (!empty($maps)) {
            foreach ($maps as $map) {
                $t = explode(':', $map);
                $price_city[$t[0]] = explode(',', $t[1]);
            }
        }

        $default_size = array(0, 0, 0);
        if ($this->config->get('module_trade_import_default_size')) {
            $d_size = array_filter(explode("x", $this->config->get('module_trade_import_default_size')));
            foreach ($default_size as $key => $value) {
                if (isset($d_size[$key])) {
                    $default_size[$key] = $d_size[$key];
                }
            }
        }

        $data = array(
            'store'     => $this->model_extension_module_trade_import->get_store_name(),
            'parent_category_id' => $this->config->get('module_trade_import_parent_id'),
            'parent_category_code' => array_filter(explode(",", $this->config->get('module_trade_import_top_category'))),
            'price_uuid'=> $this->config->get('module_trade_import_price'),
            'price_map' => $price_map,
            'price_city' => $price_city,
            'hide_product' => $this->config->get('module_trade_import_hide_product') || $this->config->get('module_trade_import_hide_empty_product'),
            'hide_noprice_product' => $this->config->get('module_trade_import_hide_product'),
            'hide_empty_product' => $this->config->get('module_trade_import_hide_empty_product'),
            'server'    => $this->config->get('module_trade_import_server'),
            'separate'  => $this->config->get('module_trade_import_add_separate_products'),
            'round_price' => $this->config->get('module_trade_import_round_price'),
            'customer_groups' => array_column($this->model_extension_module_trade_import->getCustomerGroups(), 'customer_group_id'),
            'ignore_filter' => array_filter(explode(",", mb_strtolower($this->config->get('module_trade_import_ignore_filter'), 'UTF-8'))),
            'add_properties_to_filters' => array_filter(explode(",", mb_strtolower($this->config->get('module_trade_import_add_properties_to_filters'), 'UTF-8'))),
            'keep_names' => $this->config->get('module_trade_import_keep_product_names'),
            'keep_product_description' => $this->config->get('module_trade_import_keep_product_description'),
            'keep_meta' => $this->config->get('module_trade_import_keep_product_meta'),
            'full_path_url' => $this->config->get('module_trade_import_full_path_url'),
            'names_as_uuid' => $this->config->get('module_trade_import_names_as_uuid') ? 'name' : 'property_type_uuid',
            'color_names_as_uuid' => $this->config->get('module_trade_import_names_as_uuid') ? 'name' : 'uuid',
            'properties_as_description' => $properties_map,
            'ignore_properties' => $ignore_properties,
            'default_weight' => $this->config->get('module_trade_import_default_weight'),
            'default_size' => $this->config->get('module_trade_import_default_size'),
            'product_image_jpeg' => $this->config->get('module_trade_import_product_image_jpeg') ? 'jpg' : 'png',
            'image_ignore_same_size' => $this->config->get('module_trade_import_image_ignore_same_size'),
            'ignore_noname_characteristics' => $this->config->get('module_trade_import_ignore_noname_characteristics'),
            'delivery_uuid' => $this->config->get('module_trade_import_delivery_uuid'),
            'model'     => '',
            'sku'       => '',
            'jan'       => NULL,
            'isbn'      => NULL,
            'mpn'       => NULL,
            'upc'       => NULL,
            'location'  => NULL,
            'manufacturer_id'   => NULL,
            'points'    => NULL,
            'weight'    => $this->config->get('module_trade_import_default_weight'),
            'weight_class_id'   => 1,
            'length'    => $default_size[0],
            'width'     => $default_size[1],
            'height'    => $default_size[2],
            'length_class_id'   => 1,
            'tax_class_id'      => NULL,
            'image'     => NULL,
            'additional_image' => array(),
            'minimum'   => 1,
            'subtract'  => $this->stock_checkout,
            'stock_status_id'   => 5,
            'shipping'  => 1,
            'noindex'   => 1,
            'date_available'    => date("Y-m-d"),
            'product_category' => array(),
            'product_store' => array(
                0   => 0
            ),
            'product_image' => array(),
            'sort_order' => 0,
            'product_description' => array(
                1 => array(
                    'name'  => '',
                    'description' => '',
                    'meta_h1' => '',
                    'meta_title' => '',
                    'meta_description' => '',
                    'meta_keyword' => '',
                    'tag' => ''
                )
            ),
            'product_description_composition' => array(),
            'quantity'  => 0,
            'price'     => 0,
            'ean'       => NULL,
            'product_seo_url' => array(),
            'product_special' => array(),
            'status'    => 0,
            'product_option' => array(),
            'code' => '',
            'created_at' => NULL,
            'option_data' => array(),
        );
        return $data;
    }

    protected function set_products($arr, &$data, $category_codes, $force_add = false) {
        if ($arr['uuid'] === $data['delivery_uuid']) {
            $this->services[$arr['uuid']] = array(
                'service_uuid' => $arr['uuid'],
                'name' => $arr['name']
            );
            return;
        }

        if (isset($category_codes[$arr['group_uuid']]) || $force_add || $data['parent_category_id']) {
            $data['model'] = isset($arr['article']) ? $arr['article'] : $arr['name'];
            $data['sku'] = $arr['article'];
            $data['image'] = NULL;
            $data['additional_image'] = array();
            if (isset($arr['images'])) {
                $images = is_array($arr['images']) ? $arr['images'] : array_filter(explode(",", substr($arr['images'], 1, -1)));
                foreach ($images as $key => $image) {
                    if ($key == 0) {
                        $data['image'] = $this->get_image($data, $image, false, $data['product_image_jpeg']);
                    } else {
                        $data['additional_image'][] = $this->get_image($data, $image, false, $data['product_image_jpeg']);
                    }
                }  
            }
            $data['code'] =  $arr['uuid'];
            $data['group_code'] = $arr['group_uuid'];
            $data['measure_name'] = $arr['measure_name'];
            $data['created_at'] = $arr['created_at'];
            $data['product_category'] = isset($this->categories[$arr['group_uuid']]['path']) ? $this->categories[$arr['group_uuid']]['path'] : NULL;
            $data['product_description'][1]['name'] = $arr['name'];
            $data['product_description'][1]['description'] = $arr['description'];
            $data['product_description'][1]['meta_h1'] = $arr['name'];
            $data['product_description'][1]['meta_title'] = 'Купить ' . $arr['name'] . ' в ' . $data['store'] . ' по лучшей цене';
            $data['product_description'][1]['meta_description'] = 'Покупайте ' . $arr['name'] . ' в магазине ' . $data['store'] . ' по лучшей цене';
            $data['product_description'][1]['meta_keyword'] = $arr['name'] . (isset($arr['article']) ? ',' . $arr['article'] : NULL) . ',' . $data['store'];
            $data['product_description'][1]['tag'] = $arr['name'] . (isset($arr['article']) ? ',' . $arr['article'] : NULL);
            $data['product_description_composition'] = array();
            if (!empty($arr['composition'])) {
                $data['product_description_composition'][1]['composition'] = $arr['composition'];
            }

            $data['quantity'] = 0;
            if (isset($arr['remainders'])) {
                foreach ($arr['remainders'] as $quantity) {
                    $data['quantity'] += (int)$quantity['quantity'] > 0 ? (int)$quantity['quantity'] : 0;
                }
            }
            $data['product_special'] = NULL;
            $data['product_city'] = NULL;
            $data['price'] = 0;
            $prices = array();
            if (isset($arr['prices'])) {
                foreach ($arr['prices'] as $price) {
                    if ($data['price_uuid']) {
                        if ($price['price_type_uuid'] == $data['price_uuid']) {
                            $data['price'] = $this->set_price($data['round_price'], $price['price']);
                        }
                    } else {
                        if ($data['price'] == 0) {
                            $data['price'] = $this->set_price($data['round_price'], $price['price']);
                        }
                    }
                    if (isset($data['price_map'][$price['price_type_uuid']])) {
                        foreach ($data['price_map'][$price['price_type_uuid']] as $key => $customer) {
                            $data['product_special'][$key]['customer_group_id'] = $customer;
                            $data['product_special'][$key]['priority'] = 1;
                            $data['product_special'][$key]['price'] = $this->set_price($data['round_price'], $price['price']);
                            $data['product_special'][$key]['date_start'] = date("Y-m-d H:i:s");
                            $data['product_special'][$key]['date_end'] = date("Y-m-d H:i:s", time() + 604800);
                        }
                    }
                    if (isset($data['price_city'][$price['price_type_uuid']])) {
                        foreach ($data['price_city'][$price['price_type_uuid']] as $key => $city_id) {
                            $data['product_city'][$key]['product_option_value_id'] = 0;
                            $data['product_city'][$key]['city_id'] = $city_id;
                            $data['product_city'][$key]['price'] = $data['product_city'][$key]['price_old'] = $this->set_price($data['round_price'], $price['price']);
                        }
                    }
                }
            }
            $data['ean'] = NULL;
            if (isset($arr['barcodes'])) {
                $data['ean'] = $arr['barcodes'][0]['barcode'];
            }
            if (isset($arr['remainders'])) {
                foreach ($arr['remainders'] as $warehouse) {
                    if (isset($this->warehouses[$warehouse['storage_uuid']])) {
                        $this->warehouses[$warehouse['storage_uuid']]['products'][] = array(
                            'nomenclature_uuid' => $arr['uuid'],
                            'characteristic_uuid' => $warehouse['characteristic_uuid'],
                            'quantity' => (int)$warehouse['quantity'] > 0 ? (int)$warehouse['quantity'] : 0,
                            'name' => $arr['name']
                        );
                    }
                }
            }
            if (isset($arr['properties'])) {
                foreach ($arr['properties'] as $filter) {
                    if (!in_array(mb_strtolower($filter['name'], 'UTF-8'), $data['ignore_filter'])) {
                        $filter['name'] = $this->sanitize($filter['name']);
                        $this->filters[$filter['name']]['filter_group_name'] = $filter['name'];
                        foreach ($this->array_flatten($filter['values']) as $filter_name) {
                            if (!empty($filter_name)) {
                                if (substr($filter_name, 0, 1) === "{" && substr($filter_name, -1) === "}") {
                                    $filters = json_decode(str_replace("=>", ": ", $filter_name));
                                } else {
                                    $filters = (array) $filter_name;
                                }
                                if (isset($data['product_category'])) {
                                    foreach (explode("/", $data['product_category']) as $category) {
                                        if (isset($this->categories[$category])) {
                                            foreach ($filters as $f) {
                                                $f = $this->sanitize($f);
                                                $this->filters[$filter['name']]['filters'][$f]['categories'][$category] = $this->categories[$category]['category_description'][1]['name'];
                                            }
                                        }
                                    }
                                }
                                foreach ($filters as $f) {
                                    $f = $this->sanitize($f);
                                    $this->filters[$filter['name']]['filters'][$f]['products'][$data['code']] = $arr['name'];
                                }
                            }
                        }
                    }
                }
            }
            if ($data['full_path_url'] && isset($this->categories[$arr['group_uuid']]['path'])) {
                $data['product_seo_url'][0][1] = $this->get_seo($this->categories[$arr['group_uuid']]['raw_path'] . "/" . $arr['name'], true);
            } else {
                $data['product_seo_url'][0][1] = $this->get_seo($arr['name']);
            }
            $data['status'] = 1;
            if ($data['hide_product']) {
                if ($data['hide_noprice_product']) {
                    if ($data['price'] <= 0) {
                        $data['status'] = 0;
                        if ($this->debug) {
                            echo 'Product ', $arr['name'], ' is null price. Hiding.', "\n";
                        }
                    }
                }
                if ($data['hide_empty_product']) {
                    if ($data['quantity'] <= 0) {
                        $data['status'] = 0;
                        if ($this->debug) {
                            echo 'Product ', $arr['name'], ' is empty. Hiding.', "\n";
                        }
                    }
                }
            }
            $data['option_data'] = array();
            $data['product_option'] = array();
            if (isset($arr['characteristics'])) {
                $data['option_data'][0] = array(
                    'option_description'    => array(
                        1   => array(
                            'name' => $arr['name']
                        )
                    ),
                    'type'  => 'select',
                    'sort_order'    => 0
                );
                $data['product_option'][0]['name'] = $arr['name'];
                $data['product_option'][0]['type'] = 'select';
                $data['product_option'][0]['required'] = 1;
                foreach ($arr['characteristics'] as $key => $attr) {
                    if ($data['ignore_noname_characteristics'] && empty($attr['name'])) {
                        continue;
                    }

                    $data['option_data'][0]['option_description'][1]['name'] = $arr['name'];
                    $data['option_data'][0]['type'] = 'select';
                    $data['option_data'][0]['sort_order'] = 0;
                    
                    $data['product_option'][0]['name'] = $arr['name'];
                    $data['product_option'][0]['type'] = 'select';
                    $data['product_option'][0]['required'] = 1;
                    $data['option_data'][0]['option_value'][$attr['uuid']]['image'] = NULL;
                    if (isset($attr['attachment_ids'])) {
                        $images = array();
                        foreach ($attr['attachment_ids'] as $option_image) {
                            $images[] = $this->get_image($data, $option_image, false, $data['product_image_jpeg']);
                            }
                        $data['option_data'][0]['option_value'][$attr['uuid']]['image'] = json_encode($images);
                    }
                    $data['option_data'][0]['option_value'][$attr['uuid']]['sort_order'] = $key;
                    $data['option_data'][0]['option_value'][$attr['uuid']]['code'] = $attr['uuid'];
                    $data['option_data'][0]['option_value'][$attr['uuid']]['option_value_description'][1]['name'] = $attr['name'];
                    if (isset($attr['properties'])) {
                        $property_keys = array();
                        
                        foreach ($attr['properties'] as $key => $property) {
                            $property_keys[$property[$data['names_as_uuid']]] = $key;
                        }

                        foreach ($attr['properties'] as $key => $property) {
                            if (!isset($data['ignore_properties'][mb_strtolower($property[$data['names_as_uuid']], 'UTF-8')])) {
                                $property['name'] = $this->sanitize($property['name']);
                                $this->option_characteristic[$property[$data['names_as_uuid']]]['code'] = $property['property_type_uuid'];
                                $this->option_characteristic[$property[$data['names_as_uuid']]]['type'] = isset($this->color_codes[$property[$data['names_as_uuid']]]) ? 'colors' : 'select';
                                if (!in_array(mb_strtolower($property['name'], 'UTF-8'), $data['ignore_filter']) && ($this->option_characteristic[$property[$data['names_as_uuid']]]['type'] == 'colors' || in_array(mb_strtolower($property['name'], 'UTF-8'), $data['add_properties_to_filters']))) {
                                    if ($this->option_characteristic[$property[$data['names_as_uuid']]]['type'] == 'colors') {
                                        $this->filters_color[$property['name']]['filter_group_name'] = $property['name'];
                                        foreach ($this->array_flatten($property['values']) as $p) {
                                            if (!empty($p)) {
                                                $p = $this->sanitize($p);
                                                $filters = isset($this->color_codes[$property[$data['names_as_uuid']]][$p]) ? array($p => $this->color_codes[$property[$data['names_as_uuid']]][$p]) : array($p => "");
                                                $filters = json_encode($filters, JSON_UNESCAPED_UNICODE);
                                                if (isset($data['product_category'])) {
                                                    foreach (explode("/", $data['product_category']) as $category) {
                                                        if (isset($this->categories[$category])) {
                                                            $this->filters_color[$property['name']]['filters'][$filters]['categories'][$category] = $this->categories[$category]['category_description'][1]['name'];
                                                        }
                                                    }
                                                }
                                                $this->filters_color[$property['name']]['filters'][$filters]['products'][$data['code']] = $arr['name'];
                                            }
                                        }
                                    } else {
                                        $this->filters[$property['name']]['filter_group_name'] = $property['name'];
                                        foreach ($this->array_flatten($property['values']) as $p) {
                                            if (!empty($p)) {
                                                $filters = $this->sanitize($p);
                                                if (isset($data['product_category'])) {
                                                    foreach (explode("/", $data['product_category']) as $category) {
                                                        if (isset($this->categories[$category])) {
                                                            $this->filters[$property['name']]['filters'][$filters]['categories'][$category] = $this->categories[$category]['category_description'][1]['name'];
                                                        }
                                                    }
                                                }
                                                $this->filters[$property['name']]['filters'][$filters]['products'][$data['code']] = $arr['name'];
                                            }
                                        }
                                    }
                                }
                                $this->option_characteristic[$property[$data['names_as_uuid']]]['name'] = $property['name'];
                                if (isset($property['values'])) {
                                    $this->option_characteristic[$property[$data['names_as_uuid']]]['option_value'][$arr['uuid']][$attr['uuid']] = array(
                                        'properties' => "",
                                        'descriptions' => ""
                                    );
                                    foreach ($this->array_flatten($property['values']) as $p) {
                                        if (!empty($p)) {
                                            $p = $this->sanitize($p);
                                            if ($this->option_characteristic[$property[$data['names_as_uuid']]]['type'] == 'colors') {
                                                $this->option_characteristic[$property[$data['names_as_uuid']]]['option_value'][$arr['uuid']][$attr['uuid']]['properties'] = isset($this->color_codes[$property[$data['names_as_uuid']]][$p]) ? array($p => $this->color_codes[$property[$data['names_as_uuid']]][$p]) : array($p => "");
                                            } else {
                                                $this->option_characteristic[$property[$data['names_as_uuid']]]['option_value'][$arr['uuid']][$attr['uuid']]['properties'] = $p;
                                            }
                                            if (isset($data['properties_as_description'][$property[$data['names_as_uuid']]])) {
                                                $descriptions = array();
                                                foreach ($data['properties_as_description'][$property[$data['names_as_uuid']]] as $description) {
                                                    if (isset($property_keys[$description])) {
                                                        if ($this->debug) {
                                                            echo "Adding property description {$description} for property {$property[$data['names_as_uuid']]}\n";
                                                        }
                                                        $descriptions[$attr['properties'][$property_keys[$description]]['name']] = $this->array_flatten($attr['properties'][$property_keys[$description]]['values'])[0];
                                                    }
                                                }
                                                $this->option_characteristic[$property[$data['names_as_uuid']]]['option_value'][$arr['uuid']][$attr['uuid']]['description'] = $descriptions;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    if ($data['separate']) {
                        $data['option_data'][0]['option_value'][$attr['uuid']]['name'] = $arr['name'] . " " . $attr['name'];
                        $data['option_data'][0]['option_value'][$attr['uuid']]['quantity'] = 0;
                        $data['option_data'][0]['option_value'][$attr['uuid']]['price'] = $data['price'];
                        $data['option_data'][0]['option_value'][$attr['uuid']]['discount'] = 0.00;
                        $data['option_data'][0]['option_value'][$attr['uuid']]['product_description'][1]['name'] = $arr['name'] . " " . $attr['name'];
                        $data['option_data'][0]['option_value'][$attr['uuid']]['product_description'][1]['description'] = $arr['description'];
                        $data['option_data'][0]['option_value'][$attr['uuid']]['product_description'][1]['meta_h1'] = $arr['name'] . " " . $attr['name'];
                        $data['option_data'][0]['option_value'][$attr['uuid']]['product_description'][1]['meta_title'] = 'Купить ' . $arr['name'] . " " . $attr['name'] . ' в ' . $data['store'] . ' по лучшей цене';
                        $data['option_data'][0]['option_value'][$attr['uuid']]['product_description'][1]['meta_description'] = 'Покупайте ' . $arr['name'] . " " . $attr['name'] . ' в магазине ' . $data['store'] . ' по лучшей цене';
                        $data['option_data'][0]['option_value'][$attr['uuid']]['product_description'][1]['meta_keyword'] = $arr['name'] . " " . $attr['name'] . "," . $arr['name'] . (isset($arr['article']) ? ',' . $arr['article'] : NULL) . ',' . $data['store'];
                        $data['option_data'][0]['option_value'][$attr['uuid']]['product_description'][1]['tag'] = $arr['name'] . " " . $attr['name'] . "," . $arr['name'] . (isset($arr['article']) ? ',' . $arr['article'] : NULL);
                        $data['option_data'][0]['option_value'][$attr['uuid']]['product_seo_url'][0][1] = $this->get_seo($arr['name'] . " " . $attr['name']);
                        if (isset($arr['properties'])) {
                            foreach ($arr['properties'] as $filter) {
                                if (!in_array($filter['name'], $data['ignore_filter'])) {
                                    foreach ($this->array_flatten($filter['values']) as $filter_name) {
                                        if (!empty($filter_name)) {
                                            if (substr($filter_name, 0, 1) === "{" && substr($filter_name, -1) === "}") {
                                                $filters = json_decode(str_replace("=>", ": ", $filter_name));
                                            } else {
                                                $filters = (array) $filter_name;
                                            }
                                            foreach ($filters as $f) {
                                                $f = $this->sanitize($f);
                                                $this->filters[$filter['name']]['filters'][$f]['products'][$attr['uuid']] = $arr['name'] . " " . $attr['name'];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $data['product_option'][0]['product_option_value'][$attr['uuid']]['code'] = $attr['uuid'];
                    $data['product_option'][0]['product_option_value'][$attr['uuid']]['subtract'] = $this->stock_checkout;
                    $data['product_option'][0]['product_option_value'][$attr['uuid']]['price_prefix'] = '=';
                    $data['product_option'][0]['product_option_value'][$attr['uuid']]['points_prefix'] = '+';
                    $data['product_option'][0]['product_option_value'][$attr['uuid']]['weight_prefix'] = '+';
                    $data['product_option'][0]['product_option_value'][$attr['uuid']]['points'] = NULL;
                    $data['product_option'][0]['product_option_value'][$attr['uuid']]['weight'] = NULL;
                    $data['product_option'][0]['product_option_value'][$attr['uuid']]['quantity'] = 0;
                    $data['product_option'][0]['product_option_value'][$attr['uuid']]['price'] = $data['product_option'][0]['product_option_value'][$attr['uuid']]['price_old'] = $data['price'];
                    $data['product_option'][0]['product_option_value'][$attr['uuid']]['discount'] = 0.00;
                    if (isset($arr['remainders'])) {
                        foreach ($arr['remainders'] as $quantity) {
                            if ($quantity['characteristic_uuid'] == $attr['uuid']) {
                                if ($data['separate']) {
                                    $data['option_data'][0]['option_value'][$attr['uuid']]['quantity'] += (int)$quantity['quantity'] > 0 ? (int)$quantity['quantity'] : 0;
                                }
                                $data['product_option'][0]['product_option_value'][$attr['uuid']]['quantity'] += (int)$quantity['quantity'] > 0 ? (int)$quantity['quantity'] : 0;
                            }
                        }
                    }
                    if (isset($arr['prices'])) {
                        foreach ($arr['prices'] as $price) {
                            if ($price['characteristic_uuid'] == $attr['uuid']) {
                                if ($data['price_uuid']) {
                                    if ($price['price_type_uuid'] == $data['price_uuid']) {
                                        $data['option_data'][0]['option_value'][$attr['uuid']]['price'] = $data['separate'] ? $this->set_price($data['round_price'], $price['price']) : NULL;
                                        $data['product_option'][0]['product_option_value'][$attr['uuid']]['price'] = $data['product_option'][0]['product_option_value'][$attr['uuid']]['price_old'] = $this->set_price($data['round_price'], $price['price']);
                                        if ($data['product_option'][0]['product_option_value'][$attr['uuid']]['quantity'] > 0) {
                                            $data['price'] = $this->set_price($data['round_price'], $price['price']);
                                            $prices[] = $this->set_price($data['round_price'], $price['price']);
                                        }
                                    }
                                    if (isset($data['price_map'][$price['price_type_uuid']]) && $data['separate']) {
                                        foreach ($data['price_map'][$price['price_type_uuid']] as $key => $customer) {
                                            $data['option_data'][0]['option_value'][$attr['uuid']]['product_special'][$key]['customer_group_id'] = $customer;
                                            $data['option_data'][0]['option_value'][$attr['uuid']]['product_special'][$key]['priority'] = 1;
                                            $data['option_data'][0]['option_value'][$attr['uuid']]['product_special'][$key]['price'] = $this->set_price($data['round_price'], $price['price']);
                                            $data['option_data'][0]['option_value'][$attr['uuid']]['product_special'][$key]['date_start'] = date("Y-m-d H:i:s");
                                            $data['option_data'][0]['option_value'][$attr['uuid']]['product_special'][$key]['date_end'] = date("Y-m-d H:i:s", time() + 604800);
                                        }
                                    }
                                    if (isset($data['price_city'][$price['price_type_uuid']])) {
                                        foreach ($data['price_city'][$price['price_type_uuid']] as $key => $city_id) {
                                            $data['option_data'][0]['option_value'][$attr['uuid']]['product_city'][$key]['city_id'] = $city_id;
                                            $data['option_data'][0]['option_value'][$attr['uuid']]['product_city'][$key]['price'] = $data['option_data'][0]['option_value'][$attr['uuid']]['product_city'][$key]['price_old'] = $this->set_price($data['round_price'], $price['price']);
                                            $data['product_option'][0]['product_option_value'][$attr['uuid']]['product_city'][$key]['city_id'] = $city_id;
                                            $data['product_option'][0]['product_option_value'][$attr['uuid']]['product_city'][$key]['price'] = $data['product_option'][0]['product_option_value'][$attr['uuid']]['product_city'][$key]['price_old'] = $this->set_price($data['round_price'], $price['price']);
                                        }
                                    }
                                } else {
                                    $data['option_data'][0]['option_value'][$attr['uuid']]['price'] = $data['separate'] ? $this->set_price($data['round_price'], $price['price']) : NULL;
                                    $data['product_option'][0]['product_option_value'][$attr['uuid']]['price'] = $data['product_option'][0]['product_option_value'][$attr['uuid']]['price_old'] = $this->set_price($data['round_price'], $price['price']);
                                    if ($data['product_option'][0]['product_option_value'][$attr['uuid']]['quantity'] > 0) {
                                        $data['price'] = $this->set_price($data['round_price'], $price['price']);
                                        $prices[] = $this->set_price($data['round_price'], $price['price']);
                                    }
                                    // if (isset($data['price_map'][$price['price_type_uuid']]) && $data['separate']) {
                                    //     foreach ($data['price_map'][$price['price_type_uuid']] as $key => $customer) {
                                    //         $data['option_data'][0]['option_value'][$attr['uuid']]['product_special'][$key]['customer_group_id'] = $customer;
                                    //         $data['option_data'][0]['option_value'][$attr['uuid']]['product_special'][$key]['priority'] = 1;
                                    //         $data['option_data'][0]['option_value'][$attr['uuid']]['product_special'][$key]['price'] = $this->set_price($data['round_price'], $price['price']);
                                    //         $data['option_data'][0]['option_value'][$attr['uuid']]['product_special'][$key]['date_start'] = date("Y-m-d H:i:s");
                                    //         $data['option_data'][0]['option_value'][$attr['uuid']]['product_special'][$key]['date_end'] = date("Y-m-d H:i:s", time() + 604800);
                                    //     }
                                    // }
                                    break;
                                }
                            }
                        }
                    }
                    $data['option_data'][0]['option_value'][$attr['uuid']]['status'] = 1;
                    if ($data['separate']) {
                        if ($data['hide_product']) {
                            if ($data['hide_noprice_product']) {
                                if ($data['option_data'][0]['option_value'][$attr['uuid']]['price'] <= 0) {
                                    $data['option_data'][0]['option_value'][$attr['uuid']]['status'] = 0;
                                    if ($this->debug) {
                                        echo 'Product ', $data['option_data'][0]['option_value'][$attr['uuid']]['name'], ' is null price. Hiding.', "\n";
                                    }
                                }
                            }
                            if ($data['hide_empty_product']) {
                                if ($data['option_data'][0]['option_value'][$attr['uuid']]['quantity'] <= 0) {
                                    $data['option_data'][0]['option_value'][$attr['uuid']]['status'] = 0;
                                    if ($this->debug) {
                                        echo 'Product ', $data['option_data'][0]['option_value'][$attr['uuid']]['name'], ' is empty. Hiding.', "\n";
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $data['price'] = !empty($prices) ? min($prices) : $data['price'];
            if (isset($this->discounts[$arr['uuid']])) {
                foreach ($this->discounts[$arr['uuid']] as $key => $special) {
                    foreach ($data['customer_groups'] as $group) {
                        $data['product_special'][] = array(
                            'customer_group_id' => $group,
                            'priority' => 1,
                            'price' => ($data['product_option'][0]['product_option_value'][$special['characteristic_uuid']]['quantity'] > 0 || count(array_filter($data['product_option'][0]['product_option_value'], function($a) {
                                    return $a['quantity'] > 0;
                            })) < 1) ? $this->set_price($data['round_price'], $special['special']) : $data['price'],
                            'discount' => $special['discount'],
                            'date_start' =>  $special['date_start'],
                            'date_end' => $special['date_end']
                        );
                    }
                    if (isset($data['product_city'])) {
                        foreach ($data['product_city'] as $key => $city) {
                            $data['product_city'][$key]['price'] = $this->set_price($data['round_price'], $special['special']);
                        }
                    }
                    if (!empty($special['characteristic_uuid']) && isset($data['product_option'][0]['product_option_value'][$special['characteristic_uuid']]['price'])) {
                        if ($this->debug) {
                            echo 'Adding stock for ' . $special['characteristic_uuid'] . "\n";
                        }
                        $data['option_data'][0]['option_value'][$special['characteristic_uuid']]['price'] = $data['separate'] ? $this->set_price($data['round_price'], $special['special']) : NULL;
                        $data['option_data'][0]['option_value'][$special['characteristic_uuid']]['discount'] = $data['separate'] ? $special['discount'] : NULL;
                        $data['product_option'][0]['product_option_value'][$special['characteristic_uuid']]['price'] = $this->set_price($data['round_price'], $special['special']);
                        $data['product_option'][0]['product_option_value'][$special['characteristic_uuid']]['discount'] = $special['discount'];
                        if (isset($data['option_data'][0]['option_value'][$special['characteristic_uuid']]['product_city'])) {
                            foreach ($data['option_data'][0]['option_value'][$special['characteristic_uuid']]['product_city'] as $key => $city) {
                                $data['option_data'][0]['option_value'][$special['characteristic_uuid']]['product_city'][$key]['price'] = $data['separate'] ? $this->set_price($data['round_price'], $special['special']) : NULL;
                            }
                        }
                        if (isset($data['product_option'][0]['product_option_value'][$special['characteristic_uuid']]['product_city'])) {
                            foreach ($data['product_option'][0]['product_option_value'][$special['characteristic_uuid']]['product_city'] as $key => $city) {
                                $data['product_option'][0]['product_option_value'][$special['characteristic_uuid']]['product_city'][$key]['price'] = $data['separate'] ? $this->set_price($data['round_price'], $special['special']) : NULL;
                            }
                        }
                    }
                }
            }
            $this->products[$arr['uuid']] = $data;
        }
    }

//Filters
    protected function set_filters($arr) {
        if (isset($arr['properties'])) {
            foreach ($arr['properties'] as $filter) {
                $this->filters[$filter['name']]['filter_group_name'] = $filter['name'];
                foreach ($this->array_flatten($filter['values']) as $filter_name) {
                    if (!empty($filter_name)) {
                        $filter_name = $this->sanitize($filter_name);
                        if (isset($this->categories[$arr['group_uuid']]['path'])) {
                            foreach (explode("/", $this->categories[$arr['group_uuid']]['path']) as $category) {
                                $this->filters[$filter['name']]['filters'][$filter_name]['categories'][$category] = $this->categories[$category]['category_description'][1]['name'];
                            }
                        }
                        $this->filters[$filter['name']]['filters'][$filter_name]['products'][$arr['uuid']] = $arr['name'];
                    }
                }
            }
        }
    }

//Color codes
    protected function set_color_codes($arr) {
        $data['names_as_uuid'] = $this->config->get('module_trade_import_names_as_uuid') ? 'name' : 'uuid';
        if (!empty($arr)) {
            foreach ($arr as $property_type) {
                if (isset($property_type['fields'])) {
                    if ($property_type['fields']['type'] == 'colors') {
                        foreach ($property_type['fields']['colors'] as $color_key => $color) {
                            $this->color_codes[$property_type[$data['names_as_uuid']]][$this->sanitize($color_key)] = $color;
                        }
                    }
                }
            }
        }
    }

//Characteristic Properties
    protected function set_option_characteristic($arr) {
        $data['names_as_uuid'] = $this->config->get('module_trade_import_names_as_uuid') ? 'name' : 'property_type_uuid';
        if (isset($arr['characteristics'])) {
            foreach ($arr['characteristics'] as $key => $attr) {
                if (isset($attr['properties'])) {
                    foreach ($attr['properties'] as $property) {
                        $this->option_characteristic[$property[$data['names_as_uuid']]]['code'] = $property['property_type_uuid'];
                        $this->option_characteristic[$property[$data['names_as_uuid']]]['type'] = isset($this->color_codes[$property[$data['names_as_uuid']]]) ? 'colors' : 'select';
                        $this->option_characteristic[$property[$data['names_as_uuid']]]['name'] = $property['name'];
                        if (isset($property['values'])) {
                            $this->option_characteristic[$property[$data['names_as_uuid']]]['option_value'][$arr['uuid']][$attr['uuid']] = array();
                            foreach ($this->array_flatten($property['values']) as $p) {
                                $p = $this->sanitize($p);
                                if ($this->option_characteristic[$property[$data['names_as_uuid']]]['type'] == 'colors') {
                                    $this->option_characteristic[$property[$data['names_as_uuid']]]['option_value'][$arr['uuid']][$attr['uuid']][] = isset($this->color_codes[$property[$data['names_as_uuid']]][$p]) ? array($p => $this->color_codes[$property[$data['names_as_uuid']]][$p]) : array($p => "");
                                } else {
                                    $this->option_characteristic[$property[$data['names_as_uuid']]]['option_value'][$arr['uuid']][$attr['uuid']][] = $p;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

//Discounts
    protected function set_discounts($arr) {
        if (!empty($arr)) {
            foreach ($arr as $stock) {
                if (isset($stock['stock_goods']) || !empty($stock['group_uuids']) && (empty($stock['location']) || $stock['location'] == "ONLINE" || $stock['location'] == "EVERYWHERE")) {
                    $characteristics_codes = $this->model_extension_module_trade_import->get_products_characteristics_by_categories_codes($stock['group_uuids']);
                    if (isset($stock['stock_goods'])) {
                        foreach ($stock['stock_goods'] as $product) {
                            if ($product['discount'] > 0) {
                                $this->discounts[$product['nomenclature_uuid']][] = array(
                                    'characteristic_uuid' => $product['characteristic_uuid'],
                                    'price' => $product['price'],
                                    'discount' => $product['discount'],
                                    'special' => $product['total'],
                                    'date_start' => $stock['start_at'],
                                    'date_end' => $stock['end_at']
                                );
                            }
                        }
                    }
                    foreach ($characteristics_codes as $nomenclature_uuid => $nomenclature) {
                        foreach ($nomenclature as $characteristic) {
                            $price = $characteristic['characteristic_price'] ?: $characteristic['price'];
                            $this->discounts[$nomenclature_uuid][] = array(
                                'characteristic_uuid' => $characteristic['characteristic_uuid'],
                                'price' => $price,
                                'discount' => $stock['discount'],
                                'special' => (double) $price * (100 - $stock['discount']) * 0.01,
                                'date_start' => $stock['start_at'],
                                'date_end' => $stock['end_at']
                            );
                        }
                    }
                }
            }
        }
    }

//Stocks
    protected function set_stocks_template() {
        $data = array(
            'server'    => $this->config->get('module_trade_import_server'),
            'store' => $this->model_extension_module_trade_import->get_store_name(),
            'banner_id' => array_filter(explode(",", $this->config->get('module_trade_import_banner_id'))),
            'keep_names' => $this->config->get('module_trade_import_keep_names'),
            'keep_meta' => $this->config->get('module_trade_import_keep_meta'),
            'banner_image_jpeg' => $this->config->get('module_trade_import_banner_image_jpeg') ? "jpg" : "png",
            'image_ignore_same_size' => $this->config->get('module_trade_import_image_ignore_same_size'),
        );
        return $data;
    }

    protected function set_stocks($arr, $data) {
        if (!empty($arr)) {
            foreach ($arr as $stock) {
                if (isset($stock['stock_goods']) || !empty($stock['group_uuids']) && (empty($stock['location']) || $stock['location'] == "ONLINE" || $stock['location'] == "EVERYWHERE")) {
                    $this->stocks[$stock['uuid']] = array(
                        'stocks_uuid' => $stock['uuid'],
                        'start_at' => $stock['start_at'],
                        'end_at' => $stock['end_at'],
                        'discount' => $stock['discount'],
                        'image' => !isset($stock['images']) ? '' : (is_array($stock['images']) ? $this->get_image($data, $stock['images'][0], false, $data['banner_image_jpeg']) : $this->get_image($data, array_filter(explode(",", substr($stock['images'], 1, -1)))[0], false, $data['banner_image_jpeg'])),
                        'sort_order' => 0,
                        'group_uuids' => $stock['group_uuids'],
                        'name' => $stock['name'],
                        'description' => $stock['description'],
                        'requirements' => '',
                        'meta_title' => $stock['name'],
                        'meta_description' => $stock['description'],
                        'meta_keyword' => $stock['name'] . "," . $data['store'],
                        'stocks_seo_url' => $this->get_seo($stock['name']),
                        'banner_id' => $data['banner_id'],
                        'keep_names' => $data['keep_names'],
                        'keep_meta' => $data['keep_meta'],
                        'products' => array()
                    );
                    if (isset($stock['stock_goods'])) {
                        foreach ($stock['stock_goods'] as $product) {
                            $this->stocks[$stock['uuid']]['products'][$product['nomenclature_uuid']] = array(
                                'nomenclature_uuid' => $product['nomenclature_uuid']
                            );
                        }
                    }
                    foreach ($this->model_extension_module_trade_import->get_products_by_categories_codes($stock['group_uuids']) as $uuid => $category) {
                        if (!isset($this->stocks[$stock['uuid']]['products'][$uuid])) {
                            $this->stocks[$stock['uuid']]['products'][$uuid] = array(
                                'nomenclature_uuid' => $uuid
                            );
                        }
                    }
                }
            }
        }
    }

//Warehouses
    protected function set_warehouses($arr) {
        if (!empty($arr)) {
            foreach ($arr as $wd) {
                $working_hours = $wd['working_hours'];
                foreach ($working_hours as $k => $wh) {
                    $time = date_create($wh['starting_time'], timezone_open('Europe/London'));
                    date_timezone_set($time, timezone_open($this->config->get('module_trade_import_time_zone')));
                    $working_hours[$k]['starting_time'] = date_format($time, 'H:i:s');

                    $time = date_create($wh['ending_time'], timezone_open('Europe/London'));
                    date_timezone_set($time, timezone_open($this->config->get('module_trade_import_time_zone')));
                    $working_hours[$k]['ending_time'] = date_format($time, 'H:i:s');
                }
                $this->warehouses[$wd['uuid']] = array(
                    'storage_uuid' => $wd['uuid'],
                    'name' => $wd['name'],
                    'address' => $wd['address'],
                    'working_hours' => json_encode($working_hours, JSON_UNESCAPED_UNICODE)
                );
            }
        }
    }

//Connect to Trade
    protected function connect() {   
        if ($this->config->get('module_trade_import_local_json')) {
            $response = file_get_contents('records.json');
            if ($response === false) {
                if ($this->debug) {
                    echo 'Unable to open file.';
                }
                $this->model_extension_module_trade_import->add_operation(NULL, 0, $response);
                return 0;
            } else {
                if ($this->debug) {
                    echo 'File opened.', "\n";
                }
            }
        } else {
            if (!$this->config->get('module_trade_import_enable_old_api')) {
                $url = $this->config->get('module_trade_import_code');
                $nomenclature_url = $this->config->get('module_trade_import_nomenclature');
                $token = $this->config->get('module_trade_import_token');
                $time = time();
                $date = date('c', $time);
                $ch = curl_init();
                $header = array();
                $data_string = json_encode(array('token' => $token));
                $header[] = "Content-Type: application/json";
                $header[] = "UUID: " . $token;
                $header[] = "Timestamp: " . $date;
                $header[] = "Authorization: " . hash("sha512", $token . $time);
                $header[] = "Content-Length: " . strlen($data_string);
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
                $response = curl_exec($ch);
                if ($response === false) {
                    if ($this->debug) {
                        echo 'Curl error: ', curl_error($ch), "\n";
                        echo $response;
                    }
                    curl_close($ch);
                    $this->model_extension_module_trade_import->add_operation(NULL, 0, $response);
                    return 0;
                } else {
                    if ($this->debug) {
                        echo $url, " connection successful.", "\n";
                    }
                    $response_decoded = json_decode($response, true);
                    $this->access_token = $response_decoded['access_token'];
                    curl_close($ch);
                    $ch = curl_init();
                    $header = array();
                    $header[] = "Content-Type: application/json";
                    $header[] = "Authorization: Bearer " . $this->access_token;
                    curl_setopt($ch, CURLOPT_URL, $nomenclature_url);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    $response = curl_exec($ch);
                    curl_close($ch);
                }
            } else {
                $url = $this->config->get('module_trade_import_old_api_address');
                $token = $this->config->get('module_trade_import_old_api_token');
                $ch = curl_init();
                $header = array();
                $header[] = "Content-Type: application/json";
                $header[] = "Authorization: " . $token;
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                $response = curl_exec($ch);
                if ($response === false) {
                    if ($this->debug) {
                        echo 'Curl error: ', curl_error($ch), "\n";
                        echo $response;
                    }
                    curl_close($ch);
                    $this->model_extension_module_trade_import->add_operation(NULL, 0, $response);
                    return 0;
                } else {
                    if ($this->debug) {
                        echo $url, " connection successful. Getting file.", "\n";
                    }
                }
                curl_close($ch);
            }
        }
        return $response;
    }

//Main function. URL for check: http://sys.sm27.ru/easykkm/api/v1/records.json  
    public function get_json() {
        date_default_timezone_set($this->config->get('module_trade_import_time_zone'));
        $sp = $this->config->get('module_trade_import_sync_period');
        $st = $this->config->get('module_trade_import_sync_time');
        $sync_period = strtotime("+" . str_replace("_", " ", $sp), 0) < 86400 ? strtotime("+" . str_replace("_", " ", $sp)) : strtotime("+" . str_replace("_", " ", $sp) . $st ?: NULL);
        $this->model_extension_module_trade_import->editSettingValue('module_trade_import', 'module_trade_import_sync_schedule', date("Y-m-d H:i:00", $sync_period));

        $response = $this->connect();
        if (!$response) {
            $this->model_extension_module_trade_import->add_operation(NULL, 0, $response);
            if ($this->debug) {
                echo 'Connection error.', "\n";
            }
            return 0;
        }

        if ($this->config->get('module_trade_import_save_json')) {
            file_put_contents('records.json', $response);
        }

        $groups_unsorted = json_decode($this->get_groups($response), true);
        if (!$groups_unsorted) {
            if ($this->debug) {
                echo 'Invalid JSON, unable to get groups.', "\n";
                echo $response;
            }
            $this->model_extension_module_trade_import->add_operation(NULL, 0, $response);
            return 0;
        }

        $nomenclatures = $this->get_nomenclatures($response);
        $nomenclatures_fields = $this->get_nomenclatures_fields($response);
        if (!json_decode($nomenclatures[0])) {
            if ($this->debug) {
                echo 'Invalid JSON, unable to get nomenclatures.', "\n";
                echo $response;
            }
            $this->model_extension_module_trade_import->add_operation(NULL, 0, $response);
            return 0;
        }

        $groups = $this->organize_groups($groups_unsorted['groups']['fields'], $groups_unsorted['groups']['values']);
        $timestamp = $this->get_timestamp($response);

        $stocks = json_decode($this->get_stocks($response), true);
        if ($stocks) {
            $stocks = $this->combine_fields($stocks['stocks']['fields'], $stocks['stocks']['values']);
            $this->set_discounts($stocks);
            $this->set_stocks($stocks, $this->set_stocks_template());
        }

        $warehouses = json_decode($this->get_warehouses($response), true);
        if ($warehouses) {
            $this->set_warehouses($this->combine_fields($warehouses['storages']['fields'], $warehouses['storages']['values']));
        }

        $property_types = json_decode($this->get_property_types($response), true);
        if ($property_types) {
            $property_types = $this->combine_fields($property_types['property_types']['fields'], $property_types['property_types']['values']);
            foreach ($property_types as $key => $value) {
               $property_types[$key]['fields'] = json_decode(str_replace("trade_import_replace", "\\\"", preg_replace('/[\\\\]+/', '', str_replace("\\\\\\\"", "trade_import_replace", substr($value['fields'], 2, -2)))), true);
            }
            $this->set_color_codes($property_types);
        }

        

        if ($this->debug) {
            echo memory_get_usage(true), ' bytes.', "\n";
        }

        $this->get_images($response, $nomenclatures, $nomenclatures_fields, $groups_unsorted, $groups);
        unset($response);

        $this->db->autocommit(false);
        try {
            $category_template = $this->set_category_template();
            $top_group_uuid = array_filter(explode(",", $this->config->get('module_trade_import_top_category')));
            $ignore_category = array_flip(array_filter(explode(",", $this->config->get('module_trade_import_ignore_category'))));
            $this->stock_checkout = !$this->config->get('config_stock_checkout');
            if ($this->config->get('module_trade_import_add_category')) {
                if (empty($top_group_uuid)) {
                    foreach ($groups as $group) {
                        if (!isset($ignore_category[$group['uuid']])) {
                            $this->set_category($group, $category_template);
                        }
                    }
                } else {
                    foreach ($groups as $group) {
                        if (in_array($group['uuid'], $top_group_uuid)) {
                            foreach ($group['children'] as $g) {
                                if (!isset($ignore_category[$g['uuid']])) {
                                    $this->set_category($g, $category_template);
                                }
                            }
                        }
                    }
                }
                $this->model_extension_module_trade_import->add_multiple_categories($this->categories, $this->debug);
            }

            $category_codes = $this->model_extension_module_trade_import->get_category_codes();
            $product_codes = array();
            $product_template = $this->set_product_template();
            if ($this->config->get('module_trade_import_add_product')) {
                foreach ($nomenclatures as $nomenclature) {
                    $x = $this->combine_fields($nomenclatures_fields, json_decode($nomenclature));
                    foreach ($x as $n) {
                        $this->set_products($n, $product_template, $category_codes);
                    }
                    $x = NULL;
                    if ($this->debug) {
                        echo memory_get_usage(true), ' bytes.', "\n";
                    }
                    if (!empty($this->products)) {
                        $this->model_extension_module_trade_import->add_multiple_products($this->products, $this->config->get('module_trade_import_add_separate_products'), $this->debug);
                    }
                    $product_codes += array_flip(array_keys($this->products));
                    $this->products = NULL;
                    $this->products = array();
                    gc_collect_cycles();
                }
                if ($this->debug) {
                    echo memory_get_usage(true), ' bytes.', "\n";
                }
                $this->model_extension_module_trade_import->add_multiple_filters($this->filters, $this->config->get('module_trade_import_parent_id'), $this->debug);
                $this->model_extension_module_trade_import->add_multiple_filters_color($this->filters_color, $this->config->get('module_trade_import_parent_id'), $this->debug);
                //OCFilter Integration
                if ($this->config->get('module_trade_import_ocfilter')) {
                    $this->model_extension_module_trade_import->OCFilterCopyFilters(array(
                        'copy_type' => 'checkbox',
                        'copy_status' => 1,
                        'copy_attribute' => 0,
                        'attribute_separator' => '',
                        'copy_filter' => 1,
                        'copy_option' => 0,
                        'copy_truncate' => 1,
                        'copy_category' => 1
                    ));
                    if (!empty($this->filters_color)) {
                        $this->model_extension_module_trade_import->OCFilterSetFilterColors($this->filters_color, $this->debug);
                    }
                }
                $this->model_extension_module_trade_import->add_multiple_option_characteristic($this->option_characteristic, $this->debug);
                $this->model_extension_module_trade_import->add_multiple_warehouses($this->warehouses, $this->debug);
                uasort($this->stocks, function($a, $b) {
                    return strtotime($a["start_at"]) - strtotime($b["start_at"]);
                });
                $this->model_extension_module_trade_import->add_multiple_stocks($this->stocks, $this->debug);
                $this->model_extension_module_trade_import->add_multiple_services($this->services, $this->debug);
            }
            if ($this->config->get('module_trade_import_delete_product')) {
                $this->model_extension_module_trade_import->delete_multiple_products($product_codes, $this->debug);
            }
            if ($this->config->get('module_trade_import_full_sync')) {
                $this->model_extension_module_trade_import->full_sync($this->debug);
            }
            if ($this->config->get('module_trade_import_hide_product') && !$this->config->get('module_trade_import_add_product')) {
                $this->model_extension_module_trade_import->hide_products();
            } else if (!$this->config->get('module_trade_import_hide_product') && !$this->config->get('module_trade_import_add_product')) {
                $this->model_extension_module_trade_import->show_products();
            }
            if ($this->config->get('module_trade_import_delete_category')) {
                $this->model_extension_module_trade_import->delete_multiple_categories(array_flip(array_column($groups_unsorted['groups']['values'], 0)), true);
            }
            if ($this->config->get('module_trade_import_hide_category')) {
                $this->hide_categories();
            }
            if ($this->debug) {
                echo "Commiting\n";
            }
            $this->db->commit();
            if ($this->debug) {
                echo "Cleaning cache: \n";
                print_r(glob(DIR_CACHE . "cache.trade_import*"));
            }
            $this->db->autocommit();
            array_map('unlink', glob(DIR_CACHE . "cache.trade_import*"));
            $this->model_extension_module_trade_import->add_operation($timestamp, 1);
        } catch (\Throwable $e) {
            if ($this->debug) {
                echo "STOP: \n" . $e->getMessage() . "\n";
                echo "Rollback\n";
            }
            $this->db->rollback();
            $this->db->autocommit();
            $this->model_extension_module_trade_import->add_operation(NULL, 0);
        }
    }

    //Debug functions
    public function show_groups() {
        $response = $this->connect();
        $groups_unsorted = json_decode($this->get_groups($response), true);
        if (!$groups_unsorted) {
            echo 'Invalid JSON, unable to get groups.', "\n";
            echo $response;
            $this->model_extension_module_trade_import->add_operation(NULL, 0, $response);
        } else {
            $groups = $this->organize_groups($groups_unsorted['groups']['fields'], $groups_unsorted['groups']['values']);
            echo "JSON groups:", "\n";
            print_r($groups);
        }
    }

    public function show_nomenclatures() {
        $response = $this->connect();
        $nomenclatures = $this->get_nomenclatures($response);
        $nomenclatures_fields = $this->get_nomenclatures_fields($response);
        // $groups_unsorted = json_decode($this->get_groups($response), true);
        // $groups = $this->organize_groups($groups_unsorted['groups']['fields'], $groups_unsorted['groups']['values']);
        if (!json_decode($nomenclatures[0])) {
            echo 'Invalid JSON, unable to get nomenclatures.', "\n";
            echo $response;
            $this->model_extension_module_trade_import->add_operation(NULL, 0, $response);
            return 0;
        } else {
            print_r($nomenclatures_fields);
            echo "JSON nomenclatures:", "\n";
            foreach ($nomenclatures as $key => $nomenclature) {
                echo $key . "\n";
                if (!json_decode($nomenclature)) {
                    echo "Unable to decode part" . "\n" . $nomenclature . "\n";
                } else {
                    print_r($this->combine_fields($nomenclatures_fields, json_decode($nomenclature)));
                }
                if ($this->debug) {
                    echo memory_get_usage(true), ' bytes.', "\n";
                }
            }
        }
    }

    public function show_services() {
        $response = $this->connect();
        $nomenclatures = $this->get_nomenclatures($response);
        $nomenclatures_fields = $this->get_nomenclatures_fields($response);
        if (!json_decode($nomenclatures[0])) {
            echo 'Invalid JSON, unable to get nomenclatures.', "\n";
            echo $response;
            $this->model_extension_module_trade_import->add_operation(NULL, 0, $response);
            return 0;
        } else {
            echo "JSON services:", "\n";
            foreach ($nomenclatures as $key => $nomenclature) {
                echo $key . "\n";
                if (!json_decode($nomenclature)) {
                    echo "Unable to decode part" . "\n" . $nomenclature . "\n";
                } else {
                    $n = $this->combine_fields($nomenclatures_fields, json_decode($nomenclature));
                    foreach ($n as $service) {
                        if ($service['nomenclature_kind'] === 'SERVICE') {
                            print_r($service);
                        }
                    }
                }
                if ($this->debug) {
                    echo memory_get_usage(true), ' bytes.', "\n";
                }
            }
        }
    }

    public function show_stocks() {
        $response = $this->connect();
        $stocks = json_decode($this->get_stocks($response), true);
        if (!$stocks) {
            echo 'Invalid JSON, unable to get stocks.', "\n";
            echo $response;
            $this->model_extension_module_trade_import->add_operation(NULL, 0, $response);
        } else {
            echo "JSON stocks:", "\n";
            print_r($stocks['stocks']);
            print_r($this->combine_fields($stocks['stocks']['fields'], $stocks['stocks']['values']));
        }
    }

    public function show_filters() {
        $response = $this->connect();
        $nomenclatures = $this->get_nomenclatures($response);
        $nomenclatures_fields = $this->get_nomenclatures_fields($response);
        $groups_unsorted = json_decode($this->get_groups($response), true);
        $groups = $this->organize_groups($groups_unsorted['groups']['fields'], $groups_unsorted['groups']['values']);
        if (!json_decode($nomenclatures[0])) {
            echo 'Invalid JSON, unable to get filters.', "\n";
            echo $response;
            $this->model_extension_module_trade_import->add_operation(NULL, 0, $response);
            return 0;
        } else {
            echo "JSON filters: ", "\n";
            $category_template = $this->set_category_template();
            foreach ($groups as $group) {
                $this->set_category($group, $category_template);
            }
            foreach ($nomenclatures as $nomenclature) {
                foreach ($this->combine_fields($nomenclatures_fields, json_decode($nomenclature)) as $n) {
                    $this->set_filters($n);
                }
                if ($this->debug) {
                    echo memory_get_usage(true), ' bytes.', "\n";
                }
            }
            print_r($this->filters);
        }
    }

    public function show_option_characteristic() {
        $response = $this->connect();
        $property_types = json_decode($this->get_property_types($response), true);
        $nomenclatures = $this->get_nomenclatures($response);
        $nomenclatures_fields = $this->get_nomenclatures_fields($response);
        if (!$property_types) {
            echo 'Invalid JSON, unable to property types.', "\n";
            echo $this->get_property_types($response), "\n";
            echo $response;
            $this->model_extension_module_trade_import->add_operation(NULL, 0, $response);
            return 0;
        } else {
            echo "JSON property types:", "\n";
            $property_types = $this->combine_fields($property_types['property_types']['fields'], $property_types['property_types']['values']);
            foreach ($property_types as $key => $value) {
               $property_types[$key]['fields'] = json_decode(str_replace("trade_import_replace", "\\\"", preg_replace('/[\\\\]+/', '', str_replace("\\\\\\\"", "trade_import_replace", substr($value['fields'], 2, -2)))), true);
            }
            $this->set_color_codes($property_types);
            print_r($property_types);
            print_r($this->color_codes);
            echo "JSON option characteristics: ", "\n";
            foreach ($nomenclatures as $nomenclature) {
                foreach ($this->combine_fields($nomenclatures_fields, json_decode($nomenclature)) as $n) {
                    $this->set_option_characteristic($n);
                }
                if ($this->debug) {
                    echo memory_get_usage(true), ' bytes.', "\n";
                }
            }
            print_r($this->option_characteristic);
        }
    }

    public function show_prices() {
        $response = $this->connect();
        $prices = json_decode($this->get_prices($response), true);
        if (!$prices) {
            echo 'Invalid JSON, unable to get prices.', "\n";
            echo $this->get_prices($response), "\n";
            echo $response;
            $this->model_extension_module_trade_import->add_operation(NULL, 0, $response);
            return 0;
        } else {
            echo "JSON prices:", "\n";
            print_r($prices['price_types']);
            print_r($this->combine_fields($prices['price_types']['fields'], $prices['price_types']['values']));
        }
    }

    public function show_warehouses() {
        $response = $this->connect();
        $warehouses = json_decode($this->get_warehouses($response), true);
        if (!$warehouses) {
            echo 'Invalid JSON, unable to get prices.', "\n";
            echo $this->get_warehouses($response), "\n";
            echo $response;
            $this->model_extension_module_trade_import->add_operation(NULL, 0, $response);
            return 0;
        } else {
            echo "JSON warehouses:", "\n";
            print_r($warehouses['storages']);
            print_r($this->combine_fields($warehouses['storages']['fields'], $warehouses['storages']['values']));
        }
    }

    public function add_one_product() {
    //     $response = $this->connect();
    //     $nomenclatures = $this->get_nomenclatures($response);
    //     $product_template = $this->set_product_template();
    //     $one_product_uuid = $this->config->get('module_trade_import_add_one_product');
    //     if (!json_decode($nomenclatures[0])) {
    //         echo 'Invalid JSON, unable to get nomenclatures.', "\n";
    //         echo $response;
    //         $this->model_extension_module_trade_import->add_operation(NULL, 0, $response);
    //         return 0;
    //     } else {
    //         foreach ($nomenclatures as $nomenclature) {
    //             foreach (json_decode($nomenclature) as $n) {
    //                 if ($n[0] == $one_product_uuid) {
    //                     print_r($n);
    //                     $this->import_products($n, $product_template, array(), $this->model_extension_module_trade_import->get_product_codes(), $this->model_extension_module_trade_import->get_option_codes(), true);
    //                     return;
    //                 }
    //             }
    //         }
    //     }
    }

    public function add_one_separate_product() {
    //     $response = $this->connect();
    //     $nomenclatures = $this->get_nomenclatures($response);
    //     $product_template = $this->set_product_template();
    //     $one_product_uuid = $this->config->get('module_trade_import_add_one_product');
    //     if (!json_decode($nomenclatures[0])) {
    //         echo 'Invalid JSON, unable to get nomenclatures.', "\n";
    //         echo $response;
    //         $this->model_extension_module_trade_import->add_operation(NULL, 0, $response);
    //         return 0;
    //     } else {
    //         foreach ($nomenclatures as $nomenclature) {
    //             foreach (json_decode($nomenclature) as $n) {
    //                 if ($n[0] == $one_product_uuid) {
    //                     print_r($n);
    //                     $this->import_separate_products($n, $product_template, array(), $this->model_extension_module_trade_import->get_product_codes(), true);
    //                     return;
    //                 }
    //             }
    //         }
    //     }
    }

    public function add_indexes() {
        $this->model_extension_module_trade_import->add_indexes();
        echo 'Indexes added';
    }

    public function cache_images() {
        ini_set('max_execution_time', 0);
        $this->model_extension_module_trade_import->cache_images($this->debug);
    }

    public function clean_orders() {
        $this->model_extension_module_trade_import->clean_orders();
        echo 'Trade Orders tables cleaned';
    }

    public function clean_checks() {
        $this->model_extension_module_trade_import->clean_checks();
        echo 'Trade Checks tables cleaned';
    }

    public function clean_tables() {
        $this->model_extension_module_trade_import->clean_tables();
        echo 'Trade tables cleaned';
    }

    public function clean_all() {
        $this->model_extension_module_trade_import->clean_all($this->config->get('module_trade_import_parent_id'), $this->debug);
        echo 'All tables cleaned';
    }

    public function get_customer_groups() {
        echo "Customer Groups: \n";
        print_r($this->model_extension_module_trade_import->getCustomerGroups());
        echo "\nPrice Map: \n";
        $price_map = array();
        $maps = array_filter(explode(";", $this->config->get('module_trade_import_price_map')));
        if (!empty($maps)) {
            foreach ($maps as $map) {
                $t = explode(':', $map);
                $price_map[$t[0]] = explode(',', $t[1]);
            }
        }
        print_r($price_map);
    }

    public function get_orders() {
        $orders = $this->model_extension_module_trade_import->get_orders();
        echo "Trade orders: \n";
        print_r($orders);
    }

    public function get_checks() {
        $orders = $this->model_extension_module_trade_import->get_checks();
        echo "Trade checks: \n";
        print_r($orders);
    }

    public function get_images($response = null, $nomenclatures = null, $nomenclatures_fields = null, $groups_unsorted = null, $groups = null) {

        date_default_timezone_set($this->config->get('module_trade_import_time_zone'));
        $this->sync = $this->config->get('module_trade_import_enable_sync');
        $this->sync_schedule = new DateTime($this->config->get('module_trade_import_sync_schedule'));

        if ($this->debug) {
            echo "Getting Images\n";
        }

        if (!isset($response)) {
            $response = $this->connect();
        }
        if (!isset($nomenclatures)) {
            $nomenclatures = $this->get_nomenclatures($response);
        }
        if (!isset($nomenclatures_fields)) {
            $nomenclatures_fields = $this->get_nomenclatures_fields($response);
        }
        if (!isset($groups_unsorted)) {
            $groups_unsorted = json_decode($this->get_groups($response), true);
        }
        if (!isset($groups)) {
            $groups = $this->organize_groups($groups_unsorted['groups']['fields'], $groups_unsorted['groups']['values']);
        }
        if (!json_decode($nomenclatures[0])) {
            echo 'Invalid JSON, unable to get nomenclatures.', "\n";
            echo $response;
            $this->model_extension_module_trade_import->add_operation(NULL, 0, $response);
            return 0;
        } else {
            $this->images = $this->model_extension_module_trade_import->get_images();
            $this->image_hashes = $this->model_extension_module_trade_import->get_image_hashes();
            $this->image_filesizes = $this->model_extension_module_trade_import->get_image_filesizes();
            $this->get_images_from_folder();
            $category_template = $this->set_category_template();
            $top_group_uuid = array_filter(explode(",", $this->config->get('module_trade_import_top_category')));
            $ignore_category = array_flip(array_filter(explode(",", $this->config->get('module_trade_import_ignore_category'))));
            if (empty($top_group_uuid)) {
                foreach ($groups as $group) {
                    if (!isset($ignore_category[$group['uuid']])) {
                        $this->set_category($group, $category_template);
                    }
                }
            } else {
                foreach ($groups as $group) {
                    if (in_array($group['uuid'], $top_group_uuid)) {
                        foreach ($group['children'] as $g) {
                            if (!isset($ignore_category[$g['uuid']])) {
                                $this->set_category($g, $category_template);
                            }
                        }
                    }
                }
            }
            $stocks = json_decode($this->get_stocks($response), true);
            if ($stocks) {
                $stocks = $this->combine_fields($stocks['stocks']['fields'], $stocks['stocks']['values']);
                $this->set_stocks($stocks, $this->set_stocks_template());
            }
            $product_template = $this->set_product_template();
            foreach ($nomenclatures as $nomenclature) {
                if (!json_decode($nomenclature)) {
                    echo "Unable to decode part" . "\n" . $nomenclature . "\n";
                } else {
                    $nomenclature_array = $this->combine_fields($nomenclatures_fields, json_decode($nomenclature));
                    foreach ($nomenclature_array as $arr) {
                        if ((isset($this->categories[$arr['group_uuid']]) || $product_template['parent_category_id'])) {
                            if (isset($arr['images'])) {
                                $images = is_array($arr['images']) ? $arr['images'] : array_filter(explode(",", substr($arr['images'], 1, -1)));
                                foreach ($images as $image) {
                                    $this->get_image($product_template, $image, false, $product_template['product_image_jpeg'], $this->debug);
                                }
                            }
                            if (isset($arr['characteristics'])) {
                                foreach ($arr['characteristics'] as $attr) {
                                    if (isset($attr['attachment_ids'])) {
                                        foreach ($attr['attachment_ids'] as $option_image) {
                                            $this->get_image($product_template, $option_image, false, $product_template['product_image_jpeg'], $this->debug);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                if ($this->debug) {
                    echo memory_get_usage(true), ' bytes.', "\n";
                }
            }
            $this->model_extension_module_trade_import->add_multiple_images($this->images, $this->debug);
            if ($this->debug) {
                echo "Images downloaded\n";
            }
        }
    }

    public function delete_images() {
        $imgfiles = array_merge(glob(DIR_IMAGE . 'cache/catalog/trade_import/*'), glob(DIR_IMAGE . 'catalog/trade_import/*'));
        array_map('unlink', $imgfiles);
        echo "Deleted images\n";
        print_r($imgfiles);
        $this->model_extension_module_trade_import->clean_images();
    }

    public function delete_images_not_in_table() {
        $imgfiles = array();
        $images = array_flip(array_column($this->model_extension_module_trade_import->get_images(array('group' => 'path')), 'path'));
        foreach (array_column($this->get_images_from_folder(), 'path') as $path) {
            if (!isset($images[$path])) {
                $imgfiles[] = DIR_IMAGE . $path;
                foreach (glob(DIR_IMAGE . "cache/catalog/trade_import/" . pathinfo($path, PATHINFO_FILENAME) . "*") as $p) {
                    $imgfiles[] = $p;
                }
            }
        }
        array_map('unlink', $imgfiles);
        echo "Deleted images\n";
        print_r($imgfiles);
    }

    public function delete_images_table() {
        echo "Table images cleaned\n";
        $this->model_extension_module_trade_import->clean_images();
    }
}
