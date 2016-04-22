<?php
namespace Poirot\Std\Environment;

use Poirot\Std\Interfaces\Pact\ipFactory;

class FactoryEnvironment
    implements ipFactory
{
    protected static $_aliases = array(
        ## 'name'            => EnvBase | \Path\To\Class  

        // DO_LEAST_PHPVER_SUPPORT v5.5
        // 'development'     => \Poirot\Std\Environment\EnvDevelopment::class,
        'development'     => '\Poirot\Std\Environment\EnvDevelopment',
        'dev'             => 'development',
        'debug'           => 'development',

        // DO_LEAST_PHPVER_SUPPORT v5.5
        // 'production'      => \Poirot\Std\Environment\EnvProduction::class,
        'production'      => '\Poirot\Std\Environment\EnvProduction',
        'prod'            => 'production',

        // DO_LEAST_PHPVER_SUPPORT v5.5
        // 'php-environment' => \Poirot\Std\Environment\EnvServerDefault::class,
        'php-environment' => '\Poirot\Std\Environment\EnvServerDefault',
        'php'             => 'php-environment',
        'default'         => 'php',
    );

    /**
     * Build Object With Provided Options
     * Options: [
     *   'register'   => ['name' => EnvBase|'To\ClassName', 'name' => [EnvBase|'To\ClassName', 'alias', $alias2, ..] ]
     *   'alias_name' => ['nameOrAlias', 'alias', 'alias2', ..]
     *
     * @param array $options        Associated Array
     * @param bool  $throwException Throw Exception On Wrong Option
     *
     * @throws \Exception
     * @return $this
     */
    static function with(array $options, $throwException = false)
    {
        if ($throwException && !(isset($options['register']) || isset($options['alias_name'])))
            throw new \InvalidArgumentException('Invalid Option Provided.');

        if (isset($options['register']) && $register = $options['register']) {
            foreach ($register as $name => $instanceAliases) {
                if (!is_array($instanceAliases))
                    ## ['name' => EnvBase|'To\ClassName'
                    $instanceAliases = [$instanceAliases];

                $envInstance = array_shift($instanceAliases);
                // remaining items is aliases
                // 'name' => [EnvBase|'To\ClassName', 'alias', $alias2, ..
                self::register($envInstance, $name, $instanceAliases);
            }
        }

        if (isset($options['alias_name']) && $aliases = $options['alias_name']) {
            foreach ($aliases as $nameOrAlias => $alias) {
                if (!is_array($alias))
                    ## ['name' => EnvBase|'To\ClassName'
                    $alias = [$alias];

                self::setAlias($nameOrAlias, $alias);
            }
        }
    }

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
    static function register($environment, $name, array $aliases = array())
    {
        if (
            (is_string($environment) && !class_exists($environment)) || 
            (is_object($environment) && !$environment instanceof EnvBase)
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

    /**
     * Load Build Options From Given Resource
     *
     * - usually it used in cases that we have to support
     *   more than once configure situation
     *   [code:]
     *     Configurable->with(Configurable::withOf(path\to\file.conf))
     *   [code]
     *
     *
     * @param array|mixed $optionsResource
     *
     * @throws \InvalidArgumentException if resource not supported
     * @return array
     */
    static function withOf($optionsResource)
    {
        if (!is_array($optionsResource))
            throw new \InvalidArgumentException(sprintf(
                'Options as Resource Just Support Array, given: (%s).'
                , \Poirot\Std\flatten($optionsResource)
            ));

        return $optionsResource;
    }
}
