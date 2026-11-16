<?php

namespace App\Support;

/**
 * Splits the field-boundary polygon into per-crop plots for the farm plot
 * visualization on the farmer crops page.
 *
 * Ported from organicPartition.ts (the Agriculture prototype). This math used
 * to live in the view's `@php` block, which broke twice over: a Blade view is
 * re-included on every render, so the plain `function` declarations fataled
 * with "Cannot redeclare polyArea()" the second time the page rendered in the
 * same PHP process, and partitionOrganicLand() read $cropPalettes from the
 * view's top-level scope, which a plain function cannot see — it threw a
 * TypeError for every page with an active crop.
 */
final class LandPartitioner
{

    // ── Organic partitioner color palettes (ported from Agriculture prototype) ──
    private const PALETTES = [
        ['fill' => '#3B6E4C', 'stroke' => '#244A31', 'text' => '#F7FAF7', 'subtext' => '#DCEADF', 'swatch' => '#3B6E4C'],
        ['fill' => '#C6923A', 'stroke' => '#8B621D', 'text' => '#1E1608', 'subtext' => '#3F3014', 'swatch' => '#C6923A'],
        ['fill' => '#7FA06B', 'stroke' => '#4E6B3D', 'text' => '#142212', 'subtext' => '#2B4227', 'swatch' => '#7FA06B'],
        ['fill' => '#B55D3C', 'stroke' => '#7D3920', 'text' => '#FFF9F6', 'subtext' => '#F6DDD3', 'swatch' => '#B55D3C'],
        ['fill' => '#4C7A7B', 'stroke' => '#2D4E4F', 'text' => '#F4FAFA', 'subtext' => '#D3E6E6', 'swatch' => '#4C7A7B'],
        ['fill' => '#9C8D47', 'stroke' => '#675C27', 'text' => '#1C1A0B', 'subtext' => '#383317', 'swatch' => '#9C8D47'],
        ['fill' => '#5E8C6A', 'stroke' => '#3A5E44', 'text' => '#F6FBF7', 'subtext' => '#D8EBE0', 'swatch' => '#5E8C6A'],
        ['fill' => '#8C6A52', 'stroke' => '#5C4230', 'text' => '#FBF7F4', 'subtext' => '#E6D8CE', 'swatch' => '#8C6A52'],
        ['fill' => '#6B8F4E', 'stroke' => '#44602D', 'text' => '#F7FBF3', 'subtext' => '#DDEBD1', 'swatch' => '#6B8F4E'],
        ['fill' => '#A87C4F', 'stroke' => '#71502E', 'text' => '#FFFBF6', 'subtext' => '#EFE0CE', 'swatch' => '#A87C4F'],
    ];

    // ── Polygon math (ported from organicPartition.ts) ──
    public static function polyArea(array $poly): float {
        if (count($poly) < 3) return 0.0;
        $sum = 0.0;
        $n = count($poly);
        for ($i = 0; $i < $n; $i++) {
            $a = $poly[$i];
            $b = $poly[($i + 1) % $n];
            $sum += $a['x'] * $b['y'] - $b['x'] * $a['y'];
        }
        return abs($sum) * 0.5;
    }

    public static function polyPerimeter(array $poly): float {
        if (count($poly) < 2) return 0;
        $p = 0;
        $n = count($poly);
        for ($i = 0; $i < $n; $i++) {
            $a = $poly[$i];
            $b = $poly[($i + 1) % $n];
            $p += hypot($b['x'] - $a['x'], $b['y'] - $a['y']);
        }
        return $p;
    }

    public static function polyCentroid(array $poly): array {
        if (empty($poly)) return ['x' => 400, 'y' => 270];
        $areaSum = 0.0; $cx = 0.0; $cy = 0.0;
        $n = count($poly);
        for ($i = 0; $i < $n; $i++) {
            $a = $poly[$i];
            $b = $poly[($i + 1) % $n];
            $cross = $a['x'] * $b['y'] - $b['x'] * $a['y'];
            $areaSum += $cross;
            $cx += ($a['x'] + $b['x']) * $cross;
            $cy += ($a['y'] + $b['y']) * $cross;
        }
        $denom = 3 * $areaSum;
        if (abs($denom) < 1e-6) {
            $avgX = array_reduce($poly, function($acc, $pt) { return $acc + $pt['x']; }, 0.0) / $n;
            $avgY = array_reduce($poly, function($acc, $pt) { return $acc + $pt['y']; }, 0.0) / $n;
            return ['x' => $avgX, 'y' => $avgY];
        }
        return ['x' => $cx / $denom, 'y' => $cy / $denom];
    }

    public static function cleanPolygon(array $poly): array {
        if (count($poly) < 3) return [];
        $deduped = [];
        foreach ($poly as $pt) {
            $prev = !empty($deduped) ? $deduped[count($deduped) - 1] : null;
            if (!$prev || hypot($pt['x'] - $prev['x'], $pt['y'] - $prev['y']) > 1e-4) {
                $deduped[] = $pt;
            }
        }
        if (count($deduped) > 1) {
            $first = $deduped[0];
            $last = $deduped[count($deduped) - 1];
            if (hypot($first['x'] - $last['x'], $first['y'] - $last['y']) <= 1e-4) {
                array_pop($deduped);
            }
        }
        return count($deduped) >= 3 ? $deduped : $poly;
    }

    public static function clipPolygonHalfPlane(array $poly, float $nx, float $ny, float $d, bool $keepLessOrEqual): array {
        if (count($poly) < 3) return [];
        $out = [];
        $sign = $keepLessOrEqual ? 1 : -1;
        $n = count($poly);
        for ($i = 0; $i < $n; $i++) {
            $a = $poly[$i];
            $b = $poly[($i + 1) % $n];
            $da = ($nx * $a['x'] + $ny * $a['y'] - $d) * $sign;
            $db = ($nx * $b['x'] + $ny * $b['y'] - $d) * $sign;
            $aInside = $da <= 1e-7;
            $bInside = $db <= 1e-7;
            if ($aInside && $bInside) {
                $out[] = $b;
            } elseif ($aInside && !$bInside) {
                $denom = $da - $db;
                if (abs($denom) > 1e-10) {
                    $t = $da / $denom;
                    $out[] = ['x' => $a['x'] + $t * ($b['x'] - $a['x']), 'y' => $a['y'] + $t * ($b['y'] - $a['y'])];
                }
            } elseif (!$aInside && $bInside) {
                $denom = $da - $db;
                if (abs($denom) > 1e-10) {
                    $t = $da / $denom;
                    $out[] = ['x' => $a['x'] + $t * ($b['x'] - $a['x']), 'y' => $a['y'] + $t * ($b['y'] - $a['y'])];
                }
                $out[] = $b;
            }
        }
        return self::cleanPolygon($out);
    }

    public static function splitPolygonByArea(array $poly, float $targetArea, float $nx, float $ny): array {
        $totalArea = self::polyArea($poly);
        if ($targetArea <= 1e-6) return ['taken' => [], 'remaining' => $poly];
        if ($targetArea >= $totalArea - 1e-6) return ['taken' => $poly, 'remaining' => []];

        $minP = INF; $maxP = -INF;
        foreach ($poly as $pt) {
            $p = $nx * $pt['x'] + $ny * $pt['y'];
            if ($p < $minP) $minP = $p;
            if ($p > $maxP) $maxP = $p;
        }

        $lo = $minP; $hi = $maxP;
        for ($iter = 0; $iter < 44; $iter++) {
            $mid = 0.5 * ($lo + $hi);
            $candidate = self::clipPolygonHalfPlane($poly, $nx, $ny, $mid, true);
            $area = self::polyArea($candidate);
            if ($area < $targetArea) {
                $lo = $mid;
            } else {
                $hi = $mid;
            }
        }

        $cutD = 0.5 * ($lo + $hi);
        $taken = self::clipPolygonHalfPlane($poly, $nx, $ny, $cutD, true);
        $remaining = self::clipPolygonHalfPlane($poly, $nx, $ny, $cutD, false);
        return ['taken' => $taken, 'remaining' => $remaining];
    }

    public static function sharedBoundaryLength(array $polyA, array $polyB): float {
        if (count($polyA) < 3 || count($polyB) < 3) return 0;
        $shared = 0;
        $na = count($polyA);
        $nb = count($polyB);
        for ($i = 0; $i < $na; $i++) {
            $a1 = $polyA[$i];
            $a2 = $polyA[($i + 1) % $na];
            $ax = $a2['x'] - $a1['x'];
            $ay = $a2['y'] - $a1['y'];
            $lenA = hypot($ax, $ay);
            if ($lenA < 1e-4) continue;
            $ux = $ax / $lenA;
            $uy = $ay / $lenA;

            for ($j = 0; $j < $nb; $j++) {
                $b1 = $polyB[$j];
                $b2 = $polyB[($j + 1) % $nb];
                $bx = $b2['x'] - $b1['x'];
                $by = $b2['y'] - $b1['y'];
                $lenB = hypot($bx, $by);
                if ($lenB < 1e-4) continue;

                $crossDir = abs($ux * ($by / $lenB) - $uy * ($bx / $lenB));
                if ($crossDir > 1e-3) continue;

                $perpDist = abs(($b1['x'] - $a1['x']) * $uy - ($b1['y'] - $a1['y']) * $ux);
                if ($perpDist > 0.75) continue;

                $projB1 = ($b1['x'] - $a1['x']) * $ux + ($b1['y'] - $a1['y']) * $uy;
                $projB2 = ($b2['x'] - $a1['x']) * $ux + ($b2['y'] - $a1['y']) * $uy;

                $bMin = min($projB1, $projB2);
                $bMax = max($projB1, $projB2);
                $overlap = min($lenA, $bMax) - max(0.0, $bMin);
                if ($overlap > 0.5) {
                    $shared += $overlap;
                }
            }
        }
        return $shared;
    }

    public static function polyToPointsAttr(array $poly): string {
        $pts = [];
        foreach ($poly as $p) {
            $pts[] = number_format($p['x'], 2) . ',' . number_format($p['y'], 2);
        }
        return implode(' ', $pts);
    }

    public static function getBoundaryPolygon(string $preset): array {
        switch ($preset) {
            case 'organic':
                return [
                    ['x' => 148, 'y' => 58], ['x' => 430, 'y' => 36], ['x' => 676, 'y' => 84],
                    ['x' => 748, 'y' => 238], ['x' => 702, 'y' => 422], ['x' => 486, 'y' => 498],
                    ['x' => 184, 'y' => 476], ['x' => 56, 'y' => 318], ['x' => 76, 'y' => 152],
                ];
            case 'pentagon':
                return [
                    ['x' => 392, 'y' => 36], ['x' => 734, 'y' => 178], ['x' => 642, 'y' => 486],
                    ['x' => 154, 'y' => 486], ['x' => 68, 'y' => 196],
                ];
            case 'riverbend':
                return [
                    ['x' => 108, 'y' => 74], ['x' => 520, 'y' => 44], ['x' => 738, 'y' => 156],
                    ['x' => 696, 'y' => 404], ['x' => 404, 'y' => 496], ['x' => 92, 'y' => 430],
                    ['x' => 58, 'y' => 240],
                ];
            case 'rectangle':
            default:
                return [
                    ['x' => 68, 'y' => 56], ['x' => 732, 'y' => 56],
                    ['x' => 732, 'y' => 484], ['x' => 68, 'y' => 484],
                ];
        }
    }

    public static function partitionOrganicLand(array $crops, float $totalLandSize, string $preset = 'organic', int $seed = 0): array {
        $outerPolygon = self::getBoundaryPolygon($preset);
        $outerAreaPx = self::polyArea($outerPolygon);

        $validCrops = array_values(array_filter($crops, fn($c) => $c['size'] > 0));
        $rawUsed = array_reduce($validCrops, fn($sum, $c) => $sum + $c['size'], 0.0);
        $safeTotalLand = max($totalLandSize, 0.0001);
        $isOverAllocated = $rawUsed > $safeTotalLand + 1e-6;
        $effectiveDenominator = $isOverAllocated ? $rawUsed : $safeTotalLand;

        $remainingPoly = $outerPolygon;
        $plots = [];
        $prevCutAngle = null;
        $baseAngleOffset = (($seed * 47) % 360) * (M_PI / 180);

        foreach ($validCrops as $i => $crop) {
            $remainingAreaPx = self::polyArea($remainingPoly);
            if ($remainingAreaPx <= 1e-6 || count($remainingPoly) < 3) break;

            $targetAreaPx = min(
                $remainingAreaPx,
                ($crop['size'] / $effectiveDenominator) * $outerAreaPx
            );

            $isLastFullFill =
                $targetAreaPx >= $remainingAreaPx - 0.5 ||
                ($i === count($validCrops) - 1 && !$isOverAllocated && $rawUsed >= $safeTotalLand - 1e-4);

            if ($isLastFullFill) {
                $finalPoly = $remainingPoly;
                $areaPx = self::polyArea($finalPoly);
                $centroid = self::polyCentroid($finalPoly);
                $palette = self::PALETTES[$i % count(self::PALETTES)];
                $plots[] = [
                    'id' => $crop['id'],
                    'name' => $crop['name'],
                    'size' => $crop['size'],
                    'percentage' => ($crop['size'] / $safeTotalLand) * 100,
                    'polygon' => $finalPoly,
                    'areaPx' => $areaPx,
                    'labelCenter' => $centroid,
                    'vertexCount' => count($finalPoly),
                    'shapeName' => count($finalPoly) . '-gon',
                    'palette' => $palette,
                    'furrowAngleDeg' => (($i * 37 + 18) % 140) - 70,
                    'stage' => $crop['stage'] ?? 'vegetative',
                    'batch' => $crop['batch'] ?? '',
                ];
                $remainingPoly = [];
                break;
            }

            $numAngles = 72;
            $bestScore = -INF;
            $bestTaken = [];
            $bestRemaining = [];
            $bestAngle = 0;
            $preferredAngle = fmod($baseAngleOffset + 0.52 + $i * 2.18, M_PI * 2);

            for ($k = 0; $k < $numAngles; $k++) {
                $theta = ($k / $numAngles) * M_PI * 2 + $baseAngleOffset * 0.25;
                $nx = cos($theta);
                $ny = sin($theta);

                $split = self::splitPolygonByArea($remainingPoly, $targetAreaPx, $nx, $ny);
                $taken = $split['taken'];
                $remaining = $split['remaining'];

                if (count($taken) < 3 || count($remaining) < 3) continue;

                $qTaken = self::polyArea($taken) / (self::polyPerimeter($taken) ** 2) * 4 * M_PI;
                $qRem = self::polyArea($remaining) / (self::polyPerimeter($remaining) ** 2) * 4 * M_PI;
                $minQ = min($qTaken, $qRem);

                $continuationScore = 0;
                if (count($plots) > 0) {
                    $lastPlot = $plots[count($plots) - 1];
                    $sharedWithLast = self::sharedBoundaryLength($taken, $lastPlot['polygon']);
                    if ($sharedWithLast > 6) {
                        $continuationScore += 0.42 + min(0.25, $sharedWithLast / 450);
                    } else {
                        $sharedAny = 0;
                        foreach ($plots as $prev) {
                            $sharedAny += self::sharedBoundaryLength($taken, $prev['polygon']);
                        }
                        if ($sharedAny > 6) {
                            $continuationScore += 0.22;
                        } else {
                            $continuationScore -= 0.35;
                        }
                    }
                }

                $anglePenalty = 0;
                if ($prevCutAngle !== null) {
                    $angleDiff = abs(fmod(($theta - $prevCutAngle + M_PI), M_PI * 2) - M_PI);
                    if ($angleDiff < 0.48) {
                        $anglePenalty = (0.48 - $angleDiff) * 1.4;
                    }
                }

                $vertexBonus = count($taken) >= 5 ? 0.08 : (count($taken) === 3 ? 0.05 : 0.03);
                $dirDot = cos($theta - $preferredAngle) * 0.06;

                $score = $minQ * 1.15 + $qTaken * 0.35 + $continuationScore + $vertexBonus + $dirDot - $anglePenalty;

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestTaken = $taken;
                    $bestRemaining = $remaining;
                    $bestAngle = $theta;
                }
            }

            if (count($bestTaken) < 3) {
                $fallback = self::splitPolygonByArea($remainingPoly, $targetAreaPx, 1, 0.35);
                $bestTaken = $fallback['taken'];
                $bestRemaining = $fallback['remaining'];
                $bestAngle = atan2(0.35, 1);
            }

            $areaPx = self::polyArea($bestTaken);
            $centroid = self::polyCentroid($bestTaken);
            $palette = self::PALETTES[$i % count(self::PALETTES)];

            $plots[] = [
                'id' => $crop['id'],
                'name' => $crop['name'],
                'size' => $crop['size'],
                'percentage' => ($crop['size'] / $safeTotalLand) * 100,
                'polygon' => $bestTaken,
                'areaPx' => $areaPx,
                'labelCenter' => $centroid,
                'vertexCount' => count($bestTaken),
                'shapeName' => count($bestTaken) . '-gon',
                'palette' => $palette,
                'furrowAngleDeg' => fmod(($bestAngle * 180 / M_PI + 90), 180),
                'stage' => $crop['stage'] ?? 'vegetative',
                'batch' => $crop['batch'] ?? '',
            ];

            $remainingPoly = $bestRemaining;
            $prevCutAngle = $bestAngle;
        }

        $totalFree = max(0, $safeTotalLand - $rawUsed);
        $freePlot = null;
        if (!$isOverAllocated && $totalFree > 1e-4 && count($remainingPoly) >= 3) {
            $freeAreaPx = self::polyArea($remainingPoly);
            if ($freeAreaPx > 1) {
                $freePlot = [
                    'polygon' => $remainingPoly,
                    'size' => $totalFree,
                    'percentage' => ($totalFree / $safeTotalLand) * 100,
                    'areaPx' => $freeAreaPx,
                    'labelCenter' => self::polyCentroid($remainingPoly),
                    'vertexCount' => count($remainingPoly),
                    'shapeName' => count($remainingPoly) . '-gon',
                ];
            }
        }

        return [
            'outerPolygon' => $outerPolygon,
            'plots' => $plots,
            'freePlot' => $freePlot,
            'totalUsed' => $rawUsed,
            'totalFree' => $totalFree,
            'isOverAllocated' => $isOverAllocated,
        ];
    }
}
