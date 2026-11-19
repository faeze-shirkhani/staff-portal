<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // کسی که اجازه‌ی نوشتن تسک دارد (از دیتابیس تنظیم می‌شود)
        if (!Schema::hasColumn('users', 'can_assign_tasks')) {
            Schema::table('users', function (Blueprint $t) {
                $t->boolean('can_assign_tasks')->default(false);
            });
        }

        Schema::table('tasks', function (Blueprint $t) {
            // تسک بخش یا همه: همه‌ی ردیف‌های یک گروه یک شناسه دارند
            if (!Schema::hasColumn('tasks', 'group_id')) {
                $t->uuid('group_id')->nullable()->index();
            }
            // کسی که تسک را نوشته (اگر از قبل ستونی برای این دارید، این خط اجرا نمی‌شود)
            if (!Schema::hasColumn('tasks', 'created_by')) {
                $t->unsignedBigInteger('created_by')->nullable()->index();
            }
        });

        Schema::create('task_flags', function (Blueprint $t) {
            $t->id();
            $t->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $t->unsignedBigInteger('flagged_by');
            $t->text('note')->nullable();
            $t->timestamp('flagged_at')->useCurrent();
            $t->timestamp('resolved_at')->nullable();
            $t->unsignedBigInteger('resolved_by')->nullable();
            $t->timestamps();
        });

        Schema::create('task_objections', function (Blueprint $t) {
            $t->id();
            $t->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $t->foreignId('task_flag_id')->nullable()->constrained('task_flags')->cascadeOnDelete();
            $t->unsignedBigInteger('user_id');      // کارمند معترض
            $t->text('body');
            $t->text('reply')->nullable();           // پاسخ نویسنده‌ی تسک
            $t->unsignedBigInteger('replied_by')->nullable();
            $t->timestamp('replied_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_objections');
        Schema::dropIfExists('task_flags');
        Schema::table('tasks', function (Blueprint $t) {
            if (Schema::hasColumn('tasks', 'group_id')) $t->dropColumn('group_id');
        });
        Schema::table('users', function (Blueprint $t) {
            if (Schema::hasColumn('users', 'can_assign_tasks')) $t->dropColumn('can_assign_tasks');
        });
    }
};