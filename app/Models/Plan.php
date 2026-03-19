<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'fund_id',
        'plan_category_id',
        'code',
        'name',
        'description',
        'status',
        'is_default',
        'investment_objective',
        'target_audience',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function fund()
    {
        return $this->belongsTo(Fund::class);
    }

    public function category()
    {
        return $this->belongsTo(PlanCategory::class, 'plan_category_id');
    }

    public function statusHistory()
    {
        return $this->hasMany(PlanStatusHistory::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function rules()
    {
        return $this->hasMany(PlanRule::class);
    }

    public function activeRule()
    {
        return $this->hasOne(PlanRule::class)->where('is_active', true)->latestOfMany();
    }
    public function purchaseRequests()
    {
        return $this->hasMany(PurchaseRequest::class);
    }
    public function investmentTransactions()
    {
        return $this->hasMany(InvestmentTransaction::class);
    }

    public function unitLots()
    {
        return $this->hasMany(UnitLot::class);
    }
    public function navRecords()
    {
        return $this->hasMany(NavRecord::class);
    }

    public function latestPublishedNav()
    {
        return $this->hasOne(NavRecord::class)
            ->where('status', 'published')
            ->latestOfMany('valuation_date');
    }
    public function cutoffTimeRules()
    {
        return $this->hasMany(CutoffTimeRule::class);
    }
}