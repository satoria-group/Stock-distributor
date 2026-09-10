<?php

namespace App\Services;

use App\Models\DistributorItem;
use App\Models\NetsuiteItem;
use Illuminate\Support\Collection;

class ItemMatchingService
{
    /**
     * Skor minimum agar sebuah saran boleh diterapkan secara otomatis/massal
     * tanpa ditinjau satu per satu. Sama dengan ambang batas 'high' pada
     * findMatches(), yaitu band hijau di UI.
     */
    public const AUTO_APPROVE_MIN_SCORE = 70;

    /** @var Collection<int, NetsuiteItem> */
    protected Collection $netsuiteItems;

    /** @var Collection<string, Collection<int, DistributorItem>> */
    protected Collection $historicalMappings;

    public function __construct()
    {
        $this->netsuiteItems = NetsuiteItem::all();

        // Group already mapped items by normalized item name for 100% instant historical match
        $this->historicalMappings = DistributorItem::mapped()
            ->with('netsuiteItem')
            ->get()
            ->groupBy(fn ($item) => $this->normalizeKey($item->item_name));
    }

    /**
     * Find best match and top 3 candidate matches for a distributor item name.
     *
     * @param string $distItemName
     * @return array{
     *     best_match: ?NetsuiteItem,
     *     score: int,
     *     match_type: string,
     *     reason: string,
     *     top_matches: array<int, array{item: NetsuiteItem, score: int}>
     * }
     */
    public function findMatches(string $distItemName): array
    {
        $normalizedKey = $this->normalizeKey($distItemName);

        // Level 1: Cross-distributor historical match (100% confidence)
        if ($this->historicalMappings->has($normalizedKey)) {
            $mappedGroup = $this->historicalMappings->get($normalizedKey);
            $nsItem = $mappedGroup->first()?->netsuiteItem;
            if ($nsItem) {
                return [
                    'best_match' => $nsItem,
                    'score' => 100,
                    'match_type' => 'historical',
                    'reason' => 'Sama persis dengan pemetaan cabang distributor lain',
                    'top_matches' => [
                        ['item' => $nsItem, 'score' => 100],
                    ],
                ];
            }
        }

        $distTokens = $this->tokenize($distItemName);
        $distGauge = $this->extractGauge($distItemName);
        $distCatheterType = $this->extractCatheterType($distItemName);
        $distVolume = $this->extractVolume($distItemName);
        $distPct = $this->extractPercentage($distItemName);
        $distBrand = $this->extractBrand($distItemName);

        $scoredMatches = [];

        foreach ($this->netsuiteItems as $ns) {
            $nsFullName = $ns->netsuite_name . ' ' . $ns->netsuite_id;
            $nsTokens = $this->tokenize($nsFullName);
            $nsGauge = $this->extractGauge($nsFullName);
            $nsCatheterType = $this->extractCatheterType($nsFullName);
            $nsVolume = $this->extractVolume($nsFullName);
            $nsPct = $this->extractPercentage($nsFullName);
            $nsBrand = $this->extractBrand($nsFullName);

            // Hard constraints: Mismatched key attributes disqualify
            if ($distGauge && $nsGauge && $distGauge !== $nsGauge) {
                continue;
            }
            if ($distCatheterType && $nsCatheterType && $distCatheterType !== $nsCatheterType) {
                continue;
            }
            if ($distVolume && $nsVolume && $distVolume !== $nsVolume) {
                continue;
            }
            if ($distPct && $nsPct && $distPct !== $nsPct) {
                continue;
            }

            // Jaccard Token Overlap (0 - 40 points)
            $intersect = array_intersect($distTokens, $nsTokens);
            $union = array_unique(array_merge($distTokens, $nsTokens));
            $jaccard = count($union) > 0 ? (count($intersect) / count($union)) : 0;

            // Specificity Bonuses (up to 55 points)
            $bonus = 0;
            if ($distGauge && $distGauge === $nsGauge) {
                $bonus += 25; // Exact gauge match (e.g. 18G, 20G, 22G, 24G)
            }
            if ($distCatheterType && $distCatheterType === $nsCatheterType) {
                $bonus += 10; // Exact form factor (e.g. wing or pen)
            }
            if ($distVolume && $distVolume === $nsVolume) {
                $bonus += 15; // Exact volume match (e.g. 500ml)
            }
            if ($distPct && $distPct === $nsPct) {
                $bonus += 15; // Exact concentration match (e.g. 5%)
            }
            if ($distBrand && $nsBrand && $distBrand === $nsBrand) {
                $bonus += 15; // Brand match (e.g. Satoria Medika, Kimia Farma, Suryavena)
            }

            // String Similarity ratio (0 - 15 points)
            similar_text(mb_strtolower($distItemName), mb_strtolower($ns->netsuite_name), $simText);

            $finalScore = min(98, round(($jaccard * 35) + $bonus + ($simText * 0.15)));

            $scoredMatches[] = [
                'item' => $ns,
                'score' => max(5, $finalScore),
            ];
        }

        usort($scoredMatches, fn ($a, $b) => $b['score'] <=> $a['score']);

        $top3 = array_slice($scoredMatches, 0, 3);

        // Tidak ada kandidat yang lolos hard constraint (gauge / tipe / volume /
        // konsentrasi). Jangan mengarang saran: kembalikan null supaya pemanggil
        // tahu item ini benar-benar perlu dipetakan manual.
        if (empty($top3)) {
            return [
                'best_match' => null,
                'score' => 0,
                'match_type' => 'none',
                'reason' => 'Tidak ada kandidat yang cocok dengan atribut item ini',
                'top_matches' => [],
            ];
        }

        $best = $top3[0]['item'];
        $bestScore = $top3[0]['score'];

        $matchType = 'low';
        $reason = 'Kandidat terdekat berdasarkan kemiripan umum';
        if ($bestScore >= 70) {
            $matchType = 'high';
            $reason = 'Kecocokan atribut spesifik dan nama produk sangat tinggi';
        } elseif ($bestScore >= 45) {
            $matchType = 'medium';
            $reason = 'Kecocokan nama produk relevan';
        }

        return [
            'best_match' => $best,
            'score' => $bestScore,
            'match_type' => $matchType,
            'reason' => $reason,
            'top_matches' => $top3,
        ];
    }

    /**
     * Batch process suggestions for a list of unmapped items (includes all items).
     *
     * @param Collection<int, DistributorItem> $unmappedItems
     * @return array<int, array>
     */
    public function getSuggestionsForCollection(Collection $unmappedItems): array
    {
        $suggestions = [];
        foreach ($unmappedItems as $item) {
            $match = $this->findMatches($item->item_name);
            if ($match['best_match']) {
                $suggestions[$item->id] = $match;
            }
        }

        return $suggestions;
    }

    protected function normalizeKey(string $str): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', $str)));
    }

    protected function tokenize(string $str): array
    {
        $str = mb_strtolower($str);

        // Medical & logistics alias mapping
        $replacements = [
            'cath' => 'catheter',
            'inj.' => 'injection',
            'inj' => 'injection',
            'wings' => 'wing',
            'btl' => 'botol',
            'amp' => 'ampul',
            'sod.' => 'sodium',
            'chlor' => 'chloride',
            'pct' => 'parasetamol',
            'ns' => 'sodium chloride',
            'd5' => 'dextrose 5%',
            'd10' => 'dextrose 10%',
            'rl' => 'ringer lactate',
            'generik' => '',
            '@50' => '',
            '@1' => '',
            'box' => '',
            'pch' => '',
            'disposable' => '',
        ];
        $str = strtr($str, $replacements);
        preg_match_all('/[a-z0-9]+/i', $str, $matches);

        return array_values(array_filter($matches[0], fn ($t) => strlen($t) > 1));
    }

    protected function extractGauge(string $str): ?string
    {
        if (preg_match('/\b(18|20|22|24)\s*g\b/i', $str, $m)) {
            return strtoupper($m[1] . 'G');
        }
        if (preg_match('/\b(pen|wing)\s*(18|20|22|24)\b/i', $str, $m)) {
            return strtoupper($m[2] . 'G');
        }

        return null;
    }

    protected function extractCatheterType(string $str): ?string
    {
        $str = mb_strtolower($str);
        if (str_contains($str, 'wing')) {
            return 'wing';
        }
        if (str_contains($str, 'pen')) {
            return 'pen';
        }

        return null;
    }

    protected function extractVolume(string $str): ?string
    {
        if (preg_match('/\b(\d+)\s*(ml|l)\b/i', $str, $m)) {
            return strtolower($m[1] . $m[2]);
        }

        return null;
    }

    protected function extractPercentage(string $str): ?string
    {
        if (preg_match('/\b(\d+(?:[,\.]\d+)?)\s*%/i', $str, $m)) {
            return str_replace(',', '.', $m[1]) . '%';
        }

        return null;
    }

    protected function extractBrand(string $str): ?string
    {
        $str = mb_strtolower($str);
        if (str_contains($str, 'satoria')) {
            return 'satoria';
        }
        if (str_contains($str, 'suryavena')) {
            return 'suryavena';
        }
        if (str_contains($str, 'kimia farma') || str_contains($str, 'kf')) {
            return 'kimia_farma';
        }
        if (str_contains($str, 'bbraun') || str_contains($str, 'b.braun')) {
            return 'bbraun';
        }

        return null;
    }
}
