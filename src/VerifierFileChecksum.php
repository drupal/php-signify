<?php


namespace Drupal\Signify;

/**
 * Class VerifierFileChecksum
 * Models a particular file's expected checksum using some algorithm.
 */
class VerifierFileChecksum
{
    /**
     * @var string
     */
    public $filename;
    /**
     * @var string
     */
    public $algorithm;

    /**
     * @var string
     */
    public $hex_hash;

    /**
     * @var bool
     * Indicates whether this object contains a hash obtained from a verified source.
     */
    public $trusted;

    public function __construct($filename, $algorithm, $hex_hash, $trusted)
    {
        $this->filename = $filename;
        $this->algorithm = $algorithm;
        $this->hex_hash = $hex_hash;
        $this->trusted = $trusted;
    }
}
