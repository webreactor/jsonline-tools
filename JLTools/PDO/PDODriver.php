<?php

namespace JLTools\PDO;

use \Reactor\Database\PDO\Connection as Connection;
use \JLTools\Common\BaseController;

class PDODriver {

    function __construct($app) {
        $this->app = $app;
        $args = $this->app->arguments->getAll();
        $conn_str = $this->buildConnectionString($args);
        $this->app->userMessage("Connection: $conn_str User:{$args['user']}");
        $this->connection = new Connection($conn_str, $args['user'], $args['password']);
    }

    function buildConnectionString($options) {
        $str = $options['driver'].':';
        $available = array(
            'host'          => false,
            'port'          => false,
            'unix_socket'   => false,
            'charset'       => false,
            'dbname'        => false,
        );
        $ready_options = array();
        foreach ($options as $key => $value) {
            if (isset($available[$key]) && $value != '' ) {
                $ready_options[$key] = "$key=$value";
            }
        }
        if (isset($ready_options['unix_socket'])) {
            unset($ready_options['host']);
            unset($ready_options['port']);
        }
        return $str.implode(';', $ready_options);
    }

    function sql($query, $args = array()) {
        $r = $this->connection->sql($query, $args);
        $s = $r->getStats();
        $this->app->userMessage("{$s['query']} Time: {$s['execution_time']}");
        return $r;
    }

    function dumpQuery($args) {
        if (isset($args['last_run'] )) {
            return array();
        }
        if ($args['buffer'] == 0) {
            $args['last_run'] = true;
            return $this->sql($args['query']);
        }
        $auto_add = '';
        if ($args['key'] !== '') {
            $auto_add .=  " WHERE {$args['key']} > {$args['from']} ORDER BY {$args['key']} LIMIT {$args['buffer']}";
        } else {
            $auto_add .=  " LIMIT {$args['from']}, {$args['buffer']}";
        }
        $r = $this->sql($args['query'].$auto_add);
        return $r;
    }

    function dumpQueryStream($args) {
        $args = new \ArrayObject($args);
        return new PDOStream(function() use ($args) {
            try {
                return $this->dumpQuery($args);
            } catch (\Exception $e) {
                $this->app->userMessage("Error: ".$e->getMessage());
            }
            return array();
        }, $args);
    }
}




