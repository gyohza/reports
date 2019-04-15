<?php

class Browse extends HtmlContent
{

    private $reports;

    private $lists;

    public function __construct()
    {
                
        $this->reports = array_filter(scandir("queries/"), function($v) { return preg_match("/^.+\.json$/", $v); });

        $this->lists = array(
            "categories" => array(),
            "keywords" => array(),
            "names" => array()
        );

    }

    public function echoContent()
    {
    ?>

        <style>
            #filters, #client {
                background-color: var(--valencia);
                padding: 24px;
            }
            #filters {
                display: grid;
                border-radius: 5px 5px 0px 0px;
                grid-template-columns: auto auto auto;
                padding-bottom: 3px;
            }
            #client {
                text-align: center;
                border-radius: 0px 0px 5px 5px;
                grid-template-columns: auto auto;
                padding-top: 3px;
            }
            #filters > label {
                font-size: 1.3em;
                padding: 3px;
                margin: 0px 6px;
                border-radius: 5px 5px 0px 0px;
                background-color: var(--crater);
                color: var(--wisp);
            }
            #filters > input {
                margin: 6px;
                padding: 4px;
                border-radius: 0px 0px 5px 5px;
                background-color: var(--wisp);
                font-size: 1.1em;
                color: var(--russian);
                text-align: center;
            }
            #client > * {
                display: inline-block;
                margin: 0px 1px;
                padding: 4px 20px;
            }
            #client > label {
                border-radius: 5px 0px 0px 5px;
                font-size: 1.3em;
                background-color: var(--crater);
                color: var(--wisp);
            }
            #client > input {
                border-radius: 0px 5px 5px 0px;
            }
            #apiKey:invalid {
                color: var(--valencia);
            }
            #reps {
                margin-top: 0px !important;
            }
            #btnpane {
                margin-top: 20px;
                padding: 24px;
                border-radius: 5px;
                display: flex !important;
                flex-direction: row;
                flex-wrap: wrap;
                align-items: stretch;
                background-color: var(--salmon);
            }
            #btnpane > button {
                box-sizing: border-box;
                flex-grow: 1;
                min-width: 28%;
                max-width: calc(50% - 12px);
                margin: 6px;
                padding: 5px 12px;
                font-size: 1.1em;
                overflow: hidden;
                white-space: nowrap;
                text-overflow: ellipsis;
                border-radius: 3px;
                color: var(--wisp);
                border-color: var(--faint-valencia);
                background-color: var(--valencia);
            }
            #btnpane > a:active {
                border-style: inset;
            }
            .invisible {
                display: none;
            }
        </style>
        <form onchange="applyFilters();">
            <div id="filters">
                <label for="category">Categoria</label>
                <label for="keywords">Palavra-chave</label>
                <label for="name">Nome</label>
                <input type="search" id="category" list="categories"/>
                <input type="search" id="keyword" list="keywords"/>
                <input type="search" id="name" list="names"/>
            </div>
        </form>
        <form id="reps" action="">
            <div id="client">
                <label for="apiKey">Chave de acesso</label>
                <input type="text" pattern="\w+" id="apiKey" name="apiKey"/>
            </div>
            <div id="btnpane">
                <?php
            
                    foreach ($this->reports as $r) {
            
                        $meta = json_decode(file_get_contents('queries/' . $r), true);
            
                        if (!isset($meta['indexed']) || !$meta['indexed']) continue;
            
                        if (isset($meta['name'])) {
                            $nameLong = $meta['name'];
                            $name = strtolower($nameLong);
                            if (!in_array($name, $this->lists['names'])) array_push($this->lists['names'], $name);
                        } else continue;
            
                        if (isset($meta['category'])) {
                            $cat = $meta['category'];
                            if (!in_array($cat, $this->lists['categories'])) array_push($this->lists['categories'], $cat);
                        } else $cat = "";
            
                        if (isset($meta['keywords'])) {
                            $meta['keywords'] = array_map(function($v) {
                                return preg_replace("/\W/", "", preg_replace("/\s/", "-", "_$v"));
                            }, $meta['keywords']);
                            $key = implode(" ", $meta['keywords']);
                            foreach ($meta['keywords'] as $v) {
                                if (!in_array($v, $this->lists['keywords'])) array_push($this->lists['keywords'], $v);
                            }
                        } else $key = "";

                        echo "<button type='button' data-action='" . str_replace('.json', '', $r)
                            . "' data-name='$name' data-category='$cat' class='$key'>$nameLong</button>";
            
                    }
            
                    foreach ($this->lists as $k => $v) {
                        $v = array_filter($v);
                        asort($v);
                        echo "<datalist id='$k'>" . implode("", array_map(function($opt) {
                            return "<option value='$opt'/>";
                        }, $v)) . "</datalist>";
                    }
            
                ?>
            </div>
        </form>
        <script>
            function applyFilters() {
                for (btn of document.getElementById('btnpane').getElementsByTagName('button')) {
                    let c = document.getElementById('category').value, k = document.getElementById('keyword').value, n = document.getElementById('name').value;
                    btn.classList.toggle('invisible', !(!(c.length || k.length || n.length) ^
                        (c.length && btn.dataset.category == c
                        || k.length && btn.classList.contains(k)
                        || n.length && btn.dataset.name.includes(n))));
                }
            }
            function updateAction(e) {
                document.forms['reps'].action = 'query/' + e.target.dataset.action;
                let apiKey = document.getElementById('apiKey').value.trim();
                if (apiKey.length) {
                    document.getElementById('reps').action += `?apiKey=${apiKey}`;
                }
                document.forms['reps'].submit();
            }
            document.getElementById('apiKey').addEventListener('keydown', e => {
                if (e.keyCode === 13) e.preventDefault();
            });
            for (btn of document.forms['reps'].getElementsByTagName('button'))
                btn.addEventListener('click', updateAction);
        </script>
    <?php
    }

}