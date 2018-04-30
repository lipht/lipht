<?php
namespace Test\Helper\Dummy\SubDummy;

use Lipht\Module as BaseModule;

class Module extends BaseModule {
    public static function listServices() {
        return [
            SubDummyService::class,
        ];
    }
}
