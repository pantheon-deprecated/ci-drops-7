# ci-drops-7
CI tests for the [drops-7](https://github.com/pantheon-systems/drops-7) repository.

[![CircleCI](https://circleci.com/gh/pantheon-systems/ci-drops-7.svg?style=svg)](https://circleci.com/gh/pantheon-systems/ci-drops-7)

This repository holds the Behat tests used to verify new Drupal releases on the Pantheon platform.

## Environment Variables

The following environment variables must be set either in the Circle CI environment settings page, or the machine / environment section of the `circle.yml` file:

- **ADMIN_PASSWORD:** Used to set the password for the uid 1 user during site installation.
- **GIT_EMAIL:** Used to configure the email address for the git user for commits we make.
- **TERMINUS_SITE:** Defines the remote Pantheon site that will be used to run this test.
- **TERMINUS_ENV:** Defines the name of the Pantheon multidev environment that will be created to run this test. Set to `ci-${CIRCLE_BUILD_NUM}`
- **TERMINUS_TOKEN:** A Terminus OAuth token that has write access to the terminus site specified by `TERMINUS_SITE`.
- **TESTING_DIR:** Points to a directory on Circle CI that will hold the local clone of our test repository. Set to `/tmp/ci-drops-8`.

## Circle Configuration

Circle is configured in the Circle admin interface for the project:

- [Dependency commands](https://circleci.com/gh/pantheon-systems/drops-7/edit#setup)
- [Test commands](https://circleci.com/gh/pantheon-systems/drops-7/edit#tests)
