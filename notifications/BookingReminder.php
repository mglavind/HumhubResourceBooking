<?php

namespace mglavind\humhub\modules\resourceBooking\notifications;

use humhub\helpers\Html;
use humhub\modules\notification\components\BaseNotification;
use mglavind\humhub\modules\resourceBooking\helpers\TimeHelper;
use mglavind\humhub\modules\resourceBooking\models\Booking;
use Yii;
use yii\helpers\Url;

/**
 * Notifies a user shortly before one of their bookings starts.
 */
class BookingReminder extends BaseNotification
{
    /**
     * @inheritdoc
     */
    public $moduleId = 'resource-booking';

    /**
     * @inheritdoc
     */
    public $viewName = 'bookingReminder';

    /**
     * @inheritdoc
     */
    public $priority = true;

    /**
     * Reminders are sent by the system, so there is no originator user.
     *
     * @inheritdoc
     */
    public $requireOriginator = false;

    /**
     * @inheritdoc
     */
    public function category(): BookingReminderNotificationCategory
    {
        return new BookingReminderNotificationCategory();
    }

    /**
     * @inheritdoc
     */
    public function getUrl(): ?string
    {
        if (!$this->source instanceof Booking) {
            return null;
        }

        return Url::to(['/resource-booking/booking/view', 'id' => (int)$this->source->id], true);
    }

    /**
     * @inheritdoc
     */
    public function isValid(): bool
    {
        return $this->source instanceof Booking
            && $this->source->isConfirmed()
            && !$this->source->isPast();
    }

    /**
     * @inheritdoc
     */
    public function getMailSubject(): string
    {
        if (!$this->source instanceof Booking) {
            return Yii::t('ResourceBookingModule.base', 'Booking reminder');
        }

        /** @var Booking $booking */
        $booking = $this->source;

        return Yii::t('ResourceBookingModule.base', 'Reminder: {title} starts {when}', [
            'title' => $booking->title,
            'when' => $this->getWhenLabel($booking),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function html(): string
    {
        if (!$this->source instanceof Booking) {
            return '';
        }

        /** @var Booking $booking */
        $booking = $this->source;
        $resource = $booking->resource;

        return Yii::t('ResourceBookingModule.base', 'Reminder: your booking {title} for {resource} starts {when}.', [
            'title' => Html::tag('strong', Html::encode($booking->title)),
            'resource' => Html::tag('strong', Html::encode($resource?->name ?? '')),
            'when' => Html::tag('strong', Html::encode($this->getWhenLabel($booking))),
        ]);
    }

    /**
     * Formats the booking window in the recipient's timezone, which may differ from the timezone of the
     * long-running queue worker that sends the reminder.
     */
    private function getWhenLabel(Booking $booking): string
    {
        $timeZone = TimeHelper::timezone($this->record?->user?->time_zone);
        $start = TimeHelper::fromDatabase($booking->start_time, $timeZone);
        $end = TimeHelper::fromDatabase($booking->end_time, $timeZone);

        if ($start === null || $end === null) {
            return '';
        }

        $formatter = clone Yii::$app->formatter;
        $formatter->timeZone = $timeZone->getName();

        return $formatter->asDate($start, 'medium')
            . ', ' . $formatter->asTime($start, 'short')
            . ' – ' . $formatter->asTime($end, 'short');
    }
}
