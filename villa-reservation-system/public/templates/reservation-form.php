<?php
/**
 * Villa Rezervasyon Formu
 * Frontend rezervasyon formu template
 */

if (!defined('ABSPATH')) {
    exit;
}

$villa_info = VRS_Villa_System::get_villa_info($villa_id);

if (!$villa_info) {
    echo '<p>' . __('Villa bilgileri yüklenemedi.', 'villa-reservation-system') . '</p>';
    return;
}

// Form ID oluştur
$form_id = 'vrs-reservation-form-' . $villa_id;
?>

<div class="vrs-reservation-form-container" id="<?php echo esc_attr($form_id); ?>">
    <div class="vrs-form-header">
        <h3><?php _e('Rezervasyon Yap', 'villa-reservation-system'); ?></h3>
        <p class="vrs-villa-name"><?php echo esc_html($villa_info['title']); ?></p>
    </div>

    <form class="vrs-reservation-form" method="post" action="#" novalidate>
        <?php wp_nonce_field('vrs_reservation_nonce', 'vrs_nonce'); ?>
        <input type="hidden" name="villa_id" value="<?php echo esc_attr($villa_id); ?>">
        
        <!-- Tarih Seçimi -->
        <div class="vrs-form-section vrs-dates-section">
            <h4><?php _e('Tarih Seçimi', 'villa-reservation-system'); ?></h4>
            
            <div class="vrs-date-fields">
                <div class="vrs-field vrs-field-half">
                    <label for="checkin_date_<?php echo $villa_id; ?>">
                        <?php _e('Giriş Tarihi', 'villa-reservation-system'); ?>
                        <span class="required">*</span>
                    </label>
                    <input type="text" 
                           id="checkin_date_<?php echo $villa_id; ?>" 
                           name="checkin_date" 
                           class="vrs-datepicker vrs-checkin-date" 
                           placeholder="<?php _e('Tarih seçin', 'villa-reservation-system'); ?>"
                           autocomplete="off"
                           required>
                </div>
                
                <div class="vrs-field vrs-field-half">
                    <label for="checkout_date_<?php echo $villa_id; ?>">
                        <?php _e('Çıkış Tarihi', 'villa-reservation-system'); ?>
                        <span class="required">*</span>
                    </label>
                    <input type="text" 
                           id="checkout_date_<?php echo $villa_id; ?>" 
                           name="checkout_date" 
                           class="vrs-datepicker vrs-checkout-date" 
                           placeholder="<?php _e('Tarih seçin', 'villa-reservation-system'); ?>"
                           autocomplete="off"
                           required>
                </div>
            </div>
            
            <div class="vrs-date-info">
                <div class="vrs-nights-count">
                    <span class="vrs-nights-label"><?php _e('Gece sayısı:', 'villa-reservation-system'); ?></span>
                    <span class="vrs-nights-value">0</span>
                </div>
                <div class="vrs-date-constraints">
                    <small>
                        <?php printf(
                            __('Min %d gün - Max %d gün', 'villa-reservation-system'),
                            $villa_info['minimum_stay'],
                            $villa_info['maximum_stay']
                        ); ?>
                    </small>
                </div>
            </div>
        </div>

        <!-- Misafir Seçimi -->
        <div class="vrs-form-section vrs-guests-section">
            <h4><?php _e('Misafir Sayısı', 'villa-reservation-system'); ?></h4>
            
            <div class="vrs-guest-fields">
                <div class="vrs-field vrs-field-half">
                    <label for="adults_<?php echo $villa_id; ?>">
                        <?php _e('Yetişkin', 'villa-reservation-system'); ?>
                        <span class="required">*</span>
                    </label>
                    <div class="vrs-number-input">
                        <button type="button" class="vrs-number-decrease" data-target="adults_<?php echo $villa_id; ?>">-</button>
                        <input type="number" 
                               id="adults_<?php echo $villa_id; ?>" 
                               name="adults" 
                               min="1" 
                               max="<?php echo esc_attr($villa_info['max_adults']); ?>" 
                               value="2" 
                               class="vrs-guest-input"
                               required>
                        <button type="button" class="vrs-number-increase" data-target="adults_<?php echo $villa_id; ?>">+</button>
                    </div>
                    <small><?php printf(__('Max %d', 'villa-reservation-system'), $villa_info['max_adults']); ?></small>
                </div>
                
                <div class="vrs-field vrs-field-half">
                    <label for="children_<?php echo $villa_id; ?>">
                        <?php _e('Çocuk', 'villa-reservation-system'); ?>
                    </label>
                    <div class="vrs-number-input">
                        <button type="button" class="vrs-number-decrease" data-target="children_<?php echo $villa_id; ?>">-</button>
                        <input type="number" 
                               id="children_<?php echo $villa_id; ?>" 
                               name="children" 
                               min="0" 
                               max="<?php echo esc_attr($villa_info['max_children']); ?>" 
                               value="0" 
                               class="vrs-guest-input">
                        <button type="button" class="vrs-number-increase" data-target="children_<?php echo $villa_id; ?>">+</button>
                    </div>
                    <small><?php printf(__('Max %d', 'villa-reservation-system'), $villa_info['max_children']); ?></small>
                </div>
            </div>
            
            <!-- Çocuk Yaş Aralıkları -->
            <div class="vrs-children-ages" style="display: none;">
                <h5><?php _e('Çocuk Yaşları', 'villa-reservation-system'); ?></h5>
                <div class="vrs-children-ages-container">
                    <!-- JavaScript ile dinamik olarak doldurulacak -->
                </div>
            </div>
            
            <div class="vrs-capacity-info">
                <div class="vrs-total-guests">
                    <span class="vrs-guests-label"><?php _e('Toplam kişi:', 'villa-reservation-system'); ?></span>
                    <span class="vrs-guests-value">2</span>
                    <span class="vrs-guests-max">/ <?php echo $villa_info['max_capacity']; ?></span>
                </div>
            </div>
        </div>

        <!-- Fiyat Bilgisi -->
        <div class="vrs-form-section vrs-pricing-section">
            <h4><?php _e('Fiyat Bilgisi', 'villa-reservation-system'); ?></h4>
            
            <div class="vrs-price-loading" style="display: none;">
                <span class="vrs-spinner"></span>
                <span><?php _e('Fiyat hesaplanıyor...', 'villa-reservation-system'); ?></span>
            </div>
            
            <div class="vrs-price-details" style="display: none;">
                <div class="vrs-price-breakdown">
                    <div class="vrs-price-row">
                        <span class="vrs-price-label"><?php _e('Günlük fiyat:', 'villa-reservation-system'); ?></span>
                        <span class="vrs-price-value vrs-daily-price">0 TL</span>
                    </div>
                    <div class="vrs-price-row vrs-nights-price">
                        <span class="vrs-price-label">
                            <span class="vrs-nights-text">0 gece</span> x 
                            <span class="vrs-daily-amount">0 TL</span>
                        </span>
                        <span class="vrs-price-value vrs-subtotal">0 TL</span>
                    </div>
                    <div class="vrs-price-row vrs-tax-row" style="display: none;">
                        <span class="vrs-price-label"><?php _e('Vergi:', 'villa-reservation-system'); ?></span>
                        <span class="vrs-price-value vrs-tax-amount">0 TL</span>
                    </div>
                    <div class="vrs-price-row vrs-total-row">
                        <span class="vrs-price-label vrs-total-label"><?php _e('Toplam:', 'villa-reservation-system'); ?></span>
                        <span class="vrs-price-value vrs-total-price">0 TL</span>
                    </div>
                </div>
            </div>
            
            <div class="vrs-price-notice">
                <p><small><?php _e('Fiyatlar tarih ve misafir sayısına göre hesaplanır.', 'villa-reservation-system'); ?></small></p>
            </div>
        </div>

        <!-- Misafir Bilgileri -->
        <div class="vrs-form-section vrs-guest-info-section">
            <h4><?php _e('İletişim Bilgileri', 'villa-reservation-system'); ?></h4>
            
            <div class="vrs-guest-fields">
                <div class="vrs-field">
                    <label for="guest_name_<?php echo $villa_id; ?>">
                        <?php _e('Ad Soyad', 'villa-reservation-system'); ?>
                        <span class="required">*</span>
                    </label>
                    <input type="text" 
                           id="guest_name_<?php echo $villa_id; ?>" 
                           name="guest_name" 
                           placeholder="<?php _e('Adınız ve soyadınız', 'villa-reservation-system'); ?>"
                           required>
                </div>
                
                <div class="vrs-field vrs-field-half">
                    <label for="guest_email_<?php echo $villa_id; ?>">
                        <?php _e('E-posta', 'villa-reservation-system'); ?>
                        <span class="required">*</span>
                    </label>
                    <input type="email" 
                           id="guest_email_<?php echo $villa_id; ?>" 
                           name="guest_email" 
                           placeholder="<?php _e('ornek@email.com', 'villa-reservation-system'); ?>"
                           required>
                </div>
                
                <div class="vrs-field vrs-field-half">
                    <label for="guest_phone_<?php echo $villa_id; ?>">
                        <?php _e('Telefon', 'villa-reservation-system'); ?>
                        <span class="required">*</span>
                    </label>
                    <input type="tel" 
                           id="guest_phone_<?php echo $villa_id; ?>" 
                           name="guest_phone" 
                           placeholder="<?php _e('0555 123 45 67', 'villa-reservation-system'); ?>"
                           required>
                </div>
                
                <div class="vrs-field">
                    <label for="special_requests_<?php echo $villa_id; ?>">
                        <?php _e('Özel İstekler', 'villa-reservation-system'); ?>
                    </label>
                    <textarea id="special_requests_<?php echo $villa_id; ?>" 
                              name="special_requests" 
                              rows="3" 
                              placeholder="<?php _e('Özel istekleriniz varsa buraya yazabilirsiniz...', 'villa-reservation-system'); ?>"></textarea>
                </div>
            </div>
        </div>

        <!-- Onay ve Şartlar -->
        <div class="vrs-form-section vrs-terms-section">
            <div class="vrs-terms-checkbox">
                <label class="vrs-checkbox-label">
                    <input type="checkbox" name="accept_terms" required>
                    <span class="vrs-checkmark"></span>
                    <?php printf(
                        __('<a href="%s" target="_blank">Kullanım şartlarını</a> ve <a href="%s" target="_blank">gizlilik politikasını</a> okudum, kabul ediyorum.', 'villa-reservation-system'),
                        '#', // Buraya şartlar sayfası linki
                        '#'  // Buraya gizlilik politikası linki
                    ); ?>
                </label>
            </div>
        </div>

        <!-- Form Mesajları -->
        <div class="vrs-form-messages">
            <div class="vrs-error-message" style="display: none;"></div>
            <div class="vrs-success-message" style="display: none;"></div>
        </div>

        <!-- Submit Button -->
        <div class="vrs-form-submit">
            <button type="submit" class="vrs-submit-btn" disabled>
                <span class="vrs-btn-text"><?php _e('Rezervasyon Yap', 'villa-reservation-system'); ?></span>
                <span class="vrs-btn-loading" style="display: none;">
                    <span class="vrs-spinner"></span>
                    <?php _e('İşleniyor...', 'villa-reservation-system'); ?>
                </span>
            </button>
            
            <p class="vrs-submit-notice">
                <small><?php _e('Rezervasyonunuz 30 dakika süreyle geçerlidir. Bu süre içinde ödeme yapmanız gerekmektedir.', 'villa-reservation-system'); ?></small>
            </p>
        </div>
    </form>
</div>

<!-- Villa Data for JavaScript -->
<script type="text/javascript">
if (typeof vrsVillaData === 'undefined') {
    var vrsVillaData = {};
}

vrsVillaData[<?php echo $villa_id; ?>] = {
    villaId: <?php echo $villa_id; ?>,
    maxCapacity: <?php echo $villa_info['max_capacity']; ?>,
    maxAdults: <?php echo $villa_info['max_adults']; ?>,
    maxChildren: <?php echo $villa_info['max_children']; ?>,
    minimumStay: <?php echo $villa_info['minimum_stay']; ?>,
    maximumStay: <?php echo $villa_info['maximum_stay']; ?>,
    checkinTime: '<?php echo esc_js($villa_info['checkin_time']); ?>',
    checkoutTime: '<?php echo esc_js($villa_info['checkout_time']); ?>',
    pricingType: '<?php echo esc_js($villa_info['pricing_type']); ?>',
    basePrice: <?php echo floatval($villa_info['base_price']); ?>,
    adultPrice: <?php echo floatval($villa_info['adult_price']); ?>,
    childPrice: <?php echo floatval($villa_info['child_price']); ?>,
    childDiscount: <?php echo floatval($villa_info['child_discount']); ?>
};
</script>

<style>
/* Inline CSS - Asıl stillerin CSS dosyasında olması daha iyi */
.vrs-reservation-form-container {
    max-width: 600px;
    margin: 30px auto;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    overflow: hidden;
}

.vrs-form-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 25px;
    text-align: center;
}

.vrs-form-header h3 {
    margin: 0 0 10px 0;
    font-size: 24px;
}

.vrs-villa-name {
    margin: 0;
    opacity: 0.9;
    font-size: 16px;
}

.vrs-reservation-form {
    padding: 30px;
}

.vrs-form-section {
    margin-bottom: 30px;
    padding-bottom: 25px;
    border-bottom: 1px solid #eee;
}

.vrs-form-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.vrs-form-section h4 {
    margin: 0 0 20px 0;
    color: #333;
    font-size: 18px;
    font-weight: 600;
}

.vrs-field {
    margin-bottom: 20px;
}

.vrs-field-half {
    width: calc(50% - 10px);
    display: inline-block;
    vertical-align: top;
}

.vrs-field-half:first-child {
    margin-right: 20px;
}

.vrs-field label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #555;
}

.required {
    color: #e74c3c;
}

.vrs-field input,
.vrs-field textarea,
.vrs-field select {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e1e8ed;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.3s ease;
    box-sizing: border-box;
}

.vrs-field input:focus,
.vrs-field textarea:focus {
    outline: none;
    border-color: #667eea;
}

.vrs-number-input {
    display: flex;
    align-items: center;
    border: 2px solid #e1e8ed;
    border-radius: 8px;
    overflow: hidden;
}

.vrs-number-input input {
    border: none;
    text-align: center;
    flex: 1;
    margin: 0;
}

.vrs-number-decrease,
.vrs-number-increase {
    background: #f8f9fa;
    border: none;
    width: 40px;
    height: 40px;
    cursor: pointer;
    font-size: 18px;
    font-weight: bold;
    color: #666;
    transition: background-color 0.3s ease;
}

.vrs-number-decrease:hover,
.vrs-number-increase:hover {
    background: #e9ecef;
}

.vrs-submit-btn {
    width: 100%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 15px 30px;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.3s ease;
}

.vrs-submit-btn:hover:not(:disabled) {
    transform: translateY(-2px);
}

.vrs-submit-btn:disabled {
    background: #ccc;
    cursor: not-allowed;
}

.vrs-spinner {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #333;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-right: 10px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.vrs-price-breakdown {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
}

.vrs-price-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    padding: 5px 0;
}

.vrs-total-row {
    border-top: 2px solid #dee2e6;
    padding-top: 15px;
    margin-top: 15px;
    font-weight: bold;
    font-size: 18px;
}

.vrs-error-message {
    background: #f8d7da;
    color: #721c24;
    padding: 12px 15px;
    border-radius: 8px;
    border: 1px solid #f5c6cb;
    margin-bottom: 20px;
}

.vrs-success-message {
    background: #d4edda;
    color: #155724;
    padding: 12px 15px;
    border-radius: 8px;
    border: 1px solid #c3e6cb;
    margin-bottom: 20px;
}

@media (max-width: 768px) {
    .vrs-field-half {
        width: 100%;
        margin-right: 0;
        margin-bottom: 15px;
    }
    
    .vrs-reservation-form {
        padding: 20px;
    }
}
</style>