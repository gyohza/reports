<?php

class HtmlDoc {

    private $content;

    private $pageTitle;

    private $pageHeaderTitle;

    private $lang;

    public function buildPage() {
        ?>
            <!DOCTYPE html>
            <html lang="<?php echo $this->lang; ?>">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <meta http-equiv="X-UA-Compatible" content="ie=edge">
                <title>
                    <?php echo $this->pageTitle; ?>
                </title>
                <link rel="stylesheet" type="text/css" href="/reports/style.css">
            </head>
            <body>
                <div id="bgoverlay"/></div>
                <div id="content" style="text-align: center;">
                    <h1 style="margin: 0; padding: 50px 0px 10px 0px;">
                        <?php echo $this->pageHeaderTitle; ?>
                    </h1>
                    <br/>
                    <?php
                        echo $this->content->echoContent();
                    ?>
                </div>
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

?>