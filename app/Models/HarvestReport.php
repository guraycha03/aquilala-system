<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HarvestReport extends Model
{
    protected $fillable = [
        'user_id', 'member_id', 'quotation_id', 'import_upload_id', 'crop_type', 'variety', 'variety_key', 'season',
        'land_area', 'actual_yield_metric_tons', 'actual_total_expenses', 'harvest_date'
    ];

    protected $casts = [
        'harvest_date'             => 'date',
        'land_area'                => 'decimal:2',
        'actual_yield_metric_tons' => 'decimal:2',
        'actual_total_expenses'    => 'decimal:2',
    ];

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The member record this report was filed against — the link used by
     * reports the officers imported from the association's paper records,
     * before the member had (or ever gets) a system account.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    /**
     * The archived upload this record was imported from, when it came from
     * a file. Manual reports and rows imported before the link existed are
     * null — nothing on the list is assumed to belong to a file it didn't
     * come from.
     */
    public function upload(): BelongsTo
    {
        return $this->belongsTo(ImportUpload::class, 'import_upload_id');
    }

    /**
     * Who filed this report, as it should read everywhere: the account name
     * when one exists, otherwise the member's name, otherwise a neutral
     * placeholder. Every list, chart and AI answer resolves the reporter
     * through here so member-linked history never renders as "Unknown".
     */
    public function reporterName(): string
    {
        return $this->farmer?->name
            ?: $this->member?->fullName()
            ?: 'Unknown farmer';
    }

    /**
     * Identity key for grouping/counting reporters across both links. A
     * member-linked report and a user-linked report for the same person are
     * distinct keys — they are distinct records in the system — but every
     * member-linked report collapses under its member id instead of under a
     * null user_id (which would merge all of them into one "reporter").
     */
    public function reporterKey(): string
    {
        if ($this->user_id !== null) {
            return 'u'.$this->user_id;
        }

        return 'm'.($this->member_id ?? 'x');
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }
}