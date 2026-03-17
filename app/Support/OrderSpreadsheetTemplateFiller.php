<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Order;
use App\Models\OrderHolder;
use App\Models\OrderItem;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Carbon;
use ZipArchive;

class OrderSpreadsheetTemplateFiller
{
    private const SHEET_XML_PATH = 'xl/worksheets/sheet1.xml';
    private const ITEM_FIRST_ROW = 23;
    private const ITEM_LAST_ROW = 44;
    private const BOTTOM_SECTION_FIRST_ROW = 45;
    private const NOTE_ROW = 47;
    private const TEMPLATE_ITEM_STYLE_ROW = 44;

    public function fill(string $xlsxAbsolutePath, Order $order): void
    {
        $zip = new ZipArchive();
        if ($zip->open($xlsxAbsolutePath) !== true) {
            throw new \RuntimeException("Impossibile aprire XLSX: {$xlsxAbsolutePath}");
        }

        $sheetXml = $zip->getFromName(self::SHEET_XML_PATH);
        if ($sheetXml === false) {
            $zip->close();
            throw new \RuntimeException('Impossibile leggere worksheet del template Modello.');
        }

        $updatedSheetXml = $this->fillSheetXml($sheetXml, $order);

        $zip->deleteName(self::SHEET_XML_PATH);
        $zip->addFromString(self::SHEET_XML_PATH, $updatedSheetXml);
        $zip->close();
    }

    private function fillSheetXml(string $sheetXml, Order $order): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        if (!$dom->loadXML($sheetXml)) {
            throw new \RuntimeException('Worksheet XML non valido.');
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        $sheetData = $xpath->query('/x:worksheet/x:sheetData')->item(0);
        if (!$sheetData instanceof DOMElement) {
            throw new \RuntimeException('sheetData non trovato nel template.');
        }

        $extraRows = $this->ensureItemCapacity($dom, $xpath, (int) $order->items->count());
        $lastItemRow = self::ITEM_LAST_ROW + $extraRows;
        $noteRow = self::NOTE_ROW + $extraRows;

        [$customerCity, $customerProvince] = $this->extractCityProvince((string) ($order->customer?->legal_address ?? ''));
        [$siteCity, $siteProvince] = $this->extractCityProvince((string) ($order->site?->address ?? ''));

        $this->setTextCell($dom, $xpath, $sheetData, 'M1', $this->formatDate($order->requested_at));
        $this->setTextCell($dom, $xpath, $sheetData, 'M2', (string) ($order->legacy_code ?? ''));
        $this->setTextCell($dom, $xpath, $sheetData, 'M3', $this->fullName($order->logisticsUser?->name, $order->logisticsUser?->surname));
        $this->setTextCell($dom, $xpath, $sheetData, 'M4', $this->fullName($order->journey?->driver?->name, $order->journey?->driver?->surname));
        $this->setTextCell($dom, $xpath, $sheetData, 'M5', $this->vehicleLabel($order));
        $this->setTextCell($dom, $xpath, $sheetData, 'M6', $this->trailerLabel($order));

        $this->setTextCell($dom, $xpath, $sheetData, 'B8', (string) ($order->customer?->company_name ?? ''));
        $this->setTextCell($dom, $xpath, $sheetData, 'I8', (string) ($order->site?->name ?? ''));
        $this->setTextCell($dom, $xpath, $sheetData, 'B9', $customerProvince);
        $this->setTextCell($dom, $xpath, $sheetData, 'I9', $siteProvince);
        $this->setTextCell($dom, $xpath, $sheetData, 'B10', $customerCity);
        $this->setTextCell($dom, $xpath, $sheetData, 'I10', $siteCity);
        $this->setTextCell($dom, $xpath, $sheetData, 'B11', (string) ($order->customer?->legal_address ?? ''));
        $this->setTextCell($dom, $xpath, $sheetData, 'I11', (string) ($order->site?->address ?? ''));

        $contact = $order->site?->internalContacts?->first();
        $contactLabel = $contact
            ? trim(implode(' ', array_filter([
                $contact->name ?? null,
                $contact->surname ?? null,
                $contact->phone ?? null,
                $contact->mobile ?? null,
            ])))
            : '';
        $this->setTextCell($dom, $xpath, $sheetData, 'B13', $contactLabel);
        $this->setTextCell($dom, $xpath, $sheetData, 'J13', (string) ($order->customer?->sales_email ?? ''));

        $this->fillContainersSummary($dom, $xpath, $sheetData, $order);
        $this->fillItemsTable($dom, $xpath, $sheetData, $order, $lastItemRow);

        $this->setTextCell($dom, $xpath, $sheetData, 'B' . $noteRow, (string) ($order->notes ?? ''));

        return $dom->saveXML() ?: $sheetXml;
    }

    private function fillContainersSummary(DOMDocument $dom, DOMXPath $xpath, DOMElement $sheetData, Order $order): void
    {
        foreach (range('B', 'L') as $col) {
            $this->clearCell($dom, $xpath, $sheetData, $col . '17');
            $this->clearCell($dom, $xpath, $sheetData, $col . '18');
            $this->clearCell($dom, $xpath, $sheetData, $col . '19');
        }

        $byColumn = [];
        foreach ($order->holders as $holderRow) {
            if (!$holderRow instanceof OrderHolder) {
                continue;
            }

            $col = $this->holderColumn((string) ($holderRow->holder?->name ?? ''));
            if (!isset($byColumn[$col])) {
                $byColumn[$col] = ['empty' => 0, 'filled' => 0, 'total' => 0];
            }

            $byColumn[$col]['empty'] += (int) ($holderRow->empty_holders_count ?? 0);
            $byColumn[$col]['filled'] += (int) ($holderRow->filled_holders_count ?? 0);
            $byColumn[$col]['total'] += (int) ($holderRow->total_holders_count ?? 0);
        }

        foreach ($byColumn as $col => $counts) {
            $this->setNumberCell($dom, $xpath, $sheetData, $col . '17', $counts['empty']);
            $this->setNumberCell($dom, $xpath, $sheetData, $col . '18', $counts['filled']);
            $this->setNumberCell($dom, $xpath, $sheetData, $col . '19', $counts['total']);
        }
    }

    private function fillItemsTable(
        DOMDocument $dom,
        DOMXPath $xpath,
        DOMElement $sheetData,
        Order $order,
        int $lastItemRow
    ): void
    {
        for ($row = self::ITEM_FIRST_ROW; $row <= $lastItemRow; $row++) {
            $this->clearCell($dom, $xpath, $sheetData, 'A' . $row);
            $this->clearCell($dom, $xpath, $sheetData, 'B' . $row);
            $this->clearCell($dom, $xpath, $sheetData, 'C' . $row);
            $this->clearCell($dom, $xpath, $sheetData, 'E' . $row);
            $this->clearCell($dom, $xpath, $sheetData, 'K' . $row);
            $this->clearCell($dom, $xpath, $sheetData, 'M' . $row);
        }

        $maxRows = $lastItemRow - self::ITEM_FIRST_ROW + 1;
        $items = $order->items->take($maxRows)->values();

        foreach ($items as $index => $item) {
            if (!$item instanceof OrderItem) {
                continue;
            }

            $row = self::ITEM_FIRST_ROW + $index;
            $holderLabel = (bool) ($item->is_bulk ?? false)
                ? 'SFUSO'
                : (string) ($item->holder?->name ?? '');
            $description = (string) ($item->description ?? $item->cerCode?->description ?? '');

            $this->setTextCell($dom, $xpath, $sheetData, 'A' . $row, (string) ($item->cerCode?->code ?? ''));
            $this->setNumberCell($dom, $xpath, $sheetData, 'B' . $row, (float) ($item->holder_quantity ?? 0));
            $this->setTextCell($dom, $xpath, $sheetData, 'C' . $row, $holderLabel);
            $this->setTextCell($dom, $xpath, $sheetData, 'E' . $row, $description);
            $this->setNumberCell($dom, $xpath, $sheetData, 'K' . $row, (float) ($item->weight_declared ?? 0));
        }
    }

    private function ensureItemCapacity(DOMDocument $dom, DOMXPath $xpath, int $itemsCount): int
    {
        $baseCapacity = self::ITEM_LAST_ROW - self::ITEM_FIRST_ROW + 1;
        $extraRows = max(0, $itemsCount - $baseCapacity);
        if ($extraRows === 0) {
            return 0;
        }

        $this->shiftRows($xpath, self::BOTTOM_SECTION_FIRST_ROW, $extraRows);
        $this->shiftMergeRefs($xpath, self::BOTTOM_SECTION_FIRST_ROW, $extraRows);
        $this->insertItemRows($dom, $xpath, $extraRows);
        $this->appendItemRowMerges($dom, $xpath, $extraRows);
        $this->updateDimension($xpath, $extraRows);

        return $extraRows;
    }

    private function shiftRows(DOMXPath $xpath, int $fromRow, int $offset): void
    {
        $rows = $xpath->query('/x:worksheet/x:sheetData/x:row');
        if ($rows === false) {
            return;
        }

        $rowNodes = [];
        foreach ($rows as $rowNode) {
            if ($rowNode instanceof DOMElement) {
                $rowNodes[] = $rowNode;
            }
        }

        foreach ($rowNodes as $rowNode) {
            $rowNumber = (int) ($rowNode->getAttribute('r') ?: 0);
            if ($rowNumber < $fromRow) {
                continue;
            }

            $newRow = $rowNumber + $offset;
            $rowNode->setAttribute('r', (string) $newRow);

            foreach ($rowNode->childNodes as $child) {
                if (!$child instanceof DOMElement || $child->tagName !== 'c') {
                    continue;
                }

                $ref = $child->getAttribute('r');
                if ($ref === '') {
                    continue;
                }

                if (!preg_match('/^([A-Z]+)(\d+)$/', $ref, $m)) {
                    continue;
                }

                $child->setAttribute('r', $m[1] . ($newRow));
            }
        }
    }

    private function shiftMergeRefs(DOMXPath $xpath, int $fromRow, int $offset): void
    {
        $mergeCells = $xpath->query('/x:worksheet/x:mergeCells/x:mergeCell');
        if ($mergeCells === false) {
            return;
        }

        foreach ($mergeCells as $merge) {
            if (!$merge instanceof DOMElement) {
                continue;
            }

            $ref = $merge->getAttribute('ref');
            if ($ref === '') {
                continue;
            }

            [$aCol, $aRow, $bCol, $bRow] = $this->splitRangeRef($ref);

            if ($aRow >= $fromRow) {
                $aRow += $offset;
            }
            if ($bRow >= $fromRow) {
                $bRow += $offset;
            }

            $merge->setAttribute('ref', $this->buildRangeRef($aCol, $aRow, $bCol, $bRow));
        }
    }

    private function insertItemRows(DOMDocument $dom, DOMXPath $xpath, int $extraRows): void
    {
        $sheetData = $xpath->query('/x:worksheet/x:sheetData')->item(0);
        if (!$sheetData instanceof DOMElement) {
            return;
        }

        $templateRow = $xpath->query('/x:worksheet/x:sheetData/x:row[@r="' . self::TEMPLATE_ITEM_STYLE_ROW . '"]')->item(0);
        if (!$templateRow instanceof DOMElement) {
            return;
        }

        $insertionPoint = $xpath->query(
            '/x:worksheet/x:sheetData/x:row[@r="' . (self::BOTTOM_SECTION_FIRST_ROW + $extraRows) . '"]'
        )->item(0);

        for ($i = 1; $i <= $extraRows; $i++) {
            $newRowNumber = self::ITEM_LAST_ROW + $i;
            $newRow = $templateRow->cloneNode(true);
            if (!$newRow instanceof DOMElement) {
                continue;
            }

            $newRow->setAttribute('r', (string) $newRowNumber);

            foreach ($newRow->childNodes as $cellNode) {
                if (!$cellNode instanceof DOMElement || $cellNode->tagName !== 'c') {
                    continue;
                }

                $ref = $cellNode->getAttribute('r');
                if (preg_match('/^([A-Z]+)(\d+)$/', $ref, $m)) {
                    $cellNode->setAttribute('r', $m[1] . $newRowNumber);
                }

                $this->removeCellChildren($cellNode);
                $cellNode->removeAttribute('t');
            }

            if ($insertionPoint instanceof DOMElement) {
                $sheetData->insertBefore($newRow, $insertionPoint);
            } else {
                $sheetData->appendChild($newRow);
            }
        }
    }

    private function appendItemRowMerges(DOMDocument $dom, DOMXPath $xpath, int $extraRows): void
    {
        $mergeContainer = $xpath->query('/x:worksheet/x:mergeCells')->item(0);
        if (!$mergeContainer instanceof DOMElement) {
            return;
        }

        $templateMerges = $xpath->query('/x:worksheet/x:mergeCells/x:mergeCell');
        if ($templateMerges === false) {
            return;
        }

        $patterns = [];
        foreach ($templateMerges as $merge) {
            if (!$merge instanceof DOMElement) {
                continue;
            }
            $ref = $merge->getAttribute('ref');
            if ($ref === '') {
                continue;
            }
            [$aCol, $aRow, $bCol, $bRow] = $this->splitRangeRef($ref);
            if ($aRow === self::TEMPLATE_ITEM_STYLE_ROW && $bRow === self::TEMPLATE_ITEM_STYLE_ROW) {
                $patterns[] = [$aCol, $bCol];
            }
        }

        foreach ($patterns as [$aCol, $bCol]) {
            for ($i = 1; $i <= $extraRows; $i++) {
                $row = self::ITEM_LAST_ROW + $i;
                $merge = $dom->createElementNS($mergeContainer->namespaceURI, 'mergeCell');
                $merge->setAttribute('ref', $this->buildRangeRef($aCol, $row, $bCol, $row));
                $mergeContainer->appendChild($merge);
            }
        }

        $newCount = $xpath->query('/x:worksheet/x:mergeCells/x:mergeCell')->length;
        $mergeContainer->setAttribute('count', (string) $newCount);
    }

    private function updateDimension(DOMXPath $xpath, int $extraRows): void
    {
        $dimension = $xpath->query('/x:worksheet/x:dimension')->item(0);
        if (!$dimension instanceof DOMElement) {
            return;
        }

        $ref = $dimension->getAttribute('ref');
        if (!preg_match('/^([A-Z]+\d+):([A-Z]+)(\d+)$/', $ref, $m)) {
            return;
        }

        $dimension->setAttribute('ref', $m[1] . ':' . $m[2] . ((int) $m[3] + $extraRows));
    }

    private function setTextCell(
        DOMDocument $dom,
        DOMXPath $xpath,
        DOMElement $sheetData,
        string $cellRef,
        string $value
    ): void {
        $cell = $this->getOrCreateCell($dom, $xpath, $sheetData, $cellRef);
        $this->removeCellChildren($cell);
        if ($value === '') {
            $cell->removeAttribute('t');
            return;
        }

        $cell->setAttribute('t', 'inlineStr');
        $is = $dom->createElementNS($cell->namespaceURI, 'is');
        $t = $dom->createElementNS($cell->namespaceURI, 't');
        $t->appendChild($dom->createTextNode($value));
        $is->appendChild($t);
        $cell->appendChild($is);
    }

    private function setNumberCell(
        DOMDocument $dom,
        DOMXPath $xpath,
        DOMElement $sheetData,
        string $cellRef,
        int|float $value
    ): void {
        if ($value === 0 || $value === 0.0) {
            $this->clearCell($dom, $xpath, $sheetData, $cellRef);
            return;
        }

        $cell = $this->getOrCreateCell($dom, $xpath, $sheetData, $cellRef);
        $this->removeCellChildren($cell);
        $cell->removeAttribute('t');
        $v = $dom->createElementNS($cell->namespaceURI, 'v', (string) $value);
        $cell->appendChild($v);
    }

    private function clearCell(
        DOMDocument $dom,
        DOMXPath $xpath,
        DOMElement $sheetData,
        string $cellRef
    ): void {
        $cell = $this->getOrCreateCell($dom, $xpath, $sheetData, $cellRef);
        $this->removeCellChildren($cell);
        $cell->removeAttribute('t');
    }

    private function getOrCreateCell(
        DOMDocument $dom,
        DOMXPath $xpath,
        DOMElement $sheetData,
        string $cellRef
    ): DOMElement {
        $cell = $xpath->query("//x:c[@r='{$cellRef}']")->item(0);
        if ($cell instanceof DOMElement) {
            return $cell;
        }

        [, $row] = $this->splitCellRef($cellRef);
        $rowNode = $xpath->query("//x:row[@r='{$row}']")->item(0);
        if (!$rowNode instanceof DOMElement) {
            $rowNode = $dom->createElementNS($sheetData->namespaceURI, 'row');
            $rowNode->setAttribute('r', (string) $row);
            $sheetData->appendChild($rowNode);
        }

        $newCell = $dom->createElementNS($sheetData->namespaceURI, 'c');
        $newCell->setAttribute('r', $cellRef);
        $rowNode->appendChild($newCell);

        return $newCell;
    }

    private function removeCellChildren(DOMElement $cell): void
    {
        while ($cell->firstChild !== null) {
            $cell->removeChild($cell->firstChild);
        }
    }

    private function splitCellRef(string $cellRef): array
    {
        if (!preg_match('/^([A-Z]+)(\d+)$/', $cellRef, $matches)) {
            throw new \InvalidArgumentException("Riferimento cella non valido: {$cellRef}");
        }

        return [$matches[1], (int) $matches[2]];
    }

    private function splitRangeRef(string $ref): array
    {
        if (str_contains($ref, ':')) {
            [$a, $b] = explode(':', $ref, 2);
        } else {
            $a = $ref;
            $b = $ref;
        }

        [$aCol, $aRow] = $this->splitCellRef($a);
        [$bCol, $bRow] = $this->splitCellRef($b);

        return [$aCol, $aRow, $bCol, $bRow];
    }

    private function buildRangeRef(string $aCol, int $aRow, string $bCol, int $bRow): string
    {
        $start = $aCol . $aRow;
        $end = $bCol . $bRow;

        return $start === $end ? $start : "{$start}:{$end}";
    }

    private function fullName(?string $name, ?string $surname): string
    {
        return trim(implode(' ', array_filter([$name, $surname], fn ($v) => is_string($v) && $v !== '')));
    }

    private function vehicleLabel(Order $order): string
    {
        $vehicle = $order->journey?->vehicle;
        if ($vehicle === null) {
            return '';
        }

        $parts = array_filter([(string) ($vehicle->name ?? ''), (string) ($vehicle->plate ?? '')]);
        return trim(implode(' - ', $parts));
    }

    private function trailerLabel(Order $order): string
    {
        $trailer = $order->journey?->trailer;
        if ($trailer === null) {
            return '';
        }

        $parts = array_filter([(string) ($trailer->name ?? ''), (string) ($trailer->plate ?? '')]);
        return trim(implode(' - ', $parts));
    }

    private function formatDate(mixed $value): string
    {
        if ($value instanceof Carbon) {
            return $value->format('d/m/Y');
        }

        if (is_string($value) && $value !== '') {
            try {
                return Carbon::parse($value)->format('d/m/Y');
            } catch (\Throwable) {
                return '';
            }
        }

        return Carbon::now()->format('d/m/Y');
    }

    private function extractCityProvince(string $address): array
    {
        $clean = trim($address);
        if ($clean === '') {
            return ['', ''];
        }

        $province = '';
        if (preg_match('/\(([A-Z]{2})\)\s*$/', $clean, $matches) === 1) {
            $province = $matches[1];
            $clean = trim(preg_replace('/\(([A-Z]{2})\)\s*$/', '', $clean) ?? $clean);
        }

        $parts = array_values(array_filter(array_map('trim', explode(',', $clean)), fn ($p) => $p !== ''));
        $city = end($parts);
        if (!is_string($city)) {
            $city = '';
        }

        return [$city, $province];
    }

    private function holderColumn(string $holderName): string
    {
        $name = mb_strtolower(trim($holderName));
        $name = str_replace(['à', 'è', 'é', 'ì', 'ò', 'ù'], ['a', 'e', 'e', 'i', 'o', 'u'], $name);

        if (str_contains($name, 'big bag')) {
            return 'G';
        }
        if (str_contains($name, 'sfuso')) {
            return 'H';
        }
        if (str_contains($name, 'bancal')) {
            return 'F';
        }
        if (str_contains($name, 'fust')) {
            return 'D';
        }
        if (str_contains($name, 'secch')) {
            return 'E';
        }
        if (str_contains($name, 'scatol')) {
            return 'K';
        }
        if (str_contains($name, 'batt') && str_contains($name, 'pb')) {
            return 'I';
        }
        if (str_contains($name, 'batter')) {
            return 'J';
        }
        if (str_contains($name, 'ferro') && str_contains($name, 'cass')) {
            return 'C';
        }
        if (str_contains($name, 'cass')) {
            return 'B';
        }

        return 'L';
    }
}
