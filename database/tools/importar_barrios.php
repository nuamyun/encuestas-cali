<?php

declare(strict_types=1);

/**
 * Convierte los CSV oficiales de datos.cali.gov.co en database/barrios.sql,
 * que MySQL carga al crear la base (03-barrios.sql en docker-compose.yml).
 *
 * Uso:
 *   php database/tools/importar_barrios.php --barrios=barrios.csv [--veredas=veredas.csv] --salida=database/barrios.sql
 *   (sin --salida, el SQL se imprime en la salida estándar)
 *
 * --barrios  CSV con columnas que contengan "barrio" y "comuna" (número 1-22);
 *            opcionalmente "código".
 * --veredas  CSV con columnas que contengan "vereda" y "corregimiento"
 *            (nombre del corregimiento); opcionalmente "código".
 *
 * Detecta el separador (, ; o tabulador) y convierte de Windows-1252 a
 * UTF-8 si hace falta. Lo que no puede interpretar lo informa por STDERR.
 */

$opciones = getopt('', ['barrios:', 'veredas::', 'salida::']);
if (empty($opciones['barrios'])) {
    fwrite(STDERR, "Uso: php importar_barrios.php --barrios=archivo.csv [--veredas=archivo.csv]\n");
    exit(1);
}

const CONECTORES = ['de', 'del', 'la', 'las', 'los', 'el', 'y', 'e', 'en'];

/**
 * @return list<array<string, string>>
 */
function leerCsv(string $ruta): array
{
    if (!is_readable($ruta)) {
        fwrite(STDERR, "No se puede leer {$ruta}\n");
        exit(1);
    }

    $contenido = (string) file_get_contents($ruta);
    $contenido = preg_replace('/^\xEF\xBB\xBF/', '', $contenido) ?? $contenido;
    if (!mb_check_encoding($contenido, 'UTF-8')) {
        $contenido = mb_convert_encoding($contenido, 'UTF-8', 'Windows-1252');
    }

    $lineas = preg_split('/\r\n|\r|\n/', trim($contenido)) ?: [];
    $primera = $lineas[0] ?? '';
    $conteo = [',' => substr_count($primera, ','), ';' => substr_count($primera, ';'), "\t" => substr_count($primera, "\t")];
    arsort($conteo);
    $separador = (string) array_key_first($conteo);

    $encabezados = array_map(static fn ($h) => normalizar((string) $h), str_getcsv($primera, $separador, '"', ''));
    $filas = [];
    foreach (array_slice($lineas, 1) as $linea) {
        if (trim($linea) === '') {
            continue;
        }
        $valores = str_getcsv($linea, $separador, '"', '');
        $filas[] = array_combine($encabezados, array_pad(array_slice($valores, 0, count($encabezados)), count($encabezados), ''));
    }

    return $filas;
}

function normalizar(string $texto): string
{
    $texto = mb_strtolower(trim($texto));
    $texto = strtr($texto, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);

    return preg_replace('/\s+/', ' ', $texto) ?? $texto;
}

/**
 * @param list<string> $claves
 */
function columna(array $fila, array $claves, array $excluir = []): ?string
{
    foreach (array_keys($fila) as $nombre) {
        foreach ($claves as $clave) {
            if (str_contains($nombre, $clave)) {
                foreach ($excluir as $x) {
                    if (str_contains($nombre, $x)) {
                        continue 3;
                    }
                }
                return $nombre;
            }
        }
    }

    return null;
}

function nombrePropio(string $texto): string
{
    $texto = preg_replace('/\s+/u', ' ', trim($texto)) ?? $texto;

    // Si la fuente ya trae mayúsculas y minúsculas (p. ej. el listado DIVIPOLA),
    // se respeta tal cual; solo se corrigen los nombres escritos en MAYÚSCULAS.
    if ($texto !== mb_strtoupper($texto)) {
        return $texto;
    }

    $palabras = explode(' ', mb_convert_case(mb_strtolower($texto), MB_CASE_TITLE));
    foreach ($palabras as $i => $p) {
        if (preg_match('/^(i{1,3}|iv|v|vi{1,3}|ix|x)$/i', $p) || preg_match('/\d/', $p)) {
            $palabras[$i] = mb_strtoupper($p); // romanos ("Etapa II") y siglas con número ("PP1")
        } elseif ($i > 0 && in_array(mb_strtolower($p), CONECTORES, true)) {
            $palabras[$i] = mb_strtolower($p);
        }
    }

    return implode(' ', $palabras);
}

function sql(string $valor): string
{
    return "'" . str_replace(["\\", "'"], ["\\\\", "''"], $valor) . "'";
}

date_default_timezone_set('America/Bogota');

$salida = [
    '-- Generado por database/tools/importar_barrios.php el ' . date('Y-m-d H:i'),
    '-- Fuente: database/fuentes (IDESC, listado DIVIPOLA de barrios, veredas y sectores, Acuerdo 0636 de 2026)',
    'SET NAMES utf8mb4;',
    'USE encuestas_cali;',
    '',
];

// ---------- Barrios urbanos ----------
$filas = leerCsv($opciones['barrios']);
$colBarrio = $filas ? columna($filas[0], ['barrio', 'nombre'], ['id', 'cod']) : null;
$colComuna = $filas ? columna($filas[0], ['comuna']) : null;
$colCodigo = $filas ? columna($filas[0], ['codigo', 'cod']) : null;

if ($colBarrio === null || $colComuna === null) {
    fwrite(STDERR, "El CSV de barrios debe tener columnas de barrio y comuna. Encabezados: "
        . implode(' | ', $filas ? array_keys($filas[0]) : []) . "\n");
    exit(1);
}

$ok = 0;
$omitidas = 0;
foreach ($filas as $n => $fila) {
    $nombre = nombrePropio($fila[$colBarrio] ?? '');
    $comuna = (int) preg_replace('/\D/', '', $fila[$colComuna] ?? '');
    $codigo = $colCodigo ? trim($fila[$colCodigo] ?? '') : '';

    if ($nombre === '' || $comuna < 1 || $comuna > 22) {
        $omitidas++;
        fwrite(STDERR, 'Barrios, fila ' . ($n + 2) . ' omitida: ' . json_encode($fila, JSON_UNESCAPED_UNICODE) . "\n");
        continue;
    }

    $salida[] = sprintf(
        "INSERT IGNORE INTO barrios (codigo, nombre, comuna_corregimiento_id) SELECT %s, %s, id FROM comunas_corregimientos WHERE tipo = 'comuna' AND numero = %d;",
        $codigo === '' ? 'NULL' : sql(mb_substr($codigo, 0, 10)),
        sql(mb_substr($nombre, 0, 100)),
        $comuna
    );
    $ok++;
}
fwrite(STDERR, "Barrios: {$ok} procesados, {$omitidas} omitidos.\n");

// ---------- Veredas rurales (opcional) ----------
if (!empty($opciones['veredas'])) {
    $filas = leerCsv($opciones['veredas']);
    $colVereda = $filas ? columna($filas[0], ['vereda', 'sector', 'nombre'], ['corregimiento']) : null;
    $colCorr   = $filas ? columna($filas[0], ['corregimiento']) : null;
    $colCodigo = $filas ? columna($filas[0], ['codigo', 'cod']) : null;

    if ($colVereda === null || $colCorr === null) {
        fwrite(STDERR, "El CSV de veredas debe tener columnas de vereda y corregimiento. Encabezados: "
            . implode(' | ', $filas ? array_keys($filas[0]) : []) . "\n");
        exit(1);
    }

    $nombresCorr = [
        'navarro' => 'Navarro', 'el hormiguero' => 'El Hormiguero', 'pance' => 'Pance',
        'la buitrera' => 'La Buitrera', 'villacarmelo' => 'Villacarmelo', 'los andes' => 'Los Andes',
        'pichinde' => 'Pichindé', 'la leonera' => 'La Leonera', 'felidia' => 'Felidia',
        'el saladito' => 'El Saladito', 'la elvira' => 'La Elvira', 'la castilla' => 'La Castilla',
        'la paz' => 'La Paz', 'montebello' => 'Montebello', 'golondrinas' => 'Golondrinas',
    ];

    $ok = 0;
    $omitidas = 0;
    $salida[] = '';
    foreach ($filas as $n => $fila) {
        $nombre = nombrePropio($fila[$colVereda] ?? '');
        $corr   = normalizar(preg_replace(['/^corregimiento\s+(de\s+)?/i', '/\s*\(\d+\)$/'], '', $fila[$colCorr] ?? '') ?? '');
        $codigo = $colCodigo ? trim($fila[$colCodigo] ?? '') : '';

        if ($nombre === '' || !isset($nombresCorr[$corr])) {
            $omitidas++;
            fwrite(STDERR, 'Veredas, fila ' . ($n + 2) . ' omitida: ' . json_encode($fila, JSON_UNESCAPED_UNICODE) . "\n");
            continue;
        }

        $salida[] = sprintf(
            "INSERT IGNORE INTO barrios (codigo, nombre, comuna_corregimiento_id) SELECT %s, %s, id FROM comunas_corregimientos WHERE tipo = 'corregimiento' AND nombre = %s;",
            $codigo === '' ? 'NULL' : sql(mb_substr($codigo, 0, 10)),
            sql(mb_substr($nombre, 0, 100)),
            sql($nombresCorr[$corr])
        );
        $ok++;
    }
    fwrite(STDERR, "Veredas: {$ok} procesadas, {$omitidas} omitidas.\n");
}

$sqlFinal = implode("\n", $salida) . "\n";
if (!empty($opciones['salida'])) {
    file_put_contents($opciones['salida'], $sqlFinal);
    fwrite(STDERR, "Archivo generado: {$opciones['salida']}\n");
} else {
    echo $sqlFinal;
}
