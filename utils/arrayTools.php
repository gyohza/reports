<?php
	require 'vendor/autoload.php';
	use PhpOffice\PhpSpreadsheet\Spreadsheet;
	use PhpOffice\PhpSpreadsheet\Writer\Xls;
	
	function sortHeaders($arr, $headers) {
		uksort($arr, function($a, $b) use ($headers) {
			return array_search($a, $headers) - array_search($b, $headers);
		});
		return $arr;
	}
	
	function json2csv( $json, $filename = "", $savepath = "" ) {
		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename=" . (
			strlen( $filename = trim($filename) ) ? $filename : date( "Y-m-d_H-i-s_" ) . sha1( $_SERVER['REMOTE_ADDR'] . time() )
		) . ".csv");
		header("Pragma: no-cache");
		header("Expires: 0");
		
		$bom = "\xEF\xBB\xBF";
		echo "{$bom}sep=;\n";
		
		$arr = json_decode( $json, true );
		
		$savepath = strlen( $savepath = trim( $savepath ) ) ? $savepath  : "php://output";
		$file = fopen( $savepath, "w" );
		
		$headers = array_keys($arr[key($arr)]);
		fputcsv( $file, $headers, ";" );
		
		foreach ( $arr as $row ) {
			fputcsv ( $file, sortHeaders($row, $headers), ";" ) ;
		}
		
	}
	
	function postGetContents( $assoc, $url ) {
		return file_get_contents($url, false,
			stream_context_create(
				array('http' => 
					array (
						'method' => 'POST',
						'header' => 'Content-type: application/xwww-form-urlencoded',
						'content' => http_build_query($assoc)								// Associative array with POST vars
					)
				)
			)
		);
	}
	
	function arrTbl( $arr, $id = "", $class = "", $style = "" ) {
		if ( count($arr) ) {
			
			$headers = array_keys($arr[0]);
			
			$output = "<thead><tr><th>" . implode('</th><th>', $headers) . "</th></tr></thead><tbody>";
			
			foreach ( $arr as $row ) {
				
				$output .= "<tr><td>" . implode('</td><td>', sortHeaders($row, $headers)) . "</td></tr>";

			}
			return "<table id='$id' class='$class' style='$style'> $output </tbody></table>";
		} else {
			return "<table id='$id' class='$class' hidden=''></table><br/><p><em>No results to show.</em></p><br/>";
		}
	}
	
	function arrXls( $arr, $filename = false, $meta = array() ) {
		if ( count($arr) ) {

			$workbook = new Spreadsheet();

			$sheet = $workbook->getActiveSheet();
			
			$headers = array_keys($arr[0]);
			
			// Header
			$sheet->getStyle('1:1')->getFont()->setBold(true);
			$sheet->fromArray($headers, null, 'A1');
			
			foreach ( $arr as $i => $row ) {
				
				foreach ( sortHeaders($row, $headers) as $k => $v ) {
					
					$j = array_search($k, array_keys($row));
					
					$cell = $sheet->getCellByColumnAndRow( $j + 1, $i + 2);
					$font = $cell->getStyle()->getFont();
					
					$matches = array();
					$temp = array();
					
					if ( preg_match('/<(\w+)(\s.*)?>(.*)<\/\1>/', $v, $matches) ) {
						switch ( strtolower($matches[1]) ) {
							case 'a':
								if ( preg_match('/\shref="([^"]*)"/', $matches[2], $temp) ) {
									$cell->getHyperlink()->setUrl($temp[1]);
									$font->setUnderline(true);
									$font->getColor()->setARGB('FF64B4D7');
								}
							case 'b':
							case 'strong':
								$font->setBold(true);
							case 'i':
							case 'italic':
								$font->setItalic(true);
							case 'script':
								$v = "";
						}
					}
					
					if ( preg_match('/<!--@(.*)(?!-->)@-->/', $v, $matches) ) {
						$v = $matches[1];
					}
					
					$cell->setValue( strip_tags($v) );
					
				}
				
			}
			
			$workbook->getProperties()
				->setCreator(			isset($meta['authors'])		? implode(",", $meta['authors']) : "" )
				->setLastModifiedBy(	isset($meta['maintainers'])	? implode(",", $meta['maintainers']) : "" )
				->setTitle(				isset($meta['name'])		? $meta['name'] : "" )
				->setSubject(			isset($meta['name'])		? $meta['name'] : "" )
				->setDescription(		isset($meta['description'])	? $meta['description'] : "" )
				->setKeywords(			isset($meta['keywords'])	? implode(" ", $meta['keywords']) : "" )
				->setCategory(			isset($meta['category'])	? $meta['category'] : "" );
			
			(new Xls($workbook))->save($filename ? "$filename.xls" : "php://output");
			
		}
	}
?>