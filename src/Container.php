<?php
namespace Lipht;

class Container {
    private $parent = null;
    private $services = [];

    private $stack = [];

    public function __construct(Container $parent = null) {
        $this->parent = $parent;
    }

    public function add($service) : void {
        if (is_array($service)) {
            foreach ($service as $part) {
                $this->add($part);
            }
            return;
        }

        $meta = new \ReflectionClass($service);

        if (!$meta->isInstantiable())
            throw new \Exception('Cannot add service, class not instantiable. ('.$meta->getName().')');

        $this->services[] = (object)[
            'subject' => $service,
            'meta' => $meta,
        ];
    }

    public function isAvailable(string $service) : bool {
        if ($this->isAvailableLocally($service))
            return true;

        return $this->parent && $this->parent->isAvailable($service);
    }

    public function isAvailableLocally(string $service) : bool {
        return !!$this->findDependencyLocally($service);
    }

    public function get(string $service) {

        if (in_array($service, $this->stack))
            throw new \Exception('Cannot invoke target, cyclical dependency detected. ('.$service.')');

        $this->stack[] = $service;

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

    public function findDependency($search) {
        $local = $this->findDependencyLocally($search);

        if (!$this->parent)
            return $local;

        return $local ?: $this->parent->findDependency($search);
    }

    public function inject($target, $args = []) {
        if (is_string($target) && class_exists($target))
            return $this->injectConstructor($target, $args);

        if (is_callable($target))
            return $this->injectMethod($target, $args);

        throw new \Exception('Cannot invoke target, type not supported. ('.$target.')');
    }

    public function reset() {
        if (count($this->stack))
            throw new \Exception('Cannot reset container during stack injection.');

        $this->services = [];
        return $this;
    }

    private function findDependencyLocally($search) {
        $searchMeta = new \ReflectionClass($search);
        foreach (array_reverse($this->services) as $service) {
            if ($service->meta->getName() === $searchMeta->getName()
                || is_subclass_of($service->meta->getName(), $searchMeta->getName())
                || ($searchMeta->isInterface() && $service->meta->implementsInterface($searchMeta->getName())))
                return $service;
        }

        return false;
    }

    private function hydrate($service) {
        if (is_string($service->subject))
            $service->subject = $this->buildDependency($service->meta);

        return $service->subject;
    }

    private function buildDependency(\ReflectionClass $meta) {
        $constructor = $meta->getConstructor();
        if (!$constructor)
            return $meta->newInstanceArgs([]);

        $injected = $this->provideForFunction($constructor, []);
        return $meta->newInstanceArgs($injected);
    }

    private function injectConstructor(string $classname, array $args) {
        $ref = new \ReflectionClass($classname);
        if (!$ref->isInstantiable())
            throw new \Exception('Cannot invoke target, class not instantiable. ('.$classname.')');

        $constructor = $ref->getConstructor();
        if (!$constructor)
            return new $classname;

        $injected = $this->provideForFunction($constructor, $args);
        return $ref->newInstanceArgs($injected);
    }

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

    private function fetchReflectionFunction(Callable $callable) {
        if (is_string($callable) && strpos($callable, '::'))
            $callable = explode('::', $callable);

        if (is_array($callable))
            return new \ReflectionMethod($callable[0], $callable[1]);

        return new \ReflectionFunction($callable);
    }

    private function provideForFunction($ref, array $args) {
        $injected = [];
        foreach ($ref->getParameters() as $param) {
            if (count($args) > 0) {
                $injected[] = array_shift($args);
                continue;
            }

            if ($param->isOptional()) {
                $injected[] = $param->getDefaultValue();
                continue;
            }

            $injected[] = $this->get($param->getType()->__toString());
        }

        return $injected;
    }
}
