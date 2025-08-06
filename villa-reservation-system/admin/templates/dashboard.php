<?php
/**
 * Admin Dashboard Template
 *
 * @package VillaReservationSystem
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Villa Rezervasyon Sistemi - Panel', 'villa-reservation-system'); ?></h1>
    
    <div class="vrs-dashboard">
        <!-- Statistics Cards -->
        <div class="vrs-stats-grid">
            <div class="vrs-stat-card">
                <div class="vrs-stat-icon">
                    <span class="dashicons dashicons-calendar-alt"></span>
                </div>
                <div class="vrs-stat-content">
                    <h3><?php echo number_format($total_reservations); ?></h3>
                    <p><?php _e('Toplam Rezervasyon', 'villa-reservation-system'); ?></p>
                </div>
            </div>
            
            <div class="vrs-stat-card pending">
                <div class="vrs-stat-icon">
                    <span class="dashicons dashicons-clock"></span>
                </div>
                <div class="vrs-stat-content">
                    <h3><?php echo number_format($pending_reservations); ?></h3>
                    <p><?php _e('Bekleyen Rezervasyon', 'villa-reservation-system'); ?></p>
                </div>
            </div>
            
            <div class="vrs-stat-card confirmed">
                <div class="vrs-stat-icon">
                    <span class="dashicons dashicons-yes-alt"></span>
                </div>
                <div class="vrs-stat-content">
                    <h3><?php echo number_format($confirmed_reservations); ?></h3>
                    <p><?php _e('Onaylanmış Rezervasyon', 'villa-reservation-system'); ?></p>
                </div>
            </div>
            
            <div class="vrs-stat-card">
                <div class="vrs-stat-icon">
                    <span class="dashicons dashicons-admin-home"></span>
                </div>
                <div class="vrs-stat-content">
                    <h3><?php echo number_format($total_villas); ?></h3>
                    <p><?php _e('Aktif Villa', 'villa-reservation-system'); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="vrs-quick-actions">
            <h2><?php _e('Hızlı İşlemler', 'villa-reservation-system'); ?></h2>
            <div class="vrs-action-buttons">
                <a href="<?php echo admin_url('post-new.php?post_type=villa'); ?>" class="button button-primary">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php _e('Yeni Villa Ekle', 'villa-reservation-system'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=vrs-reservations&status=pending'); ?>" class="button button-secondary">
                    <span class="dashicons dashicons-clock"></span>
                    <?php _e('Bekleyen Rezervasyonlar', 'villa-reservation-system'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=vrs-calendar'); ?>" class="button button-secondary">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <?php _e('Takvim Görünümü', 'villa-reservation-system'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=vrs-reports'); ?>" class="button button-secondary">
                    <span class="dashicons dashicons-chart-bar"></span>
                    <?php _e('Raporlar', 'villa-reservation-system'); ?>
                </a>
            </div>
        </div>
        
        <!-- Recent Reservations -->
        <div class="vrs-recent-reservations">
            <h2><?php _e('Son Rezervasyonlar', 'villa-reservation-system'); ?></h2>
            
            <?php if (!empty($recent_reservations)): ?>
                <div class="vrs-table-container">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Rezervasyon ID', 'villa-reservation-system'); ?></th>
                                <th><?php _e('Villa', 'villa-reservation-system'); ?></th>
                                <th><?php _e('Check-in', 'villa-reservation-system'); ?></th>
                                <th><?php _e('Check-out', 'villa-reservation-system'); ?></th>
                                <th><?php _e('Misafir', 'villa-reservation-system'); ?></th>
                                <th><?php _e('Durum', 'villa-reservation-system'); ?></th>
                                <th><?php _e('Tutar', 'villa-reservation-system'); ?></th>
                                <th><?php _e('Tarih', 'villa-reservation-system'); ?></th>
                                <th><?php _e('İşlemler', 'villa-reservation-system'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_reservations as $reservation): ?>
                                <tr>
                                    <td><strong>#<?php echo $reservation->id; ?></strong></td>
                                    <td>
                                        <a href="<?php echo get_edit_post_link($reservation->villa_id); ?>">
                                            <?php echo esc_html($reservation->villa_name); ?>
                                        </a>
                                    </td>
                                    <td><?php echo date_i18n(get_option('date_format'), strtotime($reservation->checkin_date)); ?></td>
                                    <td><?php echo date_i18n(get_option('date_format'), strtotime($reservation->checkout_date)); ?></td>
                                    <td>
                                        <?php echo $reservation->adults; ?> <?php _e('Yetişkin', 'villa-reservation-system'); ?>
                                        <?php if ($reservation->children > 0): ?>
                                            + <?php echo $reservation->children; ?> <?php _e('Çocuk', 'villa-reservation-system'); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="vrs-status vrs-status-<?php echo esc_attr($reservation->status); ?>">
                                            <?php
                                            switch ($reservation->status) {
                                                case 'pending':
                                                    _e('Bekliyor', 'villa-reservation-system');
                                                    break;
                                                case 'confirmed':
                                                    _e('Onaylandı', 'villa-reservation-system');
                                                    break;
                                                case 'cancelled':
                                                    _e('İptal', 'villa-reservation-system');
                                                    break;
                                                case 'completed':
                                                    _e('Tamamlandı', 'villa-reservation-system');
                                                    break;
                                                default:
                                                    echo esc_html($reservation->status);
                                            }
                                            ?>
                                        </span>
                                    </td>
                                    <td><strong><?php echo number_format($reservation->total_price, 2); ?> TL</strong></td>
                                    <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($reservation->created_at)); ?></td>
                                    <td>
                                        <a href="<?php echo admin_url('admin.php?page=vrs-reservations&action=view&id=' . $reservation->id); ?>" 
                                           class="button button-small">
                                            <?php _e('Görüntüle', 'villa-reservation-system'); ?>
                                        </a>
                                        
                                        <?php if ($reservation->status === 'pending'): ?>
                                            <button type="button" 
                                                    class="button button-small button-primary vrs-confirm-reservation" 
                                                    data-reservation-id="<?php echo $reservation->id; ?>">
                                                <?php _e('Onayla', 'villa-reservation-system'); ?>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="vrs-view-all">
                    <a href="<?php echo admin_url('admin.php?page=vrs-reservations'); ?>" class="button button-secondary">
                        <?php _e('Tüm Rezervasyonları Görüntüle', 'villa-reservation-system'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="vrs-no-data">
                    <p><?php _e('Henüz rezervasyon bulunmuyor.', 'villa-reservation-system'); ?></p>
                    <a href="<?php echo admin_url('post-new.php?post_type=villa'); ?>" class="button button-primary">
                        <?php _e('İlk Villanızı Ekleyin', 'villa-reservation-system'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- System Status -->
        <div class="vrs-system-status">
            <h2><?php _e('Sistem Durumu', 'villa-reservation-system'); ?></h2>
            
            <div class="vrs-status-grid">
                <div class="vrs-status-item">
                    <span class="vrs-status-label"><?php _e('WooCommerce:', 'villa-reservation-system'); ?></span>
                    <span class="vrs-status-value <?php echo class_exists('WooCommerce') ? 'active' : 'inactive'; ?>">
                        <?php echo class_exists('WooCommerce') ? __('Aktif', 'villa-reservation-system') : __('Pasif', 'villa-reservation-system'); ?>
                    </span>
                </div>
                
                <div class="vrs-status-item">
                    <span class="vrs-status-label"><?php _e('Google Sheets API:', 'villa-reservation-system'); ?></span>
                    <span class="vrs-status-value <?php echo get_option('vrs_google_credentials') ? 'active' : 'inactive'; ?>">
                        <?php echo get_option('vrs_google_credentials') ? __('Yapılandırılmış', 'villa-reservation-system') : __('Yapılandırılmamış', 'villa-reservation-system'); ?>
                    </span>
                </div>
                
                <div class="vrs-status-item">
                    <span class="vrs-status-label"><?php _e('Cron Jobs:', 'villa-reservation-system'); ?></span>
                    <span class="vrs-status-value <?php echo wp_next_scheduled('vrs_sync_google_sheets') ? 'active' : 'inactive'; ?>">
                        <?php echo wp_next_scheduled('vrs_sync_google_sheets') ? __('Çalışıyor', 'villa-reservation-system') : __('Çalışmıyor', 'villa-reservation-system'); ?>
                    </span>
                </div>
                
                <div class="vrs-status-item">
                    <span class="vrs-status-label"><?php _e('E-posta Sistemi:', 'villa-reservation-system'); ?></span>
                    <?php
                    $settings = get_option('vrs_settings', array());
                    $email_enabled = isset($settings['enable_email_notifications']) && $settings['enable_email_notifications'];
                    ?>
                    <span class="vrs-status-value <?php echo $email_enabled ? 'active' : 'inactive'; ?>">
                        <?php echo $email_enabled ? __('Aktif', 'villa-reservation-system') : __('Pasif', 'villa-reservation-system'); ?>
                    </span>
                </div>
            </div>
            
            <div class="vrs-status-actions">
                <a href="<?php echo admin_url('admin.php?page=vrs-settings'); ?>" class="button button-secondary">
                    <?php _e('Sistem Ayarları', 'villa-reservation-system'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=villa-reservation-system&page=google-sheets-settings'); ?>" class="button button-secondary">
                    <?php _e('Google Sheets Ayarları', 'villa-reservation-system'); ?>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="vrs-confirm-modal" class="vrs-modal" style="display: none;">
    <div class="vrs-modal-content">
        <div class="vrs-modal-header">
            <h3><?php _e('Rezervasyon Onayı', 'villa-reservation-system'); ?></h3>
            <span class="vrs-modal-close">&times;</span>
        </div>
        <div class="vrs-modal-body">
            <p><?php _e('Bu rezervasyonu onaylamak istediğinizden emin misiniz?', 'villa-reservation-system'); ?></p>
        </div>
        <div class="vrs-modal-footer">
            <button type="button" class="button button-secondary vrs-modal-cancel">
                <?php _e('İptal', 'villa-reservation-system'); ?>
            </button>
            <button type="button" class="button button-primary vrs-modal-confirm">
                <?php _e('Onayla', 'villa-reservation-system'); ?>
            </button>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Confirm reservation
    $('.vrs-confirm-reservation').on('click', function() {
        var reservationId = $(this).data('reservation-id');
        
        $('#vrs-confirm-modal').show();
        
        $('.vrs-modal-confirm').off('click').on('click', function() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vrs_admin_action',
                    admin_action: 'confirm_reservation',
                    reservation_id: reservationId,
                    nonce: vrsAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || vrsAdmin.messages.error);
                    }
                },
                error: function() {
                    alert(vrsAdmin.messages.error);
                }
            });
            
            $('#vrs-confirm-modal').hide();
        });
    });
    
    // Close modal
    $('.vrs-modal-close, .vrs-modal-cancel').on('click', function() {
        $('.vrs-modal').hide();
    });
    
    // Close modal when clicking outside
    $(window).on('click', function(e) {
        if ($(e.target).hasClass('vrs-modal')) {
            $('.vrs-modal').hide();
        }
    });
});
</script>