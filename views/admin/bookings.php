<?php

use humhub\helpers\Html;
use humhub\modules\ui\icon\widgets\Icon;
use humhub\widgets\bootstrap\Badge;
use humhub\widgets\bootstrap\Button;
use mglavind\humhub\modules\resourceBooking\helpers\Url;
use mglavind\humhub\modules\resourceBooking\models\Booking;
use yii\widgets\LinkPager;

/* @var $this \humhub\components\View */
/* @var $dataProvider \yii\data\ActiveDataProvider */
/* @var $resourceOptions array */
/* @var $filters array */

$statusOptions = [
    '' => Yii::t('ResourceBookingModule.base', 'All statuses'),
    Booking::STATUS_PENDING => Yii::t('ResourceBookingModule.base', 'Pending approval'),
    Booking::STATUS_CONFIRMED => Yii::t('ResourceBookingModule.base', 'Confirmed'),
    Booking::STATUS_REJECTED => Yii::t('ResourceBookingModule.base', 'Rejected'),
    Booking::STATUS_CANCELLED => Yii::t('ResourceBookingModule.base', 'Cancelled'),
];

$bookings = $dataProvider->getModels();
$hasFilters = $filters['status'] !== '' || $filters['resource_id'] !== null || !empty($filters['from']) || !empty($filters['to']);
?>

<div class="container-fluid">
    <?= $this->render('_menu') ?>

    <div class="card mb-3">
        <div class="card-body">
            <?= Html::beginForm(Url::toAdminBookings(), 'get', ['class' => 'row g-2 align-items-end']) ?>
            <div class="col-md-3">
                <label class="form-label small" for="rb-filter-status"><?= Yii::t('ResourceBookingModule.base', 'Status') ?></label>
                <?= Html::dropDownList('status', $filters['status'], $statusOptions, ['id' => 'rb-filter-status', 'class' => 'form-select form-select-sm']) ?>
            </div>
            <div class="col-md-3">
                <label class="form-label small" for="rb-filter-resource"><?= Yii::t('ResourceBookingModule.base', 'Resource') ?></label>
                <?= Html::dropDownList('resource_id', $filters['resource_id'], $resourceOptions, [
                    'id' => 'rb-filter-resource',
                    'prompt' => Yii::t('ResourceBookingModule.base', 'All resources'),
                    'class' => 'form-select form-select-sm',
                ]) ?>
            </div>
            <div class="col-md-2">
                <label class="form-label small" for="rb-filter-from"><?= Yii::t('ResourceBookingModule.base', 'From') ?></label>
                <?= Html::input('date', 'from', $filters['from'], ['id' => 'rb-filter-from', 'class' => 'form-control form-control-sm']) ?>
            </div>
            <div class="col-md-2">
                <label class="form-label small" for="rb-filter-to"><?= Yii::t('ResourceBookingModule.base', 'To') ?></label>
                <?= Html::input('date', 'to', $filters['to'], ['id' => 'rb-filter-to', 'class' => 'form-control form-control-sm']) ?>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <?= Button::secondary(Yii::t('ResourceBookingModule.base', 'Filter'))->submit()->sm() ?>
                <?php if ($hasFilters): ?>
                    <?= Button::light(Yii::t('ResourceBookingModule.base', 'Reset'))->link(Url::toAdminBookings())->sm() ?>
                <?php endif ?>
            </div>
            <?= Html::endForm() ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <strong><?= Yii::t('ResourceBookingModule.base', 'All bookings') ?></strong>
        </div>

        <?php if ($bookings === []): ?>
            <div class="card-body rb-empty">
                <?= Icon::get('calendar-o') ?>
                <p class="mt-2 mb-0"><?= Yii::t('ResourceBookingModule.base', 'No bookings match the current filter.') ?></p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th><?= Yii::t('ResourceBookingModule.base', 'When') ?></th>
                        <th><?= Yii::t('ResourceBookingModule.base', 'Booking') ?></th>
                        <th><?= Yii::t('ResourceBookingModule.base', 'Resource') ?></th>
                        <th><?= Yii::t('ResourceBookingModule.base', 'User') ?></th>
                        <th><?= Yii::t('ResourceBookingModule.base', 'Status') ?></th>
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
                            <td>
                                <span class="rb-dot" style="background: <?= Html::encode($booking->resource?->getColor() ?? '#435f6f') ?>"></span>
                                <?= Html::encode($booking->resource?->name ?? '') ?>
                            </td>
                            <td><?= Html::encode($booking->user?->displayName ?? '') ?></td>
                            <td>
                                <?= Badge::instance($booking->getStatusLabel(), $booking->getStatusColor()) ?>
                                <?php if ($booking->approvedBy !== null): ?>
                                    <div class="small text-muted"><?= Html::encode($booking->approvedBy->displayName) ?></div>
                                <?php endif ?>
                            </td>
                            <td>
                                <div class="rb-table-actions">
                                    <?= Button::light(Yii::t('ResourceBookingModule.base', 'View'))
                                        ->link(Url::toView((int)$booking->id))->sm() ?>

                                    <?php if ($booking->isPending()): ?>
                                        <?= Html::beginForm(['/resource-booking/admin/approve', 'id' => $booking->id], 'post', ['class' => 'd-inline']) ?>
                                        <?= Button::success(Yii::t('ResourceBookingModule.base', 'Approve'))
                                            ->icon('check')->submit()->sm() ?>
                                        <?= Html::endForm() ?>

                                        <?= Html::beginForm(['/resource-booking/admin/reject', 'id' => $booking->id], 'post', ['class' => 'd-inline']) ?>
                                        <?= Button::danger(Yii::t('ResourceBookingModule.base', 'Reject'))
                                            ->icon('times')
                                            ->submit()
                                            ->confirm(
                                                Yii::t('ResourceBookingModule.base', 'Reject booking'),
                                                Yii::t('ResourceBookingModule.base', 'Do you really want to reject this booking?'),
                                            )
                                            ->sm() ?>
                                        <?= Html::endForm() ?>
                                    <?php elseif ($booking->isConfirmed()): ?>
                                        <?= Html::beginForm(['/resource-booking/admin/cancel', 'id' => $booking->id], 'post', ['class' => 'd-inline']) ?>
                                        <?= Button::danger(Yii::t('ResourceBookingModule.base', 'Cancel'))
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
