/* =====================================================================
 *  İŞLEM GEÇMİŞİ – ARAYÜZ MANTIĞI
 *  cilginyazilim.com – İşlem Geçmişi (Audit Log)
 * ---------------------------------------------------------------------
 *  BU DOSYANIN ÖĞRETTİĞİ ASIL KAVRAM:
 *  DENETİM KAYDI, ONU ÜRETEN İŞLEMLE AYNI ANDA TAZELENMELİDİR.
 *
 *  Ekranda iki tablo var: üstte ürünler (yazılabilir), altta denetim
 *  kaydı (salt-okunur). Bir ürünü kaydettiğinizde YALNIZCA ürün
 *  tablosunu yenilemek, denetim tablosunu eski hâlinde bırakırdı ve
 *  kullanıcı "işlem kaydedildi mi?" sorusunu göremezdi.
 *
 *  Bu yüzden her yazma işleminden sonra refreshAll() çağrılır: iki tablo
 *  ve sayaç şeridi birlikte tazelenir. Denetim kaydının değeri, işlemin
 *  HEMEN ARDINDAN görünür olmasındadır.
 *
 *  İKİNCİ KAVRAM: DIFF HÜCRELERİ ASLA .html() İLE YAZILMAZ.
 *  Denetim kaydındaki eski/yeni değerler doğrudan kullanıcı verisidir
 *  (bir ürün adı `<script>` olabilir). Bunları kaçışlamadan basmak,
 *  denetim ekranını açan HERKESİ hedef alan bir XSS açığı olurdu — ve
 *  denetim ekranını genellikle en yetkili kullanıcılar açar.
 *
 *  BÖLÜMLER
 *    1  Yardımcılar (toast, post, esc)
 *    2  Ortak DataTables ayarları
 *    3  Ürünler tablosu
 *    4  İşlem geçmişi tablosu + filtreler
 *    5  Sayaç şeridi
 *    6  Ürün ekle / düzenle
 *    7  Silme onayı
 *    8  İşlem detayı (alan bazlı diff)
 *    9  CSV dışa aktarım
 * ================================================================== */

/* global jQuery, bootstrap */

(function ($) {
    'use strict';

    var ENDPOINT = 'system/ajax.php';

    /* =================================================================
     *  BÖLÜM 1 – YARDIMCILAR
     * ================================================================= */

    function toast(msg, type) {
        var el = $('<div class="cy-toast cy-toast--' + (type || 'info') + '" role="status"></div>').text(msg);

        $('#cy-toast-area').append(el);

        setTimeout(function () {
            el.addClass('cy-toast--out');
            setTimeout(function () { el.remove(); }, 300);
        }, 3600);
    }

    /** action + csrf_token'ı tek yerden ekler. */
    function post(action, data) {
        data = data || {};
        data.action = action;
        data.csrf_token = window.CY_CSRF;

        return $.ajax({ url: ENDPOINT, method: 'POST', data: data, dataType: 'json' });
    }

    /** Sunucudan gelen hata mesajını tek yerden okur. */
    function failMessage(xhr) {
        return (xhr.responseJSON || {}).description || 'Beklenmeyen bir hata oluştu.';
    }

    /**
     * Bir metni HTML olarak GÜVENLİ hâle getirir.
     *
     * $('<i>').text(s).html() kalıbı: metni bir elemana .text() ile
     * yazar (tarayıcı kaçışlar), sonra .html() ile kaçışlanmış hâlini
     * geri okur. Böylece dizgi birleştirerek HTML kurarken bile
     * kullanıcı verisi etiket olarak yorumlanmaz.
     */
    function esc(s) {
        return $('<i>').text(s == null ? '' : s).html();
    }

    /* Filtre alanlarının o anki değerleri. Liste isteği, CSV dışa
     * aktarımı ve "filtre temizle" — üçü de buradan okur; tek kaynak. */
    function filterValues() {
        return {
            f_action: $('#f-action').val(),
            f_entity: $('#f-entity').val(),
            f_from:   $('#f-from').val(),
            f_to:     $('#f-to').val()
        };
    }

    /* =================================================================
     *  BÖLÜM 2 – ORTAK DataTables AYARLARI
     * ---------------------------------------------------------------
     *  serverSide: true — sayfalama, arama ve sıralama SUNUCUDA yapılır.
     *  Denetim kaydı hızla büyüyen bir tablodur; 100.000 satırı tarayıcıya
     *  indirmek hem yavaştır hem anlamsızdır. Bu ayar tarayıcıya yalnızca
     *  görüntülenen sayfanın gönderilmesini sağlar.
     * ================================================================= */

    var dtCommon = {
        serverSide: true,
        processing: true,          // "Yükleniyor…" göstergesi
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        autoWidth: false,
        language: {
            emptyTable:   'Henüz kayıt yok',
            zeroRecords:  'Eşleşen kayıt bulunamadı',
            processing:   'Yükleniyor…',
            paginate:     { previous: '‹', next: '›', first: '«', last: '»' },
            info:         '_START_–_END_ / _TOTAL_',
            infoEmpty:    '0 kayıt',
            infoFiltered: '(_MAX_ kayıt içinde)',
            lengthMenu:   '_MENU_ kayıt',
            search:       '',
            searchPlaceholder: 'Ara…'
        }
    };

    /* =================================================================
     *  BÖLÜM 3 – ÜRÜNLER TABLOSU (denetlenen varlık)
     * ================================================================= */

    var products = $('#tbl-products').DataTable($.extend({}, dtCommon, {
        order: [[0, 'desc']],      // en yeni ürün üstte

        /* İşlem sütunu (6) ne sıralanabilir ne aranabilir: o bir
         * veritabanı sütunu değil, sunucuda üretilen bir HTML parçasıdır.
         * Sıralanabilir bırakılsaydı, istemci o indisi gönderdiğinde
         * sunucu beyaz listede bulamayıp sessizce 'id'ye düşerdi —
         * çalışır ama kafa karıştırır. */
        columnDefs: [
            { targets: [6], orderable: false, searchable: false, className: 'text-end' },
            { targets: [3, 4], className: 'text-end' }
        ],

        /* DataTables'ın hazır ajax nesnesi yerine fonksiyon kullanıyoruz;
         * böylece her isteğe action ve csrf_token ekleyebiliyoruz. */
        ajax: function (params, cb) {
            params.action = 'product_list';
            params.csrf_token = window.CY_CSRF;

            $.post(ENDPOINT, params, cb, 'json').fail(function (xhr) {
                toast(failMessage(xhr), 'danger');
            });
        }
    }));

    /* =================================================================
     *  BÖLÜM 4 – İŞLEM GEÇMİŞİ TABLOSU (salt-okunur)
     * ================================================================= */

    var audit = $('#tbl-audit').DataTable($.extend({}, dtCommon, {
        order: [[0, 'desc']],      // en son işlem üstte

        // 5 = "N alan" özeti, 6 = detay butonu — ikisi de türetilmiş sütun.
        columnDefs: [
            { targets: [5, 6], orderable: false, searchable: false },
            { targets: [6], className: 'text-end' }
        ],

        ajax: function (params, cb) {
            params.action = 'audit_list';
            params.csrf_token = window.CY_CSRF;

            /* Sunucu tarafı filtreler: DataTables'ın kendi arama
             * kutusundan BAĞIMSIZ gönderilir. Böylece "yalnızca silme
             * işlemleri" filtresi arama terimiyle BİRLİKTE çalışır. */
            $.extend(params, filterValues());

            $.post(ENDPOINT, params, cb, 'json').fail(function (xhr) {
                toast(failMessage(xhr), 'danger');
            });
        },

        /* Her çizimden sonra üst rozeti filtrelenmiş sayıyla güncelle. */
        drawCallback: function () {
            var info = this.api().page.info();
            $('#audit-count-badge').text(info.recordsDisplay.toLocaleString('tr-TR') + ' kayıt');
        }
    }));

    /* Filtre değişince tabloyu BAŞTAN yükle.
     * ajax.reload() parametresiz çağrıldığında sayfa 1'e döner — doğru
     * davranış: filtre değiştiyse 5. sayfada kalmak anlamsızdır. */
    $('#audit-filters').on('change', 'select, input', function () {
        audit.ajax.reload();
    });

    $('#btn-reset').on('click', function () {
        $('#audit-filters')[0].reset();
        audit.search('').ajax.reload();
        toast('Filtreler temizlendi.', 'info');
    });

    /* =================================================================
     *  BÖLÜM 5 – SAYAÇ ŞERİDİ
     * ================================================================= */

    function refreshStats() {
        post('stats').done(function (s) {
            $('#stat-products').text(s.products.toLocaleString('tr-TR'));
            $('#stat-total').text(s.total.toLocaleString('tr-TR'));
            $('#stat-create').text(s.create.toLocaleString('tr-TR'));
            $('#stat-update').text(s.update.toLocaleString('tr-TR'));
            $('#stat-delete').text(s.delete.toLocaleString('tr-TR'));
            $('#stat-last').text(s.last);
        });
    }

    /**
     * Her yazma işleminden sonra: iki tablo + sayaçlar.
     *
     * reload(null, false) → ikinci parametre resetPaging; false vermek
     * kullanıcıyı bulunduğu sayfada tutar. Bir ürünü düzenledikten sonra
     * 3. sayfadan 1. sayfaya fırlamak sinir bozucu olurdu.
     *
     * Denetim tablosu için ise sayfalama SIFIRLANIR: yeni kayıt en üste
     * düşer, kullanıcının onu görmesini istiyoruz.
     */
    function refreshAll() {
        products.ajax.reload(null, false);
        audit.ajax.reload(null, true);
        refreshStats();
    }

    refreshStats();

    /* =================================================================
     *  BÖLÜM 6 – ÜRÜN EKLE / DÜZENLE
     * ================================================================= */

    var modalProduct = new bootstrap.Modal('#modal-product');

    function clearErrors($form) {
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').text('');
    }

    $('#add_button').on('click', function () {
        var $f = $('#form-product')[0];

        $f.reset();
        $f.id.value = '';                       // id boş = EKLEME modu

        $('#modal-product-title').text('Yeni Ürün');
        clearErrors($('#form-product'));

        modalProduct.show();
    });

    /* OLAY DELEGASYONU: satırlar AJAX ile sonradan geldiği için
     * $('.js-edit').on('click', …) çalışmazdı — o butonlar bağlama
     * anında DOM'da yoktur. Tabloya bağlanıp seçiciyi ikinci parametre
     * olarak vermek, gelecekte eklenecek satırları da kapsar. */
    $('#tbl-products').on('click', '.js-edit', function () {
        var id = $(this).data('id');

        /* Formu listeden değil, SUNUCUDAN gelen taze veriyle dolduruyoruz.
         * Listedeki hücreler biçimlendirilmiştir ("4.299,00 ₺"); onları
         * geri ayrıştırmak hem kırılgan hem gereksizdir. */
        post('product_fetch', { id: id })
            .done(function (res) {
                var p = res.product;
                var $f = $('#form-product')[0];

                $f.id.value    = p.id;
                $f.name.value  = p.name;
                $f.sku.value   = p.sku;
                $f.price.value = p.price;
                $f.stock.value = p.stock;

                $('#modal-product-title').text('Ürün #' + p.id);
                clearErrors($('#form-product'));

                modalProduct.show();
            })
            .fail(function (xhr) { toast(failMessage(xhr), 'danger'); });
    });

    $('#form-product').on('submit', function (ev) {
        ev.preventDefault();

        var $form = $(this);
        var $btn  = $('#btn-save');

        clearErrors($form);

        var data = $form.serializeArray().reduce(function (a, f) {
            a[f.name] = f.value;
            return a;
        }, {});

        // Çift gönderim koruması: yavaş bağlantıda kullanıcı iki kez
        // basarsa iki ayrı kayıt (ve iki ayrı denetim satırı) oluşurdu.
        $btn.prop('disabled', true).addClass('is-busy');

        post('product_save', data)
            .done(function (res) {
                /* Sunucu "Ürün güncellendi (2 alan)." gibi bir mesaj döner.
                 * Alan sayısı diff'ten gelir; hiçbir şey değişmediyse
                 * "Değişiklik yapılmadı." der. Bu, denetim kaydının ne
                 * yazacağını kullanıcıya önceden söyler. */
                toast(res.description, 'success');
                modalProduct.hide();
                refreshAll();
            })
            .fail(function (xhr) {
                var res = xhr.responseJSON || {};

                // 422 → alan bazlı doğrulama, 409 → SKU çakışması
                $.each(res.errors || {}, function (field, msg) {
                    $form.find('[name="' + field + '"]').addClass('is-invalid');
                    $form.find('.invalid-feedback[data-for="' + field + '"]').text(msg);
                });

                toast(res.description || 'Kayıt edilemedi.', 'danger');
            })
            .always(function () {
                $btn.prop('disabled', false).removeClass('is-busy');
            });
    });

    /* =================================================================
     *  BÖLÜM 7 – SİLME ONAYI
     * ================================================================= */

    var modalDelete = new bootstrap.Modal('#modal-delete');
    var deleteId = null;

    $('#tbl-products').on('click', '.js-del', function () {
        deleteId = $(this).data('id');

        // .text() ile yazıyoruz: ürün adı kullanıcı verisidir.
        $('#delete-name').text($(this).data('name') || ('#' + deleteId));
        modalDelete.show();
    });

    $('#btn-delete-confirm').on('click', function () {
        var $btn = $(this).prop('disabled', true);

        post('product_delete', { id: deleteId })
            .done(function (res) {
                toast(res.description, 'success');
                modalDelete.hide();
                refreshAll();          // silme de bir denetim kaydı üretir
            })
            .fail(function (xhr) { toast(failMessage(xhr), 'danger'); })
            .always(function () { $btn.prop('disabled', false); });
    });

    /* =================================================================
     *  BÖLÜM 8 – İŞLEM DETAYI (alan bazlı diff)
     * ================================================================= */

    var modalDetail = new bootstrap.Modal('#modal-detail');

    /**
     * Bir denetim kaydını aç.
     *
     * Ayrı fonksiyon olmasının sebebi PAYLAŞILABİLİR BAĞLANTI: modal
     * açılınca adres çubuğuna #islem-42 yazılır. Denetim kaydının en
     * sık kullanımı "şuna bir bak" demektir; ekran görüntüsü göndermek
     * yerine bağlantı gönderilebilmesi işi hızlandırır.
     *
     * history.replaceState kullanıyoruz (pushState değil): tarayıcının
     * geri tuşu, açtığınız her modal için bir adım biriktirmesin.
     */
    function openDetail(id) {
        post('audit_detail', { id: id })
            .done(function (res) {
                var m = res.meta;

                $('#modal-detail-title').text('İşlem #' + m.id);

                /* Meta alanları kontrollü kaynaklardan gelir (oturum, IP,
                 * sabit metin) ama yine de esc()'den geçiriyoruz: yarın
                 * buraya serbest metin bir alan eklendiğinde açık
                 * oluşmasın. Güvenlik, "şu an gerekli mi" sorusuyla değil
                 * "yarın unutulur mu" sorusuyla kurulur. */
                $('#detail-meta').html(
                    '<dt>Zaman</dt><dd>' + esc(m.time) + '</dd>' +
                    '<dt>Aktör</dt><dd>' + esc(m.actor) + '</dd>' +
                    '<dt>İşlem</dt><dd><span class="cy-op cy-op--' + esc(m.action) + '">' +
                        esc(m.action_tr) + '</span></dd>' +
                    '<dt>Varlık</dt><dd>' + esc(m.entity) + '</dd>' +
                    '<dt>IP</dt><dd><code>' + esc(m.ip) + '</code></dd>'
                );

                $('#detail-ua').text(m.user_agent ? 'Tarayıcı: ' + m.user_agent : '');

                var $tb = $('#detail-diff').empty();

                if (!res.diff.length) {
                    $tb.append('<tr><td colspan="3" class="cy-empty">Bu işlemde alan değişikliği kaydedilmemiş.</td></tr>');
                } else {
                    res.diff.forEach(function (d) {
                        /* Değişen satırı vurgula. Kıyas string'e çevrilerek
                         * yapılır — sunucudaki diff_values() ile AYNI kural.
                         * İki taraf farklı kıyaslasaydı, sunucunun
                         * "değişti" dediği bir alan burada vurgusuz
                         * görünürdü. */
                        var changed = String(d.old) !== String(d.new);

                        /* null = o alan bu işlemde YOKTU:
                         *   create → old null (kayıt daha yoktu)
                         *   delete → new null (kayıt artık yok)
                         * Bunu boş hücre yerine "—" ile göstermek,
                         * "değeri boş string'di" ile "alan hiç yoktu"
                         * ayrımını korur. */
                        function cell(v, cls) {
                            return v === null
                                ? '<td class="cy-muted">—</td>'
                                : '<td class="' + cls + '"><span>' + esc(v) + '</span></td>';
                        }

                        $tb.append(
                            '<tr class="' + (changed ? 'cy-diff--changed' : '') + '">' +
                                '<td><b>' + esc(d.label) + '</b>' +
                                    '<small class="d-block cy-muted"><code>' + esc(d.field) + '</code></small></td>' +
                                cell(d.old, 'cy-diff__old') +
                                cell(d.new, 'cy-diff__new') +
                            '</tr>'
                        );
                    });
                }

                modalDetail.show();

                if (window.history && history.replaceState) {
                    history.replaceState(null, '', '#islem-' + m.id);
                }
            })
            .fail(function (xhr) { toast(failMessage(xhr), 'danger'); });
    }

    $('#tbl-audit').on('click', '.js-detail', function () {
        openDetail($(this).data('id'));
    });

    /* Modal kapanınca adresteki çapa temizlensin; sayfayı yenileyen
     * kullanıcı aynı modalla karşılaşmasın diye değil — yenilerse
     * karşılaşmalı; ama "kapattım, hâlâ adreste duruyor" hâli
     * kafa karıştırıcı olurdu. */
    $('#modal-detail').on('hidden.bs.modal', function () {
        if (window.history && history.replaceState && location.hash.indexOf('#islem-') === 0) {
            history.replaceState(null, '', location.pathname + location.search);
        }
    });

    /* Sayfa #islem-42 ile açıldıysa o kaydı doğrudan göster. */
    (function openFromHash() {
        var m = /^#islem-(\d+)$/.exec(location.hash || '');
        if (m) {
            openDetail(parseInt(m[1], 10));
        }
    })();

    /* =================================================================
     *  BÖLÜM 9 – CSV DIŞA AKTARIM
     * ---------------------------------------------------------------
     *  Dosya indirmeyi AJAX ile yapamayız: tarayıcı yanıtı JS'e verir,
     *  "Farklı kaydet" penceresi açılmaz. Bu yüzden ekranda görünmeyen
     *  bir <form> kurup POST ediyoruz — böylece CSRF anahtarı ve o anki
     *  filtreler de gövdede gider, tarayıcı da yanıtı indirme olarak
     *  işler (sunucu Content-Disposition: attachment gönderiyor).
     * ================================================================= */

    $('#btn-export').on('click', function () {
        var values = filterValues();

        values.action       = 'audit_export';
        values.csrf_token   = window.CY_CSRF;
        values.search_value = audit.search();   // ekrandaki arama terimi de dahil

        var $form = $('<form method="post" target="_blank"></form>').attr('action', ENDPOINT);

        $.each(values, function (k, v) {
            $form.append($('<input type="hidden">').attr('name', k).val(v == null ? '' : v));
        });

        $form.appendTo('body').trigger('submit').remove();

        toast('CSV hazırlanıyor…', 'info');
    });

})(jQuery);
