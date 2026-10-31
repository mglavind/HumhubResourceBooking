<?php

use humhub\helpers\Html;
use humhub\modules\ui\icon\widgets\Icon;
use humhub\widgets\bootstrap\Badge;
use humhub\widgets\bootstrap\Button;
use humhub\widgets\modal\ModalButton;
use mglavind\humhub\modules\resourceBooking\helpers\TimeHelper;
use mglavind\humhub\modules\resourceBooking\helpers\Url;

/* @var $this \humhub\components\View */
/* @var $booking \mglavind\humhub\modules\resourceBooking\models\Booking */
/* @var $canEdit bool */
/* @var $canCancel bool */
/* @var $canManage bool */

$resource = $booking->resource;
$category = $resource?->category;
$rules = $category?->getEffectiveRules();
?>

<div class="container">
    <div class="mb-3">
        <?= Button::light(Yii::t('ResourceBookingModule.base', 'Back to calendar'))
            ->icon('chevron-left')
            ->link(Url::toIndex(['date' => $booking->getDateValue()]))
            ->sm() ?>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex flex-wrap align-items-center gap-2">
                    <div class="flex-grow-1">
                        <h1 class="h4 mb-1"><?= Html::encode($booking->title) ?></h1>
                        <div class="text-muted small">
                            <?= Html::encode($booking->getDateLabel()) ?> · <?= Html::encode($booking->getTimeRangeLabel()) ?>
                        </div>
                    </div>
                    <?= Badge::instance($booking->getStatusLabel(), $booking->getStatusColor()) ?>
                </div>

                <div class="card-body">
                    <div class="rb-detail-grid">
                        <div class="rb-detail-label"><?= Yii::t('ResourceBookingModule.base', 'Date') ?></div>
                        <div class="rb-detail-value"><?= Html::encode($booking->getDateLabel()) ?></div>

                        <div class="rb-detail-label"><?= Yii::t('ResourceBookingModule.base', 'Time') ?></div>
                        <div class="rb-detail-value"><?= Html::encode($booking->getTimeRangeLabel()) ?></div>

                        <div class="rb-detail-label"><?= Yii::t('ResourceBookingModule.base', 'Duration') ?></div>
                        <div class="rb-detail-value"><?= Html::encode($booking->getDurationLabel()) ?></div>

                        <div class="rb-detail-label"><?= Yii::t('ResourceBookingModule.base', 'Resource') ?></div>
                        <div class="rb-detail-value">
                            <?php if ($category !== null): ?>
                                <span class="rb-dot" style="background: <?= Html::encode($category->getColor()) ?>"></span>
                                <?= Html::encode($category->name) ?> ·
                            <?php endif ?>
                            <strong><?= Html::encode($resource?->name ?? '') ?></strong>
                        </div>

                        <div class="rb-detail-label"><?= Yii::t('ResourceBookingModule.base', 'Booked by') ?></div>
                        <div class="rb-detail-value">
                            <?php if ($booking->user !== null): ?>
                                <?= Html::a(Html::encode($booking->user->displayName), $booking->user->getUrl()) ?>
                            <?php else: ?>
                                <span class="text-muted"><?= Yii::t('ResourceBookingModule.base', 'Unknown user') ?></span>
                            <?php endif ?>
                        </div>

                        <div class="rb-detail-label"><?= Yii::t('ResourceBookingModule.base', 'Created') ?></div>
                        <div class="rb-detail-value"><?= Html::encode(TimeHelper::formatDateTime($booking->created_at)) ?></div>

                        <?php if ($booking->approvedBy !== null): ?>
                            <div class="rb-detail-label"><?= Yii::t('ResourceBookingModule.base', 'Handled by') ?></div>
                            <div class="rb-detail-value">
                                <?= Html::encode($booking->approvedBy->displayName) ?>
                                <?php if ($booking->approved_at !== null): ?>
                                    · <?= Html::encode(TimeHelper::formatDateTime($booking->approved_at)) ?>
                                <?php endif ?>
                            </div>
                        <?php endif ?>
                    </div>

                    <?php if (!empty($booking->description)): ?>
                        <hr>
                        <h2 class="h6 text-muted"><?= Yii::t('ResourceBookingModule.base', 'Description') ?></h2>
                        <div><?= nl2br(Html::encode($booking->description)) ?></div>
                    <?php endif ?>

                    <?php if ($booking->isCancelled() && !empty($booking->cancellation_reason)): ?>
                        <hr>
                        <div class="alert alert-secondary mb-0">
                            <strong><?= Yii::t('ResourceBookingModule.base', 'Cancellation reason') ?>:</strong>
                            <?= Html::encode($booking->cancellation_reason) ?>
                        </div>
                    <?php endif ?>
                </div>

                <?php if ($canEdit || $canCancel || ($canManage && $booking->isPending())): ?>
                    <div class="card-footer d-flex flex-wrap gap-2">
                        <?php if ($canEdit): ?>
                            <?= ModalButton::primary(Yii::t('ResourceBookingModule.base', 'Edit'))
                                ->icon('edit')
                                ->load(Url::toEdit((int)$booking->id))->sm() ?>
                        <?php endif ?>

                        <?php if ($canManage && $booking->isPending()): ?>
                            <?= Html::beginForm(['/resource-booking/booking/approve', 'id' => $booking->id], 'post', ['class' => 'd-inline']) ?>
                            <?= Button::success(Yii::t('ResourceBookingModule.base', 'Approve'))->icon('check')->submit()->sm() ?>
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
                        <?php endif ?>

                        <?php if ($canCancel): ?>
                            <?= Html::beginForm(['/resource-booking/booking/cancel', 'id' => $booking->id], 'post', ['class' => 'd-inline ms-auto']) ?>
                            <?= Button::danger(Yii::t('ResourceBookingModule.base', 'Cancel booking'))
                                ->outline()
                                ->icon('times')
                                ->submit()
                                ->confirm(
                                    Yii::t('ResourceBookingModule.base', 'Cancel booking'),
                                    Yii::t('ResourceBookingModule.base', 'Do you really want to cancel this booking?'),
                                )
                                ->sm() ?>
                            <?= Html::endForm() ?>
                        <?php endif ?>
                    </div>
                <?php endif ?>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <strong><?= Yii::t('ResourceBookingModule.base', 'Resource') ?></strong>
                </div>
                <div class="card-body">
                    <h2 class="h5 mb-1"><?= Html::encode($resource?->name ?? '') ?></h2>

                    <?php if ($category !== null): ?>
                        <div class="text-muted small mb-2">
                            <span class="rb-dot" style="background: <?= Html::encode($category->getColor()) ?>"></span>
                            <?= Html::encode($category->name) ?>
                        </div>
                    <?php endif ?>

                    <?php if (!empty($resource?->location)): ?>
                        <div class="small mb-1"><?= Icon::get('map-marker') ?> <?= Html::encode($resource->location) ?></div>
                    <?php endif ?>

                    <?php if (($resource?->capacity ?? 0) > 0): ?>
                        <div class="small mb-1"><?= Icon::get('users') ?> <?= Yii::t('ResourceBookingModule.base', 'Capacity: {n}', ['n' => $resource->capacity]) ?></div>
                    <?php endif ?>

                    <?php if (!empty($resource?->description)): ?>
                        <hr>
                        <div class="small"><?= nl2br(Html::encode($resource->description)) ?></div>
                    <?php endif ?>

                    <?php if ($category !== null && $rules !== null): ?>
                        <hr>
                        <div class="small text-muted">
                            <div><?= Yii::t('ResourceBookingModule.base', 'Bookable hours: {start} – {end}', [
                                'start' => $rules->getOpeningLabel(),
                                'end' => $rules->getClosingLabel(),
                            ]) ?></div>
                            <div><?= $rules->requiresApproval
                                ? Yii::t('ResourceBookingModule.base', 'Bookings require approval.')
                                : Yii::t('ResourceBookingModule.base', 'Bookings are confirmed instantly.') ?></div>
                        </div>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </div>
</div>
