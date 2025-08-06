# Villa Rezervasyon Sistemi

WordPress için kapsamlı villa ve bungalov rezervasyon sistemi. WooCommerce entegrasyonu, Google Sheets senkronizasyonu ve tam Türkçe desteği içerir.

## 🌟 Özellikler

### ✅ WooCommerce Entegrasyonu
- Her villa için otomatik WooCommerce ürün oluşturma
- Entegre ödeme sistemi
- Sipariş yönetimi ve takibi
- E-ticaret raporları

### ✅ Google Sheets Senkronizasyonu
- İki yönlü veri senkronizasyonu
- Gerçek zamanlı müsaitlik güncellemesi
- Google Sheets'ten manuel tarih bloklaması
- Otomatik rezervasyon kayıtları

### ✅ Rezervasyon Sistemi
- Gelişmiş tarih seçici
- Misafir sayısı ve yaş aralığı kontrolü
- Otomatik fiyat hesaplama
- Çakışma kontrolü

### ✅ Admin Paneli
- Kapsamlı rezervasyon yönetimi
- Takvim görünümü
- Detaylı raporlar
- Villa ayarları ve fiyatlandırma

### ✅ E-posta Sistemi
- Otomatik onay mailleri
- Hatırlatma e-postaları
- Admin bildirimleri
- Özelleştirilebilir şablonlar

## 📋 Sistem Gereksinimleri

- **WordPress:** 5.0 veya üzeri
- **PHP:** 7.4 veya üzeri
- **WooCommerce:** 5.0 veya üzeri
- **MySQL:** 5.6 veya üzeri

## 🚀 Kurulum

### 1. Eklenti Yüklemesi
1. `villa-reservation-system.zip` dosyasını indirin
2. WordPress admin panelinde **Eklentiler > Yeni Ekle** bölümüne gidin
3. **Eklenti Yükle** butonuna tıklayın
4. Zip dosyasını seçin ve yükleyin
5. Eklentiyi etkinleştirin

### 2. Temel Ayarlar
1. **Villa Rezervasyon > Ayarlar** menüsüne gidin
2. E-posta ayarlarını yapılandırın
3. Para birimi ve temel kuralları belirleyin
4. Ayarları kaydedin

### 3. Google Sheets Entegrasyonu (Opsiyonel)
1. [Google Cloud Console](https://console.cloud.google.com/) hesabı oluşturun
2. Service Account oluşturun ve JSON anahtarı indirin
3. **Villa Rezervasyon > Google Sheets** bölümünden JSON dosyasını yükleyin
4. Her villa için Google Sheets URL'sini ayarlayın

## 📖 Kullanım Kılavuzu

### Villa Ekleme
1. **Villalar > Yeni Villa Ekle** menüsüne gidin
2. Villa bilgilerini doldurun
3. **Villa Ayarları** bölümünde kapasiteyi belirleyin
4. **Fiyatlandırma** bölümünde fiyat tipini seçin
5. Gerekirse Google Sheets bağlantısı yapın
6. Villayı yayınlayın

### Rezervasyon Yönetimi
1. **Villa Rezervasyon > Rezervasyonlar** menüsünden tüm rezervasyonları görüntüleyin
2. Durum filtrelerini kullanarak rezervasyonları süzün
3. Rezervasyonları onaylayın veya iptal edin
4. Detaylı rezervasyon bilgilerini görüntüleyin

### Takvim Görünümü
1. **Villa Rezervasyon > Takvim** menüsüne gidin
2. Aylık rezervasyon görünümünü inceleyin
3. Çakışan rezervasyonları kontrol edin
4. Boş tarihleri görüntüleyin

### Raporlar
1. **Villa Rezervasyon > Raporlar** bölümünde performans analizlerini görüntüleyin
2. Tarih aralığı seçerek özel raporlar oluşturun
3. Villa bazında analiz yapın
4. Gelir takibi yapın

## 🎨 Özelleştirme

### Tema Entegrasyonu
Rezervasyon formunu tema dosyalarınıza eklemek için:

```php
// Single villa sayfasında otomatik gösterim
// Otomatik olarak eklenir, manuel müdahale gerektirmez

// Manuel yerleştirme için shortcode
echo do_shortcode('[vrs_reservation_form villa_id="123"]');

// Takvim görünümü için
echo do_shortcode('[vrs_calendar villa_id="123"]');
```

### CSS Özelleştirme
Tema CSS dosyanızda özelleştirmeler yapabilirsiniz:

```css
.vrs-reservation-form {
    /* Form stillerini özelleştirin */
}

.vrs-price-display {
    /* Fiyat gösterimi stillerini değiştirin */
}
```

### Hook'lar ve Filtreler

```php
// Rezervasyon oluşturulduğunda
add_action('vrs_reservation_created', 'my_reservation_created_function');

// Rezervasyon onaylandığında
add_action('vrs_reservation_confirmed', 'my_reservation_confirmed_function');

// Fiyat hesaplama filtresini özelleştirin
add_filter('vrs_calculate_price', 'my_custom_price_calculation', 10, 5);

// E-posta şablonlarını özelleştirin
add_filter('vrs_email_template', 'my_custom_email_template', 10, 3);
```

## 🗄️ Veritabanı Yapısı

Eklenti aşağıdaki özel tabloları oluşturur:

- `wp_vrs_reservations` - Rezervasyon kayıtları
- `wp_vrs_villa_settings` - Villa ayarları
- `wp_vrs_blocked_dates` - Bloklanmış tarihler
- `wp_vrs_pricing` - Fiyatlandırma kuralları
- `wp_vrs_guest_info` - Misafir bilgileri
- `wp_vrs_email_log` - E-posta kayıtları

## 🔧 Sorun Giderme

### Yaygın Sorunlar

**Rezervasyon formu görünmüyor:**
- Villa post type'ının düzgün kayıtlı olduğunu kontrol edin
- JavaScript dosyalarının yüklendiğini kontrol edin
- Tema uyumluluğunu kontrol edin

**Google Sheets senkronizasyonu çalışmıyor:**
- JSON kimlik dosyasının doğru yüklendiğini kontrol edin
- Google Sheets URL'sinin doğru olduğunu kontrol edin
- Service Account'un Google Sheets'e erişim yetkisi olduğunu kontrol edin

**E-postalar gönderilmiyor:**
- WordPress e-posta ayarlarını kontrol edin
- SMTP eklentisi kullanmayı deneyin
- E-posta loglarını kontrol edin

### Debug Modu
Debug bilgileri için `wp-config.php` dosyasına ekleyin:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('VRS_DEBUG', true);
```

## 📄 Lisans

Bu eklenti GPL v2 veya daha üst sürüm lisansı altında dağıtılmaktadır.

## 🆘 Destek

Teknik destek ve özellik talepleri için:
- GitHub Issues kullanın
- WordPress.org destek forumuna yazın
- Dokümantasyonu inceleyin

## 📝 Değişiklik Günlüğü

### 1.0.0
- İlk stabil sürüm
- Tüm temel özellikler tamamlandı
- WooCommerce entegrasyonu
- Google Sheets senkronizasyonu
- Türkçe dil desteği
- Responsive tasarım
- E-posta sistemi

## 🤝 Katkıda Bulunma

Bu proje açık kaynak kodludur. Katkılarınızı bekliyoruz:

1. Repository'yi fork edin
2. Feature branch oluşturun (`git checkout -b feature/AmazingFeature`)
3. Değişikliklerinizi commit edin (`git commit -m 'Add some AmazingFeature'`)
4. Branch'inizi push edin (`git push origin feature/AmazingFeature`)
5. Pull Request oluşturun

## 🌟 Teşekkürler

- WordPress topluluğuna
- WooCommerce geliştirici ekibine
- Google Sheets API ekibine
- Açık kaynak topluluğuna

---

**Villa Rezervasyon Sistemi** ile villa işletmenizi dijitalleştirin! 🏖️