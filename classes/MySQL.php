<?php

class MySQL extends PDO {

    public function __construct($conn) {

        parent::__construct("mysql:host={$conn['servername']};dbname={$conn['dbname']};charset=latin1", $conn['username'], $conn['password']);

        $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    }

	function runQuery ( $query, $named_params = array(), $unnamed_params = array() ) {

		try {
			
			if ( count( array_filter($named_params) ) ){

				foreach($named_params as $k => &$v){

                    $stmt = $this->prepare("SET @$k = :$k");
                    
                    $stmt->bindParam(':' . $k, $v);
                    
                    $stmt->execute();
                    
                }
                
			}
			
			$stmt = $this->prepare($query);
			
			if ( count( array_filter($unnamed_params) ) ){

                $unnamed_params = explode(',', implode(',', $unnamed_params));
                
                $query = str_replace('0#', implode(",\n", array_fill(0, count($unnamed_params), '?')), $query) . "# IN block #";
                
                $stmt = $this->prepare($query);
                
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
        
        $stmt = null;

        return $result;
        
	}

}

?>