<?php

class Client
{

    private $apiKey;

    private $limits;

    private function validateHost($host, $allowedHosts) {

        // Tests if client matches allowed hosts. General digit wildcards (*) are allowed (e.g.: 172.22.*.*).
        $matches = array_filter($allowedHosts, function($v) use ($host) {
            return preg_match('/^' . str_replace("*", "\d{1,3}", str_replace('.', '\.', $v)) . '$/', $host);
        });
        
        return $matches;

    }

    private function updateCount($meta) {

        // Loops through all units of time.
        foreach ($meta['limits'] as $u => &$v) {

            // Check if there is a limit bound to a given unit.
            if ($v['max']) {

                // If cycle has ended, resets the usage count for that unit of time.
                if (time() >= strtotime($v['ends'])) {
                    $v['count'] = 0;
                    $interval = date_interval_create_from_date_string('1 ' . $u);
                    $v['ends'] = date_format(date_add(new DateTime(), $interval), "Y-m-d H:i:s");
                }

                if ($v['count'] >= $v['max'])
                    throw new RuntimeException("You have exceeded your limit of requests per {$u}. Cycle ends at {$v['ends']}.");

                $v['count']++;

            }

        }

        file_put_contents("./clients/{$this->apiKey}.json", json_encode($meta, JSON_PRETTY_PRINT));

    }

    public function __construct($apiKey, $roles, $host)
    {

        if (!$apiKey) throw new RuntimeException("No <code>apiKey</code> parameter!\n\nThis is a protected report and no API key was supplied.");

        $this->apiKey = $apiKey;

        if (!file_exists("./clients/$apiKey.json")) throw new RuntimeException("Invalid API key provided.");

        // Loads client's metadata.
        $meta =  json_decode(file_get_contents("./clients/$apiKey.json"), true);

        // Blocks clients whose roles are not whitelisted in the report.
        if (!array_intersect($roles, $meta['roles'])) throw new RuntimeException("Supplied API key doesn't have the right credentials to access this report.");

        // Checks if the API key is bound to predetermined IPs. If filtered array has no matches, blocks client access to the report.
        if (isset($meta['hosts']) && !$this->validateHost($host, $meta['hosts'])) throw new RuntimeException("Client IP's doesn't match API key's allowed hosts list.");

        if (isset($meta['limits'])) $this->updateCount($meta);

    }

}