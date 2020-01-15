<?php

class Database extends PDO {
	public function fetchField(string $sql, array $params = []) {
		$data = $this->fetchRow($sql, $params);
		return reset($data);
	}

	public function fetchRow(string $sql, array $params = []) {
		return $this->fetchAll($sql, $params, true);
	}

	public function fetchAll(string $sql, array $params = [], bool $return_one_row = false) {
		$stmt = $this->prepare($sql);
		$stmt->execute($params);
		
		if($return_one_row)
			return $stmt->fetch();
		
		return $stmt->fetchAll();
	}

	public function execute(string $sql, $params = []) {
		$stmt = $this->prepare($sql);
		$stmt->execute($params);

		return $stmt->rowCount();
	}
}