<?php

namespace DrupalAssociation\Signify\Tests;

use PHPUnit\Framework\TestCase;
use DrupalAssociation\Signify\Verifier;

/**
 *  Tests for the Signify class.
 *
 *  @author David Strauss
 */
class SignifyTest extends TestCase
{

  /**
   * Check for valid syntax.
   */
  public function testIsThereAnySyntaxError()
  {
     $var = new Verifier('drupal');
     $this->assertTrue(is_object($var));
  }

  /**
   * Another test.
   */
  public function testMethod()
  {
     $public_key = file_get_contents(__DIR__ . '/fixtures/test1-php-signify.pub');
     $var = new Verifier($public_key);
     $this->assertSame($public_key, $var->getPublicKeyRaw());
  }
}

