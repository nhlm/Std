<?php
namespace Poirot\Std;

use Poirot\Std\Interfaces\Pact\ipConfigurable;
use Poirot\Std\Traits\tConfigurableSetter;

class ConfigurableSetter
    extends    aConfigurable
    implements ipConfigurable
{
    use tConfigurableSetter;
}
