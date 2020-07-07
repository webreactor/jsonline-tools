<?php

namespace JLTools\Elastic;

use \JLTools\Common\CallbackStream;

class ElasticStream extends CallbackStream {

    public $buffer, $buffer_key = 0, $buffer_size = 0, $total = 0, $args;

    public function __construct($getter, $args) {
        $this->getter = $getter;
        $this->args = $args;
        $this->counter = 0;
    }

    public function next() {
        if ($this->buffer_key >= $this->buffer_size) {
            $this->buffer = call_user_func($this->getter);
            $this->buffer_key = 0;
            $this->buffer_size = count($this->buffer);
        }

        if ($this->buffer_key < $this->buffer_size) {
            $this->current = $this->buffer[$this->buffer_key];
            $this->buffer_key++;
        } else {
            $this->current = false;
            $this->counter = 0;
        }

        $this->counter++;
        if ($this->args['limit'] != 0 && $this->args['limit'] < $this->counter) {
            $this->current = false;
        }

        $this->key++;
    }
}

