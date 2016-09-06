<?php
namespace Poirot\Std;

// DO_LEAST_PHPVER_SUPPORT 5.4 closure bindto & trait
if (version_compare(phpversion(), '5.4.0') < 0) {
    require_once __DIR__.'/fixes/Mixin.php';
    return;
}

require_once __DIR__.'/Mixin.fix.php';
