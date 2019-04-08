# reports

*<h4 align="center">Simple implementation of MySQL query browser, viewer and API.</h4>*

Have you always wondered why aren't building APIs or customizable reports as simple as building a MySQL query? By writing a query and configuring a simple JSON file, you can build an exportable table / API on the fly that is ready to use.

Queries and their result sets are collectively called **reports** within this project.

## Table of Contents
1. [Setup](#TOC_1)
	1. [Database configuration file](#TOC_1_1)
		1. [FILE: `config/connections.json`](#TOC_1_1_1)
	2. [Clients and API keys](#TOC_1_2)
		1. [FILE: `clients/SOME_API_KEY_123456.json`](#TOC_1_2_1)
	3. [Report configuration files](#TOC_1_3)
		1. [FILE: `queries/report_alias.json`](#TOC_1_3_1)
		2. [FILE: `queries/myFirstQuery.sql`](#TOC_1_3_2)
2. [Basic Usage](#TOC_2)
	1. [URL structure](#TOC_2_1)
		2. [URL breakdown](#TOC_2_1_1)
3. [Further Trickery](#TOC_3)
4. [Development Status](#TOC_4)
	1. [Future](#TOC_4_1)
5. [Disclaimer](#TOC_5)

## <span id="TOC_1">Setup</span>
Configurations lie in JSON files. These are:

### <span id="TOC_1_1">Database configuration file</span>
#### <span id="TOC_1_1_1">FILE: `config/connections.json`</span>
```json
{

	"db1alias": {
		"servername": "my.host.com",
		"username": "some_user",
		"password": "a_very_secret_password",
		"database": "my_db"
	},
	
	"another_alias": {
		"servername": "my_other_host",
		"username": "someone_elses_account",
		"password": "123456_its_that_obvious",
		"database": "holycow_db"
	},
	
	"mammalia": {
		"servername": "my.host.com",
		"username": "judy_hopps",
		"password": "zootropolis2015",
		"database": "dbtopia"
	}
	
}
```

### <span id="TOC_1_2">Clients and API keys</span>
As you will see below, a report may or may not be protected (i.e. a `whitelist` may be set). Reports with whitelisted `roles`[<sup>[1]</sup>](#NOTE_1_2_roles) will be blocked to whomever:
* Does not provide an API key at all;
* Does not provide a valid API key;
* Provides an API key that is not endowed with **at least one** of the required credentials (`roles`).

**<span id="NOTE_1_2_roles">[1]</span>** *`roles` are completely abstract - meaning they do not exist anywhere as true entities. If a role exists in both `queries/some_report.json` and `clients/0123456789ABCDEF.json`, it means any one client providing the `0123456789ABCDEF` key will be able to successfully access report `some_report`.*

#### <span id="TOC_1_2_1">FILE: `clients/SOME_API_KEY_123456.json`</span>
In addition to `roles`, one may set IPs (`hosts`) allowed to use a specific API_KEY. Keep in mind that if a client must access reports from a *remote* network, you would have to whitelist their **public** IP. **Understand the implications before doing so**. Consider leaving it without a `hosts` list - if it is missing, then every IP will be allowed to use this API key.

A hypothetical `SOME_API_KEY_123456.json` could look like this: 
```json
{

    "roles": [
        "root",
        "admin",
        "mod"
    ],

    "hosts": [
        "172.22.0.2",
        "200.200.0.2"
    ]

}
```

### <span id="TOC_1_3">Report configuration files</span>
Each query has to have one mandatory `.json` file and **at least one** `.sql` file. All of them must be placed under `queries/`.

#### <span id="TOC_1_3_1">FILE: `queries/report_alias.json`</span>
In this example, `report_alias` is the [report's identifier](#TOC_2_1_1) in its url. This file should contain the report's metadata and a few data about the `.sql` files - like the desired connection's name within the `connections.json` file. See example:
```json
{

  "name": "My Report's Name",

  "category": "Example",

  "description": "This is an example report.",

  "authors": [
    "john.doe@example.com"
  ],

  "maintainers": [
    "john.doe@example.com",
    "ed.siluapda@example.com"
  ],

  "keywords": [
    "WholeEnchilada",
    "Moonspiracy"
  ],

  "indexed": true,

  "whitelist": [
    "admin",
    "mod"
  ],

  "queries": [
    {
      "conn": "db1alias",
      "query": "myFirstQuery.sql",
      "key": "a_unique_id",
      "params": {
        "start_date": "2019-01-01",
        "end_date": "2019-03-01"
      }
    }
  ]
}
```

If you wish to make your report listed in the application's home screen, set `"indexed": true`.

***Note:** the `key` attribute inside each query **must** be provided. It should be a result column from the query which will work as a unique identifier. **In other words, no duplicates will be allowed or they will be truncated!***

#### <span id="TOC_1_3_2">FILE: `queries/myFirstQuery.sql`</span>
This can be any query - with the added option to give it preset parameters. These parameters **must** be declared in `report_alias.json queries > params`. It is declared as a `"param": "value"` pair, where `"param"` will be set as a variable inside the `.sql` file and `"value"` is the param's default value in case none is provided.
```mysql
select
  id as a_unique_id, -- results WILL be grouped by these values, a_unique_id is the query's KEY (see .json).
  price,
  created_at as date
from
  order od
left join
  customer cust
    on cust.id = od.customer_id
where
  created_at between @start_date and @end_date -- Place vars wherever you want parameters to be placed.
  /* See what I did there? The vars will either be provided
  	or default will be pulled from the .json file instead. */
```

## <span id="TOC_2">Basic Usage</span>
The application's main page (`index.php`) will list every report marked with the `"indexed": true` attribute. Whenever `"indexed"` is unset, the report will **not** be displayed by default.

Listed reports can be filtered by `category`, `keyword` and `name` - all which are set in the report's `.json` file.

Clicking a report will redirect the user to its **`query/`** page. Check out more below.

### <span id="TOC_2_1">URL structure</span>

#### `http`://`yourdomain.com`/`reports`/`[mode/]` `reportAlias` `[?apiKey=YOUR_KEY]` `[&param1=val1]` `[&paramN=valN]` `[...]`

#### <span id="TOC_2_1_1">URI breakdown</span>
Some of these are so obvious and commonplace that wouldn't even need mention, but I'll include them just in case:
1. `http` - Your application protocol. Usually `http` or `https`;
2. `yourdomain.com` - Usually `localhost` when testing from your own machine, an IP or DNS;
3. `reports` - Name of the project's directory under your `/var/www/html/` or `htdocs/` directory;
4. `mode/` - Optional. If not provided, it will default to `table/`. Available modes are:
	* `query/` - Allows user to see what parameters can be customized for this particular report and redirects them to `table/` when they're finished; 
	* `table/` - Displays query results on screen (either using GET parameters or default parameters if present);
	* `json/` - Converts results into a `.json` file. **Note:** the JSON file will be an associative array, whose keys are its latest query's defined key column and whose values will be the result set rows;
	* `csv/` - Converts results into a `.csv` file. It'll be a standard comma-separated file, with `sep=,` header to avoid conflicts with countries that use [decimal comma](https://en.wikipedia.org/wiki/Decimal_separator#Countries_using_Arabic_numerals_with_decimal_comma).
	* `xls/` - Converts results into a `.xls` file.
5. `reportAlias` - Your report's alias (AKA name of its `.json` file, file extension excluded);
6. `?apiKey=YOUR_KEY`[<sup>[1]</sup>](#NOTE_2_1_1_getparams) - API key, in which `YOUR_KEY` must be the name (file extension excluded) of an existing `.json` file under `clients/`;
7. `[&param1=val1]`, `[&paramN=valN]`, ...[<sup>[1]</sup>](#NOTE_2_1_1_getparams) - Parameters that will be provided as variables to the report's query.

**<span id="NOTE_2_1_1_getparams">[1]</span>** *Remember that in **any** URL on the web, the **first** GET parameter after the report's alias **must** be preceded by `?` instead of `&`. The following parameters should be identified with a preceding `&`.*

## <span id="TOC_3">Further Trickery</span>
> << Under construction! >>

<!--
	Coming soon:
		1. Meshing two queries, possibly from different databases;
		2. Including custom HTML in queries.
-->

## <span id="TOC_4">Development Status</span>
This project was started just as a small local solution and steadily grew into something else. There's still ongoing developments, lots of ideas to build upon and I'm still very open to suggestions.

If you have any complaints or suggestions, please be sure to drop by the Issues section and leave me some notes.

### <span id="TOC_4_1">Future</span>
* There have been complaints about the name of this project - "reports" is "uninspiring" - but no suggestions were made. I know for a fact that I suck at naming things, but it would be lovely if there were an early species of some sort that I could name after. Species such as *Tiktaalik*, *Acanthostega*, coelacanths, *Dunkleosteus*, *Ambulocetus* are of particular interest but obviously a mouthful, so maybe contracting the names would work, but potentially lose their meaning. [*Pikaia*](https://en.wikipedia.org/wiki/Pikaia) (an early [Burgess Shale](https://en.wikipedia.org/wiki/Burgess_Shale) [chordate](https://en.wikipedia.org/wiki/Chordate)) bears a compact and somewhat appealing name. But then again... does the name have to reflect the application's nature? I don't know.
* API keys have no daily/weekly/monthly limits (yet). I'll work on them as soon as I can;
* Creators and maintainers should be contacted by email when their name is clicked at `reports/table/reportAlias`. It does work... but it pops up a gmail tab instead of the usual `mailto:` protocol. I did it that way because my company uses G Suite and only a few people there had email clients installed. I will adapt it in the near future;
* I know XLS sucks and I'll add XLSX support soon. I'm gonna dig through PHP Spreadsheets documentation to see if there's any difference in the XLSX and XLS class structures and adapt my code if needed.

## <span id="TOC_5">Disclaimer</span>
I've been coding for quite a few years now, but haven't been too hot on git until very recently. This is officially my first public repo - I don't expect it to garner any attention at all because I suck at advertising, but I'm quite sure several sneaky mistakes and poor choices are out there to be found. As aforementioned, you're welcome to [**file an issue**](https://github.com/gyohza/reports/issues). 

I know this is cliché to the max, but I cannot be held responsible for anything... blah blah blah. Do not include this in life-supporting devices and you'll always be able to perform a rollback. Use it with your own discretion.

Also please don't sue me - I don't do plastic straws. :)
