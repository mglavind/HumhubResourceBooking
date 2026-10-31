<?php

namespace mglavind\humhub\modules\resourceBooking\controllers;

use humhub\components\Controller;
use humhub\widgets\modal\ModalClose;
use mglavind\humhub\modules\resourceBooking\assets\Assets;
use mglavind\humhub\modules\resourceBooking\helpers\TimeHelper;
use mglavind\humhub\modules\resourceBooking\helpers\Url;
use mglavind\humhub\modules\resourceBooking\models\Booking;
use mglavind\humhub\modules\resourceBooking\models\BookingForm;
use mglavind\humhub\modules\resourceBooking\models\Resource;
use mglavind\humhub\modules\resourceBooking\permissions\CreateBooking;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class BookingController extends Controller
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
     * @param \yii\base\Action $action
     */
    public function beforeAction($action): bool
    {
        Assets::register($this->view);

        return parent::beforeAction($action);
    }

    /**
     * @param int|null $resource_id
     * @param string|null $date
     * @param string|null $start_time
     * @param string|null $end_time
     */
    public function actionCreate($resource_id = null, $date = null, $start_time = null, $end_time = null): Response|string
    {
        $resource_id = is_scalar($resource_id) ? (int)$resource_id : null;
        $date = is_string($date) ? $date : null;
        $start_time = is_string($start_time) ? $start_time : null;
        $end_time = is_string($end_time) ? $end_time : null;

        if (!Yii::$app->user->can(CreateBooking::class) && !Booking::canManage()) {
            throw new ForbiddenHttpException(Yii::t('ResourceBookingModule.base', 'You are not allowed to create bookings.'));
        }

        if (!Resource::findActive()->exists()) {
            $this->view->error(Yii::t('ResourceBookingModule.base', 'No resources are available for booking yet.'));

            return $this->redirect(['/resource-booking/index']);
        }

        $model = new BookingForm(null, [
            'resource_id' => (int)$resource_id ?: null,
            'date' => $date,
            'start_time' => $start_time,
            'end_time' => $end_time,
        ]);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return ModalClose::widget(['saved' => true, 'reload' => true]);
        }

        return $this->renderAjax('create', [
            'model' => $model,
        ]);
    }

    /**
     * @param int $id
     */
    public function actionEdit($id): string
    {
        $booking = $this->findBooking($id);

        if (!$booking->canBeEditedBy(Yii::$app->user->identity)) {
            throw new NotFoundHttpException();
        }

        if (!Booking::canManage() && !Yii::$app->user->can(CreateBooking::class)) {
            throw new ForbiddenHttpException(Yii::t('ResourceBookingModule.base', 'You are not allowed to edit bookings.'));
        }

        $model = new BookingForm($booking);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return ModalClose::widget(['saved' => true, 'reload' => true]);
        }

        return $this->renderAjax('edit', [
            'model' => $model,
            'booking' => $booking,
        ]);
    }

    /**
     * @param int $id
     */
    public function actionView($id): string
    {
        $booking = $this->findBooking($id);

        if (!$booking->canBeViewedBy(Yii::$app->user->identity)) {
            throw new NotFoundHttpException();
        }

        return $this->render('view', [
            'booking' => $booking,
            'canEdit' => $booking->canBeEditedBy(Yii::$app->user->identity),
            'canCancel' => $booking->canBeCancelledBy(Yii::$app->user->identity),
            'canManage' => Booking::canManage(),
        ]);
    }

    /**
     * @param int $id
     */
    public function actionCancel($id): Response
    {
        $this->forcePostRequest();

        $booking = $this->findBooking($id);

        if (!$booking->canBeCancelledBy(Yii::$app->user->identity)) {
            throw new ForbiddenHttpException(Yii::t('ResourceBookingModule.base', 'You are not allowed to cancel this booking.'));
        }

        $booking->status = Booking::STATUS_CANCELLED;
        $booking->cancelled_at = TimeHelper::toDatabase(TimeHelper::nowUtc());
        $reason = trim((string)Yii::$app->request->post('reason'));
        $booking->cancellation_reason = $reason !== '' ? mb_substr($reason, 0, 255) : null;
        $booking->save(false);

        $this->view->success(Yii::t('ResourceBookingModule.base', 'The booking has been cancelled.'));

        return $this->redirect($this->returnUrl($booking));
    }

    /**
     * @param int $id
     */
    public function actionApprove($id): Response
    {
        $this->forcePostRequest();

        if (!Booking::canManage()) {
            throw new ForbiddenHttpException(Yii::t('ResourceBookingModule.base', 'You are not allowed to approve bookings.'));
        }

        $booking = $this->findBooking($id);

        if (!$booking->isPending()) {
            $this->view->error(Yii::t('ResourceBookingModule.base', 'Only pending bookings can be approved.'));

            return $this->redirect($this->returnUrl($booking));
        }

        $booking->status = Booking::STATUS_CONFIRMED;
        $booking->approved_by = (int)Yii::$app->user->id;
        $booking->approved_at = TimeHelper::toDatabase(TimeHelper::nowUtc());
        $booking->save(false);

        $this->view->success(Yii::t('ResourceBookingModule.base', 'The booking has been approved.'));

        return $this->redirect($this->returnUrl($booking));
    }

    /**
     * @param int $id
     */
    public function actionReject($id): Response
    {
        $this->forcePostRequest();

        if (!Booking::canManage()) {
            throw new ForbiddenHttpException(Yii::t('ResourceBookingModule.base', 'You are not allowed to reject bookings.'));
        }

        $booking = $this->findBooking($id);

        if (!$booking->isPending()) {
            $this->view->error(Yii::t('ResourceBookingModule.base', 'Only pending bookings can be rejected.'));

            return $this->redirect($this->returnUrl($booking));
        }

        $booking->status = Booking::STATUS_REJECTED;
        $booking->approved_by = (int)Yii::$app->user->id;
        $booking->approved_at = TimeHelper::toDatabase(TimeHelper::nowUtc());
        $booking->save(false);

        $this->view->success(Yii::t('ResourceBookingModule.base', 'The booking has been rejected.'));

        return $this->redirect($this->returnUrl($booking));
    }

    public function actionMy(): string
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Booking::find()
                ->where(['user_id' => (int)Yii::$app->user->id])
                ->with(['resource.category'])
                ->orderBy(['start_time' => SORT_DESC]),
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        return $this->render('my', [
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionPending(): string
    {
        if (!Booking::canManage()) {
            throw new ForbiddenHttpException(Yii::t('ResourceBookingModule.base', 'You are not allowed to manage bookings.'));
        }

        $dataProvider = new ActiveDataProvider([
            'query' => Booking::find()
                ->where(['status' => Booking::STATUS_PENDING])
                ->orderBy(['start_time' => SORT_ASC])
                ->with(['resource.category', 'user']),
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        return $this->render('pending', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * @param int|string $id
     */
    protected function findBooking($id): Booking
    {
        $booking = Booking::find()
            ->where(['id' => is_scalar($id) ? (int)$id : 0])
            ->with(['resource.category', 'user', 'approvedBy'])
            ->one();

        if ($booking === null) {
            throw new NotFoundHttpException();
        }

        return $booking;
    }

    protected function returnUrl(Booking $booking): string
    {
        $referrer = Yii::$app->request->referrer;

        if (!empty($referrer) && str_starts_with($referrer, Yii::$app->request->hostInfo . '/')) {
            return $referrer;
        }

        return Url::toView((int)$booking->id);
    }
}
