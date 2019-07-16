<?php
namespace Lipht;

use ReflectionException;

/**
 * @method static listServices(Container|null $container)
 */
abstract class Module {
    /** @var Container|null $parentContainer */
    protected $parentContainer = null;

    /** @var Container|null $container */
    protected $container = null;

    /** @var Module[] $children */
    protected $children = [];

    /** @var Module[] $instances */
    private static $instances = [];

    // Declare a static listServices(Container) to provide services in your module

    /**
     * Module constructor.
     * @param Container|null $container
     * @throws ReflectionException
     * @throws \Exception
     */
    private function __construct(Container $container = null) {
        if (!$this->parentContainer)
            $this->parentContainer = $container;

        $this->children = [];
        $this->container()->reset();

        if (method_exists(static::class, 'listServices')) {
            $this->container()->add(static::listServices($this->container()) ?? []);
        }
    }

    /**
     * @param Container|null $container
     * @return Module
     * @throws \Exception
     */
    public static function init(Container $container = null) {
        if (self::class === static::class)
            throw new \Exception('Cannot initialize abstract base module');

        $name = static::class;
        $instance = new $name($container);
        self::$instances[static::class] = $instance;
        return $instance;
    }

    /**
     * @return Module
     * @throws \Exception
     */
    public static function getInstance() {
        if (!isset(self::$instances[static::class]))
            throw new \Exception('Cannot use uninitialized module');

        return self::$instances[static::class];
    }

    /**
     * @param string $name
     * @param Callable $callback
     * @return mixed|object
     * @throws \Exception
     */
    public static function coldStartChildModule($name, $callback = null) {
        return static::getInstance()->runInChildModule($name, $callback);
    }

    /**
     * @return Container|null
     */
    public function container() {
        if (!$this->container)
            $this->container = new Container($this->parentContainer);

        return $this->container;
    }

    /**
     * @param $service
     * @return mixed|object
     * @throws \Exception
     */
    public function get($service) {
        return $this->container()->get($service);
    }

    /**
     * @param callable $callable
     * @param array $args
     * @return mixed|object
     * @throws \Exception
     */
    public function inject($callable, $args = array()) {
        return $this->container()->inject($callable, $args);
    }

    /**
     * @param string $method
     * @param array $args
     * @return mixed|object
     * @throws \Exception
     */
    public function __call($method, $args) {
        $callback = isset($args[0]) ? $args[0] : null;
        $name = static::class.'::'.$method;

        return $this->runInChildModule($name, $callback);
    }

    /**
     * @param string $name
     * @param callable|null $callback
     * @return mixed|object
     * @throws \Exception
     */
    public function runInChildModule($name, $callback = null) {
        $parts = explode('::', str_replace('\\', '/', $name));
        $key = str_replace('/', '\\', dirname($parts[0]).'/'.ucfirst($parts[1]));

        if (!isset($this->children[$key])) {
            /** @var Module $config */
            $config = $key.'\Module';

            $this->children[$key] = $config::init($this->container());
        }

        if (!is_callable($callback)) {
            return $this->children[$key];
        }

        return $this->children[$key]->inject($callback);
    }
}
