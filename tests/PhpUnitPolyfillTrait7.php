<?php

namespace DrupalAssociation\Signify\Tests;

trait PhpUnitPolyfillTrait7
{
    public function expectException(string $exception): void
    {
        if (is_callable(['parent', 'expectException'])) {
            parent::expectException($exception);
        } else {
            $this->setExpectedException($exception);
        }
    }
}
