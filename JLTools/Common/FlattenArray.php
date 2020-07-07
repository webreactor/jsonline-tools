<?php

namespace JLTools\Common;

class FlattenArray {

    static function flatten($data, $prefix = "") {
        $rez = array();
        foreach ($data as $key => $value) {
            if (!is_array($value)) {
                $rez["$prefix$key"] = $value;
            }
        }
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $rez = array_merge($rez, self::flatten($value, "$prefix$key."));
            }
        }

        return $rez;
    }

}
