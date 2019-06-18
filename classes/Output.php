<?php

require_once dirname(__FILE__) . '/../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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

	public function toJSON()
	{
		
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

	public function toTXT( $filename = "", $savepath = "" )
	{

		header("Content-type: text/plain; charset=utf-8");
		header("Content-Disposition: attachment; filename=" . (
			strlen( $filename = trim($filename) ) ? $filename : date( "Y-m-d_H-i-s_" ) . sha1( $_SERVER['REMOTE_ADDR'] . time() )
		) . ".txt");
		
		$this->items;
		
		$savepath = strlen( $savepath = trim( $savepath ) ) ? $savepath  : "php://output";
		$file = fopen( $savepath, "w" );
		
		$headers = array_keys($this->items[key($this->items)]);
		fputcsv( $file, $headers, "\t" );
		
		foreach ( $this->items as $row ) {
			fputcsv( $file, $row, "\t" );
		}
		
	}

	public function toXLSX( $filename = false, $meta = array() )
	{
		
		if ( count($this->items) ) {

			header("Content-disposition: attachment; filename={$this->report->getLabel()}.xlsx");
			header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=utf-8");

			$workbook = new Spreadsheet();

			$sheet = $workbook->getActiveSheet();
			
			$headers = array_keys($this->items[key($this->items)]);
			
			// Header
			$sheet->getStyle('1:1')->getFont()->setBold(true);
			$sheet->fromArray($headers, null, 'A1');
			
			foreach (array_values($this->items) as $i => $row) {
				foreach ($headers as $j => $col) {

					$cell = $sheet->getCellByColumnAndRow( $j + 1, $i + 2);
					$font = $cell->getStyle()->getFont();
					
					$matches = array();
					$temp = array();
					
					if ( preg_match('/<(\w+)(\s.*)?>(.*)<\/\1>/', $row[$col], $matches) ) {
						switch ( strtolower($matches[1]) ) {
							case 'a':
								if ( preg_match('/\shref="([^"]*)"/', $matches[2], $temp) ) {
									$cell->getHyperlink()->setUrl($temp[1]);
									$font->setUnderline(true);
									$font->getColor()->setARGB('FF64B4D7');
								}
								break;
							case 'b':
							case 'strong':
								$font->setBold(true);
								break;
							case 'i':
							case 'italic':
								$font->setItalic(true);
								break;
							case 'script':
								$row[$col] = "";
								break;
						}
					}

					$cell->setValue( strip_tags($row[$col]) );

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

			(new Xlsx($workbook))->save($filename ? "$filename.xlsx" : "php://output");
			
		}

	}

}