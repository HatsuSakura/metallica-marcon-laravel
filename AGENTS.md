# Metallica Marcon — Agent Entry Point

Use the global `developer-platform` skill for workstation conventions,
knowledge recovery/promotion, secrets and KB publication. Its maintained source
is `~/developer-platform/Skills/developer-platform/SKILL.md`.

Start project research from `docs/README.md` and `docs/development-status.md`.
Recovered notes under `docs/recovery/legacy-obsidian/` are historical evidence,
not current instructions. Consult `.claude/rules/` and `.ai/` as relevant to
the task; preserve the deterministic domain and validated-JSON NLP boundaries.

## Implementation and UI conventions

Keep changes organic and modular: implement shared application behavior in
shared layouts/components, not page-name conditionals or duplicated per-page
patches. In particular, application navigation and responsive headers belong
to the common layout and must work consistently across routes. Build mobile-first
while preserving desktop usability; keep presentation components separate from
domain mutations and reuse existing contracts. Check affected consumers when
changing shared behavior.

Use DaisyUI theme tokens (`primary`, `secondary`, `success`, `warning`, `info`,
`error`, `neutral`, `base-*`), never arbitrary colors. The same information
must use the same semantic tone across screens. Driver journey/stop statuses
use `JourneyStatusBadge`: in progress and completed are `success`, skipped is
`warning`, planned/to-start are solid neutral, cancelled/unknown are neutral outline. A positive journey
count uses `success`; zero is neutral. Keep this mapping centralized.

YouTrack project key is GLE. Use the configured native YouTrack MCP for current
issue state. Do not equate code presence, a resolved issue and a production
release; record discrepancies before proposing tracker changes.

Keep complete evidence in this repository and promote sanitized, attributed
syntheses to `~/knowledge/projects/personal/metallica-marcon`. Shared/domain
promotion requires the appropriate scope and sanitization. Never commit or
index credentials, raw recovery archives, database dumps or personal data.

### Compattezza e riconoscibilità dei comandi mobile

Privilegiare il poco consumo di spazio verticale, collocando le azioni sulla
stessa riga del contenuto quando rimane leggibile. I pulsanti con sola icona
devono essere riconoscibili anche senza hover: usare forme e bordi visibili
(per esempio `btn-circle btn-outline`), non `btn-ghost` come unica indicazione
di interattività. Mantenere un'area di tocco adeguata e un nome accessibile.

I messaggi di esito devono descrivere l'operazione per l'utente, includendo
identificativi utili come il CER quando disponibili. Non spiegare nel feedback
le dinamiche interne (per esempio baseline conservata o operazione auditata).
Il feedback del viaggio è fixed in alto sotto l'header, con chiusura evidente.
Per i riepiloghi di contenitori in ritiro/consegna usare il componente condiviso
`JourneyContainerQuantities`: tessere flex con numero più grande sopra e tipo
sotto, centrate. Conservare i valori reali e la distinzione zero/assente/sfuso.
