<?php

use humhub\widgets\modal\Modal;
use humhub\widgets\modal\ModalButton;

/* @var $this \humhub\components\View */
/* @var $model \mglavind\humhub\modules\resourceBooking\models\BookingForm */

$form = Modal::beginFormDialog([
    'title' => Yii::t('ResourceBookingModule.base', '<strong>New</strong> booking'),
    'size' => Modal::SIZE_LARGE,
    'footer' => ModalButton::cancel() . ' ' . ModalButton::save(Yii::t('ResourceBookingModule.base', 'Book'))->submit(),
    'form' => ['acknowledge' => true],
]);

echo $this->render('_form', [
    'form' => $form,
    'model' => $model,
]);

Modal::endFormDialog();
