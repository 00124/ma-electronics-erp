<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ChartOfAccount extends Model
{
    protected $table = 'chart_of_accounts';
    protected $fillable = ['company_id','account_code','account_name','account_type','parent_id','description','status'];

    public function parent() { return $this->belongsTo(ChartOfAccount::class, 'parent_id'); }
    public function children() { return $this->hasMany(ChartOfAccount::class, 'parent_id'); }
    public function journalLines() { return $this->hasMany(JournalEntryLine::class, 'account_id'); }
}
