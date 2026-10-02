<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $palette = [
        '#d0123c',
        '#2563eb',
        '#059669',
        '#d97706',
        '#7c3aed',
        '#0891b2',
        '#db2777',
        '#4f46e5',
    ];

    public function up(): void
    {
        Schema::table('student_groups', function (Blueprint $table) {
            $table->string('color', 7)->default('#64748b');
        });

        $groups = DB::table('student_groups')->orderBy('id')->pluck('id');

        foreach ($groups as $index => $id) {
            DB::table('student_groups')->where('id', $id)->update([
                'color' => $this->palette[$index % count($this->palette)],
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('student_groups', function (Blueprint $table) {
            $table->dropColumn('color');
        });
    }
};
