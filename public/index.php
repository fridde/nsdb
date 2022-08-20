<?php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

function debugLog($variable){
    if(!empty($GLOBALS['LOGGER'])){
        $GLOBALS['LOGGER']->debug(print_r($variable));
    }
}


return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
