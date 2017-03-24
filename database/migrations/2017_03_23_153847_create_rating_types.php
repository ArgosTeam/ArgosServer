<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Models\RatingType;

class CreateRatingTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        
        $types = ['hot', 'chill', 'cute', 'trip'];
        
        Schema::create('rating_types', function (BluePrint $table) {
            $table->engine = 'InnoDB';

            $table->increments('id');
            $table->enum('name', $types);
            $table->timestamps();
        });

        foreach ($types as $type) {
            RatingType::create([
                'name' => $type
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
        Schema::dropIfExists('rating_types');
    }
}
