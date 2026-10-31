<?php

namespace mglavind\humhub\modules\resourceBooking\controllers;

use humhub\modules\admin\components\Controller;
use humhub\modules\admin\permissions\ManageSettings;
use mglavind\humhub\modules\resourceBooking\assets\Assets;
use mglavind\humhub\modules\resourceBooking\helpers\TimeHelper;
use mglavind\humhub\modules\resourceBooking\helpers\Url;
use mglavind\humhub\modules\resourceBooking\models\Booking;
use mglavind\humhub\modules\resourceBooking\models\Category;
use mglavind\humhub\modules\resourceBooking\models\Resource;
use mglavind\humhub\modules\resourceBooking\services\BookingReminders;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class AdminController extends Controller
{
    public function init(): void
    {
        parent::init();

        $this->appendPageTitle(Yii::t('ResourceBookingModule.base', 'Resource Booking'));
    }

    /**
     * @param \yii\base\Action $action
     */
    public function beforeAction($action): bool
    {
        Assets::register($this->view);

        return parent::beforeAction($action);
    }

    protected function getAccessRules(): array
    {
        return [
            ['permission' => ManageSettings::class],
        ];
    }

    public function actionIndex(): string
    {
        $now = TimeHelper::toDatabase(TimeHelper::nowUtc());

        return $this->render('index', [
            'stats' => [
                'categories' => (int)Category::find()->count(),
                'resources' => (int)Resource::find()->count(),
                'pending' => (int)Booking::find()->where(['status' => Booking::STATUS_PENDING])->count(),
                'upcoming' => (int)Booking::find()
                    ->where(['>=', 'end_time', $now])
                    ->andWhere(['in', 'status', Booking::activeStatuses()])
                    ->count(),
            ],
            'pendingBookings' => Booking::find()
                ->where(['status' => Booking::STATUS_PENDING])
                ->orderBy(['start_time' => SORT_ASC])
                ->limit(5)
                ->with(['resource.category', 'user'])
                ->all(),
            'upcomingBookings' => Booking::find()
                ->where(['>=', 'end_time', $now])
                ->andWhere(['in', 'status', Booking::activeStatuses()])
                ->orderBy(['start_time' => SORT_ASC])
                ->limit(5)
                ->with(['resource.category', 'user'])
                ->all(),
        ]);
    }

    public function actionSettings(): Response|string
    {
        $model = $this->module->getConfiguration();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            BookingReminders::rescheduleUpcoming();

            $this->view->saved();

            return $this->redirect(['settings']);
        }

        return $this->render('settings', [
            'model' => $model,
        ]);
    }

    public function actionCategories(): string
    {
        return $this->render('categories', [
            'categories' => Category::find()
                ->with('resources')
                ->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC])
                ->all(),
        ]);
    }

    /**
     * @param int|null $id
     */
    public function actionCategoryEdit($id = null): Response|string
    {
        $model = $id !== null
            ? $this->findCategory($id)
            : new Category(['is_active' => 1, 'color' => '#435f6f']);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $this->view->success(Yii::t('ResourceBookingModule.base', 'The category has been saved.'));

            return $this->redirect(['categories']);
        }

        return $this->render('category-form', [
            'model' => $model,
        ]);
    }

    /**
     * @param int $id
     */
    public function actionCategoryDelete($id): Response
    {
        $this->forcePostRequest();

        $model = $this->findCategory($id);

        if ($model->getResources()->exists()) {
            $this->view->error(Yii::t('ResourceBookingModule.base', 'This category still contains resources. Move or delete the resources before deleting the category.'));

            return $this->redirect(['categories']);
        }

        $model->delete();
        $this->view->success(Yii::t('ResourceBookingModule.base', 'The category has been deleted.'));

        return $this->redirect(['categories']);
    }

    public function actionResources(): string
    {
        return $this->render('resources', [
            'resources' => Resource::find()
                ->alias('r')
                ->innerJoinWith('category c')
                ->orderBy([
                    'c.sort_order' => SORT_ASC,
                    'c.name' => SORT_ASC,
                    'r.sort_order' => SORT_ASC,
                    'r.name' => SORT_ASC,
                ])
                ->all(),
        ]);
    }

    /**
     * @param int|null $id
     */
    public function actionResourceEdit($id = null): Response|string
    {
        $model = $id !== null
            ? $this->findResource($id)
            : new Resource(['is_active' => 1]);

        $categories = Category::find()
            ->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC])
            ->all();

        if ($categories === []) {
            $this->view->error(Yii::t('ResourceBookingModule.base', 'Please create a category before adding resources.'));

            return $this->redirect(['categories']);
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $this->view->success(Yii::t('ResourceBookingModule.base', 'The resource has been saved.'));

            return $this->redirect(['resources']);
        }

        $categoryOptions = [];

        foreach ($categories as $category) {
            $categoryOptions[$category->id] = $category->name
                . ($category->is_active ? '' : ' (' . Yii::t('ResourceBookingModule.base', 'inactive') . ')');
        }

        return $this->render('resource-form', [
            'model' => $model,
            'categoryOptions' => $categoryOptions,
        ]);
    }

    /**
     * @param int $id
     */
    public function actionResourceDelete($id): Response
    {
        $this->forcePostRequest();

        $model = $this->findResource($id);

        if ($model->getBookings()->exists()) {
            $this->view->error(Yii::t('ResourceBookingModule.base', 'This resource already has bookings. Deactivate it instead of deleting it.'));

            return $this->redirect(['resources']);
        }

        $model->delete();
        $this->view->success(Yii::t('ResourceBookingModule.base', 'The resource has been deleted.'));

        return $this->redirect(['resources']);
    }

    /**
     * @param string|null $status
     * @param int|null $resource_id
     * @param string|null $from
     * @param string|null $to
     */
    public function actionBookings($status = null, $resource_id = null, $from = null, $to = null): string
    {
        $status = is_string($status) ? $status : null;
        $resource_id = is_scalar($resource_id) ? (int)$resource_id : null;
        $from = is_string($from) ? $from : null;
        $to = is_string($to) ? $to : null;

        $query = Booking::find()
            ->alias('b')
            ->with(['resource.category', 'user', 'approvedBy'])
            ->orderBy(['b.start_time' => SORT_DESC]);

        if (in_array($status, Booking::STATUSES, true)) {
            $query->andWhere(['b.status' => $status]);
        }

        $resourceId = (int)$resource_id ?: null;

        if ($resourceId !== null) {
            $query->andWhere(['b.resource_id' => $resourceId]);
        }

        if (!empty($from) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
            [$fromStart] = TimeHelper::dayBounds($from);
            $query->andWhere(['>=', 'b.start_time', TimeHelper::toDatabase($fromStart)]);
        }

        if (!empty($to) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            [, $toEnd] = TimeHelper::dayBounds($to);
            $query->andWhere(['<', 'b.start_time', TimeHelper::toDatabase($toEnd)]);
        }

        $resources = Resource::find()
            ->alias('r')
            ->innerJoinWith('category c')
            ->orderBy(['c.name' => SORT_ASC, 'r.name' => SORT_ASC])
            ->all();

        $resourceOptions = [];

        foreach ($resources as $resource) {
            $resourceOptions[$resource->id] = ($resource->category?->name ? $resource->category->name . ' · ' : '')
                . $resource->name
                . ($resource->is_active ? '' : ' (' . Yii::t('ResourceBookingModule.base', 'inactive') . ')');
        }

        return $this->render('bookings', [
            'dataProvider' => new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => 25,
                ],
            ]),
            'resourceOptions' => $resourceOptions,
            'filters' => [
                'status' => in_array($status, Booking::STATUSES, true) ? $status : '',
                'resource_id' => $resourceId,
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }

    /**
     * @param int $id
     */
    public function actionApprove($id): Response
    {
        $this->forcePostRequest();

        $booking = $this->findBooking($id);

        if (!$booking->isPending()) {
            $this->view->error(Yii::t('ResourceBookingModule.base', 'Only pending bookings can be approved.'));

            return $this->redirect($this->returnUrl());
        }

        $booking->status = Booking::STATUS_CONFIRMED;
        $booking->approved_by = (int)Yii::$app->user->id;
        $booking->approved_at = TimeHelper::toDatabase(TimeHelper::nowUtc());
        $booking->save(false);

        BookingReminders::schedule($booking);

        $this->view->success(Yii::t('ResourceBookingModule.base', 'The booking has been approved.'));

        return $this->redirect($this->returnUrl());
    }

    /**
     * @param int $id
     */
    public function actionReject($id): Response
    {
        $this->forcePostRequest();

        $booking = $this->findBooking($id);

        if (!$booking->isPending()) {
            $this->view->error(Yii::t('ResourceBookingModule.base', 'Only pending bookings can be rejected.'));

            return $this->redirect($this->returnUrl());
        }

        $booking->status = Booking::STATUS_REJECTED;
        $booking->approved_by = (int)Yii::$app->user->id;
        $booking->approved_at = TimeHelper::toDatabase(TimeHelper::nowUtc());
        $booking->save(false);

        $this->view->success(Yii::t('ResourceBookingModule.base', 'The booking has been rejected.'));

        return $this->redirect($this->returnUrl());
    }

    /**
     * @param int $id
     */
    public function actionCancel($id): Response
    {
        $this->forcePostRequest();

        $booking = $this->findBooking($id);

        if (!$booking->isActiveStatus()) {
            $this->view->error(Yii::t('ResourceBookingModule.base', 'Only active bookings can be cancelled.'));

            return $this->redirect($this->returnUrl());
        }

        $booking->status = Booking::STATUS_CANCELLED;
        $booking->cancelled_at = TimeHelper::toDatabase(TimeHelper::nowUtc());
        $booking->save(false);

        $this->view->success(Yii::t('ResourceBookingModule.base', 'The booking has been cancelled.'));

        return $this->redirect($this->returnUrl());
    }

    protected function returnUrl(): string
    {
        $referrer = Yii::$app->request->referrer;

        if (!empty($referrer) && str_starts_with($referrer, Yii::$app->request->hostInfo . '/')) {
            return $referrer;
        }

        return Url::toAdminBookings();
    }

    /**
     * @param int|string $id
     */
    protected function findCategory($id): Category
    {
        $model = Category::findOne(is_scalar($id) ? (int)$id : 0);

        if ($model === null) {
            throw new NotFoundHttpException();
        }

        return $model;
    }

    /**
     * @param int|string $id
     */
    protected function findResource($id): Resource
    {
        $model = Resource::findOne(is_scalar($id) ? (int)$id : 0);

        if ($model === null) {
            throw new NotFoundHttpException();
        }

        return $model;
    }

    /**
     * @param int|string $id
     */
    protected function findBooking($id): Booking
    {
        $model = Booking::findOne(is_scalar($id) ? (int)$id : 0);

        if ($model === null) {
            throw new NotFoundHttpException();
        }

        return $model;
    }
}
