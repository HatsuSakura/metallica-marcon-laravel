<?php

namespace App\Services\Nlp\Providers;

use Illuminate\Support\Facades\Http;

class LlmNlpProvider implements NlpProvider
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     *
     * @throws \RuntimeException on API error or non-JSON response
     */
    public function parseLogistics(string $query, array $context = []): array
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('OPENAI_API_KEY not configured.');
        }

        $response = Http::withToken($this->apiKey)
            ->timeout(30)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model'           => $this->model,
                'response_format' => ['type' => 'json_object'],
                'temperature'     => 0.1,
                'max_tokens'      => 1200,
                'messages'        => [
                    ['role' => 'system', 'content' => $this->buildSystemPrompt()],
                    ['role' => 'user',   'content' => $query],
                ],
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('OpenAI API error: HTTP ' . $response->status());
        }

        $content = $response->json('choices.0.message.content');
        $decoded = json_decode((string) $content, true);

        if (! is_array($decoded)) {
            throw new \RuntimeException('LLM returned non-JSON or unparsable response.');
        }

        // Extract model used for logging
        $decoded['_model_used'] = $this->model;

        return $decoded;
    }

    private function buildSystemPrompt(): string
    {
        $today = now()->toDateString();

        return <<<PROMPT
Sei un parser NLP per una piattaforma logistica italiana di gestione ritiri rifiuti industriali.
Data odierna: {$today}

Il tuo compito: analizzare la query dell'utente e restituire SOLO un JSON valido che rispetta lo schema LogisticsQuery v1.
Nessun testo aggiuntivo. Nessuna spiegazione. Solo JSON puro.

--- SCHEMA LogisticsQuery v1 ---
{
  "scenario": "planning_sites|order_requests|hybrid",       // OBBLIGATORIO
  "reference": {
    "customer": { "id": null, "name": null },               // cliente come riferimento geografico
    "site":     { "id": null, "name": null },               // sede specifica
    "coordinates": { "lat": null, "lng": null }             // lat/lng esplicite
  },
  "geo": {
    "origin": "customer|site|coordinates",                  // OBBLIGATORIO
    "radius_km": null                                       // raggio in km (0-500)
  },
  "time": {
    "target_date": null,   // Y-m-d
    "from": null,          // Y-m-d
    "to": null             // Y-m-d
  },
  "site_filters": {
    "risk_min": null,                    // 0.0-1.0
    "risk_max": null,                    // 0.0-1.0
    "days_to_next_pickup_min": null,     // intero
    "days_to_next_pickup_max": null,     // intero
    "last_withdraw_days_min": null,      // giorni dall'ultimo ritiro
    "has_no_active_orders": null,        // boolean
    "customer_ids": [],                  // filtra per ID cliente specifico
    "exclude_customer_ids": []
  },
  "order_filters": {
    "statuses": [],          // requested|planned|executed|closed
    "hazardous": null,       // boolean — materiali pericolosi ADR
    "has_bulk": null,        // boolean — materiale sfuso
    "cer_codes": [],         // codici CER a 6 cifre
    "cer_keyword": null,     // metall|plast|legno|batter|aerosol|acid|olio|carta|vetro|gomma
    "cer_dangerous": null,   // boolean
    "min_weight_kg": null,
    "max_weight_kg": null,
    "requested_from": null,  // Y-m-d
    "requested_to": null     // Y-m-d
  },
  "sort": {
    "mode": "distance|risk|urgency|mixed",  // OBBLIGATORIO
    "weights": {                            // solo se mode=mixed, somma=1.0
      "distance": null,
      "risk": null,
      "urgency": null
    }
  },
  "limit": {
    "sites": 200,   // OBBLIGATORIO (default 200, max 500)
    "orders": 200   // OBBLIGATORIO (default 200, max 500)
  },
  "_confidence": {
    "level": "alta|media|bassa",
    "issues": []    // lista stringhe in italiano — parametri ambigui o non riconosciuti
  }
}

--- REGOLE ---
- scenario "planning_sites": l'utente vuole trovare sedi da visitare per ritirare rifiuti.
- scenario "order_requests": l'utente vuole trovare ordini aperti/richieste di ritiro.
- scenario "hybrid": entrambi.
- geo.origin è sempre obbligatorio. Se non è specificato un cliente/sede, usa "coordinates" con lat/lng null.
- sort.mode default: "distance" se non specificato.
- limit.sites e limit.orders: usa 200 come default se non specificati.
- Date relative ("ieri", "la settimana scorsa", "entro venerdì"): calcola rispetto alla data odierna {$today}.
- _confidence.level = "alta" se hai identificato 4+ parametri significativi, "media" per 2-3, "bassa" per 0-1.
- Restituisci null per i campi non applicabili, non stringhe vuote o array vuoti (eccetto dove indicato).
- Non inventare ID clienti/sedi — lascia id=null se non noto con certezza.
PROMPT;
    }
}
