<?php

namespace App\Services;

class UnitService
{
    /**
     * All recognized measurement units, grouped by family with base conversion factors.
     * For each family, factors are normalized against a base unit:
     * - Weight: base unit 'kg'
     * - Volume: base unit 'l'
     * - Count:  base unit 'pcs'
     */
    public const FAMILIES = [
        'weight' => [
            'label'     => 'Weight',
            'base_unit' => 'kg',
            'units'     => [
                'kg' => ['label' => 'Kilogram (kg)', 'factor' => 1.0,      'symbol' => 'kg'],
                'g'  => ['label' => 'Gram (g)',      'factor' => 0.001,    'symbol' => 'g'],
                'mg' => ['label' => 'Milligram (mg)','factor' => 0.000001, 'symbol' => 'mg'],
            ],
        ],
        'volume' => [
            'label'     => 'Volume',
            'base_unit' => 'l',
            'units'     => [
                'l'  => ['label' => 'Liter (L)',       'factor' => 1.0,   'symbol' => 'L'],
                'ml' => ['label' => 'Milliliter (ml)', 'factor' => 0.001, 'symbol' => 'ml'],
                'cl' => ['label' => 'Centiliter (cl)', 'factor' => 0.01,  'symbol' => 'cl'],
            ],
        ],
        'count' => [
            'label'     => 'Count / Discrete',
            'base_unit' => 'pcs',
            'units'     => [
                'pcs'    => ['label' => 'Piece (pcs)',   'factor' => 1.0, 'symbol' => 'pcs'],
                'unit'   => ['label' => 'Unit',          'factor' => 1.0, 'symbol' => 'unit'],
                'bottle' => ['label' => 'Bottle',        'factor' => 1.0, 'symbol' => 'btl'],
                'can'    => ['label' => 'Can',           'factor' => 1.0, 'symbol' => 'can'],
                'cap'    => ['label' => 'Cap / Lid',     'factor' => 1.0, 'symbol' => 'cap'],
                'box'    => ['label' => 'Box',           'factor' => 1.0, 'symbol' => 'box'],
                'pack'   => ['label' => 'Pack',          'factor' => 1.0, 'symbol' => 'pack'],
                'bag'    => ['label' => 'Bag',           'factor' => 1.0, 'symbol' => 'bag'],
                'carton' => ['label' => 'Carton',        'factor' => 1.0, 'symbol' => 'ctn'],
                'crate'  => ['label' => 'Crate',         'factor' => 1.0, 'symbol' => 'crate'],
                'roll'   => ['label' => 'Roll',          'factor' => 1.0, 'symbol' => 'roll'],
            ],
        ],
    ];

    /**
     * Get flat list of all unit keys.
     */
    public static function allKeys(): array
    {
        $keys = [];
        foreach (self::FAMILIES as $family) {
            foreach (array_keys($family['units']) as $k) {
                $keys[] = $k;
            }
        }
        return $keys;
    }

    /**
     * Get units grouped by family for dropdowns and selectors.
     */
    public static function getGrouped(): array
    {
        return self::FAMILIES;
    }

    /**
     * Get the family identifier ('weight', 'volume', 'count') for a given unit key.
     */
    public static function getFamily(?string $unit): ?string
    {
        if (!$unit) return null;
        $unit = strtolower(trim($unit));

        foreach (self::FAMILIES as $familyKey => $family) {
            if (isset($family['units'][$unit])) {
                return $familyKey;
            }
        }

        return null;
    }

    /**
     * Get all units in the same family as the given unit.
     */
    public static function getCompatibleUnits(?string $unit): array
    {
        $family = self::getFamily($unit);
        if (!$family || !isset(self::FAMILIES[$family])) {
            return self::allKeys();
        }

        return array_keys(self::FAMILIES[$family]['units']);
    }

    /**
     * Check if two units belong to the same family.
     */
    public static function areCompatible(?string $fromUnit, ?string $toUnit): bool
    {
        if (!$fromUnit || !$toUnit) return true;
        if (strtolower($fromUnit) === strtolower($toUnit)) return true;

        $famFrom = self::getFamily($fromUnit);
        $famTo   = self::getFamily($toUnit);

        // Same family is compatible
        if ($famFrom && $famTo && $famFrom === $famTo) {
            return true;
        }

        // Discrete items are often flexibly mapped
        if ($famFrom === 'count' || $famTo === 'count') {
            return true;
        }

        return false;
    }

    /**
     * Get conversion factor to convert from $fromUnit to $toUnit.
     * Multiplier such that: $qtyInToUnit = $qtyInFromUnit * factor.
     * E.g. getConversionFactor('g', 'kg') = 0.001
     * E.g. getConversionFactor('kg', 'g') = 1000
     * E.g. getConversionFactor('ml', 'l') = 0.001
     */
    public static function getConversionFactor(?string $fromUnit, ?string $toUnit): float
    {
        if (!$fromUnit || !$toUnit) return 1.0;

        $fromUnit = strtolower(trim($fromUnit));
        $toUnit   = strtolower(trim($toUnit));

        if ($fromUnit === $toUnit) return 1.0;

        $famFrom = self::getFamily($fromUnit);
        $famTo   = self::getFamily($toUnit);

        // Both in standard families
        if ($famFrom && $famTo && $famFrom === $famTo) {
            $fromFactor = self::FAMILIES[$famFrom]['units'][$fromUnit]['factor'] ?? 1.0;
            $toFactor   = self::FAMILIES[$famTo]['units'][$toUnit]['factor'] ?? 1.0;

            if ($toFactor > 0) {
                return (float) ($fromFactor / $toFactor);
            }
        }

        // Default fallback
        return 1.0;
    }

    /**
     * Convert an amount from one unit to another.
     */
    public static function convert(float $amount, ?string $fromUnit, ?string $toUnit): float
    {
        return round($amount * self::getConversionFactor($fromUnit, $toUnit), 6);
    }

    /**
     * Format a quantity with its unit symbol or label.
     */
    public static function format(float $amount, ?string $unit = null, int $decimals = 2): string
    {
        $formatted = number_format($amount, $decimals);
        // Trim trailing zeros for cleaner display if decimals is 2
        if (str_contains($formatted, '.')) {
            $formatted = rtrim(rtrim($formatted, '0'), '.');
        }

        return $unit ? "{$formatted} {$unit}" : $formatted;
    }
}
