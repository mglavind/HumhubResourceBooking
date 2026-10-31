<?php

use humhub\helpers\Html;
use humhub\modules\ui\icon\widgets\Icon;
use humhub\widgets\bootstrap\Badge;
use humhub\widgets\bootstrap\Button;
use mglavind\humhub\modules\resourceBooking\helpers\Url;
use mglavind\humhub\modules\resourceBooking\models\Booking;

/* @var $this \humhub\components\View */
/* @var $stats array */
/* @var $pendingBookings Booking[] */
/* @var $upcomingBookings Booking[] */

$cards = [
    [
        'label' => Yii::t('ResourceBookingModule.base', 'Categories'),
        'value' => $stats['categories'],
        'url' => Url::toAdminCategories(),
        'icon' => 'folder-open',
    ],
    [
        'label' => Yii::t('ResourceBookingModule.base', 'Resources'),
        'value' => $stats['resources'],
        'url' => Url::toAdminResources(),
        'icon' => 'building',
    ],
    [
        'label' => Yii::t('ResourceBookingModule.base', 'Pending approval'),
        'value' => $stats['pending'],
        'url' => Url::toAdminBookings(['status' => Booking::STATUS_PENDING]),
        'icon' => 'clock-o',
    ],
    [
        'label' => Yii::t('ResourceBookingModule.base', 'Upcoming bookings'),
        'value' => $stats['upcoming'],
        'url' => Url::toAdminBookings(),
        'icon' => 'calendar',
    ],
];
?>

<div class="container-fluid">
    <?= $this->render('_menu') ?>

    <div class="row g-3 mb-3">
        <?php foreach ($cards as $card): ?>
            <div class="col-6 col-xl-3">
                <a href="<?= Html::encode($card['url']) ?>" class="rb-stat-card d-block text-decoration-none">
                    <span class="rb-stat-value"><?= (int)$card['value'] ?></span>
                    <span class="rb-stat-label"><?= Icon::get($card['icon']) ?> <?= Html::encode($card['label']) ?></span>
                </a>
            </div>
        <?php endforeach ?>
    </div>

    <div class="row g-3">
        <div class="col-xl-7">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center">
                    <strong><?= Yii::t('ResourceBookingModule.base', 'Bookings waiting for approval') ?></strong>
                    <?= Button::light(Yii::t('ResourceBookingModule.base', 'All bookings'))
                        ->link(Url::toAdminBookings(['status' => Booking::STATUS_PENDING]))->sm()->cssClass('ms-auto') ?>
                </div>
                <div class="card-body p-0">
                    <?php if ($pendingBookings === []): ?>
                        <div class="rb-empty">
                            <?= Icon::get('check') ?>
                            <p class="mt-2 mb-0"><?= Yii::t('ResourceBookingModule.base', 'There are no pending bookings.') ?></p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                <tr>
                                    <th><?= Yii::t('ResourceBookingModule.base', 'When') ?></th>
                                    <th><?= Yii::t('ResourceBookingModule.base', 'Booking') ?></th>
                                    <th><?= Yii::t('ResourceBookingModule.base', 'User') ?></th>
                                    <th class="text-end"></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($pendingBookings as $booking): ?>
                                    <tr>
                                        <td class="text-nowrap">
                                            <div><?= Html::encode($booking->getDateLabel()) ?></div>
                                            <div class="small text-muted"><?= Html::encode($booking->getTimeRangeLabel()) ?></div>
                                        </td>
                                        <td>
                                            <div><?= Html::encode($booking->title) ?></div>
                                            <div class="small text-muted"><?= Html::encode($booking->resource?->name ?? '') ?></div>
                                        </td>
                                        <td><?= Html::encode($booking->user?->displayName ?? '') ?></td>
                                        <td class="text-end">
                                            <?= Button::light(Yii::t('ResourceBookingModule.base', 'Open'))
                                                ->link(Url::toView((int)$booking->id))->sm() ?>
                                        </td>
                                    </tr>
                                <?php endforeach ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif ?>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center">
                    <strong><?= Yii::t('ResourceBookingModule.base', 'Next bookings') ?></strong>
                    <?= Button::light(Yii::t('ResourceBookingModule.base', 'Calendar'))
                        ->link(Url::toIndex())->sm()->cssClass('ms-auto') ?>
                </div>
                <div class="card-body">
                    <?php if ($upcomingBookings === []): ?>
                        <p class="text-muted small mb-0"><?= Yii::t('ResourceBookingModule.base', 'No upcoming bookings.') ?></p>
                    <?php else: ?>
                        <div class="rb-list">
                            <?php foreach ($upcomingBookings as $booking): ?>
                                <?php $start = $booking->getStartLocal(); ?>
                                <a href="<?= Html::encode(Url::toView((int)$booking->id)) ?>" class="rb-list-item">
                                    <span class="rb-date-chip">
                                        <span class="rb-date-chip-day"><?= $start?->format('d') ?></span>
                                        <span class="rb-date-chip-month"><?= $start?->format('M') ?></span>
                                    </span>
                                    <span class="flex-grow-1 min-w-0">
                                        <span class="rb-list-title"><?= Html::encode($booking->title) ?></span>
                                        <span class="rb-list-meta"><?= Html::encode($booking->getTimeRangeLabel()) ?> · <?= Html::encode($booking->resource?->name ?? '') ?></span>
                                    </span>
                                    <?= Badge::instance($booking->getStatusLabel(), $booking->getStatusColor()) ?>
                                </a>
                            <?php endforeach ?>
                        </div>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </div>
</div>
