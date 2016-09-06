<?php
namespace Poirot\Std\Type;

// DO_LEAST_PHPVER_SUPPORT 5.4 traits
if (version_compare(phpversion(), '5.4.0') < 0) {
    ## php version not support traits
    require_once __DIR__.'/fixes/StdArray.php';
    return;
}

require_once __DIR__.'/StdArray.fix.php';
