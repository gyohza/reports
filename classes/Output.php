<?php

require_once dirname(__FILE__) . '/../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class Output
{

	private $report;

	private $items;

	private $headers;

	public function __construct($report)
	{

		if ($report instanceof Report) $this->report = $report;
		else throw new RuntimeException("Not a valid Report instance.");

		$this->report = $report;

		$this->items = $this->report->getResults();

	}

	public function toJSON() {
		
		header ( 'Content-Type: application/json' );

		echo json_encode($this->items);

	}

	public function toCSV( $filename = "", $savepath = "" )
	{

		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename=" . (
			strlen( $filename = trim($filename) ) ? $filename : date( "Y-m-d_H-i-s_" ) . sha1( $_SERVER['REMOTE_ADDR'] . time() )
		) . ".csv");
		header("Pragma: no-cache");
		header("Expires: 0");
		
		$bom = "\xEF\xBB\xBF";
		echo "{$bom}sep=;\n";
		
		$this->items;
		
		$savepath = strlen( $savepath = trim( $savepath ) ) ? $savepath  : "php://output";
		$file = fopen( $savepath, "w" );
		
		$headers = array_keys($this->items[key($this->items)]);
		fputcsv( $file, $headers, ";" );
		
		foreach ( $this->items as $row ) {
			fputcsv( $file, $row, ";" );
		}
		
	}

	public function toXLS( $filename = false, $meta = array() )
	{
		if ( count($this->items) ) {
			header("Content-disposition: attachment; filename={$this->report->getLabel()}.xls");
			header("Content-Type: application/vnd.ms-excel; charset=utf-8");

			$workbook = new Spreadsheet();

			$sheet = $workbook->getActiveSheet();
			
			$headers = array_keys($this->items[key($this->items)]);
			
			// Header
			$sheet->getStyle('1:1')->getFont()->setBold(true);
			$sheet->fromArray($headers, null, 'A1');
			
			foreach ( $this->items as $i => $row ) {
				
				foreach ( $row as $k => $v ) {
					
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

}