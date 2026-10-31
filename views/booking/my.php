<?php

use humhub\helpers\Html;
use humhub\modules\ui\icon\widgets\Icon;
use humhub\widgets\bootstrap\Badge;
use humhub\widgets\bootstrap\Button;
use humhub\widgets\modal\ModalButton;
use mglavind\humhub\modules\resourceBooking\helpers\Url;
use mglavind\humhub\modules\resourceBooking\models\Booking;
use mglavind\humhub\modules\resourceBooking\permissions\CreateBooking;
use yii\widgets\LinkPager;

/* @var $this \humhub\components\View */
/* @var $dataProvider \yii\data\ActiveDataProvider */

$bookings = $dataProvider->getModels();
?>

<div class="container">
    <div class="card rb-page-header mb-3">
        <div class="card-body d-flex flex-wrap align-items-center gap-3">
            <span class="rb-page-header-icon"><?= Icon::get('list') ?></span>
            <div class="flex-grow-1">
                <h1 class="h3 mb-1"><?= Yii::t('ResourceBookingModule.base', 'My bookings') ?></h1>
                <p class="mb-0 text-muted"><?= Yii::t('ResourceBookingModule.base', 'All your resource bookings in one place.') ?></p>
            </div>
            <?php if (Yii::$app->user->can(CreateBooking::class) || Booking::canManage()): ?>
                <?= ModalButton::primary(Yii::t('ResourceBookingModule.base', 'New booking'))
                    ->icon('plus')
                    ->load(Url::toCreate())->sm() ?>
            <?php endif ?>
            <?= Button::light(Yii::t('ResourceBookingModule.base', 'Calendar'))->icon('calendar')->link(Url::toIndex())->sm() ?>
        </div>
    </div>

    <div class="card">
        <?php if ($bookings === []): ?>
            <div class="card-body rb-empty">
                <?= Icon::get('calendar-o') ?>
                <p class="mt-2 mb-0"><?= Yii::t('ResourceBookingModule.base', 'You have not made any bookings yet.') ?></p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th><?= Yii::t('ResourceBookingModule.base', 'Date') ?></th>
                        <th><?= Yii::t('ResourceBookingModule.base', 'Time') ?></th>
                        <th><?= Yii::t('ResourceBookingModule.base', 'Resource') ?></th>
                        <th><?= Yii::t('ResourceBookingModule.base', 'Title') ?></th>
                        <th><?= Yii::t('ResourceBookingModule.base', 'Status') ?></th>
                        <th class="text-end"><?= Yii::t('ResourceBookingModule.base', 'Actions') ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($bookings as $booking): ?>
                        <?php /* @var $booking Booking */ ?>
                        <tr>
                            <td class="text-nowrap"><?= Html::encode($booking->getDateLabel()) ?></td>
                            <td class="text-nowrap"><?= Html::encode($booking->getTimeRangeLabel()) ?></td>
                            <td>
                                <span class="rb-dot" style="background: <?= Html::encode($booking->resource?->getColor() ?? '#435f6f') ?>"></span>
                                <?= Html::encode($booking->resource?->name ?? '') ?>
                            </td>
                            <td><?= Html::encode($booking->title) ?></td>
                            <td><?= Badge::instance($booking->getStatusLabel(), $booking->getStatusColor()) ?></td>
                            <td class="text-end">
                                <?= Button::light(Yii::t('ResourceBookingModule.base', 'View'))
                                    ->link(Url::toView((int)$booking->id))->sm() ?>
                            </td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            </div>
            <?php if ($dataProvider->pagination->pageCount > 1): ?>
                <div class="card-footer">
                    <?= LinkPager::widget([
                        'pagination' => $dataProvider->pagination,
                        'options' => ['class' => 'pagination justify-content-center mb-0'],
                    ]) ?>
                </div>
            <?php endif ?>
        <?php endif ?>
    </div>
</div>
