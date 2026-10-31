<?php

$testRoot = dirname(__DIR__);

\Codeception\Configuration::append(['test_root' => $testRoot]);

$humhubPath = getenv('HUMHUB_PATH');

if ($humhubPath === false) {
    $moduleConfig = require $testRoot . '/config/test.php';
    $humhubPath = $moduleConfig['humhub_root'] ?? dirname(__DIR__, 5);
}

\Codeception\Configuration::append(['humhub_root' => $humhubPath]);

$globalConfig = require $humhubPath . '/protected/humhub/tests/codeception/_loadConfig.php';

require $globalConfig['humhub_root'] . '/protected/humhub/tests/codeception/_bootstrap.php';
