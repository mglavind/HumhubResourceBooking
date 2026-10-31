<?php

use humhub\helpers\Html;
use humhub\modules\ui\icon\widgets\Icon;
use mglavind\humhub\modules\resourceBooking\helpers\Url;

/* @var $this \humhub\components\View */

$currentAction = Yii::$app->controller->action->id;
$isActive = static fn(array $actions): bool => in_array($currentAction, $actions, true);

$items = [
    [
        'label' => Yii::t('ResourceBookingModule.base', 'Overview'),
        'icon' => 'dashboard',
        'url' => Url::toAdmin(),
        'actions' => ['index'],
    ],
    [
        'label' => Yii::t('ResourceBookingModule.base', 'Bookings'),
        'icon' => 'calendar',
        'url' => Url::toAdminBookings(),
        'actions' => ['bookings'],
    ],
    [
        'label' => Yii::t('ResourceBookingModule.base', 'Categories'),
        'icon' => 'folder-open',
        'url' => Url::toAdminCategories(),
        'actions' => ['categories', 'category-edit', 'category-delete'],
    ],
    [
        'label' => Yii::t('ResourceBookingModule.base', 'Resources'),
        'icon' => 'building',
        'url' => Url::toAdminResources(),
        'actions' => ['resources', 'resource-edit', 'resource-delete'],
    ],
    [
        'label' => Yii::t('ResourceBookingModule.base', 'Settings'),
        'icon' => 'cogs',
        'url' => Url::toAdminSettings(),
        'actions' => ['settings'],
    ],
];
?>

<ul class="nav nav-pills rb-admin-nav mb-3">
    <?php foreach ($items as $item): ?>
        <li class="nav-item">
            <?= Html::a(
                Icon::get($item['icon']) . ' ' . Html::encode($item['label']),
                $item['url'],
                ['class' => 'nav-link' . ($isActive($item['actions']) ? ' active' : '')],
            ) ?>
        </li>
    <?php endforeach ?>
</ul>
