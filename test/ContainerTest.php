<?php
namespace Test;

use Lipht\Container;
use Test\ContainerTestDummyService as DummyService;
use Test\ContainerTestDummyConsumer as DummyConsumer;
use Test\ContainerTestDummyCyclicalService as DummyCyclicalService;

class ContainerTest extends TestCase {
    public function testAddByClassname() {
        $subject = $this->getSubject();
        $subject->add(DummyService::class);

        $self = $this;
        $this->assertServiceIsInjected($subject, function(DummyService $bob) use($self) {
            $self->assertEquals('bob', $bob->getName());
            return $bob;
        });
    }

    public function testAddInstance() {
        $subject = $this->getSubject();
        $subject->add(new DummyService('steve'));

        $subject2 = $this->getSubject();
        $subject2->add(DummyService::class, function($service) {
            return new DummyService('alex');
        });

        $this->assertServiceIsInjected($subject, function(DummyService $steve) {
            $this->assertEquals('steve', $steve->getName());
            return $steve;
        });

        $this->assertServiceIsInjected($subject2, function(DummyService $alex) {
            $this->assertEquals('alex', $alex->getName());
            return $alex;
        });
    }

    public function testAvailability() {
        $parent = new Container();
        $parent->add(new DummyService('alice'));

        $subject = new Container($parent);
        $subject->add(new DummyService('bob'));

        $subject2 = new Container($parent);

        $this->assertTrue($parent->isAvailable(DummyService::class));
        $this->assertTrue($parent->isAvailableLocally(DummyService::class));

        $this->assertTrue($subject->isAvailable(DummyService::class));
        $this->assertTrue($subject->isAvailableLocally(DummyService::class));

        $this->assertTrue($subject2->isAvailable(DummyService::class));
        $this->assertFalse($subject2->isAvailableLocally(DummyService::class));
    }

    public function testConstructorInjection() {
        $subject = $this->getSubject();
        $subject->add(DummyService::class);

        $helper = $subject->inject(DummyConsumer::class);
        $this->assertEquals(
            'steve is not bob',
            $helper->doSomething(new DummyService('steve')),
            'Failed to inject service into consumer instance'
        );
    }

    public function testInstanceMethodInjection() {
        $subject = $this->getSubject();
        $subject->add(DummyService::class);

        $helper = new DummyConsumer(new DummyService('steve'));
        $this->assertEquals(
            'bob is not steve',
            $subject->inject([$helper, 'doSomething']),
            'Failed to inject service into consumer method'
        );
    }

    public function testChildContainerInjection() {
        $parent = $this->getSubject();
        $parent->add(DummyService::class);

        $subject = $this->getSubject($parent);

        $self = $this;
        $this->assertServiceIsInjected($subject, function(DummyService $bob) use($self) {
            $self->assertEquals('bob', $bob->getName());
            return $bob;
        });
    }

    public function testChildContainerOverride() {
        $parent = $this->getSubject();
        $parent->add(DummyService::class);

        $subject = $this->getSubject($parent);
        $subject->add(new DummyService('alice'));

        $self = $this;
        $this->assertServiceIsInjected($subject, function(DummyService $alice) use($self) {
            $self->assertEquals('alice', $alice->getName());
            return $alice;
        });
    }

    public function testInjectionByInterface() {
        $subject = $this->getSubject();
        $subject->add(\Test\Helper\Dummy\DummyService::class);

        $self = $this;
        $this->assertServiceIsInjected($subject, function(\Test\Helper\Dummy\DummyInterface $dolan) use($self) {
            $expected = 'test';
            $self->assertEquals($expected, $dolan->echo($expected));
            return $dolan;
        });
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Cannot invoke target, cyclical dependency detected. (Test\ContainerTestDummyCyclicalService)
     */
    public function testCyclicalDependency() {
        $subject = $this->getSubject();
        $subject->add(DummyCyclicalService::class);

        $self = $this;
        $subject->inject(function(DummyCyclicalService $infinity) use ($self) {
            $self->fail('This should not be called, CyclicalService is not cyclical, can\'t test!');
        });
    }

    private function assertServiceIsInjected($container, $callable) {
        $this->assertNotNull(
            $container->inject($callable),
            'Service was not injected into callable method'
        );
    }

    private function getSubject($parent = null) {
        return new Container($parent);
    }
}

class ContainerTestDummyService {
    private $name;

    public function __construct($name = 'bob') {
        $this->name = $name;
    }

    public function getName() {
        return $this->name;
    }
}

class ContainerTestDummyConsumer {
    private $service;

    public function __construct(DummyService $service) {
        $this->service = $service;
    }

    public function doSomething(DummyService $someone) {
        return $someone->getName().' is not '.$this->service->getName();
    }
}

class ContainerTestDummyCyclicalService {
    public function __construct(ContainerTestDummyCyclicalService $self) {}
}
