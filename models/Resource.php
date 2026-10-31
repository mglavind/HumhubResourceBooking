<?php

namespace mglavind\humhub\modules\resourceBooking\models;

use humhub\components\ActiveRecord;
use Yii;
use yii\db\ActiveQuery;

/**
 * @property int $id
 * @property int $category_id
 * @property string $name
 * @property string|null $description
 * @property string|null $location
 * @property int $capacity
 * @property string|null $color
 * @property int $sort_order
 * @property int|bool $is_active
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property int|null $created_by
 * @property int|null $updated_by
 *
 * @property Category $category
 * @property Booking[] $bookings
 */
class Resource extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'resourcebooking_resource';
    }

    public function rules(): array
    {
        return [
            [['category_id', 'name'], 'required'],
            [['category_id'], 'integer'],
            [['category_id'], 'exist', 'targetClass' => Category::class, 'targetAttribute' => 'id'],
            [['name'], 'string', 'max' => 120],
            [['name'], 'unique', 'targetAttribute' => ['category_id', 'name'], 'message' => Yii::t('ResourceBookingModule.base', 'A resource with this name already exists in the selected category.')],
            [['description'], 'string'],
            [['location'], 'string', 'max' => 255],
            [['capacity', 'sort_order'], 'integer', 'min' => 0],
            [['color'], 'string', 'max' => 20],
            [['color'], 'match', 'pattern' => '/^#[0-9a-fA-F]{6}$/'],
            [['is_active'], 'boolean'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'category_id' => Yii::t('ResourceBookingModule.base', 'Category'),
            'name' => Yii::t('ResourceBookingModule.base', 'Name'),
            'description' => Yii::t('ResourceBookingModule.base', 'Description'),
            'location' => Yii::t('ResourceBookingModule.base', 'Location'),
            'capacity' => Yii::t('ResourceBookingModule.base', 'Capacity'),
            'color' => Yii::t('ResourceBookingModule.base', 'Colour'),
            'sort_order' => Yii::t('ResourceBookingModule.base', 'Sort order'),
            'is_active' => Yii::t('ResourceBookingModule.base', 'Active'),
        ];
    }

    public function attributeHints(): array
    {
        return [
            'capacity' => Yii::t('ResourceBookingModule.base', 'Set to 0 if the capacity is not relevant.'),
            'color' => Yii::t('ResourceBookingModule.base', 'Optional colour for this resource. The category colour is used when empty.'),
        ];
    }

    public function beforeValidate(): bool
    {
        $this->name = is_scalar($this->name) ? trim((string)$this->name) : '';
        $this->color = is_scalar($this->color) ? $this->color : null;

        if ($this->color === '') {
            $this->color = null;
        }

        return parent::beforeValidate();
    }

    public function beforeSave($insert): bool
    {
        $this->name = trim((string)$this->name);
        $this->sort_order = (int)$this->sort_order;
        $this->capacity = (int)$this->capacity;

        return parent::beforeSave($insert);
    }

    public function getCategory(): ActiveQuery
    {
        return $this->hasOne(Category::class, ['id' => 'category_id']);
    }

    /**
     * @return ActiveQuery<Booking>
     */
    public function getBookings(): ActiveQuery
    {
        return $this->hasMany(Booking::class, ['resource_id' => 'id']);
    }

    public function getColor(): string
    {
        if (is_string($this->color) && $this->color !== '') {
            return $this->color;
        }

        return $this->category !== null ? $this->category->getColor() : '#435f6f';
    }

    public function getDisplayName(): string
    {
        return $this->name;
    }

    public static function findActive(): ActiveQuery
    {
        return static::find()
            ->alias('r')
            ->innerJoinWith('category c')
            ->where(['r.is_active' => 1, 'c.is_active' => 1])
            ->orderBy([
                'c.sort_order' => SORT_ASC,
                'c.name' => SORT_ASC,
                'r.sort_order' => SORT_ASC,
                'r.name' => SORT_ASC,
            ]);
    }
}
