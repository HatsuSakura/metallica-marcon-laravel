<?php

namespace App\Services\Nlp\Providers;

use App\Models\Site;

class HeuristicNlpProvider implements NlpProvider
{
    // First-cut vocabulary: keyword (used for LIKE on cer_codes.description) → synonyms
    // This mapping is intentionally incomplete and will be extended over time.
    // Common Italian words that appear in company names but are too generic to use as customer-match signals.
    private const CUSTOMER_MATCH_STOPWORDS = [
        'materiali', 'materiale', 'servizi', 'servizio', 'prodotti', 'prodotto',
        'sistemi', 'sistema', 'italia', 'italiana', 'italiano', 'italiani',
        'group', 'gruppo', 'holding', 'global', 'tecnica', 'tecnici', 'tecnico',
        'lavori', 'lavoro', 'costruzioni', 'costruzione', 'gestione', 'gestioni',
        'general', 'generali', 'commerciale', 'commerciali', 'industriale', 'industriali',
        'edile', 'edili', 'logistica', 'trasporti', 'trasporto', 'forniture', 'fornitura',
        'center', 'centro', 'nord', 'west', 'east', 'soluzioni', 'soluzione',
    ];

    private const CER_KEYWORD_MAP = [
        'metall'  => ['metallo', 'metalli', 'metallici', 'metallica', 'metallico', 'ferroso', 'ferrosi', 'ferro', 'acciaio', 'rame', 'alluminio', 'zinco', 'piombo', 'rottame'],
        'plast'   => ['plastica', 'plastiche', 'plastico', 'plastici'],
        'legno'   => ['legno', 'legnoso', 'legnosi', 'legname'],
        'batter'  => ['batteria', 'batterie', 'accumulatore', 'accumulatori'],
        'aerosol' => ['bomboletta', 'bombolette', 'aerosol'],
        'acid'    => ['acido', 'acidi'],
        'olio'    => ['olio', 'oli', 'oleoso', 'oleosi', 'lubrificante', 'lubrificanti'],
        'carta'   => ['carta', 'cartone', 'cartoni', 'carta e cartone'],
        'vetro'   => ['vetro', 'vetri', 'vetrose'],
        'gomma'   => ['gomma', 'gomme', 'pneumatico', 'pneumatici'],
    ];

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function parseLogistics(string $query, array $context = []): array
    {
        $normalized = $this->normalizeText($query);
        $scenario = $this->extractScenario($normalized);
        $radiusKm = $this->extractRadiusKm($normalized);
        $riskRange = $this->extractRiskRange($normalized);
        $daysFilter = $this->extractDaysToNextPickup($normalized);
        $lastWithdrawDaysMin = $this->extractLastWithdrawDaysMin($normalized);
        $statuses = $this->extractStatuses($normalized);
        $hazardous = $this->extractHazardous($normalized);
        $hasBulk = $this->extractHasBulk($normalized);
        $cerCodes = $this->extractCerCodes($normalized);
        $cerKeyword = $this->extractCerKeyword($normalized);
        $cerDangerous = $this->extractCerDangerous($normalized);

        // Self-reference takes priority over customer name resolution
        $isSelfReference = $this->isSelfReference($normalized);
        $hqCoordinates   = $isSelfReference ? $this->hqCoordinates() : null;

        $referenceCustomer  = null;
        $ambiguousCustomers = [];
        $ambiguousToken     = null;

        if (!$isSelfReference) {
            // A forced customer from the disambiguation UI bypasses detection entirely
            $forced = $context['force_customer'] ?? null;
            if (is_array($forced) && isset($forced['id'], $forced['name'])) {
                $referenceCustomer = ['id' => (int) $forced['id'], 'name' => (string) $forced['name']];
            } else {
                $resolved = $this->resolveCustomerReference($normalized, $query);
                $referenceCustomer  = $resolved['resolved'];
                $ambiguousCustomers = $resolved['candidates'];
                $ambiguousToken     = $resolved['token'];
            }
        }

        if ($isSelfReference && $hqCoordinates) {
            $geoOrigin  = 'coordinates';
            $reference  = ['customer' => null, 'site' => null, 'coordinates' => $hqCoordinates];
        } elseif ($referenceCustomer) {
            $geoOrigin  = 'customer';
            $reference  = ['customer' => $referenceCustomer, 'site' => null, 'coordinates' => null];
        } else {
            $geoOrigin  = 'coordinates';
            $reference  = ['customer' => null, 'site' => null, 'coordinates' => null];
        }

        $confidence = $this->computeConfidence($query, [
            'scenario'            => $scenario,
            'referenceCustomer'   => $referenceCustomer,
            'ambiguousCustomers'  => $ambiguousCustomers,
            'isSelfReference'     => $isSelfReference,
            'hqCoordinates'       => $hqCoordinates,
            'riskRange'           => $riskRange,
            'daysFilter'          => $daysFilter,
            'lastWithdrawDaysMin' => $lastWithdrawDaysMin,
            'statuses'            => $statuses,
            'hazardous'           => $hazardous,
            'hasBulk'             => $hasBulk,
            'cerCodes'            => $cerCodes,
            'cerKeyword'          => $cerKeyword,
            'cerDangerous'        => $cerDangerous,
            'hasNoActiveOrders'   => $this->extractHasNoActiveOrders($normalized),
            'radiusKm'            => $radiusKm,
        ]);

        return [
            'scenario' => $scenario,
            'reference' => $reference,
            'geo' => [
                'origin'    => $geoOrigin,
                'radius_km' => $radiusKm,
            ],
            'time' => [
                'target_date' => $this->extractAbsoluteDate($normalized),
                'from'        => null,
                'to'          => null,
            ],
            'site_filters' => [
                'risk_min'                 => $riskRange['risk_min'],
                'risk_max'                 => $riskRange['risk_max'],
                'days_to_next_pickup_min'  => $daysFilter['days_to_next_pickup_min'],
                'days_to_next_pickup_max'  => $daysFilter['days_to_next_pickup_max'],
                'last_withdraw_days_min'   => $lastWithdrawDaysMin,
                'has_no_active_orders'     => $this->extractHasNoActiveOrders($normalized),
                'customer_ids'             => null,
                'exclude_customer_ids'     => null,
            ],
            'order_filters' => [
                'statuses'      => $statuses,
                'hazardous'     => $hazardous,
                'has_bulk'      => $hasBulk,
                'cer_codes'     => $cerCodes,
                'cer_keyword'   => $cerKeyword,
                'cer_dangerous' => $cerDangerous,
                'min_weight_kg' => null,
                'max_weight_kg' => null,
                'requested_from' => null,
                'requested_to'   => null,
            ],
            'sort' => [
                'mode'    => 'distance',
                'weights' => null,
            ],
            'limit' => [
                'sites'  => 200,
                'orders' => 200,
            ],
            '_confidence'            => $confidence,
            '_ambiguous_reference'   => !empty($ambiguousCustomers)
                ? ['token' => $ambiguousToken, 'candidates' => $ambiguousCustomers]
                : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $signals
     * @return array{level: string, issues: array<int, string>}
     */
    private function computeConfidence(string $originalQuery, array $signals): array
    {
        $issues = [];

        // Ambiguous abbreviation detected
        if (!empty($signals['ambiguousCustomers'])) {
            $issues[] = 'Abbreviazione ambigua: seleziona il cliente corretto dai suggerimenti.';
        }

        // Probable unresolved proper noun: capitalized word ≥4 chars that is not a stopword
        if (!$signals['isSelfReference'] && $signals['referenceCustomer'] === null && empty($signals['ambiguousCustomers'])) {
            preg_match_all('/\b([A-Z][a-zA-Zà-ú]{3,})\b/', $originalQuery, $m);
            $candidates = array_filter($m[1] ?? [], fn ($w) =>
                !in_array(mb_strtolower($w), self::CUSTOMER_MATCH_STOPWORDS, true)
            );
            if (!empty($candidates)) {
                $issues[] = 'Cliente non identificato con certezza nella query.';
            }
        }

        // No filters extracted at all
        $hasAnyFilter =
            $signals['riskRange']['risk_min'] !== null ||
            $signals['riskRange']['risk_max'] !== null ||
            $signals['daysFilter']['days_to_next_pickup_max'] !== null ||
            $signals['lastWithdrawDaysMin'] !== null ||
            !empty($signals['statuses']) ||
            $signals['hazardous'] !== null ||
            $signals['hasBulk'] !== null ||
            !empty($signals['cerCodes']) ||
            $signals['cerKeyword'] !== null ||
            $signals['cerDangerous'] !== null ||
            $signals['hasNoActiveOrders'] !== null ||
            $signals['radiusKm'] !== null ||
            $signals['referenceCustomer'] !== null ||
            $signals['isSelfReference'];

        if (!$hasAnyFilter) {
            $issues[] = 'Nessun criterio di ricerca riconosciuto nella query.';
        }

        // Ambiguous scenario
        if ($signals['scenario'] === 'hybrid') {
            $issues[] = 'Scenario ambiguo: la query contiene segnali misti di siti e ordini.';
        }

        $level = match (true) {
            count($issues) >= 2 => 'bassa',
            count($issues) === 1 => 'media',
            default              => 'alta',
        };

        return ['level' => $level, 'issues' => array_values($issues)];
    }

    private function normalizeText(?string $value): string
    {
        return mb_strtolower(trim((string) $value));
    }

    private function extractScenario(string $text): string
    {
        // Strip negative-context occurrences ("senza ordini", "nessun ordine") before detecting scenario
        $textForScenario = preg_replace('/\b(senza|nessun[oa]?)\s+(ordini|ordine)\b/ui', '', $text);

        $hasOrderWords = (bool) preg_match(
            '/\b(ordini|ordine|richiest[ae]|ritir[oi]\s+richiest[oi]|aperto|aperti)\b/ui',
            $textForScenario
        );
        $hasSitePlanningWords = (bool) preg_match(
            '/\b(rischio|critico|prossim[oi]\s+ritir[oi]|giorn[io]|programmazione|pianifica)\b/ui',
            $text
        );

        if ($hasOrderWords && $hasSitePlanningWords) {
            return 'hybrid';
        }

        if ($hasOrderWords) {
            return 'order_requests';
        }

        return 'planning_sites';
    }

    private function isSelfReference(string $text): bool
    {
        return (bool) preg_match(
            '/\b(nostra\s+sede|sede\s+nostra|sede\s+di\s+metallica(\s+marcon)?|metallica(\s+marcon)?|la\s+metallica|dalla\s+nostra|dalla\s+sede|da\s+noi|noi\s+siamo|la\s+nostra\s+azienda|la\s+nostra\s+base)\b/ui',
            $text
        );
    }

    /**
     * @return array{lat:float,lng:float}|null
     */
    private function hqCoordinates(): ?array
    {
        $lat = config('services.company.hq_lat');
        $lng = config('services.company.hq_lng');

        if ($lat === null || $lng === null) {
            return null;
        }

        return ['lat' => (float) $lat, 'lng' => (float) $lng];
    }

    /**
     * @return array{resolved: array{id:int,name:string}|null, candidates: array, token: string|null}
     */
    private function resolveCustomerReference(string $normalizedText, string $originalQuery): array
    {
        $customers = $this->loadAllCustomers();

        // Pass 1: standard match — full name or significant word (≥4 chars)
        foreach ($customers as $customer) {
            $nameLower = mb_strtolower($customer['name']);

            if (str_contains($normalizedText, $nameLower)) {
                return ['resolved' => $customer, 'candidates' => [], 'token' => null];
            }

            $words = preg_split('/[\s\.\,\-\_\/]+/', $nameLower);
            foreach ($words as $word) {
                if (
                    mb_strlen($word) >= 4
                    && !in_array($word, self::CUSTOMER_MATCH_STOPWORDS, true)
                    && preg_match('/\b' . preg_quote($word, '/') . '\b/u', $normalizedText)
                ) {
                    return ['resolved' => $customer, 'candidates' => [], 'token' => null];
                }
            }
        }

        // Pass 2: abbreviation match — e.g. "A.C." → "AC"
        $abbreviations = $this->extractAbbreviations($originalQuery);
        foreach ($abbreviations as $abbrev) {
            $candidates = $this->matchCustomersByAbbreviation($abbrev, $customers);
            if (count($candidates) === 1) {
                return ['resolved' => $candidates[0], 'candidates' => [], 'token' => null];
            }
            if (count($candidates) > 1) {
                // Reconstruct display form (e.g. "AC" → "A.C.")
                $displayToken = implode('.', str_split($abbrev)) . '.';
                return ['resolved' => null, 'candidates' => $candidates, 'token' => $displayToken];
            }
        }

        return ['resolved' => null, 'candidates' => [], 'token' => null];
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{id:int,name:string}>
     */
    private function loadAllCustomers(): \Illuminate\Support\Collection
    {
        return Site::query()
            ->with('customer:id,company_name')
            ->whereNotNull('customer_id')
            ->get()
            ->map(function (Site $site): ?array {
                $name = $site->customer?->company_name;
                if (!$site->customer || !$name) return null;
                return ['id' => (int) $site->customer_id, 'name' => (string) $name];
            })
            ->filter()
            ->unique('id')
            ->values();
    }

    /**
     * Detect dotted abbreviations in the original (non-normalized) query.
     * "A.C." → "AC", "S.E.A." → "SEA"
     *
     * @return array<int, string>
     */
    private function extractAbbreviations(string $originalQuery): array
    {
        preg_match_all('/\b(?:[A-Z]\.){2,}/u', $originalQuery, $m);
        return array_values(array_unique(
            array_map(fn ($a) => preg_replace('/\./', '', $a), $m[0] ?? [])
        ));
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array{id:int,name:string}>  $customers
     * @return array<int, array{id:int,name:string}>
     */
    private function matchCustomersByAbbreviation(string $abbrev, \Illuminate\Support\Collection $customers): array
    {
        $abbrevUpper = strtoupper($abbrev);
        return $customers->filter(function (array $customer) use ($abbrevUpper): bool {
            $parts    = preg_split('/\s+/', $customer['name']);
            $rawToken = $parts[0] ?? '';
            // Only dotted-abbreviation tokens qualify (e.g. "A.C.", "A.C.E.M.").
            // Plain words like "AC" are excluded to avoid false positives.
            if (!str_contains($rawToken, '.')) {
                return false;
            }
            $normalized = strtoupper(preg_replace('/\./', '', $rawToken));
            // Prefix match: "AC" matches "A.C." (exact) and "A.C.E.M." (starts with AC)
            return str_starts_with($normalized, $abbrevUpper);
        })->values()->all();
    }

    private function extractRadiusKm(string $text): ?float
    {
        if (!preg_match('/(\d{1,3})\s*km/ui', $text, $matches)) {
            return null;
        }

        return (float) $matches[1];
    }

    /**
     * @return array{risk_min:float|null, risk_max:float|null}
     */
    private function extractRiskRange(string $text): array
    {
        if (str_contains($text, 'critico')) {
            return ['risk_min' => 0.85, 'risk_max' => 1.0];
        }
        if (str_contains($text, 'alto')) {
            return ['risk_min' => 0.75, 'risk_max' => 0.85];
        }
        if (str_contains($text, 'medio')) {
            return ['risk_min' => 0.50, 'risk_max' => 0.75];
        }
        if (str_contains($text, 'basso')) {
            return ['risk_min' => 0.0, 'risk_max' => 0.50];
        }

        return ['risk_min' => null, 'risk_max' => null];
    }

    /**
     * @return array{days_to_next_pickup_min:int|null, days_to_next_pickup_max:int|null}
     */
    private function extractDaysToNextPickup(string $text): array
    {
        if (!preg_match('/\bentro\s+(\d{1,3})\s+giorn/ui', $text, $matches)) {
            return ['days_to_next_pickup_min' => null, 'days_to_next_pickup_max' => null];
        }

        return ['days_to_next_pickup_min' => null, 'days_to_next_pickup_max' => (int) $matches[1]];
    }

    private function extractLastWithdrawDaysMin(string $text): ?int
    {
        $trigger = '(oltre|più\s+di|piu\s+di|da\s+più\s+di|da\s+piu\s+di)';

        // "da oltre un mese" / "da più di un mese"
        if (preg_match('/\b' . $trigger . '\s+un\s+mes/ui', $text)) {
            return 30;
        }

        // "da oltre X mesi"
        if (preg_match('/\b' . $trigger . '\s+(\d+)\s+mes/ui', $text, $m)) {
            return (int) end($m) * 30;
        }

        // "da oltre X settimane"
        if (preg_match('/\b' . $trigger . '\s+(\d+)\s+settiman/ui', $text, $m)) {
            return (int) end($m) * 7;
        }

        // "da oltre X giorni"
        if (preg_match('/\b' . $trigger . '\s+(\d+)\s+giorn/ui', $text, $m)) {
            return (int) end($m);
        }

        return null;
    }

    private function extractHasBulk(string $text): ?bool
    {
        if (preg_match('/\bsfus[oa]\b|\bmateriale\s+sfuso\b|\bmateriali\s+sfusi\b/ui', $text)) {
            return true;
        }

        return null;
    }

    /**
     * Extracts explicit 6-digit CER codes from the text.
     *
     * @return array<int,string>|null
     */
    private function extractCerCodes(string $text): ?array
    {
        // Plain 6-digit codes: 102030
        preg_match_all('/\b(\d{6})\b/', $text, $plain);

        // Spaced format: 10 20 30
        preg_match_all('/\b(\d{2})\s+(\d{2})\s+(\d{2})\b/', $text, $spaced);

        $codes = $plain[1] ?? [];
        foreach (($spaced[1] ?? []) as $i => $p1) {
            $codes[] = $p1 . ($spaced[2][$i] ?? '') . ($spaced[3][$i] ?? '');
        }

        $codes = array_values(array_unique($codes));

        return !empty($codes) ? $codes : null;
    }

    private function extractCerKeyword(string $text): ?string
    {
        foreach (self::CER_KEYWORD_MAP as $keyword => $synonyms) {
            foreach ($synonyms as $synonym) {
                if (str_contains($text, $synonym)) {
                    return $keyword;
                }
            }
        }

        return null;
    }

    private function extractCerDangerous(string $text): ?bool
    {
        if (preg_match('/\b(veleno|veleni|tossic|nociv)\b/ui', $text)) {
            return true;
        }

        return null;
    }

    private function extractHasNoActiveOrders(string $text): ?bool
    {
        if (preg_match('/\b(senza\s+ordini|nessun\s+ordine|non\s+han+o\s+ordini|senza\s+ordini\s+(in\s+corso|aperti|attivi)|ordini\s+non\s+presenti)\b/ui', $text)) {
            return true;
        }

        return null;
    }

    private function extractHazardous(string $text): ?bool
    {
        if (str_contains($text, 'non pericol')) {
            return false;
        }
        if (str_contains($text, 'pericol')) {
            return true;
        }

        return null;
    }

    /**
     * @return array<int, string>|null
     */
    private function extractStatuses(string $text): ?array
    {
        $statuses = [];

        if (preg_match('/\b(richiest[ae]|aperto|aperti)\b/ui', $text)) {
            $statuses[] = 'requested';
        }
        if (str_contains($text, 'pianificat')) {
            $statuses[] = 'planned';
        }
        if (str_contains($text, 'eseguit')) {
            $statuses[] = 'executed';
        }
        if (str_contains($text, 'chius')) {
            $statuses[] = 'closed';
        }

        return empty($statuses) ? null : array_values(array_unique($statuses));
    }

    private function extractAbsoluteDate(string $text): ?string
    {
        if (preg_match('/\b(20\d{2}-\d{2}-\d{2})\b/ui', $text, $isoMatch)) {
            return $isoMatch[1];
        }

        if (preg_match('/\b(\d{1,2})\/(\d{1,2})\/(20\d{2})\b/ui', $text, $itMatch)) {
            $day   = str_pad($itMatch[1], 2, '0', STR_PAD_LEFT);
            $month = str_pad($itMatch[2], 2, '0', STR_PAD_LEFT);
            $year  = $itMatch[3];

            return "{$year}-{$month}-{$day}";
        }

        return null;
    }
}
