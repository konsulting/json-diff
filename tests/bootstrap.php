<?php

require_once __DIR__.'/../vendor/autoload.php';

// Return stub contents
function stub($name) {
    return file_get_contents(__DIR__.'/stubs/'.$name);
}
