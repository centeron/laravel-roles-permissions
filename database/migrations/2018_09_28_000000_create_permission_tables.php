<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePermissionTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tableNames = config('permissions.table_names');

        Schema::create($tableNames['auth_items'], function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->index();
            $table->smallInteger('type')->index();
            $table->string('rule')->nullable();
            $table->binary('data')->nullable();
            $table->unsignedInteger('base_auth_id')->nullable()->index();
            $table->timestamps();
        });

        Schema::create($tableNames['auth_item_childs'], function (Blueprint $table) use ($tableNames) {
            $table->unsignedInteger('parent_id');
            $table->unsignedInteger('child_id');
            $table->primary(['parent_id', 'child_id'], 'auth_item_childs_primary');
            $table->foreign('parent_id')->references('id')->on($tableNames['auth_items'])->onDelete('cascade');
            $table->foreign('child_id')->references('id')->on($tableNames['auth_items'])->onDelete('cascade');
        });

        Schema::create($tableNames['auth_assignments'], function (Blueprint $table) use ($tableNames) {
            $table->unsignedInteger('auth_item_id');
            $table->string('model');
            $table->unsignedInteger('model_id');
            $table->timestamps();
            $table->primary(['auth_item_id', 'model', 'model_id'], 'auth_assignments_primary');
            $table->foreign('auth_item_id')->references('id')->on($tableNames['auth_items'])->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tableNames = config('permissions.table_names');

        Schema::drop($tableNames['auth_assignments']);
        Schema::drop($tableNames['auth_item_childs']);
        Schema::drop($tableNames['auth_items']);
    }
}
