<?php
/**
 * Villa Reservation System - Admin Pages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class VRS_Admin_Pages {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
    }
    
    /**
     * Admin menü ekle
     */
    public function add_admin_menu() {
        // Ana menü
        add_menu_page(
            __('Villa Rezervasyon', 'villa-reservation-system'),
            __('Villa Rezervasyon', 'villa-reservation-system'),
            'manage_options',
            'villa-reservation',
            array($this, 'dashboard_page'),
            'dashicons-calendar-alt',
            30
        );
        
        // Alt menüler
        add_submenu_page(
            'villa-reservation',
            __('Dashboard', 'villa-reservation-system'),
            __('Dashboard', 'villa-reservation-system'),
            'manage_options',
            'villa-reservation',
            array($this, 'dashboard_page')
        );
        
        add_submenu_page(
            'villa-reservation',
            __('Rezervasyonlar', 'villa-reservation-system'),
            __('Rezervasyonlar', 'villa-reservation-system'),
            'manage_options',
            'villa-reservations',
            array($this, 'reservations_page')
        );
        
        add_submenu_page(
            'villa-reservation',
            __('Takvim Görünümü', 'villa-reservation-system'),
            __('Takvim Görünümü', 'villa-reservation-system'),
            'manage_options',
            'villa-calendar',
            array($this, 'calendar_page')
        );
        
        add_submenu_page(
            'villa-reservation',
            __('Google Sheets Ayarları', 'villa-reservation-system'),
            __('Google Sheets', 'villa-reservation-system'),
            'manage_options',
            'villa-google-sheets',
            array($this, 'google_sheets_page')
        );
        
        add_submenu_page(
            'villa-reservation',
            __('Ayarlar', 'villa-reservation-system'),
            __('Ayarlar', 'villa-reservation-system'),
            'manage_options',
            'villa-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Admin init
     */
    public function admin_init() {
        // Settings API kayıtları
        register_setting('vrs_settings_group', 'vrs_settings');
        
        add_settings_section(
            'vrs_general_section',
            __('Genel Ayarlar', 'villa-reservation-system'),
            array($this, 'general_section_callback'),
            'vrs_settings'
        );
        
        add_settings_field(
            'default_checkin_time',
            __('Varsayılan Check-in Saati', 'villa-reservation-system'),
            array($this, 'time_field_callback'),
            'vrs_settings',
            'vrs_general_section',
            array('field_name' => 'default_checkin_time', 'default' => '15:00')
        );
        
        add_settings_field(
            'default_checkout_time',
            __('Varsayılan Check-out Saati', 'villa-reservation-system'),
            array($this, 'time_field_callback'),
            'vrs_settings',
            'vrs_general_section',
            array('field_name' => 'default_checkout_time', 'default' => '11:00')
        );
        
        add_settings_field(
            'cancellation_policy',
            __('İptal Politikası (saat)', 'villa-reservation-system'),
            array($this, 'number_field_callback'),
            'vrs_settings',
            'vrs_general_section',
            array('field_name' => 'cancellation_policy', 'default' => '24')
        );
        
        add_settings_field(
            'admin_email',
            __('Admin E-posta Adresi', 'villa-reservation-system'),
            array($this, 'email_field_callback'),
            'vrs_settings',
            'vrs_general_section',
            array('field_name' => 'admin_email', 'default' => get_option('admin_email'))
        );
    }
    
    /**
     * Dashboard sayfası
     */
    public function dashboard_page() {
        $stats = $this->get_dashboard_stats();
        ?>
        <div class="wrap vrs-admin-page">
            <div class="vrs-admin-container">
                <div class="vrs-admin-header">
                    <h1><span class="dashicons dashicons-calendar-alt"></span> <?php _e('Villa Rezervasyon Sistemi', 'villa-reservation-system'); ?></h1>
                    <p><?php _e('Villa rezervasyon sisteminizi buradan yönetebilirsiniz.', 'villa-reservation-system'); ?></p>
                </div>
                
                <!-- İstatistik Kartları -->
                <div class="vrs-stats-grid">
                    <div class="vrs-stat-card">
                        <div class="vrs-stat-number"><?php echo $stats['total_villas']; ?></div>
                        <div class="vrs-stat-label"><?php _e('Toplam Villa', 'villa-reservation-system'); ?></div>
                    </div>
                    
                    <div class="vrs-stat-card">
                        <div class="vrs-stat-number"><?php echo $stats['total_reservations']; ?></div>
                        <div class="vrs-stat-label"><?php _e('Toplam Rezervasyon', 'villa-reservation-system'); ?></div>
                    </div>
                    
                    <div class="vrs-stat-card">
                        <div class="vrs-stat-number"><?php echo $stats['pending_reservations']; ?></div>
                        <div class="vrs-stat-label"><?php _e('Bekleyen Rezervasyonlar', 'villa-reservation-system'); ?></div>
                    </div>
                    
                    <div class="vrs-stat-card">
                        <div class="vrs-stat-number"><?php echo $stats['monthly_revenue']; ?> ₺</div>
                        <div class="vrs-stat-label"><?php _e('Bu Ay Gelir', 'villa-reservation-system'); ?></div>
                    </div>
                </div>
                
                <!-- Son Rezervasyonlar ve Kısa Menüler -->
                <div class="vrs-admin-cards">
                    <div class="vrs-admin-card">
                        <h3><span class="dashicons dashicons-list-view"></span> <?php _e('Son Rezervasyonlar', 'villa-reservation-system'); ?></h3>
                        <?php $this->render_recent_reservations(); ?>
                    </div>
                    
                    <div class="vrs-admin-card">
                        <h3><span class="dashicons dashicons-admin-tools"></span> <?php _e('Hızlı İşlemler', 'villa-reservation-system'); ?></h3>
                        <p><a href="<?php echo admin_url('post-new.php?post_type=villa'); ?>" class="vrs-btn"><?php _e('Yeni Villa Ekle', 'villa-reservation-system'); ?></a></p>
                        <p><a href="<?php echo admin_url('admin.php?page=villa-reservations'); ?>" class="vrs-btn secondary"><?php _e('Rezervasyonları Görüntüle', 'villa-reservation-system'); ?></a></p>
                        <p><a href="<?php echo admin_url('admin.php?page=villa-calendar'); ?>" class="vrs-btn secondary"><?php _e('Takvim Görünümü', 'villa-reservation-system'); ?></a></p>
                    </div>
                </div>
                
                <!-- Sistem Durumu -->
                <div class="vrs-admin-card">
                    <h3><span class="dashicons dashicons-performance"></span> <?php _e('Sistem Durumu', 'villa-reservation-system'); ?></h3>
                    <?php $this->render_system_status(); ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Rezervasyonlar sayfası
     */
    public function reservations_page() {
        // Filtreleme parametreleri
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $villa_id = isset($_GET['villa_id']) ? intval($_GET['villa_id']) : 0;
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
        
        // Rezervasyonları al
        $reservations = $this->get_reservations($status, $villa_id, $date_from, $date_to);
        $villas = get_posts(array('post_type' => 'villa', 'numberposts' => -1));
        ?>
        <div class="wrap vrs-admin-page">
            <div class="vrs-admin-container">
                <div class="vrs-admin-header">
                    <h1><span class="dashicons dashicons-list-view"></span> <?php _e('Rezervasyonlar', 'villa-reservation-system'); ?></h1>
                    <p><?php _e('Tüm rezervasyonları görüntüleyebilir ve yönetebilirsiniz.', 'villa-reservation-system'); ?></p>
                </div>
                
                <!-- Filtreler -->
                <div class="vrs-filters">
                    <form method="get" action="">
                        <input type="hidden" name="page" value="villa-reservations">
                        <div class="vrs-filters-row">
                            <div class="vrs-filter-group">
                                <label><?php _e('Durum', 'villa-reservation-system'); ?></label>
                                <select name="status">
                                    <option value=""><?php _e('Tümü', 'villa-reservation-system'); ?></option>
                                    <option value="pending" <?php selected($status, 'pending'); ?>><?php _e('Beklemede', 'villa-reservation-system'); ?></option>
                                    <option value="confirmed" <?php selected($status, 'confirmed'); ?>><?php _e('Onaylandı', 'villa-reservation-system'); ?></option>
                                    <option value="cancelled" <?php selected($status, 'cancelled'); ?>><?php _e('İptal Edildi', 'villa-reservation-system'); ?></option>
                                    <option value="completed" <?php selected($status, 'completed'); ?>><?php _e('Tamamlandı', 'villa-reservation-system'); ?></option>
                                </select>
                            </div>
                            
                            <div class="vrs-filter-group">
                                <label><?php _e('Villa', 'villa-reservation-system'); ?></label>
                                <select name="villa_id">
                                    <option value=""><?php _e('Tümü', 'villa-reservation-system'); ?></option>
                                    <?php foreach ($villas as $villa) : ?>
                                        <option value="<?php echo $villa->ID; ?>" <?php selected($villa_id, $villa->ID); ?>><?php echo $villa->post_title; ?></option>
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
                            
                            <div class="vrs-filter-group">
                                <input type="submit" class="vrs-btn" value="<?php _e('Filtrele', 'villa-reservation-system'); ?>">
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Rezervasyonlar Tablosu -->
                <div class="vrs-reservations-table">
                    <table>
                        <thead>
                            <tr>
                                <th><?php _e('ID', 'villa-reservation-system'); ?></th>
                                <th><?php _e('Villa', 'villa-reservation-system'); ?></th>
                                <th><?php _e('Müşteri', 'villa-reservation-system'); ?></th>
                                <th><?php _e('Check-in', 'villa-reservation-system'); ?></th>
                                <th><?php _e('Check-out', 'villa-reservation-system'); ?></th>
                                <th><?php _e('Misafir', 'villa-reservation-system'); ?></th>
                                <th><?php _e('Toplam', 'villa-reservation-system'); ?></th>
                                <th><?php _e('Durum', 'villa-reservation-system'); ?></th>
                                <th><?php _e('İşlemler', 'villa-reservation-system'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reservations)) : ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 40px;">
                                        <?php _e('Hiç rezervasyon bulunamadı.', 'villa-reservation-system'); ?>
                                    </td>
                                </tr>
                            <?php else : ?>
                                <?php foreach ($reservations as $reservation) : ?>
                                    <tr data-reservation-id="<?php echo $reservation->id; ?>">
                                        <td><?php echo $reservation->id; ?></td>
                                        <td><?php echo get_the_title($reservation->villa_id); ?></td>
                                        <td>
                                            <?php echo esc_html($reservation->guest_name); ?><br>
                                            <small><?php echo esc_html($reservation->guest_email); ?></small>
                                        </td>
                                        <td><?php echo date('d.m.Y', strtotime($reservation->checkin_date)); ?></td>
                                        <td><?php echo date('d.m.Y', strtotime($reservation->checkout_date)); ?></td>
                                        <td><?php echo $reservation->adults; ?> + <?php echo $reservation->children; ?></td>
                                        <td><?php echo number_format($reservation->total_price, 2); ?> ₺</td>
                                        <td><span class="vrs-status <?php echo $reservation->status; ?>"><?php echo ucfirst($reservation->status); ?></span></td>
                                        <td class="actions">
                                            <a href="<?php echo admin_url('admin.php?page=villa-reservation-details&id=' . $reservation->id); ?>" class="vrs-btn small"><?php _e('Görüntüle', 'villa-reservation-system'); ?></a>
                                            <?php if ($reservation->status !== 'cancelled') : ?>
                                                <a href="#" class="vrs-btn small danger vrs-cancel-reservation" data-reservation-id="<?php echo $reservation->id; ?>"><?php _e('İptal Et', 'villa-reservation-system'); ?></a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Takvim sayfası
     */
    public function calendar_page() {
        $current_month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
        $current_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
        $villa_id = isset($_GET['villa_id']) ? intval($_GET['villa_id']) : 0;
        
        $villas = get_posts(array('post_type' => 'villa', 'numberposts' => -1));
        ?>
        <div class="wrap vrs-admin-page">
            <div class="vrs-admin-container">
                <div class="vrs-admin-header">
                    <h1><span class="dashicons dashicons-calendar-alt"></span> <?php _e('Takvim Görünümü', 'villa-reservation-system'); ?></h1>
                    <p><?php _e('Villa rezervasyonlarını takvim görünümünde inceleyebilirsiniz.', 'villa-reservation-system'); ?></p>
                </div>
                
                <!-- Villa Seçici -->
                <div class="vrs-filters">
                    <form method="get" action="">
                        <input type="hidden" name="page" value="villa-calendar">
                        <div class="vrs-filters-row">
                            <div class="vrs-filter-group">
                                <label><?php _e('Villa Seçin', 'villa-reservation-system'); ?></label>
                                <select name="villa_id" onchange="this.form.submit()">
                                    <option value=""><?php _e('Villa seçin...', 'villa-reservation-system'); ?></option>
                                    <?php foreach ($villas as $villa) : ?>
                                        <option value="<?php echo $villa->ID; ?>" <?php selected($villa_id, $villa->ID); ?>><?php echo $villa->post_title; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                
                <?php if ($villa_id) : ?>
                    <!-- Takvim -->
                    <div class="vrs-calendar" data-villa-id="<?php echo $villa_id; ?>" data-current-month="<?php echo $current_month; ?>" data-current-year="<?php echo $current_year; ?>">
                        <?php $this->render_calendar($villa_id, $current_month, $current_year); ?>
                    </div>
                <?php else : ?>
                    <div class="vrs-message info">
                        <span class="dashicons dashicons-info"></span>
                        <?php _e('Lütfen takvim görünümü için bir villa seçin.', 'villa-reservation-system'); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Google Sheets ayarları sayfası
     */
    public function google_sheets_page() {
        // Google Sheets sınıfını include et
        if (class_exists('VRS_Google_Sheets')) {
            $google_sheets = new VRS_Google_Sheets();
            $google_sheets->admin_page();
        } else {
            echo '<div class="wrap"><h1>Google Sheets sınıfı bulunamadı.</h1></div>';
        }
    }
    
    /**
     * Ayarlar sayfası
     */
    public function settings_page() {
        ?>
        <div class="wrap vrs-admin-page">
            <div class="vrs-admin-container">
                <div class="vrs-admin-header">
                    <h1><span class="dashicons dashicons-admin-settings"></span> <?php _e('Villa Rezervasyon Ayarları', 'villa-reservation-system'); ?></h1>
                    <p><?php _e('Genel sistem ayarlarını buradan yapılandırabilirsiniz.', 'villa-reservation-system'); ?></p>
                </div>
                
                <form method="post" action="options.php">
                    <?php
                    settings_fields('vrs_settings_group');
                    do_settings_sections('vrs_settings');
                    submit_button();
                    ?>
                </form>
            </div>
        </div>
        <?php
    }
    
    /**
     * Dashboard istatistikleri al
     */
    private function get_dashboard_stats() {
        global $wpdb;
        
        $stats = array(
            'total_villas' => wp_count_posts('villa')->publish,
            'total_reservations' => 0,
            'pending_reservations' => 0,
            'monthly_revenue' => 0
        );
        
        // Toplam rezervasyon
        $stats['total_reservations'] = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->prefix}vrs_reservations
        ");
        
        // Bekleyen rezervasyonlar
        $stats['pending_reservations'] = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->prefix}vrs_reservations
            WHERE status = 'pending'
        ");
        
        // Bu ay gelir
        $stats['monthly_revenue'] = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(total_price) FROM {$wpdb->prefix}vrs_reservations
            WHERE status IN ('confirmed', 'completed')
            AND MONTH(created_at) = %d
            AND YEAR(created_at) = %d
        ", date('n'), date('Y')));
        
        $stats['monthly_revenue'] = number_format($stats['monthly_revenue'] ?: 0, 0);
        
        return $stats;
    }
    
    /**
     * Son rezervasyonları render et
     */
    private function render_recent_reservations() {
        global $wpdb;
        
        $reservations = $wpdb->get_results("
            SELECT r.*, v.post_title as villa_name
            FROM {$wpdb->prefix}vrs_reservations r
            LEFT JOIN {$wpdb->posts} v ON r.villa_id = v.ID
            ORDER BY r.created_at DESC
            LIMIT 5
        ");
        
        if (empty($reservations)) {
            echo '<p>' . __('Henüz rezervasyon bulunmuyor.', 'villa-reservation-system') . '</p>';
            return;
        }
        
        echo '<table style="width: 100%;">';
        foreach ($reservations as $reservation) {
            echo '<tr>';
            echo '<td><strong>' . esc_html($reservation->guest_name) . '</strong><br>';
            echo '<small>' . esc_html($reservation->villa_name) . '</small></td>';
            echo '<td style="text-align: right;"><span class="vrs-status ' . $reservation->status . '">' . ucfirst($reservation->status) . '</span></td>';
            echo '</tr>';
        }
        echo '</table>';
    }
    
    /**
     * Sistem durumunu render et
     */
    private function render_system_status() {
        $checks = array(
            'WooCommerce' => class_exists('WooCommerce'),
            'Google Sheets API' => get_option('vrs_google_credentials'),
            'Cron Jobs' => wp_next_scheduled('vrs_sync_google_sheets') !== false,
            'Database Tables' => $this->check_database_tables()
        );
        
        echo '<table style="width: 100%;">';
        foreach ($checks as $check => $status) {
            echo '<tr>';
            echo '<td>' . $check . '</td>';
            echo '<td style="text-align: right;">';
            if ($status) {
                echo '<span class="vrs-status confirmed">✓ ' . __('Aktif', 'villa-reservation-system') . '</span>';
            } else {
                echo '<span class="vrs-status cancelled">✗ ' . __('Problem', 'villa-reservation-system') . '</span>';
            }
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
    
    /**
     * Database tablolarını kontrol et
     */
    private function check_database_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'vrs_reservations',
            $wpdb->prefix . 'vrs_villa_settings',
            $wpdb->prefix . 'vrs_blocked_dates',
            $wpdb->prefix . 'vrs_pricing',
            $wpdb->prefix . 'vrs_guest_info',
            $wpdb->prefix . 'vrs_email_log'
        );
        
        foreach ($tables as $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Rezervasyonları al (filtreleme ile)
     */
    private function get_reservations($status = '', $villa_id = 0, $date_from = '', $date_to = '') {
        global $wpdb;
        
        $where = array('1=1');
        
        if (!empty($status)) {
            $where[] = $wpdb->prepare('r.status = %s', $status);
        }
        
        if (!empty($villa_id)) {
            $where[] = $wpdb->prepare('r.villa_id = %d', $villa_id);
        }
        
        if (!empty($date_from)) {
            $where[] = $wpdb->prepare('r.checkin_date >= %s', $date_from);
        }
        
        if (!empty($date_to)) {
            $where[] = $wpdb->prepare('r.checkout_date <= %s', $date_to);
        }
        
        $where_clause = implode(' AND ', $where);
        
        return $wpdb->get_results("
            SELECT r.*, g.guest_name, g.guest_email
            FROM {$wpdb->prefix}vrs_reservations r
            LEFT JOIN {$wpdb->prefix}vrs_guest_info g ON r.id = g.reservation_id
            WHERE {$where_clause}
            ORDER BY r.created_at DESC
        ");
    }
    
    /**
     * Takvim render et
     */
    private function render_calendar($villa_id, $month, $year) {
        // Takvim HTML'ini oluştur
        $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $first_day = date('N', mktime(0, 0, 0, $month, 1, $year));
        
        $month_names = array(
            1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
            5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
            9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'
        );
        
        ?>
        <div class="vrs-calendar-header">
            <div class="vrs-calendar-nav prev">
                <button data-direction="prev">&laquo;</button>
            </div>
            <h2><?php echo $month_names[$month] . ' ' . $year; ?></h2>
            <div class="vrs-calendar-nav next">
                <button data-direction="next">&raquo;</button>
            </div>
        </div>
        
        <div class="vrs-calendar-grid">
            <!-- Gün başlıkları -->
            <div class="vrs-calendar-day-header">Pzt</div>
            <div class="vrs-calendar-day-header">Sal</div>
            <div class="vrs-calendar-day-header">Çar</div>
            <div class="vrs-calendar-day-header">Per</div>
            <div class="vrs-calendar-day-header">Cum</div>
            <div class="vrs-calendar-day-header">Cmt</div>
            <div class="vrs-calendar-day-header">Paz</div>
            
            <?php
            // Boş hücreler (ayın başından önceki günler)
            for ($i = 1; $i < $first_day; $i++) {
                echo '<div class="vrs-calendar-day other-month"></div>';
            }
            
            // Ayın günleri
            for ($day = 1; $day <= $days_in_month; $day++) {
                $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
                $is_today = $date === date('Y-m-d');
                $is_blocked = $this->is_date_blocked($villa_id, $date);
                $reservations = $this->get_day_reservations($villa_id, $date);
                
                $classes = array('vrs-calendar-day');
                if ($is_today) $classes[] = 'today';
                if ($is_blocked) $classes[] = 'blocked';
                if (!empty($reservations)) $classes[] = 'reserved';
                
                echo '<div class="' . implode(' ', $classes) . '" data-date="' . $date . '">';
                echo '<div class="vrs-calendar-day-number">' . $day . '</div>';
                
                if (!empty($reservations)) {
                    echo '<div class="vrs-calendar-reservations">';
                    foreach ($reservations as $reservation) {
                        echo '<a href="' . admin_url('admin.php?page=villa-reservation-details&id=' . $reservation->id) . '" class="vrs-calendar-reservation">';
                        echo esc_html($reservation->guest_name);
                        echo '</a>';
                    }
                    echo '</div>';
                }
                
                echo '</div>';
            }
            ?>
        </div>
        <?php
    }
    
    /**
     * Tarih bloke mi kontrol et
     */
    private function is_date_blocked($villa_id, $date) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}vrs_blocked_dates
            WHERE villa_id = %d AND blocked_date = %s
        ", $villa_id, $date)) > 0;
    }
    
    /**
     * Günün rezervasyonlarını al
     */
    private function get_day_reservations($villa_id, $date) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT r.id, g.guest_name
            FROM {$wpdb->prefix}vrs_reservations r
            LEFT JOIN {$wpdb->prefix}vrs_guest_info g ON r.id = g.reservation_id
            WHERE r.villa_id = %d
            AND %s >= r.checkin_date
            AND %s < r.checkout_date
            AND r.status IN ('confirmed', 'pending')
        ", $villa_id, $date, $date));
    }
    
    /**
     * Settings field callbacks
     */
    public function general_section_callback() {
        echo '<p>' . __('Villa rezervasyon sistemi için genel ayarlar.', 'villa-reservation-system') . '</p>';
    }
    
    public function time_field_callback($args) {
        $options = get_option('vrs_settings');
        $value = isset($options[$args['field_name']]) ? $options[$args['field_name']] : $args['default'];
        echo '<input type="time" name="vrs_settings[' . $args['field_name'] . ']" value="' . esc_attr($value) . '" />';
    }
    
    public function number_field_callback($args) {
        $options = get_option('vrs_settings');
        $value = isset($options[$args['field_name']]) ? $options[$args['field_name']] : $args['default'];
        echo '<input type="number" name="vrs_settings[' . $args['field_name'] . ']" value="' . esc_attr($value) . '" />';
    }
    
    public function email_field_callback($args) {
        $options = get_option('vrs_settings');
        $value = isset($options[$args['field_name']]) ? $options[$args['field_name']] : $args['default'];
        echo '<input type="email" name="vrs_settings[' . $args['field_name'] . ']" value="' . esc_attr($value) . '" style="width: 300px;" />';
    }
}

// Initialize admin pages
if (is_admin()) {
    new VRS_Admin_Pages();
}