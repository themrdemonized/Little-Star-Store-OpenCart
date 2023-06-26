<?php
class ModelSaleManager extends Model
{
	private $type = "sale";
	private $extension = "manager";
			
	public function getStats($data)
	{		
		$stats = array();
		$where = array();
		
		if ($this->config->get($this->extension.'_statuses') && ($data['mode'] == "custom")) {
			$statuses = array();
			
			foreach ($this->config->get($this->extension.'_statuses') as $key => $status) {
				if (isset($status['checked']) && $status['checked']) $statuses[] = $key;
			}
			
			if ($statuses) $where[] = " o.order_status_id IN (".implode(", ", $statuses).")";
		}
		
		$filters = $this->getFilters($data);
		
		if ($filters) $where[] = $filters;
		
		if ($where) $where = "WHERE ".implode(" AND ", $where);
		else $where = "";
		
		$this->db->query("SET SESSION group_concat_max_len = 1000000");
				
    	$sql = "SELECT (SELECT GROUP_CONCAT(DISTINCT o.order_status_id) FROM `".DB_PREFIX."order` AS o ".$where.") AS statuses, (SELECT GROUP_CONCAT(DISTINCT CONCAT_WS('##', o.payment_code, o.payment_method) SEPARATOR '%%') FROM `".DB_PREFIX."order` AS o ".$where.") AS payments,
(SELECT GROUP_CONCAT(DISTINCT CONCAT_WS('##', o.shipping_code, o.shipping_method) SEPARATOR '%%') FROM `".DB_PREFIX."order` AS o ".$where.") AS shippings, (SELECT COUNT(o.order_status_id) FROM `".DB_PREFIX."order` AS o ".$where.") AS orders";
    			
      	$query = $this->db->query($sql);
      			
		if ($query->row['orders']) $stats['orders'] = $query->row['orders'];
		else $stats['orders'] = "";
				
		if ($query->row['statuses']) $stats['statuses'] = explode(",", $query->row['statuses']);
		else $stats['statuses'] = array();
				
		if ($query->row['payments']) {
			foreach (explode("%%", $query->row['payments']) as $pair) {
             	list($k, $v) = explode('##', $pair);
                $stats['payments'][$k] = $v;
			}
		} else {
			$stats['payments'] = array();
		}
				
		if ($query->row['shippings']) {
        	foreach (explode("%%", $query->row['shippings']) as $pair) {
             	list($k, $v) = explode('##', $pair);
                $stats['shippings'][$k] = $v;
			}
		} else {
			$stats['shippings'] = array();
		}

		return $stats;
	}
						
	public function getOrders($data)
	{
		$orders = array();
		$where = array();
		
		if ($this->config->get($this->extension.'_statuses') && ($data['mode'] == "custom")) {
			$statuses = array();
			
			foreach ($this->config->get($this->extension.'_statuses') as $key => $status) {
				if (isset($status['checked']) && $status['checked']) $statuses[] = $key;
			}
			
			if ($statuses) $where[] = " o.order_status_id IN (".implode(", ", $statuses).")";
		}
		
		$filters = $this->getFilters($data);
		
		if ($filters) $where[] = $filters;
		
		if ($where) $where = " WHERE ".implode(" AND ", $where);
		else $where = "";
		
		$sql = "SELECT o.order_id, o.store_id, o.store_name, o.order_status_id, (SELECT os.name FROM `".DB_PREFIX."order_status` AS os WHERE os.order_status_id = o.order_status_id AND os.language_id = '".(int)$this->config->get('config_language_id')."') AS status, CONCAT(o.firstname, ' ', o.lastname) AS customer, o.email, o.payment_company, o.customer_id, CONCAT(o.shipping_firstname, ' ', o.shipping_lastname) AS recipient, o.shipping_company, o.telephone, o.payment_lastname, o.payment_firstname, o.payment_city, o.payment_postcode, o.payment_country, o.payment_address_1, o.payment_address_2, o.payment_zone, o.shipping_firstname, o.shipping_lastname, o.shipping_city, o.shipping_postcode, o.shipping_country, o.shipping_address_1, o.shipping_address_2, o.shipping_zone, o.payment_method, o.payment_code, o.shipping_method, o.shipping_code, o.date_added, o.date_modified, o.total, o.comment, o.currency_code, o.currency_value, (SELECT ot.value FROM `".DB_PREFIX."order_total` AS ot WHERE ot.order_id = o.order_id AND code = 'sub_total') AS subtotal FROM `".DB_PREFIX."order` AS o".$where;
				
		$sql .= " ORDER BY ".$data['sort']." ".$data['order'];
		$sql .= " LIMIT ".(int)$data['start'].", ".(int)$data['limit'];

		$query = $this->db->query($sql);
		$orders = $query->rows;
				
		return $orders;
	}

	public function getFilters($data)
	{	
		$sql = array();
				
		if (isset($data['filter_order_id']) && !is_null($data['filter_order_id'])) {
			$sql[] = "(o.order_id = '".(int)$data['filter_order_id']."')";
		}
					
		if (isset($data['filter_order_status_id']) && !is_null($data['filter_order_status_id'])) {
			$sql[] = "(o.order_status_id = '".(int)$data['filter_order_status_id']."')";
		}
						
		if (isset($data['filter_customer']) && !is_null($data['filter_customer'])) {
			$tags = array('name', 'store_name', 'telephone', 'email', 'custom_field', 'payment_company', 'payment_address_1', 'payment_address_2', 'payment_country', 'payment_city', 'payment_zone', 'payment_postcode', 'payment_custom_field');
			$filter_customer = $this->db->escape($data['filter_customer']);			
			
			$conditions = array();
			
			foreach ($tags as $tag) {
				if ($tag == 'name') {
					$conditions[] = "(CONCAT(o.firstname, ' ', o.lastname) LIKE '%".$filter_customer."%')";
					$conditions[] = "(CONCAT(o.lastname, ' ', o.firstname) LIKE '%".$filter_customer."%')";
					$conditions[] = "(CONCAT(o.payment_firstname, ' ', o.payment_lastname) LIKE '%".$filter_customer."%')";
					$conditions[] = "(CONCAT(o.payment_lastname, ' ', o.payment_firstname) LIKE '%".$filter_customer."%')";
				} else {
					$conditions[] = "(o.".$tag." LIKE '%".$filter_customer."%')";
				}
			}
			
			$sql[] = "(".implode(" OR ", $conditions).")";
		}
							
		if (isset($data['filter_recipient']) && !is_null($data['filter_recipient'])) {
			$tags = array('name', 'shipping_company', 'shipping_address_1', 'shipping_address_2', 'shipping_country', 'shipping_city', 'shipping_zone', 'shipping_postcode', 'shipping_custom_field');
			$filter_recipient = $this->db->escape($data['filter_recipient']);			
			
			$conditions = array();
			
			foreach ($tags as $tag) {
				if ($tag == 'name') {
					$conditions[] = "(CONCAT(o.shipping_firstname, ' ', o.shipping_lastname) LIKE '%".$filter_recipient."%')";
					$conditions[] = "(CONCAT(o.shipping_lastname, ' ', o.shipping_firstname) LIKE '%".$filter_recipient."%')";
				} else {
					$conditions[] = "(o.".$tag." LIKE '%".$filter_recipient."%')";
				}
			}
			
			$sql[] = "(".implode(" OR ", $conditions).")";
		}
			
		if (isset($data['filter_date_added']) && !is_null($data['filter_date_added'])) {
			$data['filter_date_added'] = date('Y-m-d', strtotime($data['filter_date_added']));
			$sql[] = "(DATE(o.date_added) = DATE('".$this->db->escape($data['filter_date_added'])."'))";
		}
		
		if (isset($data['filter_date_modified']) && !is_null($data['filter_date_modified'])) {
			$data['filter_date_modified'] = date('Y-m-d', strtotime($data['filter_date_modified']));
			$sql[] = "(DATE(o.date_modified) = DATE('".$this->db->escape($data['filter_date_modified'])."'))";
		}
				
		if (isset($data['filter_products']) && !is_null($data['filter_products'])) {
			$this->db->query("SET SESSION group_concat_max_len = 1000000");
			
			$result = $this->db->query("SELECT GROUP_CONCAT(DISTINCT op.order_id) AS orders FROM `".DB_PREFIX."order_product` AS op WHERE op.`name` LIKE '%".$this->db->escape($data['filter_products'])."%' OR op.`model` LIKE '%".$this->db->escape($data['filter_products'])."%'");
			
			if ($result->row['orders']) {
				$sql[] = "(o.order_id IN (".$result->row['orders']."))";
			}
		}
								
		if (isset($data['filter_payment']) && !is_null($data['filter_payment'])) {
			$sql[] = "(o.payment_method = '".$this->db->escape($data['filter_payment'])."')";
		}
			
		if (isset($data['filter_shipping']) && !is_null($data['filter_shipping'])) {
			$sql[] = "(o.shipping_method = '".$this->db->escape($data['filter_shipping'])."')";
		}

		if (isset($data['filter_subtotal']) && !is_null($data['filter_subtotal'])) {
			$sql[] = "(subtotal = '".(float)$data['filter_subtotal']."')";
		}
		
		if (isset($data['filter_total']) && !is_null($data['filter_total'])) {
			$sql[] = "(o.total = '".(float)$data['filter_total']."')";
		}

		return implode(" AND ", $sql);
	}
}

?>