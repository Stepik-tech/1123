<?php
/**
 * Совместимость с окружениями без расширения mbstring.
 * Подключается в config.php самым первым. Реализует только функции,
 * используемые в проекте, через UTF-8 безопасные таблицы (кириллица+латиница).
 */
if (function_exists('mb_strlen')) return;

function _mb_chars(string $s): array { return preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY); }

function mb_strlen(?string $s, ?string $enc = null): int {
    return ($s === null || $s === '') ? 0 : count(_mb_chars($s));
}

function mb_substr(?string $s, int $start, ?int $len = null, ?string $enc = null): string {
    if ($s === null || $s === '') return '';
    $c = _mb_chars($s);
    return implode('', $len === null ? array_slice($c, $start) : array_slice($c, $start, $len));
}

function mb_strimwidth(?string $s, int $start, int $width, string $trimmarker = '', ?string $enc = null): string {
    if ($s === null) return '';
    $c = _mb_chars(mb_substr($s, $start));
    if (count($c) <= $width) return implode('', $c);
    $keep = max(0, $width - mb_strlen($trimmarker));
    return implode('', array_slice($c, 0, $keep)) . $trimmarker;
}

/* Таблицы регистра: ASCII + русская кириллица (+ Ё/ё отдельно из-за нелинейности кодов) */
function _mb_case(?string $s, bool $upper): string {
    if ($s === null) return '';
    static $lo = null, $up = null;
    if ($lo === null) {
        $lo = _mb_chars('абвгдеёжзийклмнопрстуфхцчшщъыьэюя');
        $up = _mb_chars('АБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯ');
        // Ё стоит после Е, а ё — после е: исправляем порядок вручную
        $lo = ['а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п','р','с','т','у','ф','х','ц','ч','ш','щ','ъ','ы','ь','э','ю','я'];
        $up = ['А','Б','В','Г','Д','Е','Ё','Ж','З','И','Й','К','Л','М','Н','О','П','Р','С','Т','У','Ф','Х','Ц','Ч','Ш','Щ','Ъ','Ы','Ь','Э','Ю','Я'];
    }
    $out = '';
    foreach (_mb_chars($s) as $ch) {
        if ($upper) {
            $i = array_search($ch, $lo, true);
            $out .= $i !== false ? $up[$i] : strtoupper($ch);
        } else {
            $i = array_search($ch, $up, true);
            $out .= $i !== false ? $lo[$i] : strtolower($ch);
        }
    }
    return $out;
}

function mb_strtolower(?string $s, ?string $enc = null): string { return _mb_case($s, false); }
function mb_strtoupper(?string $s, ?string $enc = null): string { return _mb_case($s, true); }
