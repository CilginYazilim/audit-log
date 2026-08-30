-- =====================================================================
--  cilginyazilim.com – İşlem Geçmişi (Audit Log)
--    mysql -u root -p < cy_audit.sql
-- =====================================================================

-- ---------------------------------------------------------------------
--  ÖNEMLİ: İSTEMCİ KARAKTER SETİ
-- ---------------------------------------------------------------------
--  Bu satır olmadan `mysql -u root -p < dosya.sql` komutu, İSTEMCİNİN
--  varsayılan karakter setini kullanır. Windows'ta bu genellikle latin1
--  ya da cp1254'tür; dosyadaki UTF-8 baytları latin1 sanılıp yeniden
--  kodlanır ve veri ÇİFT KODLANMIŞ (mojibake) olarak girer:
--
--      "savunması"  →  "savunmasÄ±"
--
--  Hata sessizdir: kurulum başarıyla biter, tablolar oluşur, hiçbir
--  uyarı çıkmaz. Sorun ancak FULLTEXT araması Türkçe kelimeleri
--  bulamayınca ya da ekranda bozuk harfler görününce fark edilir.
--
--  SET NAMES, istemciye "gönderdiğim baytlar utf8mb4" der ve dosyanın
--  hangi istemciyle içe aktarıldığından bağımsız olarak doğru sonucu
--  garanti eder.
-- ---------------------------------------------------------------------
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `cy_audit`
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `cy_audit`;

DROP TABLE IF EXISTS `audit_log`;
DROP TABLE IF EXISTS `products`;

-- ---------------------------------------------------------------------
--  products  (denetlenen örnek varlık)
-- ---------------------------------------------------------------------
CREATE TABLE `products` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(150) NOT NULL,
  `sku`        VARCHAR(40)  NOT NULL,
  `price`      DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `stock`      INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_products_sku` (`sku`),
  KEY `idx_products_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  audit_log  (APPEND-ONLY — bu tabloya UPDATE/DELETE yolu YOKTUR)
--    old_values / new_values : JSON. AUDIT_MODE='diff' iken yalnızca
--    değişen alanlar; 'full' iken satırın tamamı. AUDIT_REDACT'teki
--    alanların değeri '***' olarak yazılır.
-- ---------------------------------------------------------------------
CREATE TABLE `audit_log` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `actor_id`    INT UNSIGNED NULL DEFAULT NULL,
  `actor_name`  VARCHAR(150) NOT NULL DEFAULT 'Sistem',
  `action`      ENUM('create','update','delete') NOT NULL,
  `entity_type` VARCHAR(60)  NOT NULL,
  `entity_id`   BIGINT UNSIGNED NOT NULL,
  `old_values`  JSON NULL,
  `new_values`  JSON NULL,
  `ip`          VARCHAR(45)  NOT NULL DEFAULT '',
  `user_agent`  VARCHAR(255) NOT NULL DEFAULT '',
  `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_entity`  (`entity_type`, `entity_id`),
  KEY `idx_audit_action`  (`action`),
  KEY `idx_audit_actor`   (`actor_id`),
  KEY `idx_audit_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  ÖRNEK ÜRÜNLER (denetlenen varlık)
-- ---------------------------------------------------------------------
INSERT INTO `products` (`id`, `name`, `sku`, `price`, `stock`) VALUES
( 1, 'Kablosuz Klavye',           'KBD-WL-001',  549.90, 120),
( 2, '27" IPS Monitör',           'MON-27-IPS', 4299.00,  34),
( 3, 'USB-C Hub 7-in-1',          'HUB-7IN1',    899.50,  76),
( 4, 'Mekanik Anahtar Seti',      'SW-MECH-90',  349.00, 210),
( 5, 'Laptop Soğutucu Stand',     'STND-COOL',   429.90,  58),
( 6, 'Taşınabilir SSD 1 TB',      'SSD-1TB-EXT',1899.00,  17),
( 7, 'Bluetooth Kulaklık',        'HP-BT-ANC',  2249.00,   0),
( 8, '4K Web Kamerası',           'CAM-4K-PRO', 1749.50,   9),
( 9, 'Dikey Ergonomik Mouse',     'MSE-VERT-01', 699.00,  64),
(10, 'USB Mikrofon',              'MIC-USB-CD', 1299.00,  23),
(11, 'Monitör Kolu (Çift)',       'ARM-DUAL-27', 989.90,  41),
(12, 'Kablo Düzenleyici Set',     'ORG-CBL-SET', 149.90, 305);

/* Silinen 13 numaralı ürünün izi aşağıdaki denetim kayıtlarında duruyor.
 * Sayacı 14'ten başlatıyoruz ki yeni eklenen ürün, silinmiş bir kaydın
 * numarasını devralmasın — denetim geçmişinde "13 numaralı ürün" iki
 * farklı şeyi anlatır hâle gelirdi. */
ALTER TABLE `products` AUTO_INCREMENT = 14;

-- ---------------------------------------------------------------------
--  ÖRNEK DENETİM KAYITLARI
-- ---------------------------------------------------------------------
--  Depoyu ilk kez açan kişi BOŞ bir geçmiş tablosu görmesin diye
--  birkaç örnek kayıt ekliyoruz. Üç işlem türü de temsil edilir:
--
--    create → new_values dolu, old_values NULL  (kayıt yeni doğdu)
--    update → yalnızca DEĞİŞEN alanlar          (diff)
--    delete → old_values dolu, new_values NULL  (kaydın son hâli)
--
--  Zamanlar NOW() - INTERVAL ile üretilir; böylece dosya ne zaman içe
--  aktarılırsa aktarılsın kayıtlar "son birkaç gün" içinde görünür ve
--  tarih aralığı filtresi ilk denemede anlamlı sonuç verir.
--
--  DİKKAT: audit_log'a elle INSERT yapmak YALNIZCA kurulum verisi için
--  yapılır. Uygulamanın içinde bu tabloya tek giriş yolu audit()
--  fonksiyonudur (bkz. system/function.php).
-- ---------------------------------------------------------------------
INSERT INTO `audit_log`
    (`actor_id`, `actor_name`, `action`, `entity_type`, `entity_id`, `old_values`, `new_values`, `ip`, `user_agent`, `created_at`)
VALUES
-- Kurulum sırasında oluşturulan ilk ürünler
(1, 'Evren ÇILGIN', 'create', 'product',  1, NULL,
    JSON_OBJECT('name','Kablosuz Klavye','sku','KBD-WL-001','price','549.90','stock',120),
    '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', NOW() - INTERVAL 9 DAY),

(1, 'Evren ÇILGIN', 'create', 'product',  2, NULL,
    JSON_OBJECT('name','27" IPS Monitör','sku','MON-27-IPS','price','3999.00','stock',40),
    '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', NOW() - INTERVAL 9 DAY),

(2, 'Ayşe DEMİR', 'create', 'product',  7, NULL,
    JSON_OBJECT('name','Bluetooth Kulaklık','sku','HP-BT-ANC','price','2499.00','stock',25),
    '192.168.1.40', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)', NOW() - INTERVAL 7 DAY),

-- Zam: tek alan değişti, diff yalnızca o alanı taşıyor
(1, 'Evren ÇILGIN', 'update', 'product',  2,
    JSON_OBJECT('price','3999.00'),
    JSON_OBJECT('price','4299.00'),
    '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', NOW() - INTERVAL 4 DAY),

-- İki alan birden değişti
(3, 'Mehmet YILMAZ', 'update', 'product',  5,
    JSON_OBJECT('price','379.90','stock',95),
    JSON_OBJECT('price','429.90','stock',58),
    '10.0.0.12', 'Mozilla/5.0 (X11; Linux x86_64)', NOW() - INTERVAL 3 DAY),

-- Kampanya indirimi
(2, 'Ayşe DEMİR', 'update', 'product',  7,
    JSON_OBJECT('price','2499.00'),
    JSON_OBJECT('price','2249.00'),
    '192.168.1.40', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)', NOW() - INTERVAL 2 DAY),

-- Stok tükendi
(3, 'Mehmet YILMAZ', 'update', 'product',  7,
    JSON_OBJECT('stock',25),
    JSON_OBJECT('stock',0),
    '10.0.0.12', 'Mozilla/5.0 (X11; Linux x86_64)', NOW() - INTERVAL 1 DAY),

-- Silinen ürün: products tablosunda ARTIK YOK, tek izi bu satır
(1, 'Evren ÇILGIN', 'delete', 'product', 13,
    JSON_OBJECT('name','Eski Model Fare','sku','MSE-OLD-99','price','129.00','stock',0),
    NULL,
    '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', NOW() - INTERVAL 6 HOUR);

-- ---------------------------------------------------------------------
--  MEVCUT KURULUMU YÜKSELTME (veri kaybetmeden)
-- ---------------------------------------------------------------------
--  Bu depodan daha eski bir sürüm çalıştırıyorsanız, tabloları
--  silmeden aşağıdaki komutlarla güncelleyebilirsiniz. Yorumu kaldırıp
--  çalıştırın; zaten varsa MySQL "Duplicate" uyarısı verir, veri
--  bozulmaz.
-- ---------------------------------------------------------------------
-- ALTER TABLE `audit_log` ADD KEY `idx_audit_created` (`created_at`);
-- ALTER TABLE `audit_log` ADD KEY `idx_audit_entity` (`entity_type`, `entity_id`);
-- ALTER TABLE `products`  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- ALTER TABLE `audit_log` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
