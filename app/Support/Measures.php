<?php

namespace App\Support;

class Measures
{
    /** @return array{0: float, 1: float, 2: float}|null interior, exterior, width; null unless the text has three parts */
    public static function parts(?string $measures): ?array
    {
        if ($measures === null || ! preg_match('/^([\d.]+)\s*x\s*([\d.]+)\s*x\s*([\d.]+)/i', $measures, $m)) {
            return null;
        }

        return [(float) $m[1], (float) $m[2], (float) $m[3]];
    }

    /** @return array<int, array{0: string, 1: string}> */
    public static function labeled(?string $measures): array
    {
        if ($measures === null) {
            return [];
        }

        if (preg_match('/^([\d.]+)\s*x\s*([\d.]+)\s*mm\s*\(dado/i', $measures, $m)) {
            return [['Dado', $m[1].' mm'], ['Largo', $m[2].' mm']];
        }

        if (preg_match('/^([\d.]+)\s*x\s*([\d.]+)\s*x\s*([\d.]+)/i', $measures, $m)) {
            return [['Diámetro interior', $m[1].' mm'], ['Diámetro exterior', $m[2].' mm'], ['Ancho', $m[3].' mm']];
        }

        return [['Medidas', $measures]];
    }

    /** @param array{interior: ?float, exterior: ?float, ancho: ?float} $wanted */
    public static function matches(?string $measures, array $wanted): bool
    {
        $parts = self::parts($measures);

        if ($parts === null) {
            return false;
        }

        foreach ([$wanted['interior'], $wanted['exterior'], $wanted['ancho']] as $i => $value) {
            if ($value !== null && abs($parts[$i] - $value) > 0.001) {
                return false;
            }
        }

        return true;
    }
}
