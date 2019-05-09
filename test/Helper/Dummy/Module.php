<?php
namespace Test\Helper\Dummy;

use Lipht\Module as BaseModule;

class Module extends BaseModule {
    public static function listServices($container) {
        $container->add(DummyService::class);
    }
}
