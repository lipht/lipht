<?php
namespace Lipht;

abstract class Module {
    protected $parentContainer = null;
    protected $container = null;
    protected $children = [];

    private static $instances = [];

    // Declare a static listServices(Container) to provide services in your module

    private function __construct(Container $container = null) {
        if (!$this->parentContainer)
            $this->parentContainer = $container;

        $this->children = [];
        $this->container()->reset();

        if (method_exists(static::class, 'listServices')) {
            $this->container()->add(static::listServices($this->container()) ?? []);
        }
    }

    public static function init(Container $container = null) {
        if (self::class === static::class)
            throw new \Exception('Cannot initialize abstract base module');

        $name = static::class;
        $instance = new $name($container);
        self::$instances[static::class] = $instance;
        return $instance;
    }

    public static function getInstance() {
        if (!isset(self::$instances[static::class]))
            throw new \Exception('Cannot use uninitialized module');

        return self::$instances[static::class];
    }

    public static function coldStartChildModule($name, $callback = null) {
        return static::getInstance()->runInChildModule($name, $callback);
    }

    public function container() {
        if (!$this->container)
            $this->container = new Container($this->parentContainer);

        return $this->container;
    }

    public function get($service) {
        return $this->container()->get($service);
    }

    public function inject($callable, $args = array()) {
        return $this->container()->inject($callable, $args);
    }

    public function __call($method, $args) {
        $callback = isset($args[0]) ? $args[0] : null;
        $name = static::class.'::'.$method;

        return $this->runInChildModule($name, $callback);
    }

    public function runInChildModule($name, $callback = null) {
        $parts = explode('::', str_replace('\\', '/', $name));
        $key = str_replace('/', '\\', dirname($parts[0]).'/'.ucfirst($parts[1]));

        if (!isset($this->children[$key])) {
            $config = $key.'\Module';

            $this->children[$key] = $config::init($this->container());
        }

        if (!is_callable($callback)) {
            return $this->children[$key];
        }

        return $this->children[$key]->inject($callback);
    }
}
