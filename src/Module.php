<?php
namespace Lipht;

abstract class Module {
    protected static $init = false;
    protected static $parentContainer = null;
    protected static $container = null;
    protected static $children = [];

    abstract protected static function listServices();

    public static function init(Container $container = null) {
        if (!static::$parentContainer) {
            static::$parentContainer = $container;
        }

        static::container()->add(static::listServices());
        return static::class;
    }

    public static function container() {
        if (!static::$container) {
            static::$container = new Container(static::$parentContainer);
        }

        return static::$container;
    }
    public function get($service) {
        return static::container()->get($service);
    }
    public function inject($callable, $args = array()) {
        return static::container()->inject($callable, $args);
    }

    public function __call($method, $args) {
        $callback = isset($args[0]) ? $args[0] : null;
        $name = static::class.'::'.$method;

        return static::runInChildModule($name, $callback);
    }

    public static function runInChildModule($name, $callback = null) {
        $parts = explode('::', str_replace('\\', '/', $name));
        $key = str_replace('/', '\\', dirname($parts[0]).'/'.ucfirst($parts[1]));

        if (!isset(static::$children[$key])) {
            $config = $key.'\Module';

            static::$children[$key] = new $config();
            $config::init();
        }

        if (!is_callable($callback)) {
            return static::$children[$key];
        }

        return static::$children[$key]->inject($callback);
    }
}
