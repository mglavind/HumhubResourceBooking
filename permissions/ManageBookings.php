<?php

namespace mglavind\humhub\modules\resourceBooking\permissions;

use humhub\libs\BasePermission;
use humhub\modules\user\models\Group;
use Yii;

class ManageBookings extends BasePermission
{
    /**
     * @inheritdoc
     */
    protected $moduleId = 'resource-booking';

    /**
     * @inheritdoc
     */
    protected $defaultState = self::STATE_DENY;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $this->fixedGroups[] = Group::getAdminGroupId();

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function getDefaultState($groupId): int
    {
        if ((int)$groupId === Group::getAdminGroupId()) {
            return self::STATE_ALLOW;
        }

        return parent::getDefaultState($groupId);
    }

    /**
     * @inheritdoc
     */
    public function getTitle(): string
    {
        return Yii::t('ResourceBookingModule.base', 'Manage bookings');
    }

    /**
     * @inheritdoc
     */
    public function getDescription(): string
    {
        return Yii::t('ResourceBookingModule.base', 'Allows the user to approve, reject and cancel bookings of other users.');
    }
}
