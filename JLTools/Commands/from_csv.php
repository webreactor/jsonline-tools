<?php

namespace JLTools\Commands;

use \Reactor\CliArguments\ArgumentsParser;
use \Reactor\CliArguments\ArgumentDefinition;

class from_csv extends \JLTools\Common\BaseController {

    public $code = '';

    function __construct($app) {
        parent::__construct($app);
        $this->arguments->addDefinition(new ArgumentDefinition('delimeter', 'd', ',', false, false, 'Delimeter'));
        $this->arguments->addDefinition(new ArgumentDefinition('enclosure', 'n', '"', false, false, 'Enclosure'));
        $this->arguments->addDefinition(new ArgumentDefinition('escape', 's', '"', false, false, 'Escape'));
        $this->arguments->addDefinition(new ArgumentDefinition('autofields', 'a', true, true, false, 'autofields'));
        $this->arguments->parse();
        $this->args = $this->arguments->getAll();
        $this->current_fields = false;
        $this->next_is_fields = false;
    }

    function decodeMessage($src) {
        return str_getcsv(rtrim($src, $this->args['delimeter']), $this->args['delimeter'], $this->args['enclosure'], $this->args['escape']);
    }

    function processMessage($data) {
        if ($this->args['autofields'] === true) {
           $fields = $this->autoFields($data);
           if (!empty($fields)) {
                return array_combine($fields, $data);
           }
        }
        return $data;
    }

    function autoFields($data) {
        if (trim($data[0]) == '#') {
            $this->next_is_fields = true;
            $this->current_fields = false;
        } elseif ($this->next_is_fields) {
            $this->next_is_fields = false;
            $this->current_fields = $data;
        }
        return $this->current_fields;
    }

}


