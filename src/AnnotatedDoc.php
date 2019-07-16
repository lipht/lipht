<?php


namespace Lipht;


class AnnotatedDoc
{
    use Fillable;

    /**
     * @var Annotation[]
     */
    public $tags;

    /**
     * @var object
     */
    public $methods;
}