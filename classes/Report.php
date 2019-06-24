<?php

class Report
{
	
	private $client;
	private $alias;
	private $name;
	private $fingerprint;
	private $label;
	private $valid;
	private $err;
	private $meta;
	private $results;
	private $params;
	
	public function __construct($reportAlias)
	{
		
		try {
			
			// Checks if provided alias matches an existing report.
			if (!file_exists("./queries/$reportAlias.json")) throw new Exception('Report does not exist!');
			$this->meta = json_decode(file_get_contents("./queries/$reportAlias.json"), true);
			
			// Throws an error if JSON parsing unsuccessful.
			if (json_last_error()) throw new RuntimeException(json_last_error_msg());


			/********** USER AUTHENTICATION **********/

			// Checks if client is localhost - if not, checks if the report has a whitelist and it is populated.
			if ($_SERVER['REMOTE_ADDR'] !== "::1" && isset($this->meta['whitelist']) && count($this->meta['whitelist'])) {
				
				$this->client = new Client($_GET['apiKey'], $this->meta['whitelist'], $_SERVER['REMOTE_ADDR']);

			}

			$this->alias = $reportAlias;

			$this->name = $this->meta['name'];
			
			$this->valid = true;

		} catch (Exception $e) {
			
			$this->valid = false;
			
			$this->err = $e->getMessage();

		}

	}
	
	public function getAlias()
	{
		return $this->alias;
	}
	
	public function getName()
	{
		return $this->name;
	}
	
	public function getFingerprint()
	{
		return $this->fingerprint;
	}
	
	public function getLabel()
	{
		return $this->label;
	}
	
	public function isValid()
	{
		return $this->valid;
	}
	
	public function getErr()
	{
		return $this->err;
	}

	public function get($attrib)
	{
		return isset($this->meta[$attrib]) ? $this->meta[$attrib] : null;
	}

	public function printMeta()
	{
		print_r($this->meta);
	}

	public function getParams()
	{
		return $this->params ? $this->params : $this->params = array_map(
			function($v) {
				return $v['params'];
			},
			array_filter($this->meta['queries'], function($q) {
				return isset($q['params']);
			})
		);
	}
	
	public function retrieveData()
	{
		
		$mt = microtime(true);
		$this->fingerprint = str_pad( base_convert( round( ( $mt - floor($mt) ) * 10000000 ), 10, 36 ), 5, '0', STR_PAD_LEFT );
		$this->label = $this->name . " @ " . date_format( date_create(), "Y-m-d h-i-s" ) . " " . $this->fingerprint;
		
		// Defines the first key based on JSON's first query data
		$rkey = $this->meta['queries'][0]['key'];
		
		// Creates the array that will be populated with query results
		$items	= array();
		
		// Gets DB connections data
		$conns = json_decode(file_get_contents("./config/connections.json"), true);
		
		// Loops through every query in the JSON file
		foreach ( $this->meta['queries'] as $i => $q ) {
			
			$query = "/* [ {$_SERVER['PHP_SELF']} - {$this->label} ] Requester IP: {$_SERVER['REMOTE_ADDR']} */" .	//
				file_get_contents( "./queries/" . $q['query'] );
			
			$params	= isset($q['params']) ? $q['params'] : array();
			
			foreach ( $params as $k => $v ) {
				if ( isset($_GET[$k]) ) $params[$k] = trim($_GET[$k]);
			}
					
			$rkey = $q['key'];
			
			$mysql = new MySQL($conns[$q['conn']]);

			$presets = isset($q['presets']) ? $q['presets'] : array();

			foreach ( $presets as $k => $v ) {
				$mysql->query("set $k = $v");
			}

			if ( $i ) {
				
				$items = array_combine( array_column($items, $rkey), array_values( $items ) );
				
				$rows = $mysql->runQuery( $query, $params, array_keys($items) );
				
			} else {
				
				$rows = $mysql->runQuery( $query, $params, isset($_GET[$rkey]) ? explode( ",", $_GET[$rkey] ) : array() );
				
			}

			foreach ( $items as $k => $v ) {
			
				$items[$k] = array_merge( array_fill_keys(array_keys($rows[0]), null), $v );

				$split = explode(',', $k);

				if ( count($split) > 1 ) {
					foreach ( $split as $newkey ) {
						$items[$newkey] = $v;
					}
					unset($items[$k]);
				}

			}
			
			foreach ( $rows as $row ) {
				if ( $i ) {

					$items[$row[$rkey]] = array_merge( $items[$row[$rkey]], $row );

					foreach ( $items[$row[$rkey]] as $k => $v ) {
						if ( !strlen( $v ) && isset( $row[$k] ) ) $items[$row[$rkey]][$k] = $row[$k];
					}

				} else	$items[$row[$rkey]] = $row;
			}
			
		}
		
		$headers = count($items) ? array_keys($items[key($items)]) : array();

		foreach ($items as $k => $v) {

			uksort($v, function($a, $b) use ($headers) {
				return array_search($a, $headers) - array_search($b, $headers);
			});

			unset($items[$k]);

			foreach ($v as $header => $cell) {
				$items[utf8_encode($k)][utf8_encode($header)] = utf8_encode($cell);
			}

		}

		$this->results = $items;

	}

	public function getResults()
	{

		if (!isset($this->results)) $this->retrieveData();

		return $this->results;

	}

}