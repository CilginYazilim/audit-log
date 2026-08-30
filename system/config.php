<?php
/**
 * =====================================================================
 *  YAPILANDIRMA
 *  cilginyazilim.com – İşlem Geçmişi (Audit Log)
 * =====================================================================
 */

declare(strict_types=1);

if (!defined('CY_APP')) {
    http_response_code(403);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    ]);
    session_start();
}

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'cy_audit');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? (string) getenv('DB_PASS') : '');
define('DB_CHARSET', 'utf8mb4');

/* ---------------------------------------------------------------------
 *  HATA AYIKLAMA — ORTAMA GÖRE OTOMATİK
 * ---------------------------------------------------------------------
 *  Bu depo hem yerel makinede (XAMPP) hem de canlı demo sunucusunda
 *  aynı dosyalarla çalışır. APP_DEBUG'ı sabit `true` bırakmak, canlıya
 *  alındığında MySQL hata metinlerini ziyaretçinin ekranına basardı;
 *  sabit `false` bırakmak ise yerelde kurulum hatasını görünmez yapardı.
 *
 *  Çözüm: varsayılan değeri SUNUCU ADINDAN türetiyoruz. localhost /
 *  127.0.0.1 / *.local / *.test → geliştirme; diğer her şey → canlı.
 *  İstenirse ortam değişkeniyle ezilebilir:  SetEnv APP_DEBUG 0
 * ------------------------------------------------------------------ */
function cy_is_local_host(): bool
{
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
    $host = (string) preg_replace('/:\d+$/', '', $host);

    if ($host === '' || PHP_SAPI === 'cli') {
        return true;
    }

    return in_array($host, ['localhost', '127.0.0.1', '::1', '[::1]'], true)
        || str_ends_with($host, '.local')
        || str_ends_with($host, '.test')
        || str_ends_with($host, '.localhost');
}

$cyDebugEnv = getenv('APP_DEBUG');
define('APP_DEBUG', $cyDebugEnv !== false
    ? in_array(strtolower((string) $cyDebugEnv), ['1', 'true', 'on', 'yes'], true)
    : cy_is_local_host());

error_reporting(APP_DEBUG ? E_ALL : 0);
ini_set('display_errors', APP_DEBUG ? '1' : '0');

/* =====================================================================
 *  DENETİM AYARLARI
 * ---------------------------------------------------------------------
 *  AUDIT_REDACT : Loga YAZILMADAN önce maskelenecek alan adları.
 *    Parola, jeton, kart numarası gibi alanların eski/yeni değeri
 *    audit_log tablosuna DÜZ yazılırsa, denetim kaydı bir sızıntı
 *    kaynağına dönüşür. Bu alanlar '***' ile değiştirilir.
 *
 *  AUDIT_MODE : 'diff'  → yalnızca DEĞİŞEN alanların eski/yeni değeri
 *               'full'  → her işlemde tüm satırın anlık görüntüsü
 *    diff daha küçük ve okunası; full ise "o an kayıt tam olarak
 *    neydi?" sorusuna tek satırda cevap verir. Bu örnek 'diff' kullanır.
 * ================================================================== */
define('AUDIT_REDACT', ['password', 'password_hash', 'token', 'secret', 'card_number', 'cvv']);
define('AUDIT_MODE', 'diff');

/* Denetlenebilen varlık türleri. Filtre açılır listesi bu diziden
 * üretilir; istemciden gelen entity değeri de buna göre doğrulanır. */
define('AUDIT_ENTITIES', ['product' => 'Ürün']);

/* DataTables'ın istemciden gelebilecek `length` değeri için tavan. */
define('PAGE_SIZE_MAX', 200);

/* CSV dışa aktarımında tek seferde okunacak en fazla satır. Sınırsız
 * bırakmak, 1 milyon satırlık bir denetim tablosunda sunucuyu bellek
 * hatasıyla düşürürdü. */
define('EXPORT_MAX_ROWS', 5000);

/* [istek sayısı, saniye] — yazma işlemleri için hız sınırı. */
define('RATE_LIMIT_WRITE', [60, 60]);

/* Dışa aktarım pahalı bir iştir; daha sıkı sınırlanır. */
define('RATE_LIMIT_EXPORT', [10, 60]);

try {
    $db = new PDO(
        sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET),
        DB_USER, DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo APP_DEBUG
        ? 'Veritabanı bağlantı hatası: ' . $e->getMessage() . "\n\nKurulum:  mysql -u root -p < cy_audit.sql"
        : 'Veritabanına bağlanılamadı.';
    exit;
}
