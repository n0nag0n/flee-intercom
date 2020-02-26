<?php

class Controller {

	private $db;

	public function beforeroute($f3) {

		$token = $f3->REQUEST['api_token'] ?: str_replace('Bearer ', '', $f3->HEADERS['Authorization']);
		if(!$token && stripos($f3->PATTERN, 'checkEmail') === false) {
			$this->outputJson([ 'error' => 'invalid authorization' ], 403);
		}

		$this->db = $f3->db;
	}

	private function outputJson(array $array, int $http_status_code = 200) {
		http_response_code($http_status_code);
		echo json_encode($array);
		exit;
	}

	private function outputHtml(string $html, int $http_status_code = 200) {
		http_response_code($http_status_code);
		echo $html;
		exit;
	}

	private function getConversationHistoryByEmailAddress(string $email): array {
		if(filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
			throw new Exception('missing valid email address');
		}

		$db = $this->db;
		
		$lead = $db->exec("SELECT * FROM leads WHERE email = ?", [ $email ])[0];
		$user = $db->exec("SELECT * FROM users WHERE email = ?", [ $email ])[0];

		$output = [];
		if($lead['id']) {
			$output['lead'] = [];
			$output['lead']['info'] = $lead;
			$output['lead']['conversation_history'] = $this->getConversationHistoryFromUserIntercomId('lead', $lead['intercom_id']);
		}

		if($user['id']) {
			$output['user'] = [];
			$output['user']['info'] = $user;
			$output['user']['conversation_history'] = $this->getConversationHistoryFromUserIntercomId('user', $user['intercom_id']);
		}

		return $output;
	}

	public function getConversationHistoryByEmailAddressApi($f3) {
		$email = $f3->REQUEST['email'];
		if(filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
			$this->outputJson([ 'error' => 'missing valid email address' ], 403);
		}

		$output = [];
		$output = $this->getConversationHistoryByEmailAddress($email);

		$this->outputJson($output);
	}

	public function checkEmail($f3) {
		$email = $f3->REQUEST['email'];

		$html = '';
		if($email) {
			$html = $this->getConversationHistoryByEmailAddressHtml($email);
		}

		$this->outputHtml(\View::instance()->render('check_email_template.php', 'text/html', [ 'html' => $html, 'email' => $email, 'api_token' => $f3->config['api_token'] ]));
	}

	public function downloadFile($f3) {
		$id = $f3->REQUEST['id'];
		$unique_filename_hash = $f3->REQUEST['unique_filename_hash'];

		if(!$id || !$unique_filename_hash) {
			throw new Exception('missing required fields');
		}

		$result = $this->db->exec("SELECT name, content_type, filesize, content FROM conversation_attachments WHERE id = ? AND unique_filename_hash = ?", [
			$id,
			$unique_filename_hash
		]);

		if(!$result) {
			$this->db->exec("SELECT name, content_type, filesize, content FROM conversation_part_attachments WHERE id = ? AND unique_filename_hash = ?", [
				$id,
				$unique_filename_hash
			]);
		}

		if(!$result) {
			throw new Exception('attachment not found');
		}

		$attachment = $result[0];

		header('Content-Type: '.$attachment['content_type']);
		header('Content-Length: '.$attachment['filesize']);
		if(!isset($f3->REQUEST['image'])) {
			header('Content-Disposition: attachment; filename='.$attachment['name']);
		}
		header('Pragma: no-cache');
		echo $attachment['content'];
		exit;
	}

	private function getConversationHistoryFromUserIntercomId(string $type, string $intercom_id): array {

		$conversations = [];
		if($type !== 'lead' && $type !== 'user') {
			throw new Exception('must be lead or user type');
		}

		$f3 = \Base::instance();

		$db = $this->db;

		$different_conversation_ids = array_column($db->exec("SELECT conversation_id FROM conversation_customers WHERE customer_type = ? AND customer_id = ?", [ $type, $intercom_id]), 'conversation_id');

		if(!count($different_conversation_ids)) {
			return $conversations;
		}

		foreach($different_conversation_ids as $id) {
			$conversation = $db->exec("SELECT * FROM conversations WHERE id = ?", [ $id ]);
			$conversation['attachments'] = $db->exec("SELECT id, unique_filename_hash, type, name, url, TO_BASE64(content) content, content_type, attached_by_type, attached_by_id, filesize FROM conversation_attachments WHERE conversation_id = ?", [ $id ]);
			$conversation['parts'] = $db->exec("SELECT id, intercom_id, created_at, updated_at, subject, body, author_type, author_id FROM conversation_parts WHERE conversation_id = ?", [ $id ]);
			foreach($conversation['attachments'] as $key => $attachment) {
				$conversation['attachments'][$key]['attached_by'] = $this->getUserDataFromUserIntercomId($attachment['attached_by_type'], $attachment['attached_by_id']);
				$conversation['attachments'][$key]['local_download_url'] = $f3->config['local_download_url_host'].'/downloadFile?id='.$attachment['id'].'&unique_filename_hash='.$attachment['unique_filename_hash'];
				$conversation['attachments'][$key]['is_image'] = stripos($attachment['name'], '.gif') !== false || stripos($attachment['name'], '.png') !== false || stripos($attachment['name'], '.jpeg') !== false || stripos($attachment['name'], '.jpg') !== false;
			}

			foreach($conversation['parts'] as $key => $part) {
				$conversation['parts'][$key]['author'] = $this->getUserDataFromUserIntercomId($part['author_type'], $part['author_id']);
				$conversation['parts'][$key]['attachments'] = $db->exec("SELECT id, unique_filename_hash, type, name, url, TO_BASE64(content) content, content_type, attached_by_type, attached_by_id, filesize FROM conversation_part_attachments WHERE conversation_part_id = ?", [ $part['id'] ]);
				foreach($conversation['parts'][$key]['attachments'] as $attachment_key => $attachment) {
					$conversation['parts'][$key]['attachments'][$attachment_key]['attached_by'] = $this->getUserDataFromUserIntercomId($attachment['attached_by_type'], $attachment['attached_by_id']);
					$conversation['parts'][$key]['attachments'][$attachment_key]['local_download_url'] = $f3->config['local_download_url_host'].'/downloadFile?id='.$attachment['id'].'&unique_filename_hash='.$attachment['unique_filename_hash'];
					$conversation['parts'][$key]['attachments'][$attachment_key]['is_image'] = stripos($attachment['name'], '.gif') !== false || stripos($attachment['name'], '.png') !== false || stripos($attachment['name'], '.jpeg') !== false || stripos($attachment['name'], '.jpg') !== false;
				}
			}

			$conversations[] = $conversation;
		}

		return $conversations;
	}

	private function getUserDataFromUserIntercomId(string $user_type, string $intercom_id): array {

		$table = $this->getTableNameFromUserType($user_type);
		if($table === 'bot') {
			return [ 'email' => 'intercom-bot@intercom.io', 'name' => 'Bot' ];
		}

		$result = $this->db->exec("SELECT id, intercom_id, email, name FROM {$table} WHERE intercom_id = ?", [ $intercom_id ]);

		return $result[0] ?: [];
	}

	private function getTableNameFromUserType(string $user_type): string {
		$table = '';
		switch($user_type) {
			case 'lead':
				$table = 'leads';
			break;

			case 'user':
				$table = 'users';
			break;

			case 'admin':
				$table = 'admins';
			break;

			case 'bot':
				$table = 'bot';
			break;

			default:
				throw new Exception('User type not found');
		}

		return $table;
	}

	private function getConversationHistoryByEmailAddressHtml(string $email): string {

		if(filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
			$this->outputHtml('missing valid email address', 403);
		}

		$data = $this->getConversationHistoryByEmailAddress($email);

		$html = '';
		if(isset($data['lead'])) {
			$html .= $this->getHtmlByUserData('Lead', $data['lead']);
		}

		if(isset($data['user'])) {
			$html .= $this->getHtmlByUserData('User', $data['user']);
		}

		if(!$html) {
			$html = '<p>No data retrieved.</p>';
		}

		return $html;
	}

	private function getHtmlByUserData(string $user_type, array $user_data): string {

		if(!isset($user_data['info'])) {
			throw new Exception('this should have had an info key');
		}

		$f3 = \Base::instance();

		$html = \View::instance()->render('user_template.php', 'text/html', [ 'api_token' => $f3->config['api_token'], 'user_type' => $user_type, 'user' => $user_data ]);
		return $html;
	}
}