<?php

use yii\db\Migration;

class m260101_000001_initial extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('resourcebooking_category', [
            'id' => $this->primaryKey(),
            'name' => $this->string(120)->notNull(),
            'description' => $this->text()->null(),
            'color' => $this->string(20)->notNull()->defaultValue('#435f6f'),
            'sort_order' => $this->integer()->notNull()->defaultValue(0),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),

            // Booking rules. NULL means "inherit the global default".
            'requires_approval' => $this->boolean()->null(),
            'min_duration_minutes' => $this->integer()->null(),
            'max_duration_minutes' => $this->integer()->null(),
            'max_advance_days' => $this->integer()->null(),
            'max_bookings_per_user_per_day' => $this->integer()->null(),
            'max_bookings_per_user_per_week' => $this->integer()->null(),
            'buffer_minutes' => $this->integer()->null(),
            'cancellation_deadline_hours' => $this->integer()->null(),
            'opening_time' => $this->string(5)->null(),
            'closing_time' => $this->string(5)->null(),
            'allowed_weekdays' => $this->string(20)->null(),

            'created_at' => $this->dateTime()->null(),
            'updated_at' => $this->dateTime()->null(),
            'created_by' => $this->integer()->null(),
            'updated_by' => $this->integer()->null(),
        ]);

        $this->createIndex('uq_rb_category_name', 'resourcebooking_category', 'name', true);

        $this->createTable('resourcebooking_resource', [
            'id' => $this->primaryKey(),
            'category_id' => $this->integer()->notNull(),
            'name' => $this->string(120)->notNull(),
            'description' => $this->text()->null(),
            'location' => $this->string(255)->null(),
            'capacity' => $this->integer()->notNull()->defaultValue(0),
            'color' => $this->string(20)->null(),
            'sort_order' => $this->integer()->notNull()->defaultValue(0),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
            'created_at' => $this->dateTime()->null(),
            'updated_at' => $this->dateTime()->null(),
            'created_by' => $this->integer()->null(),
            'updated_by' => $this->integer()->null(),
        ]);

        $this->createIndex('idx_rb_resource_category', 'resourcebooking_resource', 'category_id');
        $this->createIndex('idx_rb_resource_active', 'resourcebooking_resource', ['is_active', 'sort_order']);
        $this->createIndex('uq_rb_resource_category_name', 'resourcebooking_resource', ['category_id', 'name'], true);

        $this->addForeignKey(
            'fk_rb_resource_category',
            'resourcebooking_resource',
            'category_id',
            'resourcebooking_category',
            'id',
            'RESTRICT',
            'CASCADE',
        );

        $this->createTable('resourcebooking_booking', [
            'id' => $this->primaryKey(),
            'resource_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->notNull(),
            'title' => $this->string(255)->notNull(),
            'description' => $this->text()->null(),
            'start_time' => $this->dateTime()->notNull(),
            'end_time' => $this->dateTime()->notNull(),
            'status' => $this->string(20)->notNull()->defaultValue('confirmed'),
            'approved_by' => $this->integer()->null(),
            'approved_at' => $this->dateTime()->null(),
            'cancelled_at' => $this->dateTime()->null(),
            'cancellation_reason' => $this->string(255)->null(),
            'created_at' => $this->dateTime()->null(),
            'updated_at' => $this->dateTime()->null(),
            'created_by' => $this->integer()->null(),
            'updated_by' => $this->integer()->null(),
        ]);

        $this->createIndex('idx_rb_booking_resource_start', 'resourcebooking_booking', ['resource_id', 'start_time']);
        $this->createIndex('idx_rb_booking_start', 'resourcebooking_booking', 'start_time');
        $this->createIndex('idx_rb_booking_end', 'resourcebooking_booking', 'end_time');
        $this->createIndex('idx_rb_booking_user', 'resourcebooking_booking', 'user_id');
        $this->createIndex('idx_rb_booking_status', 'resourcebooking_booking', 'status');

        $this->addForeignKey(
            'fk_rb_booking_resource',
            'resourcebooking_booking',
            'resource_id',
            'resourcebooking_resource',
            'id',
            'RESTRICT',
            'CASCADE',
        );
    }

    public function safeDown(): void
    {
        $this->dropForeignKey('fk_rb_booking_resource', 'resourcebooking_booking');
        $this->dropTable('resourcebooking_booking');

        $this->dropForeignKey('fk_rb_resource_category', 'resourcebooking_resource');
        $this->dropTable('resourcebooking_resource');

        $this->dropTable('resourcebooking_category');
    }
}
