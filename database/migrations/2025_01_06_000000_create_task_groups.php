<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('task_groups')) return; // قبلاً ساخته شده
        // تسکِ «همه» یا «یک بخش» یک‌بار تعریف می‌شود و برای هر کسی که بعداً وارد آن گروه شود هم ساخته می‌شود
        Schema::create('task_groups', function (Blueprint $t) {
            $t->string('id', 36)->primary();                 // همان tasks.group_id
            $t->unsignedBigInteger('created_by');
            $t->string('scope', 10);                         // all | section
            $t->string('section', 20)->nullable();
            $t->string('title', 200);
            $t->text('note')->nullable();
            $t->timestamp('deleted_at')->nullable();
            $t->timestamps();
        });

        // تسک‌های گروهیِ قبلی: از روی اعضا حدس می‌زنیم برای یک بخش بوده یا همه
        foreach (DB::table('tasks')->whereNotNull('group_id')->distinct()->pluck('group_id') as $gid) {
            $rows = DB::table('tasks as t')->join('users as u', 'u.id', '=', 't.user_id')
                ->where('t.group_id', $gid)->get(['t.*', 'u.section as usec']);
            $first = $rows->first();
            $secs = $rows->pluck('usec')->unique()->values();
            $scope = ($secs->count() === 1 && $secs[0]) ? 'section' : 'all';
            DB::table('task_groups')->insert([
                'id' => $gid, 'created_by' => $first->created_by, 'scope' => $scope,
                'section' => $scope === 'section' ? $secs[0] : null, 'title' => $first->title, 'note' => $first->note,
                'deleted_at' => $rows->every(fn ($r) => $r->deleted_at) ? now() : null,
                'created_at' => now(), 'updated_at' => now()]);
        }
    }
    public function down(): void { Schema::dropIfExists('task_groups'); }
};