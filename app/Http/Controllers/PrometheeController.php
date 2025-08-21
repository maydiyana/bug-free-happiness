<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Alternative;
use App\Models\Criteria;
use App\Models\GradeAlternativeCriteria;

class PrometheeController extends Controller
{
    public function calculate()
    {
        $userId = auth()->user()->id;

        // Ambil data dari DB
        $alternatives = Alternative::where('user_id', $userId)->get();
        $criteria = Criteria::where('user_id', $userId)->get();
        
        // Matriks keputusan
        $decisionMatrix = [];
        foreach ($alternatives as $alt) {
            foreach ($criteria as $crit) {
                $value = GradeAlternativeCriteria::where('alternative_id', $alt->id)
                    ->where('criteria_id', $crit->id)
                    ->value('grade');
                $decisionMatrix[$alt->id][$crit->id] = $value;
            }
        }

        // ===== Normalisasi (min-max normalization) =====
        $normalizedMatrix = [];
        foreach ($criteria as $crit) {
            $values = [];
            foreach ($alternatives as $alt) {
                $values[] = $decisionMatrix[$alt->id][$crit->id];
            }

            $minVal = min($values);
            $maxVal = max($values);
            $range = $maxVal - $minVal ?: 1;

            foreach ($alternatives as $alt) {
                $val = $decisionMatrix[$alt->id][$crit->id];
                if ($crit->type == 'benefit') {
                    $normalizedMatrix[$alt->id][$crit->id] = ($val - $minVal) / $range;
                } else { // cost
                    $normalizedMatrix[$alt->id][$crit->id] = ($maxVal - $val) / $range;
                }
            }
        }

        // ===== Matriks preferensi =====
       // ===== Matriks preferensi =====
$prefMatrix = [];
foreach ($alternatives as $i) {
    foreach ($alternatives as $j) {
        if ($i->id != $j->id) {
            $preferenceSum = 0;
            foreach ($criteria as $crit) {
                // Ambil nilai normalisasi untuk alternatif i dan j
                $valI = $normalizedMatrix[$i->id][$crit->id];
                $valJ = $normalizedMatrix[$j->id][$crit->id];

                // Selisih
                $diff = $valI - $valJ;

                // Fungsi preferensi sederhana
                $P = $diff > 0 ? 1 : 0;

                // Ambil bobot langsung dari database (sudah ada di $crit->weight)
                $weight = $crit->weight;

                // Tambahkan ke total preferensi
                $preferenceSum += $P * $weight;
            }
            $prefMatrix[$i->id][$j->id] = $preferenceSum;
        } else {
            $prefMatrix[$i->id][$j->id] = 0;
        }
    }
}

        // ===== Leaving Flow & Entering Flow =====
        $leavingFlow = [];
        $enteringFlow = [];
        $n = count($alternatives);

        foreach ($alternatives as $i) {
            $leavingFlow[$i->id] = array_sum($prefMatrix[$i->id]) / ($n - 1);
        }

        foreach ($alternatives as $j) {
            $sum = 0;
            foreach ($alternatives as $i) {
                $sum += $prefMatrix[$i->id][$j->id];
            }
            $enteringFlow[$j->id] = $sum / ($n - 1);
        }

        // ===== Net Flow =====
        $netFlow = [];
        foreach ($alternatives as $alt) {
            $netFlow[$alt->id] = $leavingFlow[$alt->id] - $enteringFlow[$alt->id];
        }

        // ===== Gabungkan hasil =====
        $results = [];
        foreach ($alternatives as $alt) {
            $results[] = (object) [
                'code' => 'A' . $alt->id,
                'name' => $alt->name,
                'leaving' => round($leavingFlow[$alt->id], 4),
                'entering' => round($enteringFlow[$alt->id], 4),
                'net' => round($netFlow[$alt->id], 4),
            ];
        }

        // Urutkan berdasarkan net flow tertinggi
        usort($results, function ($a, $b) {
            return $b->net <=> $a->net;
        });

        // Tambahkan ranking
        foreach ($results as $index => $res) {
            $res->rank = $index + 1;
        }

        return view('promethee', [
            'results' => $results,
            'prefMatrix' => $prefMatrix,
            'decisionMatrix' => $decisionMatrix,
            'normalizedMatrix' => $normalizedMatrix,
            'criteria' => $criteria,
            'alternatives' => $alternatives
        ]);
    }
}
