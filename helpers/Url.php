<?php

namespace mglavind\humhub\modules\resourceBooking\helpers;

use yii\helpers\Url as BaseUrl;

class Url
{
    /**
     * @param array $params
     */
    public static function toIndex(array $params = []): string
    {
        return BaseUrl::to(array_merge(['/resource-booking/index'], $params));
    }

    public static function toCreate(?int $resourceId = null, ?string $date = null, ?string $startTime = null, ?string $endTime = null): string
    {
        $params = [];

        if ($resourceId !== null) {
            $params['resource_id'] = $resourceId;
        }

        if ($date !== null) {
            $params['date'] = $date;
        }

        if ($startTime !== null) {
            $params['start_time'] = $startTime;
        }

        if ($endTime !== null) {
            $params['end_time'] = $endTime;
        }

        return BaseUrl::to(array_merge(['/resource-booking/booking/create'], $params));
    }

    public static function toEdit(int $id): string
    {
        return BaseUrl::to(['/resource-booking/booking/edit', 'id' => $id]);
    }

    public static function toView(int $id): string
    {
        return BaseUrl::to(['/resource-booking/booking/view', 'id' => $id]);
    }

    public static function toMy(): string
    {
        return BaseUrl::to(['/resource-booking/booking/my']);
    }

    public static function toPending(): string
    {
        return BaseUrl::to(['/resource-booking/booking/pending']);
    }

    public static function toAdmin(): string
    {
        return BaseUrl::to(['/resource-booking/admin']);
    }

    public static function toAdminSettings(): string
    {
        return BaseUrl::to(['/resource-booking/admin/settings']);
    }

    public static function toAdminCategories(): string
    {
        return BaseUrl::to(['/resource-booking/admin/categories']);
    }

    public static function toAdminCategoryEdit(?int $id = null): string
    {
        return BaseUrl::to($id === null
            ? ['/resource-booking/admin/category-edit']
            : ['/resource-booking/admin/category-edit', 'id' => $id]);
    }

    public static function toAdminResources(): string
    {
        return BaseUrl::to(['/resource-booking/admin/resources']);
    }

    public static function toAdminResourceEdit(?int $id = null): string
    {
        return BaseUrl::to($id === null
            ? ['/resource-booking/admin/resource-edit']
            : ['/resource-booking/admin/resource-edit', 'id' => $id]);
    }

    /**
     * @param array $params
     */
    public static function toAdminBookings(array $params = []): string
    {
        return BaseUrl::to(array_merge(['/resource-booking/admin/bookings'], $params));
    }
}
