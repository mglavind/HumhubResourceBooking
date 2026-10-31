<?php

namespace mglavind\humhub\modules\resourceBooking\models;

use humhub\components\ActiveRecord;
use mglavind\humhub\modules\resourceBooking\services\BookingRules;
use Yii;
use yii\db\ActiveQuery;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string $color
 * @property int $sort_order
 * @property int|bool $is_active
 * @property int|bool|null $requires_approval
 * @property int|null $min_duration_minutes
 * @property int|null $max_duration_minutes
 * @property int|null $max_advance_days
 * @property int|null $max_bookings_per_user_per_day
 * @property int|null $max_bookings_per_user_per_week
 * @property int|null $buffer_minutes
 * @property int|null $cancellation_deadline_hours
 * @property string|null $opening_time
 * @property string|null $closing_time
 * @property string|null $allowed_weekdays
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property int|null $created_by
 * @property int|null $updated_by
 *
 * @property Resource[] $resources
 */
class Category extends ActiveRecord
{
    /**
     * @var int[] ISO-8601 weekday numbers selected in the admin form
     */
    public $allowedWeekdays = [];

    public static function tableName(): string
    {
        return 'resourcebooking_category';
    }

    public function rules(): array
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 120],
            [['name'], 'unique'],
            [['description'], 'string'],
            [['color'], 'string', 'max' => 20],
            [['color'], 'match', 'pattern' => '/^#[0-9a-fA-F]{6}$/'],
            [['sort_order', 'min_duration_minutes', 'max_duration_minutes', 'max_advance_days',
                'max_bookings_per_user_per_day', 'max_bookings_per_user_per_week',
                'buffer_minutes', 'cancellation_deadline_hours'], 'integer', 'min' => 0],
            [['is_active', 'requires_approval'], 'boolean'],
            [['opening_time', 'closing_time'], 'match', 'pattern' => '/^([01]\d|2[0-3]):[0-5]\d$/'],
            [['allowedWeekdays'], 'each', 'rule' => ['integer', 'min' => 1, 'max' => 7]],
            [['opening_time', 'closing_time'], 'validateOpeningHours'],
            [['max_duration_minutes'], 'validateDurationRange'],
            [['allowed_weekdays'], 'safe'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'name' => Yii::t('ResourceBookingModule.base', 'Name'),
            'description' => Yii::t('ResourceBookingModule.base', 'Description'),
            'color' => Yii::t('ResourceBookingModule.base', 'Colour'),
            'sort_order' => Yii::t('ResourceBookingModule.base', 'Sort order'),
            'is_active' => Yii::t('ResourceBookingModule.base', 'Active'),
            'requires_approval' => Yii::t('ResourceBookingModule.base', 'Approval'),
            'min_duration_minutes' => Yii::t('ResourceBookingModule.base', 'Minimum duration (minutes)'),
            'max_duration_minutes' => Yii::t('ResourceBookingModule.base', 'Maximum duration (minutes)'),
            'max_advance_days' => Yii::t('ResourceBookingModule.base', 'Maximum advance booking (days)'),
            'max_bookings_per_user_per_day' => Yii::t('ResourceBookingModule.base', 'Maximum bookings per user per day'),
            'max_bookings_per_user_per_week' => Yii::t('ResourceBookingModule.base', 'Maximum bookings per user per week'),
            'buffer_minutes' => Yii::t('ResourceBookingModule.base', 'Buffer between bookings (minutes)'),
            'cancellation_deadline_hours' => Yii::t('ResourceBookingModule.base', 'Cancellation deadline (hours before start)'),
            'opening_time' => Yii::t('ResourceBookingModule.base', 'Bookable hours start'),
            'closing_time' => Yii::t('ResourceBookingModule.base', 'Bookable hours end'),
            'allowedWeekdays' => Yii::t('ResourceBookingModule.base', 'Bookable weekdays'),
        ];
    }

    public function attributeHints(): array
    {
        return [
            'requires_approval' => Yii::t('ResourceBookingModule.base', 'Leave on "global default" to use the value from the module settings.'),
            'min_duration_minutes' => Yii::t('ResourceBookingModule.base', 'Leave empty to use the global default.'),
            'max_duration_minutes' => Yii::t('ResourceBookingModule.base', 'Leave empty to use the global default.'),
            'max_advance_days' => Yii::t('ResourceBookingModule.base', 'Leave empty to use the global default.'),
            'max_bookings_per_user_per_day' => Yii::t('ResourceBookingModule.base', 'Leave empty to use the global default.'),
            'max_bookings_per_user_per_week' => Yii::t('ResourceBookingModule.base', 'Leave empty to use the global default.'),
            'buffer_minutes' => Yii::t('ResourceBookingModule.base', 'Leave empty to use the global default.'),
            'cancellation_deadline_hours' => Yii::t('ResourceBookingModule.base', 'Leave empty to use the global default.'),
            'opening_time' => Yii::t('ResourceBookingModule.base', 'Leave empty to use the global default.'),
            'closing_time' => Yii::t('ResourceBookingModule.base', 'Leave empty to use the global default.'),
            'allowedWeekdays' => Yii::t('ResourceBookingModule.base', 'Leave all unchecked to use the global defaults.'),
        ];
    }

    public function beforeValidate(): bool
    {
        $this->name = is_scalar($this->name) ? trim((string)$this->name) : '';

        foreach (['requires_approval', 'min_duration_minutes', 'max_duration_minutes', 'max_advance_days',
            'max_bookings_per_user_per_day', 'max_bookings_per_user_per_week', 'buffer_minutes',
            'cancellation_deadline_hours', 'opening_time', 'closing_time'] as $attribute) {
            if (!is_scalar($this->$attribute) && $this->$attribute !== null) {
                $this->$attribute = null;
            } elseif ($this->$attribute === '') {
                $this->$attribute = null;
            }
        }

        $weekdays = is_array($this->allowedWeekdays) ? $this->allowedWeekdays : [];
        $weekdays = array_filter($weekdays, 'is_scalar');
        $weekdays = array_map('intval', $weekdays);
        $weekdays = array_filter($weekdays, static fn(int $day): bool => $day >= 1 && $day <= 7);
        $this->allowedWeekdays = array_values(array_unique($weekdays));

        return parent::beforeValidate();
    }

    public function beforeSave($insert): bool
    {
        $this->color = $this->color ?: '#435f6f';
        $this->sort_order = (int)$this->sort_order;

        $weekdays = is_array($this->allowedWeekdays) ? $this->allowedWeekdays : [];
        $weekdays = array_values(array_unique(array_map('intval', $weekdays)));
        $weekdays = array_values(array_filter($weekdays, static fn(int $day): bool => $day >= 1 && $day <= 7));
        sort($weekdays);

        $this->allowed_weekdays = empty($weekdays) ? null : implode(',', $weekdays);

        return parent::beforeSave($insert);
    }

    public function afterFind(): void
    {
        parent::afterFind();

        $this->allowedWeekdays = empty($this->allowed_weekdays)
            ? []
            : array_values(array_map('intval', explode(',', $this->allowed_weekdays)));
    }

    public function validateOpeningHours(): void
    {
        $hasStart = !empty($this->opening_time);
        $hasEnd = !empty($this->closing_time);

        if ($hasStart !== $hasEnd) {
            $this->addError($hasStart ? 'closing_time' : 'opening_time', Yii::t('ResourceBookingModule.base', 'Please set both the start and end of the bookable hours, or neither.'));
        } elseif ($hasStart && $this->closing_time <= $this->opening_time) {
            $this->addError('closing_time', Yii::t('ResourceBookingModule.base', 'The end of the bookable hours must be after the start.'));
        }
    }

    public function validateDurationRange(): void
    {
        $rules = $this->getEffectiveRules();

        if ($rules->maxDurationMinutes > 0 && $rules->maxDurationMinutes < $rules->minDurationMinutes) {
            $this->addError('max_duration_minutes', Yii::t('ResourceBookingModule.base', 'The maximum duration must be greater than or equal to the minimum duration.'));
        }
    }

    /**
     * @return ActiveQuery<Resource>
     */
    public function getResources(): ActiveQuery
    {
        return $this->hasMany(Resource::class, ['category_id' => 'id']);
    }

    public function getActiveResources(): ActiveQuery
    {
        return $this->getResources()->andWhere(['is_active' => 1]);
    }

    public function getEffectiveRules(): BookingRules
    {
        return BookingRules::resolve($this);
    }

    public function getColor(): string
    {
        return is_string($this->color) && $this->color !== '' ? $this->color : '#435f6f';
    }

    public function getApprovalLabel(): string
    {
        if ($this->requires_approval === null) {
            return Yii::t('ResourceBookingModule.base', 'Global default');
        }

        return $this->requires_approval
            ? Yii::t('ResourceBookingModule.base', 'Required')
            : Yii::t('ResourceBookingModule.base', 'Automatic');
    }

    public static function findActive(): ActiveQuery
    {
        return static::find()
            ->where(['is_active' => 1])
            ->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC]);
    }
}
