<?php
/**
 * Reservation Details Template
 * 
 * @package VillaReservationSystem
 */

// Ensure this file is being included by WordPress
if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Get reservation ID from URL
$reservation_id = get_query_var('reservation_id');
if (!$reservation_id) {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $path_parts = explode('/', trim($path, '/'));
    $reservation_id = end($path_parts);
}

if (!$reservation_id || !is_numeric($reservation_id)) {
    ?>
    <div class="vrs-user-reservations">
        <div class="vrs-message error">
            <h3><?php _e('Rezervasyon Bulunamadı', 'villa-reservation-system'); ?></h3>
            <p><?php _e('Geçersiz rezervasyon numarası.', 'villa-reservation-system'); ?></p>
            <a href="<?php echo home_url('/rezervasyonlarim/'); ?>" class="vrs-btn vrs-btn-primary">
                <?php _e('Rezervasyonlarıma Dön', 'villa-reservation-system'); ?>
            </a>
        </div>
    </div>
    <?php
    get_footer();
    return;
}

$reservation = VRS_Reservations::get_reservation($reservation_id);

// Check if reservation exists and belongs to current user (if logged in)
if (!$reservation || (is_user_logged_in() && $reservation->user_id != get_current_user_id())) {
    ?>
    <div class="vrs-user-reservations">
        <div class="vrs-message error">
            <h3><?php _e('Rezervasyon Bulunamadı', 'villa-reservation-system'); ?></h3>
            <p><?php _e('Bu rezervasyon bulunamadı veya bu rezervasyona erişim yetkiniz bulunmuyor.', 'villa-reservation-system'); ?></p>
            <a href="<?php echo home_url('/rezervasyonlarim/'); ?>" class="vrs-btn vrs-btn-primary">
                <?php _e('Rezervasyonlarıma Dön', 'villa-reservation-system'); ?>
            </a>
        </div>
    </div>
    <?php
    get_footer();
    return;
}

$villa = get_post($reservation->villa_id);
$guest_info = $reservation->guest_name ? $reservation : null;

// Status labels
$status_labels = array(
    'pending' => __('Beklemede', 'villa-reservation-system'),
    'confirmed' => __('Onaylandı', 'villa-reservation-system'),
    'cancelled' => __('İptal Edildi', 'villa-reservation-system'),
    'completed' => __('Tamamlandı', 'villa-reservation-system')
);

$status_label = isset($status_labels[$reservation->status]) ? $status_labels[$reservation->status] : $reservation->status;
$status_class = 'vrs-status-' . $reservation->status;

// Calculate days
$checkin_date = new DateTime($reservation->checkin_date);
$checkout_date = new DateTime($reservation->checkout_date);
$days = $checkin_date->diff($checkout_date)->days;

// Check if cancellation is allowed (24 hours before check-in)
$can_cancel = false;
if ($reservation->status === 'confirmed' || $reservation->status === 'pending') {
    $now = new DateTime();
    $checkin_minus_24h = clone $checkin_date;
    $checkin_minus_24h->sub(new DateInterval('P1D'));
    $can_cancel = $now < $checkin_minus_24h;
}

// Villa information
$villa_info = $villa ? VRS_Villa_System::get_villa_info($villa->ID) : null;
?>

<div class="vrs-user-reservations">
    <div class="vrs-admin-header">
        <h1><?php printf(__('Rezervasyon #%d', 'villa-reservation-system'), $reservation->id); ?></h1>
        <p>
            <a href="<?php echo home_url('/rezervasyonlarim/'); ?>">&larr; <?php _e('Rezervasyonlarıma Dön', 'villa-reservation-system'); ?></a>
        </p>
    </div>

    <div class="vrs-reservation-card">
        <div class="vrs-reservation-header">
            <h2 class="vrs-reservation-title">
                <?php echo $villa ? esc_html($villa->post_title) : __('Villa Bulunamadı', 'villa-reservation-system'); ?>
            </h2>
            <span class="vrs-reservation-status <?php echo esc_attr($status_class); ?>">
                <?php echo esc_html($status_label); ?>
            </span>
        </div>

        <?php if ($villa && has_post_thumbnail($villa->ID)) : ?>
        <div class="vrs-villa-image">
            <?php echo get_the_post_thumbnail($villa->ID, 'large', array('style' => 'width: 100%; height: 300px; object-fit: cover; border-radius: 8px;')); ?>
        </div>
        <?php endif; ?>

        <div class="vrs-reservation-sections">
            <!-- Reservation Details -->
            <div class="vrs-section">
                <h3><?php _e('Rezervasyon Detayları', 'villa-reservation-system'); ?></h3>
                <div class="vrs-reservation-details">
                    <div class="vrs-detail-item">
                        <span class="vrs-detail-label"><?php _e('Rezervasyon No', 'villa-reservation-system'); ?></span>
                        <span class="vrs-detail-value">#<?php echo esc_html($reservation->id); ?></span>
                    </div>
                    
                    <div class="vrs-detail-item">
                        <span class="vrs-detail-label"><?php _e('Durum', 'villa-reservation-system'); ?></span>
                        <span class="vrs-detail-value">
                            <span class="vrs-status-badge <?php echo esc_attr($status_class); ?>">
                                <?php echo esc_html($status_label); ?>
                            </span>
                        </span>
                    </div>
                    
                    <div class="vrs-detail-item">
                        <span class="vrs-detail-label"><?php _e('Rezervasyon Tarihi', 'villa-reservation-system'); ?></span>
                        <span class="vrs-detail-value"><?php echo date_i18n('d F Y H:i', strtotime($reservation->created_at)); ?></span>
                    </div>
                </div>
            </div>

            <!-- Stay Details -->
            <div class="vrs-section">
                <h3><?php _e('Konaklama Bilgileri', 'villa-reservation-system'); ?></h3>
                <div class="vrs-reservation-details">
                    <div class="vrs-detail-item">
                        <span class="vrs-detail-label"><?php _e('Giriş Tarihi', 'villa-reservation-system'); ?></span>
                        <span class="vrs-detail-value">
                            <?php echo date_i18n('d F Y', strtotime($reservation->checkin_date)); ?>
                            <?php if ($villa_info && $villa_info['checkin_time']) : ?>
                                <br><small><?php printf(__('Giriş Saati: %s', 'villa-reservation-system'), $villa_info['checkin_time']); ?></small>
                            <?php endif; ?>
                        </span>
                    </div>
                    
                    <div class="vrs-detail-item">
                        <span class="vrs-detail-label"><?php _e('Çıkış Tarihi', 'villa-reservation-system'); ?></span>
                        <span class="vrs-detail-value">
                            <?php echo date_i18n('d F Y', strtotime($reservation->checkout_date)); ?>
                            <?php if ($villa_info && $villa_info['checkout_time']) : ?>
                                <br><small><?php printf(__('Çıkış Saati: %s', 'villa-reservation-system'), $villa_info['checkout_time']); ?></small>
                            <?php endif; ?>
                        </span>
                    </div>
                    
                    <div class="vrs-detail-item">
                        <span class="vrs-detail-label"><?php _e('Konaklama Süresi', 'villa-reservation-system'); ?></span>
                        <span class="vrs-detail-value"><?php printf(_n('%d gece', '%d gece', $days, 'villa-reservation-system'), $days); ?></span>
                    </div>
                </div>
            </div>

            <!-- Guest Information -->
            <div class="vrs-section">
                <h3><?php _e('Misafir Bilgileri', 'villa-reservation-system'); ?></h3>
                <div class="vrs-reservation-details">
                    <div class="vrs-detail-item">
                        <span class="vrs-detail-label"><?php _e('Yetişkin Sayısı', 'villa-reservation-system'); ?></span>
                        <span class="vrs-detail-value"><?php echo esc_html($reservation->adults); ?></span>
                    </div>
                    
                    <div class="vrs-detail-item">
                        <span class="vrs-detail-label"><?php _e('Çocuk Sayısı', 'villa-reservation-system'); ?></span>
                        <span class="vrs-detail-value"><?php echo esc_html($reservation->children); ?></span>
                    </div>
                    
                    <div class="vrs-detail-item">
                        <span class="vrs-detail-label"><?php _e('Toplam Misafir', 'villa-reservation-system'); ?></span>
                        <span class="vrs-detail-value"><?php echo esc_html($reservation->adults + $reservation->children); ?></span>
                    </div>
                    
                    <?php if ($guest_info) : ?>
                    <div class="vrs-detail-item">
                        <span class="vrs-detail-label"><?php _e('İletişim Kişisi', 'villa-reservation-system'); ?></span>
                        <span class="vrs-detail-value"><?php echo esc_html($guest_info->guest_name); ?></span>
                    </div>
                    
                    <?php if ($guest_info->guest_email) : ?>
                    <div class="vrs-detail-item">
                        <span class="vrs-detail-label"><?php _e('E-posta', 'villa-reservation-system'); ?></span>
                        <span class="vrs-detail-value"><?php echo esc_html($guest_info->guest_email); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($guest_info->guest_phone) : ?>
                    <div class="vrs-detail-item">
                        <span class="vrs-detail-label"><?php _e('Telefon', 'villa-reservation-system'); ?></span>
                        <span class="vrs-detail-value"><?php echo esc_html($guest_info->guest_phone); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($guest_info->special_requests) : ?>
                    <div class="vrs-detail-item">
                        <span class="vrs-detail-label"><?php _e('Özel İstekler', 'villa-reservation-system'); ?></span>
                        <span class="vrs-detail-value"><?php echo nl2br(esc_html($guest_info->special_requests)); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Price Details -->
            <div class="vrs-section">
                <h3><?php _e('Fiyat Detayları', 'villa-reservation-system'); ?></h3>
                <div class="vrs-price-summary">
                    <div class="vrs-price-breakdown">
                        <div class="vrs-price-item">
                            <span><?php printf(_n('%d gece', '%d gece', $days, 'villa-reservation-system'), $days); ?></span>
                            <span><?php echo number_format($reservation->total_price, 2); ?> ₺</span>
                        </div>
                        
                        <?php if ($reservation->adults > 0) : ?>
                        <div class="vrs-price-item">
                            <span><?php printf(__('%d Yetişkin', 'villa-reservation-system'), $reservation->adults); ?></span>
                            <span><?php _e('Dahil', 'villa-reservation-system'); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($reservation->children > 0) : ?>
                        <div class="vrs-price-item">
                            <span><?php printf(__('%d Çocuk', 'villa-reservation-system'), $reservation->children); ?></span>
                            <span><?php _e('Dahil', 'villa-reservation-system'); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="vrs-price-total">
                        <span><?php _e('Toplam Tutar', 'villa-reservation-system'); ?></span>
                        <span><?php echo number_format($reservation->total_price, 2); ?> ₺</span>
                    </div>
                </div>
            </div>

            <!-- Villa Information -->
            <?php if ($villa) : ?>
            <div class="vrs-section">
                <h3><?php _e('Villa Bilgileri', 'villa-reservation-system'); ?></h3>
                <div class="vrs-reservation-details">
                    <div class="vrs-detail-item">
                        <span class="vrs-detail-label"><?php _e('Villa Adı', 'villa-reservation-system'); ?></span>
                        <span class="vrs-detail-value">
                            <a href="<?php echo get_permalink($villa->ID); ?>" target="_blank">
                                <?php echo esc_html($villa->post_title); ?>
                            </a>
                        </span>
                    </div>
                    
                    <?php if ($villa_info) : ?>
                    <?php if ($villa_info['max_capacity']) : ?>
                    <div class="vrs-detail-item">
                        <span class="vrs-detail-label"><?php _e('Maksimum Kapasite', 'villa-reservation-system'); ?></span>
                        <span class="vrs-detail-value"><?php echo esc_html($villa_info['max_capacity']); ?> <?php _e('kişi', 'villa-reservation-system'); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($villa_info['minimum_stay']) : ?>
                    <div class="vrs-detail-item">
                        <span class="vrs-detail-label"><?php _e('Minimum Konaklama', 'villa-reservation-system'); ?></span>
                        <span class="vrs-detail-value"><?php echo esc_html($villa_info['minimum_stay']); ?> <?php _e('gece', 'villa-reservation-system'); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
                
                <?php if ($villa->post_excerpt) : ?>
                <div class="vrs-villa-excerpt">
                    <h4><?php _e('Villa Hakkında', 'villa-reservation-system'); ?></h4>
                    <p><?php echo esc_html($villa->post_excerpt); ?></p>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Actions -->
        <div class="vrs-reservation-actions">
            <a href="<?php echo home_url('/rezervasyonlarim/'); ?>" class="vrs-btn vrs-btn-secondary">
                <?php _e('Rezervasyonlarıma Dön', 'villa-reservation-system'); ?>
            </a>
            
            <?php if ($reservation->status === 'confirmed' && $villa) : ?>
                <a href="<?php echo get_permalink($villa->ID); ?>" class="vrs-btn vrs-btn-primary" target="_blank">
                    <?php _e('Villayı Görüntüle', 'villa-reservation-system'); ?>
                </a>
            <?php endif; ?>
            
            <?php if ($can_cancel) : ?>
                <button type="button" class="vrs-btn vrs-btn-danger vrs-cancel-reservation" 
                        data-reservation-id="<?php echo esc_attr($reservation->id); ?>"
                        onclick="if(confirm('<?php esc_attr_e('Rezervasyonu iptal etmek istediğinizden emin misiniz? Bu işlem geri alınamaz.', 'villa-reservation-system'); ?>')) { cancelReservation(<?php echo $reservation->id; ?>); }">
                    <?php _e('Rezervasyonu İptal Et', 'villa-reservation-system'); ?>
                </button>
            <?php endif; ?>
            
            <button type="button" class="vrs-btn vrs-btn-secondary" onclick="window.print();">
                <?php _e('Yazdır', 'villa-reservation-system'); ?>
            </button>
        </div>
    </div>
</div>

<script>
function cancelReservation(reservationId) {
    // Show loading
    const button = document.querySelector(`[data-reservation-id="${reservationId}"]`);
    const originalText = button.textContent;
    button.textContent = '<?php esc_js_e('İptal ediliyor...', 'villa-reservation-system'); ?>';
    button.disabled = true;
    
    // AJAX call to cancel reservation
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'vrs_cancel_reservation',
            reservation_id: reservationId,
            nonce: '<?php echo wp_create_nonce('vrs_nonce'); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload page to show updated status
            location.reload();
        } else {
            alert(data.data.message || '<?php esc_js_e('Rezervasyon iptal edilirken bir hata oluştu.', 'villa-reservation-system'); ?>');
            button.textContent = originalText;
            button.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('<?php esc_js_e('Bir hata oluştu. Lütfen tekrar deneyin.', 'villa-reservation-system'); ?>');
        button.textContent = originalText;
        button.disabled = false;
    });
}
</script>

<style>
.vrs-reservation-sections {
    display: grid;
    gap: 30px;
    margin: 30px 0;
}

.vrs-section {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
}

.vrs-section h3 {
    margin: 0 0 20px 0;
    color: #2c3e50;
    font-size: 18px;
    font-weight: 600;
    border-bottom: 2px solid #3498db;
    padding-bottom: 10px;
}

.vrs-section h4 {
    margin: 15px 0 10px 0;
    color: #34495e;
    font-size: 14px;
    font-weight: 600;
}

.vrs-villa-image {
    margin: 20px 0;
}

.vrs-villa-excerpt p {
    margin: 0;
    line-height: 1.6;
    color: #555;
}

@media print {
    .vrs-reservation-actions,
    .vrs-btn {
        display: none !important;
    }
    
    .vrs-user-reservations {
        margin: 0;
        padding: 20px;
    }
    
    .vrs-section {
        background: none;
        border: 1px solid #ddd;
        page-break-inside: avoid;
    }
}
</style>

<?php get_footer(); ?>