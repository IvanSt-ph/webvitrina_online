<?php

/** Standalone conversion only: no Laravel bootstrap, database or Composer hooks. */
require_once dirname(__DIR__).'/app/Services/AttributeImport/ImportException.php';
require_once dirname(__DIR__).'/app/Services/AttributeImport/XlsxReader.php';

use App\Services\AttributeImport\XlsxReader;

function conversionXml(string $xml): DOMDocument
{
    if (! mb_check_encoding($xml, 'UTF-8') || str_contains($xml, "\0") || preg_match('/<!DOCTYPE|<!ENTITY/i', $xml)) {
        throw new RuntimeException('Unsafe XML');
    }
    $previous = libxml_use_internal_errors(true);
    try {
        $doc = new DOMDocument;
        if (! $doc->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT)) {
            throw new RuntimeException('Malformed XML');
        }
        return $doc;
    } finally {
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
    }
}

function conversionKey(string $text): string
{
    return mb_strtolower(preg_replace('/\s+/u', ' ', trim($text)));
}

function convertAttributeWorkbook(string $source, string $destination): array
{
    $source = realpath($source) ?: throw new RuntimeException('Source not found');
    if (file_exists($destination)) {
        throw new RuntimeException('Output directory already exists; choose a new directory. Nothing overwritten.');
    }
    if (filesize($source) > 2097152) {
        throw new RuntimeException('Source exceeds 2 MiB');
    }
    $sourceHash = hash_file('sha256', $source);
    $zip = new ZipArchive;
    if ($zip->open($source, ZipArchive::RDONLY) !== true) {
        throw new RuntimeException('Cannot open source ZIP');
    }
    $parts = [];
    $total = 0;
    try {
        if ($zip->numFiles > 20) { throw new RuntimeException('Too many ZIP entries'); }
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $name = $stat['name'];
            if (! preg_match('~^(\[Content_Types\]\.xml|_rels/\.rels|xl/(workbook\.xml|styles\.xml|sharedStrings\.xml|theme/theme1\.xml|_rels/workbook\.xml\.rels|worksheets/sheet[1-7]\.xml))$~D', $name)
                || isset($parts[$name]) || ($stat['encryption_method'] ?? 0) !== 0
                || $stat['size'] > 8388608 || ($total += $stat['size']) > 12582912
                || $stat['size'] > max(1, $stat['comp_size']) * 200) {
                throw new RuntimeException('Unsupported or unsafe ZIP entry: '.$name);
            }
            $xml = $zip->getFromIndex($i, 8388609);
            if ($xml === false || strlen($xml) !== $stat['size']) { throw new RuntimeException('ZIP size mismatch'); }
            $doc = conversionXml($xml);
            $xp = new DOMXPath($doc);
            if ($xp->query('//*[local-name()="f" or local-name()="externalReference" or local-name()="hyperlink" or local-name()="AlternateContent"]')->length) {
                throw new RuntimeException('Formula, link or alternate content in '.$name);
            }
            foreach ($xp->query('//*[local-name()="Relationship"]') as $rel) {
                $target = $rel->getAttribute('Target');
                if ($rel->hasAttribute('TargetMode') || str_contains($target, '..') || str_contains($target, ':') || str_contains($target, '\\')
                    || ! preg_match('~^/?xl/(workbook\.xml|styles\.xml|sharedStrings\.xml|theme/theme1\.xml|worksheets/sheet[1-7]\.xml)$~D', $target)) {
                    throw new RuntimeException('Unsupported source relationship');
                }
            }
            $parts[$name] = $doc;
        }
    } finally { $zip->close(); }
    $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    $wb = new DOMXPath($parts['xl/workbook.xml'] ?? throw new RuntimeException('No workbook'));
    $sheets = $wb->query('//*[local-name()="sheet" and @name="Характеристики"]');
    if ($sheets->length !== 1) { throw new RuntimeException('Expected one Характеристики sheet'); }
    $rid = $sheets->item(0)->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id');
    $targets = [];
    foreach ((new DOMXPath($parts['xl/_rels/workbook.xml.rels']))->query('//*[local-name()="Relationship"]') as $rel) {
        if ($rel->getAttribute('Id') === $rid && $rel->getAttribute('Type') === 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet') {
            $targets[] = ltrim($rel->getAttribute('Target'), '/');
        }
    }
    if (count($targets) !== 1 || ! isset($parts[$targets[0]])) { throw new RuntimeException('Ambiguous worksheet target'); }
    $xp = new DOMXPath($parts[$targets[0]]);
    $xp->registerNamespace('s', $ns);
    if ($xp->query('//s:mergeCell')->length) { throw new RuntimeException('Merged source data is ambiguous'); }
    $headers = ['ID категории', 'Категория', 'Slug категории', 'Шаблон', 'Группа', 'Порядок', 'Характеристика', 'Тип поля', 'Ед. изм.', 'Варианты (;)', 'Фильтр', 'Обязательное', 'Источник', 'Статус'];
    $ledger = [];
    $duplicates = [];
    $last = 0;
    foreach ($xp->query('/s:worksheet/s:sheetData/s:row') as $row) {
        $number = $row->getAttribute('r');
        if (! ctype_digit($number) || (int) $number <= $last || (int) $number > 5001) { throw new RuntimeException('Invalid source row number'); }
        $last = (int) $number;
        $cells = array_fill(0, 14, '');
        $seen = [];
        foreach ($row->childNodes as $cell) {
            if (! $cell instanceof DOMElement) { continue; }
            if ($cell->namespaceURI !== $ns || $cell->localName !== 'c' || ! preg_match('/^([A-N])'.$number.'$/D', $cell->getAttribute('r'), $m) || isset($seen[$m[1]])) {
                throw new RuntimeException('Invalid or duplicate source cell at row '.$number);
            }
            $seen[$m[1]] = true;
            if (! in_array($cell->getAttribute('t'), ['', 'n', 'str'], true)) { throw new RuntimeException('Unsupported source cell type at '.$cell->getAttribute('r')); }
            if ($cell->childElementCount > 1 || ($cell->childElementCount && $xp->query('s:v', $cell)->length !== 1)) { throw new RuntimeException('Ambiguous source cell'); }
            $value = $xp->query('s:v', $cell)->item(0)?->textContent ?? '';
            if (mb_strlen($value) > 10000) { throw new RuntimeException('Source cell too long'); }
            $cells[ord($m[1]) - 65] = $value;
        }
        if ((int) $number === 1) {
            if ($cells !== $headers) { throw new RuntimeException('Source headers differ from reviewed V3 format'); }
            continue;
        }
        $raw = array_combine($headers, $cells);
        $mapped = array_combine(XlsxReader::HEADERS, [$cells[2], $cells[4], $cells[6], $cells[7], $cells[8], '', '', $cells[5]]);
        $errors = [];
        $warnings = [];
        $changes = [];
        foreach (['category_slug', 'attribute_name', 'type', 'sort_order'] as $field) {
            if ($mapped[$field] === '') { $errors[] = 'Пустое обязательное поле '.$field; }
        }
        foreach (['category_slug' => 255, 'group_name' => 255, 'attribute_name' => 255, 'unit' => 50] as $field => $max) {
            if (mb_strlen($mapped[$field]) > $max || trim($mapped[$field]) !== $mapped[$field] || preg_match('/[\x00-\x1f\x7f]/u', $mapped[$field])) { $errors[] = 'Некорректное значение '.$field; }
        }
        if (! preg_match('/^(0|[1-9][0-9]{0,9})$/D', $mapped['sort_order']) || (float) $mapped['sort_order'] > 4294967295) { $errors[] = 'Некорректный sort_order'; }
        if (! in_array($cells[10], ['Да', 'Нет'], true)) { $errors[] = 'Неоднозначный Фильтр: '.$cells[10]; }
        else {
            $mapped['is_filterable'] = $cells[10] === 'Да' ? '1' : '0';
            $changes[] = ['field' => 'is_filterable', 'from' => $cells[10], 'to' => $mapped['is_filterable']];
        }
        $unsupported = ! in_array($mapped['type'], ['text', 'number', 'select'], true);
        if ($unsupported) { $errors[] = 'Неподдерживаемый тип '.$mapped['type'].'; автоматическая замена запрещена'; }
        if (in_array($mapped['type'], ['select', 'multiselect'], true)) {
            $options = explode(';', $cells[9]);
            $keys = [];
            if (count($options) > 100) { $errors[] = 'Более 100 вариантов'; }
            foreach ($options as $option) {
                if ($option === '' || trim($option) !== $option || mb_strlen($option) > 255 || preg_match('/[\x00-\x1f\x7f]/u', $option)) { $errors[] = 'Пустой, длинный вариант или пробелы/управляющие символы в options'; }
                if (str_contains($option, '"') || str_contains($option, '\\')) { $errors[] = 'Возможное экранирование разделителя или кавычки: требуется ручная проверка options'; }
                $key = conversionKey($option);
                if (isset($keys[$key])) { $errors[] = 'Повторяющиеся или похожие варианты options'; }
                $keys[$key] = true;
            }
            $mapped['options'] = json_encode($options, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            $changes[] = ['field' => 'options', 'from' => $cells[9], 'to' => $mapped['options']];
        } elseif ($cells[9] !== '') { $errors[] = 'Варианты присутствуют у типа без options'; }
        if ($cells[11] !== 'Нет') { $errors[] = 'Поле Обязательное невозможно сохранить в формате MVP'; }
        if ($cells[13] === 'Проверить') { $warnings[] = 'Исходный статус: Проверить; требуется содержательная проверка'; }
        if ($errors) { $warnings = array_merge($warnings, $errors); }
        $key = json_encode([$mapped['category_slug'], conversionKey($mapped['attribute_name'])], JSON_UNESCAPED_UNICODE);
        $duplicates[$key][] = (int) $number;
        $ledger[(int) $number] = ['source_row' => (int) $number, 'source' => $raw, 'mapped' => $mapped, 'status' => $unsupported ? 'UNSUPPORTED' : ($errors ? 'EXCLUDED' : 'CONVERTED'), 'errors' => array_values(array_unique($errors)), 'warnings' => array_values(array_unique($warnings)), 'transformations' => $changes, 'output_row' => null];
    }
    if ($last === 0 || ! $xp->query('/s:worksheet/s:sheetData/s:row[@r="1"]')->length || ! $ledger) { throw new RuntimeException('Missing source data or header'); }
    foreach ($duplicates as $numbers) {
        if (count($numbers) < 2) { continue; }
        foreach ($numbers as $n) {
            $message = 'Дубликат характеристики в категории: строки '.implode(', ', $numbers);
            $ledger[$n]['errors'][] = $message;
            $ledger[$n]['warnings'][] = $message;
            if ($ledger[$n]['status'] === 'CONVERTED') { $ledger[$n]['status'] = 'EXCLUDED'; }
        }
    }
    // Different definitions across categories are retained, never merged or renamed.
    // Record them because the Laravel preview may block the combined batch.
    $variants = [];
    foreach ($ledger as $n => $entry) {
        if ($entry['status'] !== 'CONVERTED') { continue; }
        $r = $entry['mapped'];
        $signature = json_encode([$r['attribute_name'], $r['type'], $r['unit'], $r['is_filterable'], $r['options']], JSON_UNESCAPED_UNICODE);
        $variants[conversionKey($r['attribute_name'])][$signature][] = $n;
    }
    $conflicts = [];
    foreach ($variants as $name => $definitions) {
        if (count($definitions) > 1) {
            $numbers = array_merge(...array_values($definitions));
            $conflicts[] = ['name' => $name, 'source_rows' => $numbers, 'definitions' => array_map(fn ($signature, $rows) => ['definition' => json_decode($signature, true), 'source_rows' => $rows], array_keys($definitions), array_values($definitions))];
            foreach ($numbers as $n) { $ledger[$n]['warnings'][] = 'Разные определения похожего названия между категориями: '.$name.'; Laravel может заблокировать пакет'; }
        }
    }
    $outputRows = [];
    foreach ($ledger as &$entry) {
        if ($entry['status'] === 'CONVERTED') {
            $entry['output_row'] = count($outputRows) + 2;
            $outputRows[] = $entry['mapped'];
        }
    }
    unset($entry);
    $excluded = array_values(array_filter($ledger, fn ($entry) => $entry['status'] !== 'CONVERTED'));
    $summary = ['source_rows' => count($ledger), 'converted_rows' => count($outputRows), 'excluded_rows' => count($excluded), 'unsupported_rows' => count(array_filter($excluded, fn ($entry) => $entry['status'] === 'UNSUPPORTED')), 'warning_count' => array_sum(array_map(fn ($entry) => count($entry['warnings']), $ledger)), 'rows_with_warnings' => count(array_filter($ledger, fn ($entry) => $entry['warnings'] !== [])), 'different_definition_groups' => count($conflicts), 'unique_category_slugs' => count(array_unique(array_column(array_column($ledger, 'mapped'), 'category_slug')))];
    if ($summary['source_rows'] !== $summary['converted_rows'] + $summary['excluded_rows']) { throw new RuntimeException('Accounting mismatch'); }
    if (! $outputRows) { throw new RuntimeException('No convertible rows'); }
    if (hash_file('sha256', $source) !== $sourceHash) { throw new RuntimeException('Source changed during conversion'); }

    // Build a fresh package from the known-good local template, only replacing data.
    $template = new ZipArchive;
    if ($template->open(dirname(__DIR__).'/docs/templates/category-attributes.xlsx', ZipArchive::RDONLY) !== true) { throw new RuntimeException('Cannot open template'); }
    $templateParts = [];
    try {
        for ($i = 0; $i < $template->numFiles; $i++) { $templateParts[$template->getNameIndex($i)] = $template->getFromIndex($i); }
    } finally { $template->close(); }
    $writer = new XMLWriter;
    $writer->openMemory();
    $writer->startDocument('1.0', 'UTF-8');
    $writer->startElementNS(null, 'worksheet', $ns);
    $writer->startElement('sheetData');
    foreach ([XlsxReader::HEADERS, ...array_map('array_values', $outputRows)] as $index => $values) {
        $writer->startElement('row'); $writer->writeAttribute('r', (string) ($index + 1));
        foreach ($values as $column => $value) {
            $writer->startElement('c'); $writer->writeAttribute('r', chr(65 + $column).($index + 1)); $writer->writeAttribute('t', 'inlineStr');
            $writer->startElement('is'); $writer->startElement('t'); $writer->text($value); $writer->endElement(); $writer->endElement(); $writer->endElement();
        }
        $writer->endElement();
    }
    $writer->endElement(); $writer->endElement(); $writer->endDocument();
    $templateParts['xl/worksheets/sheet1.xml'] = $writer->outputMemory();
    if (! mkdir($destination, 0777, true)) { throw new RuntimeException('Cannot create output directory'); }
    $xlsx = $destination.'/category-attributes-import.xlsx';
    $out = new ZipArchive;
    if ($out->open($xlsx, ZipArchive::CREATE | ZipArchive::EXCL) !== true) { throw new RuntimeException('Cannot create output workbook'); }
    foreach ($templateParts as $name => $xml) {
        if (! $out->addFromString($name, $xml)) { throw new RuntimeException('Cannot write XLSX part'); }
    }
    if (! $out->close()) { throw new RuntimeException('Cannot finish XLSX'); }
    $readBack = (new XlsxReader)->read($xlsx);
    if (array_values($readBack) !== $outputRows) { throw new RuntimeException('Reader round-trip mismatch'); }
    if (hash_file('sha256', $source) !== $sourceHash) { throw new RuntimeException('Source hash changed'); }
    $summary['parser_verification'] = 'PASS: all eight fields of every output row match exactly';
    $report = ['source_path' => $source, 'source_sha256' => $sourceHash, 'source_unchanged' => true, 'output_sha256' => hash_file('sha256', $xlsx), 'summary' => $summary, 'notes' => ['No database access. Not a Laravel dry run.', 'All 14 source fields are retained in each ledger entry.', 'group_name is preserved in XLSX but MVP does not persist groups.', 'Semicolon is treated as the declared delimiter; no trimming, renaming or deduplication.', 'Different definitions across categories are retained with warnings; full preview may block them.', 'Source approval statuses are retained in the report, not promoted to approval.'], 'different_definitions' => $conflicts, 'rows' => array_values($ledger)];
    foreach (['conversion-report.json' => $report, 'exceptions.json' => $excluded] as $name => $data) {
        if (file_put_contents($destination.'/'.$name, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), LOCK_EX) === false) { throw new RuntimeException('Cannot write report'); }
    }
    return $summary;
}

if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    if ($argc !== 3) { fwrite(STDERR, "Usage: php scripts/convert-attribute-workbook.php source.xlsx NEW-output-directory\n"); exit(1); }
    try {
        echo json_encode(convertAttributeWorkbook($argv[1], $argv[2]), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), PHP_EOL;
    } catch (Throwable $e) { fwrite(STDERR, $e->getMessage().PHP_EOL); exit(1); }
}
