# phpunit-flaptastic

[Flaptastic](https://www.flaptastic.com/) helps stop test flapping in your CICD environment such as CircleCI, TravisCI, or Jenkins.

## Installation

SpeedTrap is installable via [Composer](http://getcomposer.org) and should be added as a `require-dev` dependency:

    composer require --dev blockjon/flaptastic


## Usage

Enable with all defaults by adding the following code to your project's `phpunit.xml` file:

```xml
<phpunit bootstrap="vendor/autoload.php">
...
    <listeners>
        <listener class="BlockJon\PHPUnit\Listener\FlaptasticListener" />
    </listeners>
</phpunit>
```

That concludes the PHP config. Now you must pass the correct environment variables in your CI environment to 
have the Flaptastic plugin actually activate.

## Configuration

| Required | Environment Variable Name    | Description |
| -------- | ---------------------------- | -------------------------- |
| Yes      | FLAPTASTIC_ORGANIZATION_ID   | Organization id |
| Yes      | FLAPTASTIC_API_TOKEN         | API token |
| Yes      | FLAPTASTIC_SERVICE           | Name of service (aka microservice) under test |
| No       | FLAPTASTIC_BRANCH            | Branch name being tested. In git, you might pass "master" or names like "myFeature" |
| No       | FLAPTASTIC_COMMIT_ID         | Version id of code tested. In git, this would be the commit sha |
| No       | FLAPTASTIC_LINK              | Link to CI (Jenkins/Circle/Travis etc) website page where you can find the full details of the test run, if applicable |
| No       | FLAPTASTIC_VERBOSITY         | Stdout verbosity. 0=none (default) 1=minimal 2=everything |



## License

phpunit-speedtrap is available under the MIT License.
