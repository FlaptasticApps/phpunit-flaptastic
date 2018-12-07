<?php

namespace BlockJon\PHPUnit\Listener;

use PHPUnit\Framework\TestListener;


/**
 * Integrates with flaptastic.com to expose flappy test information.
 */
class FlaptasticListener implements TestListener
{
    public static $FLAPTASTIC_INTRODUCED = false;

    public $organizationId;
    public $apiToken;
    public $service;
    public $branch;
    public $commitId;
    public $link;
    public $verbosity;

    public $buffer = [];

    public $testType;
    public $testSuite;
    public $testException;

    function __construct() {
        $this->organizationId = getenv('FLAPTASTIC_ORGANIZATION_ID');
        $this->apiToken = getenv('FLAPTASTIC_API_TOKEN');
        $this->service = getenv('FLAPTASTIC_SERVICE');
        $this->branch = getenv('FLAPTASTIC_BRANCH');
        $this->commitId = getenv('FLAPTASTIC_COMMIT_ID');
        $this->link = getenv('FLAPTASTIC_LINK');
        $this->verbosity = getenv('FLAPTASTIC_VERBOSITY');
    }

    private function getTestFailureFileAndLine($e) {
        if ($e instanceof \PHPUnit\Framework\ExceptionWrapper) {
            $result = [
                $e->getFile(),
                $e->getLine()
            ];
        } else {
            $result = explode(":", trim(\PHPUnit\Util\Filter::getFilteredStacktrace($e)));
        }
        return (object) [
            "file" => $result[0],
            "line" => $result[1]
        ];
    }

    private function exceptionSite($file, $targetLineNumber) {
        $result = [];
        $handle = fopen($file, "r");
        $lineNumber = 1;
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                if ($lineNumber >= $targetLineNumber - 5 && $lineNumber <= $targetLineNumber + 2) {
                    $result[] = (object) ["line_number" => $lineNumber, "line" => rtrim($line)];
                }
                $lineNumber++;
            }
            fclose($handle);
        }
        return $result;
    }

    private function addNotPassedTest($type, $test, $e) {
        $status = 'failure';
        if (in_array($type, ['error', 'failed'])) {
            $status = 'error';
        } elseif (in_array($type, ['failure'])) {
            $status = 'failed';
        }
        $result = $this->getTestFailureFileAndLine($e);
        $this->buffer[] = (object) array(
            'name' => $test->getName(),
            'file' => FlaptasticHelpers::toRelativePath($result->file),
            'line' => (int) $result->line,
            'exception' => $e->getMessage(),
            'status' => $status,
            'file_stack' => $this->getFileStack($e),
            'exception_site' => $this->exceptionSite($result->file, $result->line)
        );
    }

    private function addPassedTest($test) {
        $status = 'passed';
        $file = FlaptasticHelpers::getTestFileName($test);
        $lineNumber = FlaptasticHelpers::getTestLineNumber($test);
        $this->buffer[] = (object) array(
            'name' => $test->getName(),
            'file' => $file,
            'line' => $lineNumber,
            'status' => $status
        );
    }

    private function getFileStack($e) {
        $fileStack = [];
        foreach($e->getTrace() as $item) {
            $fileStack[] = FlaptasticHelpers::toRelativePath($item["file"]);
        }
        return $fileStack;
    }

    private function occasionallyDeliver() {
        $wrapper = (object) [
            "branch" => getenv("FLAPTASTIC_BRANCH"),
            "commit_id" => getenv("FLAPTASTIC_COMMIT_ID"),
            "link" => getenv("FLAPTASTIC_LINK"),
            "organization_id" => getenv("FLAPTASTIC_ORGANIZATION_ID"),
            "service" => getenv("FLAPTASTIC_SERVICE"),
            "timestamp" => time(),
            "test_results" => $this->buffer
        ];

        $host = getenv('FLAPTASTIC_HOST');
        if (!$host) {
            $host = 'https://frontend-api.flaptastic.com';
        }
        $url = "{$host}/api/v1/ingest";

        $client = new \GuzzleHttp\Client();

        if (!$this->missingEnvVarsDetected()) {
            try {
                $r = $client->request(
                    'POST',
                    $url,
                    [
                        'json' => $wrapper,
                        'headers' => [
                            'Bearer' => getenv('FLAPTASTIC_API_TOKEN')
                        ],
                        'timeout' => 5
                    ]
                );
                if ($r->getStatusCode() == 201) {
                    $numSent = count($this->buffer);
                    FlaptasticHelpers::stdErr(2, "\n${numSent} test results uploaded to Flaptastic.\n");
                } else {
                    FlaptasticHelpers::stdErr(1, "\nFailed sending test results to Flaptastic. Got HTTP response code {$r->getStatusCode()} with response body {$r->getBody()} .\n");
                }
            } catch (\Exception $e) {
                FlaptasticHelpers::stdErr(0, "\nWarning: Failed pushing messages to flaptastic: " . $e->getMessage());
            }
        }

        // Reset the buffer.
        $this->buffer = [];
    }

    private function missingEnvVarsDetected() {
        $requiredEnvVars = [
            'FLAPTASTIC_ORGANIZATION_ID',
            'FLAPTASTIC_API_TOKEN',
            'FLAPTASTIC_SERVICE',
            'FLAPTASTIC_BRANCH'
        ];
        foreach ($requiredEnvVars as $envVarName) {
            if (!getenv($envVarName)) {
                return true;
            }
        }
        return false;
    }

    public function addError(\PHPUnit\Framework\Test $test, \Throwable $e, float $time): void
    {
        $this->testType = 'error';
        $this->testException = $e;
    }

    public function addWarning(\PHPUnit\Framework\Test $test, \PHPUnit\Framework\Warning $e, float $time): void
    {
        $this->testType = 'warning';
    }

    public function addFailure(\PHPUnit\Framework\Test $test, \PHPUnit\Framework\AssertionFailedError $e, float $time): void
    {
        $this->testType = 'failure';
        $this->testException = $e;
    }

    public function addIncompleteTest(\PHPUnit\Framework\Test $test, \Throwable $e, float $time): void
    {
        // We are not interested in incomplete tests and they are ultimately ignored.
        $this->testType = 'incomplete';
    }

    public function addRiskyTest(\PHPUnit\Framework\Test $test, \Throwable $e, float $time): void
    {
        // We dont care if php happens to deem a test is risky.
    }

    public function addSkippedTest(\PHPUnit\Framework\Test $test, \Throwable $e, float $time): void
    {
        $this->testType = 'skipped';
    }

    public function startTest(\PHPUnit\Framework\Test $test): void
    {
        // Assume tests all pass.
        $this->testType = 'passed';
        $this->testException = null;
    }

    public function endTest(\PHPUnit\Framework\Test $test, float $time): void
    {
        if ($this->testType == 'passed') {
            $this->addPassedTest($test);
        } elseif (in_array($this->testType, ['failure', 'error'])) {
            $this->addNotPassedTest($this->testType, $test, $this->testException);
        }
    }

    public function startTestSuite(\PHPUnit\Framework\TestSuite $suite): void
    {
        $this->testSuite = $suite;
        if (!static::$FLAPTASTIC_INTRODUCED) {
            if ($this->missingEnvVarsDetected()) {
                FlaptasticHelpers::stdErr(
                    1,
                    "\nFlaptastic missing env vars detected. Delivery to Flaptastic will not be attempted.\n"
                );
            } else {
                FlaptasticHelpers::stdErr(
                    1,
                    "\nFlaptastic activated for this unit test run.\n"
                );
            }
            static::$FLAPTASTIC_INTRODUCED = true;
        }
    }

    public function endTestSuite(\PHPUnit\Framework\TestSuite $suite): void
    {
        if (count($this->buffer)) {
            $this->occasionallyDeliver();
        }
    }
}
