<?php
namespace Lipht;

use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;

class Container {
    /** @var Container|null $parent */
    private $parent = null;

    /** @var ServiceProvider[] $services */
    private $services = [];

    /** @var string[] $stack */
    private $stack = [];

    /**
     * Container constructor.
     * @param Container|null $parent
     */
    public function __construct(Container $parent = null) {
        $this->parent = $parent;
    }

    /**
     * @param object|array $service
     * @param callable|null $provider
     * @throws ReflectionException
     * @throws \Exception
     */
    public function add($service, Callable $provider = null) : void {
        if (is_array($service)) {
            foreach ($service as $part) {
                $this->add($part, $provider);
            }
            return;
        }

        $meta = new ReflectionClass($service);

        if (!$meta->isInstantiable() && !$provider)
            throw new \Exception('Cannot add service, class not instantiable. ('.$meta->getName().')');

        $this->services[] = new ServiceProvider([
            'subject' => $service,
            'meta' => $meta,
            'provider' => $provider,
        ]);
    }

    /**
     * @param string $service
     * @return bool
     * @throws ReflectionException
     */
    public function isAvailable(string $service) : bool {
        if ($this->isAvailableLocally($service))
            return true;

        return $this->parent && $this->parent->isAvailable($service);
    }

    /**
     * @param string $service
     * @return bool
     * @throws ReflectionException
     */
    public function isAvailableLocally(string $service) : bool {
        return !!$this->findDependencyLocally($service);
    }

    /**
     * @param string $service
     * @return object
     * @throws \Exception
     */
    public function get(string $service) {

        if (in_array($service, $this->stack))
            throw new \Exception('Cannot invoke target, cyclical dependency detected. ('.$service.')');

        $this->stack[] = $service;
        $instance = null;

        try {
            $dependency = $this->findDependency($service);

            if (!$dependency) {
                $this->stack = [];
                throw new \Exception('Cannot invoke target, service not available. ('.$service.')');
            }

            $instance = $this->hydrate($dependency);
        } finally {
            array_pop($this->stack);
        }

        return $instance;
    }

    /**
     * @param string $search
     * @return ServiceProvider
     * @throws ReflectionException
     */
    public function findDependency($search) {
        $local = $this->findDependencyLocally($search);

        if (!$this->parent)
            return $local;

        return $local ?: $this->parent->findDependency($search);
    }

    /**
     * @param string|callable|array $target
     * @param array $args
     * @return mixed|object
     * @throws \Exception
     */
    public function inject($target, $args = []) {
        if (is_string($target) && class_exists($target))
            return $this->injectConstructor($target, $args);

        if (is_callable($target))
            return $this->injectMethod($target, $args);

        if (is_array($target))
            $target = implode(', ', [
                get_class($target[0]),
                $target[1]
            ]);

        throw new \Exception('Cannot invoke target, type not supported. ('.$target.')');
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function reset() {
        if (count($this->stack))
            throw new \Exception('Cannot reset container during stack injection.');

        $this->services = [];
        return $this;
    }

    /**
     * @param string $search
     * @return ServiceProvider|bool
     * @throws ReflectionException
     */
    private function findDependencyLocally($search) {
        $searchMeta = new ReflectionClass($search);
        foreach (array_reverse($this->services) as $service) {
            if ($service->meta->getName() === $searchMeta->getName()
                || is_subclass_of($service->meta->getName(), $searchMeta->getName())
                || ($searchMeta->isInterface() && $service->meta->implementsInterface($searchMeta->getName())))
                return $service;
        }

        return false;
    }

    /**
     * @param ServiceProvider $service
     * @return object
     * @throws ReflectionException
     * @throws \Exception
     */
    private function hydrate($service) {
        if (!is_string($service->subject)) {
            return $service->subject;
        }

        if (!$service->provider) {
            $service->subject = $this->buildDependency($service->meta);
            return $service->subject;
        }

        $provided = call_user_func($service->provider, $service);
        $providedMeta = new ReflectionClass($provided);
        if ($providedMeta->getName() != $service->meta->getName()) {
            throw new \Exception('Cannot invoke target, wrong type from provider. (Expected any type of '.$service->subject.')');
        }

        $service->subject = $provided;
        $service->meta = $providedMeta;
        return $service->subject;
    }

    /**
     * @param ReflectionClass $meta
     * @return object
     * @throws \Exception
     */
    private function buildDependency(ReflectionClass $meta) {
        $constructor = $meta->getConstructor();
        if (!$constructor)
            return $meta->newInstanceArgs([]);

        $injected = $this->provideForFunction($constructor, []);
        return $meta->newInstanceArgs($injected);
    }

    /**
     * @param string $classname
     * @param array $args
     * @return object
     * @throws ReflectionException
     * @throws \Exception
     */
    private function injectConstructor(string $classname, array $args) {
        $ref = new ReflectionClass($classname);
        if (!$ref->isInstantiable())
            throw new \Exception('Cannot invoke target, class not instantiable. ('.$classname.')');

        $constructor = $ref->getConstructor();
        if (!$constructor)
            return new $classname;

        $injected = $this->provideForFunction($constructor, $args);
        return $ref->newInstanceArgs($injected);
    }

    /**
     * @param callable $method
     * @param array $args
     * @return mixed
     * @throws ReflectionException
     * @throws \Exception
     */
    private function injectMethod(Callable $method, array $args) {
        $ref = $this->fetchReflectionFunction($method);
        $injected = $this->provideForFunction($ref, $args);

        $target = null;
        if (is_array($method) && is_a($ref, 'ReflectionMethod')) {
            $target = $method[0];
            return $ref->invokeArgs($target, $injected);
        }

        return $ref->invokeArgs($injected);
    }

    /**
     * @param callable $callable
     * @return ReflectionFunction|ReflectionMethod
     * @throws ReflectionException
     */
    private function fetchReflectionFunction(Callable $callable) {
        if (is_string($callable) && strpos($callable, '::'))
            $callable = explode('::', $callable);

        if (is_array($callable))
            return new ReflectionMethod($callable[0], $callable[1]);

        return new ReflectionFunction($callable);
    }

    /**
     * @param ReflectionMethod $ref
     * @param array $args
     * @return object[]
     * @throws \Exception
     */
    private function provideForFunction($ref, array $args) {
        $injected = [];
        foreach ($ref->getParameters() as $param) {
            if (count($args) > 0) {
                $injected[] = array_shift($args);
                continue;
            }

            $default = null;
            try {
                $default = $param->getDefaultValue();
            } catch (ReflectionException $e) {}

            if (!is_null($default) || $param->isOptional()) {
                $injected[] = $default;
                continue;
            }

            $injected[] = $this->get(strval($param->getType()));
        }

        return $injected;
    }
}
