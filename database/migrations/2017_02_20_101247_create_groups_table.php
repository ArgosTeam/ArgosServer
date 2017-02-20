<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            
            $table->increments('id');
            $table->tinyInteger('public');
            $table->string('name');
            $table->string('description')
                ->nullable();
            $table->string('address')
                ->nullable();

            $table->integer('location_id')
                ->unsigned()
                ->nullable();

            $table->foreign('location_id')
                ->references('id')
                ->on('locations')
                ->onDelete('cascade');

            
            $table->integer('profile_pic_id')
                ->unsigned()
                ->nullable();

            $table->foreign('profile_pic_id')
                ->references('id')
                ->on('photos')
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
        Schema::dropIfExists('groups');
    }
}
