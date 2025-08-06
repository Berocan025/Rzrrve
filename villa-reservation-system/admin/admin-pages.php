<?php
/**
 * Villa Reservation System - Admin Pages
 * 
 * @package VillaReservationSystem
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class VRS_Admin_Pages {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_vrs_admin_action', array($this, 'handle_admin_ajax'));
    }
    
    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        // Main menu page
        add_menu_page(
            __('Villa Rezervasyon', 'villa-reservation-system'),
            __('Villa Rezervasyon', 'villa-reservation-system'),
            'manage_options',
            'villa-reservation-system',
            array($this, 'dashboard_page'),
            'dashicons-calendar-alt',
            30
        );
        
        // Dashboard (duplicate main page)
        add_submenu_page(
            'villa-reservation-system',
            __('Dashboard', 'villa-reservation-system'),
            __('Dashboard', 'villa-reservation-system'),
            'manage_options',
            'villa-reservation-system',
            array($this, 'dashboard_page')
        );
        
        // All Reservations
        add_submenu_page(
            'villa-reservation-system',
            __('Tüm Rezervasyonlar', 'villa-reservation-system'),
            __('Rezervasyonlar', 'villa-reservation-system'),
            'manage_options',
            'vrs-reservations',
            array($this, 'all_reservations_page')
        );
        
        // Calendar View
        add_submenu_page(
            'villa-reservation-system',
            __('Takvim Görünümü', 'villa-reservation-system'),
            __('Takvim', 'villa-reservation-system'),
            'manage_options',
            'vrs-calendar',
            array($this, 'calendar_page')
        );
        
        // Settings
        add_submenu_page(
            'villa-reservation-system',
            __('Ayarlar', 'villa-reservation-system'),
            __('Ayarlar', 'villa-reservation-system'),
            'manage_options',
            'vrs-settings',
            array($this, 'settings_page')
        );
        
        // Reports
        add_submenu_page(
            'villa-reservation-system',
            __('Raporlar', 'villa-reservation-system'),
            __('Raporlar', 'villa-reservation-system'),
            'manage_options',
            'vrs-reports',
            array($this, 'reports_page')
        );
        
        // Google Sheets
        add_submenu_page(
            'villa-reservation-system',
            __('Google Sheets', 'villa-reservation-system'),
            __('Google Sheets', 'villa-reservation-system'),
            'manage_options',
            'vrs-google-sheets',
            array($this, 'google_sheets_page')
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'villa-reservation') === false && strpos($hook, 'vrs-') === false) {
            return;
        }
        
        wp_enqueue_style(
            'vrs-admin-style',
            VRS_PLUGIN_URL . 'admin/admin-styles.css',
            array(),
            VRS_VERSION
        );
        
        wp_enqueue_script(
            'vrs-admin-script',
            VRS_PLUGIN_URL . 'admin/admin-scripts.js',
            array('jquery', 'jquery-ui-datepicker'),
            VRS_VERSION,
            true
        );
        
        wp_localize_script('vrs-admin-script', 'vrsAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vrs_admin_nonce'),
            'strings' => array(
                'loading' => __('Yükleniyor...', 'villa-reservation-system'),
                'success' => __('İşlem başarılı!', 'villa-reservation-system'),
                'error' => __('Bir hata oluştu!', 'villa-reservation-system'),
                'confirm_delete' => __('Bu işlemi silmek istediğinizden emin misiniz?', 'villa-reservation-system'),
                'confirm_cancel' => __('Bu rezervasyonu iptal etmek istediğinizden emin misiniz?', 'villa-reservation-system'),
            )
        ));
    }
    
    /**
     * Dashboard page
     */
    public function dashboard_page() {
        global $wpdb;
        
        // Get stats
        $stats = $this->get_dashboard_stats();
        $recent_reservations = $this->get_recent_reservations(5);
        $upcoming_checkins = $this->get_upcoming_checkins(5);
        ?>
        <div class="wrap vrs-admin-page">
            <div class="vrs-admin-header">
                <div>
                    <h1 class="vrs-admin-title"><?php _e('Villa Rezervasyon Sistemi', 'villa-reservation-system'); ?></h1>
                    <p class="vrs-admin-subtitle"><?php _e('Rezervasyon yönetimi ve analiz paneli', 'villa-reservation-system'); ?></p>
                </div>
                <a href="<?php echo admin_url('post-new.php?post_type=villa'); ?>" class="page-title-action">
                    <?php _e('Yeni Villa Ekle', 'villa-reservation-system'); ?>
                </a>
            </div>
            
            <!-- Quick Stats -->
            <div class="vrs-stats-grid">
                <div class="vrs-stat-card">
                    <span class="vrs-stat-number"><?php echo $stats['total_reservations']; ?></span>
                    <span class="vrs-stat-label"><?php _e('Toplam Rezervasyon', 'villa-reservation-system'); ?></span>
                </div>
                <div class="vrs-stat-card">
                    <span class="vrs-stat-number"><?php echo $stats['confirmed_reservations']; ?></span>
                    <span class="vrs-stat-label"><?php _e('Onaylı Rezervasyon', 'villa-reservation-system'); ?></span>
                </div>
                <div class="vrs-stat-card">
                    <span class="vrs-stat-number"><?php echo $stats['pending_reservations']; ?></span>
                    <span class="vrs-stat-label"><?php _e('Bekleyen Rezervasyon', 'villa-reservation-system'); ?></span>
                </div>
                <div class="vrs-stat-card">
                    <span class="vrs-stat-number"><?php echo $stats['total_revenue']; ?> ₺</span>
                    <span class="vrs-stat-label"><?php _e('Toplam Gelir', 'villa-reservation-system'); ?></span>
                </div>
                <div class="vrs-stat-card">
                    <span class="vrs-stat-number"><?php echo $stats['active_villas']; ?></span>
                    <span class="vrs-stat-label"><?php _e('Aktif Villa', 'villa-reservation-system'); ?></span>
                </div>
                <div class="vrs-stat-card">
                    <span class="vrs-stat-number"><?php echo $stats['this_month_bookings']; ?></span>
                    <span class="vrs-stat-label"><?php _e('Bu Ay Rezervasyon', 'villa-reservation-system'); ?></span>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="vrs-quick-actions">
                <a href="<?php echo admin_url('admin.php?page=vrs-reservations&status=pending'); ?>" class="vrs-quick-action">
                    <span class="dashicons dashicons-clock"></span>
                    <?php _e('Bekleyen Rezervasyonlar', 'villa-reservation-system'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=vrs-calendar'); ?>" class="vrs-quick-action">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <?php _e('Takvim Görünümü', 'villa-reservation-system'); ?>
                </a>
                <a href="<?php echo admin_url('edit.php?post_type=villa'); ?>" class="vrs-quick-action">
                    <span class="dashicons dashicons-admin-home"></span>
                    <?php _e('Villa Yönetimi', 'villa-reservation-system'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=vrs-reports'); ?>" class="vrs-quick-action">
                    <span class="dashicons dashicons-chart-area"></span>
                    <?php _e('Raporlar', 'villa-reservation-system'); ?>
                </a>
            </div>
            
            <div class="vrs-admin-cards">
                <!-- Recent Reservations -->
                <div class="vrs-admin-card">
                    <h3>
                        <span class="dashicons dashicons-list-view"></span>
                        <?php _e('Son Rezervasyonlar', 'villa-reservation-system'); ?>
                    </h3>
                    <div class="vrs-card-content">
                        <?php if (!empty($recent_reservations)): ?>
                            <table class="vrs-admin-table">
                                <thead>
                                    <tr>
                                        <th><?php _e('ID', 'villa-reservation-system'); ?></th>
                                        <th><?php _e('Villa', 'villa-reservation-system'); ?></th>
                                        <th><?php _e('Misafir', 'villa-reservation-system'); ?></th>
                                        <th><?php _e('Durum', 'villa-reservation-system'); ?></th>
                                        <th><?php _e('Tutar', 'villa-reservation-system'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_reservations as $reservation): ?>
                                        <tr>
                                            <td>#<?php echo $reservation->id; ?></td>
                                            <td><?php echo get_the_title($reservation->villa_id); ?></td>
                                            <td><?php echo esc_html($reservation->guest_name); ?></td>
                                            <td>
                                                <span class="vrs-status <?php echo $reservation->status; ?>">
                                                    <?php echo $this->get_status_label($reservation->status); ?>
                                                </span>
                                            </td>
                                            <td><?php echo number_format($reservation->total_price, 2); ?> ₺</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p><?php _e('Henüz rezervasyon bulunmuyor.', 'villa-reservation-system'); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="vrs-card-footer">
                        <a href="<?php echo admin_url('admin.php?page=vrs-reservations'); ?>" class="vrs-btn vrs-btn-primary">
                            <?php _e('Tüm Rezervasyonları Görüntüle', 'villa-reservation-system'); ?>
                        </a>
                    </div>
                </div>
                
                <!-- Upcoming Check-ins -->
                <div class="vrs-admin-card">
                    <h3>
                        <span class="dashicons dashicons-calendar"></span>
                        <?php _e('Yaklaşan Giriş Tarihleri', 'villa-reservation-system'); ?>
                    </h3>
                    <div class="vrs-card-content">
                        <?php if (!empty($upcoming_checkins)): ?>
                            <table class="vrs-admin-table">
                                <thead>
                                    <tr>
                                        <th><?php _e('Tarih', 'villa-reservation-system'); ?></th>
                                        <th><?php _e('Villa', 'villa-reservation-system'); ?></th>
                                        <th><?php _e('Misafir', 'villa-reservation-system'); ?></th>
                                        <th><?php _e('Kişi', 'villa-reservation-system'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($upcoming_checkins as $checkin): ?>
                                        <tr>
                                            <td><?php echo date_i18n('d.m.Y', strtotime($checkin->checkin_date)); ?></td>
                                            <td><?php echo get_the_title($checkin->villa_id); ?></td>
                                            <td><?php echo esc_html($checkin->guest_name); ?></td>
                                            <td><?php echo $checkin->adults + $checkin->children; ?> kişi</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p><?php _e('Yaklaşan giriş tarihi bulunmuyor.', 'villa-reservation-system'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * All reservations page
     */
    public function all_reservations_page() {
        global $wpdb;
        
        // Handle actions
        if (isset($_POST['action']) && wp_verify_nonce($_POST['_wpnonce'], 'vrs_reservation_action')) {
            $this->handle_reservation_action();
        }
        
        // Get filters
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $villa_filter = isset($_GET['villa']) ? intval($_GET['villa']) : 0;
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
        
        // Build query
        $where_clauses = array('1=1');
        if ($status_filter) {
            $where_clauses[] = $wpdb->prepare("r.status = %s", $status_filter);
        }
        if ($villa_filter) {
            $where_clauses[] = $wpdb->prepare("r.villa_id = %d", $villa_filter);
        }
        if ($date_from) {
            $where_clauses[] = $wpdb->prepare("r.checkin_date >= %s", $date_from);
        }
        if ($date_to) {
            $where_clauses[] = $wpdb->prepare("r.checkout_date <= %s", $date_to);
        }
        
        $where_sql = implode(' AND ', $where_clauses);
        
        // Get reservations
        $reservations = $wpdb->get_results("
            SELECT r.*, g.guest_name, g.guest_email, g.guest_phone
            FROM {$wpdb->prefix}vrs_reservations r
            LEFT JOIN {$wpdb->prefix}vrs_guest_info g ON r.id = g.reservation_id
            WHERE {$where_sql}
            ORDER BY r.created_at DESC
        ");
        
        // Get villas for filter
        $villas = get_posts(array(
            'post_type' => 'villa',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));
        ?>
        <div class="wrap vrs-admin-page">
            <div class="vrs-admin-header">
                <div>
                    <h1 class="vrs-admin-title"><?php _e('Tüm Rezervasyonlar', 'villa-reservation-system'); ?></h1>
                    <p class="vrs-admin-subtitle"><?php _e('Rezervasyon listesi ve yönetim paneli', 'villa-reservation-system'); ?></p>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="vrs-filter-bar">
                <form method="get" action="">
                    <input type="hidden" name="page" value="vrs-reservations">
                    
                    <div class="vrs-filter-group">
                        <label><?php _e('Durum', 'villa-reservation-system'); ?></label>
                        <select name="status">
                            <option value=""><?php _e('Tüm Durumlar', 'villa-reservation-system'); ?></option>
                            <option value="pending" <?php selected($status_filter, 'pending'); ?>><?php _e('Bekliyor', 'villa-reservation-system'); ?></option>
                            <option value="confirmed" <?php selected($status_filter, 'confirmed'); ?>><?php _e('Onaylandı', 'villa-reservation-system'); ?></option>
                            <option value="cancelled" <?php selected($status_filter, 'cancelled'); ?>><?php _e('İptal', 'villa-reservation-system'); ?></option>
                            <option value="completed" <?php selected($status_filter, 'completed'); ?>><?php _e('Tamamlandı', 'villa-reservation-system'); ?></option>
                        </select>
                    </div>
                    
                    <div class="vrs-filter-group">
                        <label><?php _e('Villa', 'villa-reservation-system'); ?></label>
                        <select name="villa">
                            <option value=""><?php _e('Tüm Villalar', 'villa-reservation-system'); ?></option>
                            <?php foreach ($villas as $villa): ?>
                                <option value="<?php echo $villa->ID; ?>" <?php selected($villa_filter, $villa->ID); ?>>
                                    <?php echo esc_html($villa->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="vrs-filter-group">
                        <label><?php _e('Başlangıç Tarihi', 'villa-reservation-system'); ?></label>
                        <input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>">
                    </div>
                    
                    <div class="vrs-filter-group">
                        <label><?php _e('Bitiş Tarihi', 'villa-reservation-system'); ?></label>
                        <input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>">
                    </div>
                    
                    <button type="submit" class="vrs-btn vrs-btn-primary"><?php _e('Filtrele', 'villa-reservation-system'); ?></button>
                    <a href="<?php echo admin_url('admin.php?page=vrs-reservations'); ?>" class="vrs-btn vrs-btn-secondary"><?php _e('Temizle', 'villa-reservation-system'); ?></a>
                </form>
            </div>
            
            <!-- Reservations Table -->
            <table class="vrs-admin-table">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'villa-reservation-system'); ?></th>
                        <th><?php _e('Villa', 'villa-reservation-system'); ?></th>
                        <th><?php _e('Misafir', 'villa-reservation-system'); ?></th>
                        <th><?php _e('Giriş - Çıkış', 'villa-reservation-system'); ?></th>
                        <th><?php _e('Kişi Sayısı', 'villa-reservation-system'); ?></th>
                        <th><?php _e('Tutar', 'villa-reservation-system'); ?></th>
                        <th><?php _e('Durum', 'villa-reservation-system'); ?></th>
                        <th><?php _e('İşlemler', 'villa-reservation-system'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($reservations)): ?>
                        <?php foreach ($reservations as $reservation): ?>
                            <tr>
                                <td>#<?php echo $reservation->id; ?></td>
                                <td><?php echo get_the_title($reservation->villa_id); ?></td>
                                <td>
                                    <strong><?php echo esc_html($reservation->guest_name); ?></strong><br>
                                    <small><?php echo esc_html($reservation->guest_email); ?></small>
                                </td>
                                <td>
                                    <?php echo date_i18n('d.m.Y', strtotime($reservation->checkin_date)); ?> - 
                                    <?php echo date_i18n('d.m.Y', strtotime($reservation->checkout_date)); ?>
                                </td>
                                <td>
                                    <?php printf(__('%d Yetişkin, %d Çocuk', 'villa-reservation-system'), $reservation->adults, $reservation->children); ?>
                                </td>
                                <td><?php echo number_format($reservation->total_price, 2); ?> ₺</td>
                                <td>
                                    <span class="vrs-status <?php echo $reservation->status; ?>">
                                        <?php echo $this->get_status_label($reservation->status); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=vrs-reservation-details&id=' . $reservation->id); ?>" class="vrs-btn vrs-btn-secondary">
                                        <?php _e('Görüntüle', 'villa-reservation-system'); ?>
                                    </a>
                                    <?php if ($reservation->status === 'pending'): ?>
                                        <button type="button" class="vrs-btn vrs-btn-success vrs-confirm-reservation" data-id="<?php echo $reservation->id; ?>">
                                            <?php _e('Onayla', 'villa-reservation-system'); ?>
                                        </button>
                                    <?php endif; ?>
                                    <?php if (in_array($reservation->status, ['pending', 'confirmed'])): ?>
                                        <button type="button" class="vrs-btn vrs-btn-danger vrs-cancel-reservation" data-id="<?php echo $reservation->id; ?>">
                                            <?php _e('İptal Et', 'villa-reservation-system'); ?>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px;">
                                <?php _e('Rezervasyon bulunamadı.', 'villa-reservation-system'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Calendar page
     */
    public function calendar_page() {
        $current_date = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : date('Y-m-d');
        $view_month = date('Y-m', strtotime($current_date));
        
        // Get reservations for the month
        global $wpdb;
        $reservations = $wpdb->get_results($wpdb->prepare("
            SELECT r.*, g.guest_name
            FROM {$wpdb->prefix}vrs_reservations r
            LEFT JOIN {$wpdb->prefix}vrs_guest_info g ON r.id = g.reservation_id
            WHERE (r.checkin_date LIKE %s OR r.checkout_date LIKE %s)
            AND r.status IN ('confirmed', 'pending')
            ORDER BY r.checkin_date
        ", $view_month . '%', $view_month . '%'));
        
        // Organize by date
        $calendar_data = array();
        foreach ($reservations as $reservation) {
            $start = strtotime($reservation->checkin_date);
            $end = strtotime($reservation->checkout_date);
            
            for ($date = $start; $date < $end; $date += 24 * 60 * 60) {
                $date_key = date('Y-m-d', $date);
                if (!isset($calendar_data[$date_key])) {
                    $calendar_data[$date_key] = array();
                }
                $calendar_data[$date_key][] = $reservation;
            }
        }
        ?>
        <div class="wrap vrs-admin-page">
            <div class="vrs-admin-header">
                <div>
                    <h1 class="vrs-admin-title"><?php _e('Rezervasyon Takvimi', 'villa-reservation-system'); ?></h1>
                    <p class="vrs-admin-subtitle"><?php _e('Aylık rezervasyon görünümü', 'villa-reservation-system'); ?></p>
                </div>
            </div>
            
            <div class="vrs-calendar">
                <div class="vrs-calendar-header">
                    <div class="vrs-calendar-nav">
                        <a href="?page=vrs-calendar&date=<?php echo date('Y-m-d', strtotime($view_month . '-01 -1 month')); ?>" class="vrs-btn vrs-btn-secondary">
                            &#8249; <?php _e('Önceki Ay', 'villa-reservation-system'); ?>
                        </a>
                        <h2 class="vrs-calendar-title"><?php echo date_i18n('F Y', strtotime($view_month . '-01')); ?></h2>
                        <a href="?page=vrs-calendar&date=<?php echo date('Y-m-d', strtotime($view_month . '-01 +1 month')); ?>" class="vrs-btn vrs-btn-secondary">
                            <?php _e('Sonraki Ay', 'villa-reservation-system'); ?> &#8250;
                        </a>
                    </div>
                    <div>
                        <input type="date" id="vrs-calendar-date" value="<?php echo $current_date; ?>" onchange="window.location='?page=vrs-calendar&date=' + this.value">
                    </div>
                </div>
                
                <div class="vrs-calendar-grid">
                    <!-- Day headers -->
                    <div class="vrs-calendar-day-header"><?php _e('Ptesi', 'villa-reservation-system'); ?></div>
                    <div class="vrs-calendar-day-header"><?php _e('Salı', 'villa-reservation-system'); ?></div>
                    <div class="vrs-calendar-day-header"><?php _e('Çarş', 'villa-reservation-system'); ?></div>
                    <div class="vrs-calendar-day-header"><?php _e('Perş', 'villa-reservation-system'); ?></div>
                    <div class="vrs-calendar-day-header"><?php _e('Cuma', 'villa-reservation-system'); ?></div>
                    <div class="vrs-calendar-day-header"><?php _e('Ctesi', 'villa-reservation-system'); ?></div>
                    <div class="vrs-calendar-day-header"><?php _e('Pazar', 'villa-reservation-system'); ?></div>
                    
                    <?php
                    $first_day = date('Y-m-01', strtotime($view_month . '-01'));
                    $last_day = date('Y-m-t', strtotime($view_month . '-01'));
                    $start_date = date('Y-m-d', strtotime('monday this week', strtotime($first_day)));
                    $end_date = date('Y-m-d', strtotime('sunday this week', strtotime($last_day)));
                    
                    $current = strtotime($start_date);
                    $end = strtotime($end_date);
                    
                    while ($current <= $end) {
                        $date_key = date('Y-m-d', $current);
                        $is_current_month = date('Y-m', $current) === $view_month;
                        $is_today = $date_key === date('Y-m-d');
                        $has_reservations = isset($calendar_data[$date_key]);
                        
                        $classes = array('vrs-calendar-day');
                        if (!$is_current_month) $classes[] = 'other-month';
                        if ($is_today) $classes[] = 'today';
                        if ($has_reservations) $classes[] = 'has-reservation';
                        ?>
                        <div class="<?php echo implode(' ', $classes); ?>">
                            <div class="vrs-calendar-day-number"><?php echo date('j', $current); ?></div>
                            <?php if ($has_reservations): ?>
                                <?php foreach ($calendar_data[$date_key] as $reservation): ?>
                                    <div class="vrs-calendar-reservation" title="<?php echo esc_attr($reservation->guest_name . ' - ' . get_the_title($reservation->villa_id)); ?>">
                                        <?php echo esc_html(substr($reservation->guest_name, 0, 15)); ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <?php
                        $current += 24 * 60 * 60;
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'vrs_settings')) {
            $this->save_settings();
        }
        
        $settings = get_option('vrs_settings', array());
        ?>
        <div class="wrap vrs-admin-page">
            <div class="vrs-admin-header">
                <div>
                    <h1 class="vrs-admin-title"><?php _e('Sistem Ayarları', 'villa-reservation-system'); ?></h1>
                    <p class="vrs-admin-subtitle"><?php _e('Genel sistem konfigürasyonu', 'villa-reservation-system'); ?></p>
                </div>
            </div>
            
            <form method="post" action="">
                <?php wp_nonce_field('vrs_settings'); ?>
                
                <div class="vrs-admin-cards">
                    <!-- General Settings -->
                    <div class="vrs-admin-card">
                        <h3><?php _e('Genel Ayarlar', 'villa-reservation-system'); ?></h3>
                        <div class="vrs-card-content">
                            <div class="vrs-form-grid">
                                <div class="vrs-form-field">
                                    <label><?php _e('Sistem E-posta Adresi', 'villa-reservation-system'); ?></label>
                                    <input type="email" name="settings[admin_email]" value="<?php echo esc_attr($settings['admin_email'] ?? get_option('admin_email')); ?>">
                                    <p class="description"><?php _e('Rezervasyon bildirimlerinin gönderileceği e-posta adresi', 'villa-reservation-system'); ?></p>
                                </div>
                                
                                <div class="vrs-form-field">
                                    <label><?php _e('Gönderen İsmi', 'villa-reservation-system'); ?></label>
                                    <input type="text" name="settings[from_name]" value="<?php echo esc_attr($settings['from_name'] ?? get_option('blogname')); ?>">
                                </div>
                                
                                <div class="vrs-form-field">
                                    <label><?php _e('Para Birimi', 'villa-reservation-system'); ?></label>
                                    <select name="settings[currency]">
                                        <option value="TRY" <?php selected($settings['currency'] ?? 'TRY', 'TRY'); ?>>Türk Lirası (₺)</option>
                                        <option value="USD" <?php selected($settings['currency'] ?? 'TRY', 'USD'); ?>>US Dollar ($)</option>
                                        <option value="EUR" <?php selected($settings['currency'] ?? 'TRY', 'EUR'); ?>>Euro (€)</option>
                                    </select>
                                </div>
                                
                                <div class="vrs-form-field">
                                    <label><?php _e('Minimum Rezervasyon Süresi (gün)', 'villa-reservation-system'); ?></label>
                                    <input type="number" name="settings[min_days]" value="<?php echo esc_attr($settings['min_days'] ?? 1); ?>" min="1">
                                </div>
                                
                                <div class="vrs-form-field">
                                    <label><?php _e('Maksimum Rezervasyon Süresi (gün)', 'villa-reservation-system'); ?></label>
                                    <input type="number" name="settings[max_days]" value="<?php echo esc_attr($settings['max_days'] ?? 30); ?>" min="1">
                                </div>
                                
                                <div class="vrs-form-field">
                                    <label><?php _e('Rezervasyon Süre Sınırı (dakika)', 'villa-reservation-system'); ?></label>
                                    <input type="number" name="settings[reservation_timeout]" value="<?php echo esc_attr($settings['reservation_timeout'] ?? 30); ?>" min="5">
                                    <p class="description"><?php _e('Ödeme yapılmazsa rezervasyon ne kadar süre sonra iptal edilir', 'villa-reservation-system'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Email Settings -->
                    <div class="vrs-admin-card">
                        <h3><?php _e('E-posta Ayarları', 'villa-reservation-system'); ?></h3>
                        <div class="vrs-card-content">
                            <div class="vrs-form-grid">
                                <div class="vrs-form-field">
                                    <label>
                                        <input type="checkbox" name="settings[enable_customer_emails]" value="1" <?php checked($settings['enable_customer_emails'] ?? 1, 1); ?>>
                                        <?php _e('Müşteri e-postalarını gönder', 'villa-reservation-system'); ?>
                                    </label>
                                </div>
                                
                                <div class="vrs-form-field">
                                    <label>
                                        <input type="checkbox" name="settings[enable_admin_emails]" value="1" <?php checked($settings['enable_admin_emails'] ?? 1, 1); ?>>
                                        <?php _e('Yönetici e-postalarını gönder', 'villa-reservation-system'); ?>
                                    </label>
                                </div>
                                
                                <div class="vrs-form-field">
                                    <label>
                                        <input type="checkbox" name="settings[enable_reminder_emails]" value="1" <?php checked($settings['enable_reminder_emails'] ?? 1, 1); ?>>
                                        <?php _e('Hatırlatma e-postalarını gönder', 'villa-reservation-system'); ?>
                                    </label>
                                </div>
                                
                                <div class="vrs-form-field">
                                    <label><?php _e('Hatırlatma e-posta günleri', 'villa-reservation-system'); ?></label>
                                    <input type="text" name="settings[reminder_days]" value="<?php echo esc_attr($settings['reminder_days'] ?? '3,1'); ?>">
                                    <p class="description"><?php _e('Giriş tarihinden kaç gün önce hatırlatma gönderilsin (virgülle ayırın)', 'villa-reservation-system'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <p class="submit">
                    <button type="submit" name="submit" class="vrs-btn vrs-btn-primary">
                        <?php _e('Ayarları Kaydet', 'villa-reservation-system'); ?>
                    </button>
                </p>
            </form>
        </div>
        <?php
    }
    
    /**
     * Reports page
     */
    public function reports_page() {
        $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-01');
        $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-t');
        
        global $wpdb;
        
        // Revenue report
        $revenue_data = $wpdb->get_results($wpdb->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as bookings,
                SUM(total_price) as revenue
            FROM {$wpdb->prefix}vrs_reservations
            WHERE status = 'confirmed'
            AND DATE(created_at) BETWEEN %s AND %s
            GROUP BY DATE(created_at)
            ORDER BY date
        ", $start_date, $end_date));
        
        // Villa performance
        $villa_performance = $wpdb->get_results($wpdb->prepare("
            SELECT 
                r.villa_id,
                COUNT(*) as total_bookings,
                SUM(CASE WHEN r.status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_bookings,
                SUM(CASE WHEN r.status = 'confirmed' THEN r.total_price ELSE 0 END) as total_revenue,
                AVG(CASE WHEN r.status = 'confirmed' THEN r.total_price ELSE NULL END) as avg_booking_value
            FROM {$wpdb->prefix}vrs_reservations r
            WHERE DATE(r.created_at) BETWEEN %s AND %s
            GROUP BY r.villa_id
            ORDER BY total_revenue DESC
        ", $start_date, $end_date));
        ?>
        <div class="wrap vrs-admin-page">
            <div class="vrs-admin-header">
                <div>
                    <h1 class="vrs-admin-title"><?php _e('Raporlar', 'villa-reservation-system'); ?></h1>
                    <p class="vrs-admin-subtitle"><?php _e('Detaylı performans analizi', 'villa-reservation-system'); ?></p>
                </div>
            </div>
            
            <!-- Date Filter -->
            <div class="vrs-filter-bar">
                <form method="get" action="">
                    <input type="hidden" name="page" value="vrs-reports">
                    
                    <div class="vrs-filter-group">
                        <label><?php _e('Başlangıç Tarihi', 'villa-reservation-system'); ?></label>
                        <input type="date" name="start_date" value="<?php echo esc_attr($start_date); ?>">
                    </div>
                    
                    <div class="vrs-filter-group">
                        <label><?php _e('Bitiş Tarihi', 'villa-reservation-system'); ?></label>
                        <input type="date" name="end_date" value="<?php echo esc_attr($end_date); ?>">
                    </div>
                    
                    <button type="submit" class="vrs-btn vrs-btn-primary"><?php _e('Raporu Güncelle', 'villa-reservation-system'); ?></button>
                </form>
            </div>
            
            <div class="vrs-admin-cards">
                <!-- Daily Revenue Chart -->
                <div class="vrs-admin-card">
                    <h3><?php _e('Günlük Gelir Grafiği', 'villa-reservation-system'); ?></h3>
                    <div class="vrs-card-content">
                        <?php if (!empty($revenue_data)): ?>
                            <table class="vrs-admin-table">
                                <thead>
                                    <tr>
                                        <th><?php _e('Tarih', 'villa-reservation-system'); ?></th>
                                        <th><?php _e('Rezervasyon Sayısı', 'villa-reservation-system'); ?></th>
                                        <th><?php _e('Gelir', 'villa-reservation-system'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($revenue_data as $data): ?>
                                        <tr>
                                            <td><?php echo date_i18n('d.m.Y', strtotime($data->date)); ?></td>
                                            <td><?php echo $data->bookings; ?></td>
                                            <td><?php echo number_format($data->revenue, 2); ?> ₺</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p><?php _e('Seçilen tarih aralığında veri bulunamadı.', 'villa-reservation-system'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Villa Performance -->
                <div class="vrs-admin-card">
                    <h3><?php _e('Villa Performansı', 'villa-reservation-system'); ?></h3>
                    <div class="vrs-card-content">
                        <?php if (!empty($villa_performance)): ?>
                            <table class="vrs-admin-table">
                                <thead>
                                    <tr>
                                        <th><?php _e('Villa', 'villa-reservation-system'); ?></th>
                                        <th><?php _e('Toplam Rezervasyon', 'villa-reservation-system'); ?></th>
                                        <th><?php _e('Onaylı Rezervasyon', 'villa-reservation-system'); ?></th>
                                        <th><?php _e('Toplam Gelir', 'villa-reservation-system'); ?></th>
                                        <th><?php _e('Ortalama Rezervasyon Değeri', 'villa-reservation-system'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($villa_performance as $data): ?>
                                        <tr>
                                            <td><?php echo get_the_title($data->villa_id); ?></td>
                                            <td><?php echo $data->total_bookings; ?></td>
                                            <td><?php echo $data->confirmed_bookings; ?></td>
                                            <td><?php echo number_format($data->total_revenue, 2); ?> ₺</td>
                                            <td><?php echo number_format($data->avg_booking_value, 2); ?> ₺</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p><?php _e('Villa performans verisi bulunamadı.', 'villa-reservation-system'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Google Sheets page
     */
    public function google_sheets_page() {
        if (isset($_POST['upload_credentials']) && wp_verify_nonce($_POST['_wpnonce'], 'vrs_google_sheets')) {
            $this->save_google_credentials();
        }
        
        $credentials = get_option('vrs_google_credentials');
        ?>
        <div class="wrap vrs-admin-page">
            <div class="vrs-admin-header">
                <div>
                    <h1 class="vrs-admin-title"><?php _e('Google Sheets Entegrasyonu', 'villa-reservation-system'); ?></h1>
                    <p class="vrs-admin-subtitle"><?php _e('Google Sheets API ayarları ve senkronizasyon', 'villa-reservation-system'); ?></p>
                </div>
            </div>
            
            <div class="vrs-admin-cards">
                <!-- Credentials Upload -->
                <div class="vrs-admin-card">
                    <h3><?php _e('API Kimlik Bilgileri', 'villa-reservation-system'); ?></h3>
                    <div class="vrs-card-content">
                        <?php if ($credentials): ?>
                            <div class="vrs-notice success">
                                <p><?php _e('Google Sheets API kimlik bilgileri yüklenmiş. Son güncelleme:', 'villa-reservation-system'); ?> 
                                <?php echo date_i18n('d.m.Y H:i', get_option('vrs_google_credentials_updated', time())); ?></p>
                            </div>
                        <?php else: ?>
                            <div class="vrs-notice warning">
                                <p><?php _e('Google Sheets entegrasyonu için önce API kimlik bilgilerini yüklemeniz gerekiyor.', 'villa-reservation-system'); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <form method="post" enctype="multipart/form-data">
                            <?php wp_nonce_field('vrs_google_sheets'); ?>
                            
                            <div class="vrs-form-field">
                                <label><?php _e('Service Account JSON Dosyası', 'villa-reservation-system'); ?></label>
                                <input type="file" name="credentials_file" accept=".json" required>
                                <p class="description">
                                    <?php _e('Google Cloud Console\'dan indirdiğiniz service account JSON dosyasını yükleyin.', 'villa-reservation-system'); ?>
                                    <a href="https://console.cloud.google.com/apis/credentials" target="_blank"><?php _e('Google Cloud Console', 'villa-reservation-system'); ?></a>
                                </p>
                            </div>
                            
                            <button type="submit" name="upload_credentials" class="vrs-btn vrs-btn-primary">
                                <?php _e('Kimlik Bilgilerini Yükle', 'villa-reservation-system'); ?>
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Sync Status -->
                <div class="vrs-admin-card">
                    <h3><?php _e('Senkronizasyon Durumu', 'villa-reservation-system'); ?></h3>
                    <div class="vrs-card-content">
                        <?php $this->show_sync_status(); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Helper Methods
     */
    private function get_dashboard_stats() {
        global $wpdb;
        
        return array(
            'total_reservations' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vrs_reservations"),
            'confirmed_reservations' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vrs_reservations WHERE status = 'confirmed'"),
            'pending_reservations' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vrs_reservations WHERE status = 'pending'"),
            'total_revenue' => $wpdb->get_var("SELECT SUM(total_price) FROM {$wpdb->prefix}vrs_reservations WHERE status = 'confirmed'") ?: 0,
            'active_villas' => wp_count_posts('villa')->publish,
            'this_month_bookings' => $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*) FROM {$wpdb->prefix}vrs_reservations 
                WHERE MONTH(created_at) = %d AND YEAR(created_at) = %d
            ", date('n'), date('Y')))
        );
    }
    
    private function get_recent_reservations($limit = 5) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT r.*, g.guest_name
            FROM {$wpdb->prefix}vrs_reservations r
            LEFT JOIN {$wpdb->prefix}vrs_guest_info g ON r.id = g.reservation_id
            ORDER BY r.created_at DESC
            LIMIT %d
        ", $limit));
    }
    
    private function get_upcoming_checkins($limit = 5) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT r.*, g.guest_name
            FROM {$wpdb->prefix}vrs_reservations r
            LEFT JOIN {$wpdb->prefix}vrs_guest_info g ON r.id = g.reservation_id
            WHERE r.checkin_date >= CURDATE()
            AND r.status = 'confirmed'
            ORDER BY r.checkin_date
            LIMIT %d
        ", $limit));
    }
    
    private function get_status_label($status) {
        $labels = array(
            'pending' => __('Bekliyor', 'villa-reservation-system'),
            'confirmed' => __('Onaylandı', 'villa-reservation-system'),
            'cancelled' => __('İptal', 'villa-reservation-system'),
            'completed' => __('Tamamlandı', 'villa-reservation-system')
        );
        
        return $labels[$status] ?? $status;
    }
    
    private function handle_reservation_action() {
        // Implementation for reservation actions
        // This would be called via AJAX or form submission
    }
    
    private function save_settings() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $settings = $_POST['settings'] ?? array();
        update_option('vrs_settings', $settings);
        
        add_settings_error('vrs_settings', 'settings_saved', __('Ayarlar kaydedildi.', 'villa-reservation-system'), 'success');
    }
    
    private function save_google_credentials() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        if (!isset($_FILES['credentials_file']) || $_FILES['credentials_file']['error'] !== UPLOAD_ERR_OK) {
            add_settings_error('vrs_google_sheets', 'upload_error', __('Dosya yüklenirken hata oluştu.', 'villa-reservation-system'), 'error');
            return;
        }
        
        $file_content = file_get_contents($_FILES['credentials_file']['tmp_name']);
        $json_data = json_decode($file_content, true);
        
        if (!$json_data || !isset($json_data['type']) || $json_data['type'] !== 'service_account') {
            add_settings_error('vrs_google_sheets', 'invalid_file', __('Geçersiz service account dosyası.', 'villa-reservation-system'), 'error');
            return;
        }
        
        update_option('vrs_google_credentials', $json_data);
        update_option('vrs_google_credentials_updated', time());
        
        add_settings_error('vrs_google_sheets', 'credentials_saved', __('Kimlik bilgileri başarıyla kaydedildi.', 'villa-reservation-system'), 'success');
    }
    
    private function show_sync_status() {
        // Show sync status for all villas
        $villas = get_posts(array(
            'post_type' => 'villa',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));
        
        if (empty($villas)) {
            echo '<p>' . __('Henüz villa eklenmemiş.', 'villa-reservation-system') . '</p>';
            return;
        }
        
        echo '<table class="vrs-admin-table">';
        echo '<thead><tr>';
        echo '<th>' . __('Villa', 'villa-reservation-system') . '</th>';
        echo '<th>' . __('Google Sheets URL', 'villa-reservation-system') . '</th>';
        echo '<th>' . __('Durum', 'villa-reservation-system') . '</th>';
        echo '<th>' . __('Son Sync', 'villa-reservation-system') . '</th>';
        echo '<th>' . __('İşlemler', 'villa-reservation-system') . '</th>';
        echo '</tr></thead><tbody>';
        
        foreach ($villas as $villa) {
            $google_sheet_url = get_post_meta($villa->ID, '_vrs_google_sheet_url', true);
            $last_sync = get_post_meta($villa->ID, '_vrs_last_sync', true);
            
            echo '<tr>';
            echo '<td>' . esc_html($villa->post_title) . '</td>';
            echo '<td>' . ($google_sheet_url ? '<a href="' . esc_url($google_sheet_url) . '" target="_blank">Sheet</a>' : '-') . '</td>';
            echo '<td>';
            if ($google_sheet_url) {
                echo '<span class="vrs-sync-indicator connected">' . __('Bağlı', 'villa-reservation-system') . '</span>';
            } else {
                echo '<span class="vrs-sync-indicator disconnected">' . __('Bağlı Değil', 'villa-reservation-system') . '</span>';
            }
            echo '</td>';
            echo '<td>' . ($last_sync ? date_i18n('d.m.Y H:i', $last_sync) : '-') . '</td>';
            echo '<td>';
            if ($google_sheet_url) {
                echo '<button type="button" class="vrs-btn vrs-btn-secondary vrs-sync-villa" data-villa="' . $villa->ID . '">' . __('Senkronize Et', 'villa-reservation-system') . '</button>';
            }
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    /**
     * Handle admin AJAX requests
     */
    public function handle_admin_ajax() {
        check_ajax_referer('vrs_admin_nonce', 'nonce');
        
        $action = sanitize_text_field($_POST['admin_action']);
        
        switch ($action) {
            case 'confirm_reservation':
                $this->ajax_confirm_reservation();
                break;
            case 'cancel_reservation':
                $this->ajax_cancel_reservation();
                break;
            case 'sync_villa':
                $this->ajax_sync_villa();
                break;
            default:
                wp_send_json_error(array('message' => __('Geçersiz işlem.', 'villa-reservation-system')));
        }
    }
    
    private function ajax_confirm_reservation() {
        $reservation_id = intval($_POST['reservation_id']);
        
        if (!$reservation_id) {
            wp_send_json_error(array('message' => __('Geçersiz rezervasyon ID.', 'villa-reservation-system')));
        }
        
        global $wpdb;
        $result = $wpdb->update(
            $wpdb->prefix . 'vrs_reservations',
            array('status' => 'confirmed'),
            array('id' => $reservation_id),
            array('%s'),
            array('%d')
        );
        
        if ($result !== false) {
            do_action('vrs_reservation_confirmed', $reservation_id);
            wp_send_json_success(array('message' => __('Rezervasyon onaylandı.', 'villa-reservation-system')));
        } else {
            wp_send_json_error(array('message' => __('Rezervasyon onaylanırken hata oluştu.', 'villa-reservation-system')));
        }
    }
    
    private function ajax_cancel_reservation() {
        $reservation_id = intval($_POST['reservation_id']);
        
        if (!$reservation_id) {
            wp_send_json_error(array('message' => __('Geçersiz rezervasyon ID.', 'villa-reservation-system')));
        }
        
        global $wpdb;
        $result = $wpdb->update(
            $wpdb->prefix . 'vrs_reservations',
            array('status' => 'cancelled'),
            array('id' => $reservation_id),
            array('%s'),
            array('%d')
        );
        
        if ($result !== false) {
            do_action('vrs_reservation_cancelled', $reservation_id);
            wp_send_json_success(array('message' => __('Rezervasyon iptal edildi.', 'villa-reservation-system')));
        } else {
            wp_send_json_error(array('message' => __('Rezervasyon iptal edilirken hata oluştu.', 'villa-reservation-system')));
        }
    }
    
    private function ajax_sync_villa() {
        $villa_id = intval($_POST['villa_id']);
        
        if (!$villa_id) {
            wp_send_json_error(array('message' => __('Geçersiz villa ID.', 'villa-reservation-system')));
        }
        
        // Trigger Google Sheets sync
        $google_sheets = new VRS_Google_Sheets();
        $result = $google_sheets->sync_villa($villa_id);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Senkronizasyon tamamlandı.', 'villa-reservation-system')));
        } else {
            wp_send_json_error(array('message' => __('Senkronizasyon hatası.', 'villa-reservation-system')));
        }
    }
}

// Initialize admin pages
new VRS_Admin_Pages();