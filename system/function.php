<?php
/**
 * =====================================================================
 *  YARDIMCI FONKSİYONLAR
 *  cilginyazilim.com – İşlem Geçmişi (Audit Log)
 * ---------------------------------------------------------------------
 *  BÖLÜM 1  Çıktı / JSON
 *  BÖLÜM 2  CSRF
 *  BÖLÜM 3  Hız sınırı
 *  BÖLÜM 4  DataTables sunucu-taraf yardımcıları (arama/sıralama beyaz liste)
 *  BÖLÜM 5  DENETİM: audit() tek yazma noktası + diff üretimi + maskeleme
 * =====================================================================
 */

declare(strict_types=1);

if (!defined('CY_APP')) {
    http_response_code(403);
    exit;
}

/* ===== BÖLÜM 1 – ÇIKTI / JSON ===================================== */

function e(?string $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function json_response(array $p, int $s = 200): void
{
    if (!headers_sent()) {
        http_response_code($s);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
    }
    echo json_encode($p, JSON_UNESCAPED_UNICODE);
    exit;
}

function json_success(string $d, array $x = []): void
{
    json_response(array_merge(['success' => true, 'type' => 'success', 'description' => $d], $x));
}

function json_error(string $d, int $s = 400, array $x = []): void
{
    json_response(array_merge(['success' => false, 'type' => 'danger', 'description' => $d], $x), $s);
}

/* ===== BÖLÜM 2 – CSRF =========================================== */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function require_csrf(): void
{
    $t = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($t) || $t === '' || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $t)) {
        json_error('Oturum doğrulaması başarısız. Sayfayı yenileyin.', 403);
    }
}

/* ===== BÖLÜM 3 – HIZ SINIRI ===================================== */

function rate_limit_dir(): string
{
    $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cy_audit_rate';
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    return $dir;
}

function client_ip(): string
{
    return (string) ($_SERVER['REMOTE_ADDR'] ?? 'bilinmiyor');
}

function rate_limit(string $bucket, int $limit, int $window): void
{
    $file = rate_limit_dir() . DIRECTORY_SEPARATOR . sha1($bucket . '|' . client_ip()) . '.json';
    $h = @fopen($file, 'c+');
    if ($h === false) {
        return;
    }
    flock($h, LOCK_EX);
    $now  = microtime(true);
    $hits = json_decode((string) stream_get_contents($h), true);
    $hits = is_array($hits) ? $hits : [];
    $hits = array_values(array_filter($hits, static fn($t) => is_numeric($t) && ($now - (float) $t) < $window));
    if (count($hits) >= $limit) {
        $retry = max(1, (int) ceil($window - ($now - (float) $hits[0])));
        flock($h, LOCK_UN);
        fclose($h);
        json_error("Çok fazla istek. {$retry} saniye sonra tekrar deneyin.", 429, ['retry_after' => $retry]);
    }
    $hits[] = $now;
    ftruncate($h, 0);
    rewind($h);
    fwrite($h, json_encode($hits));
    fflush($h);
    flock($h, LOCK_UN);
    fclose($h);
}

function escape_like(string $v): string
{
    return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $v);
}

/* =====================================================================
 *  BÖLÜM 4 – DataTables SUNUCU-TARAF YARDIMCILARI
 * ================================================================== */

/**
 * DataTables istek parametrelerini güvenli değerlere indirger.
 * $sortable: [dizi indisi => 'sütun adı'] beyaz listesi. Sütun adı SQL'e
 * METİN olarak girer, bağlanamaz — bu yüzden beyaz liste ZORUNLUDUR.
 * @return array{start:int,length:int,search:string,orderBy:string,orderDir:string,draw:int}
 */
function datatables_params(array $sortable, string $default): array
{
    $draw   = (int) ($_POST['draw'] ?? 1);
    $start  = max(0, (int) ($_POST['start'] ?? 0));
    $length = (int) ($_POST['length'] ?? 10);
    if ($length < 1 || $length > PAGE_SIZE_MAX) {
        $length = 10;
    }

    $colIdx  = (int) ($_POST['order'][0]['column'] ?? -1);
    $orderBy = $sortable[$colIdx] ?? $default;

    $dir = strtolower((string) ($_POST['order'][0]['dir'] ?? 'desc'));
    $orderDir = $dir === 'asc' ? 'ASC' : 'DESC';

    return [
        'draw'     => $draw,
        'start'    => $start,
        'length'   => $length,
        'search'   => trim((string) ($_POST['search']['value'] ?? '')),
        'orderBy'  => $orderBy,
        'orderDir' => $orderDir,
    ];
}

/* =====================================================================
 *  BÖLÜM 5 – DENETİM (AUDIT)
 * ---------------------------------------------------------------------
 *  audit() TEK yazma noktasıdır. Her CRUD işleyicisi işini bitirince
 *  onu çağırır. audit_log tablosunda UPDATE/DELETE yolu YOKTUR —
 *  denetim kaydı APPEND-ONLY (yalnızca ekleme) olmalıdır; aksi hâlde
 *  "kaydı kim değiştirdi?" sorusunun cevabı da değiştirilebilir olurdu.
 *
 *  diff_values(): eski ve yeni dizileri karşılaştırır, YALNIZCA değişen
 *  anahtarları [alan => [eski, yeni]] biçiminde döndürür. AUDIT_REDACT
 *  listesindeki alanların değerleri '***' ile maskelenir.
 * ================================================================== */

function redact(string $field, mixed $value): mixed
{
    return in_array($field, AUDIT_REDACT, true) ? '***' : $value;
}

/**
 * @return array<string,array{0:mixed,1:mixed}>  değişen alanlar
 */
function diff_values(array $old, array $new): array
{
    $changes = [];
    $keys = array_unique(array_merge(array_keys($old), array_keys($new)));

    foreach ($keys as $k) {
        $a = $old[$k] ?? null;
        $b = $new[$k] ?? null;
        // Gevşek değil KESİN kıyas; "0" ile 0 aynı sayılmasın diye string'e çevir.
        if ((string) $a !== (string) $b) {
            $changes[$k] = [redact($k, $a), redact($k, $b)];
        }
    }

    return $changes;
}

/**
 * Denetim yükünü JSON'a çevirir.
 *
 * NEDEN AYRI BİR FONKSİYON?
 *  json_encode() geçersiz UTF-8 gördüğünde `false` döner — HATA FIRLATMAZ.
 *  Bu `false` doğrudan PDO'ya bağlanırsa MySQL'e boş dizgi gider ve
 *  JSON sütunu "CONSTRAINT failed" der. Hata mesajı sorunun kaynağını
 *  (bozuk kodlama) hiç anmadığı için saatlerce aranır.
 *
 *  JSON_INVALID_UTF8_SUBSTITUTE: bozuk baytı U+FFFD ile değiştirir.
 *  Denetim kaydı için doğru tercih budur — bir baytı okunamadı diye
 *  KAYDIN TAMAMINI kaybetmek, denetimin amacına aykırıdır.
 */
function audit_json(?array $payload): ?string
{
    if ($payload === null) {
        return null;
    }

    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

    return $json === false ? '{}' : $json;
}

/**
 * Denetim kaydı yaz.
 *
 * @param string     $action  'create' | 'update' | 'delete'
 * @param string     $entity  'product' gibi mantıksal tür
 * @param int        $entityId
 * @param array|null $old     işlemden ÖNCEki değerler (create'de null)
 * @param array|null $new     işlemden SONRAki değerler (delete'de null)
 */
function audit(PDO $db, string $action, string $entity, int $entityId, ?array $old, ?array $new): void
{
    // Bir diziyi anahtarlarına göre maskele.
    $redactAll = static function (?array $arr): ?array {
        if ($arr === null) {
            return null;
        }
        $out = [];
        foreach ($arr as $k => $v) {
            $out[$k] = redact((string) $k, $v);
        }
        return $out;
    };

    if (AUDIT_MODE === 'diff' && $action === 'update') {
        // Yalnızca değişen alanlar (diff_values zaten maskeliyor).
        $changes = diff_values($old ?? [], $new ?? []);

        /* HİÇBİR ALAN DEĞİŞMEDİYSE KAYIT YAZMA.
         *
         * Kullanıcı formu açıp hiçbir şeye dokunmadan "Kaydet"e basmış
         * olabilir. Bunu da loglamak, denetim tablosunu boş satırlarla
         * doldurur ve asıl aranan değişikliği bulmayı zorlaştırır.
         * Denetim kaydının değeri, SİNYAL/GÜRÜLTÜ oranını yüksek
         * tutmaktan gelir: her satırın bir karşılığı olmalıdır. */
        if ($changes === []) {
            return;
        }

        $payloadOld = [];
        $payloadNew = [];
        foreach ($changes as $field => [$a, $b]) {
            $payloadOld[$field] = $a;
            $payloadNew[$field] = $b;
        }
    } else {
        // create / delete ya da AUDIT_MODE='full': tüm satırın anlık görüntüsü.
        $payloadOld = $redactAll($old);
        $payloadNew = $redactAll($new);
    }

    $stmt = $db->prepare(
        'INSERT INTO audit_log
            (actor_id, actor_name, action, entity_type, entity_id, old_values, new_values, ip, user_agent)
         VALUES
            (:aid, :aname, :act, :etype, :eid, :old, :new, :ip, :ua)'
    );
    $stmt->execute([
        ':aid'   => (int) ($_SESSION['user_id'] ?? 0) ?: null,
        ':aname' => (string) ($_SESSION['user_name'] ?? 'Misafir'),
        ':act'   => $action,
        ':etype' => $entity,
        ':eid'   => $entityId,
        ':old'   => audit_json($payloadOld),
        ':new'   => audit_json($payloadNew),
        ':ip'    => client_ip(),
        ':ua'    => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
    ]);
}

/* =====================================================================
 *  BÖLÜM 6 – SUNUM YARDIMCILARI (denetim kaydını okunur kılan katman)
 * ---------------------------------------------------------------------
 *  Denetim kaydının teknik olarak doğru olması yetmez; İNSAN tarafından
 *  okunabilir olması gerekir. "price: 429.90 → 599.90" satırını gören
 *  bir muhasebeci `price`in ne olduğunu bilmek zorunda değildir.
 *  Aşağıdaki eşlemeler alan adlarını ve işlem adlarını Türkçeleştirir.
 *
 *  DİKKAT: Bu bir SUNUM katmanıdır. Veritabanına YAZILAN değer her
 *  zaman ham alan adıdır (`price`). Türkçeleştirmeyi kaydın içine
 *  yazsaydık, yarın etiketi değiştirdiğimizde geçmiş kayıtlar
 *  okunamaz hâle gelirdi.
 * ================================================================== */

const FIELD_LABELS = [
    'name'  => 'Ürün adı',
    'sku'   => 'Stok kodu (SKU)',
    'price' => 'Fiyat',
    'stock' => 'Stok adedi',
];

const ACTION_LABELS = [
    'create' => 'Eklendi',
    'update' => 'Güncellendi',
    'delete' => 'Silindi',
];

function field_label(string $field): string
{
    return FIELD_LABELS[$field] ?? $field;
}

function action_label(string $action): string
{
    return ACTION_LABELS[$action] ?? $action;
}

function entity_label(string $entity): string
{
    return AUDIT_ENTITIES[$entity] ?? $entity;
}

/**
 * Değişen alanların insan okur özeti: "Fiyat, Stok adedi".
 * Üç alandan fazlaysa kırpılır — tablo hücresi taşmasın.
 */
function changes_summary(array $fields, int $max = 3): string
{
    if ($fields === []) {
        return '';
    }
    $labels = array_map('field_label', $fields);
    $shown  = array_slice($labels, 0, $max);
    $rest   = count($labels) - count($shown);

    return implode(', ', $shown) . ($rest > 0 ? " +{$rest}" : '');
}

/**
 * 'YYYY-MM-DD' biçimli bir tarih filtresini doğrular.
 * Geçersizse null döner; çağıran taraf o filtreyi hiç uygulamaz.
 * Böylece `f_from=1 OR 1=1` gibi bir deneme sorguya hiç ulaşmaz —
 * zaten bağlanmış (bind) parametre olarak gitse de, hatalı girdinin
 * sessizce "hepsini getir"e dönüşmesini istemeyiz.
 */
function valid_date(?string $v): ?string
{
    $v = trim((string) $v);
    if ($v === '') {
        return null;
    }
    $d = DateTime::createFromFormat('Y-m-d', $v);

    return ($d && $d->format('Y-m-d') === $v) ? $v : null;
}

/**
 * CSV dışa aktarımı.
 *
 * İKİ İNCELİK:
 *  1) UTF-8 BOM. Excel, BOM'suz bir CSV'yi Windows'ta ANSI sanar ve
 *     Türkçe karakterler bozulur ("Ürün" → "ÃœrÃ¼n"). Üç baytlık BOM
 *     Excel'e "bu dosya UTF-8" der.
 *  2) CSV formül enjeksiyonu. `=`, `+`, `-`, `@` ile başlayan bir hücre
 *     Excel'de FORMÜL olarak çalışır; `=cmd|'/c calc'!A1` gibi bir ürün
 *     adı, dosyayı açan kişinin makinesinde komut çalıştırabilir.
 *     Başına tek tırnak koyarak hücreyi metne zorluyoruz.
 */
function csv_cell(mixed $v): string
{
    $v = (string) $v;

    return ($v !== '' && strpbrk($v[0], "=+-@	
") !== false) ? "'" . $v : $v;
}

function csv_download(string $filename, array $header, iterable $rows): void
{
    if (!headers_sent()) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');
    }

    $out = fopen('php://output', 'w');
    fwrite($out, "ï»¿");            // UTF-8 BOM (bkz. yukarıdaki not)
    fputcsv($out, $header, ';');              // Türkçe Excel ';' bekler
    foreach ($rows as $r) {
        fputcsv($out, array_map('csv_cell', $r), ';');
    }
    fclose($out);
    exit;
}

/* ---- Ürün doğrulaması (denetlenen örnek varlık) -------------------- */

/** @return array{0:array<string,mixed>,1:array<string,string>} */
function validate_product(array $in): array
{
    $errors = [];
    $name  = trim((string) ($in['name'] ?? ''));
    $sku   = strtoupper(trim((string) ($in['sku'] ?? '')));
    $price = (string) ($in['price'] ?? '');
    $stock = (string) ($in['stock'] ?? '');

    if (mb_strlen($name) < 2 || mb_strlen($name) > 150) {
        $errors['name'] = 'Ürün adı 2-150 karakter olmalıdır.';
    }
    if (!preg_match('/^[A-Z0-9\-]{2,40}$/', $sku)) {
        $errors['sku'] = 'SKU 2-40 karakter; büyük harf, rakam ve tire.';
    }
    if (!is_numeric($price) || (float) $price < 0 || (float) $price > 9999999) {
        $errors['price'] = 'Fiyat 0 ile 9.999.999 arasında olmalıdır.';
    }
    if (!ctype_digit($stock) || (int) $stock > 1000000) {
        $errors['stock'] = 'Stok 0 ile 1.000.000 arasında bir tam sayı olmalıdır.';
    }

    return [[
        'name'  => $name,
        'sku'   => $sku,
        'price' => number_format((float) $price, 2, '.', ''),
        'stock' => (int) $stock,
    ], $errors];
}
