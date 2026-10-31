<?php

use humhub\helpers\Html;
use humhub\modules\ui\icon\widgets\Icon;
use humhub\widgets\bootstrap\Badge;
use humhub\widgets\bootstrap\Button;
use mglavind\humhub\modules\resourceBooking\helpers\Url;

/* @var $this \humhub\components\View */
/* @var $resources \mglavind\humhub\modules\resourceBooking\models\Resource[] */
?>

<div class="container-fluid">
    <?= $this->render('_menu') ?>

    <div class="card">
        <div class="card-header d-flex align-items-center">
            <strong><?= Yii::t('ResourceBookingModule.base', 'Resources') ?></strong>
            <?= Button::primary(Yii::t('ResourceBookingModule.base', 'New resource'))
                ->icon('plus')
                ->link(Url::toAdminResourceEdit())->sm()->cssClass('ms-auto') ?>
        </div>

        <?php if ($resources === []): ?>
            <div class="card-body rb-empty">
                <?= Icon::get('building') ?>
                <p class="mt-2 mb-0"><?= Yii::t('ResourceBookingModule.base', 'Add the resources that users should be able to book.') ?></p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th><?= Yii::t('ResourceBookingModule.base', 'Resource') ?></th>
                        <th><?= Yii::t('ResourceBookingModule.base', 'Category') ?></th>
                        <th><?= Yii::t('ResourceBookingModule.base', 'Location') ?></th>
                        <th><?= Yii::t('ResourceBookingModule.base', 'Capacity') ?></th>
                        <th><?= Yii::t('ResourceBookingModule.base', 'Status') ?></th>
                        <th class="text-end"><?= Yii::t('ResourceBookingModule.base', 'Actions') ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($resources as $resource): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-start gap-2">
                                    <span class="rb-color-preview mt-1" style="background: <?= Html::encode($resource->getColor()) ?>"></span>
                                    <div>
                                        <div class="fw-semibold"><?= Html::encode($resource->name) ?></div>
                                        <?php if (!empty($resource->description)): ?>
                                            <div class="small text-muted"><?= Html::encode($resource->description) ?></div>
                                        <?php endif ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if ($resource->category !== null): ?>
                                    <span class="rb-dot" style="background: <?= Html::encode($resource->category->getColor()) ?>"></span>
                                    <?= Html::encode($resource->category->name) ?>
                                <?php endif ?>
                            </td>
                            <td><?= Html::encode($resource->location ?? '') ?></td>
                            <td><?= (int)$resource->capacity ?: '–' ?></td>
                            <td>
                                <?= $resource->is_active
                                    ? Badge::success(Yii::t('ResourceBookingModule.base', 'Active'))
                                    : Badge::secondary(Yii::t('ResourceBookingModule.base', 'Inactive')) ?>
                            </td>
                            <td>
                                <div class="rb-table-actions">
                                    <?= Button::light(Yii::t('ResourceBookingModule.base', 'Edit'))
                                        ->icon('edit')
                                        ->link(Url::toAdminResourceEdit((int)$resource->id))->sm() ?>

                                    <?= Html::beginForm(['/resource-booking/admin/resource-delete', 'id' => $resource->id], 'post', ['class' => 'd-inline']) ?>
                                    <?= Button::danger()
                                        ->icon('trash')
                                        ->tooltip(Yii::t('ResourceBookingModule.base', 'Delete resource'))
                                        ->submit()
                                        ->confirm(
                                            Yii::t('ResourceBookingModule.base', 'Delete resource'),
                                            Yii::t('ResourceBookingModule.base', 'Do you really want to delete this resource?'),
                                        )
                                        ->sm() ?>
                                    <?= Html::endForm() ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        <?php endif ?>
    </div>
</div>
