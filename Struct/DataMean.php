<?php
namespace Poirot\Std\Struct;


// DO_LEAST_PHPVER_SUPPORT 5.5 yield
if (version_compare(phpversion(), '5.5.0') < 0) {
    ## php version not support traits
    require_once __DIR__.'/fixes/DataMean.php';
    return;
}

require_once __DIR__.'/DataMean.fix.php';
