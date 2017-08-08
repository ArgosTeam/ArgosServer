<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('firstname')
                ->nullable();
            $table->string('lastname')
                ->nullable();
            $table->enum('sex', ['male', 'female', 'neutral']);
            $table->string('email')
                ->nullable();
            $table->string('phone');
            $table->string('nickname');
            $table->string('password');
            $table->integer('profile_pic_id')
                ->unsigned()
                ->nullable();

            $table->foreign('profile_pic_id')
                ->references('id')
                ->on('photos')
                ->onDelete('cascade');

            $table->string('cursus')
                ->nullable();

            $table->date('dob');
            
            $table->string('remember_token')
                ->nullable();
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
        Schema::dropIfExists('users');
    }
}
