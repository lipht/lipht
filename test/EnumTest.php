<?php
namespace Test;

use Lipht\Enum;

class EnumTest extends TestCase {
    public function testEnum() {
        Enum::bakeAll();

        $this->assertEquals('foo', (string) EnumHelper::$foo);
        $this->assertEquals(0, EnumHelper::$foo->ordinal);
        $this->assertEquals('foo', EnumHelper::$foo->label);
        $this->assertEquals('bar', (string) EnumHelper::$bar);
        $this->assertEquals(3, EnumHelper::$bar->ordinal);
        $this->assertEquals('bar', EnumHelper::$bar->label);
        $this->assertEquals('baz', (string) EnumHelper::$baz);
        $this->assertEquals(1, EnumHelper::$baz->ordinal);
        $this->assertEquals('Friendly Name', EnumHelper::$baz->label);
        $this->assertEquals('custom value', EnumHelper::$bar->customAnnotation);

        $this->assertEquals([
            EnumHelper::$foo,
            EnumHelper::$bar,
            EnumHelper::$baz,
        ], EnumHelper::values());
    }

    public function testCallWithEnumType() {
        Enum::bakeAll();

        $helper = function (EnumHelper $value) {
            $this->assertEquals('baz', $value);
        };

        $helper(EnumHelper::$baz);
    }
}

class EnumHelper extends Enum {
    static $foo;

    /** @customAnnotation(custom value) */
    static $bar = 3;

    /** @label(Friendly Name) */
    static $baz;
}
