<?php
namespace Test;

use Lipht\AnnotationReader;
use Test\Helper\AnnotatedDummy;

class AnnotationReaderTest extends TestCase {
    public function testParseClass() {
        $class = new \ReflectionClass(AnnotatedDummy::class);

        $expected = (object)[
            'tags' => [
                (object)[
                    'name' => 'foo',
                    'args' => ['']
                ],
                (object)[
                    'name' => 'bar',
                    'args' => ['baz']
                ],
                (object)[
                    'name' => 'baz',
                    'args' => ['', 'bar']
                ],
            ],
            'methods' => (object)[
                'hello' => (object)[
                    'tags' => [
                        (object)[
                            'name' => 'annotate',
                            'args' => ['this'],
                        ],
                    ],
                ],
                'world' => (object)[
                    'tags' => [
                        (object)[
                            'name' => 'fiz',
                            'args' => ['buz', 'a:\w+\d+'],
                        ],
                        (object)[
                            'name' => 'empty',
                            'args' => ['method', 'is', 'sad'],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, AnnotationReader::parse($class),
            'Bad format');
    }
}
