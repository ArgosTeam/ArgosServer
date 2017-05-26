<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGroupStoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('group_story', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('story_id')
                ->unsigned();

            $table->foreign('story_id')
                ->references('id')
                ->on('stories')
                ->onDelete('cascade');
            
            $table->integer('group_id')
                ->unsigned();

            $table->foreign('group_id')
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
        Schema::dropIfExists('group_story');
    }
}
