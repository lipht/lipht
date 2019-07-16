<?php
namespace Lipht;

trait Fillable {
    /**
     * Fillable constructor.
     * @param array $attributes
     */
    public function __construct($attributes = []) {
        $this->fill($attributes);
    }

    /**
     * @param iterable $attributes
     */
    public function fill($attributes) : void {
        foreach((array)$attributes as $key => $value) {
            if (!property_exists($this, $key))
                continue;

            $this->{$key} = $value;
        }
    }

    /**
     * @param array $items
     * @return array
     */
    public static function many(array $items) : array {
        return array_map(
            function($item) {
                return new static($item);
            },
            $items
        );
    }
}
