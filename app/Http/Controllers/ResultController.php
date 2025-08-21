<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\CalculationsController;
use App\Http\Controllers\PrometheeController;
use App\Models\Alternative;
use App\Models\Criteria;
use App\Models\GradeAlternativeCriteria;

class ResultController extends Controller
{
    public function index()
    {
        $calculatioObj = new CalculationsController;
        $prometheeObj  = new PrometheeController;

        $alternatives = Alternative::where('user_id', auth()->user()->id)->get();
        $criteria     = Criteria::where('user_id', auth()->user()->id)->get();

        if (count($criteria) == 0 || count($alternatives) == 0) {
            return view('modal.calculationModal.noCalculationModal');
        }

        // --- Perhitungan TOPSIS ---
        $decisionMatrix  = $calculatioObj->getDecisionMatrix($alternatives);
        $normMatrix      = $calculatioObj->norm($decisionMatrix);
        $weightedNorm    = $calculatioObj->getWeightedNorm($normMatrix, $criteria);
        $idealPositive   = $calculatioObj->getIdealPositive($weightedNorm, $criteria);
        $idealNegative   = $calculatioObj->getIdealNegative($weightedNorm, $criteria);
        $solutionPositive = $calculatioObj->getSolutionPositive($weightedNorm, $idealPositive);
        $solutionNegative = $calculatioObj->getSolutionNegative($weightedNorm, $idealNegative);
        $PreferenceValue = $calculatioObj->getPreferenceValue($solutionPositive, $solutionNegative);
        $pairedValues    = $this->pairingValues($PreferenceValue, $alternatives);
        $sortedResults   = $this->getSortedResult($pairedValues, 'grade', 0);

        $solutionNegativeRanked = collect($solutionNegative)
            ->map(function ($value, $index) use ($alternatives) {
                return (object) [
                    'code'  => 'A' . ($index + 1),
                    'name'  => $alternatives[$index]->name,
                    'value' => $value
                ];
            })
            ->sortByDesc('value')
            ->values()
            ->map(function ($item, $rank) {
                $item->rank = $rank + 1;
                return $item;
            });
 // Ambil nilai matriks keputusan
        $decisionMatrix = [];
        foreach ($alternatives as $alt) {
            $grades = GradeAlternativeCriteria::where('alternative_id', $alt->id)
                ->orderBy('criteria_id')
                ->pluck('grade')
                ->toArray();
            $decisionMatrix[] = $grades;
        }

        // Normalisasi matriks
        $normMatrix = [];
        foreach (array_keys($criteria->toArray()) as $j) {
            $col = array_column($decisionMatrix, $j);
            $max = max($col);
            $min = min($col);
            foreach ($col as $i => $val) {
                if ($criteria[$j]->type == 'benefit') {
                    $normMatrix[$i][$j] = $val / $max;
                } else {
                    $normMatrix[$i][$j] = $min / $val;
                }
            }
        }

        // Bobot * Normalisasi
        $weightedNorm = [];
        foreach ($normMatrix as $i => $row) {
            foreach ($row as $j => $val) {
                $weightedNorm[$i][$j] = $val * $criteria[$j]->weight;
            }
        }

        // ===================== PROMETHEE =====================
        // Matriks preferensi
        $prefMatrix = [];
        foreach ($weightedNorm as $i => $rowI) {
            foreach ($weightedNorm as $j => $rowJ) {
                if ($i == $j) {
                    $prefMatrix[$i][$j] = 0;
                } else {
                    $sum = 0;
                    foreach ($rowI as $k => $v) {
                        $d = $v - $rowJ[$k];
                        $sum += ($d > 0) ? 1 : 0; // fungsi preferensi sederhana
                    }
                    $prefMatrix[$i][$j] = $sum / count($criteria);
                }
            }
        }

        // Leaving & Entering Flow
        $flows = [];
        foreach ($alternatives as $i => $alt) {
            $leaving = array_sum($prefMatrix[$i]) / (count($alternatives) - 1);
            $entering = array_sum(array_column($prefMatrix, $i)) / (count($alternatives) - 1);
            $netflow = $leaving - $entering;

            $flows[] = (object) [
                'code' => 'A' . ($i + 1),
                'name' => $alt->name,
                'leaving' => $leaving,
                'entering' => $entering,
                'netflow' => $netflow
            ];
        }

        // Ranking PROMETHEE
        $prometheeRanking = collect($flows)
            ->sortByDesc('netflow')
            ->values()
            ->map(function ($item, $rank) {
                $item->rank = $rank + 1;
                return $item;
            });

        

return view('result', compact(
    'sortedResults',
    'solutionNegative',
    'solutionNegativeRanked',
    'prometheeRanking'
));

    }

    public function pairingValues($PreferenceValue, $alternatives)
    {
        $result = [];
        for ($i = 0; $i < count($alternatives); $i++) {
            array_push($result, (object) [
                'id'    => $alternatives[$i]->id,
                'code'  => 'A' . ($i + 1),
                'name'  => $alternatives[$i]->name,
                'grade' => $PreferenceValue[$i],
            ]);
        }
        return $result;
    }

    public function getSortedResult($pairedValues, $sortBy, Bool $asc)
    {
        if ($asc) {
            $sortedResult = collect($pairedValues)->sortBy($sortBy);
        } else {
            $sortedResult = collect($pairedValues)->sortByDesc('grade');
        }

        $sortedResult = $sortedResult->values();

        for ($i = 0; $i < count($sortedResult); $i++) {
            $sortedResult[$i]->rank = $i + 1;
        }

        return collect($sortedResult);
    }
}
