<?php
/**
 * My Reservations Template
 * 
 * @package VillaReservationSystem
 */

// Ensure this file is being included by WordPress
if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Check if user is logged in
if (!is_user_logged_in()) {
    ?>
    <div class="vrs-user-reservations">
        <div class="vrs-admin-header">
            <h1><?php _e('Rezervasyonlarım', 'villa-reservation-system'); ?></h1>
            <p><?php _e('Rezervasyonlarınızı görmek için giriş yapmalısınız.', 'villa-reservation-system'); ?></p>
        </div>
        
        <div class="vrs-message error">
            <p><?php _e('Bu sayfayı görüntüleyebilmek için giriş yapmalısınız.', 'villa-reservation-system'); ?> 
            <a href="<?php echo wp_login_url(get_permalink()); ?>"><?php _e('Giriş Yap', 'villa-reservation-system'); ?></a></p>
        </div>
    </div>
    <?php
    get_footer();
    return;
}

$current_user = wp_get_current_user();
$reservations = VRS_Reservations::get_user_reservations($current_user->ID);
?>

<div class="vrs-user-reservations">
    <div class="vrs-admin-header">
        <h1><?php _e('Rezervasyonlarım', 'villa-reservation-system'); ?></h1>
        <p><?php printf(__('Merhaba %s, rezervasyonlarınızı aşağıda görebilirsiniz.', 'villa-reservation-system'), $current_user->display_name); ?></p>
    </div>

    <?php if (empty($reservations)) : ?>
        <div class="vrs-empty-state">
            <h3><?php _e('Henüz rezervasyonunuz bulunmuyor', 'villa-reservation-system'); ?></h3>
            <p><?php _e('Rezervasyon yapmak için villalarımızı inceleyebilirsiniz.', 'villa-reservation-system'); ?></p>
            <a href="<?php echo home_url('/villa/'); ?>" class="vrs-btn vrs-btn-primary">
                <?php _e('Villaları İncele', 'villa-reservation-system'); ?>
            </a>
        </div>
    <?php else : ?>
        <div class="vrs-reservations-grid">
            <?php foreach ($reservations as $reservation) : 
                $villa = get_post($reservation->villa_id);
                $guest_info = $reservation->guest_name ? $reservation : null;
                $status_class = 'vrs-status-' . $reservation->status;
                
                // Status labels
                $status_labels = array(
                    'pending' => __('Beklemede', 'villa-reservation-system'),
                    'confirmed' => __('Onaylandı', 'villa-reservation-system'),
                    'cancelled' => __('İptal Edildi', 'villa-reservation-system'),
                    'completed' => __('Tamamlandı', 'villa-reservation-system')
                );
                
                $status_label = isset($status_labels[$reservation->status]) ? $status_labels[$reservation->status] : $reservation->status;
                
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
            ?>
            <div class="vrs-reservation-card">
                <div class="vrs-reservation-header">
                    <h3 class="vrs-reservation-title">
                        <?php echo $villa ? esc_html($villa->post_title) : __('Villa Bulunamadı', 'villa-reservation-system'); ?>
                    </h3>
                    <span class="vrs-reservation-status <?php echo esc_attr($status_class); ?>">
                        <?php echo esc_html($status_label); ?>
                    </span>
                </div>
                
                <div class="vrs-reservation-details">
                    <div class="vrs-detail-item">
                        <span class="vrs-detail-label"><?php _e('Rezervasyon No', 'villa-reservation-system'); ?></span>
                        <span class="vrs-detail-value">#<?php echo esc_html($reservation->id); ?></span>
                    </div>
                    
                    <div class="vrs-detail-item">
                        <span class="vrs-detail-label"><?php _e('Giriş Tarihi', 'villa-reservation-system'); ?></span>
                        <span class="vrs-detail-value"><?php echo date_i18n('d F Y', strtotime($reservation->checkin_date)); ?></span>
                    </div>
                    
                    <div class="vrs-detail-item">
                        <span class="vrs-detail-label"><?php _e('Çıkış Tarihi', 'villa-reservation-system'); ?></span>
                        <span class="vrs-detail-value"><?php echo date_i18n('d F Y', strtotime($reservation->checkout_date)); ?></span>
                    </div>
                    
                    <div class="vrs-detail-item">
                        <span class="vrs-detail-label"><?php _e('Süre', 'villa-reservation-system'); ?></span>
                        <span class="vrs-detail-value"><?php printf(_n('%d gece', '%d gece', $days, 'villa-reservation-system'), $days); ?></span>
                    </div>
                    
                    <div class="vrs-detail-item">
                        <span class="vrs-detail-label"><?php _e('Misafir Sayısı', 'villa-reservation-system'); ?></span>
                        <span class="vrs-detail-value">
                            <?php printf(__('%d Yetişkin', 'villa-reservation-system'), $reservation->adults); ?>
                            <?php if ($reservation->children > 0) : ?>
                                <?php printf(__(', %d Çocuk', 'villa-reservation-system'), $reservation->children); ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    
                    <div class="vrs-detail-item">
                        <span class="vrs-detail-label"><?php _e('Toplam Tutar', 'villa-reservation-system'); ?></span>
                        <span class="vrs-detail-value"><?php echo number_format($reservation->total_price, 2); ?> ₺</span>
                    </div>
                    
                    <div class="vrs-detail-item">
                        <span class="vrs-detail-label"><?php _e('Rezervasyon Tarihi', 'villa-reservation-system'); ?></span>
                        <span class="vrs-detail-value"><?php echo date_i18n('d F Y H:i', strtotime($reservation->created_at)); ?></span>
                    </div>
                    
                    <?php if ($guest_info && $guest_info->guest_name) : ?>
                    <div class="vrs-detail-item">
                        <span class="vrs-detail-label"><?php _e('İletişim Kişisi', 'villa-reservation-system'); ?></span>
                        <span class="vrs-detail-value"><?php echo esc_html($guest_info->guest_name); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="vrs-reservation-actions">
                    <a href="<?php echo home_url("/rezervasyon/{$reservation->id}/"); ?>" class="vrs-btn vrs-btn-primary">
                        <?php _e('Detayları Görüntüle', 'villa-reservation-system'); ?>
                    </a>
                    
                    <?php if ($reservation->status === 'confirmed' && $villa) : ?>
                        <a href="<?php echo get_permalink($villa->ID); ?>" class="vrs-btn vrs-btn-secondary">
                            <?php _e('Villayı Görüntüle', 'villa-reservation-system'); ?>
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($can_cancel) : ?>
                        <button type="button" class="vrs-btn vrs-btn-danger vrs-cancel-reservation" 
                                data-reservation-id="<?php echo esc_attr($reservation->id); ?>"
                                onclick="if(confirm('<?php esc_attr_e('Rezervasyonu iptal etmek istediğinizden emin misiniz?', 'villa-reservation-system'); ?>')) { cancelReservation(<?php echo $reservation->id; ?>); }">
                            <?php _e('İptal Et', 'villa-reservation-system'); ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
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

<?php get_footer(); ?>