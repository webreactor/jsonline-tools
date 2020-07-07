<?php

namespace JLTools\Commands;

use \Reactor\CliArguments\ArgumentsParser;
use \Reactor\CliArguments\ArgumentDefinition;
use \JLTools\Common\FlattenArray;

class to_html extends \JLTools\Common\BaseController {

    function __construct($app) {
        parent::__construct($app);
        $this->current_fields = array();
    }

    function onStartStream() {
        $this->app->output("<html><head><title></title><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" /></head><body><table border=\"1\">");
    }

    function onStopStream() {
        $this->app->output("</table></table>");
    }

    function processMessage($src) {
        $src = FlattenArray::flatten($src);
        $fields = array_keys($src);
        if (!$this->compareArrays($this->current_fields, $fields)) {
            $this->current_fields = $fields;
           $this->sendMessage($fields);
        }
        return $src;
    }

    function encodeMessage($data) {
        $data = array_map("htmlspecialchars", $data);
        return "<tr><td>".implode('</td><td>', $data)."</td></tr>";
    }

    function compareArrays($a1, $a2) {
        return implode(',', $a1) === implode(',', $a2);
    }

}

