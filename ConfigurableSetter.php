<?php
namespace Poirot\Std;

use Poirot\Std\Traits\tConfigurableSetter;

class ConfigurableSetter
{
    use tConfigurableSetter;

    /**
     * Construct
     *
     * @param array $setter
     */
    function __construct(array $setter = null)
    {
        if ($setter !== null)
            $this->with($setter);
    }
}
