<?php

namespace mglavind\humhub\modules\resourceBooking\codeceptionTest;

use DateTimeImmutable;
use DateTimeZone;
use mglavind\humhub\modules\resourceBooking\helpers\TimeHelper;
use mglavind\humhub\modules\resourceBooking\models\Category;
use mglavind\humhub\modules\resourceBooking\services\BookingRules;
use tests\codeception\_support\HumHubDbTestCase;

class BookingRulesTest extends HumHubDbTestCase
{
    public function testGlobalDefaultsAreUsedForEmptyCategory(): void
    {
        $rules = BookingRules::resolve(new Category());

        $this->assertSame(15, $rules->minDurationMinutes);
        $this->assertSame(480, $rules->maxDurationMinutes);
        $this->assertSame('08:00', $rules->getOpeningLabel());
        $this->assertSame('18:00', $rules->getClosingLabel());
        $this->assertTrue($rules->isDayAllowed(1));
        $this->assertFalse($rules->isDayAllowed(6));
    }

    public function testCategoryOverridesGlobalRules(): void
    {
        $category = new Category([
            'requires_approval' => 1,
            'min_duration_minutes' => 30,
            'max_duration_minutes' => 120,
            'buffer_minutes' => 10,
            'opening_time' => '09:00',
            'closing_time' => '17:00',
        ]);
        $category->allowedWeekdays = [2, 4];

        $rules = BookingRules::resolve($category);

        $this->assertTrue($rules->requiresApproval);
        $this->assertSame(30, $rules->minDurationMinutes);
        $this->assertSame(120, $rules->maxDurationMinutes);
        $this->assertSame(10, $rules->bufferMinutes);
        $this->assertSame('09:00', $rules->getOpeningLabel());
        $this->assertSame('17:00', $rules->getClosingLabel());
        $this->assertTrue($rules->isDayAllowed(2));
        $this->assertFalse($rules->isDayAllowed(3));
    }

    public function testTimeHelpersUseUtcForStorage(): void
    {
        $date = new DateTimeImmutable('2026-10-31 12:00:00', new DateTimeZone('UTC'));

        $this->assertSame('2026-10-31 12:00:00', TimeHelper::toDatabase($date));
        $this->assertSame(90, TimeHelper::minutesBetween($date, $date->modify('+90 minutes')));
        $this->assertSame(540, BookingRules::timeToMinutes('09:00'));
    }
}
