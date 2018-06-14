<?php
namespace Lipht;

class Exception extends \Exception {
    private $extraData;

    public function __construct($customCode, $extraData = [], \Throwable $parent = null) {
        $this->extraData = $extraData;

        parent::__construct($customCode, 1, $parent);
    }

    public function getExtraData() {
        return $this->extraData;
    }
}
