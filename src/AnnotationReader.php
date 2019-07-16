<?php
namespace Lipht;

use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

class AnnotationReader {

    private function __construct()
    {
    }

    /**
     * @param ReflectionMethod|ReflectionProperty|ReflectionClass $target
     * @return AnnotatedMember|AnnotatedClass
     * @throws \Exception
     */
    public static function parse($target) {
        if (is_a($target, ReflectionClass::class))
            return static::parseClass($target);

        if (is_a($target, ReflectionMethod::class)
            || is_a($target, ReflectionProperty::class))
            return static::parseMember($target);

        throw new \Exception('Invalid target, expected a Reflection instance');
    }

    /**
     * @param ReflectionClass $target
     * @return AnnotatedClass
     * @throws \Exception
     */
    private static function parseClass(ReflectionClass $target) {
        $annotations = static::parseDoc($target->getDocComment());
        $children = [];
        foreach ($target->getMethods() as $method) {
            if (empty($method->getDocComment()))
                continue;

            $children[$method->getName()] = static::parse($method);
        }

        return new AnnotatedClass([
            'tags' => $annotations,
            'methods' => (object) $children,
        ]);
    }

    /**
     * @param ReflectionMethod|ReflectionProperty $target
     * @return AnnotatedMember
     */
    private static function parseMember($target) {
        $annotations = static::parseDoc($target->getDocComment());

        return new AnnotatedMember([
            'tags' => $annotations
        ]);
    }

    /**
     * @param string|bool $doc
     * @return Annotation[]
     */
    private static function parseDoc($doc) {
        static $pattern = '/@(\w+)(?:\(((?:\s*[^\,\(\)\s]*\s*\,?)*)\))?/i';
        $matches = [];
        $annotations = [];

        preg_match_all($pattern, $doc, $matches);

        array_shift($matches);

        for ($i=0; $i < count($matches[0]); $i++) {
            if (empty($matches[0][$i]))
                continue;

            $args = explode(',', $matches[1][$i]);

            $annotations[] = new Annotation([
                'name' => $matches[0][$i],
                'args' => array_map(function($part){
                    return trim($part);
                }, $args)
            ]);
        }

        return $annotations;
    }
}
