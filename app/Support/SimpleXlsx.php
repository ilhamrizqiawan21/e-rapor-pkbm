<?php

namespace App\Support;

use RuntimeException;
use SimpleXMLElement;
use XMLReader;
use ZipArchive;

/**
 * Small, dependency-free XLSX reader/writer for the controlled import files.
 * It deliberately supports inline strings and shared strings only; formulas
 * are never evaluated and are therefore not trusted during grade import.
 */
final class SimpleXlsx
{
    /**
     * @return array<string, array<int, array<int, string>>>
     */
    public static function read(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true || $zip->locateName('[Content_Types].xml') === false) {
            $zip->close();
            throw new RuntimeException('File XLSX tidak valid.');
        }

        try {
            $sharedStrings = self::sharedStrings($zip->getFromName('xl/sharedStrings.xml') ?: null);
            $workbook = self::xml($zip->getFromName('xl/workbook.xml'));
            $relationships = self::xml($zip->getFromName('xl/_rels/workbook.xml.rels'));
            if ($workbook === null || $relationships === null) {
                throw new RuntimeException('Struktur workbook XLSX tidak lengkap.');
            }

            $relationshipTargets = [];
            foreach ($relationships->Relationship as $relationship) {
                $relationshipTargets[(string) $relationship['Id']] = (string) $relationship['Target'];
            }

            $sheets = [];
            foreach ($workbook->xpath('//*[local-name()="sheet"]') ?: [] as $sheet) {
                $attributes = $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
                $relationshipId = (string) ($attributes['id'] ?? '');
                $target = $relationshipTargets[$relationshipId] ?? null;
                if ($target === null) {
                    continue;
                }
                $sheetPath = str_starts_with($target, 'xl/') ? ltrim($target, '/') : 'xl/'.ltrim($target, '/');
                $sheets[(string) $sheet['name']] = self::rows($zip->getFromName($sheetPath), $sharedStrings);
            }

            return $sheets;
        } finally {
            $zip->close();
        }
    }

    /**
     * @param  array<string, array<int, array<int, string>>>  $sheets
     * @param  array<string, array<int, int>>  $editableColumns
     * @param  array<string, array<int, string>>  $editableCells
     * @param  array<string, array<string, mixed>>  $sheetOptions
     */
    public static function write(string $path, array $sheets, array $editableColumns = [], array $editableCells = [], array $sheetOptions = []): void
    {
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Template XLSX tidak dapat dibuat.');
        }

        $sheetCount = count($sheets);
        $contentTypes = '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        for ($index = 1; $index <= $sheetCount; $index++) {
            $contentTypes .= '<Override PartName="/xl/worksheets/sheet'.$index.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        $contentTypes .= '</Types>';

        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');

        $workbook = '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>';
        $relationships = '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        $index = 0;
        foreach (array_keys($sheets) as $name) {
            $index++;
            $workbook .= '<sheet name="'.self::escape($name).'" sheetId="'.$index.'" r:id="rId'.$index.'"/>';
            $relationships .= '<Relationship Id="rId'.$index.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$index.'.xml"/>';
        }
        $workbook .= '</sheets></workbook>';
        $relationships .= '<Relationship Id="rIdStyles" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
        $zip->addFromString('xl/workbook.xml', $workbook);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $relationships);
        $zip->addFromString('xl/styles.xml', self::styles());

        $index = 0;
        foreach ($sheets as $name => $rows) {
            $index++;
            $zip->addFromString('xl/worksheets/sheet'.$index.'.xml', self::sheet($rows, $editableColumns[$name] ?? [], $editableCells[$name] ?? [], $sheetOptions[$name] ?? []));
        }

        $zip->close();
    }

    /** @return array<int, string> */
    private static function sharedStrings(?string $xml): array
    {
        if ($xml === null || $xml === '') {
            return [];
        }
        $reader = new XMLReader;
        $reader->XML($xml, null, LIBXML_NONET | LIBXML_COMPACT);
        $values = [];
        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'si') {
                continue;
            }
            $item = self::xml($reader->readOuterXml());
            $values[] = $item === null ? '' : implode('', array_map(static fn ($text): string => (string) $text, $item->xpath('.//*[local-name()="t"]') ?: []));
        }
        $reader->close();

        return $values;
    }

    /** @return array<int, array<int, string>> */
    private static function rows(string|false $xml, array $sharedStrings): array
    {
        if ($xml === false || $xml === '') {
            return [];
        }
        $reader = new XMLReader;
        if (! $reader->XML($xml, null, LIBXML_NONET | LIBXML_COMPACT)) {
            throw new RuntimeException('Worksheet XLSX memiliki XML tidak valid.');
        }
        $rows = [];
        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'row') {
                continue;
            }
            $rows[] = self::row($reader->readOuterXml(), $sharedStrings);
        }
        $reader->close();

        return $rows;
    }

    /** @return array<int, string> */
    private static function row(string $xml, array $sharedStrings): array
    {
        $item = self::xml($xml);
        if ($item === null) {
            return [];
        }
        $values = [];
        foreach ($item->xpath('//*[local-name()="c"]') ?: [] as $cell) {
            $reference = (string) ($cell['r'] ?? '');
            $column = self::columnNumber($reference);
            $type = (string) ($cell['t'] ?? '');
            $value = (string) ($cell->v ?? '');
            if ($type === 's') {
                $value = $sharedStrings[(int) $value] ?? '';
            } elseif ($type === 'inlineStr') {
                $value = implode('', array_map(static fn ($text): string => (string) $text, $cell->xpath('.//*[local-name()="t"]') ?: []));
            }
            $values[$column] = $value;
        }
        if ($values === []) {
            return [];
        }
        ksort($values);
        $row = array_fill(0, max(array_keys($values)) + 1, '');
        foreach ($values as $column => $value) {
            $row[$column] = $value;
        }

        return $row;
    }

    private static function xml(string|false|null $xml): ?SimpleXMLElement
    {
        if ($xml === false || $xml === null || $xml === '') {
            return null;
        }
        $parsed = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);

        return $parsed === false ? null : $parsed;
    }

    /**
     * @param  array<int, int>  $editableColumns
     * @param  array<int, string>  $editableCells
     * @param  array<string, mixed>  $options
     */
    private static function sheet(array $rows, array $editableColumns, array $editableCells, array $options = []): string
    {
        $widths = $options['widths'] ?? [];
        $rowHeights = $options['row_heights'] ?? [];
        $freezeRows = (int) ($options['freeze_rows'] ?? 1);
        $wrapAll = (bool) ($options['wrap_all'] ?? false);
        $readonlyColumns = $options['readonly_columns'] ?? [];
        $autoFilter = (bool) ($options['auto_filter'] ?? true);
        $maxColumn = max(0, count($rows[0] ?? []) - 1);
        $maxRow = max(1, count($rows));

        $xml = '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        if (! empty($options['tab_color'])) {
            $xml .= '<sheetPr><tabColor rgb="'.self::escape((string) $options['tab_color']).'"/></sheetPr>';
        }
        $xml .= '<sheetViews><sheetView showGridLines="0" workbookViewId="0">';
        if ($freezeRows > 0) {
            $xml .= '<pane ySplit="'.$freezeRows.'" topLeftCell="A'.($freezeRows + 1).'" activePane="bottomLeft" state="frozen"/>';
        }
        $xml .= '</sheetView></sheetViews>';
        if ($widths !== []) {
            $xml .= '<cols>';
            foreach ($widths as $columnIndex => $width) {
                $xml .= '<col min="'.((int) $columnIndex + 1).'" max="'.((int) $columnIndex + 1).'" width="'.((float) $width).'" customWidth="1"/>';
            }
            $xml .= '</cols>';
        }
        $xml .= '<sheetData>';
        foreach ($rows as $rowIndex => $row) {
            $number = $rowIndex + 1;
            $rowHeight = $rowHeights[$number] ?? null;
            $xml .= '<row r="'.$number.'"'.($rowHeight !== null ? ' ht="'.((float) $rowHeight).'" customHeight="1"' : '').'>';
            foreach (array_values($row) as $columnIndex => $value) {
                if ($value === null) {
                    continue;
                }
                $cellReference = self::columnName($columnIndex).$number;
                $isEditable = in_array($columnIndex, $editableColumns, true) || in_array($cellReference, $editableCells, true);
                $isReadonly = in_array($columnIndex, $readonlyColumns, true);
                $style = $rowIndex === 0 ? 1 : ($isEditable ? 2 : ($isReadonly ? 3 : ($wrapAll ? 4 : 0)));
                $xml .= '<c r="'.self::columnName($columnIndex).$number.'" s="'.$style.'" t="inlineStr"><is><t xml:space="preserve">'.self::escape((string) $value).'</t></is></c>';
            }
            $xml .= '</row>';
        }
        $xml .= '</sheetData><sheetProtection sheet="1" objects="1" scenarios="1"/>';
        if ($autoFilter) {
            $xml .= '<autoFilter ref="A1:'.self::columnName($maxColumn).$maxRow.'"/>';
        }
        $xml .= '<pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.3" footer="0.3"/></worksheet>';

        return $xml;
    }

    private static function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="10"/><name val="Arial"/></font><font><b/><sz val="10"/><name val="Arial"/></font></fonts><fills count="5"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FFDDEBF7"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFE8F5E9"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFF1F5F9"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"/><right style="thin"/><top style="thin"/><bottom style="thin"/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="5"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="0" fillId="3" borderId="1" applyAlignment="1"><protection locked="0"/><alignment vertical="top" wrapText="1"/></xf><xf numFmtId="0" fontId="0" fillId="4" borderId="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    private static function columnNumber(string $reference): int
    {
        preg_match('/\A([A-Z]+)/i', $reference, $matches);
        $letters = strtoupper($matches[1] ?? 'A');
        $number = 0;
        foreach (str_split($letters) as $letter) {
            $number = ($number * 26) + ord($letter) - 64;
        }

        return max(0, $number - 1);
    }

    private static function columnName(int $index): string
    {
        $name = '';
        do {
            $name = chr($index % 26 + 65).$name;
            $index = intdiv($index, 26) - 1;
        } while ($index >= 0);

        return $name;
    }
}
