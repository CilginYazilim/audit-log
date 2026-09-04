# Değişiklik Günlüğü

Bu dosyanın biçimi [Keep a Changelog](https://keepachangelog.com/tr/1.1.0/) esas alınarak,
sürüm numaralandırması [Semantic Versioning](https://semver.org/lang/tr/) kuralına göre tutulur.

---

## [1.1.0] — 2026-09-04

### Eklendi

- **Veritabanı bilgileri artık `.env` dosyasından okunabiliyor.**
  Daha önce tek yol `system/config.php` dosyasını elle düzenlemekti — ve
  o dosya depoda durur: yazdığınız parola hem GitHub'a gider hem de ilk
  dağıtımda depodaki sürümle değiştirilerek kaybolur.

  Depo köküne `.env.example` eklendi; kopyalayıp `.env` yapmanız yeterli.
  `.env` zaten `.gitignore` içindeydi.

  Değer arama sırası: `.env` → sunucunun gerçek ortam değişkeni → bu
  dosyadaki varsayılan. (`config.local.php` destekleyen depolarda o hâlâ
  en önde gelir; eski kurulumlar olduğu gibi çalışır.)

  Uygulama kodu değişmedi: `cy_env()` yardımcısı bilerek `getenv()` ile
  aynı sözleşmeyi taşır (değer ya da `false`), böylece mevcut `?:` ve
  `!== false` kalıplarının hiçbirine dokunulmadı.

### Değiştirildi

- **Depo adı `PHP-MySQL-Audit-Log-Islem-Gecmisi-PDO-Ajax-DataTables`
  yerine `audit-log` oldu.** Uzun ad adres satırında okunmuyordu ve
  klasör adıyla eşleşmediği için vitrindeki bağlantılar kırılıyordu.
  Klon, ZIP, issue ve yerel kurulum adresleri buna göre güncellendi.
  GitHub eski adresi yenisine yönlendirir; eski bağlantılar kırılmaz.

- **Zaman dilimi artık açıkça sabitleniyor.** `system/config.php` içinde
  `APP_TIMEZONE` (varsayılan `Europe/Istanbul`) tanımlanıp
  `date_default_timezone_set()` çağrılıyor; ortam değişkeniyle
  değiştirilebilir.

  **Ölçülen sorun:** XAMPP'ın `php.ini` dosyasındaki `date.timezone`,
  MySQL'in kullandığı sistem diliminden farklı olabiliyor. Test
  makinesinde PHP `Europe/Berlin`, MySQL ise `Europe/Istanbul`
  kullanıyordu ve aynı anı anlatan iki satır bir saat farklı görünüyordu:

  ```
  worker günlüğü (PHP date)  : 14:03:17
  veritabanı  (MySQL NOW())  : 15:03:17
  ```

  Zaman **aritmetiği** bu depoda bilinçli olarak SQL tarafında yapıldığı
  için (`NOW()`, `INTERVAL`, `TIMESTAMPDIFF`) hesaplar zaten doğruydu;
  kayan şey PHP'nin ekrana ve günlüğe bastığı saatti. Ama demoyu deneyen
  biri için bu, "sistem yanlış çalışıyor" gibi görünüyordu.

### Düzeltildi

- **"Canlı Demo" bağlantısı kırıktı.** README'lerdeki en görünür düğme
  `…/kutuphane/uygulama/PHP-MySQL-Audit-Log-Islem-Gecmisi-PDO-Ajax-DataTables-main/` adresine gidiyordu; o adres **404**
  döndürüyordu. Vitrindeki gerçek adres `…/kutuphane/uygulama/audit-log/`.
  Her iki dildeki README'de tüm geçtiği yerler düzeltildi ve adreslerin
  200 döndüğü doğrulandı.

---

## [1.0.1] — 2026-08-30

### Eklendi

- `system/config.local.php` desteği: canlı veritabanı künyesi artık `config.php`'ye değil, `.gitignore`'daki ayrı bir dosyaya yazılır. İki sebeple: parola depoya girmez ve `config.php` her dağıtımda depodaki sürümle değiştirildiği için elle yapılan düzenleme kaybolmaz.
- `system/config.local.php.example` — doldurulacak şablon.

`DB_*` sabitleri artık `if (! defined(...))` ile korunuyor: öncelik sırası **config.local.php → ortam değişkeni → yerel varsayılan**.

---

## [1.0.0] — 2026-08-30

İlk genel sürüm. Denetim katmanı, arayüz ve belgelendirme üretime hazır durumda.

### Eklendi

**Denetim çekirdeği**
- `audit()` — denetim kaydının **tek yazma noktası**: diff üretimi, maskeleme ve `INSERT` tek fonksiyonda.
- `diff_values()` — yalnızca **değişen** alanları `[alan => [eski, yeni]]` biçiminde üretir.
- `AUDIT_REDACT` — `password`, `token`, `cvv` gibi alanlar loga **yazılmadan önce** `***` ile maskelenir.
- `AUDIT_MODE` — `diff` (yalnızca değişen alanlar) ve `full` (satırın tamamı) modları.
- `AUDIT_ENTITIES` — denetlenebilen varlık türleri; filtre listesi ve sunucu doğrulaması aynı kaynaktan beslenir.
- **Append-only** garantisi: `audit_log` tablosuna `INSERT` dışında işlem yapan uç nokta yok.
- Veri ve denetim kaydı **aynı transaction** içinde yazılır; biri başarısız olursa ikisi de geri alınır.
- `audit_json()` — bozuk UTF-8 baytı `U+FFFD` ile değiştirir, böylece kodlama hatası kaydın tamamını yutmaz.
- Hiçbir alan değişmediyse denetim satırı **yazılmaz**; tablo boş kayıtlarla dolmaz.

**Arayüz**
- Sayaç şeridi: toplam kayıt, ekleme / güncelleme / silme sayıları ve son işlem zamanı.
- Alan bazlı **renkli diff** tablosu (eski değer üstü çizili kırmızı, yeni değer yeşil).
- İşlem türü, varlık türü ve **tarih aralığı** filtreleri + "filtreyi temizle".
- Paylaşılabilir kayıt bağlantısı: detay modalı açıldığında adres `#islem-42` olur, o adresle açılan sayfa doğrudan ilgili kaydı gösterir.
- Silme onayı için markayla uyumlu modal; tarayıcının `confirm()` kutusu kaldırıldı.
- Çift gönderim koruması (kaydet butonu istek boyunca kilitlenir).
- Ürün listesinde stok rozeti (0 → kırmızı, <20 → turuncu) ve SKU etiketi.

**Dışa aktarım**
- `audit_export` — CSV dışa aktarım. Ekrandaki liste ile **aynı filtre fonksiyonunu** kullanır; ikisi ayrışamaz.
- UTF-8 BOM + `;` ayracı: Türkçe Excel'de doğrudan açılır.
- CSV formül enjeksiyonuna karşı `=`, `+`, `-`, `@` ile başlayan hücreler metne zorlanır.
- Her değişen alan **ayrı satır** — dosya Excel'de süzülebilir.

**Mobil ve erişilebilirlik**
- Dar ekranda **yatay kaydırma yok**: ikincil sütunlar gizlenir, bilgi detay modalında korunur (991px / 767px / 480px eşikleri).
- Dokunma hedefleri en az 32–44px; filtre alanları ve sayfalama düğmeleri büyütüldü.
- Tüm ikon butonlarda `aria-label`, toast alanında `aria-live`.
- İşlem rozetleri renk **ve** metin taşır — renk tek başına anlam taşımaz.

**Altyapı**
- `stats` uç noktası: dört sayaç tek sorguda (`SUM(action = '…')`).
- `RATE_LIMIT_EXPORT` — dışa aktarım için ayrı ve daha sıkı hız sınırı.
- `EXPORT_MAX_ROWS` — dışa aktarımda satır tavanı.
- `valid_date()` — tarih filtreleri `DateTime::createFromFormat` ile biçim doğrulamasından geçer.
- Tarih aralığında bitiş günü **tamamen dahildir** (`< ertesi gün`).

**Belgelendirme**
- Türkçe ve İngilizce README (kurulum, güvenlik tablosu, API referansı, şema kararları, SSS).
- Ekran görüntüleri: genel görünüm, diff detayı, mobil görünüm.

### Değiştirildi

- `APP_DEBUG` artık sabit değil: sunucu adından türetilir (`localhost`, `*.test`, `*.local` → `true`; diğer her yer → `false`). `APP_DEBUG` ortam değişkeniyle ezilebilir.
- Marka tasarım kalıbı (`cilginyazilim.css`) kütüphanedeki güncel sürümle eşitlendi; mobil kuralları ve başlık kontrolleri geldi.
- `cy_audit.sql` örnek verisi genişletildi: 5 → **12 ürün**, 1 → **8 denetim kaydı**. Zamanlar `NOW() - INTERVAL` ile üretilir, böylece kayıtlar ne zaman içe aktarılırsa aktarılsın "son birkaç gün" içinde görünür.
- Ürünler tablosunun `AUTO_INCREMENT` değeri 14'ten başlatıldı; silinmiş 13 numaralı kaydın numarası yeniden kullanılmıyor.
- Denetim listesi artık alan adlarının Türkçe özetini gösteriyor ("Fiyat, Stok adedi") — yalnızca "2 alan" değil.
- Benzersizlik hatası artık indeks adına bakarak ayırt ediliyor; her `23000` hatası "SKU zaten var" sayılmıyor.

### Güvenlik

- `.htaccess`: `README*.md` dosyaları bilinçli istisna olarak açıldı (kütüphane sayfası içerik olarak okuyor); diğer `.md`, `.sql`, `.json`, `.log`, `.ini`, `.bak` dosyaları kapalı.
- `DirectoryIndex index.php` eklendi — demo alt klasörden servis edilirken klasör adresi doğrudan uygulamayı açar.

[1.0.1]: https://github.com/CilginYazilim/audit-log/releases/tag/v1.0.1
[1.0.0]: https://github.com/CilginYazilim/audit-log/releases/tag/v1.0.0
