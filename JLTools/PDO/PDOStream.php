<?php

namespace JLTools\PDO;

use \JLTools\Common\CallbackStream;

class PDOStream extends CallbackStream {

    public $buffer, $args;

    public function __construct($getter, $args) {
        $this->getter = $getter;
        $this->buffer = null;
        $this->current = null;
        $this->args = $args;
    }

    public function rewind() {
        $this->next();
        $this->key = 1;
    }

    public function next() {
        if ($this->buffer !== null) {
            $this->current = $this->buffer->line();
        }
        if ($this->current === null) {
            $this->buffer = call_user_func($this->getter);
            if (!empty($this->buffer)) {
                $this->current = $this->buffer->line();
            }
        }
        if ($this->args['limit'] != 0 && $this->key >= $this->args['limit']) {
            $this->current = null;
        }

        $this->key++;
        if ($this->current !== null && $this->args['key'] !== '') {
            $this->args['from'] = $this->current[$this->args['key']];
        } else {
            $this->args['from'] = $this->key;
        }
    }

}
