<?php
namespace Lipht;

use JsonSerializable;
use ReflectionClass;
use ReflectionException;

abstract class Enum implements JsonSerializable {
    // element
    /** @var integer $ordinal */
    private $ordinal;

    /** @var string $name */
    private $name;

    /** @var array $extra */
    private $extra;

    // definition
    /** @var Enum[] $baked */
    private static $baked = [];

    /** @var Enum[] $properties */
    private static $properties = [];

    /**
     * Enum constructor.
     * @param int $ordinal
     * @param string $name
     * @param array $extra
     */
    private function __construct($ordinal, $name, $extra = null) {
        $this->ordinal = $ordinal;
        $this->name = $name;
        $this->extra = $extra ?? [];
    }

    /**
     * @param string $propertyName
     * @return mixed|null
     */
    public function __get($propertyName) {
        if ($propertyName === 'ordinal')
            return $this->ordinal;

        if (in_array($propertyName, array_keys($this->extra)))
            return $this->extra[$propertyName];

        return null;
    }

    /**
     * @return string
     */
    public function jsonSerialize() {
        return $this->name;
    }

    /**
     * @return string
     */
    public function __toString() {
        return $this->name;
    }

    /**
     * @return array|mixed
     * @throws ReflectionException
     * @throws \Exception
     */
    public static function values() {
        $meta = new ReflectionClass(static::class);
        if ($meta->isAbstract())
            return [];

        if (!in_array(static::class, self::$baked)) {
            $className = static::class;
            throw new \Exception("Cannot read properties of unbaked Enum ({$className}). Please bake it first");
        }

        return self::$properties[static::class];
    }

    /**
     * @throws ReflectionException
     */
    public static function bake() {
        if (in_array(static::class, self::$baked))
            return;

        $meta = new ReflectionClass(static::class);
        if ($meta->isAbstract())
            return;

        $props = array_keys($meta->getStaticProperties());

        $nextValue = 0;
        $values = [];
        $internal = array_filter(array_map(function($item) use (&$values, $meta) {
            $prop = $meta->getProperty($item);

            if (!$prop->isPublic())
                return null;

            $extra = [];
            $anns = AnnotationReader::parse($prop);

            foreach ($anns->tags as $tag) {
                $tagValue = array_filter($tag->args);
                if (count($tagValue) === 1) {
                    [$tagValue] = $tagValue;
                }

                $extra[$tag->name] = $tagValue;
            }

            $value = $prop->getValue($item);
            $value = is_numeric($value) ? intval($value) : null;

            if (in_array($value, $values)) {
                $value = null;
            }

            if (!is_null($value)) {
                $values[] = $value;
            }

            return (object) [
                'name' => $prop->getName(),
                'value' => $value,
                'extra' => $extra,
            ];
        }, $props));

        $properties = [];
        foreach ($internal as $element) {
            while(in_array($nextValue, $values)) $nextValue++;

            $value = $element->value ?? $nextValue;
            $name = $element->name;
            $extra = $element->extra;

            if (!isset($extra['label'])) {
                $extra['label'] = $name;
            }

            $values[] = $value;

            $property = new static($value, $name, $extra);
            static::$$name = $property;
            $properties[$value] = $property;
        }

        self::$baked[] = static::class;
        self::$properties[static::class] = $properties;
    }

    /**
     * @throws ReflectionException
     */
    public static function bakeAll() {
        $all = get_declared_classes();
        foreach($all as $class) {
            if (!is_subclass_of($class, Enum::class))
                continue;

            /** @var Enum $class */
            $class::bake();
        }
    }
}
