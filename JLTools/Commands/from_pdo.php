<?php

namespace JLTools\Commands;

use \Reactor\CliArguments\ArgumentsParser;
use \Reactor\CliArguments\ArgumentDefinition;
use \JLTools\PDO\PDODriver;

class from_pdo extends \JLTools\Common\BaseController {

    public $code = '';

    function __construct($app) {
        parent::__construct($app);
        $this->arguments->addDefinition(new ArgumentDefinition('_words_', '', '', false, true, 'SQL query'));
        $this->arguments->addDefinition(new ArgumentDefinition('driver', 'e', 'mysql', false, false, 'PDO driver'));
        $this->arguments->addDefinition(new ArgumentDefinition('host', 'h', 'localhost', false, false, 'Host'));
        $this->arguments->addDefinition(new ArgumentDefinition('port', 'P', '3306', false, false, 'Port'));
        $this->arguments->addDefinition(new ArgumentDefinition('user', 'u', '', false, false, 'User'));
        $this->arguments->addDefinition(new ArgumentDefinition('password', 'p', '', false, false, 'Password'));
        $this->arguments->addDefinition(new ArgumentDefinition('dbname', 'd', '', false, false, 'Database'));
        $this->arguments->addDefinition(new ArgumentDefinition('charset', 'c', 'uft8', true, false, 'Charset'));
        $this->arguments->addDefinition(new ArgumentDefinition('buffer', 'b', 0, false, false, 'Buffer, auto adds order by and limit'));
        $this->arguments->addDefinition(new ArgumentDefinition('from', 'f', 0, false, false, 'Skip records'));
        $this->arguments->addDefinition(new ArgumentDefinition('key', 'k', '', false, false, 'ID field for scroll window strategy'));
        $this->arguments->addDefinition(new ArgumentDefinition('unix_socket', 'u', '', false, false, 'Unix socket path'));
        $this->arguments->addDefinition(new ArgumentDefinition('limit', 'l', 0, false, false, 'Software limit on records - it will not create sql limit statement'));
        $this->arguments->parse();
    }

    function startStream() {
        $args = $this->arguments->getAll();
        $query = $this->arguments->get('_words_');
        if (!isset($query[2])) {
            $this->app->errorMessage("Where is SQL?");
            die(1);
        }
        $args['query'] = $query[2];
        $elastic = new PDODriver($this->app);

        $rez = $elastic->dumpQueryStream($args);

        foreach ($rez as $line) {
            $this->sendMessage($line);
        }
    }

}

