<?php
namespace Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Issuance extends Model
{
    use HasFactory;
    
    public $timestamps = false;
    
    protected $fillable = [
        'reader_id',
        'book_id',
        'issue_date',
        'return_date',
        'due_date'
    ];
    
    protected $casts = [
        'issue_date' => 'date',
        'return_date' => 'date',
        'due_date' => 'date'
    ];
    
    public function reader()
    {
        return $this->belongsTo(Reader::class);
    }
    
    public function book()
    {
        return $this->belongsTo(Book::class);
    }
    
    public function isOverdue(): bool
    {
        return is_null($this->return_date) && $this->due_date < now();
    }
}