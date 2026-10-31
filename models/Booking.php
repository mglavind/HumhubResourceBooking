<?php

namespace mglavind\humhub\modules\resourceBooking\models;

use DateTimeImmutable;
use humhub\components\ActiveRecord;
use humhub\modules\admin\permissions\ManageSettings;
use humhub\modules\user\models\User;
use mglavind\humhub\modules\resourceBooking\helpers\TimeHelper;
use mglavind\humhub\modules\resourceBooking\permissions\ManageBookings;
use Yii;
use yii\db\ActiveQuery;

/**
 * @property int $id
 * @property int $resource_id
 * @property int $user_id
 * @property string $title
 * @property string|null $description
 * @property string $start_time
 * @property string $end_time
 * @property string $status
 * @property int|null $approved_by
 * @property string|null $approved_at
 * @property string|null $cancelled_at
 * @property string|null $cancellation_reason
 * @property string|null $reminder_sent_at
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property int|null $created_by
 * @property int|null $updated_by
 *
 * @property Resource $resource
 * @property Category $category
 * @property User $user
 * @property User $approvedBy
 */
class Booking extends ActiveRecord
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_REJECTED,
        self::STATUS_CANCELLED,
    ];

    public static function tableName(): string
    {
        return 'resourcebooking_booking';
    }

    public function rules(): array
    {
        return [
            [['resource_id', 'user_id', 'title', 'start_time', 'end_time', 'status'], 'required'],
            [['resource_id', 'user_id', 'approved_by'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['description'], 'string'],
            [['cancellation_reason'], 'string', 'max' => 255],
            [['status'], 'in', 'range' => self::STATUSES],
            [['resource_id'], 'exist', 'targetClass' => Resource::class, 'targetAttribute' => 'id'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'resource_id' => Yii::t('ResourceBookingModule.base', 'Resource'),
            'title' => Yii::t('ResourceBookingModule.base', 'Title'),
            'description' => Yii::t('ResourceBookingModule.base', 'Description'),
            'start_time' => Yii::t('ResourceBookingModule.base', 'Start'),
            'end_time' => Yii::t('ResourceBookingModule.base', 'End'),
            'status' => Yii::t('ResourceBookingModule.base', 'Status'),
        ];
    }

    public function getResource(): ActiveQuery
    {
        return $this->hasOne(Resource::class, ['id' => 'resource_id']);
    }

    public function getCategory(): ActiveQuery
    {
        return $this->hasOne(Category::class, ['id' => 'category_id'])->via('resource');
    }

    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getApprovedBy(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'approved_by']);
    }

    /**
     * @return string[]
     */
    public static function activeStatuses(): array
    {
        return [self::STATUS_PENDING, self::STATUS_CONFIRMED];
    }

    public static function findActive(): ActiveQuery
    {
        return static::find()->andWhere(['in', 'status', self::activeStatuses()]);
    }

    /**
     * @return Booking[]
     */
    public static function findUpcomingForUser(int $userId, int $limit = 5): array
    {
        return static::find()
            ->where(['user_id' => $userId])
            ->andWhere(['in', 'status', self::activeStatuses()])
            ->andWhere(['>=', 'end_time', TimeHelper::toDatabase(TimeHelper::nowUtc())])
            ->orderBy(['start_time' => SORT_ASC])
            ->limit($limit)
            ->with(['resource.category'])
            ->all();
    }

    public function isActiveStatus(): bool
    {
        return in_array($this->status, self::activeStatuses(), true);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isPast(): bool
    {
        return $this->end_time !== null
            && $this->end_time < TimeHelper::toDatabase(TimeHelper::nowUtc());
    }

    public function getStartLocal(): ?DateTimeImmutable
    {
        return TimeHelper::fromDatabase($this->start_time);
    }

    public function getEndLocal(): ?DateTimeImmutable
    {
        return TimeHelper::fromDatabase($this->end_time);
    }

    public function getDateValue(): string
    {
        return $this->getStartLocal()?->format(TimeHelper::DATE_FORMAT) ?? TimeHelper::date()->format(TimeHelper::DATE_FORMAT);
    }

    public function getTimeRangeLabel(): string
    {
        return TimeHelper::formatTime($this->start_time) . ' – ' . TimeHelper::formatTime($this->end_time);
    }

    public function getDateLabel(): string
    {
        $start = $this->getStartLocal();

        return $start !== null ? Yii::$app->formatter->asDate($start, 'medium') : '';
    }

    public function getDateTimeLabel(): string
    {
        $start = $this->getStartLocal();

        return $start !== null ? Yii::$app->formatter->asDatetime($start, 'medium') : '';
    }

    public function getDurationMinutes(): int
    {
        $start = TimeHelper::fromDatabase($this->start_time);
        $end = TimeHelper::fromDatabase($this->end_time);

        if ($start === null || $end === null) {
            return 0;
        }

        return max(0, TimeHelper::minutesBetween($start, $end));
    }

    public function getDurationLabel(): string
    {
        $minutes = $this->getDurationMinutes();
        $hours = intdiv($minutes, 60);
        $remaining = $minutes % 60;

        if ($hours > 0 && $remaining > 0) {
            return Yii::t('ResourceBookingModule.base', '{hours}h {minutes}m', ['hours' => $hours, 'minutes' => $remaining]);
        }

        if ($hours > 0) {
            return Yii::t('ResourceBookingModule.base', '{hours}h', ['hours' => $hours]);
        }

        return Yii::t('ResourceBookingModule.base', '{minutes}m', ['minutes' => $remaining]);
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => Yii::t('ResourceBookingModule.base', 'Pending approval'),
            self::STATUS_CONFIRMED => Yii::t('ResourceBookingModule.base', 'Confirmed'),
            self::STATUS_REJECTED => Yii::t('ResourceBookingModule.base', 'Rejected'),
            self::STATUS_CANCELLED => Yii::t('ResourceBookingModule.base', 'Cancelled'),
            default => (string)$this->status,
        };
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_CONFIRMED => 'success',
            self::STATUS_REJECTED => 'danger',
            self::STATUS_CANCELLED => 'secondary',
            default => 'light',
        };
    }

    public function canBeViewedBy(?User $user): bool
    {
        if ($this->isActiveStatus()) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        return $this->isOwnedBy($user) || static::canManage() || Yii::$app->user->can(ManageSettings::class);
    }

    public function canBeEditedBy(?User $user): bool
    {
        if ($user === null || !$this->isActiveStatus() || $this->isPast()) {
            return false;
        }

        return $this->isOwnedBy($user) || static::canManage();
    }

    public function canBeCancelledBy(?User $user): bool
    {
        if ($user === null || !$this->isActiveStatus() || $this->isPast()) {
            return false;
        }

        if (static::canManage()) {
            return true;
        }

        if (!$this->isOwnedBy($user)) {
            return false;
        }

        $start = $this->getStartLocal();

        if ($start === null || $start <= TimeHelper::now()) {
            return false;
        }

        if ($this->isPending()) {
            return true;
        }

        $rules = $this->resource?->category?->getEffectiveRules();

        $deadlineHours = $rules?->cancellationDeadlineHours ?? 0;
        $deadline = $start->modify('-' . $deadlineHours . ' hours');

        return TimeHelper::now() < $deadline;
    }

    public function isOwnedBy(User $user): bool
    {
        return (int)$this->user_id === (int)$user->id;
    }

    public static function canManage(): bool
    {
        return !Yii::$app->user->isGuest && Yii::$app->user->can(ManageBookings::class);
    }
}
