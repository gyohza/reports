<?php

	require_once dirname(__FILE__) . "/classes/_loader.php";
	require_once 'utils/arrayTools.php';
	
	$mode = isset($_GET['mode']) ? trim ( $_GET['mode'] ) : "table";
	$repAlias = isset($_GET['report']) ? trim($_GET['report']) : false;
	
	if (!$repAlias) {
		
		$browse = new HtmlDoc(array(
			"content" => new Browse(),
			"pageTitle" => "Explorar Relatórios",
			"pageHeaderTitle" => "Explorar Relatórios",
			"lang" => "pt"
		));
		
		$browse->buildPage();
		
	} else {
		
		$report = new Report($repAlias);
		
		if ( $mode == 'query' && $report->isValid() ) {

			$query = new HtmlDoc(array(
				"content" => new Query($report),
				"pageTitle" => $report->getName(),
				"pageHeaderTitle" => "Configurar Relatório - " . $report->getName(),
				"lang" => "pt"
			));

			$query->buildPage();

		} else if ( $report->isValid() ) {
			
			$items = $report->getResults();
			
			$json = json_encode( $items );

			switch ( $mode ) {

				case "json":
					header ( 'Content-Type: application/json' );
					echo $json;
					break;

				case "csv":
					json2csv( $json, $reportId );
					break;

				case "xls":
					header("Content-disposition: attachment; filename=$reportId.xls");
					header("Content-Type: application/vnd.ms-excel; charset=utf-8");
					
					arrXls( array_values($items), false, $rdata );
					break;

				default:
					$itemCount = count($report->getResults());

					$results = new HtmlDoc(array(
						"content" => new Results($report),
						"pageTitle" => "(" . $itemCount . ") " . $report->getLabel(),
						"pageHeaderTitle" => $report->getName() . " <em class='bright'>($itemCount " . ($itemCount == 1 ? 'item' : 'itens') . ")</em>",
						"lang" => "pt"
					));

					$results->buildPage();

			}
			
		} else {

			header('Location: /' . basename(getcwd()));

		}
		
	}
?>