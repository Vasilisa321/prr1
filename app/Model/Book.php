<?php
namespace Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;
    
    public $timestamps = false;
    
    protected $fillable = [
        'title',
        'author',
        'year',
        'price',
        'is_new_edition',
        'annotation',
        'total_copies',
        'available_copies'
    ];
    
    protected $casts = [
        'is_new_edition' => 'boolean',
        'year' => 'integer',
        'price' => 'float',

    ];
    
    public function issuances()
    {
        return $this->hasMany(Issuance::class);
    }
    
    public function isAvailable(): bool
    {
        return $this->available_copies > 0;
    }
    
    public function decrementCopies(): void
    {
        $this->available_copies--;
        $this->save();
    }
    
    public function incrementCopies(): void
    {
        $this->available_copies++;
        $this->save();
    }
}