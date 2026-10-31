<?php

namespace mglavind\humhub\modules\resourceBooking\notifications;

use humhub\modules\notification\components\NotificationCategory;
use Yii;

/**
 * Notification category for booking reminders.
 */
class BookingReminderNotificationCategory extends NotificationCategory
{
    /**
     * @inheritdoc
     */
    public $id = 'resourceBookingReminder';

    /**
     * @inheritdoc
     */
    public function getTitle(): string
    {
        return Yii::t('ResourceBookingModule.base', 'Booking reminders');
    }

    /**
     * @inheritdoc
     */
    public function getDescription(): string
    {
        return Yii::t('ResourceBookingModule.base', 'Receive a reminder before one of your bookings starts.');
    }
}
