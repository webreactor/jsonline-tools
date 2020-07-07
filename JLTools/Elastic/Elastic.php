<?php

namespace JLTools\Elastic;

use \Reactor\HttpClient as HttpClient;

class Elastic {

    function __construct($app) {
        $this->app = $app;

        $this->defaults = array(
            'index' => '',
            'sort' => '@timestamp',
            'total' => 0,
            'counter' => 0,
            'on_page' => 100,
            'source' => false,
            'limit' => 0,
        );
    }

    function connect($connection) {
        $this->client = new HttpClient\HttpClient(rtrim($connection, '/').'/', array(\CURLOPT_TIMEOUT => 10));
        $this->client->pushMiddleware(new HttpClient\Middleware\JsonBody());
    }

    function queryElastic($method, $url, $get = array(), $post = null) {
        $r = $this->client->exec($method, $url, $get, $post);
        $this->app->userMessage("{$method} {$r['info']['url']}", $post);
        if ($r['info']['generic_code'] != '2xx') {
            $error_message = $r['info']['code'].' '.$r['info']['generic_code_message'];
            if (!empty($r['response_data'])) {
                $this->app->errorMessage($error_message, $r['response_data']);
            } else {
                $this->app->errorMessage("$error_message body> {$r['response_body']}\n");
            }
            die(1);
        }
        if (!isset($r['response_data']) && $method !== 'DELETE') {
            $this->app->errorMessage("unexpected response body> {$r['response_body']}\n");
            die(1);
        }
        // $this->app->userMessage("Elastic: ", $r['response_data']);
        return $r['response_data'];
    }


    function getMatchingIndexes($index_match) {
        $r = $this->queryElastic('GET', $index_match.'/_settings/index.provided_name');
        $r = array_keys($r);
        sort($r);
        return $r;
    }

    function dumpEachIndex($args) {
        do {
            if (!isset($args['index_list'][$args['current_index']])) {
                $this->app->userMessage("Done");
                return array();
            }
            $index = $args['index_list'][$args['current_index']];
            $r = $this->scrolledSearch($index, $args);
            if ($r['hits']['hit_size'] < $args['buffer']) {
                $args['current_index']++;
                if ($r['hits']['hit_size'] > 0) {
                    break;
                }
            } else {
                break;
            }
        } while (true);
        $data = $r['hits'];


        $rez = array();
        if ($args['source'] === true) {
            foreach ($data['hits'] as $line) {
                $rez[] = $line['_source'];
            }
        } else {
            $rez = $data['hits'];
        }
        return $rez;
    }

    function dumpIndex($args) {
        if (isset($args['last_round'])) {
            $this->app->userMessage("Done");
            return array();
        }
        $r = $this->scrolledSearch($args['index_match'], $args);
        if ($r['hits']['hit_size'] < $args['buffer']) {
            $args['last_round'] = true;
        }
        $data = $r['hits'];
        $rez = array();
        if ($args['source'] === true) {
            foreach ($data['hits'] as $line) {
                $rez[] = $line['_source'];
            }
        } else {
            $rez = $data['hits'];
        }
        return $rez;
    }



    function dumpIndexStream($args) {
        $args = array_merge($this->defaults, $args);
        $args = new \ArrayObject($args);
        $this->connect($args['server']);
        $this->app->userMessage("Settings", $args);
        $args['index_match'] = rawurlencode(implode(',', $args['index']));


        if ($args['mode'] == 'each') {
            $args['index_list'] = $this->getMatchingIndexes($args['index_match']);
            $args['current_index'] = 0;
            $this->app->userMessage("Searching over indexes: ", $args['index_list']);
            return new ElasticStream(function() use ($args) {
                return $this->dumpEachIndex($args);
            }, $args);
        } 

        if ($args['mode'] == 'all') {
            $args['total'] = $this->countSearch($args);

            $this->app->userMessage("Found {$args['total']} records");
            if ($args['total'] > 0) {
                return new ElasticStream(function() use ($args) {
                    return $this->dumpIndex($args);
                }, $args);
            }
        }
        $this->app->userMessage("Unknown mode {$args['mode']}");
        return array();
    }


    function countSearch($args) {
        $query = $this->buildQuery($args);
        unset($query['sort']);
        unset($query['size']);
        $r = $this->queryElastic('GET', $args['index_match'].'/_count', array(), $query);
        if (!isset($r['count'])) {
            return 0;
        }
        return $r['count'];
    }

    function buildQuery($args) {
        if ($args['query'][0] == '{') {
            return array(
                'size' => $args['buffer'],
                'sort'=> $args['sort'],
                "query" => json_decode($args['query']),
            );
        }
        return array(
            'size' => $args['buffer'],
            'sort'=> $args['sort'],
            "query" => array(
                "query_string" => array(
                    "query" => $args['query']
                )
            )
        );
    }

    function scrolledSearch($index, $args) {
        
        $this->app->userMessage("Query index: {$index}");

        if (isset($args['_scroll_id'])) {
            $r = $this->queryElastic('GET', '_search/scroll', array(), array(
                'scroll' => '1m',
                'scroll_id'=> $args['_scroll_id'],
            ));
        } else {
            $r = $this->queryElastic('GET', $index.'/_search', array(
                'scroll'=> '1m',
            ), $this->buildQuery($args));
        }

        // handling switched scroll id
        if (isset($r['_scroll_id'])) {
            if (isset($args['_scroll_id']) && $args['_scroll_id'] !== $r['_scroll_id']) {
                $this->cleanScroll($args);
            }
            $args['_scroll_id'] = $r['_scroll_id'];
        }

        $r['hits']['hit_size'] = count($r['hits']['hits']);
        if ($r['hits']['hit_size'] < $args['buffer']) {
            $this->cleanScroll($args);
        }
        $args['counter'] += $r['hits']['hit_size'];

        $left_msg = "";
        if ($args['total'] > 0) {
            $left_msg = " Left: ".($args['total'] - $args['counter']);
        }
        $this->app->userMessage("Found: {$r['hits']['hit_size']} Total: {$args['counter']}".$left_msg);

        return $r;
    }

    function cleanScroll($args) {
        if (isset($args['_scroll_id'])) {
            $r = $this->queryElastic('DELETE', '_search/scroll', array(), array(
                'scroll_id'=> array(
                    $args['_scroll_id']
                ),
            ));
            unset($args['_scroll_id']);
        }
    }

}
