<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGroupGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('group_groups', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('group_id')->unsigned();

            $table->foreign('group_id')
                ->references('id')
                ->on('groups')
                ->onDelete('cascade');

            $table->integer('linked_group_id')->unsigned();

            $table->foreign('linked_group_id')
                ->references('id')
                ->on('groups')
                ->onDelete('cascade');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('group_groups');
    }
}
