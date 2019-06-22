<?php

class HtmlDoc
{

    private $content;

    private $pageTitle;

    private $pageHeaderTitle;

    private $lang;

    public function echoSelf()
    {
        ?>
            <!DOCTYPE html>
            <html lang="<?= $this->lang ?>">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <meta http-equiv="X-UA-Compatible" content="ie=edge">
                <title>
                    <?= $this->pageTitle ?>
                </title>
                <link rel="stylesheet" type="text/css" href="/<?= basename(getcwd())?>/style.css">
            </head>
            <body>
                <div id="bgoverlay"/></div>
                <div class="left" id="home" style="--ind: 0;">
                    <a style="background-image: url(/<?= basename(getcwd())?>/img/baseline_home_black_36dp.png);"
                        href="/<?= basename(getcwd())?>"><?= basename(getcwd())?></a>
                </div>
                <div id="content" style="text-align: center;">
                    <h1 id="pageHeader">
                        <?= $this->pageHeaderTitle ?>
                    </h1>
                    <?= $this->content->echoContent() ?>
                </div>
                <script src="/<?= basename(getcwd())?>/js/k.min.js"></script>
            </body>
            </html>
        <?php
    }

    public function __construct( $config =
        array(
            "content" => null,      /* Path to the file with desired contents. */
            "pageTitle" => null,        /* Content of <title> tag. */
            "pageHeaderTitle" => null,  /* Content of <h1> headline. */
            "lang" => null,             /* lang attribute of <html> wrapper tag. */
        )
    ) {
        if (!($config['content']) instanceof HtmlContent) throw new RuntimeException("Invalid page content.");
        $this->content = $config['content'];
        $this->pageTitle = isset($config['pageTitle']) ? $config['pageTitle'] : "";
        $this->pageHeaderTitle = isset($config['pageHeaderTitle']) ? $config['pageHeaderTitle'] : "";
        $this->lang = isset($config['lang']) ? $config['lang'] : "en";
    }

}