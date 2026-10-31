<?php

use humhub\helpers\Html;
use humhub\widgets\bootstrap\Button;
use humhub\widgets\form\ActiveForm;
use mglavind\humhub\modules\resourceBooking\services\BookingRules;

/* @var $this \humhub\components\View */
/* @var $model \mglavind\humhub\modules\resourceBooking\models\Configuration */

$weekdayOptions = [];

for ($weekday = 1; $weekday <= 7; $weekday++) {
    $weekdayOptions[$weekday] = BookingRules::weekdayLabel($weekday);
}

$startHours = [];
$endHours = [];

for ($hour = 0; $hour <= 23; $hour++) {
    $startHours[$hour] = sprintf('%02d:00', $hour);
}

for ($hour = 1; $hour <= 24; $hour++) {
    $endHours[$hour] = sprintf('%02d:00', $hour);
}
?>

<div class="container-fluid">
    <?= $this->render('_menu') ?>

    <div class="card">
        <div class="card-header">
            <strong><?= Yii::t('ResourceBookingModule.base', 'Booking settings') ?></strong>
        </div>
        <div class="card-body">
            <?php $form = ActiveForm::begin(['acknowledge' => true]); ?>

            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="rb-form-section-title"><?= Yii::t('ResourceBookingModule.base', 'Approval and duration') ?></div>

                    <?= $form->field($model, 'defaultRequiresApproval')->checkbox() ?>
                    <?= $form->field($model, 'defaultMinDurationMinutes')->input('number', ['min' => 1]) ?>
                    <?= $form->field($model, 'defaultMaxDurationMinutes')->input('number', ['min' => 1]) ?>
                    <?= $form->field($model, 'defaultMaxAdvanceDays')->input('number', ['min' => 0]) ?>
                    <?= $form->field($model, 'defaultBufferMinutes')->input('number', ['min' => 0]) ?>
                    <?= $form->field($model, 'defaultCancellationDeadlineHours')->input('number', ['min' => 0]) ?>
                </div>

                <div class="col-lg-6">
                    <div class="rb-form-section-title"><?= Yii::t('ResourceBookingModule.base', 'Limits') ?></div>

                    <?= $form->field($model, 'defaultMaxBookingsPerUserPerDay')->input('number', ['min' => 0]) ?>
                    <?= $form->field($model, 'defaultMaxBookingsPerUserPerWeek')->input('number', ['min' => 0]) ?>

                    <div class="rb-form-section-title mt-4"><?= Yii::t('ResourceBookingModule.base', 'Bookable hours') ?></div>

                    <div class="row">
                        <div class="col-md-6">
                            <?= $form->field($model, 'businessHoursStart')->input('time') ?>
                        </div>
                        <div class="col-md-6">
                            <?= $form->field($model, 'businessHoursEnd')->input('time') ?>
                        </div>
                    </div>

                    <?= Html::hiddenInput($model->formName() . '[allowedWeekdays]', '') ?>
                    <?= $form->field($model, 'allowedWeekdays')->checkboxList($weekdayOptions, [
                        'class' => 'd-flex flex-wrap gap-3',
                    ]) ?>

                    <div class="rb-form-section-title mt-4"><?= Yii::t('ResourceBookingModule.base', 'Day calendar') ?></div>

                    <div class="row">
                        <div class="col-md-6">
                            <?= $form->field($model, 'calendarStartHour')->dropDownList($startHours) ?>
                        </div>
                        <div class="col-md-6">
                            <?= $form->field($model, 'calendarEndHour')->dropDownList($endHours) ?>
                        </div>
                    </div>

                    <div class="rb-form-section-title mt-4"><?= Yii::t('ResourceBookingModule.base', 'Reminders') ?></div>

                    <?= $form->field($model, 'reminderMinutesBefore')->input('number', ['min' => 0, 'step' => 5]) ?>
                </div>
            </div>

            <hr>

            <div class="d-flex gap-2">
                <?= Button::save()->submit() ?>
                <?= Button::light(Yii::t('ResourceBookingModule.base', 'Back to overview'))->link(['index']) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
