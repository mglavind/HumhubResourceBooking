<?php

use humhub\helpers\MailStyleHelper;
use humhub\widgets\mails\MailButton;
use humhub\widgets\mails\MailButtonList;

/* @var $this \yii\web\View */
/* @var $viewable \humhub\modules\notification\components\BaseNotification */
/* @var $url string */
/* @var $source \mglavind\humhub\modules\resourceBooking\models\Booking */
?>
<?php $this->beginContent('@notification/views/layouts/mail.php') ?>
    <table width="100%" border="0" cellspacing="0" cellpadding="0" align="left">
        <tr>
            <td style="font-size: 14px; line-height: 22px; font-family:<?= MailStyleHelper::getFontFamily() ?>; color:<?= MailStyleHelper::getTextColorHighlight() ?>; font-weight:300; text-align:left">
                <?= $viewable->html() ?>
            </td>
        </tr>
        <tr>
            <td height="10"></td>
        </tr>
        <tr>
            <td height="10"
                style="border-top: 1px solid <?= MailStyleHelper::getBackgroundColorPage() ?>"></td>
        </tr>
        <tr>
            <td height="10"></td>
        </tr>
        <tr>
            <td>
                <?= MailButtonList::widget(['buttons' => [
                    MailButton::widget([
                        'url' => $url,
                        'text' => Yii::t('ResourceBookingModule.base', 'View booking'),
                    ]),
                ]]) ?>
            </td>
        </tr>
    </table>
<?php $this->endContent();
