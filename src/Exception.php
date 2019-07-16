<?php
namespace Lipht;

use Throwable;

class Exception extends \Exception {
    /**
     * @var array $extraData
     */
    private $extraData;

    /**
     * Exception constructor.
     * @param string $customCode
     * @param array $extraData
     * @param Throwable|null $parent
     */
    public function __construct($customCode, $extraData = [], Throwable $parent = null) {
        $this->extraData = $extraData;

        parent::__construct($customCode, 1, $parent);
    }

    /**
     * @return array
     */
    public function getExtraData() {
        return $this->extraData;
    }
}
