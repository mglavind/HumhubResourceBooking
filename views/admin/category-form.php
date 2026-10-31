<?php

use humhub\helpers\Html;
use humhub\widgets\bootstrap\Button;
use humhub\widgets\form\ActiveForm;
use mglavind\humhub\modules\resourceBooking\helpers\Url;
use mglavind\humhub\modules\resourceBooking\services\BookingRules;

/* @var $this \humhub\components\View */
/* @var $model \mglavind\humhub\modules\resourceBooking\models\Category */

$weekdayOptions = [];

for ($weekday = 1; $weekday <= 7; $weekday++) {
    $weekdayOptions[$weekday] = BookingRules::weekdayLabel($weekday);
}

$approvalOptions = [
    '' => Yii::t('ResourceBookingModule.base', 'Global default'),
    1 => Yii::t('ResourceBookingModule.base', 'Require approval'),
    0 => Yii::t('ResourceBookingModule.base', 'Confirm automatically'),
];

$isNew = $model->isNewRecord;
?>

<div class="container-fluid">
    <?= $this->render('_menu') ?>

    <div class="card">
        <div class="card-header">
            <strong><?= $isNew
                ? Yii::t('ResourceBookingModule.base', 'New category')
                : Yii::t('ResourceBookingModule.base', 'Edit category') ?></strong>
        </div>
        <div class="card-body">
            <?php $form = ActiveForm::begin(['acknowledge' => true]); ?>

            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="rb-form-section-title"><?= Yii::t('ResourceBookingModule.base', 'General') ?></div>

                    <?= $form->field($model, 'name')->textInput(['maxlength' => true, 'autofocus' => true]) ?>
                    <?= $form->field($model, 'description')->textarea(['rows' => 3]) ?>

                    <div class="row">
                        <div class="col-md-6">
                            <?= $form->field($model, 'color')->input('color', ['class' => 'form-control form-control-color']) ?>
                        </div>
                        <div class="col-md-3">
                            <?= $form->field($model, 'sort_order')->input('number', ['min' => 0]) ?>
                        </div>
                        <div class="col-md-3 d-flex align-items-center">
                            <?= $form->field($model, 'is_active')->checkbox() ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="rb-form-section-title"><?= Yii::t('ResourceBookingModule.base', 'Availability') ?></div>

                    <?= $form->field($model, 'requires_approval')->dropDownList($approvalOptions) ?>

                    <div class="row">
                        <div class="col-md-6">
                            <?= $form->field($model, 'opening_time')->input('time') ?>
                        </div>
                        <div class="col-md-6">
                            <?= $form->field($model, 'closing_time')->input('time') ?>
                        </div>
                    </div>

                    <?= Html::hiddenInput($model->formName() . '[allowedWeekdays]', '') ?>
                    <?= $form->field($model, 'allowedWeekdays')->checkboxList($weekdayOptions, [
                        'class' => 'd-flex flex-wrap gap-3',
                    ]) ?>
                </div>
            </div>

            <hr>

            <div class="rb-form-section-title"><?= Yii::t('ResourceBookingModule.base', 'Booking rules') ?></div>

            <div class="row">
                <div class="col-md-4">
                    <?= $form->field($model, 'min_duration_minutes')->input('number', ['min' => 0]) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'max_duration_minutes')->input('number', ['min' => 0]) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'buffer_minutes')->input('number', ['min' => 0]) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'max_advance_days')->input('number', ['min' => 0]) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'max_bookings_per_user_per_day')->input('number', ['min' => 0]) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'max_bookings_per_user_per_week')->input('number', ['min' => 0]) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'cancellation_deadline_hours')->input('number', ['min' => 0]) ?>
                </div>
            </div>

            <hr>

            <div class="d-flex gap-2">
                <?= Button::save()->submit() ?>
                <?= Button::light(Yii::t('ResourceBookingModule.base', 'Cancel'))->link(Url::toAdminCategories()) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
