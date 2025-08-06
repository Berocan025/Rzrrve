<?php
/**
 * Admin Dashboard Template
 * Villa Rezervasyon Sistemi - Admin Panel Dashboard
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Villa Rezervasyon Sistemi - Panel', 'villa-reservation-system'); ?></h1>
    
    <!-- İstatistik Kartları -->
    <div class="vrs-dashboard-stats">
        <div class="vrs-stat-card">
            <div class="vrs-stat-icon">
                <span class="dashicons dashicons-calendar-alt"></span>
            </div>
            <div class="vrs-stat-content">
                <h3><?php echo number_format($stats['current_month_reservations']); ?></h3>
                <p><?php _e('Bu Ay Rezervasyon', 'villa-reservation-system'); ?></p>
                <?php if ($stats['last_month_reservations'] > 0): ?>
                    <?php $percentage = (($stats['current_month_reservations'] - $stats['last_month_reservations']) / $stats['last_month_reservations']) * 100; ?>
                    <span class="vrs-stat-change <?php echo $percentage >= 0 ? 'positive' : 'negative'; ?>">
                        <?php echo $percentage >= 0 ? '+' : ''; ?><?php echo number_format($percentage, 1); ?>%
                    </span>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="vrs-stat-card">
            <div class="vrs-stat-icon">
                <span class="dashicons dashicons-money-alt"></span>
            </div>
            <div class="vrs-stat-content">
                <h3><?php echo number_format($stats['current_month_revenue'], 2); ?> ₺</h3>
                <p><?php _e('Bu Ay Gelir', 'villa-reservation-system'); ?></p>
            </div>
        </div>
        
        <div class="vrs-stat-card">
            <div class="vrs-stat-icon">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="vrs-stat-content">
                <h3><?php echo number_format($stats['pending_reservations']); ?></h3>
                <p><?php _e('Bekleyen Rezervasyon', 'villa-reservation-system'); ?></p>
                <?php if ($stats['pending_reservations'] > 0): ?>
                    <a href="<?php echo admin_url('edit.php?post_type=villa&page=vrs-reservations&status=pending'); ?>" class="vrs-stat-link">
                        <?php _e('Detayları Gör', 'villa-reservation-system'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="vrs-stat-card">
            <div class="vrs-stat-icon">
                <span class="dashicons dashicons-admin-home"></span>
            </div>
            <div class="vrs-stat-content">
                <?php $villa_count = wp_count_posts('villa')->publish; ?>
                <h3><?php echo number_format($villa_count); ?></h3>
                <p><?php _e('Aktif Villa', 'villa-reservation-system'); ?></p>
                <a href="<?php echo admin_url('edit.php?post_type=villa'); ?>" class="vrs-stat-link">
                    <?php _e('Villaları Yönet', 'villa-reservation-system'); ?>
                </a>
            </div>
        </div>
    </div>
    
    <div class="vrs-dashboard-content">
        <div class="vrs-dashboard-left">
            <!-- Son Rezervasyonlar -->
            <div class="vrs-dashboard-widget">
                <div class="vrs-widget-header">
                    <h2><?php _e('Son Rezervasyonlar', 'villa-reservation-system'); ?></h2>
                    <a href="<?php echo admin_url('edit.php?post_type=villa&page=vrs-reservations'); ?>" class="button button-secondary">
                        <?php _e('Tümünü Gör', 'villa-reservation-system'); ?>
                    </a>
                </div>
                <div class="vrs-widget-content">
                    <?php if (!empty($recent_reservations)): ?>
                        <table class="vrs-recent-reservations">
                            <thead>
                                <tr>
                                    <th><?php _e('Müşteri', 'villa-reservation-system'); ?></th>
                                    <th><?php _e('Villa', 'villa-reservation-system'); ?></th>
                                    <th><?php _e('Tarih', 'villa-reservation-system'); ?></th>
                                    <th><?php _e('Durum', 'villa-reservation-system'); ?></th>
                                    <th><?php _e('Tutar', 'villa-reservation-system'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_reservations as $reservation): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo esc_html($reservation->first_name . ' ' . $reservation->last_name); ?></strong>
                                            <br>
                                            <small><?php echo esc_html($reservation->email); ?></small>
                                        </td>
                                        <td>
                                            <a href="<?php echo get_edit_post_link($reservation->villa_id); ?>">
                                                <?php echo esc_html($reservation->villa_name); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <?php echo date('d.m.Y', strtotime($reservation->checkin_date)); ?> - 
                                            <?php echo date('d.m.Y', strtotime($reservation->checkout_date)); ?>
                                            <br>
                                            <small><?php echo esc_html($reservation->adults); ?> Yetişkin, <?php echo esc_html($reservation->children); ?> Çocuk</small>
                                        </td>
                                        <td>
                                            <?php
                                            $status_labels = array(
                                                'pending' => __('Bekliyor', 'villa-reservation-system'),
                                                'confirmed' => __('Onaylandı', 'villa-reservation-system'),
                                                'cancelled' => __('İptal', 'villa-reservation-system')
                                            );
                                            $status_class = 'vrs-status-' . $reservation->status;
                                            ?>
                                            <span class="vrs-reservation-status <?php echo $status_class; ?>">
                                                <?php echo $status_labels[$reservation->status]; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong><?php echo number_format($reservation->total_price, 2); ?> ₺</strong>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p><?php _e('Henüz rezervasyon bulunmuyor.', 'villa-reservation-system'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Hızlı İşlemler -->
            <div class="vrs-dashboard-widget">
                <div class="vrs-widget-header">
                    <h2><?php _e('Hızlı İşlemler', 'villa-reservation-system'); ?></h2>
                </div>
                <div class="vrs-widget-content">
                    <div class="vrs-quick-actions">
                        <a href="<?php echo admin_url('post-new.php?post_type=villa'); ?>" class="vrs-quick-action">
                            <span class="dashicons dashicons-plus"></span>
                            <?php _e('Yeni Villa Ekle', 'villa-reservation-system'); ?>
                        </a>
                        
                        <a href="<?php echo admin_url('edit.php?post_type=villa&page=vrs-calendar'); ?>" class="vrs-quick-action">
                            <span class="dashicons dashicons-calendar"></span>
                            <?php _e('Takvim Görünümü', 'villa-reservation-system'); ?>
                        </a>
                        
                        <button type="button" class="vrs-quick-action" id="vrs-quick-block-date">
                            <span class="dashicons dashicons-lock"></span>
                            <?php _e('Tarih Kapat', 'villa-reservation-system'); ?>
                        </button>
                        
                        <button type="button" class="vrs-quick-action" id="vrs-sync-sheets">
                            <span class="dashicons dashicons-update"></span>
                            <?php _e('Sheets Senkronize Et', 'villa-reservation-system'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="vrs-dashboard-right">
            <!-- Popüler Villalar -->
            <div class="vrs-dashboard-widget">
                <div class="vrs-widget-header">
                    <h2><?php _e('En Popüler Villalar (Son 30 Gün)', 'villa-reservation-system'); ?></h2>
                </div>
                <div class="vrs-widget-content">
                    <?php if (!empty($popular_villas)): ?>
                        <ul class="vrs-popular-villas">
                            <?php foreach ($popular_villas as $villa): ?>
                                <li class="vrs-popular-villa">
                                    <div class="vrs-villa-info">
                                        <strong><?php echo esc_html($villa->post_title); ?></strong>
                                        <span class="vrs-reservation-count">
                                            <?php echo number_format($villa->reservation_count); ?> rezervasyon
                                        </span>
                                    </div>
                                    <div class="vrs-villa-progress">
                                        <?php 
                                        $max_reservations = $popular_villas[0]->reservation_count;
                                        $percentage = ($villa->reservation_count / $max_reservations) * 100;
                                        ?>
                                        <div class="vrs-progress-bar">
                                            <div class="vrs-progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p><?php _e('Son 30 günde rezervasyon bulunmuyor.', 'villa-reservation-system'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Sistem Durumu -->
            <div class="vrs-dashboard-widget">
                <div class="vrs-widget-header">
                    <h2><?php _e('Sistem Durumu', 'villa-reservation-system'); ?></h2>
                    <a href="<?php echo admin_url('admin.php?page=villa-reservations-system'); ?>" class="button button-secondary">
                        <?php _e('Detaylı Rapor', 'villa-reservation-system'); ?>
                    </a>
                </div>
                <div class="vrs-widget-content">
                    <div class="vrs-system-status">
                        <?php
                        // WooCommerce durumu
                        $wc_active = class_exists('WooCommerce');
                        ?>
                        <div class="vrs-status-item">
                            <span class="vrs-status-indicator <?php echo $wc_active ? 'active' : 'inactive'; ?>"></span>
                            <span class="vrs-status-label">WooCommerce</span>
                            <span class="vrs-status-value"><?php echo $wc_active ? __('Aktif', 'villa-reservation-system') : __('İnaktif', 'villa-reservation-system'); ?></span>
                        </div>
                        
                        <?php
                        // Google Sheets durumu
                        $credentials_file = VRS_PLUGIN_PATH . 'credentials.json';
                        $sheets_configured = file_exists($credentials_file);
                        ?>
                        <div class="vrs-status-item">
                            <span class="vrs-status-indicator <?php echo $sheets_configured ? 'active' : 'inactive'; ?>"></span>
                            <span class="vrs-status-label">Google Sheets</span>
                            <span class="vrs-status-value"><?php echo $sheets_configured ? __('Yapılandırılmış', 'villa-reservation-system') : __('Yapılandırılmamış', 'villa-reservation-system'); ?></span>
                        </div>
                        
                        <?php
                        // E-posta durumu
                        $email_test = wp_mail_test();
                        ?>
                        <div class="vrs-status-item">
                            <span class="vrs-status-indicator <?php echo $email_test ? 'active' : 'warning'; ?>"></span>
                            <span class="vrs-status-label">E-posta</span>
                            <span class="vrs-status-value"><?php echo $email_test ? __('Çalışıyor', 'villa-reservation-system') : __('Sorunlu', 'villa-reservation-system'); ?></span>
                        </div>
                        
                        <?php
                        // Cron jobs durumu
                        $cron_disabled = defined('DISABLE_WP_CRON') && DISABLE_WP_CRON;
                        ?>
                        <div class="vrs-status-item">
                            <span class="vrs-status-indicator <?php echo !$cron_disabled ? 'active' : 'warning'; ?>"></span>
                            <span class="vrs-status-label">Cron Jobs</span>
                            <span class="vrs-status-value"><?php echo !$cron_disabled ? __('Aktif', 'villa-reservation-system') : __('Devre Dışı', 'villa-reservation-system'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hızlı Tarih Kapatma Modal -->
<div id="vrs-quick-block-modal" class="vrs-modal" style="display: none;">
    <div class="vrs-modal-content">
        <div class="vrs-modal-header">
            <h3><?php _e('Tarih Kapat', 'villa-reservation-system'); ?></h3>
            <button type="button" class="vrs-modal-close">&times;</button>
        </div>
        <div class="vrs-modal-body">
            <form id="vrs-quick-block-form">
                <div class="vrs-form-group">
                    <label for="vrs-block-villa"><?php _e('Villa Seçin', 'villa-reservation-system'); ?></label>
                    <select id="vrs-block-villa" name="villa_id" required>
                        <option value=""><?php _e('Villa seçin...', 'villa-reservation-system'); ?></option>
                        <?php
                        $villas = get_posts(array(
                            'post_type' => 'villa',
                            'posts_per_page' => -1,
                            'post_status' => 'publish'
                        ));
                        foreach ($villas as $villa):
                        ?>
                            <option value="<?php echo $villa->ID; ?>"><?php echo esc_html($villa->post_title); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="vrs-form-group">
                    <label for="vrs-block-date"><?php _e('Tarih', 'villa-reservation-system'); ?></label>
                    <input type="date" id="vrs-block-date" name="date" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="vrs-form-group">
                    <label for="vrs-block-reason"><?php _e('Sebep (İsteğe Bağlı)', 'villa-reservation-system'); ?></label>
                    <input type="text" id="vrs-block-reason" name="reason" placeholder="<?php _e('Bakım, özel rezervasyon vb.', 'villa-reservation-system'); ?>">
                </div>
                
                <div class="vrs-form-actions">
                    <button type="submit" class="button button-primary"><?php _e('Tarihi Kapat', 'villa-reservation-system'); ?></button>
                    <button type="button" class="button button-secondary vrs-modal-close"><?php _e('İptal', 'villa-reservation-system'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
function wp_mail_test() {
    // E-posta test fonksiyonu
    return true; // Gerçek implementasyon gerekli
}
?>