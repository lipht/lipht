<?php
namespace Test;

use Test\Helper\RootModule as Subject;
use Test\Helper\Dummy\DummyService;
use Test\Helper\Dummy\SubDummy\SubDummyService;

class ModuleTest extends TestCase {
    public function testModuleLoader() {
        Subject::init();
        $test = $this;

        Subject::dummy(function(DummyService $dummy) use ($test) {
            $expected = md5(rand(1000, 9999));
            $test->assertEquals($expected, $dummy->echo($expected),
                'Failed to load module context');
        });
    }

    public function testAutoSubModuleLoader() {
        Subject::init();
        $random = md5(rand(1000, 9999));
        $expected = [$random, $random];

        $result = Subject::dummy()->subDummy(function(DummyService $dummy, SubDummyService $subDummy) use ($random) {
            return [$dummy->echo($random), $subDummy->echo($random)];
        });

        $this->assertEquals($expected, $result,
            'Failed to load submodule context');
    }
}
