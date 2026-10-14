<?php
/**
 * Generador simple de archivos XLSX
 * Versión mejorada usando ZIP
 */
class SimpleXLSXGen
{
    private $sheets = array();
    private $sheetNames = array();

    public function __construct()
    {
    }

    public function addSheet($data, $name = 'Sheet1')
    {
        $this->sheets[] = $data;
        $this->sheetNames[] = $name;
        return $this;
    }

    public function toString()
    {
        return $this->generateXLSX();
    }

    private function generateXLSX()
    {
        $temp = sys_get_temp_dir() . '/xlsx_' . uniqid();
        if (!is_dir($temp)) {
            mkdir($temp, 0777, true);
        }

        // Crear estructura de directorio
        @mkdir($temp . '/_rels', 0777, true);
        @mkdir($temp . '/xl', 0777, true);
        @mkdir($temp . '/xl/_rels', 0777, true);
        @mkdir($temp . '/xl/worksheets', 0777, true);

        // Crear contenido
        $this->createContentTypes($temp);
        $this->createRels($temp);
        $this->createWorkbook($temp);
        $this->createWorkbookRels($temp);
        $this->createWorksheets($temp);
        $this->createStyles($temp);

        // Crear ZIP
        $zipFile = sys_get_temp_dir() . '/output_' . uniqid() . '.xlsx';
        $zip = new ZipArchive();
        
        if ($zip->open($zipFile, ZipArchive::CREATE) === true) {
            $this->addDirToZip($temp, $zip, '');
            $zip->close();
            
            $content = file_get_contents($zipFile);
            
            // Limpiar temporales
            $this->removeDir($temp);
            @unlink($zipFile);
            
            return $content;
        }
        
        // Cleanup if error
        $this->removeDir($temp);
        @unlink($zipFile);
        throw new Exception('No se pudo crear el archivo XLSX');
    }

    private function createContentTypes($temp)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' . "\n";
        $xml .= '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' . "\n";
        $xml .= '<Default Extension="xml" ContentType="application/xml"/>' . "\n";
        $xml .= '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' . "\n";
        
        foreach ($this->sheets as $i => $sheet) {
            $xml .= '<Override PartName="/xl/worksheets/sheet' . ($i + 1) . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' . "\n";
        }
        
        $xml .= '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' . "\n";
        $xml .= '</Types>';

        file_put_contents($temp . '/[Content_Types].xml', $xml);
    }

    private function createRels($temp)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . "\n";
        $xml .= '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' . "\n";
        $xml .= '</Relationships>';

        file_put_contents($temp . '/_rels/.rels', $xml);
    }

    private function createWorkbook($temp)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' . "\n";
        $xml .= '<sheets>' . "\n";

        foreach ($this->sheetNames as $i => $name) {
            $xml .= '<sheet name="' . htmlspecialchars($name) . '" sheetId="' . ($i + 1) . '" r:id="rId' . ($i + 2) . '"/>' . "\n";
        }

        $xml .= '</sheets>' . "\n";
        $xml .= '</workbook>';

        file_put_contents($temp . '/xl/workbook.xml', $xml);
    }

    private function createWorkbookRels($temp)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . "\n";

        foreach ($this->sheets as $i => $sheet) {
            $xml .= '<Relationship Id="rId' . ($i + 2) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . ($i + 1) . '.xml"/>' . "\n";
        }

        $xml .= '<Relationship Id="rId' . (count($this->sheets) + 2) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' . "\n";
        $xml .= '</Relationships>';

        file_put_contents($temp . '/xl/_rels/workbook.xml.rels', $xml);
    }

    private function createStyles($temp)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' . "\n";
        $xml .= '<fonts><font><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font></fonts>' . "\n";
        $xml .= '<fills><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>' . "\n";
        $xml .= '<borders><border><left/><right/><top/><bottom/><diagonal/></border></borders>' . "\n";
        $xml .= '<cellStyleXfs><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>' . "\n";
        $xml .= '<cellXfs><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs>' . "\n";
        $xml .= '</styleSheet>';

        file_put_contents($temp . '/xl/styles.xml', $xml);
    }

    private function createWorksheets($temp)
    {
        foreach ($this->sheets as $i => $data) {
            $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
            $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' . "\n";
            $xml .= '<dimension ref="A1"/>' . "\n";
            $xml .= '<sheetData>' . "\n";

            foreach ($data as $rowIdx => $row) {
                $xml .= '<row r="' . ($rowIdx + 1) . '">' . "\n";

                foreach ($row as $colIdx => $value) {
                    $colLetter = $this->numberToColumn($colIdx);
                    $cellRef = $colLetter . ($rowIdx + 1);
                    
                    // Determinar si es número o texto
                    $isNumeric = is_numeric($value) && $value !== '';
                    
                    if ($isNumeric && strpos($value, '.') === false) {
                        $xml .= '<c r="' . $cellRef . '" t="n"><v>' . intval($value) . '</v></c>' . "\n";
                    } elseif ($isNumeric) {
                        $xml .= '<c r="' . $cellRef . '" t="n"><v>' . floatval($value) . '</v></c>' . "\n";
                    } else {
                        $xml .= '<c r="' . $cellRef . '" t="inlineStr"><is><t>' . htmlspecialchars($value) . '</t></is></c>' . "\n";
                    }
                }

                $xml .= '</row>' . "\n";
            }

            $xml .= '</sheetData>' . "\n";
            $xml .= '</worksheet>';

            file_put_contents($temp . '/xl/worksheets/sheet' . ($i + 1) . '.xml', $xml);
        }
    }

    private function numberToColumn($num)
    {
        $numeric = $num % 26;
        $letter = chr(65 + $numeric);
        $num = intval(($num - $numeric) / 26);
        
        while ($num > 0) {
            $numeric = ($num - 1) % 26;
            $letter = chr(65 + $numeric) . $letter;
            $num = intval((($num - 1 - $numeric) / 26));
        }

        return $letter;
    }

    private function addDirToZip($dir, &$zip, $basePath = '')
    {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $path = $dir . DIRECTORY_SEPARATOR . $file;
                $zipPath = str_replace('\\', '/', $basePath . $file);
                
                if (is_dir($path)) {
                    $zip->addEmptyDir($zipPath . '/');
                    $this->addDirToZip($path, $zip, $zipPath . '/');
                } else {
                    $zip->addFile($path, $zipPath);
                }
            }
        }
    }

    private function removeDir($dir)
    {
        if (is_dir($dir)) {
            $files = @scandir($dir);
            if ($files !== false) {
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..') {
                        $path = $dir . DIRECTORY_SEPARATOR . $file;
                        if (is_dir($path)) {
                            $this->removeDir($path);
                        } else {
                            @unlink($path);
                        }
                    }
                }
            }
            @rmdir($dir);
        }
    }
}
