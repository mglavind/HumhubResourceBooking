<?php

namespace mglavind\humhub\modules\resourceBooking\models;

use DateTimeImmutable;
use mglavind\humhub\modules\resourceBooking\helpers\TimeHelper;
use mglavind\humhub\modules\resourceBooking\services\BookingReminders;
use mglavind\humhub\modules\resourceBooking\services\BookingRules;
use Yii;
use yii\base\Model;

class BookingForm extends Model
{
    public ?Booking $booking = null;

    public $resource_id;
    public $date;
    public $start_time;
    public $end_time;
    public $title;
    public $description;

    private ?Resource $_resource = null;

    public function __construct(?Booking $booking = null, $config = [])
    {
        $this->booking = $booking;
        parent::__construct($config);

        if ($booking !== null && !$booking->isNewRecord) {
            $this->fillFromBooking($booking);
        }
    }

    public function init(): void
    {
        parent::init();

        $this->resource_id = is_scalar($this->resource_id) ? $this->resource_id : null;
        $this->date = is_scalar($this->date) ? (string)$this->date : '';
        $this->start_time = is_scalar($this->start_time) ? (string)$this->start_time : '';
        $this->end_time = is_scalar($this->end_time) ? (string)$this->end_time : '';
        $this->title = is_scalar($this->title) ? (string)$this->title : '';
        $this->description = is_scalar($this->description) ? (string)$this->description : '';

        if ($this->booking === null) {
            $this->booking = new Booking();
        }

        if (!$this->booking->isNewRecord) {
            return;
        }

        if (empty($this->date)) {
            $this->date = TimeHelper::now()->format(TimeHelper::DATE_FORMAT);
        }

        if (empty($this->resource_id)) {
            $this->resource_id = Resource::findActive()->select('r.id')->scalar() ?: null;
        }

        if (empty($this->start_time)) {
            $rules = $this->getEffectiveRules();
            $now = TimeHelper::now();
            $today = TimeHelper::date();
            $selectedDate = TimeHelper::date($this->date);

            if ($selectedDate < $today) {
                $selectedDate = $today;
                $this->date = $selectedDate->format(TimeHelper::DATE_FORMAT);
            }

            $closing = $selectedDate->setTime(0, 0)->modify('+' . $rules->getClosingMinutes() . ' minutes');

            if ($selectedDate->format('Y-m-d') === $now->format('Y-m-d') && $now >= $closing) {
                $this->date = $selectedDate->modify('+1 day')->format(TimeHelper::DATE_FORMAT);
            }

            $this->start_time = $this->defaultStartTime();
        }

        if (empty($this->end_time)) {
            $this->end_time = $this->defaultEndTime($this->start_time);
        }

        if ($this->title === null) {
            $this->title = '';
        }
    }

    public function beforeValidate(): bool
    {
        foreach (['date', 'start_time', 'end_time', 'title', 'description'] as $attribute) {
            $this->$attribute = is_scalar($this->$attribute) ? (string)$this->$attribute : '';
        }

        $this->resource_id = is_scalar($this->resource_id) ? $this->resource_id : null;
        $this->title = trim((string)$this->title);
        $this->description = trim((string)$this->description);

        return parent::beforeValidate();
    }

    public function rules(): array
    {
        return [
            [['resource_id', 'title', 'date', 'start_time', 'end_time'], 'required'],
            [['resource_id'], 'integer'],
            [['title'], 'string', 'min' => 2, 'max' => 255],
            [['description'], 'string', 'max' => 5000],
            [['date'], 'validateDate'],
            [['start_time', 'end_time'], 'validateTimeRange'],
            [['resource_id'], 'validateResource'],
            [['resource_id', 'date', 'start_time', 'end_time'], 'validateBookingRules'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'resource_id' => Yii::t('ResourceBookingModule.base', 'Resource'),
            'date' => Yii::t('ResourceBookingModule.base', 'Date'),
            'start_time' => Yii::t('ResourceBookingModule.base', 'Start time'),
            'end_time' => Yii::t('ResourceBookingModule.base', 'End time'),
            'title' => Yii::t('ResourceBookingModule.base', 'Title'),
            'description' => Yii::t('ResourceBookingModule.base', 'Description'),
        ];
    }

    public function attributeHints(): array
    {
        return [
            'title' => Yii::t('ResourceBookingModule.base', 'For example the purpose of the booking.'),
            'description' => Yii::t('ResourceBookingModule.base', 'Optional details for other users and the booking manager.'),
        ];
    }

    public function validateDate(): void
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$this->date)) {
            $this->addError('date', Yii::t('ResourceBookingModule.base', 'Please enter a valid date.'));
            return;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $this->date, TimeHelper::userTimezone());
        $errors = DateTimeImmutable::getLastErrors();

        if (
            $date === false
            || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
            || $date->format('Y-m-d') !== $this->date
        ) {
            $this->addError('date', Yii::t('ResourceBookingModule.base', 'Please enter a valid date.'));
        }
    }

    public function validateTimeRange(): void
    {
        $start = TimeHelper::combine((string)$this->date, (string)$this->start_time);
        $end = TimeHelper::combine((string)$this->date, (string)$this->end_time);

        if ($start === null) {
            $this->addError('start_time', Yii::t('ResourceBookingModule.base', 'Please enter a valid start time.'));
        }

        if ($end === null) {
            $this->addError('end_time', Yii::t('ResourceBookingModule.base', 'Please enter a valid end time.'));
        }

        if ($start !== null && $end !== null && $end <= $start) {
            $this->addError('end_time', Yii::t('ResourceBookingModule.base', 'The end time must be after the start time.'));
        }
    }

    public function validateResource(): void
    {
        $this->_resource = null;

        if ($this->hasErrors('resource_id') || empty($this->resource_id)) {
            return;
        }

        $resource = Resource::find()
            ->where(['id' => (int)$this->resource_id])
            ->with('category')
            ->one();

        if (
            $resource === null
            || !$resource->is_active
            || $resource->category === null
            || !$resource->category->is_active
        ) {
            $this->addError('resource_id', Yii::t('ResourceBookingModule.base', 'The selected resource is not available.'));
            return;
        }

        $this->_resource = $resource;
    }

    public function validateBookingRules(): void
    {
        if ($this->hasErrors()) {
            return;
        }

        $resource = $this->getResourceModel();
        $category = $resource?->category;

        if ($resource === null || $category === null) {
            $this->addError('resource_id', Yii::t('ResourceBookingModule.base', 'The selected resource is not available.'));
            return;
        }

        $rules = $category->getEffectiveRules();
        $start = TimeHelper::combine((string)$this->date, (string)$this->start_time);
        $end = TimeHelper::combine((string)$this->date, (string)$this->end_time);

        if ($start === null || $end === null) {
            return;
        }

        if ($start->getTimestamp() < time()) {
            $this->addError('start_time', Yii::t('ResourceBookingModule.base', 'Bookings cannot start in the past.'));
        }

        if ($rules->maxAdvanceDays > 0) {
            $latest = TimeHelper::now()->modify('+' . $rules->maxAdvanceDays . ' days');
            if ($start > $latest) {
                $this->addError('start_time', Yii::t('ResourceBookingModule.base', 'Bookings can be made at most {days} days in advance.', [
                    'days' => $rules->maxAdvanceDays,
                ]));
            }
        }

        if (!$rules->isDayAllowed((int)$start->format('N'))) {
            $this->addError('date', Yii::t('ResourceBookingModule.base', 'Bookings are not allowed on this weekday.'));
        }

        $startMinutes = TimeHelper::minutesOfDay($start);
        $endMinutes = TimeHelper::minutesOfDay($end);

        if ($startMinutes < $rules->getOpeningMinutes() || $endMinutes > $rules->getClosingMinutes()) {
            $this->addError('start_time', Yii::t('ResourceBookingModule.base', 'Bookings are only possible between {start} and {end}.', [
                'start' => $rules->getOpeningLabel(),
                'end' => $rules->getClosingLabel(),
            ]));
        }

        $duration = TimeHelper::minutesBetween($start, $end);

        if ($duration < $rules->minDurationMinutes) {
            $this->addError('end_time', Yii::t('ResourceBookingModule.base', 'The minimum booking duration is {minutes} minutes.', [
                'minutes' => $rules->minDurationMinutes,
            ]));
        }

        if ($rules->maxDurationMinutes > 0 && $duration > $rules->maxDurationMinutes) {
            $this->addError('end_time', Yii::t('ResourceBookingModule.base', 'The maximum booking duration is {minutes} minutes.', [
                'minutes' => $rules->maxDurationMinutes,
            ]));
        }

        if ($this->hasOverlap($start, $end, $rules->bufferMinutes)) {
            $this->addError('start_time', Yii::t('ResourceBookingModule.base', 'The resource is already booked during this time. Please choose another time or resource.'));
        }

        if ($rules->maxBookingsPerUserPerDay > 0) {
            [$dayStart, $dayEnd] = TimeHelper::dayBounds($this->date);
            if ($this->countUserBookings($category, $dayStart, $dayEnd) >= $rules->maxBookingsPerUserPerDay) {
                $this->addError('date', Yii::t('ResourceBookingModule.base', 'You have reached the maximum number of bookings for this category on this day.'));
            }
        }

        if ($rules->maxBookingsPerUserPerWeek > 0) {
            [$weekStart, $weekEnd] = TimeHelper::weekBounds($start);
            if ($this->countUserBookings($category, $weekStart, $weekEnd) >= $rules->maxBookingsPerUserPerWeek) {
                $this->addError('date', Yii::t('ResourceBookingModule.base', 'You have reached the maximum number of bookings for this category this week.'));
            }
        }
    }

    public function save(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        $resource = $this->getResourceModel();

        if ($resource === null || $resource->category === null) {
            return false;
        }

        // Serialise bookings per category so concurrent requests cannot double-book a resource
        // or exceed the per-user quota. The lock is released automatically if the connection ends.
        $lockName = 'resource-booking-category-' . $resource->category->id;
        $lock = Yii::$app->db->createCommand('SELECT GET_LOCK(:name, 10)', [':name' => $lockName])->queryScalar();

        if ((int)$lock !== 1) {
            $this->addError('resource_id', Yii::t('ResourceBookingModule.base', 'The resource is currently being booked. Please try again in a moment.'));

            return false;
        }

        try {
            // Validate again after acquiring the lock in order to catch a booking created meanwhile.
            $this->clearErrors();

            if (!$this->validate()) {
                return false;
            }

            $rules = $resource->category->getEffectiveRules();
            $start = TimeHelper::combine((string)$this->date, (string)$this->start_time);
            $end = TimeHelper::combine((string)$this->date, (string)$this->end_time);

            if ($start === null || $end === null) {
                return false;
            }

            $booking = $this->booking;
            $isNew = $booking->isNewRecord;

            $previousStart = $booking->getOldAttribute('start_time');
            $previousEnd = $booking->getOldAttribute('end_time');
            $previousResource = $booking->getOldAttribute('resource_id');

            $booking->resource_id = (int)$this->resource_id;
            $booking->title = trim((string)$this->title);
            $booking->description = trim((string)$this->description) ?: null;
            $booking->start_time = TimeHelper::toDatabase($start);
            $booking->end_time = TimeHelper::toDatabase($end);

            $changed = !$isNew && (
                $previousStart !== $booking->start_time
                || $previousEnd !== $booking->end_time
                || (int)$previousResource !== (int)$booking->resource_id
            );

            if ($isNew) {
                $booking->user_id = (int)Yii::$app->user->id;
                $booking->status = !Booking::canManage() && $rules->requiresApproval
                    ? Booking::STATUS_PENDING
                    : Booking::STATUS_CONFIRMED;
            } elseif (!Booking::canManage()) {
                if ($booking->isPending() || ($rules->requiresApproval && $changed)) {
                    $booking->status = Booking::STATUS_PENDING;
                } else {
                    $booking->status = Booking::STATUS_CONFIRMED;
                }
            }

            // A moved booking deserves a fresh reminder.
            if ($changed) {
                $booking->reminder_sent_at = null;
            }

            if (!$booking->save(false)) {
                $this->addError('resource_id', Yii::t('ResourceBookingModule.base', 'The booking could not be saved.'));
                return false;
            }

            BookingReminders::schedule($booking);

            return true;
        } finally {
            try {
                Yii::$app->db->createCommand('SELECT RELEASE_LOCK(:name)', [':name' => $lockName])->execute();
            } catch (\Throwable $e) {
                Yii::warning('Could not release resource booking lock: ' . $e->getMessage(), 'resource-booking');
            }
        }
    }

    public function getResourceModel(): ?Resource
    {
        if ($this->_resource === null && !empty($this->resource_id)) {
            $this->_resource = Resource::find()
                ->where(['id' => (int)$this->resource_id])
                ->with('category')
                ->one();
        }

        return $this->_resource;
    }

    public function getResourceOptions(): array
    {
        $options = [];

        foreach (Resource::findActive()->all() as $resource) {
            $label = $resource->name;

            if ($resource->category !== null) {
                $label = $resource->category->name . ' · ' . $label;
            }

            if ($resource->capacity > 0) {
                $label .= ' (' . (int)$resource->capacity . ')';
            }

            $options[$resource->id] = $label;
        }

        return $options;
    }

    public function getEffectiveRules(): BookingRules
    {
        return $this->getResourceModel()?->category?->getEffectiveRules() ?? BookingRules::resolve();
    }

    private function fillFromBooking(Booking $booking): void
    {
        $start = TimeHelper::fromDatabase($booking->start_time);
        $end = TimeHelper::fromDatabase($booking->end_time);

        $this->resource_id = $booking->resource_id;
        $this->date = $start?->format(TimeHelper::DATE_FORMAT) ?? TimeHelper::now()->format(TimeHelper::DATE_FORMAT);
        $this->start_time = $start?->format(TimeHelper::TIME_FORMAT) ?? '09:00';
        $this->end_time = $end?->format(TimeHelper::TIME_FORMAT) ?? '10:00';
        $this->title = $booking->title;
        $this->description = $booking->description;
    }

    private function defaultStartTime(): string
    {
        $rules = $this->getEffectiveRules();
        $date = TimeHelper::date((string)$this->date);
        $opening = $date->setTime(0, 0)->modify('+' . $rules->getOpeningMinutes() . ' minutes');
        $closing = $date->setTime(0, 0)->modify('+' . $rules->getClosingMinutes() . ' minutes');
        $now = TimeHelper::now();

        $start = $opening;

        if ($date->format('Y-m-d') === $now->format('Y-m-d') && $now > $opening) {
            $minutes = (int)$now->format('G') * 60 + (int)$now->format('i');
            $rounded = (int)ceil($minutes / 30) * 30;
            $candidate = $date->setTime(0, 0)->modify('+' . $rounded . ' minutes');

            if ($candidate > $opening && $candidate < $closing) {
                $start = $candidate;
            } elseif ($candidate >= $closing) {
                $start = $closing->modify('-30 minutes');
            }
        }

        if ($start >= $closing) {
            $start = $closing->modify('-30 minutes');
        }

        return $start->format(TimeHelper::TIME_FORMAT);
    }

    private function defaultEndTime(string $startTime): string
    {
        $rules = $this->getEffectiveRules();
        $date = TimeHelper::date((string)$this->date);
        $closing = $date->setTime(0, 0)->modify('+' . $rules->getClosingMinutes() . ' minutes');
        $start = TimeHelper::combine((string)$this->date, $startTime);

        if ($start === null) {
            return $closing->format(TimeHelper::TIME_FORMAT);
        }

        $end = $start->modify('+60 minutes');

        if ($end > $closing) {
            $end = $closing;
        }

        if ($end <= $start) {
            $end = $start->modify('+30 minutes');
        }

        return $end->format(TimeHelper::TIME_FORMAT);
    }

    private function hasOverlap(DateTimeImmutable $start, DateTimeImmutable $end, int $bufferMinutes): bool
    {
        $query = Booking::find()
            ->where(['resource_id' => (int)$this->resource_id])
            ->andWhere(['in', 'status', Booking::activeStatuses()])
            ->andWhere(['<', 'start_time', TimeHelper::toDatabase($end->modify('+' . $bufferMinutes . ' minutes'))])
            ->andWhere(['>', 'end_time', TimeHelper::toDatabase($start->modify('-' . $bufferMinutes . ' minutes'))]);

        if (!$this->booking->isNewRecord) {
            $query->andWhere(['<>', 'id', $this->booking->id]);
        }

        return $query->exists();
    }

    private function countUserBookings(Category $category, DateTimeImmutable $from, DateTimeImmutable $to): int
    {
        $userId = $this->booking->isNewRecord
            ? (int)Yii::$app->user->id
            : (int)$this->booking->user_id;

        $query = Booking::find()
            ->alias('b')
            ->innerJoin(['r' => Resource::tableName()], 'r.id = b.resource_id')
            ->where(['r.category_id' => $category->id])
            ->andWhere(['b.user_id' => $userId])
            ->andWhere(['in', 'b.status', Booking::activeStatuses()])
            ->andWhere(['>=', 'b.start_time', TimeHelper::toDatabase($from)])
            ->andWhere(['<', 'b.start_time', TimeHelper::toDatabase($to)]);

        if (!$this->booking->isNewRecord) {
            $query->andWhere(['<>', 'b.id', $this->booking->id]);
        }

        return (int)$query->count();
    }
}
