namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Kalnoy\Nestedset\NodeTrait;

class Category extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */

    use NodeTrait;

    protected $fillable = ['name', 'count'];

    public function event() {
        return $this->belongsTo(Category::class);
    }

    public function users() {
        return $this->belongsToMany(Category::class)
            ->withPivot('count')
            ->withTimestamps();
    }
}
