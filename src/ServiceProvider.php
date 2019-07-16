<?php
namespace Lipht;

use ReflectionClass;

class ServiceProvider
{
    use Fillable;

    /** @var object */
    public $subject;

    /** @var ReflectionClass */
    public $meta;

    /** @var Callable|null */
    public $provider;
}
