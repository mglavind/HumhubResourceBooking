<?php

use yii\db\Migration;

class m261031_120000_add_reminder_sent_at extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn(
            'resourcebooking_booking',
            'reminder_sent_at',
            $this->dateTime()->null()->after('cancellation_reason'),
        );
    }

    public function safeDown(): void
    {
        $this->dropColumn('resourcebooking_booking', 'reminder_sent_at');
    }
}
