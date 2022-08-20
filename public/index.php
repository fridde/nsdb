<?php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

if(!function_exists('debugLog')){
    function debugLog($variable){
        if(!empty($GLOBALS['LOGGER'])){
            $GLOBALS['LOGGER']->debug(var_export($variable, true));
        }
    }
}



return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
