<?php
namespace Lipht;

abstract class Enum {
    // element
    private $ordinal;
    private $name;
    private $label;

    private function __construct($ordinal, $name, $label = null) {
        $this->ordinal = $ordinal;
        $this->name = $name;
        $this->label = $label ?? $name;
    }

    public function __get($propertyName) {
        switch($propertyName) {
            case 'ordinal':
                return $this->ordinal;
            case 'label':
                return $this->label;
        }

        $className = static::class;
        throw new \Exception("Undefined property {$className}::{$propertyName}");
    }

    public function __toString() {
        return $this->name;
    }

    // definition
    private static $baked = [];

    public static function bake() {
        if (in_array(static::class, self::$baked))
            return;

        $meta = new \ReflectionClass(static::class);
        if ($meta->isAbstract())
            return;

        $props = array_keys($meta->getStaticProperties());

        $nextValue = 0;
        $values = [];
        $internal = array_filter(array_map(function($item) use (&$values, $meta) {
            $prop = $meta->getProperty($item);

            if (!$prop->isPublic())
                return null;

            $label = null;
            $anns = AnnotationReader::parse($prop);

            foreach ($anns->tags as $tag) {
                if ($tag->name != 'label')
                    continue;

                [$label] = $tag->args;
                break;
            }

            $value = $prop->getValue();
            $value = is_numeric($value) ? intval($value) : null;

            if (!is_null($value)
                && !in_array($value, $values)) {
                $values[] = $value;
            } else {
                $value = null;
            }

            return (object) [
                'name' => $prop->getName(),
                'value' => $value,
                'label' => $label,
            ];
        }, $props));

        foreach ($internal as $element) {
            while(in_array($nextValue, $values)) $nextValue++;

            $value = $element->value ?? $nextValue;
            $name = $element->name;
            $label = $element->label;

            $values[] = $value;

            static::$$name = new static($value, $name, $label);
        }

        self::$baked[] = static::class;
    }

    public static function bakeAll() {
        $all = get_declared_classes();
        foreach($all as $class) {
            if (!is_subclass_of($class, Enum::class))
                continue;

            $class::bake();
        }
    }
}
