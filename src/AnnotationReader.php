<?php
namespace Lipht;

class AnnotationReader {
    public static function parse($target) {
        if (is_a($target, \ReflectionClass::class))
            return static::parseClass($target);

        if (is_a($target, \ReflectionMethod::class)
            || is_a($target, \ReflectionProperty::class))
            return static::parseMember($target);
    }

    private static function parseClass(\ReflectionClass $target) {
        $annotations = static::parseDoc($target->getDocComment());
        $children = [];
        foreach ($target->getMethods() as $method) {
            if (empty($method->getDocComment()))
                continue;

            $children[$method->getName()] = static::parse($method);
        }

        return (object)[
            'tags' => $annotations,
            'methods' => (object)$children,
        ];
    }

    private static function parseMember($target) {
        $annotations = static::parseDoc($target->getDocComment());
        return (object)['tags' => $annotations];
    }

    private static function parseDoc($doc) {
        $pattern = '/@(\w+)\(((?:\s*[^\,\(\)\s]*\s*\,?)*)\)/i';
        $matches = [];
        $annotations = [];

        preg_match_all($pattern, $doc, $matches);

        array_shift($matches);

        for ($i=0; $i < count($matches[0]); $i++) {
            if (empty($matches[0][$i]))
                continue;

            $annotations[] = (object)[
                'name' => $matches[0][$i],
                'args' => array_map(function($part){
                    return trim($part);
                }, explode(',', $matches[1][$i]))
            ];
        }

        return $annotations;
    }
}
