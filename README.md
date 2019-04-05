# reports

<h5 align="center"><em>Simple implementation of MySQL query browser, viewer and API.</em></h5>

Have you always wondered why aren't building APIs or customizable reports not as simple as building a MySQL query? By writing a query and configuring a simple JSON file, you can build an exportable table / API that is ready to use.

A query and its result set compose a kind of entity called **report** within this app.

## Setup
Configurations lie in JSON files. These are:

### Database configuration file
#### File `config/connections.json`
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

### Report configuration files
Each query has to have one mandatory `.json` file and **at least one** `.sql` file. All of them must be placed under `queries/`.

#### File `queries/report_alias.json`
In this example, `report_alias` is the report's identifier in its url. This file should contain the report's metadata and a few data about the `.sql` files - like the desired connection's name within the `connections.json` file. See example:
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
    "mods"
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
*Note that the `key` attribute inside each query **must** be provided. It should be a result column from the query which will work as a unique identifier. **In other words, no duplicates will be allowed or they will be truncated!***

#### File `myFirstQuery.sql`
This can be any query - with the added option to give it a preset parameter. This parameter **must** be declared in `report_alias.json queries > params`. It is declared as a key: value pair, where key will be set as a variable inside the `.sql` file and the value is the default value if none is provided.
```mysql
select
  id as a_unique_id, /* the results will be grouped by these values, because the column is set as the query's key. */
  price,
  created_at as date
from
  order od
left join
  customer cust
    on cust.id = od.customer_id
where
  created_at between @start_date and @end_date
  /* See what I did there? The vars will either be provided or default from the .json file will be used instead. */
```
