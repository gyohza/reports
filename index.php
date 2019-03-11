<?php
	require './utils/arrayTools.php';
	require './utils/mysqlPDO.php';
	
	$interval = false;
	$email = false;
	$mode = isset($_GET['mode']) ? trim ( $_GET['mode'] ) : "table";
	$report = isset($_GET['report']) ? $_GET['report'] : false;
	
	if (!$report) {
		
		echo "<!doctype html><html><head><title>oops</title><script src='scripts/k.min.js'></script></head><body>No reports passed.</body></html>";
		
	} else {
		
		try{
			$rdata = json_decode ( file_get_contents( "./queries/{$_GET['report']}.json" ), true );
			
			$report = $rdata['name'];
			
			$exists = !json_last_error();
			
			$style = file_get_contents( "./style.css" );
		} catch ( Exception $e ) {
			$exists = false;
		}
		
		if ( $mode == 'browse' && $exists) {
			?>
			<!doctype html>
			<html>
				<head>
					<link rel='icon' href='../../favicon.png' sizes='16x16' type='image/ico'/>
					<meta charset='UTF8'/>
					<style><?php echo $style; ?></style>
					<title>
						Configurar Relatório - <?php echo "$report" ?>
					</title>
				</head>
				<body class='black'>
					<center class='fullscreen'>
						<form method="GET" class="center">
							<input type='hidden' name='mode' value='table'/>
							<div class="grey core">
								<h1 style="margin: 0; padding-top: 10px; color: var(--black);">
									<?php echo $report; ?>
								</h1>
								<table>
									<?php
										foreach ( $rdata['queries'] as $k => $v ) {
											if ( isset($v['params']) ) {
												$prm = $v['params'];
												foreach ( $prm as $p => $d ) {
													echo "<tr><th><label for='$p'>$p</label></th>"
														."<td><input name='$p' value='$d'/></td></tr>";
												}
											}
										};
									?>
									<tr>
										<td colspan="2">
											<input class="fullwidth orange" type="submit" value="Gerar relatório"/>
										</td>
									</tr>
								</table>
							</div>
						</form>
					</center>
				</body>
			</html>
			<?php
		} else if (
			$exists
		) {
			
			$conns = json_decode(file_get_contents("./config/connections.json"), true);
			
			$mt = microtime(true);

			$u = str_pad( base_convert( round( ( $mt - floor($mt) ) * 10000000 ), 10, 36 ), 5, '0', STR_PAD_LEFT );
			
			$reportId = $report . " @ " . date_format( date_create(), "Y-m-d h-i-s" ) . " " . $u;
			
			// Defines the first key based on JSON's first query data
			$rkey = $rdata['queries'][0]['key'];
			
			// Creates the array that will be populated with query results
			$items	= array();
			
			// Loops through every query in the JSON file
			foreach ( $rdata['queries'] as $i => $q ){
				$conndata = $conns[$q['conn']];
				$servername	= $conndata['servername'];
				$username	= $conndata['username'];
				$password	= $conndata['password'];
				$database	= $conndata['database'];
				
				$query = "/* [ {$_SERVER['PHP_SELF']} - $reportId ] Requester IP: {$_SERVER['REMOTE_ADDR']} */" .	//
					file_get_contents( "./queries/" . $q['query'] );
				
				$params	= isset($q['params']) ? $q['params'] : array();
				
				foreach ( $params as $k => $v ) {
					if ( isset($_GET[$k]) ) $params[$k] = trim($_GET[$k]);
				}
						
				$rkey = $q['key'];
				
				if ( $i ) {
					
					$items = array_combine( array_column($items, $rkey), array_values( $items ) );
					$rows = runQuery ( $servername, $database, $username, $password, $query, $params, array_keys($items) );
					
				} else {
					
					$rows = runQuery ( $servername, $database, $username, $password, $query, $params, isset($_GET[$rkey]) ? explode( ",", $_GET[$rkey] ) : array() );
					
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
					if ( $i ){
						$items[$row[$rkey]] = array_merge( $items[$row[$rkey]], $row );

						foreach ( $items[$row[$rkey]] as $k => $v ) {
							if ( !strlen( $v ) && isset( $row[$k] ) ) $items[$row[$rkey]][$k] = $row[$k];
						}
					}
					else
						$items[$row[$rkey]] = $row;
				}
				
			}
			
			foreach ($items as $k => $v) {
				unset($items[$k]);
				foreach ($v as $header => $cell) {
					$items[utf8_encode($k)][utf8_encode($header)] = utf8_encode($cell);
				}
			}
			
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
					?>
					<!doctype html>
					<html>
						<head>
							<title>
								<?php echo '(' . count($items) . ') ' . $report; ?>
							</title>
							<link rel='icon' href='../../favicon.png' sizes='16x16' type='image/ico'/>
							<meta charset='UTF8'/>
							<style><?php echo $style; ?></style>
						</head>
						<body class='black'>
							<div id="bgoverlay"/></div>
							<center>
								<?php if (count($items)) { ?>
								<form id="export">
									<?php
										foreach ( $rdata['queries'] as $k => $v ) {
											if ( isset($v['params']) ) {
												foreach ( $v['params'] as $k => $v ) {
													if (isset($_GET[$k])) echo "<input name='$k' value='{$_GET[$k]}' type='hidden' />";
												}
											}
										}
									?>
									<input name="mode" type="submit" value="xls"/>
									<input name="mode" type="submit" value="csv"/>
								</form>
								<?php } ?>
								
								<h1 style='padding-top: 50px; --theme: var(--yellow);'>
									<?php echo $report . ' <em class="bright">(' . count($items) . ' ' . (count($items) == 1 ? 'item' : 'itens' ) . ')</em>' ; ?>
								</h1>
					<?php
						
						$addressees = implode( ',', array_unique(array_merge($rdata['authors'], $rdata['maintainers'])) );
						
						$maintainers = isset( $rdata['maintainers'] ) ? implode( ", ",
							array_map( function( $cur ) use ($report, $addressees) {
								return "<a href='https://mail.google.com/mail/?view=cm&fs=1&to=$cur&su=$report&body=http://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}%0A%0A&cc=$addressees'>"
								. ucwords( str_replace( ".", " " , preg_replace( "/@.*/", "", $cur ) ) ) . "</a>";
							}, $rdata['maintainers'] )
						) : "";
						
						$authors = isset( $rdata['authors'] ) ?
							implode( ", ",
								array_map( function( $cur ) use ($report, $addressees) {
									return "<a href='https://mail.google.com/mail/?view=cm&fs=1&to=$cur&su=$report&body=http://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}%0A%0A&cc=$addressees'>"
									. ucwords( str_replace( ".", " " , preg_replace( "/@.*/", "", $cur ) ) ) . "</a>";
								}, $rdata['authors'] )
							) : "";
							
						$credits = (
									!( strlen( $maintainers ) ||  strlen( $authors ) ) ? "" :
									$rdata['maintainers'] == $rdata['authors'] ? "<small>Relatório criado e mantido por $maintainers.</small>" :
									"<small>Relatório " . implode( " e ", array( strlen( $authors ) ? "criado por $authors" : null, strlen( $maintainers ) ? "mantido por $maintainers" : null)) . ".</small>"
								);
						
						echo $credits . arrTbl( array_values($items), "results");
						
					?>
								<script>
									for (elem of document.querySelectorAll('#results > tbody > tr > td > table')) {
										elem.parentNode.classList.add('collapsed');
										elem.parentNode.addEventListener('click', (e) => {
											e.currentTarget.classList.toggle('collapsed');
										});
									}
								</script>
							</center>
						</body>
					</html>
					<?php
			}
			
		}
		
	}
?>