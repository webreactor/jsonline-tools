<?php

namespace JLTools\Common;

class BaseController {

    function __construct($app) {
        $this->app = $app;
        $this->arguments = $this->app->arguments;
    }

    function decodeMessage($src) {
        return json_decode($src, true);
    }

    function processMessage($src) {
        return $src;
    }


    function onStartStream() {
    }

    function onStopStream() {
    }

    function encodeMessage($message) {
        return json_encode($message, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    function sendMessage($message) {
        $message = $this->encodeMessage($message);
        $this->app->output($message);
    }

    function startStream() {
        $this->onStartStream();
        while ($message = $this->app->input()) {
            $message = trim($message);
            if ($message !== "") {
                $message = $this->decodeMessage($message);
                if ($message !== null) {
                    $message = $this->processMessage($message);
                    if ($message !== null) {
                        $this->sendMessage($message);
                    }
                } 
            }
        }
        $this->onStopStream();
    }

    function help() {
        $this->helpDescription();
        $this->helpArguments();
        $this->helpExamples();
    }

    function helpDescription() {
    }

    function helpExamples() {
    }

    function helpArguments() {
        $this->app->output("Arguments:");
        $this->app->output("  Full name    | Short | Default            | Note");
        $this->app->output("-------------------------------------------------------");
        foreach ($this->arguments->definitions as $key => $definition) {
            if ($key != '_words_') {
                if (!$definition->is_flag) {
                    $this->app->output(sprintf("  --%-12s -%-6s %-20s %s",
                        $definition->name,
                        $definition->short,
                        $definition->default,
                        $definition->description
                    ));
                } else {
                    $this->app->output(sprintf("  --%-12s -%-6s %-20s %s",
                        $definition->name,
                        $definition->short,
                        'false',
                        $definition->description
                    ));
                }
            } else {
                $this->app->output(sprintf("  %-12s   %-6s  %-20s %s",
                    '{last arg}',
                    '-',
                    '-',
                    $definition->description
                ));
            }
        }
        $this->app->output("");// new line
    }

}

