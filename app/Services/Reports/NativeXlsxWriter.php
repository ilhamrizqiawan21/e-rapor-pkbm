<?php

namespace App\Services\Reports;

use RuntimeException;
use ZipArchive;

/**
 * Small dependency-free XLSX writer for deterministic report exports.
 * Cells are written as inline strings or numeric values so Excel can open the
 * workbook without a spreadsheet Composer package.
 */
final class NativeXlsxWriter
{
    /**
     * @param  array<int, array{name: string, rows: array<int, array<int, array{value: mixed, style?: int}>>, widths?: array<int, float>, freeze_row?: int}>  $sheets
     */
    public function write(array $sheets, string $path): void
    {
        if ($sheets === []) {
            throw new RuntimeException('Workbook ledger tidak memiliki sheet.');
        }

        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Workbook ledger tidak dapat dibuat.');
        }

        try {
            $zip->addFromString('[Content_Types].xml', $this->contentTypes(count($sheets)));
            $zip->addFromString('_rels/.rels', $this->rootRelationships());
            $zip->addFromString('xl/workbook.xml', $this->workbook($sheets));
            $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationships(count($sheets)));
            $zip->addFromString('xl/styles.xml', $this->styles());

            foreach ($sheets as $index => $sheet) {
                $zip->addFromString('xl/worksheets/sheet'.($index + 1).'.xml', $this->worksheet($sheet));
            }

            $zip->close();
        } catch (\Throwable $exception) {
            $zip->close();
            @unlink($path);
            throw $exception;
        }
    }

    /** @param array<int, array{name: string}> $sheets */
    private function workbook(array $sheets): string
    {
        $items = '';
        foreach ($sheets as $index => $sheet) {
            $items .= '<sheet name="'.$this->escape((string) $sheet['name']).'" sheetId="'.($index + 1).'" r:id="rId'.($index + 1).'"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<bookViews><workbookView/></bookViews><sheets>'.$items.'</sheets></workbook>';
    }

    private function workbookRelationships(int $sheetCount): string
    {
        $items = '';
        for ($index = 1; $index <= $sheetCount; $index++) {
            $items .= '<Relationship Id="rId'.$index.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$index.'.xml"/>';
        }

        $items .= '<Relationship Id="rId'.($sheetCount + 1).'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.$items.'</Relationships>';
    }

    private function rootRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private function contentTypes(int $sheetCount): string
    {
        $overrides = '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        for ($index = 1; $index <= $sheetCount; $index++) {
            $overrides .= '<Override PartName="/xl/worksheets/sheet'.$index.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'.$overrides.'</Types>';
    }

    /** @param array{name: string, rows: array<int, array<int, array{value: mixed, style?: int}>>, widths?: array<int, float>, freeze_row?: int} $sheet */
    private function worksheet(array $sheet): string
    {
        $rows = '';
        foreach ($sheet['rows'] as $rowIndex => $row) {
            $rowNumber = $rowIndex + 1;
            $cells = '';
            foreach ($row as $columnIndex => $cell) {
                $value = $cell['value'] ?? null;
                if ($value === null) {
                    continue;
                }
                $style = (int) ($cell['style'] ?? 0);
                $reference = $this->columnName($columnIndex + 1).$rowNumber;
                $cells .= $this->cell($reference, $value, $style);
            }
            $rows .= '<row r="'.$rowNumber.'">'.$cells.'</row>';
        }

        $columns = '';
        foreach (($sheet['widths'] ?? []) as $index => $width) {
            $column = $index + 1;
            $columns .= '<col min="'.$column.'" max="'.$column.'" width="'.number_format((float) $width, 2, '.', '').'" customWidth="1"/>';
        }

        $freezeRow = max(0, (int) ($sheet['freeze_row'] ?? 0));
        $pane = $freezeRow > 0
            ? '<pane ySplit="'.$freezeRow.'" topLeftCell="A'.($freezeRow + 1).'" activePane="bottomLeft" state="frozen"/>'
            : '';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView workbookViewId="0">'.$pane.'</sheetView></sheetViews>'
            .($columns !== '' ? '<cols>'.$columns.'</cols>' : '')
            .'<sheetData>'.$rows.'</sheetData></worksheet>';
    }

    private function cell(string $reference, mixed $value, int $style): string
    {
        if (is_int($value) || is_float($value)) {
            return '<c r="'.$reference.'" s="'.$style.'"><v>'.$this->escape((string) $value).'</v></c>';
        }

        return '<c r="'.$reference.'" s="'.$style.'" t="inlineStr"><is><t xml:space="preserve">'.$this->escape((string) $value).'</t></is></c>';
    }

    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<numFmts count="1"><numFmt numFmtId="164" formatCode="0.00"/></numFmts>'
            .'<fonts count="2"><font><sz val="10"/><name val="Arial"/></font><font><b/><sz val="10"/><name val="Arial"/></font></fonts>'
            .'<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FFDDEBF7"/><bgColor indexed="64"/></patternFill></fill></fills>'
            .'<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"/><right style="thin"/><top style="thin"/><bottom style="thin"/><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="8">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" applyAlignment="1"><alignment vertical="top"/></xf>'
            .'<xf numFmtId="0" fontId="1" fillId="2" borderId="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="1" fillId="0" borderId="0" applyAlignment="1"><alignment vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="1" fillId="2" borderId="1" applyAlignment="1"><alignment horizontal="left" vertical="center" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" applyAlignment="1"><alignment horizontal="center" vertical="top" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" applyAlignment="1"><alignment horizontal="right" vertical="top" wrapText="1"/></xf>'
            .'<xf numFmtId="164" fontId="0" fillId="0" borderId="1" applyAlignment="1"><alignment horizontal="right" vertical="top" wrapText="1"/></xf>'
            .'</cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    }

    private function columnName(int $number): string
    {
        $name = '';
        while ($number > 0) {
            $remainder = ($number - 1) % 26;
            $name = chr(65 + $remainder).$name;
            $number = intdiv($number - 1, 26);
        }

        return $name;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
