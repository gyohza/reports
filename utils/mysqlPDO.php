<?php
	function runQuery ( $servername, $dbname, $username, $password, $query, $named_params = array(), $unnamed_params = array() ) {
		try {
			$conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=latin1", $username, $password);
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			
			if ( count( array_filter($named_params) ) ){
				foreach($named_params as $k => &$v){
					$stmt = $conn->prepare("SET @$k = :$k");
					$stmt->bindParam(':' . $k, $v);
					$stmt->execute();
				}
			}
			
			$stmt = $conn->prepare($query);
			
			if ( count( array_filter($unnamed_params) ) ){
				$unnamed_params = explode(',', implode(',', $unnamed_params));
				$query = str_replace('0#', implode(",\n", array_fill(0, count($unnamed_params), '?')), $query) . "# IN block #";
				$stmt = $conn->prepare($query);
				foreach($unnamed_params as $k => &$v){
					$stmt->bindParam($k + 1, $v);
				}
			}
			
			$stmt->execute();
			$stmt->setFetchMode(PDO::FETCH_ASSOC);
			$result = $stmt->fetchAll();
			
		} catch ( PDOException $e ) {
			echo "Error: " . $e->getMessage();
			$result = null;
		}
		$conn = null;
		return $result;
	}
?>