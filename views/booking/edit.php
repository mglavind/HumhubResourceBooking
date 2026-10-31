<?php

use humhub\widgets\modal\Modal;
use humhub\widgets\modal\ModalButton;

/* @var $this \humhub\components\View */
/* @var $model \mglavind\humhub\modules\resourceBooking\models\BookingForm */
/* @var $booking \mglavind\humhub\modules\resourceBooking\models\Booking */

$form = Modal::beginFormDialog([
    'title' => Yii::t('ResourceBookingModule.base', '<strong>Edit</strong> booking'),
    'size' => Modal::SIZE_LARGE,
    'footer' => ModalButton::cancel() . ' ' . ModalButton::save()->submit(),
    'form' => ['acknowledge' => true],
]);

echo $this->render('_form', [
    'form' => $form,
    'model' => $model,
]);

Modal::endFormDialog();
