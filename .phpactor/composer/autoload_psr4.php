<?php

/**
 * PSR-4 map for Phpactor.
 *
 * HumHub is not a Composer package, so its own namespace is added on top of the
 * autoload map of a local HumHub core copy. Set HUMHUB_IDE_CORE to override the
 * default location (see docs/DEVELOPER.md).
 */

$core = getenv('HUMHUB_IDE_CORE');

if ($core === false || $core === '') {
    $core = (getenv('HOME') ?: '') . '/.cache/phpactor/humhub-core';
}

$mapFile = $core . '/protected/vendor/composer/autoload_psr4.php';

if (!is_file($mapFile)) {
    return [];
}

$map = require $mapFile;
$map['humhub\\'] = [$core . '/protected/humhub'];

return $map;
