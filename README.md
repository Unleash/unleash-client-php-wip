# unleash-client-php
PHP client for Unleash.

## Important

THIS IS NOT READY TO BE USED YET! If you want to have a PHP client for Unleaseh, please contribute!

This client will only happen if someone wants to contribute. Just create an issue reuest if you are interested in contributing. 

# Getting started

## Installation

Install the library via composer (currently you need to provide the repository in the composer.json first):

```json
"repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/Unleash/unleash-client-php.git"
        }
    ]
```

Then you can require the library via the commandline.

```bash
composer require unleash/client
```

## Code Usage

```php

// initialize the client
$unleashClient = new \Unleash\Unleash(
        $appName,
        $url,
        $instanceId = null,
);

// to retrieve the current state of the feature flags
$unleashClient->fetch(); 

// check if a feature is enabled
if ($unleashClient->isEnabled('amazing_feature')) {
    echo 'Dude that amazing feature is enabled!';
}
```

## Usage with Unleash

See https://unleash.github.io/ for details.

## Usage with Gitlab

* Get an Gitlab Premium license or the open source contract on gitlab.com
* Open your Project
* Head over to `Operations > Feature Flags`
* Create a new feature e.g. `amazing_feature`
* Retrieve the API URL and Instance ID via the configure button
* Configure your client and roll out the code

See https://docs.gitlab.com/ee/user/project/operations/feature_flags.html for more details.

## Running the tests

We use phpunit for testing, which is installed as dev dependency. You can execute the tests by executing

```bash
phpunit
```
