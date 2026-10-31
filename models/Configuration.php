<?php

namespace mglavind\humhub\modules\resourceBooking\models;

use humhub\components\SettingsManager;
use Yii;
use yii\base\Model;

class Configuration extends Model
{
    public SettingsManager $settingsManager;

    public $defaultRequiresApproval = false;
    public $defaultMinDurationMinutes = 15;
    public $defaultMaxDurationMinutes = 480;
    public $defaultMaxAdvanceDays = 90;
    public $defaultMaxBookingsPerUserPerDay = 0;
    public $defaultMaxBookingsPerUserPerWeek = 0;
    public $defaultBufferMinutes = 0;
    public $defaultCancellationDeadlineHours = 24;

    public $businessHoursStart = '08:00';
    public $businessHoursEnd = '18:00';
    public $allowedWeekdays = [1, 2, 3, 4, 5];

    public $calendarStartHour = 8;
    public $calendarEndHour = 18;

    /**
     * Minutes before the booking starts at which a reminder notification is sent. 0 disables reminders.
     */
    public $reminderMinutesBefore = 0;

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['defaultRequiresApproval'], 'boolean'],
            [['defaultMinDurationMinutes', 'defaultMaxDurationMinutes', 'defaultMaxAdvanceDays',
                'defaultMaxBookingsPerUserPerDay', 'defaultMaxBookingsPerUserPerWeek',
                'defaultBufferMinutes', 'defaultCancellationDeadlineHours'], 'integer', 'min' => 0],
            [['reminderMinutesBefore'], 'integer', 'min' => 0],
            [['defaultMinDurationMinutes'], 'integer', 'min' => 1],
            [['defaultMaxDurationMinutes'], 'integer', 'min' => 1],
            [['businessHoursStart', 'businessHoursEnd'], 'match', 'pattern' => '/^([01]\d|2[0-3]):[0-5]\d$/'],
            [['defaultMinDurationMinutes', 'defaultMaxDurationMinutes', 'businessHoursStart', 'businessHoursEnd',
                'calendarStartHour', 'calendarEndHour'], 'required'],
            [['allowedWeekdays'], 'required'],
            [['allowedWeekdays'], 'each', 'rule' => ['integer', 'min' => 1, 'max' => 7]],
            [['calendarStartHour'], 'integer', 'min' => 0, 'max' => 23],
            [['calendarEndHour'], 'integer', 'min' => 1, 'max' => 24],
            [['businessHoursEnd'], 'validateBusinessHours'],
            [['calendarEndHour'], 'validateCalendarHours'],
            [['defaultMaxDurationMinutes'], 'validateDuration'],
        ];
    }

    public function beforeValidate(): bool
    {
        $weekdays = is_array($this->allowedWeekdays) ? $this->allowedWeekdays : [];
        $weekdays = array_filter($weekdays, 'is_scalar');
        $weekdays = array_map('intval', $weekdays);
        $weekdays = array_filter($weekdays, static fn(int $day): bool => $day >= 1 && $day <= 7);
        $this->allowedWeekdays = array_values(array_unique($weekdays));

        foreach (['defaultMaxAdvanceDays', 'defaultMaxBookingsPerUserPerDay', 'defaultMaxBookingsPerUserPerWeek',
            'defaultBufferMinutes', 'defaultCancellationDeadlineHours'] as $attribute) {
            if ($this->$attribute === '') {
                $this->$attribute = 0;
            }
        }

        return parent::beforeValidate();
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'defaultRequiresApproval' => Yii::t('ResourceBookingModule.base', 'Require approval for new bookings'),
            'defaultMinDurationMinutes' => Yii::t('ResourceBookingModule.base', 'Minimum duration (minutes)'),
            'defaultMaxDurationMinutes' => Yii::t('ResourceBookingModule.base', 'Maximum duration (minutes)'),
            'defaultMaxAdvanceDays' => Yii::t('ResourceBookingModule.base', 'Maximum advance booking (days)'),
            'defaultMaxBookingsPerUserPerDay' => Yii::t('ResourceBookingModule.base', 'Maximum bookings per user per day'),
            'defaultMaxBookingsPerUserPerWeek' => Yii::t('ResourceBookingModule.base', 'Maximum bookings per user per week'),
            'defaultBufferMinutes' => Yii::t('ResourceBookingModule.base', 'Buffer between bookings (minutes)'),
            'defaultCancellationDeadlineHours' => Yii::t('ResourceBookingModule.base', 'Cancellation deadline (hours before start)'),
            'businessHoursStart' => Yii::t('ResourceBookingModule.base', 'Bookable hours start'),
            'businessHoursEnd' => Yii::t('ResourceBookingModule.base', 'Bookable hours end'),
            'allowedWeekdays' => Yii::t('ResourceBookingModule.base', 'Bookable weekdays'),
            'calendarStartHour' => Yii::t('ResourceBookingModule.base', 'Calendar day view start'),
            'calendarEndHour' => Yii::t('ResourceBookingModule.base', 'Calendar day view end'),
            'reminderMinutesBefore' => Yii::t('ResourceBookingModule.base', 'Send a reminder'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeHints(): array
    {
        return [
            'defaultRequiresApproval' => Yii::t('ResourceBookingModule.base', 'New bookings have to be approved by a booking manager before they are confirmed.'),
            'defaultMaxAdvanceDays' => Yii::t('ResourceBookingModule.base', 'Set to 0 to allow bookings at any time in the future.'),
            'defaultMaxBookingsPerUserPerDay' => Yii::t('ResourceBookingModule.base', 'Set to 0 for no limit.'),
            'defaultMaxBookingsPerUserPerWeek' => Yii::t('ResourceBookingModule.base', 'Set to 0 for no limit.'),
            'defaultBufferMinutes' => Yii::t('ResourceBookingModule.base', 'Minimum gap between two bookings of the same resource.'),
            'defaultCancellationDeadlineHours' => Yii::t('ResourceBookingModule.base', 'Users cannot cancel a confirmed booking after this deadline. Set to 0 to allow cancellation until the start.'),
            'allowedWeekdays' => Yii::t('ResourceBookingModule.base', 'Categories can override these weekdays.'),
            'calendarStartHour' => Yii::t('ResourceBookingModule.base', 'First hour shown in the day calendar.'),
            'calendarEndHour' => Yii::t('ResourceBookingModule.base', 'Last hour shown in the day calendar.'),
            'reminderMinutesBefore' => Yii::t('ResourceBookingModule.base', 'Minutes before the booking starts. Set to 0 to disable reminders.'),
        ];
    }

    public function validateBusinessHours(): void
    {
        if (
            $this->businessHoursStart !== ''
            && $this->businessHoursEnd !== ''
            && $this->businessHoursEnd <= $this->businessHoursStart
        ) {
            $this->addError('businessHoursEnd', Yii::t('ResourceBookingModule.base', 'The end of the bookable hours must be after the start.'));
        }
    }

    public function validateCalendarHours(): void
    {
        if ($this->calendarEndHour <= $this->calendarStartHour) {
            $this->addError('calendarEndHour', Yii::t('ResourceBookingModule.base', 'The calendar end must be after the calendar start.'));
        }
    }

    public function validateDuration(): void
    {
        if ($this->defaultMaxDurationMinutes < $this->defaultMinDurationMinutes) {
            $this->addError('defaultMaxDurationMinutes', Yii::t('ResourceBookingModule.base', 'The maximum duration must be greater than or equal to the minimum duration.'));
        }
    }

    public function loadBySettings(): void
    {
        $this->defaultRequiresApproval = (bool)$this->settingsManager->get('defaultRequiresApproval', false);
        $this->defaultMinDurationMinutes = (int)$this->settingsManager->get('defaultMinDurationMinutes', 15);
        $this->defaultMaxDurationMinutes = (int)$this->settingsManager->get('defaultMaxDurationMinutes', 480);
        $this->defaultMaxAdvanceDays = (int)$this->settingsManager->get('defaultMaxAdvanceDays', 90);
        $this->defaultMaxBookingsPerUserPerDay = (int)$this->settingsManager->get('defaultMaxBookingsPerUserPerDay', 0);
        $this->defaultMaxBookingsPerUserPerWeek = (int)$this->settingsManager->get('defaultMaxBookingsPerUserPerWeek', 0);
        $this->defaultBufferMinutes = (int)$this->settingsManager->get('defaultBufferMinutes', 0);
        $this->defaultCancellationDeadlineHours = (int)$this->settingsManager->get('defaultCancellationDeadlineHours', 24);
        $this->businessHoursStart = (string)$this->settingsManager->get('businessHoursStart', '08:00');
        $this->businessHoursEnd = (string)$this->settingsManager->get('businessHoursEnd', '18:00');
        $weekdays = array_values(array_filter(
            array_map('intval', (array)$this->settingsManager->getSerialized('allowedWeekdays', [1, 2, 3, 4, 5])),
            static fn(int $day): bool => $day >= 1 && $day <= 7,
        ));
        $this->allowedWeekdays = empty($weekdays) ? [1, 2, 3, 4, 5] : $weekdays;
        $this->calendarStartHour = (int)$this->settingsManager->get('calendarStartHour', 8);
        $this->calendarEndHour = (int)$this->settingsManager->get('calendarEndHour', 18);
        $this->reminderMinutesBefore = (int)$this->settingsManager->get('reminderMinutesBefore', 0);
    }

    public function save(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        $this->settingsManager->set('defaultRequiresApproval', $this->defaultRequiresApproval ? 1 : 0);
        $this->settingsManager->set('defaultMinDurationMinutes', $this->defaultMinDurationMinutes);
        $this->settingsManager->set('defaultMaxDurationMinutes', $this->defaultMaxDurationMinutes);
        $this->settingsManager->set('defaultMaxAdvanceDays', $this->defaultMaxAdvanceDays);
        $this->settingsManager->set('defaultMaxBookingsPerUserPerDay', $this->defaultMaxBookingsPerUserPerDay);
        $this->settingsManager->set('defaultMaxBookingsPerUserPerWeek', $this->defaultMaxBookingsPerUserPerWeek);
        $this->settingsManager->set('defaultBufferMinutes', $this->defaultBufferMinutes);
        $this->settingsManager->set('defaultCancellationDeadlineHours', $this->defaultCancellationDeadlineHours);
        $this->settingsManager->set('businessHoursStart', $this->businessHoursStart);
        $this->settingsManager->set('businessHoursEnd', $this->businessHoursEnd);
        $this->settingsManager->setSerialized('allowedWeekdays', array_values(array_map('intval', is_array($this->allowedWeekdays) ? $this->allowedWeekdays : [])));
        $this->settingsManager->set('calendarStartHour', $this->calendarStartHour);
        $this->settingsManager->set('calendarEndHour', $this->calendarEndHour);
        $this->settingsManager->set('reminderMinutesBefore', (int)$this->reminderMinutesBefore);

        return true;
    }
}
