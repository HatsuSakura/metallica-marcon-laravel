<?php

namespace App\Services;

use App\Enums\OrderDocumentsStatus;
use App\Enums\OrderStatus;
use App\Jobs\GenerateOrderDocumentsJob;
use App\Models\Order;
use App\Support\OrderSpreadsheetTemplateFiller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class OrderDocumentGenerationService
{
    private const ORDER_XLSX_TEMPLATE_PATH = 'templates/orders/modulo-ordine-modello-no-formule.xlsx';
    private const GENERATING_STALE_TIMEOUT_MINUTES = 10;

    public function __construct(
        private readonly OrderSpreadsheetTemplateFiller $templateFiller
    ) {
    }

    public function enqueueGeneration(Order $order): void
    {
        $order->forceFill([
            'status' => OrderStatus::STATUS_CREATED->value,
            'documents_status' => OrderDocumentsStatus::GENERATING->value,
            'documents_error' => null,
        ])->save();

        GenerateOrderDocumentsJob::dispatch($order->id);
    }

    public function generate(Order $order): string
    {
        $snapshot = $order->load([
            'customer:id,company_name,legal_address',
            'customer.seller:id,name,surname,user_code',
            'site:id,name,address',
            'site.internalContacts:id,site_id,name,surname,phone,mobile,email,role',
            'logisticsUser:id,name,surname,email',
            'journey:id,driver_id,vehicle_id,trailer_id',
            'journey.driver:id,name,surname',
            'journey.vehicle:id,name,plate',
            'journey.trailer:id,name,plate',
            'items.cerCode:id,code,description,is_dangerous',
            'items.holder:id,name',
            'holders.holder:id,name',
        ]);

        $version = $this->resolveNextDocumentVersion($snapshot);
        $documentBaseName = $this->documentBaseName($snapshot);
        $basePath = "orders/documents/order-{$snapshot->id}";
        $filePath = "{$basePath}/manifest-v{$version}.json";
        $xlsxPath = "{$basePath}/{$documentBaseName}-v{$version}.xlsx";

        Storage::disk('local')->put($filePath, json_encode([
            'order_id' => $snapshot->id,
            'legacy_code' => $snapshot->legacy_code,
            'customer' => $snapshot->customer?->only(['id', 'company_name', 'legal_address']),
            'site' => $snapshot->site?->only(['id', 'name', 'address']),
            'status' => OrderStatus::fromMixed($snapshot->status)->value,
            'documents_version' => $version,
            'generated_at' => now()->toIso8601String(),
            'items' => $snapshot->items->map(fn ($item) => [
                'id' => $item->id,
                'cer_code' => $item->cerCode?->code,
                'cer_description' => $item->cerCode?->description,
                'description' => $item->description,
                'holder' => $item->holder?->name,
                'holder_quantity' => $item->holder_quantity,
                'weight_declared' => $item->weight_declared,
            ])->values()->all(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->writeTemplateXlsx($xlsxPath, $snapshot);
        $this->writeAdrHpPdfIfNeeded($basePath, $documentBaseName, $snapshot, $version);

        $currentStatus = OrderStatus::fromMixed($snapshot->status ?? OrderStatus::STATUS_CREATED->value);

        $nextStatus = in_array($currentStatus, [OrderStatus::STATUS_CREATED, OrderStatus::STATUS_READY, OrderStatus::STATUS_PLANNED], true)
            ? OrderStatus::STATUS_READY->value
            : $currentStatus->value;

        $snapshot->forceFill([
            'documents_status' => OrderDocumentsStatus::GENERATED->value,
            'documents_generated_at' => now(),
            'documents_error' => null,
            'documents_version' => $version,
            'status' => $nextStatus,
        ])->save();

        return $filePath;
    }

    public function markFailed(Order $order, string $error): void
    {
        $order->forceFill([
            'documents_status' => OrderDocumentsStatus::FAILED->value,
            'documents_error' => mb_substr($error, 0, 2000),
        ])->save();
    }

    public function invalidateAfterReadyOrderEdit(Order $order): void
    {
        $currentStatus = OrderStatus::fromMixed($order->status ?? OrderStatus::STATUS_CREATED->value);

        $documentsState = OrderDocumentsStatus::fromMixed($order->documents_status ?? OrderDocumentsStatus::NOT_GENERATED->value);

        $shouldInvalidate = in_array($currentStatus, [OrderStatus::STATUS_READY, OrderStatus::STATUS_PLANNED], true)
            || $documentsState === OrderDocumentsStatus::GENERATED;

        if (!$shouldInvalidate) {
            return;
        }

        $order->forceFill([
            'status' => OrderStatus::STATUS_CREATED->value,
            'documents_status' => OrderDocumentsStatus::NOT_GENERATED->value,
            'documents_generated_at' => null,
            'documents_error' => null,
        ])->save();
    }

    public function statusPayload(Order $order): array
    {
        return [
            'order_id' => $order->id,
            'order_status' => OrderStatus::fromMixed($order->status)->value,
            'documents_status' => OrderDocumentsStatus::fromMixed($order->documents_status)->value,
            'documents_generated_at' => $order->documents_generated_at,
            'documents_error' => $order->documents_error,
            'documents_version' => (int) ($order->documents_version ?? 0),
        ];
    }

    public function isGeneratingStateStale(Order $order): bool
    {
        $updatedAt = $order->updated_at;
        if (!$updatedAt instanceof \Illuminate\Support\Carbon) {
            return false;
        }

        return $updatedAt->lte(now()->subMinutes(self::GENERATING_STALE_TIMEOUT_MINUTES));
    }

    public function recoverStaleGeneratingState(Order $order): void
    {
        $order->forceFill([
            'documents_status' => OrderDocumentsStatus::NOT_GENERATED->value,
            'documents_error' => null,
        ])->save();
    }

    public function listDocuments(Order $order): array
    {
        $disk = Storage::disk('local');
        $basePath = $this->documentsBasePath($order);
        $allowedExtensions = ['xlsx', 'pdf'];

        if (!$disk->exists($basePath)) {
            return [];
        }

        $files = $disk->files($basePath);
        $latestByTemplate = [];
        $baseName = $this->documentBaseName($order);

        foreach ($files as $filePath) {
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            if (!in_array($extension, $allowedExtensions, true)) {
                continue;
            }

            $name = basename($filePath);
            if (!preg_match('/^' . preg_quote($baseName, '/') . '(?:-adr-hp)?-v\d+\.(xlsx|pdf)$/i', $name)) {
                continue;
            }
            $templateKey = preg_replace('/-v\d+(?=\.[^.]+$)/i', '', $name) ?? $name;

            $document = [
                'name' => basename($filePath),
                'path' => $filePath,
                'size' => (int) $disk->size($filePath),
                'last_modified' => $disk->lastModified($filePath),
                'extension' => $extension,
            ];

            $current = $latestByTemplate[$templateKey] ?? null;
            if ($current === null || ($document['last_modified'] > $current['last_modified'])) {
                $latestByTemplate[$templateKey] = $document;
            }
        }

        $documents = array_values($latestByTemplate);
        usort($documents, fn (array $a, array $b) => $b['last_modified'] <=> $a['last_modified']);

        return $documents;
    }

    public function findDocumentPathByName(Order $order, string $fileName): ?string
    {
        $safeFileName = basename($fileName);
        $candidate = $this->documentsBasePath($order) . '/' . $safeFileName;

        return Storage::disk('local')->exists($candidate) ? $candidate : null;
    }

    private function documentsBasePath(Order $order): string
    {
        return "orders/documents/order-{$order->id}";
    }

    private function resolveNextDocumentVersion(Order $order): int
    {
        $dbVersion = (int) ($order->documents_version ?? 0);
        $maxExistingVersion = 0;

        $basePath = $this->documentsBasePath($order);
        $disk = Storage::disk('local');
        if ($disk->exists($basePath)) {
            foreach ($disk->files($basePath) as $filePath) {
                $name = basename($filePath);
                if (preg_match('/-v(\d+)\.(xlsx|pdf)$/i', $name, $matches) !== 1) {
                    continue;
                }

                $version = (int) $matches[1];
                if ($version > $maxExistingVersion) {
                    $maxExistingVersion = $version;
                }
            }
        }

        return max($dbVersion, $maxExistingVersion) + 1;
    }

    private function writeTemplateXlsx(string $xlsxPath, Order $order): void
    {
        $disk = Storage::disk('local');
        $dir = dirname($xlsxPath);
        if (!$disk->exists($dir)) {
            $disk->makeDirectory($dir);
        }

        $templatePath = resource_path(self::ORDER_XLSX_TEMPLATE_PATH);
        if (!is_file($templatePath)) {
            throw new \RuntimeException("Template XLSX non trovato: {$templatePath}");
        }

        $contents = file_get_contents($templatePath);
        if ($contents === false) {
            throw new \RuntimeException("Impossibile leggere il template XLSX: {$templatePath}");
        }

        $disk->put($xlsxPath, $contents);

        $absolutePath = storage_path('app/' . ltrim($xlsxPath, '/'));
        $this->templateFiller->fill($absolutePath, $order);
    }

    private function writeAdrHpPdfIfNeeded(string $basePath, string $baseName, Order $order, int $version): void
    {
        $qualifyingItems = $this->qualifyingAdrHpItems($order);

        if ($qualifyingItems->isEmpty()) {
            return;
        }

        $legacyCode = trim((string) ($order->legacy_code ?? ''));
        $safeLegacyCode = htmlspecialchars($legacyCode !== '' ? $legacyCode : (string) $order->id, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $customerName = htmlspecialchars((string) ($order->customer?->company_name ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $withdrawAt = $order->fixed_withdraw_at ?? $order->expected_withdraw_at;
        $withdrawLabel = $withdrawAt instanceof Carbon
            ? $withdrawAt->format('d/m/Y H:i')
            : ($withdrawAt ? Carbon::parse($withdrawAt)->format('d/m/Y H:i') : '-');
        $safeWithdrawLabel = htmlspecialchars($withdrawLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $logoDataUri = $this->logoDataUri();

        $pages = $qualifyingItems->map(function ($item) use ($safeLegacyCode, $customerName, $safeWithdrawLabel, $logoDataUri) {
            $hpValue = trim((string) ($item->adr_hp ?? ''));
            $adrValue = $this->adrLabelForPdf($item);
            $hpLabel = $hpValue !== '' ? htmlspecialchars($hpValue, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : 'N/D';
            $cerCode = htmlspecialchars((string) ($item->cerCode?->code ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $cerDescription = htmlspecialchars((string) ($item->cerCode?->description ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $logoHtml = $logoDataUri
                ? '<img src="' . $logoDataUri . '" alt="Logo aziendale" style="width: 222px; max-width: 100%; height: auto;">'
                : '<div style="font-size: 24px; font-weight: 700;">METALLICA MARCON</div>';

            return '<div style="page-break-after: always; width: 100%; font-family: Arial, sans-serif; color: #111827;">'
                . '<div style="display: table; width: 100%; margin-bottom: 22px;">'
                . '<div style="display: table-cell; width: 62%; vertical-align: top;">' . $logoHtml . '</div>'
                . '<div style="display: table-cell; width: 38%; text-align: right; vertical-align: top;">'
                . '<div style="font-size: 32px; font-weight: 700; letter-spacing: 0.4px;">ETICHETTA</div>'
                . '<div style="font-size: 32px; font-weight: 700; margin-top: 4px; line-height: 1;">' . $safeLegacyCode . '</div>'
                . '</div>'
                . '</div>'
                . '<div style="padding: 0;">'
                . $this->pdfFieldRowHtml('Cliente', $customerName, 24)
                . $this->pdfFieldRowHtml('Data ritiro', $safeWithdrawLabel, 24)
                . $this->pdfFieldRowHtml('Codice CER', $cerCode, 24)
                . $this->pdfFieldRowHtml('Tipo', $cerDescription, 24, true)
                . '<div style="display: table; width: 100%; margin-bottom: 16px;">'
                . '<div style="display: table-cell; width: 50%; padding-right: 8px;">' . $this->pdfFieldRowHtml('HP', $hpLabel, 24) . '</div>'
                . '<div style="display: table-cell; width: 50%; padding-left: 8px;">' . $this->pdfFieldRowHtml('ADR', $adrValue, 24) . '</div>'
                . '</div>'
                . '<div style="display: table; width: 100%; margin-bottom: 16px;">'
                . '<div style="display: table-cell; width: 50%; padding-right: 8px;">' . $this->pdfBlankFieldHtml('Peso Lordo', 70) . '</div>'
                . '<div style="display: table-cell; width: 50%; padding-left: 8px;">' . $this->pdfBlankFieldHtml('Tara', 70) . '</div>'
                . '</div>'
                . '<div style="display: table; width: 100%; margin-bottom: 16px;">'
                . '<div style="display: table-cell; width: 50%; padding-right: 8px;">' . $this->pdfBlankFieldHtml('Lotto', 70) . '</div>'
                . '<div style="display: table-cell; width: 50%; padding-left: 8px;">' . $this->pdfBlankFieldHtml('Volume', 70) . '</div>'
                . '</div>'
                . $this->pdfBlankFieldHtml('Operatore', 40)
                . '</div>'
                . '</div>';
        })->all();

        if (!empty($pages)) {
            $lastIndex = count($pages) - 1;
            $pages[$lastIndex] = preg_replace('/page-break-after:\s*always;?\s*/i', '', $pages[$lastIndex], 1) ?? $pages[$lastIndex];
        }

        $html = '<html><body style="margin: 0; padding: 24px 16px 16px 16px;">'
            . implode('', $pages)
            . '</body></html>';

        $pdf = app('dompdf.wrapper');
        $pdf->loadHTML($html)->setPaper('a4');
        $pdfPath = "{$basePath}/{$baseName}-adr-hp-v{$version}.pdf";
        Storage::disk('local')->put($pdfPath, $pdf->output());
    }

    private function documentBaseName(Order $order): string
    {
        $legacyCode = trim((string) ($order->legacy_code ?? ''));
        if ($legacyCode === '') {
            return "order-{$order->id}";
        }

        $safe = preg_replace('/[^A-Za-z0-9_-]+/', '-', $legacyCode) ?? '';
        $safe = trim($safe, '-');
        if ($safe === '') {
            return "order-{$order->id}";
        }

        return "order-{$safe}";
    }

    private function requiresAdrHpDocument(Order $order): bool
    {
        return $this->qualifyingAdrHpItems($order)->isNotEmpty();
    }

    private function qualifyingAdrHpItems(Order $order): \Illuminate\Support\Collection
    {
        return $order->items->filter(function ($item): bool {
            $adrEnabled = (bool) ($item->has_adr ?? $item->adr ?? false);
            $hasHpCode = trim((string) ($item->adr_hp ?? '')) !== '';

            return $adrEnabled || $hasHpCode;
        })->values();
    }

    private function logoDataUri(): ?string
    {
        $absolutePath = storage_path('app/logo.png');
        if (!is_file($absolutePath)) {
            return null;
        }

        $contents = file_get_contents($absolutePath);
        if ($contents === false) {
            return null;
        }

        return 'data:image/png;base64,' . base64_encode($contents);
    }

    private function adrLabelForPdf(object $item): string
    {
        $hasAdr = (bool) ($item->has_adr ?? $item->adr ?? false);
        if (!$hasAdr) {
            return 'NO';
        }

        $isTotal = (bool) ($item->is_adr_total ?? false);
        $hasTotalExemption = (bool) ($item->has_adr_total_exemption ?? false);
        $hasPartialExemption = (bool) ($item->has_adr_partial_exemption ?? false);

        if ($isTotal) {
            return 'Totale';
        }

        if ($hasTotalExemption) {
            return 'Esenzione totale';
        }

        if ($hasPartialExemption) {
            return 'Esenzione parziale';
        }

        return 'ADR';
    }

    private function pdfFieldRowHtml(string $label, string $value, int $valueFontSize = 20, bool $allowWrap = false): string
    {
        $whiteSpace = $allowWrap ? 'normal' : 'nowrap';
        $lineHeight = $allowWrap ? '1.0' : '1.1';

        return '<div style="margin-bottom: 12px;">'
            . '<div style="font-size: 16px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: #374151; margin-bottom: 4px;">' . $label . '</div>'
            . '<div style="border: 2px solid #111827; min-height: 44px; padding: 10px 14px; font-size: ' . $valueFontSize . 'px; font-weight: 700; white-space: ' . $whiteSpace . '; line-height: ' . $lineHeight . ';">' . $value . '</div>'
            . '</div>';
    }

    private function pdfBlankFieldHtml(string $label, int $height = 82): string
    {
        return '<div>'
            . '<div style="font-size: 16px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: #374151; margin-bottom: 4px;">' . $label . '</div>'
            . '<div style="border: 2px solid #111827; height: ' . $height . 'px; padding: 0 14px;"></div>'
            . '</div>';
    }
}

