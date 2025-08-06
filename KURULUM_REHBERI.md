# Villa Rezervasyon Sistemi - Kurulum Rehberi

## 📋 Gereksinimler

### Sunucu Gereksinimleri
- **PHP:** 7.4 veya üzeri
- **WordPress:** 5.0 veya üzeri  
- **WooCommerce:** 5.0 veya üzeri
- **MySQL:** 5.6 veya üzeri
- **cURL:** Etkin olmalı (Google Sheets entegrasyonu için)

### WordPress Gereksinimleri
- WooCommerce eklentisi kurulu ve aktif
- WordPress'de dosya yazma izinleri
- wp-cron işlevselliği aktif

## 🚀 Kurulum Adımları

### 1. Eklenti Kurulumu

#### WordPress Admin Paneli Üzerinden:
1. WordPress admin paneline giriş yapın
2. **Eklentiler → Yeni Ekle** sayfasına gidin
3. **Eklenti Yükle** butonuna tıklayın
4. `villa-reservation-system.zip` dosyasını seçin
5. **Şimdi Kur** butonuna tıklayın
6. Kurulum tamamlandıktan sonra **Etkinleştir** butonuna tıklayın

#### FTP ile Manuel Kurulum:
1. `villa-reservation-system.zip` dosyasını çıkartın
2. `villa-reservation-system` klasörünü `/wp-content/plugins/` dizinine yükleyin
3. WordPress admin panelinde **Eklentiler** sayfasına gidin
4. "Villa Reservation System" eklentisini bulun ve **Etkinleştir** butonuna tıklayın

### 2. İlk Yapılandırma

#### Temel Ayarlar:
1. **Villa Rezervasyon → Ayarlar** sayfasına gidin
2. **Genel Ayarlar** sekmesinde:
   - Varsayılan check-in/check-out saatlerini belirleyin
   - Minimum/maksimum konaklama sürelerini ayarlayın
   - İptal politikalarını yapılandırın

3. **E-posta Ayarları** sekmesinde:
   - Gönderen adı ve e-posta adresini ayarlayın
   - Hatırlatma e-postası ayarlarını yapılandırın

#### Google Sheets Entegrasyonu (İsteğe Bağlı):
1. Google Cloud Console'da proje oluşturun
2. Google Sheets API'yi etkinleştirin
3. Service Account oluşturun ve JSON key dosyasını indirin
4. **Villa Rezervasyon → Ayarlar → Google Sheets Ayarları** sekmesinde JSON dosyasını yükleyin

### 3. Villa Ekleme

1. **Villa Rezervasyon → Villa Ekle** sayfasına gidin
2. Villa bilgilerini doldurun:
   - Villa adı ve açıklaması
   - Öne çıkan görsel
   - Villa kategorisi ve etiketleri

3. **Villa Ayarları** meta box'ında:
   - Maksimum kapasite bilgilerini girin
   - Check-in/check-out saatlerini belirleyin
   - Minimum/maksimum konaklama sürelerini ayarlayın

4. **Fiyatlandırma** meta box'ında:
   - Fiyatlandırma türünü seçin (Sabit fiyat / Kişi başı)
   - Fiyat bilgilerini girin

5. Google Sheets kullanıyorsanız **Google Sheets Entegrasyonu** meta box'ında:
   - Google Sheets URL'sini girin
   - Sayfa adını belirtin
   - Bağlantıyı test edin

## ⚙️ Detaylı Yapılandırma

### WooCommerce Ayarları

Eklenti WooCommerce ile otomatik entegre olur, ancak bazı ayarları kontrol etmelisiniz:

1. **WooCommerce → Ayarlar → Ürünler** sayfasında:
   - Sanal ürünler için kargo ayarlarını devre dışı bırakın

2. **WooCommerce → Ayarlar → Ödeme** sayfasında:
   - Kullanmak istediğiniz ödeme yöntemlerini aktif edin

### E-posta Şablonları

Eklenti aşağıdaki e-posta şablonlarını kullanır:
- Rezervasyon onay e-postası
- Yeni rezervasyon bildirimi (admin)
- Rezervasyon hatırlatması
- İptal bildirimi

Şablonları özelleştirmek için `wp-content/themes/your-theme/villa-reservation-system/emails/` klasöründe düzenleyebilirsiniz.

### Google Sheets Yapılandırması

#### 1. Google Cloud Console Kurulumu:
```
1. https://console.cloud.google.com/ adresine gidin
2. Yeni proje oluşturun
3. "APIs & Services" → "Library" sayfasına gidin
4. "Google Sheets API" aratın ve etkinleştirin
5. "Credentials" sayfasına gidin
6. "Create Credentials" → "Service Account" seçin
7. Service account detaylarını doldurun
8. "Keys" sekmesinde "Add Key" → "Create new key" → "JSON" seçin
9. İndirilen JSON dosyasını saklayın
```

#### 2. Google Sheets Dosya Kurulumu:
```
1. Yeni bir Google Sheets dosyası oluşturun
2. İlk satıra tarih başlıklarını ekleyin (A1: "01.01.2024", B1: "02.01.2024" vb.)
3. Service Account'un e-posta adresini Google Sheets'e düzenleyici olarak paylaşın
4. Dosya URL'sini WordPress admin panelinde villa ayarlarına ekleyin
```

## 🎨 Frontend Entegrasyonu

### Rezervasyon Formu Gösterimi

Rezervasyon formu otomatik olarak tekil villa sayfalarında görünür. Manuel olarak eklemek için:

```php
// Tema dosyalarında
echo do_shortcode('[villa_reservation_form villa_id="123"]');

// Widget'larda
[villa_reservation_form villa_id="123"]
```

### Takvim Gösterimi

Villa için müsaitlik takvimi göstermek için:

```php
// Tema dosyalarında  
echo do_shortcode('[villa_calendar villa_id="123"]');

// Widget'larda
[villa_calendar villa_id="123"]
```

### Kullanıcı Rezervasyon Sayfası

Kullanıcıların rezervasyonlarını görmesi için sayfa oluşturun:

1. **Sayfalar → Yeni Ekle** sayfasına gidin
2. Sayfa başlığı: "Rezervasyonlarım"
3. İçeriğe şu shortcode'u ekleyin: `[villa_my_reservations]`
4. Sayfayı yayınlayın

## 🔒 Güvenlik Ayarları

### Dosya İzinleri
```bash
# Plugin klasörü
chmod 755 /wp-content/plugins/villa-reservation-system/

# Upload klasörü (Google Sheets JSON dosyası için)
chmod 755 /wp-content/uploads/villa-reservation-system/
chmod 644 /wp-content/uploads/villa-reservation-system/service-account.json
```

### Cron Jobs

Eklenti aşağıdaki cron job'ları kullanır:
- Google Sheets senkronizasyonu (günlük)
- E-posta hatırlatmaları (günlük)  
- Süresi dolmuş rezervasyon temizliği (günlük)

WordPress cron'unun çalıştığından emin olun.

## 🧪 Test Etme

### Temel İşlevsellik Testi:

1. **Villa Ekleme Testi:**
   - Yeni villa ekleyin
   - Tüm meta field'ların kaydedildiğini kontrol edin

2. **Rezervasyon Testi:**
   - Frontend'de rezervasyon formu doldurun
   - WooCommerce checkout sürecini tamamlayın
   - E-posta bildirimlerinin geldiğini kontrol edin

3. **Google Sheets Testi:**
   - Test bağlantısı butonunu kullanın
   - Manuel senkronizasyon yapın
   - Rezervasyon sonrası Sheets'te renklendirme kontrolü

### Admin Panel Testi:

1. **Villa Rezervasyon → Genel Bakış** sayfasında istatistikleri kontrol edin
2. **Rezervasyonlar** sayfasında filtreleme işlevlerini test edin
3. **Raporlar** sayfasında farklı tarih aralıklarını deneyin

## 🔄 Güncelleme

### Eklenti Güncellemesi:
1. Mevcut eklentiyi devre dışı bırakın
2. Eski plugin klasörünü silin
3. Yeni versiyonu yükleyin
4. Eklentiyi tekrar etkinleştirin

**Önemli:** Güncelleme öncesi mutlaka veritabanı yedeği alın!

### Veritabanı Yedeği:
```sql
-- Rezervasyon tabloları
CREATE TABLE backup_vrs_reservations AS SELECT * FROM wp_vrs_reservations;
CREATE TABLE backup_vrs_villa_settings AS SELECT * FROM wp_vrs_villa_settings;
CREATE TABLE backup_vrs_blocked_dates AS SELECT * FROM wp_vrs_blocked_dates;
CREATE TABLE backup_vrs_pricing AS SELECT * FROM wp_vrs_pricing;
CREATE TABLE backup_vrs_guest_info AS SELECT * FROM wp_vrs_guest_info;
CREATE TABLE backup_vrs_email_log AS SELECT * FROM wp_vrs_email_log;
```

## 📞 Destek

### Yaygın Sorunlar:

**Rezervasyon formu görünmüyor:**
- WooCommerce'in aktif olduğunu kontrol edin
- Tema'nın `the_content` filtresi kullandığından emin olun

**Google Sheets bağlantı hatası:**
- Service Account JSON dosyasının doğru yüklendiğini kontrol edin
- Google Sheets'in Service Account ile paylaşıldığından emin olun
- cURL'nin sunucuda etkin olduğunu kontrol edin

**E-posta gönderiminde sorun:**
- WordPress wp_mail fonksiyonunun çalıştığını test edin
- SMTP eklentisi kullanmayı düşünün

**Cron job'lar çalışmıyor:**
- WordPress cron'un etkin olduğunu kontrol edin
- Sunucu cron job'ları kurulumunu düşünün

### Log Dosyaları:

Hata ayıklama için WordPress debug loglarını kontrol edin:
```php
// wp-config.php dosyasına ekleyin
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

## 📋 Özellikler Özeti

### ✅ Mevcut Özellikler:
- Villa custom post type ve taxonomies
- Rezervasyon yönetim sistemi  
- WooCommerce entegrasyonu
- Google Sheets senkronizasyonu
- E-posta bildirimleri
- Admin dashboard ve raporlar
- Responsive frontend arayüzü
- Türkçe dil desteği

### 🎯 Kullanım Senaryoları:
- Tatil villaları
- Butik oteller
- Bungalov işletmeleri
- Kiralık villa siteleri
- Emlak rezervasyon sistemleri

## 📄 Lisans

Bu eklenti GPL v2 lisansı altında dağıtılmaktadır.

---

**Başarılı kurulumlar dileriz! 🎉**

*Son güncelleme: {{ current_date }}*