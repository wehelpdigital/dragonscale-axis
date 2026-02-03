<?php

namespace App\Models;

class EcomSalesReport extends BaseModel
{
    protected $table = 'ecom_sales_reports';

    protected $fillable = [
        'usersId',
        'reportName',
        'reportType',
        'dateFrom',
        'dateTo',
        'filters',
        'reportData',
        'groupBy',
        'notes',
        'deleteStatus'
    ];

    protected $casts = [
        'usersId' => 'integer',
        'dateFrom' => 'date',
        'dateTo' => 'date',
        'filters' => 'array',
        'reportData' => 'array',
        'deleteStatus' => 'integer'
    ];

    /**
     * Report type labels
     */
    const REPORT_TYPES = [
        'overview' => 'Sales Overview',
        'by_store' => 'Sales by Store',
        'by_product' => 'Sales by Product',
        'trend' => 'Sales Trend',
        'discount' => 'Discount Analysis',
        'commission' => 'Commission Report'
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'usersId', 'id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('usersId', $userId);
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('reportType', $type);
    }

    /**
     * Accessors
     */
    public function getReportTypeLabelAttribute()
    {
        return self::REPORT_TYPES[$this->reportType] ?? $this->reportType;
    }

    public function getDateRangeAttribute()
    {
        if ($this->dateFrom && $this->dateTo) {
            return $this->dateFrom->format('M j, Y') . ' - ' . $this->dateTo->format('M j, Y');
        } elseif ($this->dateFrom) {
            return 'From ' . $this->dateFrom->format('M j, Y');
        } elseif ($this->dateTo) {
            return 'Until ' . $this->dateTo->format('M j, Y');
        }
        return 'All Time';
    }

    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at ? $this->created_at->format('M j, Y g:i A') : null;
    }
}
