<?php

class Query extends HtmlContent
{
	
	private $report;

	public function __construct($report)
	{

		if ($report instanceof Report) $this->report = $report;
		else throw new RuntimeException("Not a valid Report instance.");

	}
	
	public function buildTable()
	{
		
		$filteredParams = 
			array_map(function($v) { return $v['params']; },
				array_filter($this->report->get('queries'), function($q) {
					return isset($q['params']);
				})
			);

		if (!count($filteredParams)) return "";

		$params = array_merge(...$filteredParams);
		
		array_walk($params, function(&$v, $k) {
			$v = "<tr><th><label for='$k'>$k</label></th>"
			."<td><input name='$k' value='$v'/></td></tr>";
		});
		
		return implode("", $params);
		
	}

	public function echoContent()
	{
	?>
		<form method="GET" action="../table/<?php echo $this->report->getAlias(); ?>" style="margin: auto;">
			<div>
				<table>
					<thead>
						<th>
							Parâmetro
						</th>
						<th>
							Valor
						</th>
					</thead>
					<tbody>
						<?php
							echo $this->buildTable();
						?>
						<tr>
							<td colspan="2">
								<input type="submit" value="Gerar relatório"/>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</form>
	<?php
	}

}