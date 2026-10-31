<?php

namespace mglavind\humhub\modules\resourceBooking\helpers;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Yii;

class TimeHelper
{
    public const DB_FORMAT = 'Y-m-d H:i:s';
    public const DATE_FORMAT = 'Y-m-d';
    public const TIME_FORMAT = 'H:i';

    public static function userTimezone(): DateTimeZone
    {
        $timeZone = null;

        if (!Yii::$app->user->isGuest && Yii::$app->user->identity !== null) {
            $timeZone = Yii::$app->user->identity->time_zone;
        }

        if (empty($timeZone)) {
            $timeZone = Yii::$app->settings->get('defaultTimeZone', Yii::$app->timeZone);
        }

        return static::timezone($timeZone);
    }

    public static function timezone(?string $identifier): DateTimeZone
    {
        if (!empty($identifier)) {
            try {
                return new DateTimeZone($identifier);
            } catch (\Throwable) {
                // Fall through to the application timezone.
            }
        }

        return new DateTimeZone(Yii::$app->timeZone ?: 'UTC');
    }

    public static function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', static::userTimezone());
    }

    public static function nowUtc(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    public static function toUtc(DateTimeInterface $date): DateTimeImmutable
    {
        return (new DateTimeImmutable('@' . $date->getTimestamp()))
            ->setTimezone(new DateTimeZone('UTC'));
    }

    public static function toDatabase(DateTimeInterface $date): string
    {
        return static::toUtc($date)->format(self::DB_FORMAT);
    }

    public static function fromDatabase(?string $value, ?DateTimeZone $timezone = null): ?DateTimeImmutable
    {
        if (empty($value)) {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat(self::DB_FORMAT, $value, new DateTimeZone('UTC'));

        if ($date === false) {
            return null;
        }

        return $date->setTimezone($timezone ?? static::userTimezone());
    }

    public static function date(?string $value = null): DateTimeImmutable
    {
        $timezone = static::userTimezone();

        if (!empty($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            [$year, $month, $day] = array_map('intval', explode('-', $value));

            if (checkdate($month, $day, $year)) {
                $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, $timezone);

                if ($date !== false) {
                    return $date;
                }
            }
        }

        return new DateTimeImmutable('today', $timezone);
    }

    /**
     * @return DateTimeImmutable[] [start of day, start of next day] in the user timezone
     */
    public static function dayBounds(string $date): array
    {
        $start = DateTimeImmutable::createFromFormat('!Y-m-d', $date, static::userTimezone());

        if (
            $start === false
            || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $matches)
            || !checkdate((int)$matches[2], (int)$matches[3], (int)$matches[1])
        ) {
            $start = new DateTimeImmutable('today', static::userTimezone());
        }

        return [$start, $start->modify('+1 day')];
    }

    /**
     * @return DateTimeImmutable[] [start of week (Monday), start of next week]
     */
    public static function weekBounds(DateTimeImmutable $date): array
    {
        $start = $date->modify('monday this week')->setTime(0, 0);

        return [$start, $start->modify('+7 days')];
    }

    public static function combine(string $date, string $time): ?DateTimeImmutable
    {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $dateParts)) {
            return null;
        }

        if (!checkdate((int)$dateParts[2], (int)$dateParts[3], (int)$dateParts[1])) {
            return null;
        }

        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $time)) {
            return null;
        }

        $result = DateTimeImmutable::createFromFormat(
            '!Y-m-d H:i',
            $date . ' ' . substr($time, 0, 5),
            static::userTimezone(),
        );

        return $result !== false ? $result : null;
    }

    public static function formatTime(?string $value): string
    {
        $date = static::fromDatabase($value);

        if ($date === null) {
            return '';
        }

        return $date->format(Yii::$app->formatter->isShowMeridiem() ? 'g:i A' : 'H:i');
    }

    public static function formatDateTime(?string $value): string
    {
        $date = static::fromDatabase($value);

        return $date !== null ? Yii::$app->formatter->asDatetime($date) : '';
    }

    public static function formatDate(DateTimeImmutable $date): string
    {
        return Yii::$app->formatter->asDate($date, 'full');
    }

    public static function minutesBetween(DateTimeInterface $start, DateTimeInterface $end): int
    {
        return (int)floor(($end->getTimestamp() - $start->getTimestamp()) / 60);
    }

    public static function minutesOfDay(DateTimeInterface $date): int
    {
        return ((int)$date->format('G') * 60) + (int)$date->format('i');
    }
}
