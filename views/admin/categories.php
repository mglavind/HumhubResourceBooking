<?php

use humhub\helpers\Html;
use humhub\modules\ui\icon\widgets\Icon;
use humhub\widgets\bootstrap\Badge;
use humhub\widgets\bootstrap\Button;
use mglavind\humhub\modules\resourceBooking\helpers\Url;

/* @var $this \humhub\components\View */
/* @var $categories \mglavind\humhub\modules\resourceBooking\models\Category[] */
?>

<div class="container-fluid">
    <?= $this->render('_menu') ?>

    <div class="card">
        <div class="card-header d-flex align-items-center">
            <strong><?= Yii::t('ResourceBookingModule.base', 'Categories') ?></strong>
            <?= Button::primary(Yii::t('ResourceBookingModule.base', 'New category'))
                ->icon('plus')
                ->link(Url::toAdminCategoryEdit())->sm()->cssClass('ms-auto') ?>
        </div>

        <?php if ($categories === []): ?>
            <div class="card-body rb-empty">
                <?= Icon::get('folder-open') ?>
                <p class="mt-2 mb-0"><?= Yii::t('ResourceBookingModule.base', 'Create a category before adding resources.') ?></p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th><?= Yii::t('ResourceBookingModule.base', 'Category') ?></th>
                        <th><?= Yii::t('ResourceBookingModule.base', 'Resources') ?></th>
                        <th><?= Yii::t('ResourceBookingModule.base', 'Approval') ?></th>
                        <th><?= Yii::t('ResourceBookingModule.base', 'Rules') ?></th>
                        <th><?= Yii::t('ResourceBookingModule.base', 'Status') ?></th>
                        <th class="text-end"><?= Yii::t('ResourceBookingModule.base', 'Actions') ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($categories as $category): ?>
                        <?php $rules = $category->getEffectiveRules(); ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-start gap-2">
                                    <span class="rb-color-preview mt-1" style="background: <?= Html::encode($category->getColor()) ?>"></span>
                                    <div>
                                        <div class="fw-semibold"><?= Html::encode($category->name) ?></div>
                                        <?php if (!empty($category->description)): ?>
                                            <div class="small text-muted"><?= Html::encode($category->description) ?></div>
                                        <?php endif ?>
                                    </div>
                                </div>
                            </td>
                            <td><?= count($category->resources) ?></td>
                            <td><?= Html::encode($category->getApprovalLabel()) ?></td>
                            <td class="small text-muted">
                                <div><?= Html::encode($rules->getOpeningLabel()) ?> – <?= Html::encode($rules->getClosingLabel()) ?></div>
                                <div><?= Yii::t('ResourceBookingModule.base', '{min} – {max} minutes', [
                                    'min' => $rules->minDurationMinutes,
                                    'max' => $rules->maxDurationMinutes ?: '∞',
                                ]) ?></div>
                            </td>
                            <td>
                                <?= $category->is_active
                                    ? Badge::success(Yii::t('ResourceBookingModule.base', 'Active'))
                                    : Badge::secondary(Yii::t('ResourceBookingModule.base', 'Inactive')) ?>
                            </td>
                            <td>
                                <div class="rb-table-actions">
                                    <?= Button::light(Yii::t('ResourceBookingModule.base', 'Edit'))
                                        ->icon('edit')
                                        ->link(Url::toAdminCategoryEdit((int)$category->id))->sm() ?>

                                    <?= Html::beginForm(['/resource-booking/admin/category-delete', 'id' => $category->id], 'post', ['class' => 'd-inline']) ?>
                                    <?= Button::danger()
                                        ->icon('trash')
                                        ->tooltip(Yii::t('ResourceBookingModule.base', 'Delete category'))
                                        ->submit()
                                        ->confirm(
                                            Yii::t('ResourceBookingModule.base', 'Delete category'),
                                            Yii::t('ResourceBookingModule.base', 'Do you really want to delete this category?'),
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
