<?php

namespace JLTools\Commands;

use \Reactor\CliArguments\ArgumentsParser;
use \Reactor\CliArguments\ArgumentDefinition;
use \JLTools\Elastic\Elastic;

class from_elastic extends \JLTools\Common\BaseController {

    public $code = '';

    function __construct($app) {
        parent::__construct($app);
        $this->arguments->addDefinition(new ArgumentDefinition('_words_', '', 'http://localhost:9200', false, true, 'Connection string'));
        $this->arguments->addDefinition(new ArgumentDefinition('index', 'i', '', false, true, 'Index'));
        $this->arguments->addDefinition(new ArgumentDefinition('query', 'q', '*', false, false, 'Search query'));
        $this->arguments->addDefinition(new ArgumentDefinition('sort', 's', '@timestamp', false, false, 'Sort by field, default @timestamp'));
        $this->arguments->addDefinition(new ArgumentDefinition('from', 'f', 0, false, false, 'Skip records'));
        $this->arguments->addDefinition(new ArgumentDefinition('buffer', 'b', 100, false, false, 'Read buffer size, default 100'));
        $this->arguments->addDefinition(new ArgumentDefinition('source', 'x', true, true, false, 'Return only _source from results'));
        $this->arguments->addDefinition(new ArgumentDefinition('limit', 'l', 0, false, false, 'Limit amount records fetched, 0 is unlimited'));
        $this->arguments->addDefinition(new ArgumentDefinition('mode', 'm', 'all', false, false, 'all - query all matching indexes as one, each - query each matching index individualy'));
        $this->arguments->parse();
    }

    function startStream() {
        $args = $this->arguments->getAll();
        $server = $this->arguments->get('_words_');
        if (!isset($server[2])) {
            return;
        }
        $args['server'] = $server[2];
        $elastic = new Elastic($this->app);
        if (empty($args['index'])) {
            die('Specify --index');
        }
        $rez = $elastic->dumpIndexStream($args);

        foreach ($rez as $line) {
            $this->sendMessage($line);
        }
    }

}





