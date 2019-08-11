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
        $this->assertEquals($message, $var->verifyMessage($signature . $message));
    }

    /**
     * Tests a successful embedded signature and message verification.
     */
    public function testPositiveVerificationEmbeddedMessage()
    {
        $public_key = file_get_contents(__DIR__ . '/fixtures/embed.pub');
        $var = new Verifier($public_key);
        $this->assertSame($public_key, $var->getPublicKeyRaw());
        $signature_and_message = file_get_contents(__DIR__ . '/fixtures/embed.sig');
        $message = file_get_contents(__DIR__ . '/fixtures/embed.txt');
        $this->assertEquals($message, $var->verifyMessage($signature_and_message));
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
        $var->verifyMessage($signature . $message);
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
        $var->verifyMessage($signature . $message);
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

    public function testVerifyChecksumList()
    {
        $public_key = file_get_contents(__DIR__ . '/fixtures/checksumlist.pub');
        $var = new Verifier($public_key);
        $signed_checksumlist = file_get_contents(__DIR__ . '/fixtures/checksumlist.sig');
        $this->assertEquals(1, $var->verifyChecksumList($signed_checksumlist, __DIR__ . '/fixtures'));
    }

    public function testVerifyChecksumFile()
    {
        $public_key = file_get_contents(__DIR__ . '/fixtures/checksumlist.pub');
        $var = new Verifier($public_key);
        $this->assertEquals(1, $var->verifyChecksumFile(__DIR__ . '/fixtures/checksumlist.sig'));
    }

    /**
     * @expectedException \DrupalAssociation\Signify\VerifierException
     * @expectedExceptionMessage File "payload-compromised.zip" does not pass checksum verification.
     */
    public function testChecksumFileCompromisedArchive()
    {
        $public_key = file_get_contents(__DIR__ . '/fixtures/checksumlist.pub');
        $var = new Verifier($public_key);
        $var->verifyChecksumFile(__DIR__ . '/fixtures/checksumlist-compromised.sig');
    }

    /**
     * @expectedException \DrupalAssociation\Signify\VerifierException
     * @expectedExceptionMessage The real path of checksum list file at
     */
    public function testChecksumFileMissing()
    {
        $public_key = file_get_contents(__DIR__ . '/fixtures/checksumlist.pub');
        $var = new Verifier($public_key);
        $var->verifyChecksumFile(__DIR__ . '/fixtures/not_a_file');
    }

    /**
     * @expectedException \DrupalAssociation\Signify\VerifierException
     * @expectedExceptionMessage could not be read.
     */
    public function testChecksumFileItselfUnreadable()
    {
        $public_key = file_get_contents(__DIR__ . '/fixtures/checksumlist.pub');
        $var = new Verifier($public_key);
        $var->verifyChecksumFile(__DIR__ . '/fixtures');
    }

    /**
     * @expectedException \DrupalAssociation\Signify\VerifierException
     * @expectedExceptionMessage File "payload.zip" in the checksum list could not be read.
     */
    public function testChecksumListUnreadableFile()
    {
        $public_key = file_get_contents(__DIR__ . '/fixtures/checksumlist.pub');
        $var = new Verifier($public_key);
        $signed_checksumlist = file_get_contents(__DIR__ . '/fixtures/checksumlist.sig');
        $var->verifyChecksumList($signed_checksumlist, __DIR__ . '/intentionally wrong path');
    }
}
