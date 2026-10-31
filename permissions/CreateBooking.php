<?php

namespace mglavind\humhub\modules\resourceBooking\permissions;

use humhub\libs\BasePermission;
use Yii;

class CreateBooking extends BasePermission
{
    /**
     * @inheritdoc
     */
    protected $moduleId = 'resource-booking';

    /**
     * @inheritdoc
     */
    protected $defaultState = self::STATE_ALLOW;

    /**
     * @inheritdoc
     */
    public function getTitle(): string
    {
        return Yii::t('ResourceBookingModule.base', 'Create bookings');
    }

    /**
     * @inheritdoc
     */
    public function getDescription(): string
    {
        return Yii::t('ResourceBookingModule.base', 'Allows the user to create and edit their own resource bookings.');
    }
}
