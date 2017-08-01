<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Models\Mood;

class CreateMoodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $moods = ['mood_0', 'mood_1', 'mood_2', 'mood_3', 'mood_4', 'mood_5'];
        
        Schema::create('moods', function (Blueprint $table) {
            $table->increments('id');
            
            $table->string('name');

            $table->timestamps();
        });

        foreach ($moods as $mood) {
            Mood::create([
                'name' => $mood
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
