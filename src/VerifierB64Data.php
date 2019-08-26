<?php

namespace Drupal\Signify;

class VerifierB64Data
{
    const PKALG = 'Ed';
    const KEYNUMLEN = 8;

    public $keyNum;
    public $data;

    public function __construct($b64data, $length) {
        $decoded = base64_decode($b64data, true);
        $alg = substr($decoded, 0, 2);
        if ($alg !== self::PKALG) {
          throw new VerifierException(sprintf('Unexpected algorithm string %s', $alg));
        }
        $this->keyNum = substr($decoded, 2, self::KEYNUMLEN);
        $this->data = substr($decoded, 2 + self::KEYNUMLEN);
        if ($length !== SODIUM_CRYPTO_SIGN_BYTES && $length !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            throw new VerifierException(sprintf('Length must be %d or %d. Got %d', SODIUM_CRYPTO_SIGN_BYTES, SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES, $length));
        }
        if (strlen($this->data) !== $length) {
            throw new VerifierException('Data does not match expected length.');
        }
    }

}
