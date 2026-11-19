<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->boolean('can_assign_tasks')->default(false);   // اجازه‌ی نوشتن تسک برای دیگران
        });
        Schema::table('tasks', function (Blueprint $t) {
            $t->string('group_id', 36)->nullable()->index();    // تسکی که برای یک بخش یا همه نوشته شده
        });
        Schema::create('task_flags', function (Blueprint $t) {  // تذکر (قرمزشدن)
            $t->id();
            $t->unsignedBigInteger('task_id')->index();
            $t->unsignedBigInteger('flagged_by');
            $t->text('note');
            $t->timestamp('created_at')->nullable();
            $t->timestamp('resolved_at')->nullable();           // برداشتن تذکر؛ پاک نمی‌شود
            $t->unsignedBigInteger('resolved_by')->nullable();
        });
        Schema::create('task_objections', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('task_id')->index();
            $t->unsignedBigInteger('user_id');
            $t->text('body');
            $t->timestamp('created_at')->nullable();
            $t->text('reply')->nullable();
            $t->unsignedBigInteger('replied_by')->nullable();
            $t->timestamp('replied_at')->nullable();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('task_objections');
        Schema::dropIfExists('task_flags');
        Schema::table('tasks', fn (Blueprint $t) => $t->dropColumn('group_id'));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('can_assign_tasks'));
    }
};
