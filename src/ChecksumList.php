<?php

namespace Drupal\Signify;

class ChecksumList implements \Countable, \Iterator {

    // Allowed checksum algorithms and their base-16 (hex) lengths.
    protected $HASH_ALGO_BASE64_LENGTHS = array('SHA256' => 64, 'SHA512' => 128);

    protected $checksums = array();

    protected $position = 0;

    public function __construct($checksum_list_raw, $list_is_trusted)
    {
        $lines = explode("\n", $checksum_list_raw);
        foreach ($lines as $line) {
            if (trim($line) == '') {
                continue;
            }

            if (substr($line, 0, 1) === '\\') {
                throw new VerifierException('Filenames with problematic characters are not yet supported.');
            }

            $algo = substr($line, 0, strpos($line, ' '));
            if (empty($this->HASH_ALGO_BASE64_LENGTHS[$algo])) {
                throw new VerifierException("Algorithm \"$algo\" is unsupported for checksum verification.");
            }

            $filename_start = strpos($line, '(') + 1;
            $bytes_after_filename = $this->HASH_ALGO_BASE64_LENGTHS[$algo] + 4;
            $filename = substr($line, $filename_start, -$bytes_after_filename);

            $this->checksums[] = new VerifierFileChecksum($filename, $algo, substr($line, -$this->HASH_ALGO_BASE64_LENGTHS[$algo]), $list_is_trusted);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function current() {
        return $this->checksums[$this->position];
    }

    /**
     * {@inheritdoc}
     */
    public function next() {
        $this->position += 1;
    }

    /**
     * {@inheritdoc}
     */
    public function key() {
        return $this->position;
    }

    /**
     * {@inheritdoc}
     */
    public function valid() {
        return isset($this->checksums[$this->position]);
    }

    /**
     * {@inheritdoc}
     */
    public function rewind() {
        $this->position = 0;
    }

    /**
     * {@inheritdoc}
     */
    public function count() {
        return count($this->checksums);
    }

}
