<?php

namespace JLTools\Commands;

use \Reactor\CliArguments\ArgumentsParser;
use \Reactor\CliArguments\ArgumentDefinition;

class to_gpx extends process {

    function __construct($app) {
        parent::__construct($app);
        $this->arguments->addDefinition(new ArgumentDefinition('lat', 'a', "latitude", false, false, 'Latitude index name in json'));
        $this->arguments->addDefinition(new ArgumentDefinition('lon', 'o', "longitude", false, false, 'Longitude index name in json'));
        $this->arguments->addDefinition(new ArgumentDefinition('time', 't', "time", false, false, 'Time index name in json'));
        $this->arguments->parse();
        $code = array('$rez = array();');
        foreach (array('lat', 'lon', 'time') as $key) {
            $code[] = '$rez["'.$key.'"] ='.'$src["'.$this->arguments->get($key).'"]??0';
        }
        $code[] = '$rez["time"] = 0 + $rez["time"];';
        $this->code = implode(";\n", $code);
    }

    function encodeMessage($data) {
        return $data;
    }

    function startStream() {
        $this->sendMessage( '<?xml version="1.0" encoding="UTF-8"?>
        <gpx creator="xcom.com" xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://www.topografix.com/GPX/1/1">
        ');
        $c_day = 0;
        while ($t = fgets(STDIN)) {
            $message = json_decode($t, true);
            $message = $this->processMessage($message);
            if ($message !== null) {
                $day = date('Y-m-d', $message['time']);
                if ($c_day != $day) {
                    if ($c_day !== 0) {
                        $this->sendMessage("</trkseg></trk>");
                    }
                    $c_day = $day;
                    $this->sendMessage("<trk><name>$day</name><trkseg>");
                }
                $this->sendMessage('<trkpt lat="'.$message['lat'].'" lon="'.$message['lon'].'">');
                if ($message['time'] != 0) {
                    $this->sendMessage('<time>'.date(\DATE_ATOM, $message['time']).'</time>');
                }
                $this->sendMessage('</trkpt>');
            }
        }
        if ($c_day !== 0) {
            $this->sendMessage("</trkseg></trk>");
        }
        $this->sendMessage('</gpx>');
    }

}

