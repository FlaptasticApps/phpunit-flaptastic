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

```
use BlockJon\PHPUnit\Listener\FlaptasticDisableableTest;
```

Finally, in order to have this plugin work, go into your CI system and configure the correct environment variables as seen below.

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


## License

phpunit-flaptastic is available under the MIT License.
