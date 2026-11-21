<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentChannel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get theme color styling and icon metadata based on channel name or slug.
     */
    public function getThemeColorAttribute(): array
    {
        $slug = strtolower($this->slug ?? '');
        $name = strtolower($this->name ?? '');

        if (str_contains($slug, 'cash') || str_contains($name, 'cash')) {
            return [
                'bg' => 'bg-emerald-50 dark:bg-emerald-950/40',
                'border' => 'border-emerald-200 dark:border-emerald-800/50',
                'text' => 'text-emerald-700 dark:text-emerald-300',
                'badge' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300 border-emerald-300 dark:border-emerald-700',
                'icon_bg' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
                'type' => 'cash',
            ];
        }

        if (str_contains($slug, 'bank') || str_contains($name, 'bank') || str_contains($slug, 'zigama') || str_contains($name, 'zigama')) {
            return [
                'bg' => 'bg-blue-50 dark:bg-blue-950/40',
                'border' => 'border-blue-200 dark:border-blue-800/50',
                'text' => 'text-blue-700 dark:text-blue-300',
                'badge' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/60 dark:text-blue-300 border-blue-300 dark:border-blue-700',
                'icon_bg' => 'bg-blue-500/10 text-blue-600 dark:text-blue-400',
                'type' => 'bank',
            ];
        }

        if (str_contains($slug, 'momo') || str_contains($slug, 'mobile') || str_contains($name, 'mobile') || str_contains($name, 'mtn')) {
            return [
                'bg' => 'bg-amber-50 dark:bg-amber-950/40',
                'border' => 'border-amber-200 dark:border-amber-800/50',
                'text' => 'text-amber-700 dark:text-amber-300',
                'badge' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300 border-amber-300 dark:border-amber-700',
                'icon_bg' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
                'type' => 'mobile_money',
            ];
        }

        if (str_contains($slug, 'airtel') || str_contains($name, 'airtel')) {
            return [
                'bg' => 'bg-rose-50 dark:bg-rose-950/40',
                'border' => 'border-rose-200 dark:border-rose-800/50',
                'text' => 'text-rose-700 dark:text-rose-300',
                'badge' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/60 dark:text-rose-300 border-rose-300 dark:border-rose-700',
                'icon_bg' => 'bg-rose-500/10 text-rose-600 dark:text-rose-400',
                'type' => 'airtel',
            ];
        }

        if (str_contains($slug, 'gift') || str_contains($name, 'gift')) {
            return [
                'bg' => 'bg-purple-50 dark:bg-purple-950/40',
                'border' => 'border-purple-200 dark:border-purple-800/50',
                'text' => 'text-purple-700 dark:text-purple-300',
                'badge' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/60 dark:text-purple-300 border-purple-300 dark:border-purple-700',
                'icon_bg' => 'bg-purple-500/10 text-purple-600 dark:text-purple-400',
                'type' => 'gift',
            ];
        }

        return [
            'bg' => 'bg-indigo-50 dark:bg-indigo-950/40',
            'border' => 'border-indigo-200 dark:border-indigo-800/50',
            'text' => 'text-indigo-700 dark:text-indigo-300',
            'badge' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/60 dark:text-indigo-300 border-indigo-300 dark:border-indigo-700',
            'icon_bg' => 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400',
            'type' => 'gateway',
        ];
    }
}
