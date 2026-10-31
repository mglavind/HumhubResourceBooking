<?php

use humhub\widgets\bootstrap\Button;
use humhub\widgets\form\ActiveForm;
use mglavind\humhub\modules\resourceBooking\helpers\Url;

/* @var $this \humhub\components\View */
/* @var $model \mglavind\humhub\modules\resourceBooking\models\Resource */
/* @var $categoryOptions array */

$isNew = $model->isNewRecord;
?>

<div class="container-fluid">
    <?= $this->render('_menu') ?>

    <div class="card">
        <div class="card-header">
            <strong><?= $isNew
                ? Yii::t('ResourceBookingModule.base', 'New resource')
                : Yii::t('ResourceBookingModule.base', 'Edit resource') ?></strong>
        </div>
        <div class="card-body">
            <?php $form = ActiveForm::begin(['acknowledge' => true]); ?>

            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="rb-form-section-title"><?= Yii::t('ResourceBookingModule.base', 'General') ?></div>

                    <?= $form->field($model, 'name')->textInput(['maxlength' => true, 'autofocus' => true]) ?>
                    <?= $form->field($model, 'category_id')->dropDownList($categoryOptions, [
                        'prompt' => Yii::t('ResourceBookingModule.base', 'Select a category'),
                    ]) ?>
                    <?= $form->field($model, 'description')->textarea(['rows' => 3]) ?>
                </div>

                <div class="col-lg-5">
                    <div class="rb-form-section-title"><?= Yii::t('ResourceBookingModule.base', 'Details') ?></div>

                    <?= $form->field($model, 'location')->textInput(['maxlength' => true]) ?>

                    <div class="row">
                        <div class="col-md-6">
                            <?= $form->field($model, 'capacity')->input('number', ['min' => 0]) ?>
                        </div>
                        <div class="col-md-6">
                            <?= $form->field($model, 'color')->textInput(['maxlength' => 7, 'placeholder' => '#435f6f']) ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <?= $form->field($model, 'sort_order')->input('number', ['min' => 0]) ?>
                        </div>
                        <div class="col-md-6 d-flex align-items-center">
                            <?= $form->field($model, 'is_active')->checkbox() ?>
                        </div>
                    </div>
                </div>
            </div>

            <hr>

            <div class="d-flex gap-2">
                <?= Button::save()->submit() ?>
                <?= Button::light(Yii::t('ResourceBookingModule.base', 'Cancel'))->link(Url::toAdminResources()) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
