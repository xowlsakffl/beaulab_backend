<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('categories')) {
            return;
        }

        DB::transaction(function (): void {
            $now = now();

            $root = DB::table('categories')
                ->select(['id'])
                ->whereIn('domain', ['HOSPITAL_COMMUNITY', 'TALK'])
                ->where('code', 'TALK_ROOT')
                ->orderBy('id')
                ->first();

            if ($root !== null) {
                $children = DB::table('categories')
                    ->select(['id', 'name'])
                    ->where('parent_id', $root->id)
                    ->orderBy('id')
                    ->get();

                foreach ($children as $child) {
                    DB::table('categories')
                        ->where('id', $child->id)
                        ->update([
                            'domain' => 'TALK',
                            'parent_id' => null,
                            'depth' => 1,
                            'full_path' => $child->name,
                            'updated_at' => $now,
                        ]);
                }

                DB::table('categories')
                    ->where('id', $root->id)
                    ->delete();
            }

            DB::table('categories')
                ->where('domain', 'HOSPITAL_COMMUNITY')
                ->update([
                    'domain' => 'TALK',
                    'updated_at' => $now,
                ]);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('categories')) {
            return;
        }

        DB::transaction(function (): void {
            $now = now();

            $root = DB::table('categories')
                ->select(['id'])
                ->where('domain', 'HOSPITAL_COMMUNITY')
                ->where('code', 'TALK_ROOT')
                ->orderBy('id')
                ->first();

            $rootId = $root?->id;

            if (! is_numeric($rootId)) {
                $rootId = DB::table('categories')->insertGetId([
                    'domain' => 'HOSPITAL_COMMUNITY',
                    'parent_id' => null,
                    'depth' => 1,
                    'name' => 'Talk',
                    'code' => 'TALK_ROOT',
                    'full_path' => 'Talk',
                    'sort_order' => 1,
                    'status' => 'ACTIVE',
                    'is_menu_visible' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $talkCategories = DB::table('categories')
                ->select(['id', 'name'])
                ->where('domain', 'TALK')
                ->whereNull('parent_id')
                ->where('code', '!=', 'TALK_ROOT')
                ->where('code', 'like', 'TALK_%')
                ->orderBy('id')
                ->get();

            foreach ($talkCategories as $category) {
                DB::table('categories')
                    ->where('id', $category->id)
                    ->update([
                        'domain' => 'HOSPITAL_COMMUNITY',
                        'parent_id' => (int) $rootId,
                        'depth' => 2,
                        'full_path' => 'Talk > '.$category->name,
                        'updated_at' => $now,
                    ]);
            }
        });
    }
};
