<?php
namespace Test;

use Lipht\AnnotatedClass;
use Lipht\AnnotatedMember;
use Lipht\Annotation;
use Lipht\AnnotationReader;
use Test\Helper\AnnotatedDummy;

class AnnotationReaderTest extends TestCase {
    public function testParseClass() {
        $class = new \ReflectionClass(AnnotatedDummy::class);

        $expected = new AnnotatedClass([
            'tags' => [
                new Annotation([
                    'name' => 'foo',
                    'args' => ['']
                ]),
                new Annotation([
                    'name' => 'bar',
                    'args' => ['baz']
                ]),
                new Annotation([
                    'name' => 'baz',
                    'args' => ['', 'bar']
                ]),
            ],
            'methods' => (object)[
                'hello' => new AnnotatedMember([
                    'tags' => [
                        new Annotation([
                            'name' => 'annotate',
                            'args' => ['this'],
                        ]),
                    ],
                ]),
                'world' => new AnnotatedMember([
                    'tags' => [
                        new Annotation([
                            'name' => 'fiz',
                            'args' => ['buz', 'a:\w+\d+'],
                        ]),
                        new Annotation([
                            'name' => 'empty',
                            'args' => ['method', 'is', 'sad'],
                        ]),
                    ],
                ]),
            ],
        ]);

        $this->assertEquals($expected, AnnotationReader::parse($class),
            'Bad format');
    }

    public function testParseLambdaFunctions()
    {
        $expected = new AnnotatedMember([
            'tags' => [
                new Annotation([
                    'name' => 'foo',
                    'args' => ['bar'],
                ]),
            ]
        ]);

        $this->assertEquals(
            $expected,
            AnnotationReader::parse(new \ReflectionFunction(/** @foo(bar) */function(){ echo "Hello"; }))
        );
    }
}
