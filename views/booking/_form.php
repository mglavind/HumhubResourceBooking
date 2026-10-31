<?php

/* @var $this \humhub\components\View */
/* @var $form \humhub\widgets\form\ActiveForm */
/* @var $model \mglavind\humhub\modules\resourceBooking\models\BookingForm */

$rules = $model->getEffectiveRules();
?>

<div class="row g-3">
    <div class="col-md-6">
        <?= $form->field($model, 'resource_id')->dropDownList($model->getResourceOptions(), [
            'prompt' => Yii::t('ResourceBookingModule.base', 'Select a resource'),
        ]) ?>
    </div>
    <div class="col-md-6">
        <?= $form->field($model, 'date')->input('date') ?>
    </div>
    <div class="col-md-6">
        <?= $form->field($model, 'start_time')->input('time', ['step' => 300]) ?>
    </div>
    <div class="col-md-6">
        <?= $form->field($model, 'end_time')->input('time', ['step' => 300]) ?>
    </div>
    <div class="col-12">
        <?= $form->field($model, 'title')->textInput(['maxlength' => true, 'autofocus' => true]) ?>
    </div>
    <div class="col-12">
        <?= $form->field($model, 'description')->textarea(['rows' => 4]) ?>
    </div>
</div>

<div class="alert alert-light border mt-3 mb-0 small">
    <div class="d-flex flex-wrap gap-3">
        <span><?= Yii::t('ResourceBookingModule.base', 'Bookable hours: {start} – {end}', [
            'start' => $rules->getOpeningLabel(),
            'end' => $rules->getClosingLabel(),
        ]) ?></span>
        <span><?= Yii::t('ResourceBookingModule.base', 'Duration: {min} – {max} minutes', [
            'min' => $rules->minDurationMinutes,
            'max' => $rules->maxDurationMinutes ?: '∞',
        ]) ?></span>
        <span><?= $rules->requiresApproval
            ? Yii::t('ResourceBookingModule.base', 'Approval required')
            : Yii::t('ResourceBookingModule.base', 'Instant confirmation') ?></span>
    </div>
</div>
