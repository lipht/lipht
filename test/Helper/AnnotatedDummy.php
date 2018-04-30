<?php
namespace Test\Helper;

/**
 * @foo()
 * @bar(baz)
 * @baz(,bar)
 */
class AnnotatedDummy {
    /**
     * @annotate(this)
     */
    public function hello() {
    }

    /**
     * @fiz(buz, a:\w+\d+)
     * @empty(method, is, sad)
     */
    public function world() {
    }
}
