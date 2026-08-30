<div align="center">

<img src="assets/images/logo.png" alt="Çılgın Yazılım" width="90">

# İşlem Geçmişi (Audit Log)

### PHP PDO · MySQL · AJAX · DataTables · Bootstrap 5 · Çılgın Yazılım Tasarım Kalıbı

**Her değişikliğin izini bırakan, kendisi değiştirilemeyen kayıt.**

[![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-4479A1?style=flat-square&logo=mysql&logoColor=white)](https://mysql.com)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.2-7952B3?style=flat-square&logo=bootstrap&logoColor=white)](https://getbootstrap.com)
[![DataTables](https://img.shields.io/badge/DataTables-1.13-0f5499?style=flat-square)](https://datatables.net)
[![License](https://img.shields.io/badge/Lisans-MIT-16a34a?style=flat-square)](LICENSE)

**🇹🇷 Türkçe** &nbsp;·&nbsp; [🇬🇧 English](README.en.md)

[**▶ Canlı Demo**](https://cilginyazilim.com/kutuphane/uygulama/PHP-MySQL-Audit-Log-Islem-Gecmisi-PDO-Ajax-DataTables-main/) &nbsp;·&nbsp; [Kaynak Kütüphanesi](https://cilginyazilim.com/kutuphane/php-audit-log) &nbsp;·&nbsp; [cilginyazilim.com](https://cilginyazilim.com)

</div>

---

<div align="center">

## Canlı Demo

**Kurulum yok, kayıt yok, indirme yok — tarayıcınızdan 3 saniyede deneyin.**

<a href="https://cilginyazilim.com/kutuphane/uygulama/PHP-MySQL-Audit-Log-Islem-Gecmisi-PDO-Ajax-DataTables-main/"><img src="https://img.shields.io/badge/CANLI_DEMOYU_A%C3%87-0b5cb5?style=for-the-badge&logo=googlechrome&logoColor=white&labelColor=061321" alt="Canlı Demoyu Aç" height="42"></a>
&nbsp;
<a href="https://cilginyazilim.com/kutuphane/php-audit-log"><img src="https://img.shields.io/badge/KAYNAK_KODU_%C4%B0NCELE-0ea5e9?style=for-the-badge&logo=readthedocs&logoColor=white&labelColor=061321" alt="Kaynak Kodu İncele" height="42"></a>
&nbsp;
<a href="https://github.com/CilginYazilim/PHP-MySQL-Audit-Log-Islem-Gecmisi-PDO-Ajax-DataTables/archive/refs/heads/main.zip"><img src="https://img.shields.io/badge/ZIP_%C4%B0ND%C4%B0R-16a34a?style=for-the-badge&logo=github&logoColor=white&labelColor=061321" alt="ZIP İndir" height="42"></a>

<br><br>

<a href="https://cilginyazilim.com/kutuphane/uygulama/PHP-MySQL-Audit-Log-Islem-Gecmisi-PDO-Ajax-DataTables-main/" title="Canlı demoyu açmak için tıklayın">
  <img src="docs/screenshots/01-genel-gorunum.png" alt="İşlem geçmişi canlı demo önizlemesi" width="860">
</a>

<sub>▲ Görsele tıklayarak demoyu açabilirsiniz</sub>

</div>

<br>

### Demoda 60 saniyede neleri deneyebilirsiniz?

| # | Şunu deneyin | Perde arkasında ne oluyor? |
|:-:|--------------|----------------------------|
| **1** | Bir ürünün **fiyatını** değiştirip kaydedin | Alttaki geçmişe anında `Güncellendi` satırı düşer; toast **"1 alan"** der — bu sayı tahmin değil, sunucudaki `diff_values()` çıktısıdır |
| **2** | Aynı ürünü **hiçbir şey değiştirmeden** tekrar kaydedin | Geçmişe **satır eklenmez**. "Kaydet'e bastım" bir olay değildir; denetim kaydı yalnızca gerçek değişikliği tutar |
| **3** | 👁 **Göz** butonuna basın | Alan bazlı diff açılır: eski değer üstü çizili kırmızı, yeni değer yeşil. Değişmeyen alanlar listede **hiç yoktur** |
| **4** | Detay modalı açıkken **adres çubuğuna** bakın | `#islem-42` yazar. Bağlantıyı kopyalayıp gönderdiğinizde karşı taraf **doğrudan o kaydı** açar |
| **5** | Bir ürünü **silin** | Ürün gider, **izi kalır**: `delete` satırının `old_values` alanı kaydın son hâlinin tamamını taşır |
| **6** | Ürün adına `<script>alert(1)</script>` yazmayı deneyin | Kayıt reddedilir; kaydedilse bile hem listeye hem diff hücrelerine **kaçışlanarak** basılırdı |
| **7** | **İşlem türü** filtresini `Silindi` yapın | Filtre sunucuda uygulanır ve DataTables'ın kendi arama kutusuyla **birlikte** çalışır |
| **8** | **Tarih aralığı** seçin | Bitiş günü **tamamen dahildir** (`< ertesi gün`); klasik "son günün kayıtları kayboldu" hatası yoktur |
| **9** | ⇩ **CSV** butonuna basın | Ekrandaki **aynı filtrelerle** dosya iner. Her değişen alan ayrı satırdır; Excel'de süzülebilir |
| **10** | Telefonunuzdan açın | Tablo yatay kaydırmaya **zorlamaz**; ikincil sütunlar gizlenir, bilgi detay modalında durur |

> **İpucu:** Demoyu açıkken **F12 → Network** sekmesini açın. Her istekte `ajax.php`'ye giden `action` ve `csrf_token` alanlarını, dönen JSON'u ve HTTP durum kodlarını (200 / 403 / 422 / 429) canlı görebilirsiniz.

### Demo alanı hakkında bilinmesi gerekenler

| Konu | Durum |
|------|-------|
| **Veriler** | `cy_audit.sql` içindeki **12 ürün + 8 örnek denetim kaydı**. Gerçek kişi verisi yoktur. |
| **Sıfırlama** | Demo veritabanı **düzenli aralıklarla** başlangıç haline döner; eklediğiniz kayıtlar kalıcı değildir. |
| **Kimlik doğrulama** | **Yoktur.** Bilinçli bir tercihtir — örnek, denetim katmanına odaklanır. Aktör adı oturuma sahte olarak yazılır (bkz. [Aktör nereden geliyor?](#aktör-kim-nereden-geliyor)). |
| **`APP_DEBUG`** | Canlıda **otomatik `false`** — sunucu adından türetilir, yerelde `true` kalır. |
| **Bağımlılık** | **Sıfır.** Composer yok, npm yok, CDN yok. Demo internetsiz bir sunucuda da aynı çalışır. |

> Demo geçici olarak kapalıysa endişelenmeyin: depoyu klonlayıp `cy_audit.sql`'i içe aktarmanız aynı ekranı kendi bilgisayarınızda **2 dakikada** ayağa kaldırır → [Kurulum](#kurulum)

---

## Bu Proje Nedir?

ERP, muhasebe ve yönetim panellerinde en çok istenen özellik "işlem geçmişi"dir. İnternette bulacağınız örneklerin çoğu ise şunu yapar: bir `logs` tablosu açar, içine `"Ahmet ürünü güncelledi"` gibi bir cümle yazar ve biter.

O cümle üç soruyu da cevapsız bırakır: **hangi alan** değişti, **neydi**, **ne oldu?** Üstelik o tabloya `DELETE` atmanın önünde hiçbir engel yoktur — yani "bu fiyatı kim değiştirdi?" sorusunun cevabı, o cevabı vermek istemeyen kişi tarafından silinebilir. Böyle bir kayıt denetim değil, **süstür**.

Bu proje o üç soruyu da cevaplayan bir denetim katmanının nasıl kurulacağını gösteriyor: `audit_log` tablosu **append-only**'dir (uygulamada o tabloya `UPDATE`/`DELETE` yapan **hiçbir yol yoktur**), kayıt **alan bazlı diff** tutar, hassas alanlar **loga yazılmadan önce** maskelenir ve veri ile kaydı **aynı transaction** içinde yazılır — biri başarısız olursa ikisi de geri alınır.

**Kimler için uygun?**

- Kendi projesine denetim / işlem geçmişi katmanı ekleyecekler
- "Bu kaydı kim değiştirdi?" sorusunu üretimde cevaplamak zorunda kalanlar
- PHP + AJAX + DataTables üçlüsünü **doğru** öğrenmek isteyenler
- Bootstrap 5 üzerine kurulu, tekrar kullanılabilir bir tasarım kalıbı arayanlar

> **Klonla, `cy_audit.sql`'i içe aktar, çalıştır.** Başka hiçbir kurulum adımı yok. Composer yok, npm yok, internet bağlantısı bile gerekmiyor — tüm kütüphaneler proje içinde.

Bu proje, **[Çılgın Yazılım Kütüphanesi](https://cilginyazilim.com/kutuphane)** altında yayınlanan açıklamalı, üretime hazır örneklerden biridir.

---

## İçindekiler

- [Canlı Demo](#canlı-demo)
- [Ekran Görüntüleri](#ekran-görüntüleri)
- [Üç Kritik Karar](#üç-kritik-karar)
- [Neler Var?](#neler-var)
- [Güvenlik: Neyi, Nasıl Kapattık?](#güvenlik-neyi-nasıl-kapattık)
- [Kurulum](#kurulum)
- [Yapılandırma](#yapılandırma)
- [Kendi Projenize Eklemek](#kendi-projenize-eklemek)
- [Çılgın Yazılım Tasarım Kalıbı](#çılgın-yazılım-tasarım-kalıbı)
- [Dosya Yapısı](#dosya-yapısı)
- [Nasıl Çalışıyor?](#nasıl-çalışıyor)
- [AJAX API Referansı](#ajax-api-referansı)
- [Veritabanı Şeması](#veritabanı-şeması)
- [Sık Sorulanlar](#sık-sorulanlar)
- [Canlı Ortama Alırken](#canlı-ortama-alırken)
- [Sorun Giderme](#sorun-giderme)
- [Yol Haritası](#yol-haritası)
- [Katkı](#katkı)
- [Lisans](#lisans)

---

## Ekran Görüntüleri

### Genel görünüm

Üstte yazılabilir ürün tablosu, altta salt-okunur denetim kaydı. Aradaki sayaç şeridi denetim kaydının nabzını gösterir: toplam kayıt, ekleme / güncelleme / silme sayıları ve son işlem zamanı.

![Genel görünüm](docs/screenshots/01-genel-gorunum.png)

### Alan bazlı diff detayı

Yalnızca **değişen** alan listelenir. Eski değer üstü çizili kırmızı, yeni değer vurgulu yeşil. Alan adı hem Türkçe etiketiyle hem ham sütun adıyla (`price`) gösterilir — çünkü veritabanına yazılan her zaman ham addır.

![Diff detayı](docs/screenshots/02-diff-detayi.png)

### Mobil görünüm

Dar ekranda tablo yatay kaydırmaya zorlamaz: ikincil sütunlar gizlenir, bilgi detay modalında korunur. Dokunma hedefleri en az 32–44px'dir.

<img src="docs/screenshots/03-mobil.png" alt="Mobil görünüm" width="360">

**Üç modal:**

| Modal | Açılış | İçerik |
|-------|--------|--------|
| ✎ **Ekle / Düzenle** | Yeni Ürün veya kalem butonu | Form, alan bazlı hata mesajları, "bu işlem geçmişe düşecek" uyarısı |
| 🗑 **Silme onayı** | Çöp kutusu butonu | Neyin silineceği + **"denetime kaydedilir"** uyarısı |
| 👁 **İşlem detayı** | Geçmiş tablosundaki göz butonu | Meta bilgiler (zaman, aktör, IP, tarayıcı) + alan bazlı diff tablosu + paylaşılabilir `#islem-42` bağlantısı |

---

## Üç Kritik Karar

### 1) Tek yazma noktası — `audit()`

Denetim kaydı, her işleyicinin kendi başına yazdığı bir şey olsaydı, yarın eklenen bir işleyici onu yazmayı **unuturdu** ve kimse fark etmezdi. Bu yüzden tek bir fonksiyon vardır:

```php
audit($db, 'update', 'product', $id, $old, $new);
//     ^     ^         ^          ^     ^     ^
//     |     |         |          |     |     └─ işlemden SONRAKİ değerler
//     |     |         |          |     └─────── işlemden ÖNCEKİ değerler
//     |     |         |          └───────────── kayıt id'si
//     |     |         └──────────────────────── mantıksal varlık türü
//     |     └────────────────────────────────── create | update | delete
//     └──────────────────────────────────────── PDO bağlantısı
```

Yeni bir varlık denetlemek istediğinizde yazacağınız kod tek satırdır. Diff üretimi, maskeleme ve `INSERT` bu fonksiyonun içindedir.

### 2) Veri ve kaydı aynı transaction'da yazmak

`audit()` herhangi bir sebeple patlarsa (bozuk kodlama, disk dolu, kilit zaman aşımı) ürün satırı çoktan kaydedilmiş olurdu ve ortada **izi olmayan bir kayıt** kalırdı. Bir denetim sisteminde bu, sessizce oluşan en tehlikeli durumdur: tabloya bakan kişi hiçbir eksik görmez.

```php
$db->beginTransaction();
try {
    $stmt->execute([...]);                       // veri
    audit($db, 'create', 'product', $newId, null, $data);   // kayıt
    $db->commit();                               // ya ikisi de…
} catch (PDOException $e) {
    $db->rollBack();                             // …ya hiçbiri
    throw $e;
}
```

InnoDB kullanmamızın somut karşılığı budur.

### 3) Append-only — kaydın kendisi de korunmalı

`system/ajax.php` içinde `audit_log` tablosuna `INSERT` dışında bir işlem yapan **tek bir uç nokta yoktur**. Denetim satırı yalnızca bir CRUD işleminin **yan etkisi** olarak doğar; arayüzden silinemez, düzenlenemez.

> Veritabanı seviyesinde de kilitlemek isterseniz, uygulama kullanıcısına o tabloda yalnızca `INSERT` ve `SELECT` yetkisi verin → [Canlı Ortama Alırken](#canlı-ortama-alırken)

---

## Neler Var?

<table>
<tr><td width="50%" valign="top">

**Arayüz**
- Marka gradyanlı başlık ve modallar
- Sayaç şeridi (toplam / ekleme / güncelleme / silme / son işlem)
- Üç modal (form / silme onayı / işlem detayı)
- Alan bazlı **renkli diff** tablosu
- İşlem türü, varlık türü ve **tarih aralığı** filtreleri
- **CSV dışa aktarım** (ekrandaki filtrelerle birebir aynı)
- Paylaşılabilir kayıt bağlantısı (`#islem-42`)
- Sağ üstte toast bildirimleri
- **Otomatik koyu tema** (işletim sistemi ayarını izler)
- **Mobil için ayrıca inceltilmiş** — yatay kaydırma yok, dokunma hedefleri ≥32px, ARIA etiketli
- Tamamı Türkçe — CDN'siz, çevrimdışı çalışır

</td><td width="50%" valign="top">

**Altyapı**
- Tek yazma noktası: `audit()`
- **Alan bazlı diff** (yalnızca değişen alanlar)
- Hassas alan **maskeleme** (`password`, `token`, `cvv` …)
- **Append-only** denetim tablosu
- Veri + kayıt **aynı transaction**'da
- Sunucu taraflı (server-side) DataTables
- Tek AJAX uç noktası, `action` tabanlı yönlendirme
- CSRF + hız sınırı (yazma ve dışa aktarım ayrı)
- Alan bazlı hata mesajları (HTTP 422)
- Ortam değişkeni desteği, ortama göre otomatik `APP_DEBUG`
- **Kodun her satırı açıklamalı**

</td></tr>
</table>

---

## Güvenlik: Neyi, Nasıl Kapattık?

| Açık | Tipik hatalı kod | Bu projede |
|------|------------------|------------|
| **SQL Injection** | `"... WHERE id = '".$_POST['id']."'"` | Tüm sorgular prepared statement, `EMULATE_PREPARES = false`. Sıralama sütunu **beyaz liste**den geçer; varlık türü filtresi `AUDIT_ENTITIES` sabit listesiyle doğrulanır; tarih filtreleri `DateTime::createFromFormat` ile biçim kontrolünden geçer. |
| **XSS (denetim ekranında)** | `$('#cell').html(row.old_value)` | Eski/yeni değerler **doğrudan kullanıcı verisidir**. Sunucuda `e()`, istemcide `esc()` ile kaçışlanır. Denetim ekranını genellikle en yetkili kullanıcılar açar — burada bir açık en pahalı açıktır. |
| **CSRF** | *(genelde hiç yok)* | Oturuma bağlı 32 baytlık token, **her** AJAX isteğinde, `hash_equals()` ile sabit zamanlı doğrulama. Token'sız istek → **HTTP 403**. |
| **Denetim kaydının silinmesi** | `DELETE FROM logs WHERE id = ?` | `audit_log` için **hiçbir yazma uç noktası yok**. Tek giriş yolu `audit()`. |
| **İzi olmayan kayıt** | Önce `INSERT`, sonra ayrı bir `INSERT` | Veri ve denetim kaydı **tek transaction**. Kayıt yazılamazsa veri de geri alınır. |
| **Denetim kaydından sızıntı** | `old_values` içinde düz parola | `AUDIT_REDACT` listesindeki alanlar loga **yazılmadan önce** `***` ile değiştirilir. |
| **Bozuk kodlamanın kaydı yutması** | `json_encode()` → `false` → boş sütun | `JSON_INVALID_UTF8_SUBSTITUTE`: bozuk bayt `U+FFFD` olur, **kayıt kaybolmaz**. |
| **CSV formül enjeksiyonu** | Hücreyi olduğu gibi yazmak | `=`, `+`, `-`, `@` ile başlayan hücrelerin başına tek tırnak konur; Excel formül olarak çalıştıramaz. |
| **LIKE joker karakterleri** | `LIKE '%$search%'` | `%`, `_`, `\` kaçışlanır. |
| **Kaynak tüketimi** | `LIMIT $length` | Sayfa boyutu `PAGE_SIZE_MAX` (200), dışa aktarım `EXPORT_MAX_ROWS` (5000) ile sınırlı; yazma ve dışa aktarım ayrı **hız sınırı** kovalarında. |
| **Bilgi sızıntısı** | Ekrana basılan MySQL hataları | `APP_DEBUG` sunucu adından türetilir; canlıda otomatik `false`, detay `error_log()`'a gider. |
| **Kurulum dosyasının indirilmesi** | `/cy_audit.sql` → HTTP 200 | `.htaccess`: `.sql`, `.md`, `.json`, `.log` … kapalı (README dosyaları bilinçli istisnadır). |

---

## Kurulum

> Sadece görmek istiyorsanız kurulum gerekmez → [**Canlı Demoyu açın**](https://cilginyazilim.com/kutuphane/uygulama/PHP-MySQL-Audit-Log-Islem-Gecmisi-PDO-Ajax-DataTables-main/). Aşağıdaki adımlar projeyi kendi bilgisayarınızda çalıştırmak içindir (~2 dakika).

### Gereksinimler

- PHP **8.0+** (`pdo_mysql` eklentisi)
- MySQL **5.7+** veya MariaDB **10.3+** *(JSON sütunu için)*
- Apache (XAMPP / WAMP / Laragon) — ya da PHP'nin yerleşik sunucusu

### Adımlar

**1 — Projeyi indirin**

```bash
git clone https://github.com/CilginYazilim/PHP-MySQL-Audit-Log-Islem-Gecmisi-PDO-Ajax-DataTables.git
cd PHP-MySQL-Audit-Log-Islem-Gecmisi-PDO-Ajax-DataTables
```

**2 — Veritabanını oluşturun**

`cy_audit.sql` veritabanını da kendisi oluşturur; önceden `cy_audit` adında bir veritabanı açmanıza gerek yok.

```bash
mysql -u root -p < cy_audit.sql
```

phpMyAdmin ile: **İçe Aktar → Dosya seç → `cy_audit.sql` → Başlat**

**3 — Çalıştırın**

```bash
php -S 127.0.0.1:8000
```

XAMPP kullanıyorsanız projeyi `htdocs` altına koyup şu adresi açın:
`http://localhost/PHP-MySQL-Audit-Log-Islem-Gecmisi-PDO-Ajax-DataTables/`

**4 — Tarayıcıda açın** → `http://127.0.0.1:8000/`

Karşınıza **12 ürün** ve **8 örnek denetim kaydı** dolu, çalışır durumda bir ekran gelecek.

---

## Yapılandırma

Tüm ayarlar [system/config.php](system/config.php) içinde, açıklamalarıyla birlikte:

| Sabit | Varsayılan | Açıklama |
|-------|-----------|----------|
| `DB_HOST` | `127.0.0.1` | Veritabanı sunucusu |
| `DB_NAME` | `cy_audit` | Veritabanı adı |
| `DB_USER` | `root` | Kullanıcı adı |
| `DB_PASS` | *(boş)* | Parola |
| `AUDIT_REDACT` | `password`, `token`, `cvv` … | Loga **yazılmadan önce** `***` ile maskelenecek alan adları |
| `AUDIT_MODE` | `diff` | `diff` → yalnızca değişen alanlar · `full` → her işlemde satırın tamamı |
| `AUDIT_ENTITIES` | `['product' => 'Ürün']` | Denetlenebilen varlık türleri; filtre listesi ve doğrulama buradan beslenir |
| `PAGE_SIZE_MAX` | `200` | DataTables sayfa boyutu tavanı |
| `EXPORT_MAX_ROWS` | `5000` | CSV dışa aktarımda tek seferde okunacak en fazla satır |
| `RATE_LIMIT_WRITE` | `[60, 60]` | Yazma işlemleri: 60 saniyede 60 istek |
| `RATE_LIMIT_EXPORT` | `[10, 60]` | Dışa aktarım: 60 saniyede 10 istek |
| `APP_DEBUG` | *ortama göre* | `localhost` / `*.test` / `*.local` → `true`, diğer her yerde `false` |

### `AUDIT_MODE`: `diff` mi `full` mü?

| | `diff` | `full` |
|-|--------|--------|
| Yazılan veri | Yalnızca değişen alanlar | Satırın tamamı |
| Tablo boyutu | Küçük | Büyür |
| "O an kayıt neydi?" | Zincirin tamamını okumak gerekir | Tek satırda cevap |
| Uygun olduğu yer | Sık güncellenen tablolar | Yasal saklama, geri alma (rollback) senaryoları |

Bu örnek `diff` kullanır. `full`'e geçmek tek satırlık bir değişikliktir; kod yolu ikisini de destekler.

### Şifreyi koda yazmayın

Tüm `DB_*` sabitleri ortam değişkeniyle geçersiz kılınabilir:

```bash
# Linux / macOS
export DB_HOST=localhost DB_USER=uygulama DB_PASS='guclu-sifre'

# Windows (PowerShell)
$env:DB_USER = "uygulama"; $env:DB_PASS = "guclu-sifre"
```

Apache için `.htaccess` ya da `httpd.conf` içinde: `SetEnv DB_PASS "guclu-sifre"`

---

## Kendi Projenize Eklemek

Denetim katmanını taşımak için **iki dosya** yeter: `audit_log` tablosu ve `audit()` fonksiyonu.

**1 — Tabloyu oluşturun** (`cy_audit.sql` içindeki `CREATE TABLE audit_log` bloğu)

**2 — `audit()` ve yardımcılarını kopyalayın** ([system/function.php](system/function.php) → BÖLÜM 5)

**3 — Her yazma işleminden sonra çağırın:**

```php
// EKLEME — old yok
$db->beginTransaction();
$stmt->execute([...]);
$newId = (int) $db->lastInsertId();
audit($db, 'create', 'siparis', $newId, null, $data);
$db->commit();

// GÜNCELLEME — ÖNCE eski satırı okuyun, diff için şart
$old = $db->prepare('SELECT ... WHERE id = ?') /* ... */ ->fetch();
$db->beginTransaction();
$stmt->execute([...]);
audit($db, 'update', 'siparis', $id, $old, $data);
$db->commit();

// SİLME — new yok; old, kaydın son kopyasıdır
audit($db, 'delete', 'siparis', $id, $old, null);
```

**4 — Yeni varlığı tanıtın** ([system/config.php](system/config.php)):

```php
define('AUDIT_ENTITIES', ['product' => 'Ürün', 'siparis' => 'Sipariş']);
```

**5 — Alan etiketlerini ekleyin** ([system/function.php](system/function.php) → `FIELD_LABELS`) — isteğe bağlıdır; eklemezseniz ham sütun adı gösterilir.

> **Aktör kim, nereden geliyor?**
> `audit()` aktörü `$_SESSION['user_id']` ve `$_SESSION['user_name']` üzerinden okur. Bu örnekte gerçek giriş olmadığı için `system/ajax.php` başında sahte bir aktör yazılır. Kendi projenizde o iki satırı silin; oturum zaten giriş sisteminizden gelecektir.

---

## Çılgın Yazılım Tasarım Kalıbı

[assets/css/cilginyazilim.css](assets/css/cilginyazilim.css) dosyası bu projeye değil **markaya** aittir. Diğer örnek projelerde de aynı görsel dili kullanabilmek için ayrı bir dosya olarak tutulur; bu örneğe özgü her şey (sayaç şeridi, işlem rozetleri, diff tablosu) [assets/css/style.css](assets/css/style.css) içindedir.

### Hazır bileşenler

| Sınıf | Ne işe yarar |
|-------|--------------|
| `.cy-card` / `.cy-card__header` / `__body` / `__footer` | Gradyan başlıklı ana kart |
| `.cy-brand` / `.cy-brand__mark` / `__title` / `__subtitle` | Logo kutusu + başlık bloğu |
| `.cy-btn` + `--primary` \| `--onbrand` \| `--glass` | Marka butonları |
| `.cy-btn-icon` + `--view` \| `--edit` \| `--delete` | Tablo içi ikon butonları |
| `.cy-table` / `.cy-actions` / `.cy-id` / `.cy-name` | Marka görünümlü tablo bileşenleri |
| `.cy-badge` + `--glass` \| `--soft` | Rozetler |
| `.cy-modal` / `.cy-detail` | Gradyan başlıklı modal ve etiket/değer listesi |
| `.cy-toast` + `--success` \| `--danger` \| `--info` | Bildirim balonları |

**Bu örneğe özgü** (`style.css`): `.cy-stats` / `.cy-stat`, `.cy-op--create|update|delete`, `.cy-diff` / `.cy-diff__old` / `.cy-diff__new`, `.cy-filters`, `.cy-stock`, `.cy-sku`.

### Renkleri değiştirmek

Tüm bileşenler CSS değişkenlerinden beslenir. **Tek yeri** değiştirmek yeter:

```css
:root {
    --cy-brand-900: #061321;   /* Logodaki en koyu lacivert */
    --cy-brand-600: #0b5cb5;   /* Ana marka mavisi          */
    --cy-accent:    #0ea5e9;   /* Vurgu rengi               */
    --cy-gradient:  linear-gradient(135deg, #061321, #0b5cb5 45%, #0284c7);
}
```

### Koyu tema

Kullanıcının işletim sistemi koyu temadaysa **otomatik** devreye girer. Zorlamak isterseniz:

```html
<html data-cy-theme="dark">   <!-- veya "light" -->
```

---

## Dosya Yapısı

```
.
├── index.php                      # Arayüz (sunum katmanı) — veritabanına dokunmaz
├── cy_audit.sql                   # Şema + 12 ürün + 8 örnek denetim kaydı
├── README.md / README.en.md       # Belgelendirme
├── CHANGELOG.md                   # Sürüm notları
├── LICENSE                        # MIT
├── .htaccess                      # Güvenlik başlıkları, dosya erişim kuralları
├── .gitignore
│
├── docs/
│   └── screenshots/               # README'de kullanılan ekran görüntüleri
│
├── system/
│   ├── config.php                 # Ayarlar, oturum, PDO bağlantısı
│   ├── function.php               # ★ audit() — tek yazma noktası + diff + maskeleme
│   ├── ajax.php                   # AJAX uç noktası / action yönlendiricisi
│   └── .htaccess                  # Bu klasöre doğrudan erişimi kısıtlar
│
└── assets/
    ├── css/
    │   ├── bootstrap.min.css
    │   ├── dataTables.bootstrap5.min.css
    │   ├── cilginyazilim.css      # ★ MARKA TASARIM KALIBI
    │   └── style.css              # Sadece bu sayfaya özel eklemeler
    ├── js/
    │   ├── jquery-3.7.0.js
    │   ├── bootstrap.bundle.js
    │   ├── jquery.dataTables.min.js
    │   ├── dataTables.bootstrap5.min.js
    │   └── audit.js               # ★ Arayüz mantığı
    └── images/logo.png
```

**Yükleme sırası önemlidir:**

```
CSS:  bootstrap → dataTables → cilginyazilim → style
JS:   jQuery → bootstrap.bundle → dataTables → dataTables.bootstrap5 → audit
```

---

## Nasıl Çalışıyor?

```
┌─────────────────────────────────────────────────────────────────────┐
│  TARAYICI  (index.php + assets/js/audit.js)                         │
│                                                                      │
│  Ürün tablosu ──┐                                                    │
│  Form gönder ───┤                                                    │
│  Filtreler ─────┼──► jQuery AJAX ──► POST { action, csrf_token, … }  │
│  Geçmiş tablosu ┘                            │                       │
│                                              │                       │
│  Yazma bitince refreshAll():                 │                       │
│    ürün tablosu + geçmiş tablosu + sayaçlar  │                       │
└──────────────────────────────────────────────┼───────────────────────┘
                                               ▼
┌─────────────────────────────────────────────────────────────────────┐
│  SUNUCU  (system/ajax.php)                                          │
│                                                                      │
│   1. POST mu?                    → değilse 405                      │
│   2. require_csrf()              → geçersizse 403                   │
│   3. rate_limit()                → aşılırsa 429                     │
│   4. action'a göre dağıt                                            │
│   5. validate_product()          → hatalıysa 422 + errors           │
│                                                                      │
│   6. ┌── BEGIN TRANSACTION ───────────────────────────────┐         │
│      │   INSERT / UPDATE / DELETE  →  products            │         │
│      │   audit()                   →  audit_log           │         │
│      │     ├─ diff_values()  : yalnızca değişen alanlar   │         │
│      │     ├─ redact()       : hassas alanlar → '***'     │         │
│      │     └─ audit_json()   : bozuk bayt → U+FFFD        │         │
│      └── COMMIT  (biri patlarsa ROLLBACK: ikisi de yok)   ┘         │
│                                                                      │
│   7. json_response()             → tek noktadan JSON çıkışı         │
└─────────────────────────────────────────────────────────────────────┘
```

### Sorumluluk dağılımı

| Dosya | Görevi |
|-------|--------|
| **[index.php](index.php)** | Sadece sunum. CSRF token üretir, tabloları/modalları çizer. Veritabanına dokunmaz. |
| **[assets/js/audit.js](assets/js/audit.js)** | DataTables kurulumu, filtreler, modallar, diff çizimi, CSV formu. |
| **[system/ajax.php](system/ajax.php)** | Yönlendirici + tüm güvenlik kontrolleri + transaction sınırları. |
| **[system/function.php](system/function.php)** | `audit()`, `diff_values()`, `redact()`, CSRF, hız sınırı, CSV yardımcıları. |
| **[system/config.php](system/config.php)** | Oturum, sabitler, tek bir PDO örneği. |

---

## AJAX API Referansı

Tüm istekler `POST` ile [system/ajax.php](system/ajax.php) adresine yapılır ve geçerli bir `csrf_token` içermelidir.

<details>
<summary><b><code>action=product_list</code></b> — Ürün listesi (DataTables)</summary>

**İstek:** `draw`, `start`, `length`, `search[value]`, `order[0][column]`, `order[0][dir]`

Sıralanabilir sütunlar beyaz listeyle sınırlıdır: `0 → id`, `1 → name`, `2 → sku`, `3 → price`, `4 → stock`, `5 → updated_at`. İşlem sütunu (6) veritabanı sütunu olmadığı için sıralanamaz.
</details>

<details>
<summary><b><code>action=product_save</code></b> — Ekleme / güncelleme</summary>

**İstek:** `id` *(0 veya boş → ekleme)*, `name`, `sku`, `price`, `stock`

**Başarılı (200):**
```json
{ "success": true, "type": "success", "description": "Ürün güncellendi (2 alan).", "id": 5 }
```

Mesajdaki alan sayısı `diff_values()` çıktısından gelir. Hiçbir alan değişmediyse `"Değişiklik yapılmadı."` döner ve **denetim kaydı yazılmaz**.

**Doğrulama hatası (422):**
```json
{
  "success": false, "type": "danger",
  "description": "Lütfen formdaki hataları düzeltin.",
  "errors": { "sku": "SKU 2-40 karakter; büyük harf, rakam ve tire." }
}
```
`errors` anahtarları form alanlarının `name` değerleriyle birebir aynıdır. SKU çakışmasında **409** döner, gövde yine `errors.sku` taşır.
</details>

<details>
<summary><b><code>action=product_fetch</code></b> — Tek ürün (form doldurma)</summary>

**İstek:** `id` — Hazır HTML değil **ham veri** döner:

```json
{ "success": true, "product": { "id": 5, "name": "Laptop Soğutucu Stand", "sku": "STND-COOL", "price": "429.90", "stock": 58 } }
```
</details>

<details>
<summary><b><code>action=product_delete</code></b> — Silme</summary>

**İstek:** `id` — Ürün silinir, `delete` denetim kaydı yazılır. `old_values` kaydın **son hâlinin tamamını** taşır; bu, silinen kaydın tek kopyasıdır.
</details>

<details>
<summary><b><code>action=audit_list</code></b> — Denetim listesi (salt-okunur)</summary>

**İstek:** DataTables parametreleri + `f_action`, `f_entity`, `f_from`, `f_to`

Filtreler `audit_filters()` içinde tek yerden kurulur; `audit_export` **aynı fonksiyonu** kullanır, böylece ekrandaki liste ile inen dosya asla ayrışmaz.
</details>

<details>
<summary><b><code>action=audit_detail</code></b> — Alan bazlı diff</summary>

**İstek:** `id`

```json
{
  "success": true,
  "meta": { "id": 5, "time": "27.08.2026 13:20:38", "actor": "Mehmet YILMAZ",
            "action": "update", "action_tr": "Güncellendi", "entity": "Ürün #5",
            "ip": "10.0.0.12", "user_agent": "Mozilla/5.0 …" },
  "diff": [
    { "field": "price", "label": "Fiyat",      "old": "379.90", "new": "429.90" },
    { "field": "stock", "label": "Stok adedi", "old": 95,       "new": 58 }
  ]
}
```

`field` ham sütun adıdır (veritabanına yazılan), `label` ekranda gösterilen Türkçe etikettir. `null` = o alan bu işlemde **yoktu** (create'te `old`, delete'te `new`).
</details>

<details>
<summary><b><code>action=audit_export</code></b> — CSV dışa aktarım</summary>

**İstek:** `f_action`, `f_entity`, `f_from`, `f_to`, `search_value`

`text/csv` döner (`Content-Disposition: attachment`). UTF-8 BOM'lu, `;` ayraçlı — Türkçe Excel'de doğrudan açılır. **Her değişen alan ayrı satırdır**, böylece dosya Excel'de süzülebilir.
</details>

<details>
<summary><b><code>action=stats</code></b> — Sayaç şeridi</summary>

```json
{ "success": true, "products": 12, "total": 8, "create": 3, "update": 4, "delete": 1, "last": "30.08.2026 07:20:38" }
```
Dört sayaç tek sorguda üretilir (`SUM(action = '…')`), tablo bir kez taranır.
</details>

### HTTP durum kodları

| Kod | Anlamı |
|-----|--------|
| `200` | İşlem başarılı |
| `400` | Geçersiz parametre (örn. hatalı ID) |
| `403` | CSRF token geçersiz veya oturum düşmüş |
| `404` | Kayıt bulunamadı |
| `405` | POST dışı istek |
| `409` | Benzersizlik çakışması (SKU zaten var) |
| `422` | Form doğrulama hatası (`errors` alanı döner) |
| `429` | Hız sınırı aşıldı (`retry_after` saniye döner) |
| `500` | Sunucu / veritabanı hatası |

---

## Veritabanı Şeması

```sql
CREATE TABLE `audit_log` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `actor_id`    INT UNSIGNED NULL DEFAULT NULL,
  `actor_name`  VARCHAR(150) NOT NULL DEFAULT 'Sistem',
  `action`      ENUM('create','update','delete') NOT NULL,
  `entity_type` VARCHAR(60)  NOT NULL,       -- 'product', 'siparis' …
  `entity_id`   BIGINT UNSIGNED NOT NULL,
  `old_values`  JSON NULL,                   -- create'te NULL
  `new_values`  JSON NULL,                   -- delete'te NULL
  `ip`          VARCHAR(45)  NOT NULL DEFAULT '',
  `user_agent`  VARCHAR(255) NOT NULL DEFAULT '',
  `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_entity`  (`entity_type`, `entity_id`),
  KEY `idx_audit_action`  (`action`),
  KEY `idx_audit_actor`   (`actor_id`),
  KEY `idx_audit_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

| Karar | Neden |
|-------|-------|
| **`BIGINT` id** | Denetim tablosu ana tablodan **kat kat hızlı** büyür; `INT` tavanı (2,1 milyar) düşünüldüğünden erken gelir |
| **`entity_type` + `entity_id`** | Tek tablo tüm varlıkları denetler. Her varlık için ayrı log tablosu açmak, "bu kullanıcı bugün ne yaptı?" sorusunu `UNION` cehennemine çevirir |
| **`JSON` sütunlar** | Alan sayısı varlıktan varlığa değişir. Sabit sütunlar (`old_price`, `old_stock` …) yeni bir alan eklendiğinde şema değişikliği gerektirirdi |
| **`actor_id` **ve** `actor_name`** | İkisi birlikte tutulur: kullanıcı silinse veya adı değişse bile **o günkü ad** kayıtta kalır |
| **`idx_audit_created`** | Tarih aralığı filtresinin dayandığı indeks; olmadan her sorgu tam tarama olur |
| **InnoDB** | Transaction desteği — veri ve kaydın birlikte yazılabilmesinin ön şartı |

---

## Sık Sorulanlar

<details>
<summary><b>Denetim tablosu ne kadar büyür? Ne yapmalıyım?</b></summary>

`diff` modunda bir güncelleme satırı tipik olarak 150–400 bayt tutar. Günde 10.000 işlem yapan bir sistemde yıllık ~1 GB eder. İki yaygın strateji:

1. **Bölümleme (partitioning):** `created_at` üzerinden aylık `RANGE` bölümleri; eski bölümü tek komutla düşürürsünüz.
2. **Arşivleme:** N aydan eski satırları soğuk bir tabloya/dosyaya taşıyın. Silmeyin — **arşivleyin**; denetim kaydını silmek, tuttuğunuz kaydın anlamını zayıflatır.
</details>

<details>
<summary><b>Neden `logs` tablosuna cümle yazmıyoruz?</b></summary>

`"Ahmet ürünü güncelledi"` cümlesi üç soruyu da cevapsız bırakır: hangi alan, neydi, ne oldu? Ayrıca aranamaz, süzülemez ve raporlanamaz. Alan bazlı diff ise `"price alanı 429.90'dan 599.90'a çekilmiş"` sorusunu **SQL ile** cevaplayabilir hâle getirir.
</details>

<details>
<summary><b>`audit_log` tablosunu veritabanı seviyesinde de kilitleyebilir miyim?</b></summary>

Evet, önerilir. Uygulama kullanıcısına o tabloda yalnızca ekleme ve okuma yetkisi verin:

```sql
REVOKE UPDATE, DELETE ON cy_audit.audit_log FROM 'uygulama'@'localhost';
GRANT  INSERT, SELECT ON cy_audit.audit_log TO   'uygulama'@'localhost';
```

Böylece bir SQL injection açığı bile denetim kaydını değiştiremez.
</details>

<details>
<summary><b>Hassas alanları maskelemek yerine hiç yazmasam olmaz mı?</b></summary>

Olur ama bilgi kaybedersiniz. `***` yazmak *"bu alan değişti ama içeriğini saklamıyoruz"* der; hiç yazmamak ise *"bu alan hiç değişmedi"* ile karışır. Denetimde bu ikisi **çok farklı** iki cümledir.
</details>

<details>
<summary><b>Denetim kaydını başka bir veritabanına yazabilir miyim?</b></summary>

Yazabilirsiniz ama **transaction garantisini kaybedersiniz** — bu projedeki en önemli özelliklerden biri budur. Ayrı sunucu gerekiyorsa önce yerel tabloya yazın, sonra bir kuyruk işçisiyle kopyalayın.
</details>

<details>
<summary><b>Neden hem istemcide hem sunucuda doğrulama var?</b></summary>

İstemci doğrulaması **kullanıcı deneyimi** içindir; JavaScript kapatılabilir veya istek doğrudan `curl` ile atılabilir. **Gerçek koruma her zaman sunucudadır.**
</details>

<details>
<summary><b>`serverSide: true` ayarını kapatabilir miyim?</b></summary>

Ürün tablosunda kapatabilirsiniz. Denetim tablosunda **kapatmayın**: o tablo hızla yüz binlerce satıra ulaşır ve hepsini tarayıcıya indirmek sayfayı dondurur.
</details>

---

## Canlı Ortama Alırken

- [ ] `APP_DEBUG` zaten ortamdan türetiliyor — yine de canlıda `false` olduğunu **doğrulayın**
- [ ] Veritabanı için `root` yerine **sınırlı yetkili** bir kullanıcı oluşturun
- [ ] `audit_log` tablosunda uygulama kullanıcısından **`UPDATE` ve `DELETE` yetkisini alın**
- [ ] Kimlik bilgilerini **ortam değişkeni** olarak tanımlayın, koda gömmeyin
- [ ] **HTTPS** kullanın; `session.cookie_secure = 1` ve `session.cookie_httponly = 1` ayarlayın
- [ ] **Giriş sistemi ekleyin** ve `system/ajax.php` başındaki sahte aktör satırlarını **silin**
- [ ] Nginx kullanıyorsanız `.htaccess` çalışmaz; `.sql` ve `.md` erişimini sunucu yapılandırmasından kapatın:
  ```nginx
  location ~* \.(sql|log|ini|bak)$ { deny all; }
  ```
- [ ] Denetim tablosu için **arşivleme / bölümleme** planı yapın
- [ ] `audit_log` yedeklerini ana veriyle **aynı sıklıkta** alın

---

## Sorun Giderme

| Belirti | Çözüm |
|---------|-------|
| **"Veritabanına bağlanılamadı"** | MySQL çalışmıyor veya `DB_*` bilgileri hatalı. XAMPP panelinden MySQL'i başlatın. |
| **`CONSTRAINT ... failed` (JSON sütunu)** | Eski bir sürümde `json_encode()` bozuk UTF-8'de `false` dönüyordu. Bu sürümde `audit_json()` bunu kapatır; güncel dosyayı kullandığınızdan emin olun. |
| **Tablo boş, "Yükleniyor…" takılı** | F12 → Network. Genelde `system/ajax.php` bir PHP hatası döndürüyordur; yerelde `APP_DEBUG` zaten `true`, yanıtı okuyun. |
| **HTTP 403 dönüyor** | Oturum düşmüş — sayfayı yenileyin. Sunucuda `session.save_path` yazılabilir olmalıdır. |
| **CSV Excel'de bozuk görünüyor** | Dosya UTF-8 BOM'lu ve `;` ayraçlıdır. Excel "Metni Sütunlara Dönüştür" ile açtıysanız ayracı `;` seçin. |
| **`$ is not defined`** | JavaScript yükleme sırası bozulmuş. jQuery **her zaman** en başta gelmelidir. |
| **Türkçe karakterler bozuk** | Veritabanı utf8mb4 değil. `cy_audit.sql` sonundaki `CONVERT TO CHARACTER SET utf8mb4` satırlarını çalıştırın. |
| **DataTables "Requested unknown parameter"** | `<th>` sayısı ile sunucudan dönen dizi uzunluğu farklı. İkisini eşitleyin. |
| **Geçmişe satır düşmüyor** | Güncellemede **hiçbir alan değişmemiş** olabilir — bu durumda kayıt bilinçli olarak yazılmaz. |

---

## Yol Haritası

- [ ] Denetim kaydından **geri alma** (rollback): `old_values` ile kaydı eski hâline döndürme
- [ ] `full` modda satır anlık görüntülerinden **zaman çizelgesi** görünümü
- [ ] Kullanıcı girişi ve rol tabanlı yetkilendirme (yalnızca yöneticiler geçmişi görsün)
- [ ] Denetim tablosu için otomatik **arşivleme / bölümleme** betiği
- [ ] Webhook: belirli alanlar değiştiğinde bildirim gönderme
- [ ] Excel (XLSX) ve PDF dışa aktarım
- [ ] PHPUnit ile birim testleri (`diff_values`, `redact`, `valid_date`)
- [ ] Koyu tema için elle açma/kapama düğmesi

---

## Katkı

**Bu proje herkese açıktır — dilediğiniz geliştirmeyle katkı sağlayabilirsiniz.**

📦 **Depo:** [github.com/CilginYazilim/PHP-MySQL-Audit-Log-Islem-Gecmisi-PDO-Ajax-DataTables](https://github.com/CilginYazilim/PHP-MySQL-Audit-Log-Islem-Gecmisi-PDO-Ajax-DataTables)

| Nasıl katkı sağlarım? | Nereden |
|----------------------|---------|
| 🐛 Hata bildir | [Issues](https://github.com/CilginYazilim/PHP-MySQL-Audit-Log-Islem-Gecmisi-PDO-Ajax-DataTables/issues) |
| 💡 Özellik öner | [Issues](https://github.com/CilginYazilim/PHP-MySQL-Audit-Log-Islem-Gecmisi-PDO-Ajax-DataTables/issues) |
| 🔧 Kod gönder | [Pull Requests](https://github.com/CilginYazilim/PHP-MySQL-Audit-Log-Islem-Gecmisi-PDO-Ajax-DataTables/pulls) |
| ❓ Soru sor | [Discussions](https://github.com/CilginYazilim/PHP-MySQL-Audit-Log-Islem-Gecmisi-PDO-Ajax-DataTables/discussions) |

### Katkı ölçütleri

- **Kod açıklamalı olsun.** Bu projenin temel amacı öğretmek; yorumsuz kod PR'ı geri döner.
- **`audit_log` tablosuna yazma uç noktası eklemeyin.** Append-only olması projenin tezidir.
- **Yeni bir yazma işlemi eklerken** `audit()` çağrısını ve transaction'ı unutmayın.
- **Tasarım değişikliklerini** `style.css` üzerinden yapın; `cilginyazilim.css` markaya aittir ve diğer projelerle **ortaktır**.
- Yeni bir dış kütüphane eklemeden önce issue açıp tartışalım — proje bilinçli olarak bağımlılıksızdır.

---

## Lisans

[MIT](LICENSE) — ticari kullanım dahil serbesttir.

<div align="center">

### Önce bir deneyin

<a href="https://cilginyazilim.com/kutuphane/uygulama/PHP-MySQL-Audit-Log-Islem-Gecmisi-PDO-Ajax-DataTables-main/"><img src="https://img.shields.io/badge/CANLI_DEMOYU_A%C3%87-0b5cb5?style=for-the-badge&logo=googlechrome&logoColor=white&labelColor=061321" alt="Canlı Demoyu Aç" height="42"></a>
&nbsp;
<a href="https://cilginyazilim.com/kutuphane"><img src="https://img.shields.io/badge/D%C4%B0%C4%9EER_%C3%96RNEKLER-061321?style=for-the-badge&logo=bookstack&logoColor=white&labelColor=061321" alt="Diğer Örnekler" height="42"></a>

**[cilginyazilim.com](https://cilginyazilim.com)** tarafından ❤ ile geliştirildi

Faydalı bulduysanız ⭐ vermeyi unutmayın.

</div>
