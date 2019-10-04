<?php

namespace Drupal\Signify;

use Iterator;

class FailedCheckumFilter extends \FilterIterator {

    protected $workingDirectory;

    public function __construct(Iterator $iterator, $working_directory) {
        parent::__construct($iterator);
        $this->workingDirectory = $working_directory;
    }

    /**
     * {@inheritdoc}
     */
    public function accept() {
        /** @var \Drupal\Signify\VerifierFileChecksum $checksum */
        $checksum = $this->current();
        $hash_file_path = $this->workingDirectory . DIRECTORY_SEPARATOR . $checksum->filename;
        $algorithm = strtolower($checksum->algorithm);
        $hash = @hash_file($algorithm, $hash_file_path);
        return $hash !== $checksum->hex_hash;
    }

}
