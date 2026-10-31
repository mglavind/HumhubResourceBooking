<?php

namespace mglavind\humhub\modules\resourceBooking;

use mglavind\humhub\modules\resourceBooking\models\Configuration;
use mglavind\humhub\modules\resourceBooking\permissions\CreateBooking;
use mglavind\humhub\modules\resourceBooking\permissions\ManageBookings;
use Yii;
use yii\helpers\Url;

class Module extends \humhub\components\Module
{
    /**
     * @inheritdoc
     */
    public $defaultRoute = 'index';

    /**
     * @inheritdoc
     */
    public $resourcesPath = 'resources';

    private ?Configuration $configuration = null;

    /**
     * @inheritdoc
     */
    public function getConfigUrl(): string
    {
        return Url::to(['/resource-booking/admin/settings']);
    }

    /**
     * @inheritdoc
     */
    public function getPermissions($contentContainer = null): array
    {
        if ($contentContainer !== null) {
            return [];
        }

        return [
            new CreateBooking(),
            new ManageBookings(),
        ];
    }

    /**
     * @return static
     */
    public static function instance(): self
    {
        /** @var static $module */
        $module = Yii::$app->getModule('resource-booking');

        return $module;
    }

    public function getConfiguration(): Configuration
    {
        if ($this->configuration === null) {
            $this->configuration = new Configuration(['settingsManager' => $this->settings]);
            $this->configuration->loadBySettings();
        }

        return $this->configuration;
    }
}
