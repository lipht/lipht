<?php
namespace Test\Helper;

use Lipht\Module as BaseModule;

class RootModule extends BaseModule {
    public static function dummy($callback = null) {
        return static::runInChildModule(__METHOD__, $callback);
    }

    public static function listServices() {
        return [];
    }
}
