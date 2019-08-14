# PHP Signify

PHP library for verification of BSD Signify signature files, plus PHP and shell
implementations of verifying extended CSIG signature files.

## Use Case

Drupal's auto-update and core validation work depends on access to trusted
metadata and code assets. Because Drupal is deployed globally to diverse
environments, our implementation should support public and private mirroring
of data as well as validation by widely varying PHP releases and web host
configurations. Hosts with outdated root certificates, insecure ciphers, or
outdated TLS version support should not undermine access to these new Drupal
features.

## Why Signify?

Signify is a defacto standard created by OpenBSD to validate assets using modern
cryptography. The cryptographic foundations are entirely available in PHP's
Sodium APIs and are available for legacy PHP versions through sodium_compat.
Therefore, it is possible to implement Signify on every PHP version currently
supported by current Drupal releases.

Signify allows Drupal to anchor trust to on-disk keys shipped with the initial
installation. Combined with the broad supportability of Signify's cryptography
in PHP releases, we can establish trust on quite outdated infrastructure. This
fits with our goal of building update features that support the long tail of
installations.

## Extending Signify

OpenBSD's build and release model differs from Drupal's.

First, Drupal maintains an extensive hosted build infrastructure that covers
everything from core to arbitrary community projects. Maintaining the
availability of these systems creates challenges for protecting the secrets, and
we desire keeping the root of trust offline.

Second, our release model isn't a compelling foundation for key rotation. Our
major releases happen at too long of an interval for rotation. Our minor
releases are more frequent, but users expect cross-compatibility (and requisite
signature validation to work) across arbitrary mixes of minor releases and
module builds. Site owners may also neglect their site across quite a few minor
releases, making OpenBSD's model of shipping future release keys insufficient
for maintaining continuity (without, say, shipping the next 10 keys and having
little recourse if one of the corresponding private keys leaks).

Taking a little inspiration from X.509 -- with an emphasis on little -- we've
extended Signify to support chained signatures. We call this format CSIG.

## Chaining with CSIG

Our goal with CSIG is to support an offline root protected by an HSM. That HSM
setup should periodically produce expiring signatures against the next public
key for use within the build infrastructure.

We can accomplish this using the building blocks of Signify, but we'd like to
pack the pieces into a single file for ease of distribution and validation.

Initial setup of CSIG infrastructure:

1. Generate a keypair on the HSM.
1. Export the public key and package in Signify's format.
1. Bundle this public key with Drupal releases.

Periodic key rotation on the build infrastructure:

1. Generate a keypair on the build infrastructure. This can happen automatically but not be used until promoted into use by the final step.
1. Use the HSM to sign and embed a message containing an expiration date and the build infrastructure's public key.
1. Upload that signed message to the build infrastructure. This functions as an intermediate certificate.

Generating a CSIG for a build:

1. Generate a tagged (BSD-style) sha512sum of the built asset.
1. Sign and embed the generated checksum list (to use the OpenBSD term) using the build infrastructure's secret key.
1. Prepend the intermediate certificate.

Validating an asset using a CSIG:

1. Extract and validate the intermediate certificate against the root public key.
1. Check that the intermediate certificate remains valid (today in UTC is not after the valid-through date).
1. Extract the intermediate public key from the now-validated intermediate certificate.
1. Extract the signed checksum list.
1. Validate the signed checksum list against the intermediate public key.

### Format of a CSIG

Bold lines are annotations that do not occur in the CSIG.

* **Intermediate key and its expiration**
  * Untrusted Comment (line #1)
  * Base64-Encoded Signature by Root Secret Key (line #2)
  * **Message is an expiring public key, or xpub**
    * Valid Through Date in UTC in YYYY-MM-DD Format (line #3)
    * **Build Infrastructure Public Key in Signify Format**
      * "Untrusted" Comment (line #4)
      * Base64-Encoded Public Key (Build Infrastructure Key) (line #5)
* **Message or Checksum List signed with key on lines 4-5**
  * Untrusted Comment (line #6)
  * Base64-Encoded Signature by Build Infrastructure Key (line #7)
  * **Message or Checksum List**
    * Message or Checksum List Entries (lines 8+)

If there is only one checksum list entry, the result should be nine lines,
including a blank line at the end. Each additional checksum list entry adds one
line.

A possible point of confusion is that line 4 begins with `untrusted comment`,
but in fact it is part of the overall message signed by the Root Secret Key.
This is done to allow easy usage of the Build Infrastructure Public Key in
Signify format - it must necessarily begin with the bytes `untrusted comment`.
#### Example CSIG File

    untrusted comment: verify with root.pub
    RWT/sFZ5HK1Dq7ML8TDNwKQGd40VZMEUXyC9bdI37YscjwO9+SZoyqmRSTWbJoQeGanRYpcBY4gxvKiWDjkwrVIqAksv0g08cwI=
    2019-09-10
    untrusted comment: build infrastructure key generated 2019-08-10
    RWQ5TWYMFcc7gi3kSGCZrFm0rR4O0NnLvH603c4vMvHEvovmzzpgW8eC
    untrusted comment: verify with build-infrastructure-20190810.pub
    RWQ5TWYMFcc7gpE7lJZ2dbMK/x9iUPD08lfjGQtha9n4qIW/h7kQBjBcaYNNNKzQpJY3Xjgttm+TkxqlQNpz9sT+48mgC+xjCgY=
    SHA512 (module.zip) = f53bef3e52bcbd7d822190a7458706ff5a4b10066a52e843ef10779b55f2b6ad16c928b42def63b2204af1e7c0baaf8d9ab1d172e2b78174626f42da90a15904

#### Example CLI Creation of a CSIG File

For convenience, this example uses the `-n` option to disable passphrases.

    $ signify -G -n -p root.pub -s root.sec
    $ signify -G -n -p intermediate.pub -s intermediate.sec
    $ date --utc --iso-8601 --date="+30 days" > expiration
    $ cat expiration intermediate.pub | signify -S -e -s root.sec -m - -x intermediate.xpub.sig  # xpub = expiring public key
    $ sha512sum --tag module.zip > module-checksum-list
    $ signify -S -e -s intermediate.sec -m module-checksum-list -x module.sig
    $ cat intermediate.xpub.sig module.sig > module.csig

#### Example CLI Validation of CSIG File

Requisite files: `root.pub` `module.zip` `module.csig`

    $ head --lines=5 module.csig > intermediate.xpub.sig
    $ signify -V -e -p root.pub -m intermediate.xpub  # Verifies/extracts intermediate.xpub.sig, creates intermediate.xpub
    $ head --lines=1 intermediate.xpub  # Displays valid-through date in UTC. Should be on or after the current date in UTC.
    $ tail --lines=2 intermediate.xpub > intermediate.pub
    $ tail --lines=+6 module.csig | signify -C -p intermediate.pub -x -

## Running Tests

    sudo dnf install composer
    git clone https://github.com/drupalassociation/php-signify
    cd php-signify
    composer install
    vendor/bin/phpunit
