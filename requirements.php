<?php

if (version_compare(PHP_VERSION, '8.2.0', '<')) {
    return 'The Resource Booking module requires PHP 8.2 or later.';
}

if (!Yii::$app->urlManager->enablePrettyUrl && !(defined('YII_ENV_TEST') && YII_ENV_TEST)) {
    return 'Please enable URL Rewriting: https://docs.humhub.org/docs/admin/installation/#pretty-urls';
}

return null;
