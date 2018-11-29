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
        $result = explode(":", trim(\PHPUnit\Util\Filter::getFilteredStacktrace($e)));
        return (object) [
            "file" => $result[0],
            "line" => $result[1]
        ];
    }

    private function toRelativePath($absolutePath) {
        return preg_replace("~^{$_SERVER['PWD']}/~", "", $absolutePath);
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
            'file' => $this->toRelativePath($result->file),
            'line' => (int) $result->line,
            'exception' => $e->getMessage(),
            'status' => $status,
            'file_stack' => $this->getFileStack($e),
            'exception_site' => $this->exceptionSite($result->file, $result->line)
        );
    }

    private function addPassedTest($test) {
        $status = 'passed';
        $file = $this->getTestFileName($test);
        $lineNumber = $this->getTestLineNumber($test);
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
            $fileStack[] = $this->toRelativePath($item["file"]);
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
                    // yay it worked.
                }
            } catch (\Exception $e) {
                $this->stdErr(0, "\nWarning: Failed pushing messages to flaptastic: " . $e->getMessage());
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

    private function stdErr($level, $message) {
        if ($this->verbosityAllows($level)) {
            fwrite(STDERR, $message . "\n");
        }
    }

    private function verbosityAllows($level) {
        $verbosity = getenv("FLAPTASTIC_VERBOSITY");
        return !$verbosity || (int) $verbosity >= $level;
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
        printf( "Test '%s' ended with state {$this->testType}.\n", $test->getName());
        if ($this->testType == 'passed') {
            $this->addPassedTest($test);
        } elseif (in_array($this->testType, ['failure', 'error'])) {
            $this->addNotPassedTest($this->testType, $test, $this->testException);
        }
    }

    public function startTestSuite(\PHPUnit\Framework\TestSuite $suite): void
    {
        $this->testSuite = $suite;
        printf("TestSuite '%s' started.\n", $suite->getName());
        if ($this->missingEnvVarsDetected() && !static::$FLAPTASTIC_INTRODUCED) {
            $this->stdErr(2, "\nFlaptastic missing env vars detected. Delivery to Flaptastic will not be attempted.\n");
            static::$FLAPTASTIC_INTRODUCED = true;
        }
    }

    public function getTestFileName($test) {
        $reflection = new \ReflectionClass(get_class($test));
        return $this->toRelativePath($reflection->getFileName());
    }

    public function getTestLineNumber($test) {
        $reflection = new \ReflectionClass(get_class($test));
        return $reflection->getMethod($test->getName())->getStartLine();
    }

    public function endTestSuite(\PHPUnit\Framework\TestSuite $suite): void
    {
        if (count($this->buffer)) {
            $this->occasionallyDeliver();
        }
    }
}
