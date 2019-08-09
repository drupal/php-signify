<?php
use PHPUnit\Framework\TestCase;
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
	$var = new DrupalAssociation\Signify;
	$this->assertTrue(is_object($var));
  }

  /**
  * Another test.
  */
  public function testMethod()
  {
	$var = new DrupalAssociation\Signify;
	$this->assertEqual($var->testMethod(), 'drupal');
  }
}

