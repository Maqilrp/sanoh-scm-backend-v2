<?php

namespace App\Models\PerformanceReport;

use App\Models\Users\BusinessPartner;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;

class PerformanceReport extends Model
{
    use HasFactory, Notifiable;

    protected $connection = 'mysql';

    // Table name
    protected $table = 'performance_report';

    protected $primaryKey = 'performance_no';

    public $timestamps = false;

    // Column
    protected $fillable = [
        'performance_no',
        'bp_code',
        'date',
        'file',
        'upload_at',
    ];

    // Relationship
    public function partner(): BelongsTo
    {
        return $this->belongsTo(BusinessPartner::class, 'bp_code', 'bp_code');
    }
}
