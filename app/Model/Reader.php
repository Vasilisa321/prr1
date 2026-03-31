<?php
namespace Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reader extends Model
{
    use HasFactory;
    
    public $timestamps = false;
    
    protected $fillable = [
        'card_number',
        'full_name',
        'address',
        'phone',
        'created_at'
    ];
    
    protected $casts = [
        'created_at' => 'datetime'
    ];
    
    public function issuances()
    {
        return $this->hasMany(Issuance::class);
    }
    
    public function activeIssuances()
    {
        return $this->hasMany(Issuance::class)->whereNull('return_date');
    }
    
    public function getDebtBooks()
    {
        return $this->hasMany(Issuance::class)
            ->whereNull('return_date')
            ->where('due_date', '<', date('Y-m-d'))
            ->get();
    }
}