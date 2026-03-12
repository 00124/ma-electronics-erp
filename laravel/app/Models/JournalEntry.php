<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    protected $table = 'journal_entries';
    protected $fillable = ['company_id','entry_number','entry_date','reference','description','status','created_by'];
    protected $casts = ['entry_date' => 'date'];

    public function lines() { return $this->hasMany(JournalEntryLine::class); }
}
