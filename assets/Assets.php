<?php

namespace mglavind\humhub\modules\resourceBooking\assets;

use humhub\components\assets\AssetBundle;

class Assets extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = '@resource-booking/resources';

    /**
     * @inheritdoc
     */
    public $forceCopy = false;

    /**
     * @inheritdoc
     */
    public $css = [
        'css/resource-booking.css',
    ];

    /**
     * @inheritdoc
     */
    public $js = [
        'js/resource-booking.js',
    ];
}
