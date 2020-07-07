<?php

namespace JLTools\Commands;

use \Reactor\CliArguments\ArgumentsParser;
use \Reactor\CliArguments\ArgumentDefinition;

class help extends \JLTools\Common\BaseController {

    public $code = '';


    function startStream() {
        $words = $this->arguments->get('_words_');
        $command = 'show-all';
        if (isset($words[2])) {
            $command = $words[2];
        }

        if ($command !== 'show-all') {
            $this->app->userMessage("Command $command");
            $handler = $this->app->commandHandler($command);
            if (get_class($handler) != get_class($this)) {
                $handler->help();
                return;
            }
        }
        $this->app->userMessage('Available commands', $this->getCommands());
        $this->app->userMessage('use help command for detailed info');
    }

    function getCommands() {
        $dir = scandir(__dir__);
        $rez = array();
        foreach ($dir as $value) {
            if ($value[0] != '.' && $value != 'help') {
                $rez[] = substr($value, 0, -4);
            }
        }
        return $rez;
    }

}


