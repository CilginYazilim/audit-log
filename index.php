<?php
/**
 * =====================================================================
 *  ARAYÜZ (Sunum Katmanı)
 *  cilginyazilim.com – İşlem Geçmişi (Audit Log)
 * ---------------------------------------------------------------------
 *  Ekran iki tablodan oluşur:
 *
 *    ÜSTTE  → Ürünler. Yazılabilir. Ekleme / düzenleme / silme burada.
 *    ALTTA  → İşlem geçmişi. SALT-OKUNUR. Üstte yapılan her işlem,
 *             hiçbir ek kod yazılmadan buraya düşer.
 *
 *  Bu ayrım bilinçlidir ve projenin bütün fikri budur: denetim kaydı,
 *  denetlediği ekranın YANINDA durmalı ki değeri görülebilsin. Ayrı bir
 *  "loglar" sayfasına gömülen denetim kaydına kimse bakmaz.
 *
 *  Bu dosya veritabanına DOKUNMAZ. Tüm veri system/ajax.php üzerinden,
 *  AJAX ile gelir.
 * =====================================================================
 */

declare(strict_types=1);

define('CY_APP', true);
require __DIR__ . '/system/config.php';
require __DIR__ . '/system/function.php';

$token = csrf_token();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="author" content="Çılgın Yazılım - cilginyazilim.com">
    <meta name="description" content="PHP PDO ve MySQL ile append-only işlem geçmişi (audit log): tek yazma noktası, alan bazlı diff, hassas alan maskeleme, CSV dışa aktarım.">
    <meta name="theme-color" content="#0b5cb5">

    <title>İşlem Geçmişi (Audit Log) | Çılgın Yazılım</title>

    <link rel="icon" type="image/png" href="assets/images/logo.png">

    <!--
        CSS YÜKLEME SIRASI ÖNEMLİDİR:
          1) bootstrap      → temel çatı
          2) dataTables     → tablo eklentisi stilleri
          3) cilginyazilim  → MARKA TASARIM KALIBI (Bootstrap'i ezer)
          4) style          → yalnızca bu sayfaya özel eklemeler
    -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="assets/css/cilginyazilim.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="cy-app">

<!-- Sayfanın en üstündeki ince marka şeridi -->
<div class="cy-topbar"></div>

<div class="container py-4 py-lg-5">

    <!-- =================================================================
         ANA KART – ÜRÜNLER (denetlenen varlık)
         ================================================================= -->
    <div class="cy-card mb-4">

        <div class="cy-card__header">
            <div class="cy-header-top d-flex flex-wrap justify-content-between align-items-center gap-3">

                <a class="cy-brand" href="https://cilginyazilim.com" target="_blank" rel="noopener">
                    <span class="cy-brand__mark">
                        <img src="assets/images/logo.png" alt="Çılgın Yazılım logosu">
                    </span>
                    <div>
                        <h1 class="cy-brand__title">İşlem Geçmişi (Audit Log)</h1>
                        <p class="cy-brand__subtitle">
                            Tek yazma noktası &middot; Alan bazlı diff &middot; Maskeleme &middot; Append-only
                        </p>
                    </div>
                </a>

                <div class="cy-header-controls d-flex align-items-center gap-2 flex-wrap">
                    <span class="cy-badge cy-badge--glass">
                        <strong id="stat-products">0</strong> ürün
                    </span>

                    <a class="btn cy-btn cy-btn--glass"
                       href="https://github.com/CilginYazilim/audit-log"
                       target="_blank" rel="noopener" title="Projeyi GitHub'da aç">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" style="vertical-align:-2px">
                            <path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27s1.36.09 2 .27c1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.01 8.01 0 0 0 16 8c0-4.42-3.58-8-8-8Z"/>
                        </svg>
                        <span class="cy-header-controls__label">GitHub</span>
                    </a>

                    <button type="button" id="add_button" class="btn cy-btn cy-btn--onbrand">
                        <span aria-hidden="true">＋</span> Yeni Ürün
                    </button>
                </div>
            </div>
        </div>

        <div class="cy-card__body">

            <!-- ---------------------------------------------------------
                 SAYAÇ ŞERİDİ
                 Denetim kaydının "nabzı". Her yazma işleminden sonra
                 tazelenir; kullanıcı işleminin geçmişe düştüğünü tabloyu
                 okumadan da görür.
                 --------------------------------------------------------- -->
            <div class="cy-stats" id="cy-stats">
                <div class="cy-stat">
                    <span class="cy-stat__value" id="stat-total">0</span>
                    <span class="cy-stat__label">Toplam kayıt</span>
                </div>
                <div class="cy-stat cy-stat--create">
                    <span class="cy-stat__value" id="stat-create">0</span>
                    <span class="cy-stat__label">Eklendi</span>
                </div>
                <div class="cy-stat cy-stat--update">
                    <span class="cy-stat__value" id="stat-update">0</span>
                    <span class="cy-stat__label">Güncellendi</span>
                </div>
                <div class="cy-stat cy-stat--delete">
                    <span class="cy-stat__value" id="stat-delete">0</span>
                    <span class="cy-stat__label">Silindi</span>
                </div>
                <div class="cy-stat cy-stat--wide">
                    <span class="cy-stat__value cy-stat__value--sm" id="stat-last">—</span>
                    <span class="cy-stat__label">Son işlem</span>
                </div>
            </div>

            <h2 class="cy-section-title">Ürünler <small>yazılabilir</small></h2>

            <div class="table-responsive">
                <table id="tbl-products" class="table cy-table w-100">
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Ad</th>
                            <th scope="col">SKU</th>
                            <th scope="col">Fiyat</th>
                            <th scope="col">Stok</th>
                            <th scope="col">Güncelleme</th>
                            <th scope="col" class="text-end">İşlem</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>

        <div class="cy-card__footer d-flex flex-wrap justify-content-between gap-2">
            <span>Buradaki her işlem, aşağıdaki geçmişe <b>otomatik</b> düşer.</span>
            <span>PHP <?= e(PHP_VERSION) ?></span>
        </div>
    </div>

    <!-- =================================================================
         İŞLEM GEÇMİŞİ (salt-okunur)
         ================================================================= -->
    <div class="cy-card">

        <div class="cy-card__header">
            <div class="cy-header-top d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h2 class="cy-brand__title mb-0">İşlem Geçmişi</h2>
                    <p class="cy-brand__subtitle mb-0">
                        Salt-okunur &middot; <code>audit_log</code> tablosuna UPDATE/DELETE yolu yoktur
                    </p>
                </div>

                <div class="cy-header-controls d-flex align-items-center gap-2 flex-wrap">
                    <span class="cy-badge cy-badge--glass" id="audit-count-badge">0 kayıt</span>

                    <button type="button" id="btn-export" class="btn cy-btn cy-btn--glass"
                            title="Görüntülenen filtreyle CSV indir">
                        <span aria-hidden="true">⇩</span>
                        <span class="cy-header-controls__label">CSV</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="cy-card__body">

            <!-- ---------------------------------------------------------
                 FİLTRE ÇUBUĞU
                 Bu alanlar DataTables'ın kendi arama kutusundan BAĞIMSIZ
                 olarak sunucuya gider (bkz. assets/js/audit.js). Böylece
                 "yalnızca silme işlemleri" filtresi arama terimiyle
                 BİRLİKTE çalışır.
                 --------------------------------------------------------- -->
            <form id="audit-filters" class="cy-filters row g-2 align-items-end mb-3" novalidate>
                <div class="col-6 col-lg-3">
                    <label class="form-label" for="f-action">İşlem türü</label>
                    <select id="f-action" class="form-select">
                        <option value="">Tümü</option>
                        <option value="create">Eklendi</option>
                        <option value="update">Güncellendi</option>
                        <option value="delete">Silindi</option>
                    </select>
                </div>

                <div class="col-6 col-lg-3">
                    <label class="form-label" for="f-entity">Varlık türü</label>
                    <select id="f-entity" class="form-select">
                        <option value="">Tümü</option>
                        <?php foreach (AUDIT_ENTITIES as $key => $label): ?>
                            <option value="<?= e($key) ?>"><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-6 col-lg-2">
                    <label class="form-label" for="f-from">Başlangıç</label>
                    <input type="date" id="f-from" class="form-control">
                </div>

                <div class="col-6 col-lg-2">
                    <label class="form-label" for="f-to">Bitiş</label>
                    <input type="date" id="f-to" class="form-control">
                </div>

                <div class="col-12 col-lg-2 d-grid">
                    <button type="button" id="btn-reset" class="btn cy-btn">Filtreyi temizle</button>
                </div>
            </form>

            <div class="table-responsive">
                <table id="tbl-audit" class="table cy-table w-100">
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Zaman</th>
                            <th scope="col">Aktör</th>
                            <th scope="col">İşlem</th>
                            <th scope="col">Varlık</th>
                            <th scope="col">Değişiklik</th>
                            <th scope="col" class="text-end">Detay</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>

        <div class="cy-card__footer d-flex flex-wrap justify-content-between gap-2">
            <span>Hassas alanlar (<code>password</code>, <code>token</code>, <code>cvv</code> …) loga <b>***</b> olarak yazılır.</span>
            <span>Kayıtlar append-only</span>
        </div>
    </div>

    <div class="cy-footer-note mt-4">
        <p class="mb-1">
            Bu açık kaynak örnek, <a href="https://cilginyazilim.com" target="_blank" rel="noopener">cilginyazilim.com</a>
            tarafından geliştirilmiştir. MIT lisanslıdır; dilediğiniz gibi indirip kullanabilirsiniz.
        </p>
        <p class="mb-1">
            Katkı sağlamak ister misiniz? Depoyu çatallayın (fork) ve pull request gönderin:
            <a href="https://github.com/CilginYazilim/audit-log"
               target="_blank" rel="noopener">github.com/CilginYazilim</a>
        </p>
        <p class="mb-0">
            Aynı tasarım kalıbıyla hazırlanmış diğer açıklamalı örnekler:
            <a href="https://cilginyazilim.com/kutuphane" target="_blank" rel="noopener">cilginyazilim.com/kutuphane</a>
        </p>
    </div>
</div>


<!-- =====================================================================
     MODAL 1 – ÜRÜN FORMU (ekleme ve düzenleme AYNI modal)
     ===================================================================== -->
<div class="modal fade" id="modal-product" tabindex="-1" aria-labelledby="modal-product-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content cy-modal">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-product-title">Yeni Ürün</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>

            <form id="form-product" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="id">

                    <div class="mb-3">
                        <label class="form-label" for="p-name">Ürün adı</label>
                        <input id="p-name" name="name" class="form-control" maxlength="150" autocomplete="off">
                        <div class="invalid-feedback" data-for="name"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="p-sku">Stok kodu (SKU)</label>
                        <input id="p-sku" name="sku" class="form-control text-uppercase" maxlength="40" autocomplete="off">
                        <div class="invalid-feedback" data-for="sku"></div>
                        <div class="form-text">Büyük harf, rakam ve tire. Örn. <code>KBD-WL-001</code></div>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label" for="p-price">Fiyat (₺)</label>
                            <input id="p-price" name="price" type="number" step="0.01" min="0" class="form-control" inputmode="decimal">
                            <div class="invalid-feedback" data-for="price"></div>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label" for="p-stock">Stok</label>
                            <input id="p-stock" name="stock" type="number" min="0" class="form-control" inputmode="numeric">
                            <div class="invalid-feedback" data-for="stock"></div>
                        </div>
                    </div>

                    <p class="cy-hint mb-0">
                        Kaydettiğinizde bu işlem, aşağıdaki geçmişe <b>otomatik</b> olarak düşer.
                    </p>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn cy-btn" data-bs-dismiss="modal">Vazgeç</button>
                    <button type="submit" class="btn cy-btn cy-btn--primary" id="btn-save">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- =====================================================================
     MODAL 2 – SİLME ONAYI
     ---------------------------------------------------------------------
     Tarayıcının confirm() kutusu yerine kendi modalımız: hem markayla
     uyumlu hem de "bu işlem denetime kaydedilir" uyarısını taşıyabiliyor.
     Kullanıcı, iz bıraktığını BİLEREK silmelidir.
     ===================================================================== -->
<div class="modal fade" id="modal-delete" tabindex="-1" aria-labelledby="modal-delete-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content cy-modal">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-delete-title">Ürünü sil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body text-center">
                <div class="cy-danger-icon" aria-hidden="true">🗑</div>
                <p class="mb-1"><b id="delete-name">—</b> silinecek.</p>
                <p class="cy-hint mb-0">Bu işlem geri alınamaz, ancak <b>denetime kaydedilir</b>.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn cy-btn" data-bs-dismiss="modal">Vazgeç</button>
                <button type="button" class="btn cy-btn cy-btn--danger" id="btn-delete-confirm">Evet, sil</button>
            </div>
        </div>
    </div>
</div>


<!-- =====================================================================
     MODAL 3 – İŞLEM DETAYI (alan bazlı eski → yeni)
     ===================================================================== -->
<div class="modal fade" id="modal-detail" tabindex="-1" aria-labelledby="modal-detail-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content cy-modal">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-detail-title">İşlem detayı</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body">
                <dl class="cy-detail" id="detail-meta"></dl>

                <h3 class="cy-section-title mt-3">Değişen alanlar</h3>

                <div class="table-responsive">
                    <table class="table cy-table cy-diff mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Alan</th>
                                <th scope="col">Eski değer</th>
                                <th scope="col">Yeni değer</th>
                            </tr>
                        </thead>
                        <tbody id="detail-diff"></tbody>
                    </table>
                </div>

                <p class="cy-hint mt-3 mb-0" id="detail-ua"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn cy-btn" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>


<div id="cy-toast-area" class="cy-toast-area" aria-live="polite" aria-atomic="true"></div>

<script>window.CY_CSRF = <?= json_encode($token) ?>;</script>
<script src="assets/js/jquery-3.7.0.js"></script>
<script src="assets/js/bootstrap.bundle.js"></script>
<script src="assets/js/jquery.dataTables.min.js"></script>
<script src="assets/js/dataTables.bootstrap5.min.js"></script>
<script src="assets/js/audit.js"></script>
</body>
</html>
