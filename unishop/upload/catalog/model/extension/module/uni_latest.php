<?php
class ModelExtensionModuleUniLatest extends Model {
	
	public function getProducts($data) {
		$uniset = $this->config->get('config_unishop2');
		$lang_id = (int)$this->config->get('config_language_id');
		$store_id = (int)$this->config->get('config_store_id');
		
		$date = date('Y-m-d', strtotime('-'.$data['date'].'days'));
		
		$sql = "SELECT p.product_id, p.date_available, (SELECT AVG(rating) FROM `".DB_PREFIX."review` r1 WHERE r1.product_id = p.product_id AND r1.status = '1' GROUP BY r1.product_id) AS rating";
		$sql .= " FROM (SELECT * FROM `".DB_PREFIX ."product` p1 WHERE p1.status = '1' AND p1.product_id = (SELECT p1.product_id FROM `".DB_PREFIX."product_to_store` WHERE p1.product_id = product_id AND store_id = '".$store_id."')";
		$sql .= " GROUP BY p1.product_id ORDER BY p1.date_available DESC, p1.product_id DESC LIMIT 0, ".(int)$data['limit'].") as p";
		
		if (!empty($data['category_id'])) {
			$sql .= " LEFT JOIN `".DB_PREFIX."product_to_category` p2c ON (p.product_id = p2c.product_id)";
		}
		
		$sql .= " LEFT JOIN `".DB_PREFIX."product_description` pd ON (p.product_id = pd.product_id) WHERE";
		
		if (!empty($data['category_id'])) {
			$sql .= " p2c.category_id = '".(int)$data['category_id']."' AND";
		}
		
		$sql .= " p.date_available >= '".$this->db->escape($date)."' AND pd.language_id = '".$lang_id."' GROUP BY p.product_id";

		$sort_data = [
			'pd.name',
			'p.model',
			'p.quantity',
			'p.price',
			'rating',
			'p.date_available'
		];
		
		$sort_qty = '';
				
		if($uniset['sort_qty'] == 1){
			if(isset($data['sort']) && $data['sort'] == 'p.date_available') {
				$sort_qty = '(p.quantity > 0) DESC,';
			}
		} elseif($uniset['sort_qty'] == 2) {
			$sort_qty = '(p.quantity > 0) DESC,';
		}
		
		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			if ($data['sort'] == 'pd.name' || $data['sort'] == 'p.model') {
				$sql .= " ORDER BY ".$sort_qty." LCASE(".$data['sort'].")";
			} else {
				$sql .= " ORDER BY ".$sort_qty." ".$data['sort'];
			}
			
			if (isset($data['order']) && ($data['order'] == 'DESC')) {
				$sql .= " DESC,";
			} else {
				$sql .= " ASC,";
			}
		} else {
			$sql .= " ORDER BY ".$sort_qty;
		}

		$sql .= " LCASE(pd.name) ASC";

		$product_data = [];

		$query = $this->db->query($sql);
		
		$results = $query->rows;
		
		if($results) {
			foreach ($results as $result) {
				$product_data[$result['product_id']] = $this->model_catalog_product->getProduct((int)$result['product_id']);
			}
		}

		return $product_data;
	}
	
	public function getProductCategories($data) {
		$lang_id = (int)$this->config->get('config_language_id');
		$store_id = (int)$this->config->get('config_store_id');
		$products = [];
		$result = [];
		
		$date = date('Y-m-d', strtotime('-'.$data['date'].'days'));
		
		$sql = "SELECT p.product_id FROM `".DB_PREFIX ."product` p LEFT JOIN ".DB_PREFIX."product_to_store p2s ON (p.product_id = p2s.product_id)";
		$sql .= " WHERE p.status = '1' AND p2s.store_id = '".$store_id."' AND p.date_available >= '".$this->db->escape($date)."'";
		$sql .= " GROUP BY p.product_id ORDER BY p.date_available DESC, p.product_id DESC LIMIT 0, ".(int)$data['limit']."";
		
		$query = $this->db->query($sql);
		
		if ($query->rows) {
			$products = array_column($query->rows, 'product_id');
		}
		
		if($products) {
			$sql = "SELECT c.category_id, cd.name FROM `".DB_PREFIX."product_to_category` p2c LEFT JOIN `".DB_PREFIX."category` c ON (p2c.category_id = c.category_id) LEFT JOIN `".DB_PREFIX."category_description` cd ON (p2c.category_id = cd.category_id)";
			$sql .= " LEFT JOIN ".DB_PREFIX."category_to_store c2s ON (p2c.category_id = c2s.category_id)";
			$sql .= " WHERE product_id IN (".implode(',', array_map('intval', $products)).") AND cd.language_id = '".$lang_id."' AND c2s.store_id = '".$store_id."' AND c.status = '1' GROUP BY c.category_id ORDER BY LCASE(cd.name) ASC LIMIT 0, 50";
			
			$query = $this->db->query($sql);
			
			if($query->rows && count($query->rows) > 1) {
				$result = $query->rows;
			}
		}

		return $result;
	}
}
?>