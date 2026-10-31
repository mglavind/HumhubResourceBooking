<?php

use humhub\helpers\Html;
use humhub\modules\ui\icon\widgets\Icon;
use humhub\widgets\bootstrap\Badge;
use humhub\widgets\modal\ModalButton;
use mglavind\humhub\modules\resourceBooking\helpers\TimeHelper;
use mglavind\humhub\modules\resourceBooking\helpers\Url;
use mglavind\humhub\modules\resourceBooking\models\Booking;

/* @var $this \humhub\components\View */
/* @var $resources \mglavind\humhub\modules\resourceBooking\models\Resource[] */
/* @var $bookingsByResource array */
/* @var $dateValue string */
/* @var $canCreate bool */
?>

<div class="rb-agenda">
    <?php $today = TimeHelper::date(); ?>
    <?php $now = TimeHelper::now(); ?>
    <?php foreach ($resources as $resource): ?>
        <?php
        $bookings = $bookingsByResource[(int)$resource->id] ?? [];
        usort($bookings, static fn(Booking $a, Booking $b): int => strcmp($a->start_time, $b->start_time));

        $day = TimeHelper::date($dateValue);
        $rules = $resource->category?->getEffectiveRules();
        $canBookResource = $canCreate
            && $day >= $today
            && ($rules === null || (
                $rules->isDayAllowed((int)$day->format('N'))
                && ($day > $today || $now < $day->setTime(0, 0)->modify('+' . $rules->getClosingMinutes() . ' minutes'))
            ));
        ?>
        <section class="rb-agenda-resource">
            <div class="rb-agenda-resource-header">
                <span class="rb-dot mt-1" style="background: <?= Html::encode($resource->getColor()) ?>"></span>
                <div class="flex-grow-1 min-w-0">
                    <h3 class="h5 mb-1"><?= Html::encode($resource->name) ?></h3>
                    <div class="small text-muted">
                        <?php if ($resource->category !== null): ?>
                            <?= Html::encode($resource->category->name) ?>
                        <?php endif ?>
                        <?php if (!empty($resource->location)): ?>
                            · <?= Icon::get('map-marker') ?> <?= Html::encode($resource->location) ?>
                        <?php endif ?>
                        <?php if ($resource->capacity > 0): ?>
                            · <?= Yii::t('ResourceBookingModule.base', 'Capacity: {n}', ['n' => $resource->capacity]) ?>
                        <?php endif ?>
                        · <?= Yii::t('ResourceBookingModule.base', 'Bookings: {n}', ['n' => count($bookings)]) ?>
                    </div>
                </div>
                <?php if ($canBookResource): ?>
                    <?= ModalButton::primary(Yii::t('ResourceBookingModule.base', 'Book'))
                        ->icon('plus')
                        ->load(Url::toCreate((int)$resource->id, $dateValue))->sm() ?>
                <?php endif ?>
            </div>

            <?php if ($bookings === []): ?>
                <p class="rb-agenda-empty"><?= Yii::t('ResourceBookingModule.base', 'No bookings for this resource on this day.') ?></p>
            <?php else: ?>
                <div class="rb-agenda-list">
                    <?php foreach ($bookings as $booking): ?>
                        <a href="<?= Html::encode(Url::toView((int)$booking->id)) ?>"
                           class="rb-agenda-item<?= $booking->isPending() ? ' is-pending' : '' ?>"
                           style="--rb-booking-color: <?= Html::encode($resource->getColor()) ?>">
                            <span class="rb-agenda-time"><?= Html::encode($booking->getTimeRangeLabel()) ?></span>
                            <span class="rb-agenda-main">
                                <span class="rb-agenda-title"><?= Html::encode($booking->title) ?></span>
                                <span class="rb-agenda-meta">
                                    <?= Html::encode($booking->user?->displayName ?? '') ?>
                                    <?php if (!empty($booking->description)): ?>
                                        · <?= Html::encode($booking->description) ?>
                                    <?php endif ?>
                                </span>
                            </span>
                            <?= Badge::instance($booking->getStatusLabel(), $booking->getStatusColor()) ?>
                        </a>
                    <?php endforeach ?>
                </div>
            <?php endif ?>
        </section>
    <?php endforeach ?>
</div>
