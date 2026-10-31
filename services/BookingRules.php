<?php

namespace mglavind\humhub\modules\resourceBooking\services;

use DateTimeImmutable;
use mglavind\humhub\modules\resourceBooking\models\Category;
use mglavind\humhub\modules\resourceBooking\Module;
use Yii;
use yii\base\BaseObject;

class BookingRules extends BaseObject
{
    public bool $requiresApproval = false;
    public int $minDurationMinutes = 15;
    public int $maxDurationMinutes = 480;
    public int $maxAdvanceDays = 90;
    public int $maxBookingsPerUserPerDay = 0;
    public int $maxBookingsPerUserPerWeek = 0;
    public int $bufferMinutes = 0;
    public int $cancellationDeadlineHours = 24;
    public string $openingTime = '08:00';
    public string $closingTime = '18:00';

    /**
     * @var int[] ISO-8601 weekday numbers (1 = Monday, 7 = Sunday)
     */
    public array $allowedWeekdays = [1, 2, 3, 4, 5];

    public static function resolve(?Category $category = null): self
    {
        $configuration = Module::instance()->getConfiguration();

        return new static([
            'requiresApproval' => $category !== null && $category->requires_approval !== null
                ? (bool)$category->requires_approval
                : $configuration->defaultRequiresApproval,
            'minDurationMinutes' => (int)($category?->min_duration_minutes ?? $configuration->defaultMinDurationMinutes),
            'maxDurationMinutes' => (int)($category?->max_duration_minutes ?? $configuration->defaultMaxDurationMinutes),
            'maxAdvanceDays' => (int)($category?->max_advance_days ?? $configuration->defaultMaxAdvanceDays),
            'maxBookingsPerUserPerDay' => (int)($category?->max_bookings_per_user_per_day ?? $configuration->defaultMaxBookingsPerUserPerDay),
            'maxBookingsPerUserPerWeek' => (int)($category?->max_bookings_per_user_per_week ?? $configuration->defaultMaxBookingsPerUserPerWeek),
            'bufferMinutes' => (int)($category?->buffer_minutes ?? $configuration->defaultBufferMinutes),
            'cancellationDeadlineHours' => (int)($category?->cancellation_deadline_hours ?? $configuration->defaultCancellationDeadlineHours),
            'openingTime' => is_string($category?->opening_time) && $category->opening_time !== ''
                ? $category->opening_time
                : $configuration->businessHoursStart,
            'closingTime' => is_string($category?->closing_time) && $category->closing_time !== ''
                ? $category->closing_time
                : $configuration->businessHoursEnd,
            'allowedWeekdays' => !empty($category?->allowedWeekdays)
                ? array_values(array_map('intval', $category->allowedWeekdays))
                : array_values(array_map('intval', $configuration->allowedWeekdays)),
        ]);
    }

    public function getOpeningMinutes(): int
    {
        return static::timeToMinutes($this->openingTime);
    }

    public function getClosingMinutes(): int
    {
        return static::timeToMinutes($this->closingTime);
    }

    public function isDayAllowed(int $isoWeekday): bool
    {
        return in_array($isoWeekday, $this->allowedWeekdays, true);
    }

    public function isOpen(): bool
    {
        return $this->getClosingMinutes() > $this->getOpeningMinutes();
    }

    public function getOpeningLabel(): string
    {
        return $this->formatMinutes($this->getOpeningMinutes());
    }

    public function getClosingLabel(): string
    {
        return $this->formatMinutes($this->getClosingMinutes());
    }

    /**
     * @return string[]
     */
    public function getWeekdayLabels(): array
    {
        return array_map(static fn(int $weekday): string => static::weekdayLabel($weekday), $this->allowedWeekdays);
    }

    public static function weekdayLabel(int $isoWeekday): string
    {
        $date = (new DateTimeImmutable('monday this week'))->modify('+' . ($isoWeekday - 1) . ' days');

        return Yii::$app->formatter->asDate($date, 'php:l');
    }

    public static function timeToMinutes(string $time): int
    {
        [$hours, $minutes] = array_pad(explode(':', $time), 2, '0');

        return ((int)$hours * 60) + (int)$minutes;
    }

    public static function formatMinutes(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }
}
