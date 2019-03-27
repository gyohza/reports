<?php

	require_once dirname(__FILE__) . "/classes/_loader.php";
	
	$mode = isset($_GET['mode']) ? trim ( $_GET['mode'] ) : "table";
	$repAlias = isset($_GET['report']) ? trim($_GET['report']) : false;
	
	if (!$repAlias) {
		
		$browse = new HtmlDoc(array(
			"content" => new Browse(),
			"pageTitle" => "Explorar Relatórios",
			"pageHeaderTitle" => "Explorar Relatórios",
			"lang" => "pt"
		));
		
		$browse->echoSelf();
		
	} else {
		
		$report = new Report($repAlias);
		
		if ( $mode == 'query' && $report->isValid() ) {

			$query = new HtmlDoc(array(
				"content" => new Query($report),
				"pageTitle" => $report->getName(),
				"pageHeaderTitle" => "Configurar Relatório - " . $report->getName(),
				"lang" => "pt"
			));

			$query->echoSelf();

		} else if ( $report->isValid() ) {
			
			$items = $report->getResults();
			
			$json = json_encode( $items );

			switch ( strtolower($mode) ) {

				case "json":
					(new Output($report))->toJSON();
					break;

				case "csv":
					(new Output($report))->toCSV();
					break;

				case "xls":
					(new Output($report))->toXLS();
					break;

				case "table":
					$itemCount = count($report->getResults());

					$results = new HtmlDoc(array(
						"content" => new Results($report),
						"pageTitle" => "(" . $itemCount . ") " . $report->getLabel(),
						"pageHeaderTitle" => $report->getName() . " <em class='bright'>($itemCount " . ($itemCount == 1 ? 'item' : 'itens') . ")</em>",
						"lang" => "pt"
					));

					$results->echoSelf();
					break;
					
				default:
					header('Location: /' . basename(getcwd()));

			}
			
		} else {
			
			header('Location: /' . basename(getcwd()));

		}
		
	}