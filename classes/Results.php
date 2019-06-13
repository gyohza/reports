<?php

class Results extends HtmlContent
{
	
	private $report;
	
	private $items;

	public function __construct($report)
	{

		if ($report instanceof Report) $this->report = $report;
		else throw new RuntimeException("Not a valid Report instance.");

		$this->items = $report->getResults();
		
	}

	public function assembleTbl()
	{

		$output = "";

		if ( count($this->items) ) {
			
			$headers = array_keys($this->items[key($this->items)]);
			
			foreach ( $this->items as $row ) {
				$output .= "<tr><td>" . implode('</td><td>', $row) . "</td></tr>";	
			}

			$output = "<div id='tblContainer'><table id='results'><thead><tr><th>"
				. implode('</th><th>', $headers) .
				"</th></tr></thead><tbody> $output </tbody></table></div>";

		} else {

			$output =  "<p><em>No results to show.</em></p>";

		}

		return $output;

	}
	
	public function echoContent()
	{
	?>
		<style>
			#content {
				max-width: 96vw;
			}
			#export {
				position: fixed;
				margin: 0px !important;
				display: grid !important;
				top: 20px !important;
				right: 20px !important;
			}
			#export > * {
				min-width: 0px;
			}
			input[name='mode'] {
				opacity: 0.9;
				cursor: pointer;
				transform: none;
				border-color: var(--faint-valencia);
				border-radius: 5px;
				margin: 2px;
				background-color: var(--valencia);
				box-shadow: none;
				transition: transform 0.2s, color 0.2s, opacity 0.2s;
				width: 90px;
			}
			input[name='mode']:hover {
				color: #fff;
				opacity: 1;
				transform: scale(1.05, 1.05);
			}
			#tblContainer {
				box-sizing: border-box;
				overflow: auto;
			}
			#results {
				margin: auto !important;
			}
			#results > thead > tr > th {
				background-color: var(--valencia);
				position: sticky;
				top: 2px;
			}
			#results > tbody {
				overflow-y: auto;
			}
			#results > tbody > tr > td > table {
				display: block;
				margin: auto;
				overflow: hidden;
				visibility: hidden;
				max-height: 10px;
			}
			#results > tbody > tr > td > table::before {
				display: inline-block;
				position: relative;
				transform: translateY(-50%);
				content: "...";
				font-weight: bold;
				font-size: 24px;
				color: var(--russian);
				visibility: visible;
				border-radius: 9px;
				height: 100%;
				width: 20%;
			}
			#results > tbody > tr > td:not(.collapsed) {
				max-width: none;
			}

			#results > tbody > tr > td:not(.collapsed) > * {
				display: block !important;
				overflow: auto !important;
				visibility: visible !important;
				max-height: none !important;
			}
			#results > tbody > tr > td:not(.collapsed) > table {
				border: 3px dashed var(--valencia);
				cursor: pointer;
			}
			#results > tbody > tr > td:not(.collapsed) > table::before {
				display: none;
			}
			#results > tbody > tr:hover {
				--opacity-level: 0.2;
				background-color: var(--faint-valencia);
			}
		</style>
		<div class="left" id="customize" style="--ind: 1;">
			<a style="background-image: url(/<?= basename(getcwd())?>/img/baseline_search_black_36dp.png);"
				href="/<?= basename(getcwd()) . '/query/' . $this->report->getAlias() ?>">Refazer pesquisa</a>
		</div>
		<form id="export">
		<?php
			foreach ( $this->report->get('queries') as $k => $v ) {
				if ( isset($v['params']) ) {
					foreach ( $v['params'] as $k => $v ) {
						if (isset($_GET[$k])) echo "<input name='$k' value='{$_GET[$k]}' type='hidden' />";
					}
				}
			}
		?>
			<input name="mode" type="submit" value="xlsx"/>
			<input name="mode" type="submit" value="csv"/>
			<input name="mode" type="submit" value="txt"/>
			<input type="hidden" name="apiKey" value="<?= (isset($_GET['apiKey']) ? $_GET['apiKey'] : '' ) ?>" />
		</form>

		<?php
			
			$addressees = implode( ',', array_unique(array_merge($this->report->get('authors'), $this->report->get('maintainers'))) );
			
			$maintainers = $this->report->get('maintainers') ? implode( ", ",
				array_map( function( $cur ) use ($addressees) {
					return "<a href='https://mail.google.com/mail/?view=cm&fs=1&to=$cur&su={$this->report->getName()}&body=http://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}%0A%0A&cc=$addressees'>"
					. ucwords( str_replace( ".", " " , preg_replace( "/@.*/", "", $cur ) ) ) . "</a>";
				}, $this->report->get('maintainers') )
			) : "";
			
			$authors = $this->report->get('authors') ?
				implode( ", ",
					array_map( function( $cur ) use ($addressees) {
						return "<a href='https://mail.google.com/mail/?view=cm&fs=1&to=$cur&su={$this->report->getName()}&body=http://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}%0A%0A&cc=$addressees'>"
						. ucwords( str_replace( ".", " " , preg_replace( "/@.*/", "", $cur ) ) ) . "</a>";
					}, $this->report->get('authors') )
				) : "";
				
			$credits = (
						!( strlen( $maintainers ) ||  strlen( $authors ) ) ? "" :
						$this->report->get('maintainers') == $this->report->get('authors') ? "<small>Relatório criado e mantido por $maintainers.</small>" :
						"<small>Relatório " . implode( " e ", array( strlen( $authors ) ? "criado por $authors" : null, strlen( $maintainers ) ? "mantido por $maintainers" : null)) . ".</small>"
					);
			
			echo $credits . $this->assembleTbl();
			
		?>
		
		<script>
			for (elem of document.querySelectorAll('#results > tbody > tr > td > table')) {
				elem.parentNode.classList.add('collapsed');
				elem.parentNode.addEventListener('click', (e) => {
					e.currentTarget.classList.toggle('collapsed');
				});
			}
		</script>
	
	<?php
	}
	
}