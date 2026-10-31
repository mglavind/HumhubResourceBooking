<?php

use yii\db\Migration;

class uninstall extends Migration
{
    public function safeUp(): void
    {
        $this->dropTable('resourcebooking_booking');
        $this->dropTable('resourcebooking_resource');
        $this->dropTable('resourcebooking_category');
    }

    public function safeDown(): bool
    {
        echo "uninstall does not support migration down.\n";

        return false;
    }
}
