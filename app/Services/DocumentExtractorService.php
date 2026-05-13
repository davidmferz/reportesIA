<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use Smalot\PdfParser\Parser as PdfParser;

class DocumentExtractorService
{
    /**
     * Extrae el contenido de texto de un archivo basándose en su extensión
     */
    public function extractText(string $filePath): string
    {
        $fullPath = Storage::disk('local')->path($filePath);

        if (!file_exists($fullPath)) {
            throw new \Exception("El archivo no existe: {$filePath}");
        }

        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

        return match ($extension) {
            'pdf' => $this->extractFromPdf($fullPath),
            'docx' => $this->extractFromDocx($fullPath),
            'doc' => $this->extractFromDoc($fullPath),
            'xlsx', 'xls', 'xlsb' => $this->extractFromExcel($fullPath),
            'txt', 'md', 'csv' => $this->extractFromText($fullPath),
            'vsdx' => $this->extractFromVisio($fullPath),
            default => $this->extractFromText($fullPath),
        };
    }

    /**
     * Extrae texto de un PDF
     */
    protected function extractFromPdf(string $fullPath): string
    {
        try {
            $parser = new PdfParser();
            $pdf = $parser->parseFile($fullPath);
            return $pdf->getText();
        } catch (\Exception $e) {
            return "[Error al extraer PDF: {$e->getMessage()}]";
        }
    }

    /**
     * Extrae texto de un DOCX
     */
    protected function extractFromDocx(string $fullPath): string
    {
        try {
            $phpWord = WordIOFactory::load($fullPath);
            $text = '';

            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getHeaders() as $header) {
                    foreach ($header->getElements() as $element) {
                        $text .= $this->extractTextFromElement($element) . "\n";
                    }
                }

                foreach ($section->getElements() as $element) {
                    $text .= $this->extractTextFromElement($element) . "\n";
                }

                foreach ($section->getFooters() as $footer) {
                    foreach ($footer->getElements() as $element) {
                        $text .= $this->extractTextFromElement($element) . "\n";
                    }
                }
            }

            return trim($text);
        } catch (\Throwable $e) {
            throw new \RuntimeException("Falló la extracción del DOCX: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Extrae texto de elementos de PhpWord recursivamente.
     *
     * Histórico: este método tragaba contenido entero. (1) Table no tiene getElements()
     * sino getRows()/getCells(), así que el recursor no bajaba a celdas — el cuerpo del
     * docx (muchas veces maquetado dentro de tablas) se perdía. (2) Algunos elementos
     * devuelven array en getText(), y el ".= array" tira "Array to string conversion"
     * que el catch superior convertía en mensaje "[Error al extraer...]" guardado como
     * si fuera contenido válido. Ahora: bajamos a tablas y defendemos getText() arrays.
     */
    protected function extractTextFromElement($element): string
    {
        $text = '';

        if ($element instanceof \PhpOffice\PhpWord\Element\Table) {
            foreach ($element->getRows() as $row) {
                foreach ($row->getCells() as $cell) {
                    foreach ($cell->getElements() as $cellElement) {
                        $text .= $this->extractTextFromElement($cellElement);
                    }
                    $text .= "\t";
                }
                $text .= "\n";
            }
            return $text;
        }

        if (method_exists($element, 'getText')) {
            $value = $element->getText();
            if (is_string($value)) {
                $text .= $value;
            } elseif (is_array($value)) {
                array_walk_recursive($value, function ($leaf) use (&$text) {
                    if (is_scalar($leaf)) {
                        $text .= (string) $leaf . ' ';
                    }
                });
            }
        }

        if (method_exists($element, 'getElements')) {
            foreach ($element->getElements() as $childElement) {
                $text .= $this->extractTextFromElement($childElement);
            }
        }

        return $text;
    }

    /**
     * Extrae texto de DOC (formato antiguo)
     */
    protected function extractFromDoc(string $fullPath): string
    {
        // Para archivos DOC antiguos, intentamos leer como texto plano
        try {
            $content = file_get_contents($fullPath);
            // Limpiar caracteres no imprimibles
            $content = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $content);
            return $content ?: "[Archivo DOC - contenido no extraíble directamente]";
        } catch (\Exception $e) {
            return "[Error al extraer DOC: {$e->getMessage()}]";
        }
    }

    /**
     * Extrae texto de archivos Excel
     */
    protected function extractFromExcel(string $fullPath): string
    {
        try {
            $spreadsheet = SpreadsheetIOFactory::load($fullPath);
            $text = '';

            foreach ($spreadsheet->getAllSheets() as $sheetIndex => $sheet) {
                $sheetName = $sheet->getTitle();
                $text .= "=== Hoja: {$sheetName} ===\n\n";

                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                for ($row = 1; $row <= $highestRow; $row++) {
                    $rowData = [];
                    for ($col = 'A'; $col <= $highestColumn; $col++) {
                        $cellValue = $sheet->getCell($col . $row)->getCalculatedValue();
                        if ($cellValue !== null && $cellValue !== '') {
                            $rowData[] = $cellValue;
                        }
                    }
                    if (!empty($rowData)) {
                        $text .= implode(' | ', $rowData) . "\n";
                    }
                }
                $text .= "\n";
            }

            return trim($text);
        } catch (\Exception $e) {
            return "[Error al extraer Excel: {$e->getMessage()}]";
        }
    }

    /**
     * Extrae texto de archivos de texto plano
     */
    protected function extractFromText(string $fullPath): string
    {
        try {
            return file_get_contents($fullPath) ?: '';
        } catch (\Exception $e) {
            return "[Error al leer archivo de texto: {$e->getMessage()}]";
        }
    }

    /**
     * Extrae texto de archivos Visio (VSDX)
     * VSDX es un formato XML comprimido similar a DOCX
     */
    protected function extractFromVisio(string $fullPath): string
    {
        try {
            $zip = new \ZipArchive();
            if ($zip->open($fullPath) === true) {
                $text = '';

                // Buscar archivos XML dentro del VSDX
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $filename = $zip->getNameIndex($i);
                    if (strpos($filename, '.xml') !== false) {
                        $content = $zip->getFromIndex($i);
                        // Extraer texto de los nodos XML
                        $xml = @simplexml_load_string($content);
                        if ($xml) {
                            $text .= $this->extractTextFromXml($xml);
                        }
                    }
                }
                $zip->close();

                return trim($text) ?: "[Archivo Visio - estructura extraída]";
            }
            return "[No se pudo abrir archivo Visio]";
        } catch (\Exception $e) {
            return "[Error al extraer Visio: {$e->getMessage()}]";
        }
    }

    /**
     * Extrae texto de un objeto SimpleXML recursivamente
     */
    protected function extractTextFromXml(\SimpleXMLElement $xml): string
    {
        $text = '';

        foreach ($xml->children() as $child) {
            $nodeText = trim((string) $child);
            if (!empty($nodeText)) {
                $text .= $nodeText . "\n";
            }
            $text .= $this->extractTextFromXml($child);
        }

        return $text;
    }

    /**
     * Obtiene información del archivo
     */
    public function getFileInfo(string $filePath): array
    {
        $fullPath = Storage::disk('local')->path($filePath);

        return [
            'exists' => file_exists($fullPath),
            'size' => file_exists($fullPath) ? filesize($fullPath) : 0,
            'extension' => pathinfo($fullPath, PATHINFO_EXTENSION),
            'mime_type' => file_exists($fullPath) ? mime_content_type($fullPath) : null,
        ];
    }
}
