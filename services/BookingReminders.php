<?php

namespace mglavind\humhub\modules\resourceBooking\services;

use DateTimeZone;
use mglavind\humhub\modules\resourceBooking\helpers\TimeHelper;
use mglavind\humhub\modules\resourceBooking\jobs\BookingReminderJob;
use mglavind\humhub\modules\resourceBooking\models\Booking;
use mglavind\humhub\modules\resourceBooking\Module;
use mglavind\humhub\modules\resourceBooking\notifications\BookingReminder;
use Yii;

/**
 * Schedules and sends the reminder notifications configured in the module settings.
 */
class BookingReminders
{
    /**
     * Queues a reminder for the given booking.
     */
    public static function schedule(Booking $booking): void
    {
        $minutes = static::minutesBefore();

        if (
            $minutes <= 0
            || $booking->isNewRecord
            || !$booking->isConfirmed()
            || $booking->reminder_sent_at !== null
        ) {
            return;
        }

        $start = TimeHelper::fromDatabase($booking->start_time, new DateTimeZone('UTC'));

        if ($start === null || $start <= TimeHelper::nowUtc()) {
            return;
        }

        $sendAt = $start->modify('-' . $minutes . ' minutes');
        $delay = max(0, $sendAt->getTimestamp() - time());

        Yii::$app->queue->delay($delay)->push(new BookingReminderJob([
            'bookingId' => (int)$booking->id,
        ]));
    }

    /**
     * Sends the reminder when the booking is currently inside its reminder window.
     *
     * The job may run late or belong to an outdated schedule, so the final decision is taken here
     * instead of trusting the queue delay.
     */
    public static function sendIfDue(Booking $booking): void
    {
        $minutes = static::minutesBefore();

        if (
            $minutes <= 0
            || !$booking->isConfirmed()
            || $booking->reminder_sent_at !== null
            || $booking->user === null
        ) {
            return;
        }

        $start = TimeHelper::fromDatabase($booking->start_time, new DateTimeZone('UTC'));

        if ($start === null) {
            return;
        }

        $now = TimeHelper::nowUtc();
        $sendAt = $start->modify('-' . $minutes . ' minutes');

        if ($now < $sendAt || $now >= $start) {
            return;
        }

        // Atomically claim the reminder. Concurrent or duplicated queue jobs would otherwise both send it.
        $claimed = Booking::updateAll(
            ['reminder_sent_at' => $now->format(TimeHelper::DB_FORMAT)],
            ['id' => (int)$booking->id, 'reminder_sent_at' => null],
        );

        if ($claimed === 0) {
            return;
        }

        BookingReminder::instance()
            ->about($booking)
            ->send($booking->user);
    }

    /**
     * Queues reminders for all confirmed bookings that are still in the future.
     *
     * Called when the reminder setting changes, so existing bookings benefit from it as well.
     */
    public static function rescheduleUpcoming(): void
    {
        if (static::minutesBefore() <= 0) {
            return;
        }

        $bookings = Booking::find()
            ->where(['status' => Booking::STATUS_CONFIRMED])
            ->andWhere(['reminder_sent_at' => null])
            ->andWhere(['>', 'start_time', TimeHelper::toDatabase(TimeHelper::nowUtc())])
            ->all();

        foreach ($bookings as $booking) {
            static::schedule($booking);
        }
    }

    public static function minutesBefore(): int
    {
        // Read the setting uncached: queue workers are long-running processes, so a cached value would not
        // pick up changes made from the web interface.
        return max(0, (int)Module::instance()->settings->getUncached('reminderMinutesBefore', 0));
    }
}
