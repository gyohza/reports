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
		
		$filteredParams = $this->report->getParams();

		$params = array_merge(...$filteredParams);

		array_walk($params, function(&$v, $k) {

			if (is_array($v)) {

				$str = "<tr><th><label for='$k'>$k</label></th>"
					. "<td><select name='$k' required>";
				foreach ($v as $lbl => $val) {
					$str .= "<option value='$val'>$lbl</option>";
				}
				$str .= "</select>";

				$v = $str;

			} else {
				$v = "<tr><th><label for='$k'>$k</label></th>"
				."<td><input name='$k' value='$v'/></td></tr>";
			}

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
						<?= $this->buildTable() ?>
						<tr>
							<td colspan="2">
								<input type="submit" value="Gerar relatório"/>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
			<input type="hidden" name="apiKey" value="<?= (isset($_GET['apiKey']) ? $_GET['apiKey'] : '' ) ?>" />
		</form>
	<?php
	}

}