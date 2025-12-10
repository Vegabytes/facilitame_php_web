<?php
/**
 * FACILITAME - Análisis Estático de Código
 *
 * Este script analiza el código fuente buscando:
 * - Vulnerabilidades de seguridad
 * - Código duplicado
 * - Malas prácticas
 * - Problemas de SQL
 *
 * Ejecutar: php tests/code_analysis.php
 */

define('BASE_PATH', dirname(__DIR__));
define('GREEN', "\033[32m");
define('RED', "\033[31m");
define('YELLOW', "\033[33m");
define('CYAN', "\033[36m");
define('RESET', "\033[0m");
define('BOLD', "\033[1m");

class CodeAnalyzer {
    private array $issues = [];
    private array $stats = [
        'critical' => 0,
        'warning' => 0,
        'info' => 0,
        'files_scanned' => 0
    ];

    public function analyze(): void {
        echo BOLD . "\n╔════════════════════════════════════════════════════╗\n";
        echo "║   FACILITAME - ANÁLISIS ESTÁTICO DE CÓDIGO         ║\n";
        echo "╚════════════════════════════════════════════════════╝\n" . RESET;

        $this->analyzeSecurityIssues();
        $this->analyzeSQLIssues();
        $this->analyzeCodeDuplication();
        $this->analyzeBadPractices();
        $this->analyzeGroupByIssues();

        $this->printSummary();
    }

    /**
     * Buscar problemas de seguridad
     */
    private function analyzeSecurityIssues(): void {
        $this->section("ANÁLISIS DE SEGURIDAD");

        // 1. SQL Injection: Variables en queries sin preparar
        $patterns = [
            '/\$pdo->query\s*\([^)]*\$/' => 'SQL Injection: pdo->query() con variable',
            '/LIMIT\s+\$[a-z_]+\s+OFFSET\s+\$/' => 'SQL Injection: LIMIT/OFFSET con variable',
            '/WHERE.*=\s*[\'"]?\s*\$_(?:GET|POST|REQUEST)/' => 'SQL Injection: Input directo en WHERE',
            '/IN\s*\(\s*\$[a-z_]+\s*\)/' => 'SQL Injection potencial: IN() con variable',
        ];

        $dirs = ['controller', 'api', 'bold'];

        foreach ($dirs as $dir) {
            $path = BASE_PATH . '/' . $dir;
            if (!is_dir($path)) continue;

            $files = glob($path . '/*.php');
            foreach ($files as $file) {
                $content = file_get_contents($file);
                $lines = explode("\n", $content);
                $filename = basename($file);

                foreach ($patterns as $pattern => $description) {
                    if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                        foreach ($matches[0] as $match) {
                            $lineNum = substr_count(substr($content, 0, $match[1]), "\n") + 1;
                            $this->addIssue('critical', $description, $dir . '/' . $filename, $lineNum, $match[0]);
                        }
                    }
                }
            }
        }

        // 2. XSS: Echo de $_GET/$_POST sin escapar
        $xssPatterns = [
            '/echo\s+\$_(?:GET|POST|REQUEST)\s*\[/' => 'XSS: Echo directo de input',
            '/\?>\s*<[^>]*<?=\s*\$_(?:GET|POST)/' => 'XSS: Input en HTML sin escapar',
            '/href\s*=\s*[\'"][^"\']*\$_GET/' => 'XSS: $_GET en atributo href',
        ];

        foreach (['pages', 'components', 'layout'] as $dir) {
            $this->scanDir(BASE_PATH . '/' . $dir, $xssPatterns, 'critical');
        }
    }

    /**
     * Buscar problemas de SQL específicos
     */
    private function analyzeSQLIssues(): void {
        $this->section("ANÁLISIS DE SQL");

        // Buscar GROUP BY problemáticos
        $dirs = ['controller', 'api', 'bold'];

        foreach ($dirs as $dir) {
            $path = BASE_PATH . '/' . $dir;
            if (!is_dir($path)) continue;

            $files = glob($path . '/*.php');
            foreach ($files as $file) {
                $content = file_get_contents($file);
                $filename = basename($file);

                // Buscar CASE WHEN con GROUP BY (problema ONLY_FULL_GROUP_BY)
                if (preg_match('/CASE\s+WHEN.*IS\s+NOT\s+NULL.*GROUP\s+BY/is', $content)) {
                    // Verificar si ya está corregido (usa subquery)
                    if (!preg_match('/\(SELECT\s+COUNT\(\*\)\s+FROM/i', $content)) {
                        $this->addIssue('critical', 'GROUP BY con CASE WHEN no agregado (ONLY_FULL_GROUP_BY)', $dir . '/' . $filename);
                    }
                }

                // Buscar SELECT * con GROUP BY
                if (preg_match('/SELECT\s+[^,]*\.\*.*GROUP\s+BY/is', $content)) {
                    $this->addIssue('warning', 'SELECT * con GROUP BY puede fallar con ONLY_FULL_GROUP_BY', $dir . '/' . $filename);
                }
            }
        }
    }

    /**
     * Buscar GROUP BY problemáticos restantes
     */
    private function analyzeGroupByIssues(): void {
        $this->section("ANÁLISIS DE GROUP BY");

        $dirs = ['controller', 'api'];

        foreach ($dirs as $dir) {
            $path = BASE_PATH . '/' . $dir;
            if (!is_dir($path)) continue;

            $files = glob($path . '/*.php');
            foreach ($files as $file) {
                $content = file_get_contents($file);
                $filename = basename($file);

                // Buscar patrones problemáticos de GROUP BY
                $problematicPatterns = [
                    // CASE WHEN con JOIN y GROUP BY
                    '/LEFT\s+JOIN.*notifications.*GROUP\s+BY\s+\w+\.id\s*$/im' => 'JOIN notifications con GROUP BY simple',
                    // COUNT con GROUP BY no en todas las columnas
                    '/COUNT\s*\([^)]+\).*,.*GROUP\s+BY\s+\w+\.id\s*$/im' => 'COUNT con otras columnas en GROUP BY simple',
                ];

                foreach ($problematicPatterns as $pattern => $desc) {
                    if (preg_match($pattern, $content)) {
                        $this->addIssue('warning', $desc, $dir . '/' . $filename);
                    }
                }
            }
        }
    }

    /**
     * Analizar código duplicado
     */
    private function analyzeCodeDuplication(): void {
        $this->section("ANÁLISIS DE CÓDIGO DUPLICADO");

        // Contar patrones duplicados

        // 1. Paginación duplicada
        $paginationPattern = '/\$page\s*=.*intval.*\$_GET\s*\[\s*[\'"]page[\'"]\s*\]/';
        $paginationCount = 0;
        $paginationFiles = [];

        foreach (['controller', 'api'] as $dir) {
            $path = BASE_PATH . '/' . $dir;
            if (!is_dir($path)) continue;

            $files = glob($path . '/*.php');
            foreach ($files as $file) {
                $content = file_get_contents($file);
                if (preg_match($paginationPattern, $content)) {
                    $paginationCount++;
                    $paginationFiles[] = basename($file);
                }
            }
        }

        if ($paginationCount > 5) {
            $this->addIssue('info', "Código de paginación duplicado en {$paginationCount} archivos", 'Múltiples archivos');
        }

        // 2. Swal.fire duplicado
        $swalCount = 0;
        $jsPath = BASE_PATH . '/assets/js/bold';
        if (is_dir($jsPath)) {
            $jsFiles = glob($jsPath . '/*.js');
            foreach ($jsFiles as $file) {
                $content = file_get_contents($file);
                $swalCount += substr_count($content, 'Swal.fire');
            }
        }

        if ($swalCount > 20) {
            $this->addIssue('info', "Swal.fire() usado {$swalCount} veces - considerar helper", 'assets/js/bold/');
        }

        // 3. Componentes duplicados por rol
        $componentsPath = BASE_PATH . '/components';
        if (is_dir($componentsPath)) {
            $components = glob($componentsPath . '/*.php');
            $basenames = [];

            foreach ($components as $file) {
                $name = basename($file, '.php');
                // Extraer nombre base (sin sufijo de rol)
                $baseName = preg_replace('/-(admin|cliente|proveedor|comercial|asesoria)$/', '', $name);
                if (!isset($basenames[$baseName])) {
                    $basenames[$baseName] = [];
                }
                $basenames[$baseName][] = $name;
            }

            foreach ($basenames as $base => $variants) {
                if (count($variants) > 2) {
                    $this->addIssue('info', "Componente '{$base}' tiene " . count($variants) . " variantes por rol - unificar", 'components/');
                }
            }
        }
    }

    /**
     * Analizar malas prácticas
     */
    private function analyzeBadPractices(): void {
        $this->section("ANÁLISIS DE MALAS PRÁCTICAS");

        // 1. Funciones muy largas en functions.php
        $functionsFile = BASE_PATH . '/bold/functions.php';
        if (file_exists($functionsFile)) {
            $content = file_get_contents($functionsFile);
            $lineCount = substr_count($content, "\n");

            if ($lineCount > 1500) {
                $this->addIssue('warning', "functions.php tiene {$lineCount} líneas - considerar modularizar", 'bold/functions.php');
            }

            // Contar funciones
            preg_match_all('/^function\s+([a-z_]+)\s*\(/m', $content, $matches);
            $funcCount = count($matches[1]);

            if ($funcCount > 50) {
                $this->addIssue('info', "functions.php tiene {$funcCount} funciones - considerar separar en módulos", 'bold/functions.php');
            }
        }

        // 2. Debug statements en producción
        $debugPatterns = [
            '/error_log\s*\(/' => 'error_log() encontrado',
            '/var_dump\s*\(/' => 'var_dump() encontrado',
            '/print_r\s*\(/' => 'print_r() encontrado',
            '/console\.log\s*\(/' => 'console.log() encontrado',
        ];

        foreach (['controller', 'api'] as $dir) {
            $path = BASE_PATH . '/' . $dir;
            if (!is_dir($path)) continue;

            $files = glob($path . '/*.php');
            foreach ($files as $file) {
                $content = file_get_contents($file);
                $filename = basename($file);

                foreach ($debugPatterns as $pattern => $desc) {
                    $count = preg_match_all($pattern, $content);
                    if ($count > 3) {
                        $this->addIssue('info', "{$desc} ({$count} veces) - remover para producción", $dir . '/' . $filename);
                    }
                }
            }
        }

        // 3. Archivos sin usar (backups en BD-like names)
        foreach (['controller', 'api'] as $dir) {
            $path = BASE_PATH . '/' . $dir;
            if (!is_dir($path)) continue;

            $files = glob($path . '/*.php');
            foreach ($files as $file) {
                $filename = basename($file);
                if (preg_match('/^\d{4}-\d{2}-\d{2}/', $filename) || preg_match('/\.bak|\.old|_backup/i', $filename)) {
                    $this->addIssue('info', "Archivo de backup detectado - considerar eliminar", $dir . '/' . $filename);
                }
            }
        }
    }

    /**
     * Escanear directorio recursivamente
     */
    private function scanDir(string $path, array $patterns, string $severity): void {
        if (!is_dir($path)) return;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') continue;

            $this->stats['files_scanned']++;
            $content = file_get_contents($file->getPathname());
            $relativePath = str_replace(BASE_PATH . '/', '', $file->getPathname());

            foreach ($patterns as $pattern => $description) {
                if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                    foreach ($matches[0] as $match) {
                        $lineNum = substr_count(substr($content, 0, $match[1]), "\n") + 1;
                        $this->addIssue($severity, $description, $relativePath, $lineNum);
                    }
                }
            }
        }
    }

    /**
     * Agregar issue
     */
    private function addIssue(string $severity, string $message, string $file, ?int $line = null, ?string $code = null): void {
        $this->stats[$severity]++;

        $this->issues[] = [
            'severity' => $severity,
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'code' => $code ? substr(trim($code), 0, 60) : null
        ];

        // Output inmediato
        $color = match($severity) {
            'critical' => RED,
            'warning' => YELLOW,
            'info' => CYAN,
            default => RESET
        };

        $label = strtoupper($severity);
        $location = $line ? "{$file}:{$line}" : $file;

        echo "{$color}[{$label}]{RESET} {$message}\n";
        echo "        └─ {$location}\n";

        if ($code) {
            echo "           " . CYAN . substr($code, 0, 60) . RESET . "\n";
        }
    }

    /**
     * Imprimir sección
     */
    private function section(string $title): void {
        echo "\n" . BOLD . "═══ {$title} ═══" . RESET . "\n\n";
    }

    /**
     * Imprimir resumen
     */
    private function printSummary(): void {
        echo "\n" . BOLD . "╔════════════════════════════════════════════════════╗\n";
        echo "║                    RESUMEN                          ║\n";
        echo "╚════════════════════════════════════════════════════╝" . RESET . "\n\n";

        echo RED . "  ● Críticos:  " . $this->stats['critical'] . RESET . "\n";
        echo YELLOW . "  ● Warnings:  " . $this->stats['warning'] . RESET . "\n";
        echo CYAN . "  ● Info:      " . $this->stats['info'] . RESET . "\n";
        echo "\n  Archivos escaneados: " . $this->stats['files_scanned'] . "\n";

        if ($this->stats['critical'] > 0) {
            echo "\n" . RED . BOLD . "⚠ HAY ISSUES CRÍTICOS QUE REQUIEREN ATENCIÓN INMEDIATA" . RESET . "\n";
        }
    }

    /**
     * Obtener resultados como array
     */
    public function getResults(): array {
        return [
            'stats' => $this->stats,
            'issues' => $this->issues
        ];
    }
}

// Ejecutar análisis
$analyzer = new CodeAnalyzer();
$analyzer->analyze();

// Guardar reporte JSON
$results = $analyzer->getResults();
$reportPath = BASE_PATH . '/tests/analysis_report.json';
file_put_contents($reportPath, json_encode($results, JSON_PRETTY_PRINT));
echo "\n📄 Reporte guardado en: tests/analysis_report.json\n";
