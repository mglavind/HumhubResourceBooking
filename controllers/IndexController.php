<?php

namespace mglavind\humhub\modules\resourceBooking\controllers;

use humhub\components\Controller;
use humhub\modules\admin\permissions\ManageSettings;
use mglavind\humhub\modules\resourceBooking\assets\Assets;
use mglavind\humhub\modules\resourceBooking\helpers\TimeHelper;
use mglavind\humhub\modules\resourceBooking\models\Booking;
use mglavind\humhub\modules\resourceBooking\models\Category;
use mglavind\humhub\modules\resourceBooking\models\Resource;
use mglavind\humhub\modules\resourceBooking\permissions\CreateBooking;
use Yii;

class IndexController extends Controller
{
    public function init(): void
    {
        parent::init();

        $this->appendPageTitle(Yii::t('ResourceBookingModule.base', 'Resource Booking'));
    }

    public function getAccessRules(): array
    {
        return [
            ['login'],
        ];
    }

    /**
     * @param string|null $date
     * @param int|string|null $category
     * @param int|string|null $resource
     * @param string|null $view
     */
    public function actionIndex($date = null, $category = null, $resource = null, $view = null): string
    {
        $date = is_string($date) ? $date : null;
        $category = is_scalar($category) ? (string)$category : null;
        $resource = is_scalar($resource) ? (string)$resource : null;
        $viewMode = in_array($view, ['timeline', 'agenda'], true) ? $view : 'timeline';

        Assets::register($this->view);

        $day = TimeHelper::date($date);
        $dateValue = $day->format(TimeHelper::DATE_FORMAT);
        [$dayStart, $dayEnd] = TimeHelper::dayBounds($dateValue);

        $categoryId = (int)$category ?: null;
        $resourceId = (int)$resource ?: null;

        $allResources = Resource::findActive()->all();
        $resourcesQuery = Resource::findActive();

        if ($categoryId !== null) {
            $resourcesQuery->andWhere(['r.category_id' => $categoryId]);
        }

        if ($resourceId !== null) {
            $resourcesQuery->andWhere(['r.id' => $resourceId]);
        }

        $resources = $resourcesQuery->all();
        $resourceIds = array_map(static fn(Resource $item): int => (int)$item->id, $resources);

        $bookings = [];

        if ($resourceIds !== []) {
            $bookings = Booking::find()
                ->where(['in', 'resource_id', $resourceIds])
                ->andWhere(['in', 'status', Booking::activeStatuses()])
                ->andWhere(['<', 'start_time', TimeHelper::toDatabase($dayEnd)])
                ->andWhere(['>', 'end_time', TimeHelper::toDatabase($dayStart)])
                ->orderBy(['start_time' => SORT_ASC])
                ->with('user')
                ->all();
        }

        $bookingsByResource = [];

        foreach ($bookings as $booking) {
            $bookingsByResource[(int)$booking->resource_id][] = $booking;
        }

        $configuration = $this->module->getConfiguration();
        $calendarStartHour = max(0, min(23, $configuration->calendarStartHour));
        $calendarEndHour = max($calendarStartHour + 1, min(24, $configuration->calendarEndHour));

        $canManage = Booking::canManage();

        return $this->render('index', [
            'day' => $day,
            'dateValue' => $dateValue,
            'previousDate' => $day->modify('-1 day')->format(TimeHelper::DATE_FORMAT),
            'nextDate' => $day->modify('+1 day')->format(TimeHelper::DATE_FORMAT),
            'resources' => $resources,
            'bookingsByResource' => $bookingsByResource,
            'categories' => Category::findActive()->all(),
            'allResources' => $allResources,
            'filters' => [
                'category' => $categoryId,
                'resource' => $resourceId,
            ],
            'calendarStartHour' => $calendarStartHour,
            'calendarEndHour' => $calendarEndHour,
            'slotMinutes' => 30,
            'canCreate' => Yii::$app->user->can(CreateBooking::class) || Booking::canManage(),
            'canManage' => $canManage,
            'viewMode' => $viewMode,
            'pendingCount' => $canManage
                ? (int)Booking::find()->where(['status' => Booking::STATUS_PENDING])->count()
                : 0,
            'isAdmin' => Yii::$app->user->can(ManageSettings::class),
        ]);
    }
}
