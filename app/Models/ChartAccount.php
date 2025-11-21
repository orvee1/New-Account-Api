<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class ChartAccount extends Model
{
    // সব ফিল্ড mass assignable (তুমি চাইলে fillable ব্যবহার করতে পারো)
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'depth'     => 'integer',
        'sort_order'=> 'integer',
    ];

    /* ---------------- Relations ---------------- */

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ChartAccount::class, 'parent_id')
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    /**
     * Recursive children (children_recursive)
     */
    public function childrenRecursive(): HasMany
    {
        return $this->children()->with('childrenRecursive');
    }

    /**
     * Recursive parent (প্রয়োজনে)
     */
    public function parentRecursive(): BelongsTo
    {
        return $this->parent()->with('parentRecursive');
    }

    /* ---------------- Scopes ---------------- */

    public function scopeRoot($q)
    {
        return $q->whereNull('parent_id');
    }

    public function scopeGroups($q)
    {
        return $q->where('type', 'group');
    }

    public function scopeLedgers($q)
    {
        return $q->where('type', 'ledger');
    }

    /* ---------------- Accessors ---------------- */

    public function getIsGroupAttribute(): bool
    {
        return $this->type === 'group';
    }

    public function getIsLedgerAttribute(): bool
    {
        return $this->type === 'ledger';
    }

    /* ---------------- Helpers ---------------- */

    /**
     * same parent & company এর মধ্যে পরের sort_order বের করে
     */
    public function nextSortOrder(): int
    {
        $query = static::query()
            ->where('company_id', $this->company_id)
            ->where('parent_id', $this->parent_id);

        $max = $query->max('sort_order');

        return is_null($max) ? 0 : $max + 1;
    }

    /**
     * parent code এর উপর ভিত্তি করে next code generate
     * উদাহরণ:
     *  - root → "1", "2", ...
     *  - parent "1.1" → "1.1.1", "1.1.2", ...
     */
    public function generateCode(): string
    {
        // parent থাকলে
        if ($this->parent) {
            $base = $this->parent->code ?: (string)$this->parent->id;

            $siblings = static::query()
                ->where('company_id', $this->company_id)
                ->where('parent_id', $this->parent_id)
                ->whereNotNull('code')
                ->pluck('code')
                ->toArray();

            $maxSuffix = 0;

            foreach ($siblings as $code) {
                $parts = explode('.', $code);
                $last  = (int)end($parts);
                if ($last > $maxSuffix) {
                    $maxSuffix = $last;
                }
            }

            $next = $maxSuffix + 1;

            return $base . '.' . $next;
        }

        // root হলে
        $rootCodes = static::query()
            ->where('company_id', $this->company_id)
            ->whereNull('parent_id')
            ->whereNotNull('code')
            ->pluck('code')
            ->toArray();

        $maxRoot = 0;

        foreach ($rootCodes as $code) {
            // ধরে নিচ্ছি root code simple number যেমন "1","2"
            $val = (int)$code;
            if ($val > $maxRoot) {
                $maxRoot = $val;
            }
        }

        $nextRoot = $maxRoot + 1;

        return (string)$nextRoot;
    }

    /* ---------------- Booted: path, depth, audit ---------------- */

    protected static function booted(): void
    {
        // creating এর সময় basic fields
        static::creating(function (self $model) {
            // NOT NULL path -> প্রথমে temporary কিছু দিচ্ছি
            if (empty($model->path)) {
                $model->path = '/';  // পরে created event-এ ঠিক করা হবে
            }

            if (is_null($model->depth)) {
                $model->depth = 0;
            }

            // sort_order auto
            if (is_null($model->sort_order)) {
                $temp = new static([
                    'company_id' => $model->company_id,
                    'parent_id'  => $model->parent_id,
                ]);
                $model->sort_order = $temp->nextSortOrder();
            }

            // created_by / updated_by
            if (Auth::check()) {
                $uid = Auth::id();

                if (is_null($model->created_by)) {
                    $model->created_by = $uid;
                }
                if (is_null($model->updated_by)) {
                    $model->updated_by = $uid;
                }
            }

            // code auto generate যদি null থাকে
            if (empty($model->code)) {
                // parent relationship eager না থাকলে load করি
                if ($model->parent_id && !$model->relationLoaded('parent')) {
                    $model->load('parent');
                }
                $model->code = $model->generateCode();
            }
        });

        // updating → শুধু updated_by আপডেট
        static::updating(function (self $model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });

        // created → এখন সঠিক path + depth সেট করব
        static::created(function (self $model) {
            $parentPath = null;

            if ($model->parent) {
                $parentPath = trim($model->parent->path ?? '', '/');  // "2/3"
            }

            if ($parentPath) {
                $path = '/' . $parentPath . '/' . $model->id; // "/2/3/101"
            } else {
                $path = '/' . $model->id; // root → "/2"
            }

            $model->path  = $path;
            $model->depth = substr_count($path, '/') - 1;

            $model->saveQuietly();
        });
    }
}
