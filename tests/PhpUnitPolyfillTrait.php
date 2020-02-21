<?php

namespace DrupalAssociation\Signify\Tests;

$php_unit_version = class_exists('PHPUnit\Runner\Version') ? \PHPUnit\Runner\Version::id() : \PHPUnit_Runner_Version::id();

if (version_compare($php_unit_version, '7.0.0') < 0) {
    trait PhpUnitPolyfillTrait
    {
        public function expectException($exception)
        {
            if (is_callable(['parent', 'expectException'])) {
                parent::expectException($exception);
            } else {
                $this->setExpectedException($exception);
            }
        }
    }
} else {
    trait PhpUnitPolyfillTrait
    {
        use PhpUnitPolyfillTrait7;
    }
}
