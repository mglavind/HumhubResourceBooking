<?php

/**
 * Class map for Phpactor.
 *
 * The global Yii class is normally loaded through Composer's "files" autoloading,
 * which Phpactor does not evaluate, so it is registered explicitly here.
 */

$core = getenv('HUMHUB_IDE_CORE');

if ($core === false || $core === '') {
    $core = (getenv('HOME') ?: '') . '/.cache/phpactor/humhub-core';
}

$mapFile = $core . '/protected/vendor/composer/autoload_classmap.php';

if (!is_file($mapFile)) {
    return [];
}

$map = require $mapFile;
$map['Yii'] = $core . '/protected/vendor/yiisoft/yii2/Yii.php';

return $map;
