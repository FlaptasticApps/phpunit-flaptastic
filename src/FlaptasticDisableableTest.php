<?php

namespace FlaptasticApps\PHPUnit\Listener;

trait FlaptasticDisableableTest {

    static $flaptasticOrganizationId;
    static $flaptasticApiToken;
    static $flaptasticService;
    static $flaptasticVerbosity;
    static $flaptasticDisabledTestsLoaded = false;
    static $testsToSkip = [];

    /**
     * @beforeClass
     */
    public static function ensureDisabledTestsCached()
    {
        if (!self::$flaptasticDisabledTestsLoaded) {
            $client = new \GuzzleHttp\Client();
            self::$flaptasticOrganizationId = \getenv('FLAPTASTIC_ORGANIZATION_ID');
            self::$flaptasticApiToken = getenv('FLAPTASTIC_API_TOKEN');
            self::$flaptasticService = getenv('FLAPTASTIC_SERVICE');
            self::$flaptasticVerbosity = getenv('FLAPTASTIC_VERBOSITY');
            if (!self::$flaptasticVerbosity) {
                self::$flaptasticVerbosity = 0;
            } else {
                self::$flaptasticVerbosity = (int) self::$flaptasticVerbosity;
            }
            self::$flaptasticDisabledTestsLoaded = true;
            if (self::$flaptasticOrganizationId && self::$flaptasticApiToken && self::$flaptasticService) {
                try {
                    $host = getenv('FLAPTASTIC_HOST');
                    if (!$host) {
                        $host = 'https://frontend-api.flaptastic.com';
                    }
                    $orgId = self::$flaptasticOrganizationId;
                    $service = self::$flaptasticService;
                    $url = "{$host}/api/v1/skippedtests/{$orgId}/{$service}";
                    $r = $client->request(
                        'GET',
                        $url,
                        [
                            'headers' => [
                                'Bearer' => self::$flaptasticApiToken
                            ],
                            'timeout' => 5
                        ]
                    );
                    if ($r->getStatusCode() == 200) {
                        $doc = json_decode($r->getBody()->getContents(), true);
                        self::$testsToSkip = $doc;
                    }
                } catch (\Exception $e) {
                    // 
                }
            }
        }
    }

    /**
     * @before
     */
    public function conditionallySkipTest()
    {
        $skippedTests = self::$testsToSkip;
        $fileName = FlaptasticHelpers::getTestFileName($this);
        $name = $this->getName();
        if (array_key_exists($fileName, $skippedTests)) {
            foreach ($skippedTests[$fileName] as $target) {
                if ($target["name"] == $name) {
                    $this->markTestSkipped("{$fileName}:{$name} is marked as disabled in Flaptastic and is skipped.");
                }
            }
        }
        // exit(1);
    }
}
