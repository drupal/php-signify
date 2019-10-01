# Ed25519ph Interoperability Example

The objective of this sub-project is to show interoperability for a signature generated with Ed25519ph with PHP's Sodium implementation. The reason this helps is because standard Ed25519 requires the payload to be available to the signer, which becomes unwieldy with larger files if signing is detached over a message bus (which is Drupal's goal). Ed25519ph only requires a SHA-512 hash of the content to be made available to the signer.

## Installing Dependencies

    sudo dnf install meson libsodium-devel

## Building

From this directory:

    meson builddir
    cd builddir
    ninja
