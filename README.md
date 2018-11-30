# phpunit-flaptastic

[Flaptastic](https://www.flaptastic.com/) helps stop test flapping in your CICD environment such as CircleCI, TravisCI, or Jenkins. This project is registered on packagist https://packagist.org/packages/blockjon/flaptastic allowing you to easily install it with composer.

## Installation

pytest-flaptastic is installable via [Composer](http://getcomposer.org) and should be added as a `require-dev` dependency:

    composer require --dev blockjon/flaptastic


## Usage

Enable Flaptastic's autoloader in your PHPUnit `phpunit.xml` file like this:

```xml
<phpunit bootstrap="vendor/autoload.php">
...
    <listeners>
        <listener class="BlockJon\PHPUnit\Listener\FlaptasticListener" />
    </listeners>
</phpunit>
```

Next, in order to make instant test disablement work, you should add the following trait to all of your TestCase classes, or even simpler, just once to your base TestCase if your tests all extend from a common base TestCase.

*Note that PHP Trait 'use' statements must go **inside** your TestCase class.*
```
use BlockJon\PHPUnit\Listener\FlaptasticDisableableTest;
```

Finally, configure your CI environment with the correct environment variables as seen below.

## Environment Variables Configuration for CI

| Required | Environment Variable Name    | Description |
| -------- | ---------------------------- | -------------------------- |
| Yes      | FLAPTASTIC_ORGANIZATION_ID   | Organization id |
| Yes      | FLAPTASTIC_API_TOKEN         | API token |
| Yes      | FLAPTASTIC_SERVICE           | Name of service (aka microservice name) under test |
| No       | FLAPTASTIC_BRANCH            | Branch name being tested. In git, you might pass "master" or names like "myFeature". (CI systems like Circle have special variables that expose this value.) |
| No       | FLAPTASTIC_COMMIT_ID         | Version id of code tested. In git, this would be the commit sha. (CI systems like Circle have special variables that expose this value.) |
| No       | FLAPTASTIC_LINK              | Link to CI (Jenkins/Circle/Travis etc) website page where you can find the full details of the test run, if applicable. (CI systems like Circle have special variables that expose this value.) |
| No       | FLAPTASTIC_VERBOSITY         | Stdout verbosity. 0=none (default) 1=minimal 2=everything |


## CircleCI 2.0 Configuration
A simple project might have a CircleCI 2.0 YML that ultimately does a 'make test' like this:
```
      - run: make test
```
In CircleCI 2.0, we must map some of Circle's variables to Flaptastic varibles and include the Flaptastic organization id like this:
```
      - run:
          name: Run PHPUnit With Flaptastic
          environment:
            FLAPTASTIC_ORGANIZATION_ID: "<your org id goes here>"
            FLAPTASTIC_VERBOSITY: 1
          command: |
            echo 'export FLAPTASTIC_BRANCH=$CIRCLE_BRANCH' >> $BASH_ENV
            echo 'export FLAPTASTIC_LINK=$CIRCLE_BUILD_URL' >> $BASH_ENV
            echo 'export FLAPTASTIC_SERVICE=$CIRCLE_PROJECT_REPONAME' >> $BASH_ENV
            echo 'export FLAPTASTIC_COMMIT_ID=$CIRCLE_SHA1' >> $BASH_ENV
            source $BASH_ENV
            make test
```
Please be sure to pass the organization ID as you real ID value as a string with double quotes. At the time of this writing, CircleCI will botch our 64-bit integer ids without the double quotes.

Finally, go to your CircleCI project page and copy & paste your Flaptastic API token into an enviornment variable called "FLAPTASTIC_API_TOKEN" with your token value as the value and click save.

## License

phpunit-flaptastic is available under the MIT License.
