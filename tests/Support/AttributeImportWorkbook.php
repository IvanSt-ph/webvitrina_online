<?php

namespace Tests\Support;

use App\Services\AttributeImport\XlsxReader;
use ZipArchive;

final class AttributeImportWorkbook
{
    public static function write(string $path, array $rows = [], array $overrides = [], bool $withColors = false): void
    {
        $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $rels = 'http://schemas.openxmlformats.org/package/2006/relationships';
        $office = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
        $sheet = '<worksheet xmlns="'.$ns.'"><sheetData>';
        foreach ([$withColors ? XlsxReader::COLOR_HEADERS : XlsxReader::HEADERS, ...$rows] as $index => $row) {
            $number = $index + 1;
            $sheet .= '<row r="'.$number.'">';
            foreach (array_values($row) as $col => $value) {
                $sheet .= '<c r="'.chr(65 + $col).$number.'" t="inlineStr"><is><t>'.htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</t></is></c>';
            }
            $sheet .= '</row>';
        }
        $sheet .= '</sheetData></worksheet>';
        $parts = [
            '[Content_Types].xml' => '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>',
            '_rels/.rels' => '<Relationships xmlns="'.$rels.'"><Relationship Id="rId1" Type="'.$office.'/officeDocument" Target="xl/workbook.xml"/></Relationships>',
            'xl/workbook.xml' => '<workbook xmlns="'.$ns.'" xmlns:r="'.$office.'"><sheets><sheet name="Attributes" sheetId="1" r:id="rId1"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<Relationships xmlns="'.$rels.'"><Relationship Id="rId1" Type="'.$office.'/worksheet" Target="worksheets/sheet1.xml"/></Relationships>',
            'xl/worksheets/sheet1.xml' => $sheet,
        ];
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Cannot create fixture');
        }
        foreach (array_replace($parts, $overrides) as $name => $xml) {
            $zip->addFromString($name, $xml);
        }
        $zip->close();
    }
}
