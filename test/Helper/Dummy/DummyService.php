<?php
namespace Test\Helper\Dummy;

class DummyService implements DummyInterface {
    public function echo($string) {
        return $string;
    }
}
