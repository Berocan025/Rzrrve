# Villa Rezervasyon Sistemi

WooCommerce entegrasyonu ve Google Sheets senkronizasyonu olan profesyonel villa rezervasyon sistemi.

## Özellikler

### 🏠 Villa Yönetimi
- Custom post type ile villa tanımlama
- Villa kategorileri ve etiketleri
- Kapsamlı villa ayarları (kapasite, konaklama süreleri, check-in/out saatleri)
- Esnek fiyatlandırma sistemi (kişi başı / sabit fiyat)
- Villa görselleri ve açıklamaları

### 📅 Rezervasyon Sistemi
- Kullanıcı dostu tarih seçim arayüzü
- Gerçek zamanlı müsaitlik kontrolü
- Çakışan rezervasyonları engelleyen sistem
- Minimum/maksimum konaklama süresi kontrolü
- Misafir sayısı kontrolü (yetişkin/çocuk)
- Çocuk yaş aralıkları
- Özel istekler ve notlar

### 💰 WooCommerce Entegrasyonu
- Her rezervasyon için otomatik WooCommerce ürünü
- Sipariş durumu ile rezervasyon durumu senkronizasyonu
- WooCommerce checkout süreci
- Ödeme gateway'leri desteği
- Sipariş e-postalarında rezervasyon detayları

### 📊 Google Sheets Entegrasyonu
- Villa başına ayrı Google Sheets tablosu
- İki yönlü senkronizasyon
- Google Sheets'te boyalı hücre = sitede kapalı tarih
- Siteden rezervasyon = Sheets'te otomatik renklendirme
- Google Sheets API v4 desteği
- Otomatik senkronizasyon (cron jobs)

### 📧 E-posta Sistemi
- Rezervasyon onay mailleri
- Admin bildirim mailleri
- Hatırlatma mailleri
- İptal bildirimleri
- Tamamen Türkçe mail şablonları
- HTML email desteği

### 🎛️ Admin Paneli
- Kapsamlı dashboard
- Rezervasyon listesi ve filtreleme
- Takvim görünümü
- Villa ayarları yönetimi
- Google Sheets ayarları
- Manuel tarih bloke/açma
- İstatistikler ve raporlar

### 🎨 Frontend Özellikleri
- Responsive tasarım
- jQuery UI Datepicker
- AJAX form gönderimi
- Gerçek zamanlı fiyat hesaplama
- Kullanıcı rezervasyon geçmişi
- Modern ve kullanıcı dostu arayüz

## Gereksinimler

- WordPress 5.0+
- WooCommerce 5.0+
- PHP 7.4+
- MySQL 5.6+

## Kurulum

1. Plugin dosyasını WordPress admin panelinden yükleyin
2. Eklentiyi etkinleştirin
3. WooCommerce'in kurulu ve aktif olduğundan emin olun
4. Villa Rezervasyon menüsünden ayarları yapılandırın

## Google Sheets Entegrasyonu Kurulumu

1. Google Cloud Console'da proje oluşturun
2. Google Sheets API'yi etkinleştirin
3. Service Account oluşturun
4. JSON key dosyasını indirin
5. Admin panelinden JSON dosyasını yükleyin
6. Her villa için Google Sheets URL'ini ekleyin

## Kullanım

### Villa Ekleme
1. Admin panelinden "Villalar" → "Yeni Ekle"
2. Villa bilgilerini doldurun
3. Villa ayarlarını yapılandırın
4. Fiyatlandırma bilgilerini girin
5. Google Sheets URL'ini ekleyin (opsiyonel)

### Rezervasyon Formu
Villa sayfasına otomatik olarak rezervasyon formu eklenir. Kısa kod ile manuel ekleme:
```
[villa_reservation_form villa_id="123"]
```

### Takvim Görünümü
```
[villa_calendar villa_id="123"]
```

## Database Tabloları

- `vrs_reservations` - Rezervasyon bilgileri
- `vrs_villa_settings` - Villa ayarları
- `vrs_blocked_dates` - Bloke tarihler
- `vrs_pricing` - Fiyatlandırma kuralları
- `vrs_guest_info` - Misafir bilgileri
- `vrs_email_log` - E-posta geçmişi

## Hooks ve Filtreler

### Actions
- `vrs_reservation_created` - Rezervasyon oluşturulduğunda
- `vrs_reservation_confirmed` - Rezervasyon onaylandığında
- `vrs_reservation_cancelled` - Rezervasyon iptal edildiğinde
- `vrs_before_price_calculation` - Fiyat hesabından önce
- `vrs_after_price_calculation` - Fiyat hesabından sonra

### Filters
- `vrs_reservation_form_fields` - Rezervasyon formu alanları
- `vrs_price_calculation` - Fiyat hesaplama
- `vrs_email_content` - E-posta içeriği
- `vrs_blocked_dates` - Bloke tarihler

## Güvenlik

- Nonce verification
- Data sanitization
- SQL injection koruması
- XSS koruması
- Capability checks
- CSRF koruması

## Performans

- AJAX ile sayfa yenilenmeden işlemler
- Optimized database queries
- Caching desteği
- Lazy loading
- Minified CSS/JS

## Destek

Bu plugin tamamen ücretsizdir ve açık kaynak kodludur. Özelleştirme ihtiyaçlarınız için profesyonel destek alabilirsiniz.

## Lisans

Bu proje MIT lisansı altında yayınlanmaktadır.

## Changelog

### 1.0.0
- İlk sürüm
- Tüm temel özellikler
- WooCommerce entegrasyonu
- Google Sheets senkronizasyonu
- Türkçe dil desteği