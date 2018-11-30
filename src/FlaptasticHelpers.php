<?php

namespace BlockJon\PHPUnit\Listener;


class FlaptasticHelpers
{

    public static function toRelativePath($absolutePath) {
        return preg_replace("~^{$_SERVER['PWD']}/~", "", $absolutePath);
    }

    public static function stdErr($level, $message) {
        if (self::verbosityAllows($level)) {
            fwrite(STDERR, $message . "\n");
        }
    }

    public static function verbosityAllows($level) {
        $envVerbosity = getenv("FLAPTASTIC_VERBOSITY");
        if (!$envVerbosity) {
            $envVerbosity = 0;
        }
        return (int) $envVerbosity >= $level;
    }

    public static function getTestFileName($test) {
        $reflection = new \ReflectionClass(get_class($test));
        return static::toRelativePath($reflection->getFileName());
    }

    public static function getTestLineNumber($test) {
        $reflection = new \ReflectionClass(get_class($test));
        return $reflection->getMethod($test->getName())->getStartLine();
    }
}
