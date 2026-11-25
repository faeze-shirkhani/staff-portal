<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'can_assign_tasks')) {
            Schema::table('users', function (Blueprint $t) {
                $t->boolean('can_assign_tasks')->default(false);
            });
        }

        Schema::table('tasks', function (Blueprint $t) {
            if (!Schema::hasColumn('tasks', 'group_id')) {
                $t->uuid('group_id')->nullable()->index();
            }
            if (!Schema::hasColumn('tasks', 'created_by')) {
                $t->unsignedBigInteger('created_by')->nullable()->index();
            }
        });

        if (!Schema::hasTable('task_flags')) {
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
        } else {
            Schema::table('task_flags', function (Blueprint $t) {
                if (!Schema::hasColumn('task_flags', 'flagged_at')) $t->timestamp('flagged_at')->nullable();
                if (!Schema::hasColumn('task_flags', 'updated_at')) $t->timestamp('updated_at')->nullable();
            });
            DB::table('task_flags')->whereNull('flagged_at')->update(['flagged_at' => DB::raw('created_at')]);
        }

        if (!Schema::hasTable('task_objections')) {
            Schema::create('task_objections', function (Blueprint $t) {
                $t->id();
                $t->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
                $t->foreignId('task_flag_id')->nullable()->constrained('task_flags')->cascadeOnDelete();
                $t->unsignedBigInteger('user_id');
                $t->text('body');
                $t->text('reply')->nullable();
                $t->unsignedBigInteger('replied_by')->nullable();
                $t->timestamp('replied_at')->nullable();
                $t->timestamps();
            });
        } else {
            Schema::table('task_objections', function (Blueprint $t) {
                if (!Schema::hasColumn('task_objections', 'task_flag_id')) $t->unsignedBigInteger('task_flag_id')->nullable()->index();
                if (!Schema::hasColumn('task_objections', 'updated_at')) $t->timestamp('updated_at')->nullable();
            });
        }
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