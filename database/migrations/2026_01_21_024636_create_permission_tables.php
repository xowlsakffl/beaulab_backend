<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $teams = config('permission.teams');
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';

        throw_if(empty($tableNames), Exception::class, 'Error: config/permission.php not loaded. Run [php artisan config:clear] and try again.');
        throw_if($teams && empty($columnNames['team_foreign_key'] ?? null), Exception::class, 'Error: team_foreign_key on config/permission.php not loaded. Run [php artisan config:clear] and try again.');

        /**
         * permissions
         * 개별 권한 정의 테이블
         */
        Schema::create($tableNames['permissions'], static function (Blueprint $table) {
            // $table->engine('InnoDB');
            $table->bigIncrements('id')->comment('권한 ID');
            $table->string('name')->comment('권한 식별자 (예: admin.user.create)');
            $table->string('guard_name')->comment('적용 guard 이름 (예: admin)');
            $table->timestamps();

            $table->unique(['name', 'guard_name']);
        });
        DB::statement("ALTER TABLE {$tableNames['permissions']} COMMENT = '권한(Permission) 정의 테이블'");

        /**
         * roles
         * 권한 묶음(역할) 테이블
         */
        Schema::create($tableNames['roles'], static function (Blueprint $table) use ($teams, $columnNames) {
            // $table->engine('InnoDB');
            $table->bigIncrements('id')->comment('역할 ID');
            if ($teams || config('permission.testing')) { // permission.testing is a fix for sqlite testing
                $table->unsignedBigInteger($columnNames['team_foreign_key'])->nullable()->comment('팀/테넌트 ID');;
                $table->index($columnNames['team_foreign_key'], 'roles_team_foreign_key_index');
            }
            $table->string('name')->comment('역할 이름 (예: super_admin)');
            $table->string('guard_name')->comment('적용 guard 이름 (예: admin)');
            $table->timestamps();
            if ($teams || config('permission.testing')) {
                $table->unique([$columnNames['team_foreign_key'], 'name', 'guard_name']);
            } else {
                $table->unique(['name', 'guard_name']);
            }
        });
        DB::statement("ALTER TABLE {$tableNames['roles']} COMMENT = '권한 역할(Role) 정의 테이블'");

        /**
         * model_has_permissions
         * 모델 ↔ 권한 직접 매핑
         */
        Schema::create($tableNames['model_has_permissions'], static function (Blueprint $table) use ($tableNames, $columnNames, $pivotPermission, $teams) {
            $table->unsignedBigInteger($pivotPermission)->comment('권한 ID');

            $table->string('model_type')->comment('모델 클래스명');
            $table->unsignedBigInteger($columnNames['model_morph_key'])->comment('모델 ID');
            $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_permissions_model_id_model_type_index');

            $table->foreign($pivotPermission)
                ->references('id') // permission id
                ->on($tableNames['permissions'])
                ->onDelete('cascade');
            if ($teams) {
                $table->unsignedBigInteger($columnNames['team_foreign_key'])->comment('팀/테넌트 ID');
                $table->index($columnNames['team_foreign_key'], 'model_has_permissions_team_foreign_key_index');

                $table->primary([$columnNames['team_foreign_key'], $pivotPermission, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_permissions_permission_model_type_primary');
            } else {
                $table->primary([$pivotPermission, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_permissions_permission_model_type_primary');
            }

        });
        DB::statement("ALTER TABLE {$tableNames['model_has_permissions']} COMMENT = '모델에 직접 부여된 권한 매핑 테이블'");

        /**
         * model_has_roles
         * 모델 ↔ 역할 매핑
         */
        Schema::create($tableNames['model_has_roles'], static function (Blueprint $table) use ($tableNames, $columnNames, $pivotRole, $teams) {
            $table->unsignedBigInteger($pivotRole)->comment('역할 ID');

            $table->string('model_type')->comment('모델 클래스명');
            $table->unsignedBigInteger($columnNames['model_morph_key'])->comment('모델 ID');
            $table->index(
                [$columnNames['model_morph_key'], 'model_type'],
                'model_has_roles_model_id_model_type_index'
            );

            $table->foreign($pivotRole)
                ->references('id')
                ->on($tableNames['roles'])
                ->onDelete('cascade');

            if ($teams) {
                $table->unsignedBigInteger($columnNames['team_foreign_key'])->comment('팀/테넌트 ID');
                $table->index($columnNames['team_foreign_key'], 'model_has_roles_team_foreign_key_index');

                $table->primary([
                    $columnNames['team_foreign_key'],
                    $pivotRole,
                    $columnNames['model_morph_key'],
                    'model_type'
                ]);
            } else {
                $table->primary([
                    $pivotRole,
                    $columnNames['model_morph_key'],
                    'model_type'
                ]);
            }
        });
        DB::statement("ALTER TABLE {$tableNames['model_has_roles']} COMMENT = '모델과 역할(Role) 매핑 테이블'");

        /**
         * role_has_permissions
         * 역할 ↔ 권한 매핑
         */
        Schema::create($tableNames['role_has_permissions'], static function (Blueprint $table) use ($tableNames, $pivotRole, $pivotPermission) {
            $table->unsignedBigInteger($pivotPermission)->comment('권한 ID');
            $table->unsignedBigInteger($pivotRole)->comment('역할 ID');

            $table->foreign($pivotPermission)
                ->references('id')
                ->on($tableNames['permissions'])
                ->onDelete('cascade');

            $table->foreign($pivotRole)
                ->references('id')
                ->on($tableNames['roles'])
                ->onDelete('cascade');

            $table->primary([$pivotPermission, $pivotRole]);
        });
        DB::statement("ALTER TABLE {$tableNames['role_has_permissions']} COMMENT = '역할별 권한 구성 테이블'");


        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names');

        throw_if(empty($tableNames), Exception::class, 'Error: config/permission.php not found and defaults could not be merged. Please publish the package configuration before proceeding, or drop the tables manually.');

        Schema::drop($tableNames['role_has_permissions']);
        Schema::drop($tableNames['model_has_roles']);
        Schema::drop($tableNames['model_has_permissions']);
        Schema::drop($tableNames['roles']);
        Schema::drop($tableNames['permissions']);
    }
};
