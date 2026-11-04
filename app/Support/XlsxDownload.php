<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

/**
 * Writes a workbook through ZipArchive. The host runs 32-bit PHP, where PhpSpreadsheet's
 * own writer fails because its ZipStream dependency stores file checksums in an int.
 */
class XlsxDownload
{
    private const MIME = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    public static function response(Spreadsheet $book, string $name): StreamedResponse
    {
        $path = self::write($book);

        return response()->streamDownload(function () use ($path) {
            readfile($path);
            unlink($path);
        }, $name, ['Content-Type' => self::MIME]);
    }

    private static function write(Spreadsheet $book): string
    {
        $path = tempnam(sys_get_temp_dir(), 'gdm');
        $zip = new ZipArchive;

        if ($path === false || $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('No se pudo crear el archivo de Excel.');
        }

        $sheets = $book->getAllSheets();
        $header = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $types = $workbook = $relations = '';

        foreach ($sheets as $index => $sheet) {
            $number = $index + 1;
            $types .= '<Override PartName="/xl/worksheets/sheet'.$number.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $workbook .= '<sheet name="'.self::text($sheet->getTitle()).'" sheetId="'.$number.'" r:id="rId'.$number.'"/>';
            $relations .= '<Relationship Id="rId'.$number.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$number.'.xml"/>';
            $zip->addFromString("xl/worksheets/sheet{$number}.xml", $header.self::sheetXml($sheet->toArray(null, false, false, false)));
        }

        $zip->addFromString('[Content_Types].xml', $header.'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'.$types.'</Types>');
        $zip->addFromString('_rels/.rels', $header.'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', $header.'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>'.$workbook.'</sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', $header.'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.$relations.'</Relationships>');

        if (! $zip->close()) {
            throw new RuntimeException('No se pudo guardar el archivo de Excel.');
        }

        return $path;
    }

    /** @param array<int, array<int, mixed>> $rows */
    private static function sheetXml(array $rows): string
    {
        $xml = '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';

        foreach (array_values($rows) as $rowIndex => $row) {
            $line = $rowIndex + 1;
            $xml .= '<row r="'.$line.'">';
            foreach (array_values($row) as $columnIndex => $value) {
                if ($value === null || $value === '') {
                    continue;
                }
                $reference = Coordinate::stringFromColumnIndex($columnIndex + 1).$line;
                $xml .= is_int($value) || is_float($value)
                    ? '<c r="'.$reference.'"><v>'.$value.'</v></c>'
                    : '<c r="'.$reference.'" t="inlineStr"><is><t xml:space="preserve">'.self::text((string) $value).'</t></is></c>';
            }
            $xml .= '</row>';
        }

        return $xml.'</sheetData></worksheet>';
    }

    private static function text(string $value): string
    {
        return htmlspecialchars((string) preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $value), ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
