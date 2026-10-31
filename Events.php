<?php

namespace mglavind\humhub\modules\resourceBooking;

use humhub\modules\admin\permissions\ManageSettings;
use humhub\modules\admin\widgets\AdminMenu;
use humhub\modules\ui\menu\MenuLink;
use humhub\widgets\TopMenu;
use Yii;
use yii\base\Event;

class Events
{
    public static function onTopMenuInit(Event $event): void
    {
        /** @var TopMenu $menu */
        $menu = $event->sender;

        $menu->addEntry(new MenuLink([
            'label' => Yii::t('ResourceBookingModule.base', 'Bookings'),
            'icon' => 'calendar',
            'url' => ['/resource-booking/index'],
            'sortOrder' => 450,
            'isVisible' => !Yii::$app->user->isGuest,
            'isActive' => self::isModuleController(['index', 'booking']),
        ]));
    }

    public static function onAdminMenuInit(Event $event): void
    {
        /** @var AdminMenu $menu */
        $menu = $event->sender;

        $menu->addEntry(new MenuLink([
            'label' => Yii::t('ResourceBookingModule.base', 'Resource Booking'),
            'icon' => 'calendar',
            'url' => ['/resource-booking/admin'],
            'sortOrder' => 1000,
            'isVisible' => Yii::$app->user->can(ManageSettings::class),
            'isActive' => self::isModuleController(['admin']),
        ]));
    }

    /**
     * @param string[] $controllers
     */
    private static function isModuleController(array $controllers): bool
    {
        return Yii::$app->controller !== null
            && Yii::$app->controller->module !== null
            && Yii::$app->controller->module->id === 'resource-booking'
            && in_array(Yii::$app->controller->id, $controllers, true);
    }
}
