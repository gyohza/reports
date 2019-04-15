<?php

class ErrScreen extends HtmlContent
{
	
	private $report;

	public function __construct($report)
	{

		if ($report instanceof Report) $this->report = $report;
		else throw new RuntimeException("Not a valid Report instance.");

	}

	public function echoContent()
	{
	?>
        <style>
            #pageHeader {
                font-size: 8em;
                line-height: 0px;
            }
            #subtitle {
                color: var(--salmon);
            }
            #errScreen {
                border-radius: 15px;
                margin-top: 30px !important;
                padding: 30px 50px;
                color: #fff;
                text-shadow: 2px 2px 1px #2228;
                background-color: var(--faint-sky);
                transform: rotate(0deg);
                animation: awkwardspin 0.7s;
            }
            @keyframes awkwardspin {
                0% { transform: rotate(0deg); }
                55% { transform: rotate(364deg); }
                75% { transform: rotate(365deg); }
                100% { transform: rotate(360deg); }
            }
            #back {
                display: inline-block;
                margin: auto;
            }
        </style>
        <h1 id="subtitle">Your request has been blown to smithereens.</h1>
        <div id="errScreen">
            <h3><?= $this->report->getErr() ?></h3>
        </div>
        <button id="back" onclick="window.history.back()">Go back</button>
	<?php
	}

}