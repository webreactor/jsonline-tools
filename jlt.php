<?php

namespace JLTools;

include 'vendor/autoload.php';

$app = new Application();

$app->handleCliRequest($argv);
