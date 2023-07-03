<?php
class ModelExtensionModuleUniRequest extends Model {
	private $email = 'request@localhost.com';
	
	public function addRequest($data) {
		$this->db->query("INSERT INTO `".DB_PREFIX."uni_request` SET type = '".$this->db->escape(strip_tags($data['type']))."', name = '".$this->db->escape(strip_tags($data['name']))."', phone = '".$this->db->escape(strip_tags($data['phone']))."', mail = '".$this->db->escape(strip_tags(mb_strtolower($data['mail'], 'UTF-8')))."', product_id = '".(int)$data['product_id']."', comment = '".$this->db->escape(strip_tags($data['comment']))."', admin_comment = '', date_added = NOW(), date_modified = NOW(), status = '".(int)$data['status']."'");
		$request_id = $this->db->getLastId();
	}

	public function getRequest($request_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM `".DB_PREFIX."uni_request` WHERE request_id = '".(int)$request_id."'");

		return $query->row;
	}

	public function getRequests($data = array()) {
		$sql = "SELECT request_id, type, name, phone, mail, product_id, comment, admin_comment, date_added, date_modified, status FROM `".DB_PREFIX."uni_request` WHERE request_id != '0'";	
		
		if (isset($data['product_id'])) {
			$sql .= " AND product_id = '".(int)$data['product_id']."'";
		}
		
		$sql .= " AND request_list = '1'";
		$sql .= " AND status = '3'";
		$sql .= " ORDER BY date_added";	
		$sql .= " DESC";

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}			

			if ($data['limit'] < 1) {
				$data['limit'] = 5;
			}	

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}
		
		$query = $this->db->query($sql);	
		
		return $query->rows;
	}

	public function getTotalRequests($data = []) {
		$sql = "SELECT COUNT(*) AS total FROM `".DB_PREFIX."uni_request` WHERE request_id != '0'";
		
		if (isset($data['product_id'])) {
			$sql .= " AND product_id = '".(int)$data['product_id']."'";
		}
		
		$sql .= " AND request_list = '1' AND status = '3'";
		
		$query = $this->db->query($sql);
		
		return $query->row['total'];
	}
	
	public function getAttempts($ip) {
		$email = $this->email;
		
		$query = $this->db->query("SELECT * FROM `".DB_PREFIX."customer_login` WHERE email = '".$this->db->escape(mb_strtolower($email))."' AND ip = '".$this->db->escape($ip)."'");

		return $query->row;
	}
	
	public function addAttempt($ip) {
		$email = $this->email;
		
		$query = $this->db->query("SELECT * FROM `".DB_PREFIX."customer_login` WHERE email = '".$this->db->escape(mb_strtolower($email))."' AND ip = '".$this->db->escape($ip)."'");
		
		if (!$query->num_rows) {
			$this->db->query("INSERT INTO `".DB_PREFIX."customer_login` SET email = '".$this->db->escape(mb_strtolower($email))."', ip = '".$this->db->escape($ip)."', total = 1, date_added = '".$this->db->escape(date('Y-m-d H:i:s'))."', date_modified = '".$this->db->escape(date('Y-m-d H:i:s'))."'");
		} else {
			$total = strtotime($query->row['date_modified']) < strtotime('-1 hour') ? 1 : '(total + 1)';
			
			$this->db->query("UPDATE `".DB_PREFIX."customer_login` SET total = ".$total.", date_modified = '".$this->db->escape(date('Y-m-d H:i:s'))."' WHERE customer_login_id = '".(int)$query->row['customer_login_id']."'");
		}
	}
}
?>