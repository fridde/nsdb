<?php

use App\Kernel;

//phpinfo(); die();
//$link = mysqli_connect("127.0.0.1", "root", $_ENV['MYSQL_ROOT_PASSWORD'], 'nsdb');

/*if (!$link) {
    echo "Error: Unable to connect to MySQL." . PHP_EOL;
    echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
    echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
    exit;
}
echo "Success: A proper connection to MySQL was made! The docker database is great." . PHP_EOL;
mysqli_close($link);*/



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
