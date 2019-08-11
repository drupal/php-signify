# PHP Signify

## Use Case

Drupal's auto-update and core validation work depend on access to trusted
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
for maintaining continuity (without, say, shipping the next 10 keys).

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

1. Generate a keypair on the build infrastructure.
1. Use the HSM to sign and embed a message containing an expiration date and the build infrastructure's public key.
1. Upload that signed message to the build infrastructure. This functions as an intermediate certificate.

Generating a CSIG for a build:

1. Generate a tagged (BSD-style) sha512sum of the built asset.
1. Sign and embed the generated checksum list (to use the OpenBSD term) using the build infrastructure's secret key.
1. Prepend the intermediate certificate.

Validating an asset using a CSIG:

1. Extract and validate the intermediate certificate against the root public key.
1. Check that the intermediate certificate remains valid (is not expired).
1. Extract the intermediate public key from the now-validated intermediate certificate.
1. Extract the signed checksum list.
1. Validate the signed checksum list against the intermediate public key.

### Format of a CSIG

* Intermediate Certificate
** Untrusted Comment (1 line)
** Base64-Encoded Signature by Root Secret Key (1 line)
** Message
*** Expiration Date in YYYY-MM-DD Format (1 line)
*** Build Infrastructure Public Key in Signify Format
**** Untrusted Comment (1 line)
**** Base64-Encoded Public Key
* Signed Checksum List
** Untrusted Comment (1 line)
** Base64-Encoded Signature by Build Infrastructure Key (1 line)
** Message
*** Checksum List Entries (1 or more lines)

If there is only one checksum list entry, the result should be nine lines,
including a blank line at the end. Each additional checksum list entry adds one
line.

#### Example CSIG File

    untrusted comment: verify with root.pub
    RWT/sFZ5HK1Dq7ML8TDNwKQGd40VZMEUXyC9bdI37YscjwO9+SZoyqmRSTWbJoQeGanRYpcBY4gxvKiWDjkwrVIqAksv0g08cwI=
    2019-09-10
    untrusted comment: build infrastructure key generated 2019-08-10
    RWQ5TWYMFcc7gi3kSGCZrFm0rR4O0NnLvH603c4vMvHEvovmzzpgW8eC
    untrusted comment: verify with build-infrastructure-20190810.pub
    RWQ5TWYMFcc7gpE7lJZ2dbMK/x9iUPD08lfjGQtha9n4qIW/h7kQBjBcaYNNNKzQpJY3Xjgttm+TkxqlQNpz9sT+48mgC+xjCgY=
    SHA512 (module.zip) = f53bef3e52bcbd7d822190a7458706ff5a4b10066a52e843ef10779b55f2b6ad16c928b42def63b2204af1e7c0baaf8d9ab1d172e2b78174626f42da90a15904

#### Example CLI Manipulation of CSIG File

    $ head --lines=5 module.csig | signify -V -p root.pub -m - > expiration-and-build-pubkey
    $ head --lines=1 expiration-and-build-pubkey  # Displays expiration. Should be on or after today.
    $ tail --lines=2 expiration-and-build-pubkey > trusted-build-infra-key.pub
    $ tail --lines=+6 module.csig | signify -C -p trusted-build-infra-key.pub -x -

## Running Tests

    sudo dnf install composer phpunit8
    git clone https://github.com/drupalassociation/php-signify
    cd php-signify
    composer install
    phpunit8
