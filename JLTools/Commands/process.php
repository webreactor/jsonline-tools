<?php

namespace JLTools\Commands;

use \Reactor\CliArguments\ArgumentsParser;
use \Reactor\CliArguments\ArgumentDefinition;

class process extends \JLTools\Common\BaseController {

    public $code = '';

    function __construct($app) {
        parent::__construct($app);
        $this->arguments->addDefinition(new ArgumentDefinition('_words_', '', '', false, true, 'PHP code'));
        $this->arguments->parse();
        $code = $this->arguments->get('_words_');
        $code = array_slice($code, 2);
        $this->code = implode(";\n", $code);
    }

    function processMessage($src) {
        $code = '$rez = $src; '.$this->code.'; return $rez;';
        return eval($code);
    }

}


