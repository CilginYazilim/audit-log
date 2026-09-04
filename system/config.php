<?php
/**
 * =====================================================================
 *  YAPILANDIRMA
 *  cilginyazilim.com – İşlem Geçmişi (Audit Log)
 * =====================================================================
 */

declare(strict_types=1);

/* ---------------------------------------------------------------------
 *  .env DESTEĞİ
 * ---------------------------------------------------------------------
 *  Veritabanı bilgileri bu dosyanın İÇİNDE durmak zorunda değil.
 *  Depo kökündeki ".env" dosyasına yazarsanız buradaki varsayılanlar
 *  devreye girmez — ve ".env" .gitignore içinde olduğu için parolanız
 *  depoya hiç girmez.
 *
 *  NEDEN AYRI BİR DOSYA?
 *  config.php DEPODA durur ve her dağıtımda depodaki sürümle
 *  DEĞİŞTİRİLİR; içine elle yazdığınız parola bir sonraki deploy'da
 *  silinir. .env ise deploy'un dokunmadığı bir dosyadır: bir kez
 *  oluşturursunuz, kalıcıdır.
 *
 *  DEĞER ARAMA SIRASI
 *      1. config.local.php içinde define() edilmişse o kazanır
 *         (bu dosyada varsa; aşağıdaki "! defined()" kontrolleri)
 *      2. .env dosyası
 *      3. Sunucunun gerçek ortam değişkeni (Apache SetEnv, systemd…)
 *      4. Bu dosyadaki varsayılan
 *
 *  cy_env() bilerek getenv() ile AYNI şeyi döndürür (değer ya da
 *  false). Böylece aşağıdaki satırlar olduğu gibi çalışmaya devam
 *  eder; "?:" ve "!== false" kalıplarının hiçbiri değişmedi.
 * ------------------------------------------------------------------ */
if (! function_exists('cy_env')) {
    /**
     * .env dosyasından (yoksa ortamdan) bir değer okur.
     *
     * @return string|false Değer yoksa false — getenv() ile aynı sözleşme.
     */
    function cy_env(string $key): string|false
    {
        static $env = null;

        if ($env === null) {
            $env  = [];
            $file = dirname(__DIR__) . '/.env';

            if (is_file($file) && is_readable($file)) {
                /* IGNORE_NEW_LINES + SKIP_EMPTY_LINES: satır sonlarını ve
                 * boş satırları baştan eler; ayrıştırma sadeleşir. */
                $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

                foreach ($lines as $line) {
                    $line = trim($line);

                    // Yorum satırı ya da "=" içermeyen satır atlanır.
                    if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
                        continue;
                    }

                    [$name, $value] = explode('=', $line, 2);

                    $name  = trim($name);
                    $value = trim($value);

                    /* Tırnak içindeki değerlerden tırnakları at:
                     * DB_PASS="a b c" → a b c
                     * Tırnak zorunlu değildir; yalnızca boşluk içeren
                     * parolalar için gerekir. */
                    if (strlen($value) >= 2
                        && ($value[0] === '"' || $value[0] === "'")
                        && $value[strlen($value) - 1] === $value[0]
                    ) {
                        $value = substr($value, 1, -1);
                    }

                    if ($name !== '') {
                        $env[$name] = $value;
                    }
                }
            }
        }

        // .env'de varsa o; yoksa sunucunun gerçek ortam değişkeni.
        return $env[$key] ?? getenv($key);
    }
}

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

/* ---------------------------------------------------------------------
 *  SUNUCUYA ÖZEL AYARLAR — system/config.local.php
 * ---------------------------------------------------------------------
 *  Canlı veritabanı adı/kullanıcı/parola BU DOSYAYA DEĞİL, yanındaki
 *  config.local.php dosyasına yazılır. Nedeni iki katmanlı:
 *
 *    1) config.php depoda durur; parolayı buraya yazmak onu GitHub'a taşır.
 *    2) config.php her dağıtımda depodaki sürümle DEĞİŞTİRİLİR — elle
 *       yapılan düzenleme bir sonraki deploy'da silinir.
 *
 *  config.local.php ise .gitignore içindedir: depoya girmez, deploy ona
 *  dokunmaz. Örnek içerik için: config.local.php.example
 * ------------------------------------------------------------------ */
$yerelAyar = __DIR__ . '/config.local.php';

if (is_file($yerelAyar)) {
    require_once $yerelAyar;
}

/* Aşağıdakiler yalnızca config.local.php (ya da ortam değişkeni) değer
 * VERMEDİYSE devreye girer; yerel XAMPP kurulumuna göre varsayılanlardır. */
if (! defined('DB_HOST')) {
    define('DB_HOST', cy_env('DB_HOST') ?: '127.0.0.1');
}
if (! defined('DB_NAME')) {
    define('DB_NAME', cy_env('DB_NAME') ?: 'cy_audit');
}
if (! defined('DB_USER')) {
    define('DB_USER', cy_env('DB_USER') ?: 'root');
}
if (! defined('DB_PASS')) {
    define('DB_PASS', cy_env('DB_PASS') !== false ? (string) cy_env('DB_PASS') : '');
}

// utf8mb4: Türkçe karakterler ve emoji dahil tüm Unicode'u destekler.
define('DB_CHARSET', 'utf8mb4');

/* ---------------------------------------------------------------------
 *  ZAMAN DİLİMİ
 * ---------------------------------------------------------------------
 *  ÖLÇÜLEN SORUN: php.ini'de date.timezone çoğu XAMPP kurulumunda
 *  sunucunun coğrafi diliminden farklıdır. Bu makinede PHP
 *  "Europe/Berlin", MySQL ise sistem dilimi (Europe/Istanbul)
 *  kullanıyordu; aynı anı anlatan iki satır BİR SAAT farklı görünüyordu:
 *
 *      worker günlüğü (PHP date)  : 14:03:17
 *      veritabanı  (MySQL NOW())  : 15:03:17
 *
 *  Bu depodaki zaman ARİTMETİĞİ bilinçli olarak SQL tarafında yapılır
 *  (NOW(), INTERVAL, TIMESTAMPDIFF), bu yüzden hesaplar zaten doğrudur.
 *  Kayan şey, PHP'nin ekrana/günlüğe bastığı saatti — ve demoyu
 *  deneyen biri için bu, "sistem yanlış çalışıyor" gibi görünür.
 *
 *  Çözüm: dilimi ORTAMA bırakmak yerine açıkça sabitliyoruz. Kendi
 *  sunucunuzda farklı bir dilim istiyorsanız APP_TIMEZONE ortam
 *  değişkenini tanımlamanız yeterlidir; kod değiştirmenize gerek yok.
 * ------------------------------------------------------------------ */
define('APP_TIMEZONE', cy_env('APP_TIMEZONE') ?: 'Europe/Istanbul');

// @ kullanmıyoruz: geçersiz bir dilim adı sessizce yutulmamalı.
if (in_array(APP_TIMEZONE, timezone_identifiers_list(), true)) {
    date_default_timezone_set(APP_TIMEZONE);
}

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

$cyDebugEnv = cy_env('APP_DEBUG');
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
