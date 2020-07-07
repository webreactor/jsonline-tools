<?php

namespace JLTools;

use \Reactor\CliArguments\ArgumentsParser;
use \Reactor\CliArguments\ArgumentDefinition;


class Application {

    var $command = '';

    function handleCliRequest($args) {
        $this->arguments = new ArgumentsParser($args);
        $this->arguments->addDefinition(new ArgumentDefinition('_words_', '', '', false, true, 'Command'));
        $this->arguments->parse();
        $words = $this->arguments->get('_words_');
        if (!isset($words[1])) {
            $words[1] = 'help';
        }
        try {
            $handler = $this->commandHandler($words[1]);
            $handler->startStream();
        } catch (\Exception $e) {
            $this->errorMessage($e->getMessage());
            die(1);
        }

    }

    function commandHandler($command) {
        $command = str_replace('-','_', $command);
        $class = '\\JLTools\\Commands\\'.str_replace('-','_', $command);

        if (!class_exists($class)) {
            return $this->commandHandler('help');
        }

        $this->command = $command;
        return new $class($this, $this->arguments);
    }

    function userMessage($message, $data = null) {
        if ($data !== null) {
            fwrite(STDERR, "{$this->command}: {$message}\n".json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n");
        } else {
            fwrite(STDERR, "{$this->command}: {$message}\n");
        }
    }

    function errorMessage($message, $data = null) {
        if ($data !== null) {
            fwrite(STDERR, "\033[0;31mError in {$this->command}: {$message}\033[0m\n".json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n");
        } else {
            fwrite(STDERR, "\033[0;31mError in {$this->command}: {$message}\033[0m\n");
        }
    }

    function output($message) {
        echo $message."\n";
    }

    function input() {
        return fgets(STDIN);
    }

}
