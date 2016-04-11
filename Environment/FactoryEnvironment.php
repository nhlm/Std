<?php
namespace Poirot\Std\Environment;

use Poirot\Std\Interfaces\Pact\ipFactory;

/*

$EnvSettings = AliasEnvFactory::with(function() {
    $default = ($env_mode = getenv('POIROT_ENV_MODE')) ? $env_mode : 'default';
    return (defined('DEBUG') && constant('DEBUG')) ? 'dev' : $default;
});
$EnvSettings->apply();

*/

class FactoryEnvironment implements ipFactory
{
    protected static $_aliases = [
        'development'     => \Poirot\Std\Environment\EnvDevelopment::class,
        'dev'             => 'development',
        'debug'           => 'development',

        'production'      => \Poirot\Std\Environment\EnvProduction::class,
        'prod'            => 'production',

        'php-environment' => \Poirot\Std\Environment\EnvServerDefault::class,
        'php'             => 'php-environment',
        'default'         => 'php',
    ];

    /**
     * Factory To Settings Environment
     *
     * callable:
     *  string|BaseEnv function()
     *  return alias or env instance
     *
     * @param string|callable $aliasOrCallable
     *
     * @throws \Exception
     * @return EnvBase
     */
    static function of($aliasOrCallable)
    {
        $alias = $aliasOrCallable;

        if (is_callable($alias))
            $alias = call_user_func($aliasOrCallable);

        if ($alias instanceof EnvBase)
            ## Callable return Environment Instance
            return $alias;

        elseif (!is_string($alias))
            throw new \Exception(sprintf(
                'Invalid Alias name provided. it must be string given: %s.'
                , (is_callable($aliasOrCallable))
                  ? \Poirot\Std\flatten($alias).' provided from Callable' : \Poirot\Std\flatten($alias)
            ));

        ## find alias names: dev->development ==> class_name
        $EnvClass = null;
        while(isset(self::$_aliases[$alias]))
            $EnvClass = $alias = self::$_aliases[$alias];

        if (is_string($EnvClass) && class_exists($EnvClass))
            $EnvClass = new $EnvClass();

        if (! $EnvClass instanceof EnvBase)
            throw new \Exception("Class map for {$alias} environment not implemented.");

        return $EnvClass;
    }

    /**
     * Register New Environment With Given Name
     *
     * - it can override current environment with existence name
     *
     * @param EnvBase|string $environment Environment instance or class name
     * @param string         $name        Registered Name
     * @param array          $aliases     Name Aliases
     */
    static function register($environment, $name, array $aliases = [])
    {
        if (
            (is_string($environment) && !class_exists($environment))
            || (is_object($environment) && !$environment instanceof EnvBase)
        )
            throw new \InvalidArgumentException(sprintf(
                'Invalid Environment Provided; It must be class name or object instance of EnvBase. given: (%s).'
                , \Poirot\Std\flatten($environment)
            ));

        self::$_aliases[$name] = $environment;

        self::setAlias($name, $aliases);
    }

    /**
     * Set Alias Or Name Aliases
     *
     * @param string       $name  Alias Or Name
     * @param array|string $alias Alias(es)
     */
    static function setAlias($name, $alias)
    {
        if (!is_array($alias))
            $alias = [$alias];

        foreach($alias as $a)
            self::$_aliases[(string) $a] = (string) $name;
    }
}
