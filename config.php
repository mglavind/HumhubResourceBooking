<?php

use humhub\modules\admin\widgets\AdminMenu;
use humhub\widgets\TopMenu;
use mglavind\humhub\modules\resourceBooking\Events;

return [
    'id' => 'resource-booking',
    'class' => 'mglavind\humhub\modules\resourceBooking\Module',
    'namespace' => 'mglavind\humhub\modules\resourceBooking',
    'events' => [
        [TopMenu::class, TopMenu::EVENT_INIT, [Events::class, 'onTopMenuInit']],
        [AdminMenu::class, AdminMenu::EVENT_INIT, [Events::class, 'onAdminMenuInit']],
    ],
];
