<?php
/**
 * =====================================================================
 *  AJAX UÇ NOKTASI
 *  cilginyazilim.com – İşlem Geçmişi (Audit Log)
 * ---------------------------------------------------------------------
 *  ÜRÜN CRUD (denetlenen örnek varlık):
 *    action=product_list / product_fetch / product_save / product_delete
 *  DENETİM KAYDI (salt-okunur):
 *    action=audit_list   → DataTables listesi (filtreli)
 *    action=audit_detail → tek kaydın alan-bazlı diff'i
 *    action=audit_export → aynı filtrelerle CSV dışa aktarım
 *    action=stats        → üst şeritteki sayaclar
 *
 *  audit_log için EKLEME/GÜNCELLEME/SİLME uç noktası YOKTUR. Denetim
 *  kaydı yalnızca audit() ile, bir CRUD işleminin YAN ETKİSİ olarak
 *  yazılır. Append-only.
 * =====================================================================
 */

declare(strict_types=1);

define('CY_APP', true);
require __DIR__ . '/config.php';
require __DIR__ . '/function.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Yalnızca POST istekleri kabul edilir.', 405);
}

require_csrf();

/* Bu örnekte gerçek giriş yoktur; denetim kaydında "kim" görünsün diye
 * sahte bir aktör oturuma yazılır. Gerçek projede burası auth-starter
 * ya da rbac-login-system'den gelen oturumdur. */
$_SESSION['user_id']   = $_SESSION['user_id']   ?? 1;
$_SESSION['user_name'] = $_SESSION['user_name'] ?? 'Evren ÇILGIN';

$action = isset($_POST['action']) ? strtolower(trim((string) $_POST['action'])) : '';

try {
    switch ($action) {
        case 'product_list':   handle_product_list($db);   break;
        case 'product_fetch':  handle_product_fetch($db);  break;
        case 'product_save':   handle_product_save($db);   break;
        case 'product_delete': handle_product_delete($db); break;
        case 'audit_list':     handle_audit_list($db);     break;
        case 'audit_detail':   handle_audit_detail($db);   break;
        case 'audit_export':   handle_audit_export($db);   break;
        case 'stats':          handle_stats($db);          break;
        default:               json_error('Bilinmeyen işlem.', 400);
    }
} catch (PDOException $e) {
    error_log('[AUDIT] DB: ' . $e->getMessage());
    json_error(APP_DEBUG ? 'DB hatası: ' . $e->getMessage() : 'Beklenmeyen bir veritabanı hatası.', 500);
} catch (Throwable $e) {
    error_log('[AUDIT] Hata: ' . $e->getMessage());
    json_error(APP_DEBUG ? 'Hata: ' . $e->getMessage() : 'Beklenmeyen bir hata.', 500);
}

/* =====================================================================
 *  ÜRÜN LİSTESİ (DataTables server-side)
 * ================================================================== */
function handle_product_list(PDO $db): void
{
    $sortable = [0 => 'id', 1 => 'name', 2 => 'sku', 3 => 'price', 4 => 'stock', 5 => 'updated_at'];
    $p = datatables_params($sortable, 'id');

    $where = '';
    $params = [];
    if ($p['search'] !== '') {
        $where = 'WHERE name LIKE :q OR sku LIKE :q';
        $params[':q'] = '%' . escape_like($p['search']) . '%';
    }

    $total = (int) $db->query('SELECT COUNT(*) FROM products')->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM products $where");
    $stmt->execute($params);
    $filtered = (int) $stmt->fetchColumn();

    $sql = "SELECT id, name, sku, price, stock, updated_at
            FROM products $where
            ORDER BY {$p['orderBy']} {$p['orderDir']}
            LIMIT :off, :len";
    $stmt = $db->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':off', $p['start'], PDO::PARAM_INT);
    $stmt->bindValue(':len', $p['length'], PDO::PARAM_INT);
    $stmt->execute();

    $rows = [];
    foreach ($stmt->fetchAll() as $r) {
        $id = (int) $r['id'];

        /* Stok rozeti: 0 ise kırmızı, 20'nin altındaysa turuncu. Denetim
         * kaydının değerini göstermek için önce DEĞerİ görünür kılmak
         * gerekir: kullanıcı "stok neden 0'a düştü?" diye sorduğunda
         * cevabı alttaki geçmiş tablosunda arar. */
        $stock = (int) $r['stock'];
        $stockCls = $stock === 0 ? 'danger' : ($stock < 20 ? 'warning' : 'success');

        $rows[] = [
            '<span class="cy-id">#' . $id . '</span>',
            '<span class="cy-name">' . e($r['name']) . '</span>',
            '<code class="cy-sku">' . e($r['sku']) . '</code>',
            '<span class="cy-price">' . e(number_format((float) $r['price'], 2, ',', '.')) . ' ₺</span>',
            '<span class="cy-stock cy-stock--' . $stockCls . '">' . $stock . '</span>',
            '<span class="cy-nowrap cy-muted">' . e(date('d.m.Y H:i', strtotime((string) $r['updated_at']))) . '</span>',
            '<div class="cy-actions">'
            . '<button type="button" class="cy-btn-icon cy-btn-icon--edit js-edit" data-id="' . $id
            . '" aria-label="' . e($r['name']) . ' kaydını düzenle" title="Düzenle">✎</button>'
            . '<button type="button" class="cy-btn-icon cy-btn-icon--delete js-del" data-id="' . $id
            . '" data-name="' . e($r['name']) . '" aria-label="' . e($r['name'])
            . ' kaydını sil" title="Sil">🗑</button>'
            . '</div>',
        ];
    }

    json_response([
        'draw'            => $p['draw'],
        'recordsTotal'    => $total,
        'recordsFiltered' => $filtered,
        'data'            => $rows,
    ]);
}

function handle_product_fetch(PDO $db): void
{
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if (!$id) {
        json_error('Geçersiz kayıt.', 400);
    }
    $stmt = $db->prepare('SELECT id, name, sku, price, stock FROM products WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    if (!$row) {
        json_error('Kayıt bulunamadı.', 404);
    }
    json_response(['success' => true, 'product' => $row]);
}

/* =====================================================================
 *  ÜRÜN KAYDET  (ekle veya güncelle) — audit() burada çağrılır
 * ================================================================== */
function handle_product_save(PDO $db): void
{
    rate_limit('write', ...RATE_LIMIT_WRITE);

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
    [$data, $errors] = validate_product($_POST);
    if ($errors) {
        json_error('Lütfen formdaki hataları düzeltin.', 422, ['errors' => $errors]);
    }

    if ($id === 0) {
        /* ---- EKLE ----
         * VERİ ve DENETİM KAYDI AYNI İŞLEMDE (transaction) yazılır.
         *
         * Neden şart? audit() herhangi bir sebeple patlarsa (bozuk
         * kodlama, disk dolu, kilit zaman aşımı) ürün satırı çoktan
         * kaydedilmiş olurdu ve ortada İZİ OLMAYAN bir kayıt kalırdı.
         * Bir denetim sisteminde bu, sessizce oluşan en tehlikeli
         * durumdur: tabloya bakan kişi hiçbir eksik görmez.
         *
         * beginTransaction/commit ikilisi ikisini tek bir "ya ikisi de
         * ya hiçbiri" adımına dönüştürür. InnoDB kullanmamızın somut
         * karşılığı budur. */
        $db->beginTransaction();
        try {
            $stmt = $db->prepare('INSERT INTO products (name, sku, price, stock) VALUES (:n, :s, :p, :st)');
            $stmt->execute([':n' => $data['name'], ':s' => $data['sku'], ':p' => $data['price'], ':st' => $data['stock']]);

            $newId = (int) $db->lastInsertId();
            audit($db, 'create', 'product', $newId, null, $data);

            $db->commit();
        } catch (PDOException $e) {
            $db->rollBack();
            if ($e->getCode() === '23000' && str_contains($e->getMessage(), 'uq_products_sku')) {
                json_error('Bu SKU zaten kullanımda.', 409, ['errors' => ['sku' => 'Bu SKU zaten var.']]);
            }
            throw $e;
        }

        json_success('Ürün eklendi.', ['id' => $newId]);
    }

    // ---- GÜNCELLE ----  (önce ESKİ değeri oku — diff için şart)
    $stmt = $db->prepare('SELECT name, sku, price, stock FROM products WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $old = $stmt->fetch();
    if (!$old) {
        json_error('Kayıt bulunamadı.', 404);
    }

    $db->beginTransaction();
    try {
        $stmt = $db->prepare('UPDATE products SET name = :n, sku = :s, price = :p, stock = :st WHERE id = :id');
        $stmt->execute([':n' => $data['name'], ':s' => $data['sku'], ':p' => $data['price'], ':st' => $data['stock'], ':id' => $id]);

        audit($db, 'update', 'product', $id, $old, $data);

        $db->commit();
    } catch (PDOException $e) {
        $db->rollBack();
        if ($e->getCode() === '23000' && str_contains($e->getMessage(), 'uq_products_sku')) {
            json_error('Bu SKU zaten kullanımda.', 409, ['errors' => ['sku' => 'Bu SKU zaten var.']]);
        }
        throw $e;
    }

    // diff boşsa "değişiklik yok" bilgisi ver ama yine 200.
    $changed = count(diff_values($old, $data));
    json_success($changed ? "Ürün güncellendi ($changed alan)." : 'Değişiklik yapılmadı.', ['id' => $id]);
}

function handle_product_delete(PDO $db): void
{
    rate_limit('write', ...RATE_LIMIT_WRITE);

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if (!$id) {
        json_error('Geçersiz kayıt.', 400);
    }
    $stmt = $db->prepare('SELECT name, sku, price, stock FROM products WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $old = $stmt->fetch();
    if (!$old) {
        json_error('Kayıt bulunamadı.', 404);
    }

    $db->beginTransaction();
    try {
        $db->prepare('DELETE FROM products WHERE id = :id')->execute([':id' => $id]);
        audit($db, 'delete', 'product', $id, $old, null);

        $db->commit();
    } catch (PDOException $e) {
        $db->rollBack();
        throw $e;
    }

    /* Ürün gitti ama İZİ kaldı. Denetim kaydı, silinen kaydın son
     * hâlinin tek kopyasıdır — bu yüzden delete'te old_values TAM
     * satırdır, diff değil. */
    json_success('Ürün silindi. Son hâli işlem geçmişine yazıldı.');
}

/* =====================================================================
 *  DENETİM KAYDI FİLTRELERİ  (liste ve CSV dışa aktarım ORTAK kullanır)
 * ---------------------------------------------------------------------
 *  Filtre kurma işini tek fonksiyonda topluyoruz. Aksi hâlde CSV çıktısı
 *  ile ekrandaki liste ZAMANLA birbirinden ayrışırdı: birine yeni bir
 *  filtre eklenip diğerine eklenmediğinde kullanıcı "ekranda 12 satır
 *  vardı, dosyada 400 satır çıktı" derdi. Bir denetim aracında bu güven
 *  kaybı, aracın kendisini işe yaramaz kılar.
 *
 *  @return array{0:string,1:array<string,mixed>}  [WHERE parçası, parametreler]
 * ================================================================== */
function audit_filters(): array
{
    $conds  = [];
    $params = [];

    $fAction = strtolower(trim((string) ($_POST['f_action'] ?? '')));
    if (in_array($fAction, ['create', 'update', 'delete'], true)) {
        $conds[] = 'action = :fa';
        $params[':fa'] = $fAction;
    }

    // Varlık türü SABİT bir listeden doğrulanır; serbest metin kabul edilmez.
    $fEntity = trim((string) ($_POST['f_entity'] ?? ''));
    if ($fEntity !== '' && array_key_exists($fEntity, AUDIT_ENTITIES)) {
        $conds[] = 'entity_type = :fe';
        $params[':fe'] = $fEntity;
    }

    /* Tarih aralığı. Bitiş gününün TAMAMI dahil olsun diye "< ertesi gün"
     * kullanıyoruz. '<= :to' yazsaydık, :to '2026-08-30' olduğunda MySQL
     * bunu '2026-08-30 00:00:00' sayar ve o günün bütün kayıtları
     * filtrenin DIŞINDA kalırdı — klasik ve sinsi bir hata. */
    if ($from = valid_date($_POST['f_from'] ?? null)) {
        $conds[] = 'created_at >= :ff';
        $params[':ff'] = $from . ' 00:00:00';
    }
    if ($to = valid_date($_POST['f_to'] ?? null)) {
        $conds[] = 'created_at < :ft';
        $params[':ft'] = date('Y-m-d 00:00:00', strtotime($to . ' +1 day'));
    }

    /* DataTables aramayı search[value] olarak gönderir; CSV butonu ise
     * düz bir search_value alanı yollar. İkisini de kabul ediyoruz. */
    $search = trim((string) ($_POST['search']['value'] ?? $_POST['search_value'] ?? ''));
    if ($search !== '') {
        $conds[] = '(actor_name LIKE :q OR entity_type LIKE :q OR CAST(entity_id AS CHAR) LIKE :q)';
        $params[':q'] = '%' . escape_like($search) . '%';
    }

    return [$conds ? 'WHERE ' . implode(' AND ', $conds) : '', $params];
}

/** Bir denetim satırında adı geçen (değişen) alanların listesi. */
function audit_changed_fields(array $row): array
{
    $old = json_decode((string) $row['old_values'], true) ?: [];
    $new = json_decode((string) $row['new_values'], true) ?: [];

    return array_values(array_unique(array_merge(array_keys($old), array_keys($new))));
}

/* =====================================================================
 *  DENETİM KAYDI LİSTESİ  (salt-okunur, filtreli)
 * ================================================================== */
function handle_audit_list(PDO $db): void
{
    $sortable = [0 => 'id', 1 => 'created_at', 2 => 'actor_name', 3 => 'action', 4 => 'entity_type'];
    $p = datatables_params($sortable, 'id');

    [$where, $params] = audit_filters();

    $total = (int) $db->query('SELECT COUNT(*) FROM audit_log')->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM audit_log $where");
    $stmt->execute($params);
    $filtered = (int) $stmt->fetchColumn();

    $stmt = $db->prepare(
        "SELECT id, created_at, actor_name, action, entity_type, entity_id,
                old_values, new_values, ip
         FROM audit_log $where
         ORDER BY {$p['orderBy']} {$p['orderDir']}
         LIMIT :off, :len"
    );
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':off', $p['start'], PDO::PARAM_INT);
    $stmt->bindValue(':len', $p['length'], PDO::PARAM_INT);
    $stmt->execute();

    $rows = [];
    foreach ($stmt->fetchAll() as $r) {
        $fields = audit_changed_fields($r);
        $ts     = strtotime((string) $r['created_at']);

        $rows[] = [
            '<span class="cy-id">#' . (int) $r['id'] . '</span>',

            // Tarih ve saat alt alta: mobilde sütun daralınca taşmasın.
            '<span class="cy-time"><b>' . e(date('d.m.Y', $ts)) . '</b>'
                . '<small>' . e(date('H:i:s', $ts)) . '</small></span>',

            '<span class="cy-actor"><b>' . e($r['actor_name']) . '</b>'
                . '<small>' . e($r['ip']) . '</small></span>',

            '<span class="cy-op cy-op--' . e($r['action']) . '">'
                . e(action_label((string) $r['action'])) . '</span>',

            '<span class="cy-entity">' . e(entity_label((string) $r['entity_type']))
                . ' <b>#' . (int) $r['entity_id'] . '</b></span>',

            $fields
                ? '<span class="cy-fields"><b>' . count($fields) . '</b> alan'
                    . '<small>' . e(changes_summary($fields)) . '</small></span>'
                : '<span class="cy-muted">—</span>',

            '<button type="button" class="cy-btn-icon cy-btn-icon--view js-detail" data-id="'
                . (int) $r['id'] . '" aria-label="' . (int) $r['id']
                . ' numaralı işlemin detayı" title="Detay">👁</button>',
        ];
    }

    json_response([
        'draw'            => $p['draw'],
        'recordsTotal'    => $total,
        'recordsFiltered' => $filtered,
        'data'            => $rows,
    ]);
}

/* =====================================================================
 *  DENETİM KAYDI → CSV
 * ---------------------------------------------------------------------
 *  Denetim kaydının en sık gerçek kullanımı şudur: "şu tarih
 *  aralığındaki fiyat değişikliklerini muhasebeye gönder." Ekranda
 *  okunan bir tablo bunu karşılamaz, dosya gerekir. Dışa aktarım
 *  LİSTEYLE AYNI filtreleri kullanır (bkz. audit_filters).
 * ================================================================== */
function handle_audit_export(PDO $db): void
{
    rate_limit('export', ...RATE_LIMIT_EXPORT);

    [$where, $params] = audit_filters();

    $stmt = $db->prepare(
        "SELECT id, created_at, actor_name, action, entity_type, entity_id, old_values, new_values, ip
         FROM audit_log $where
         ORDER BY id DESC
         LIMIT :len"
    );
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':len', EXPORT_MAX_ROWS, PDO::PARAM_INT);
    $stmt->execute();

    $rows = [];
    foreach ($stmt->fetchAll() as $r) {
        $old = json_decode((string) $r['old_values'], true) ?: [];
        $new = json_decode((string) $r['new_values'], true) ?: [];

        /* Her DEĞİŞEN ALAN için ayrı satır. Tek satıra ham JSON basmak,
         * dosyayı Excel'de süzülemez ve toplanamaz hâle getirirdi. */
        foreach (audit_changed_fields($r) as $f) {
            $rows[] = [
                $r['id'],
                date('d.m.Y H:i:s', strtotime((string) $r['created_at'])),
                $r['actor_name'],
                action_label((string) $r['action']),
                entity_label((string) $r['entity_type']),
                $r['entity_id'],
                field_label($f),
                $old[$f] ?? '',
                $new[$f] ?? '',
                $r['ip'],
            ];
        }
    }

    csv_download(
        'islem-gecmisi-' . date('Y-m-d-Hi') . '.csv',
        ['Kayıt', 'Zaman', 'Aktör', 'İşlem', 'Varlık', 'Varlık No', 'Alan', 'Eski değer', 'Yeni değer', 'IP'],
        $rows
    );
}

/* =====================================================================
 *  ÜST ŞERİT SAYAÇLARI
 * ---------------------------------------------------------------------
 *  Tek sorguda dört sayaç. Üç ayrı COUNT sorgusu atmak yerine
 *  SUM(action = '...') kullanmak tabloyu bir kez taratır.
 * ================================================================== */
function handle_stats(PDO $db): void
{
    $s = $db->query(
        "SELECT COUNT(*)               AS toplam,
                SUM(action = 'create') AS eklendi,
                SUM(action = 'update') AS guncellendi,
                SUM(action = 'delete') AS silindi,
                MAX(created_at)        AS son
         FROM audit_log"
    )->fetch() ?: [];

    json_response([
        'success'  => true,
        'products' => (int) $db->query('SELECT COUNT(*) FROM products')->fetchColumn(),
        'total'    => (int) ($s['toplam'] ?? 0),
        'create'   => (int) ($s['eklendi'] ?? 0),
        'update'   => (int) ($s['guncellendi'] ?? 0),
        'delete'   => (int) ($s['silindi'] ?? 0),
        'last'     => !empty($s['son']) ? date('d.m.Y H:i:s', strtotime((string) $s['son'])) : '—',
    ]);
}

/* =====================================================================
 *  DENETİM KAYDI DETAYI  (alan-bazlı eski → yeni)
 * ================================================================== */
function handle_audit_detail(PDO $db): void
{
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if (!$id) {
        json_error('Geçersiz kayıt.', 400);
    }
    $stmt = $db->prepare(
        'SELECT id, created_at, actor_name, action, entity_type, entity_id,
                old_values, new_values, ip, user_agent
         FROM audit_log WHERE id = :id'
    );
    $stmt->execute([':id' => $id]);
    $r = $stmt->fetch();
    if (!$r) {
        json_error('Kayıt bulunamadı.', 404);
    }

    $old = json_decode((string) $r['old_values'], true) ?: [];
    $new = json_decode((string) $r['new_values'], true) ?: [];

    // Görüntülenecek alanların birleşimi (create → yalnızca new, delete → yalnızca old)
    $fields = array_values(array_unique(array_merge(array_keys($old), array_keys($new))));
    $diff = [];
    foreach ($fields as $f) {
        $diff[] = [
            'field' => $f,
            'label' => field_label($f),   // ekranda Türkçe, kayıtta ham alan adı
            'old'   => $old[$f] ?? null,
            'new'   => $new[$f] ?? null,
        ];
    }

    json_response([
        'success' => true,
        'meta'    => [
            'id'         => (int) $r['id'],
            'time'       => date('d.m.Y H:i:s', strtotime((string) $r['created_at'])),
            'actor'      => $r['actor_name'],
            'action'     => $r['action'],
            'action_tr'  => action_label((string) $r['action']),
            'entity'     => entity_label((string) $r['entity_type']) . ' #' . (int) $r['entity_id'],
            'ip'         => $r['ip'],
            'user_agent' => $r['user_agent'],
        ],
        'diff'    => $diff,
    ]);
}
