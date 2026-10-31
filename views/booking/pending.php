<?php

use humhub\helpers\Html;
use humhub\modules\ui\icon\widgets\Icon;
use humhub\widgets\bootstrap\Button;
use mglavind\humhub\modules\resourceBooking\helpers\Url;
use yii\widgets\LinkPager;

/* @var $this \humhub\components\View */
/* @var $dataProvider \yii\data\ActiveDataProvider */

$bookings = $dataProvider->getModels();
?>

<div class="container">
    <div class="card rb-page-header mb-3">
        <div class="card-body d-flex flex-wrap align-items-center gap-3">
            <span class="rb-page-header-icon"><?= Icon::get('clock-o') ?></span>
            <div class="flex-grow-1">
                <h1 class="h3 mb-1"><?= Yii::t('ResourceBookingModule.base', 'Pending approvals') ?></h1>
                <p class="mb-0 text-muted"><?= Yii::t('ResourceBookingModule.base', 'Review booking requests that are waiting for confirmation.') ?></p>
            </div>
            <?= Button::light(Yii::t('ResourceBookingModule.base', 'Calendar'))->icon('calendar')->link(Url::toIndex())->sm() ?>
        </div>
    </div>

    <div class="card">
        <?php if ($bookings === []): ?>
            <div class="card-body rb-empty">
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
                        <th><?= Yii::t('ResourceBookingModule.base', 'Resource') ?></th>
                        <th class="text-end"><?= Yii::t('ResourceBookingModule.base', 'Actions') ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($bookings as $booking): ?>
                        <?php /* @var $booking Booking */ ?>
                        <tr>
                            <td class="text-nowrap">
                                <div><?= Html::encode($booking->getDateLabel()) ?></div>
                                <div class="small text-muted"><?= Html::encode($booking->getTimeRangeLabel()) ?></div>
                            </td>
                            <td>
                                <div class="fw-semibold"><?= Html::encode($booking->title) ?></div>
                                <?php if (!empty($booking->description)): ?>
                                    <div class="small text-muted"><?= Html::encode($booking->description) ?></div>
                                <?php endif ?>
                            </td>
                            <td><?= Html::encode($booking->user?->displayName ?? '') ?></td>
                            <td>
                                <span class="rb-dot" style="background: <?= Html::encode($booking->resource?->getColor() ?? '#435f6f') ?>"></span>
                                <?= Html::encode($booking->resource?->name ?? '') ?>
                            </td>
                            <td>
                                <div class="rb-table-actions">
                                    <?= Button::light(Yii::t('ResourceBookingModule.base', 'View'))
                                        ->link(Url::toView((int)$booking->id))->sm() ?>

                                    <?= Html::beginForm(['/resource-booking/booking/approve', 'id' => $booking->id], 'post', ['class' => 'd-inline']) ?>
                                    <?= Button::success(Yii::t('ResourceBookingModule.base', 'Approve'))
                                        ->icon('check')->submit()->sm() ?>
                                    <?= Html::endForm() ?>

                                    <?= Html::beginForm(['/resource-booking/booking/reject', 'id' => $booking->id], 'post', ['class' => 'd-inline']) ?>
                                    <?= Button::danger(Yii::t('ResourceBookingModule.base', 'Reject'))
                                        ->icon('times')
                                        ->submit()
                                        ->confirm(
                                            Yii::t('ResourceBookingModule.base', 'Reject booking'),
                                            Yii::t('ResourceBookingModule.base', 'Do you really want to reject this booking?'),
                                        )
                                        ->sm() ?>
                                    <?= Html::endForm() ?>
                                </div>
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
