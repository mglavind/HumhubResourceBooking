<?php

use humhub\helpers\Html;
use humhub\modules\ui\icon\widgets\Icon;
use humhub\widgets\bootstrap\Button;
use humhub\widgets\modal\ModalButton;
use mglavind\humhub\modules\resourceBooking\helpers\TimeHelper;
use mglavind\humhub\modules\resourceBooking\helpers\Url;

/* @var $this \humhub\components\View */
/* @var $day DateTimeImmutable */
/* @var $dateValue string */
/* @var $previousDate string */
/* @var $nextDate string */
/* @var $resources \mglavind\humhub\modules\resourceBooking\models\Resource[] */
/* @var $allResources \mglavind\humhub\modules\resourceBooking\models\Resource[] */
/* @var $bookingsByResource array */
/* @var $categories \mglavind\humhub\modules\resourceBooking\models\Category[] */
/* @var $filters array */
/* @var $calendarStartHour int */
/* @var $calendarEndHour int */
/* @var $slotMinutes int */
/* @var $canCreate bool */
/* @var $canManage bool */
/* @var $pendingCount int */
/* @var $viewMode string */
/* @var $isAdmin bool */

$categoryOptions = [];

foreach ($categories as $category) {
    $categoryOptions[$category->id] = $category->name;
}

$resourceFilterOptions = [];

foreach ($allResources as $resourceOption) {
    $resourceFilterOptions[$resourceOption->id] = ($resourceOption->category?->name ? $resourceOption->category->name . ' · ' : '') . $resourceOption->name;
}

$slotMinutes = max(15, (int)$slotMinutes);
$slotHeight = 32;
$gridStart = $day->setTime($calendarStartHour, 0);
$gridEnd = $day->setTime($calendarEndHour, 0);
$slotCount = max(1, (int)ceil((($calendarEndHour - $calendarStartHour) * 60) / $slotMinutes));
$bodyHeight = $slotCount * $slotHeight;
$now = TimeHelper::now();
$canCreateOnDay = $canCreate && TimeHelper::date($dateValue) >= TimeHelper::date();

$rulesByResource = [];
$bookedRanges = [];

foreach ($resources as $resourceItem) {
    $rulesByResource[(int)$resourceItem->id] = $resourceItem->category?->getEffectiveRules();
}

// Reserve the time occupied by active bookings (including the configured buffer) so slots cannot be dragged over them.
foreach ($bookingsByResource as $resourceBookings) {
    foreach ($resourceBookings as $booking) {
        $bookingStart = $booking->getStartLocal();
        $bookingEnd = $booking->getEndLocal();

        if ($bookingStart === null || $bookingEnd === null) {
            continue;
        }

        $resourceId = (int)$booking->resource_id;
        $buffer = (int)($rulesByResource[$resourceId]?->bufferMinutes ?? 0);
        $startMinutes = $bookingStart <= $day ? 0 : TimeHelper::minutesOfDay($bookingStart);
        $endMinutes = $bookingEnd >= $day->modify('+1 day') ? 1440 : TimeHelper::minutesOfDay($bookingEnd);

        $bookedRanges[$resourceId][] = [$startMinutes - $buffer, $endMinutes + $buffer];
    }
}

$filterUrl = static function (string $date) use ($filters, $viewMode): string {
    return Url::toIndex(array_filter([
        'date' => $date,
        'category' => $filters['category'],
        'resource' => $filters['resource'],
        'view' => $viewMode,
    ], static fn($value): bool => $value !== null && $value !== ''));
};

$viewUrl = static function (string $view) use ($dateValue, $filters): string {
    return Url::toIndex(array_filter([
        'date' => $dateValue,
        'category' => $filters['category'],
        'resource' => $filters['resource'],
        'view' => $view,
    ], static fn($value): bool => $value !== null && $value !== ''));
};

$hasFilters = $filters['category'] !== null || $filters['resource'] !== null;
?>

<div class="container">
    <div class="card rb-page-header mb-3">
        <div class="card-body d-flex flex-wrap align-items-center gap-3">
            <span class="rb-page-header-icon"><?= Icon::get('calendar') ?></span>
            <div class="flex-grow-1">
                <h1 class="h3 mb-1"><?= Yii::t('ResourceBookingModule.base', 'Resource Booking') ?></h1>
                <p class="mb-0 text-muted"><?= Yii::t('ResourceBookingModule.base', 'Book meeting rooms, equipment and other shared resources.') ?></p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <?php if ($canManage): ?>
                    <?= Button::warning($pendingCount > 0
                        ? Yii::t('ResourceBookingModule.base', 'Pending approvals ({n})', ['n' => $pendingCount])
                        : Yii::t('ResourceBookingModule.base', 'Pending approvals'))
                        ->icon('clock-o')
                        ->link(Url::toPending())->sm() ?>
                <?php endif ?>
                <?= Button::light(Yii::t('ResourceBookingModule.base', 'My bookings'))->icon('list')->link(Url::toMy())->sm() ?>
                <?php if ($canCreateOnDay && $resources !== []): ?>
                    <?= ModalButton::primary(Yii::t('ResourceBookingModule.base', 'New booking'))
                        ->icon('plus')
                        ->load(Url::toCreate((int)$resources[0]->id, $dateValue))->sm() ?>
                <?php endif ?>
                <?php if ($isAdmin): ?>
                    <?= Button::light(Yii::t('ResourceBookingModule.base', 'Administration'))->icon('cogs')->link(Url::toAdmin())->sm() ?>
                <?php endif ?>
            </div>
        </div>
    </div>

    <div class="card rb-toolbar mb-3">
        <div class="card-body py-2 d-flex flex-wrap flex-lg-nowrap align-items-center gap-2">
            <div class="btn-group btn-group-sm flex-shrink-0" role="group" aria-label="<?= Html::encode(Yii::t('ResourceBookingModule.base', 'Change day')) ?>">
                <?= Button::light()->icon('chevron-left')->tooltip(Yii::t('ResourceBookingModule.base', 'Previous day'))->link($filterUrl($previousDate))->sm() ?>
                <?= Button::light(Yii::t('ResourceBookingModule.base', 'Today'))->link($filterUrl(TimeHelper::now()->format(TimeHelper::DATE_FORMAT)))->sm() ?>
                <?= Button::light()->icon('chevron-right')->tooltip(Yii::t('ResourceBookingModule.base', 'Next day'))->link($filterUrl($nextDate))->sm() ?>
            </div>

            <?= Html::beginForm(Url::toIndex(), 'get', [
                'class' => 'd-flex flex-wrap flex-sm-nowrap align-items-center gap-2 flex-grow-1 min-w-0',
                'data-rb-autosubmit' => '1',
            ]) ?>
            <?= Html::hiddenInput('view', $viewMode) ?>
            <?= Html::input('date', 'date', $dateValue, [
                'class' => 'form-control form-control-sm rb-filter-date',
                'aria-label' => Yii::t('ResourceBookingModule.base', 'Date'),
            ]) ?>
            <?= Html::dropDownList('category', $filters['category'], $categoryOptions, [
                'prompt' => Yii::t('ResourceBookingModule.base', 'All categories'),
                'class' => 'form-select form-select-sm rb-filter-category',
                'aria-label' => Yii::t('ResourceBookingModule.base', 'Category'),
            ]) ?>
            <?= Html::dropDownList('resource', $filters['resource'], $resourceFilterOptions, [
                'prompt' => Yii::t('ResourceBookingModule.base', 'All resources'),
                'class' => 'form-select form-select-sm rb-filter-resource',
                'aria-label' => Yii::t('ResourceBookingModule.base', 'Resource'),
            ]) ?>
            <?php if ($hasFilters): ?>
                <?= Button::light()->icon('times')->tooltip(Yii::t('ResourceBookingModule.base', 'Reset filters'))
                    ->link(Url::toIndex(['date' => $dateValue, 'view' => $viewMode]))->sm() ?>
            <?php endif ?>
            <noscript>
                <?= Button::secondary(Yii::t('ResourceBookingModule.base', 'Apply'))->submit()->sm() ?>
            </noscript>
            <?= Html::endForm() ?>

            <div class="btn-group btn-group-sm flex-shrink-0 ms-lg-auto" role="group" aria-label="<?= Html::encode(Yii::t('ResourceBookingModule.base', 'Day view layout')) ?>">
                <?= $viewMode === 'timeline'
                    ? Button::primary(Yii::t('ResourceBookingModule.base', 'Timeline'))->link($viewUrl('timeline'))
                    : Button::light(Yii::t('ResourceBookingModule.base', 'Timeline'))->link($viewUrl('timeline')) ?>
                <?= $viewMode === 'agenda'
                    ? Button::primary(Yii::t('ResourceBookingModule.base', 'Agenda'))->link($viewUrl('agenda'))
                    : Button::light(Yii::t('ResourceBookingModule.base', 'Agenda'))->link($viewUrl('agenda')) ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <?php if ($resources === []): ?>
                <div class="rb-empty">
                    <?= Icon::get('calendar-o') ?>
                    <p class="mt-2 mb-2"><?= Yii::t('ResourceBookingModule.base', 'No resources match the current filter.') ?></p>
                    <?php if ($allResources === []): ?>
                        <p class="mb-0"><?= Yii::t('ResourceBookingModule.base', 'An administrator has not added any resources yet.') ?></p>
                    <?php else: ?>
                        <?= Button::light(Yii::t('ResourceBookingModule.base', 'Clear filter'))->link(Url::toIndex(['date' => $dateValue, 'view' => $viewMode]))->sm() ?>
                    <?php endif ?>
                </div>
            <?php elseif ($viewMode === 'agenda'): ?>
                <div class="p-3">
                    <?= $this->render('_agenda', [
                        'resources' => $resources,
                        'bookingsByResource' => $bookingsByResource,
                        'dateValue' => $dateValue,
                        'canCreate' => $canCreate,
                    ]) ?>
                </div>
            <?php else: ?>
                <div class="rb-calendar" data-rb-calendar style="--rb-columns: <?= count($resources) ?>; --rb-min-width: <?= 56 + (count($resources) * 170) ?>px">
                    <div class="rb-calendar-header">
                        <div></div>
                        <?php foreach ($resources as $resource): ?>
                            <div class="rb-resource-head">
                                <span class="rb-resource-head-name"><?= Html::encode($resource->name) ?></span>
                                <span class="rb-resource-head-meta">
                                    <span class="rb-dot" style="background: <?= Html::encode($resource->getColor()) ?>"></span>
                                    <?= Html::encode($resource->category?->name ?? '') ?>
                                    <?php if ($resource->capacity > 0): ?>
                                        · <?= Yii::t('ResourceBookingModule.base', 'Capacity: {n}', ['n' => $resource->capacity]) ?>
                                    <?php endif ?>
                                </span>
                            </div>
                        <?php endforeach ?>
                    </div>

                    <div class="rb-calendar-body">
                        <div class="rb-time-axis" style="height: <?= $bodyHeight ?>px">
                            <?php for ($hour = $calendarStartHour; $hour <= $calendarEndHour; $hour++): ?>
                                <?php $labelTop = (($hour - $calendarStartHour) * 60 / $slotMinutes) * $slotHeight; ?>
                                <span class="rb-time-label" style="top: <?= $labelTop ?>px"><?= sprintf('%02d:00', $hour) ?></span>
                            <?php endfor ?>
                        </div>

                        <?php foreach ($resources as $resource): ?>
                            <?php
                            $resourceId = (int)$resource->id;
                            $rules = $rulesByResource[$resourceId] ?? null;
                            $ranges = $bookedRanges[$resourceId] ?? [];
                            $createUrl = Url::toCreate($resourceId, $dateValue);
                            ?>
                            <div class="rb-day-column"
                                 data-rb-column
                                 data-create-url="<?= Html::encode($createUrl) ?>"
                                 data-slot-minutes="<?= $slotMinutes ?>"
                                 data-slot-height="<?= $slotHeight ?>"
                                 style="height: <?= $bodyHeight ?>px">
                                <?php for ($slot = 0; $slot < $slotCount; $slot++): ?>
                                    <?php
                                    $slotStart = $gridStart->modify('+' . ($slot * $slotMinutes) . ' minutes');
                                    $slotEnd = $slotStart->modify('+' . $slotMinutes . ' minutes');
                                    $slotTime = $slotStart->format('H:i');
                                    $slotEndTime = $slotEnd->format('H:i');

                                    if ($slotEndTime === '00:00') {
                                        $slotEndTime = '23:59';
                                    }

                                    $slotStartMinutes = TimeHelper::minutesOfDay($slotStart);
                                    $isAvailable = $rules === null
                                        || ($rules->isDayAllowed((int)$slotStart->format('N'))
                                            && $slotStartMinutes >= $rules->getOpeningMinutes()
                                            && ($slotStartMinutes + $slotMinutes) <= $rules->getClosingMinutes());

                                    foreach ($ranges as [$rangeStart, $rangeEnd]) {
                                        if ($slotStartMinutes < $rangeEnd && ($slotStartMinutes + $slotMinutes) > $rangeStart) {
                                            $isAvailable = false;
                                            break;
                                        }
                                    }

                                    $isPast = $slotStart < $now;
                                    $canBookSlot = $canCreate && $isAvailable && !$isPast;
                                    ?>
                                    <?php if ($canBookSlot): ?>
                                        <a href="<?= Html::encode(Url::toCreate($resourceId, $dateValue, $slotTime)) ?>"
                                           class="rb-slot is-bookable"
                                           data-rb-slot
                                           data-slot-time="<?= $slotTime ?>"
                                           data-slot-end="<?= $slotEndTime ?>"
                                           draggable="false"
                                           style="height: <?= $slotHeight ?>px"
                                           data-action-click="ui.modal.load"
                                           data-action-click-url="<?= Html::encode(Url::toCreate($resourceId, $dateValue, $slotTime)) ?>"
                                           data-pjax-prevent
                                           title="<?= Html::encode(Yii::t('ResourceBookingModule.base', 'Book {resource} from {time}. Drag to select a longer time span.', ['resource' => $resource->name, 'time' => $slotTime])) ?>"></a>
                                    <?php else: ?>
                                        <span class="rb-slot" style="height: <?= $slotHeight ?>px" aria-hidden="true"></span>
                                    <?php endif ?>
                                <?php endfor ?>

                                <?php foreach ($bookingsByResource[$resourceId] ?? [] as $booking): ?>
                                    <?php
                                    $bookingStart = $booking->getStartLocal();
                                    $bookingEnd = $booking->getEndLocal();

                                    if ($bookingStart === null || $bookingEnd === null || $bookingEnd <= $gridStart || $bookingStart >= $gridEnd) {
                                        continue;
                                    }

                                    $visibleStart = $bookingStart < $gridStart ? $gridStart : $bookingStart;
                                    $visibleEnd = $bookingEnd > $gridEnd ? $gridEnd : $bookingEnd;
                                    $top = (TimeHelper::minutesBetween($gridStart, $visibleStart) / $slotMinutes) * $slotHeight;
                                    $height = max(24, (TimeHelper::minutesBetween($visibleStart, $visibleEnd) / $slotMinutes) * $slotHeight - 2);
                                    ?>
                                    <a href="<?= Html::encode(Url::toView((int)$booking->id)) ?>"
                                       class="rb-booking<?= $booking->isPending() ? ' is-pending' : '' ?>"
                                       style="top: <?= (int)$top ?>px; height: <?= (int)$height ?>px; --rb-booking-color: <?= Html::encode($resource->getColor()) ?>"
                                       title="<?= Html::encode($booking->title . ' · ' . $booking->getTimeRangeLabel() . ' · ' . ($booking->user?->displayName ?? '')) ?>">
                                        <span class="rb-booking-time"><?= Html::encode($booking->getTimeRangeLabel()) ?></span>
                                        <span class="rb-booking-title"><?= Html::encode($booking->title) ?></span>
                                        <span class="rb-booking-meta">
                                            <?= Html::encode($booking->user?->displayName ?? '') ?>
                                            <?php if ($booking->isPending()): ?>
                                                · <?= Html::encode(Yii::t('ResourceBookingModule.base', 'Pending')) ?>
                                            <?php endif ?>
                                        </span>
                                    </a>
                                <?php endforeach ?>

                                <div class="rb-drag-hint" data-rb-drag-hint hidden></div>
                            </div>
                        <?php endforeach ?>
                    </div>
                </div>
            <?php endif ?>
        </div>
    </div>
</div>
