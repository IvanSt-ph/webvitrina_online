<?php

namespace App\Services\AttributeImport;

use App\Services\AttributeImport\ImportException as RuntimeException;
use DOMDocument;
use DOMXPath;
use ZipArchive;

/** Deliberately limited, single-sheet template reader. Never extracts ZIP files. */
final class XlsxReader
{
    public const HEADERS = ['category_slug', 'group_name', 'attribute_name', 'type', 'unit', 'is_filterable', 'options', 'sort_order'];

    public const COLOR_HEADERS = [...self::HEADERS, 'colors'];

    public const MAX_BYTES = 2097152;

    public function read(string $path): array
    {
        if (! is_file($path) || filesize($path) > self::MAX_BYTES) {
            throw new RuntimeException('Файл отсутствует или превышает 2 МиБ.');
        }
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            throw new RuntimeException('Некорректный XLSX/ZIP.');
        }
        try {
            if ($zip->numFiles > 20) {
                throw new RuntimeException('Слишком много частей XLSX. Используйте шаблон.');
            }
            $parts = [];
            $total = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $name = $stat['name'];
                if (! preg_match('~^(\[Content_Types\]\.xml|_rels/\.rels|docProps/(app|core)\.xml|xl/(workbook\.xml|_rels/workbook\.xml\.rels|styles\.xml|sharedStrings\.xml|theme/theme1\.xml|worksheets/sheet1\.xml))$~D', $name)
                    || isset($parts[$name]) || ($stat['encryption_method'] ?? 0) !== 0
                    || $stat['size'] > 8388608 || ($total += $stat['size']) > 12582912
                    || $stat['size'] > max(1, $stat['comp_size']) * 200) {
                    throw new RuntimeException('Неподдерживаемая или небезопасная структура ZIP. Используйте шаблон.');
                }
                $xml = $zip->getFromIndex($i, 8388609);
                if ($xml === false || strlen($xml) !== $stat['size'] || ! mb_check_encoding($xml, 'UTF-8')
                    || str_contains($xml, "\0") || preg_match('/<!DOCTYPE|<!ENTITY/i', $xml)) {
                    throw new RuntimeException('Недопустимый XML.');
                }
                if (preg_match('/<\?xml\s[^?]*encoding=["\']([^"\']+)["\']/i', $xml, $encoding) && strcasecmp($encoding[1], 'UTF-8') !== 0) {
                    throw new RuntimeException('Поддерживается только XML в UTF-8.');
                }
                $doc = new DOMDocument;
                $previous = libxml_use_internal_errors(true);
                try {
                    if (! $doc->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT)) {
                        throw new RuntimeException('Повреждённый XML.');
                    }
                } finally {
                    libxml_clear_errors();
                    libxml_use_internal_errors($previous);
                }
                $xp = new DOMXPath($doc);
                $forbidden = $xp->query('//*[local-name()="f" or local-name()="externalReference" or local-name()="hyperlink" or local-name()="definedName" or local-name()="mergeCell" or local-name()="AlternateContent"]');
                if ($forbidden->length) {
                    $node = $forbidden->item(0);
                    $location = $node instanceof \DOMElement ? $node->getAttribute('ref') : '';
                    while ($node instanceof \DOMElement) {
                        if ($node->localName === 'c') {
                            $location = $node->getAttribute('r');
                            break;
                        }
                        $node = $node->parentNode;
                    }
                    throw new RuntimeException(($location !== '' ? 'Ячейка '.$location.': ' : '').'Формулы, ссылки, именованные диапазоны и объединённые ячейки запрещены.');
                }
                if (in_array($name, ['_rels/.rels', 'xl/_rels/workbook.xml.rels'], true)) {
                    $this->validateRelationships($doc, $name);
                } elseif ($xp->query('//*[local-name()="Relationship"]')->length) {
                    throw new RuntimeException('Связь вне допустимой части XLSX.');
                }
                $parts[$name] = $doc;
            }
            foreach (['[Content_Types].xml', '_rels/.rels', 'xl/workbook.xml', 'xl/_rels/workbook.xml.rels', 'xl/worksheets/sheet1.xml'] as $required) {
                if (! isset($parts[$required])) {
                    throw new RuntimeException('Отсутствует обязательная часть XLSX: '.$required);
                }
            }
            foreach (['_rels/.rels', 'xl/_rels/workbook.xml.rels'] as $relationships) {
                foreach ($parts[$relationships]->documentElement->childNodes as $rel) {
                    if ($rel instanceof \DOMElement && ! isset($parts[$rel->getAttribute('Target')])) {
                        throw new RuntimeException('Связь с отсутствующей частью XLSX.');
                    }
                }
            }
            $officeDocuments = 0;
            foreach ((new DOMXPath($parts['_rels/.rels']))->query('//*[local-name()="Relationship"]') as $rel) {
                if ($rel->getAttribute('Type') === 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument'
                    && $rel->getAttribute('Target') === 'xl/workbook.xml') {
                    $officeDocuments++;
                }
            }
            if ($officeDocuments !== 1) {
                throw new RuntimeException('Некорректная связь с книгой XLSX.');
            }
            $wb = new DOMXPath($parts['xl/workbook.xml']);
            if ($wb->query('//*[local-name()="sheet"]')->length !== 1) {
                throw new RuntimeException('Разрешён ровно один лист.');
            }
            $sheet = $wb->query('//*[local-name()="sheet"]')->item(0);
            $rid = $sheet->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id');
            $mapped = 0;
            foreach ((new DOMXPath($parts['xl/_rels/workbook.xml.rels']))->query('//*[local-name()="Relationship"]') as $rel) {
                if ($rel->getAttribute('Id') === $rid && $rel->getAttribute('Target') === 'xl/worksheets/sheet1.xml'
                    && $rel->getAttribute('Type') === 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet') {
                    $mapped++;
                }
            }
            if ($mapped !== 1 || ($sheet->hasAttribute('state') && $sheet->getAttribute('state') !== 'visible')) {
                throw new RuntimeException('Нестандартное или скрытое содержимое книги.');
            }
            foreach ((new DOMXPath($parts['[Content_Types].xml']))->query('//*[@ContentType]') as $part) {
                if (preg_match('/macro|vba|external|ole/i', $part->getAttribute('ContentType'))) {
                    throw new RuntimeException('Активное содержимое XLSX запрещено.');
                }
            }
            $strings = [];
            if (isset($parts['xl/sharedStrings.xml'])) {
                foreach ((new DOMXPath($parts['xl/sharedStrings.xml']))->query('//*[local-name()="si"]') as $si) {
                    foreach ($si->childNodes as $child) {
                        if ($child instanceof \DOMElement && ($child->localName !== 't' || $child->childElementCount)) {
                            throw new RuntimeException('Форматированный текст в ячейках не поддерживается. Используйте обычный текст.');
                        }
                    }
                    $strings[] = $si->textContent;
                }
            }
            $xp = new DOMXPath($parts['xl/worksheets/sheet1.xml']);
            $xp->registerNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $rows = [];
            $last = 0;
            $headers = null;
            foreach ($xp->query('/s:worksheet/s:sheetData/s:row') as $row) {
                $num = $row->getAttribute('r');
                if (! ctype_digit($num) || (int) $num <= $last || (int) $num > 5001) {
                    throw new RuntimeException('Недопустимая нумерация строк (максимум 5000 строк данных).');
                }
                $last = (int) $num;
                $cells = array_fill(0, 9, '');
                $seen = [];
                foreach ($row->childNodes as $cell) {
                    if (! $cell instanceof \DOMElement) {
                        continue;
                    }
                    if ($cell->namespaceURI !== 'http://schemas.openxmlformats.org/spreadsheetml/2006/main' || $cell->localName !== 'c' || ! preg_match('/^([A-I])'.$num.'$/D', $cell->getAttribute('r'), $m) || isset($seen[$m[1]])) {
                        throw new RuntimeException("Строка {$num}: недопустимая ячейка.");
                    }
                    $seen[$m[1]] = true;
                    $type = $cell->getAttribute('t');
                    foreach ($cell->childNodes as $child) {
                        if (! $child instanceof \DOMElement) {
                            continue;
                        }
                        if ($child->namespaceURI !== $cell->namespaceURI || ! in_array($child->localName, ['is', 'v'], true)) {
                            throw new RuntimeException("Строка {$num}: нестандартное содержимое ячейки.");
                        }
                    }
                    if ($type === 'inlineStr' && ($xp->query('s:is', $cell)->length !== 1 || $xp->query('s:is/*', $cell)->length !== 1 || $xp->query('s:is/s:t', $cell)->length !== 1)) {
                        throw new RuntimeException("Строка {$num}: допускается только обычный текст.");
                    }
                    if ($xp->query('s:v', $cell)->length > 1 || ($type === 'inlineStr' && $xp->query('s:v', $cell)->length)) {
                        throw new RuntimeException("Строка {$num}: неоднозначное значение ячейки.");
                    }
                    if (($type !== 'inlineStr' && $xp->query('s:is', $cell)->length)
                        || $xp->query('s:v/*', $cell)->length) {
                        throw new RuntimeException("Строка {$num}: неоднозначное строковое значение.");
                    }
                    $v = $xp->query('s:v', $cell)->item(0)?->textContent ?? '';
                    $value = match ($type) {
                        'inlineStr' => $xp->query('s:is', $cell)->item(0)?->textContent ?? '',
                        's' => ctype_digit($v) && array_key_exists((int) $v, $strings) ? $strings[(int) $v] : throw new RuntimeException("Строка {$num}: некорректная строковая ссылка."),
                        '', 'n', 'str' => $v,
                        default => throw new RuntimeException("Строка {$num}: неподдерживаемый тип ячейки."),
                    };
                    if (mb_strlen($value) > 10000) {
                        throw new RuntimeException("Строка {$num}: слишком длинная ячейка.");
                    }
                    $cells[ord($m[1]) - 65] = $value;
                }
                if ((int) $num === 1) {
                    $headers = isset($seen['I']) ? self::COLOR_HEADERS : self::HEADERS;
                    if (array_slice($cells, 0, count($headers)) !== $headers) {
                        throw new RuntimeException('Колонки должны точно соответствовать шаблону A:H или A:I.');
                    }
                } elseif (implode('', $cells) !== '') {
                    if ($headers === null || ($headers === self::HEADERS && isset($seen['I']))) {
                        throw new RuntimeException('Структура строки не соответствует заголовкам.');
                    }
                    $rows[(int) $num] = array_combine($headers, array_slice($cells, 0, count($headers))) + ['colors' => ''];
                }
            }
            if (! $xp->query('/s:worksheet/s:sheetData/s:row[@r="1"]')->length || ! $rows) {
                throw new RuntimeException('Нет заголовков или строк данных.');
            }

            return $rows;
        } finally {
            $zip->close();
        }
    }
    /** Resolve against the owning part, never against the filesystem or .rels directory. */
    private function validateRelationships(DOMDocument $doc, string $source): void
    {
        $namespace = 'http://schemas.openxmlformats.org/package/2006/relationships';
        $office = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/';
        $allowed = $source === '_rels/.rels' ? [
            'xl/workbook.xml' => $office.'officeDocument',
            'docProps/app.xml' => $office.'extended-properties',
            'docProps/core.xml' => $namespace.'/metadata/core-properties',
        ] : [
            'xl/worksheets/sheet1.xml' => $office.'worksheet',
            'xl/styles.xml' => $office.'styles',
            'xl/sharedStrings.xml' => $office.'sharedStrings',
            'xl/theme/theme1.xml' => $office.'theme',
        ];
        $root = $doc->documentElement;
        if (! $root || $root->localName !== 'Relationships' || $root->namespaceURI !== $namespace) {
            throw new RuntimeException('Некорректная часть связей XLSX.');
        }
        $ids = $targets = [];
        foreach ($root->childNodes as $rel) {
            if (! $rel instanceof \DOMElement) {
                continue;
            }
            $target = $rel->getAttribute('Target');
            $id = $rel->getAttribute('Id');
            // No URI decoding or dot-segment cleanup: reject aliases rather than guessing.
            if ($rel->namespaceURI !== $namespace || $rel->localName !== 'Relationship' || $rel->childElementCount
                || ! preg_match('~^/?[A-Za-z0-9_-]+(?:/[A-Za-z0-9_-]+)*\.xml$~D', $target)
                || ($rel->hasAttribute('TargetMode') && $rel->getAttribute('TargetMode') !== 'Internal')
                || ! preg_match('/^[A-Za-z_][A-Za-z0-9_.-]*$/D', $id) || isset($ids[$id])) {
                throw new RuntimeException('Внешние, неоднозначные или недопустимые связи XLSX запрещены.');
            }
            $resolved = str_starts_with($target, '/') ? substr($target, 1)
                : ($source === '_rels/.rels' ? '' : 'xl/').$target;
            if (! isset($allowed[$resolved]) || $rel->getAttribute('Type') !== $allowed[$resolved] || isset($targets[$resolved])) {
                throw new RuntimeException('Неожиданная или повторная связь XLSX.');
            }
            $ids[$id] = $targets[$resolved] = true;
            // Canonical package part names used by all subsequent checks; only in-memory DOM changes.
            $rel->setAttribute('Target', $resolved);
        }
    }

}
