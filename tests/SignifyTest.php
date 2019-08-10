<?php

namespace DrupalAssociation\Signify\Tests;

use DrupalAssociation\Signify\VerifierException;
use PHPUnit\Framework\TestCase;
use DrupalAssociation\Signify\Verifier;

/**
 *  Tests for the Signify class.
 *
 * @author David Strauss
 * @author Mike Baynton
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
     * Tests a successful message verification.
     */
    public function testPositiveVerification()
    {
        $public_key = file_get_contents(__DIR__ . '/fixtures/test1-php-signify.pub');
        $var = new Verifier($public_key);
        $this->assertSame($public_key, $var->getPublicKeyRaw());
        $signature = file_get_contents(__DIR__ . '/fixtures/artifact1.php.sig');
        $message = file_get_contents(__DIR__ . '/fixtures/artifact1.php');
        $this->assertEquals($message, $var->verifyMessage($signature, $message));
    }

    /**
     * @expectedException \DrupalAssociation\Signify\VerifierException
     * @expectedExceptionMessage checked against wrong key
     */
    public function testIncorrectPubkey()
    {
        $public_key = file_get_contents(__DIR__ . '/fixtures/test2-php-signify.pub');
        $var = new Verifier($public_key);
        $signature = file_get_contents(__DIR__ . '/fixtures/artifact1.php.sig');
        $message = file_get_contents(__DIR__ . '/fixtures/artifact1.php');
        $var->verifyMessage($signature, $message);
    }

    /**
     * @expectedException \DrupalAssociation\Signify\VerifierException
     * @expectedExceptionMessage Signature did not match
     */
    public function testIncorrectMessage()
    {
        $public_key = file_get_contents(__DIR__ . '/fixtures/test1-php-signify.pub');
        $var = new Verifier($public_key);
        $signature = file_get_contents(__DIR__ . '/fixtures/artifact1.php.sig');
        $message = file_get_contents(__DIR__ . '/fixtures/artifact1.php') . 'bad message';
        $var->verifyMessage($signature, $message);
    }

    /**
     * @dataProvider invalidPublicKeys
     */
    public function testInvalidPublicKey($public_key, $exception_message)
    {
        try {
            $verifier = new Verifier($public_key);
            $verifier->getPublicKey();
            $this->fail('Invalid public key did not result in an exception.');
        } catch (VerifierException $e) {
            if(is_string($exception_message)) {
                $this->assertContains($exception_message, $e->getMessage());
            }
        }
    }

    /**
     * Data provider for testInvalidPublicKey()
     */
    public function invalidPublicKeys()
    {
        $public_key = file_get_contents(__DIR__ . '/fixtures/test1-php-signify.pub');

        $missing_comment = implode("\n", array_slice(explode("\n", $public_key), 1));
        $truncated_key = substr($public_key, 0, -3) . "\n";
        $wrong_comment = str_replace('untrusted', 'super trustworthy', $public_key);

        $pk_parts = explode("\n", $public_key);
        $epic_comment = str_pad($pk_parts[0], Verifier::COMMENTHDRLEN + Verifier::COMMENTMAXLEN + 1, 'x') . "\n" . implode("\n", array_slice($pk_parts, 1));

        return array(
            array($missing_comment, 'must contain two newlines'),
            array($truncated_key, 'Data does not match expected length'),
            array($wrong_comment, 'comment must start with'),
            array($epic_comment, sprintf('comment longer than %d bytes', Verifier::COMMENTMAXLEN))
        );
    }
}
