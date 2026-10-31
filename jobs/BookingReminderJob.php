<?php

namespace mglavind\humhub\modules\resourceBooking\jobs;

use mglavind\humhub\modules\resourceBooking\models\Booking;
use mglavind\humhub\modules\resourceBooking\services\BookingReminders;
use yii\base\BaseObject;
use yii\queue\JobInterface;

/**
 * Delayed queue job that sends a booking reminder once the reminder window is reached.
 */
class BookingReminderJob extends BaseObject implements JobInterface
{
    public int $bookingId = 0;

    /**
     * @inheritdoc
     */
    public function execute($queue): void
    {
        $booking = Booking::findOne($this->bookingId);

        if ($booking === null) {
            return;
        }

        BookingReminders::sendIfDue($booking);
    }
}
