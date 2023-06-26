<?php
class ControllerExtensionModuleTradeImport extends Controller {
    public function index() {
        $this->load->model('setting/setting');
        date_default_timezone_set($this->config->get('module_trade_import_time_zone'));
        if (($this->config->get('module_trade_import_enable_sync') == 1) && (time() - strtotime($this->config->get('module_trade_import_sync_schedule')) >= 0)) {
            $this->trade_import->get_json();
        }
    }
}