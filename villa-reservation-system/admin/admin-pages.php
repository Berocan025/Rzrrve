<?php
/**
 * Admin Pages
 * Villa Rezervasyon Sistemi - Admin Sayfaları
 *
 * @package VillaReservationSystem
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

class VRS_Admin_Pages {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_init', array($this, 'admin_init'));
        
        // AJAX actions
        add_action('wp_ajax_vrs_dashboard_stats', array($this, 'ajax_dashboard_stats'));
        add_action('wp_ajax_vrs_quick_block_date', array($this, 'ajax_quick_block_date'));
        add_action('wp_ajax_vrs_unblock_date', array($this, 'ajax_unblock_date'));
    }
    
    /**
     * Admin menüsünü ekle
     */
    public function add_admin_menu() {
        // Ana menü
        add_menu_page(
            __('Villa Rezervasyon', 'villa-reservation-system'),
            __('Villa Rezervasyon', 'villa-reservation-system'),
            'manage_options',
            'villa-reservations',
            array($this, 'dashboard_page'),
            'dashicons-calendar-alt',
            30
        );
        
        // Alt menüler
        add_submenu_page(
            'villa-reservations',
            __('Panel', 'villa-reservation-system'),
            __('Panel', 'villa-reservation-system'),
            'manage_options',
            'villa-reservations',
            array($this, 'dashboard_page')
        );
        
        add_submenu_page(
            'villa-reservations',
            __('Ayarlar', 'villa-reservation-system'),
            __('Ayarlar', 'villa-reservation-system'),
            'manage_options',
            'villa-reservations-settings',
            array($this, 'settings_page')
        );
        
        add_submenu_page(
            'villa-reservations',
            __('E-posta Şablonları', 'villa-reservation-system'),
            __('E-posta Şablonları', 'villa-reservation-system'),
            'manage_options',
            'villa-reservations-emails',
            array($this, 'email_templates_page')
        );
        
        add_submenu_page(
            'villa-reservations',
            __('Raporlar', 'villa-reservation-system'),
            __('Raporlar', 'villa-reservation-system'),
            'manage_options',
            'villa-reservations-reports',
            array($this, 'reports_page')
        );
        
        add_submenu_page(
            'villa-reservations',
            __('Sistem Durumu', 'villa-reservation-system'),
            __('Sistem Durumu', 'villa-reservation-system'),
            'manage_options',
            'villa-reservations-system',
            array($this, 'system_status_page')
        );
    }
    
    /**
     * Admin scriptlerini yükle
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'villa-reservations') === false) {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1', true);
        
        wp_enqueue_script(
            'vrs-admin',
            VRS_PLUGIN_URL . 'admin/js/admin.js',
            array('jquery', 'jquery-ui-datepicker'),
            VRS_VERSION,
            true
        );
        
        wp_enqueue_style('jquery-ui-datepicker');
        wp_enqueue_style(
            'vrs-admin',
            VRS_PLUGIN_URL . 'admin/css/admin.css',
            array(),
            VRS_VERSION
        );
        
        wp_localize_script('vrs-admin', 'vrsAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vrs_admin_nonce'),
            'strings' => array(
                'confirmDelete' => __('Bu işlemi geri alamazsınız. Emin misiniz?', 'villa-reservation-system'),
                'loading' => __('Yükleniyor...', 'villa-reservation-system'),
                'error' => __('Bir hata oluştu. Lütfen tekrar deneyin.', 'villa-reservation-system'),
                'success' => __('İşlem başarıyla tamamlandı.', 'villa-reservation-system'),
                'dateBlocked' => __('Tarih başarıyla kapatıldı.', 'villa-reservation-system'),
                'dateUnblocked' => __('Tarih başarıyla açıldı.', 'villa-reservation-system')
            )
        ));
    }
    
    /**
     * Admin init
     */
    public function admin_init() {
        // Admin bildirimleri
        if (isset($_GET['vrs_message'])) {
            add_action('admin_notices', array($this, 'show_admin_notices'));
        }
    }
    
    /**
     * Admin bildirimleri göster
     */
    public function show_admin_notices() {
        $message = sanitize_text_field($_GET['vrs_message']);
        $type = isset($_GET['vrs_type']) ? sanitize_text_field($_GET['vrs_type']) : 'success';
        
        $messages = array(
            'settings_saved' => __('Ayarlar başarıyla kaydedildi.', 'villa-reservation-system'),
            'email_template_saved' => __('E-posta şablonu başarıyla kaydedildi.', 'villa-reservation-system'),
            'sync_completed' => __('Google Sheets senkronizasyonu tamamlandı.', 'villa-reservation-system'),
            'test_email_sent' => __('Test e-postası gönderildi.', 'villa-reservation-system')
        );
        
        if (isset($messages[$message])) {
            echo '<div class="notice notice-' . esc_attr($type) . ' is-dismissible">';
            echo '<p>' . esc_html($messages[$message]) . '</p>';
            echo '</div>';
        }
    }
    
    /**
     * Dashboard sayfası
     */
    public function dashboard_page() {
        $current_month_start = date('Y-m-01');
        $current_month_end = date('Y-m-t');
        $last_month_start = date('Y-m-01', strtotime('-1 month'));
        $last_month_end = date('Y-m-t', strtotime('-1 month'));
        
        // İstatistikleri al
        global $wpdb;
        
        $stats = array();
        
        // Bu ay rezervasyon sayısı
        $stats['current_month_reservations'] = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}vrs_reservations 
            WHERE created_at BETWEEN %s AND %s AND status != 'cancelled'
        ", $current_month_start, $current_month_end));
        
        // Geçen ay rezervasyon sayısı
        $stats['last_month_reservations'] = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}vrs_reservations 
            WHERE created_at BETWEEN %s AND %s AND status != 'cancelled'
        ", $last_month_start, $last_month_end));
        
        // Toplam gelir (bu ay)
        $stats['current_month_revenue'] = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(total_price) FROM {$wpdb->prefix}vrs_reservations 
            WHERE created_at BETWEEN %s AND %s AND status = 'confirmed'
        ", $current_month_start, $current_month_end)) ?: 0;
        
        // Bekleyen rezervasyonlar
        $stats['pending_reservations'] = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->prefix}vrs_reservations 
            WHERE status = 'pending'
        ");
        
        // En popüler villalar
        $popular_villas = $wpdb->get_results("
            SELECT v.post_title, COUNT(r.id) as reservation_count
            FROM {$wpdb->prefix}vrs_reservations r
            LEFT JOIN {$wpdb->posts} v ON r.villa_id = v.ID
            WHERE r.status != 'cancelled' AND r.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY r.villa_id
            ORDER BY reservation_count DESC
            LIMIT 5
        ");
        
        // Son rezervasyonlar
        $recent_reservations = $wpdb->get_results("
            SELECT r.*, v.post_title as villa_name, g.first_name, g.last_name
            FROM {$wpdb->prefix}vrs_reservations r
            LEFT JOIN {$wpdb->posts} v ON r.villa_id = v.ID
            LEFT JOIN {$wpdb->prefix}vrs_guest_info g ON r.id = g.reservation_id
            ORDER BY r.created_at DESC
            LIMIT 10
        ");
        
        include VRS_PLUGIN_PATH . 'admin/templates/dashboard.php';
    }
    
    /**
     * Ayarlar sayfası
     */
    public function settings_page() {
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['vrs_settings_nonce'], 'vrs_save_settings')) {
            $this->save_settings();
            wp_redirect(add_query_arg(array(
                'page' => 'villa-reservations-settings',
                'vrs_message' => 'settings_saved'
            ), admin_url('admin.php')));
            exit;
        }
        
        $settings = get_option('vrs_settings', array());
        $defaults = array(
            'default_checkin_time' => '15:00',
            'default_checkout_time' => '11:00',
            'minimum_stay' => 1,
            'maximum_stay' => 30,
            'booking_window' => 365,
            'cancellation_policy' => 24,
            'auto_confirm' => 'no',
            'currency_symbol' => '₺',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
            'google_api_key' => '',
            'email_from_name' => get_bloginfo('name'),
            'email_from_address' => get_option('admin_email'),
            'admin_notification' => 'yes',
            'customer_confirmation' => 'yes',
            'reminder_email' => 'yes',
            'reminder_days' => '3,1'
        );
        
        $settings = wp_parse_args($settings, $defaults);
        
        include VRS_PLUGIN_PATH . 'admin/templates/settings.php';
    }
    
    /**
     * Ayarları kaydet
     */
    private function save_settings() {
        $settings = array(
            'default_checkin_time' => sanitize_text_field($_POST['default_checkin_time']),
            'default_checkout_time' => sanitize_text_field($_POST['default_checkout_time']),
            'minimum_stay' => intval($_POST['minimum_stay']),
            'maximum_stay' => intval($_POST['maximum_stay']),
            'booking_window' => intval($_POST['booking_window']),
            'cancellation_policy' => intval($_POST['cancellation_policy']),
            'auto_confirm' => sanitize_text_field($_POST['auto_confirm']),
            'currency_symbol' => sanitize_text_field($_POST['currency_symbol']),
            'date_format' => sanitize_text_field($_POST['date_format']),
            'time_format' => sanitize_text_field($_POST['time_format']),
            'google_api_key' => sanitize_text_field($_POST['google_api_key']),
            'email_from_name' => sanitize_text_field($_POST['email_from_name']),
            'email_from_address' => sanitize_email($_POST['email_from_address']),
            'admin_notification' => isset($_POST['admin_notification']) ? 'yes' : 'no',
            'customer_confirmation' => isset($_POST['customer_confirmation']) ? 'yes' : 'no',
            'reminder_email' => isset($_POST['reminder_email']) ? 'yes' : 'no',
            'reminder_days' => sanitize_text_field($_POST['reminder_days'])
        );
        
        update_option('vrs_settings', $settings);
    }
    
    /**
     * E-posta şablonları sayfası
     */
    public function email_templates_page() {
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['vrs_email_nonce'], 'vrs_save_email_template')) {
            $this->save_email_template();
            wp_redirect(add_query_arg(array(
                'page' => 'villa-reservations-emails',
                'vrs_message' => 'email_template_saved'
            ), admin_url('admin.php')));
            exit;
        }
        
        $current_template = isset($_GET['template']) ? sanitize_text_field($_GET['template']) : 'customer_confirmation';
        
        $templates = array(
            'customer_confirmation' => __('Müşteri Onay E-postası', 'villa-reservation-system'),
            'customer_pending' => __('Müşteri Bekleyen Rezervasyon E-postası', 'villa-reservation-system'),
            'customer_cancellation' => __('Müşteri İptal E-postası', 'villa-reservation-system'),
            'customer_reminder' => __('Müşteri Hatırlatma E-postası', 'villa-reservation-system'),
            'admin_new_reservation' => __('Yönetici Yeni Rezervasyon E-postası', 'villa-reservation-system'),
            'admin_confirmation' => __('Yönetici Onay E-postası', 'villa-reservation-system'),
            'admin_cancellation' => __('Yönetici İptal E-postası', 'villa-reservation-system')
        );
        
        $template_content = get_option('vrs_email_template_' . $current_template, '');
        
        include VRS_PLUGIN_PATH . 'admin/templates/email-templates.php';
    }
    
    /**
     * E-posta şablonunu kaydet
     */
    private function save_email_template() {
        $template = sanitize_text_field($_POST['template']);
        $subject = sanitize_text_field($_POST['email_subject']);
        $content = wp_kses_post($_POST['email_content']);
        
        update_option('vrs_email_template_' . $template . '_subject', $subject);
        update_option('vrs_email_template_' . $template, $content);
    }
    
    /**
     * Raporlar sayfası
     */
    public function reports_page() {
        $current_year = date('Y');
        $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : $current_year . '-01-01';
        $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-d');
        
        global $wpdb;
        
        // Rezervasyon istatistikleri
        $reservation_stats = $wpdb->get_results($wpdb->prepare("
            SELECT 
                DATE_FORMAT(created_at, '%%Y-%%m') as month,
                COUNT(*) as total_reservations,
                SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_reservations,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_reservations,
                SUM(CASE WHEN status = 'confirmed' THEN total_price ELSE 0 END) as total_revenue
            FROM {$wpdb->prefix}vrs_reservations 
            WHERE created_at BETWEEN %s AND %s
            GROUP BY DATE_FORMAT(created_at, '%%Y-%%m')
            ORDER BY month DESC
        ", $start_date, $end_date));
        
        // Villa bazında istatistikler
        $villa_stats = $wpdb->get_results($wpdb->prepare("
            SELECT 
                v.post_title as villa_name,
                COUNT(r.id) as total_reservations,
                SUM(CASE WHEN r.status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_reservations,
                SUM(CASE WHEN r.status = 'confirmed' THEN r.total_price ELSE 0 END) as total_revenue,
                AVG(CASE WHEN r.status = 'confirmed' THEN r.total_price ELSE NULL END) as avg_revenue
            FROM {$wpdb->prefix}vrs_reservations r
            LEFT JOIN {$wpdb->posts} v ON r.villa_id = v.ID
            WHERE r.created_at BETWEEN %s AND %s
            GROUP BY r.villa_id
            ORDER BY total_revenue DESC
        ", $start_date, $end_date));
        
        include VRS_PLUGIN_PATH . 'admin/templates/reports.php';
    }
    
    /**
     * Sistem durumu sayfası
     */
    public function system_status_page() {
        $system_info = array();
        
        // WordPress bilgileri
        $system_info['wordpress'] = array(
            'version' => get_bloginfo('version'),
            'multisite' => is_multisite() ? 'Evet' : 'Hayır',
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize')
        );
        
        // PHP bilgileri
        $system_info['php'] = array(
            'version' => PHP_VERSION,
            'curl_enabled' => function_exists('curl_init') ? 'Evet' : 'Hayır',
            'json_enabled' => function_exists('json_encode') ? 'Evet' : 'Hayır',
            'openssl_enabled' => extension_loaded('openssl') ? 'Evet' : 'Hayır'
        );
        
        // WooCommerce bilgileri
        if (class_exists('WooCommerce')) {
            $system_info['woocommerce'] = array(
                'version' => WC()->version,
                'currency' => get_woocommerce_currency(),
                'store_address' => WC()->countries->get_base_address()
            );
        }
        
        // Database bilgileri
        global $wpdb;
        $system_info['database'] = array(
            'version' => $wpdb->db_version(),
            'prefix' => $wpdb->prefix,
            'charset' => $wpdb->charset,
            'collate' => $wpdb->collate
        );
        
        // Eklenti tabloları kontrolü
        $tables = array(
            'vrs_reservations',
            'vrs_villa_settings', 
            'vrs_blocked_dates',
            'vrs_pricing',
            'vrs_guest_info',
            'vrs_email_log'
        );
        
        $table_status = array();
        foreach ($tables as $table) {
            $table_name = $wpdb->prefix . $table;
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
            $table_status[$table] = $exists ? 'Mevcut' : 'Eksik';
        }
        
        // Google Sheets bağlantı durumu
        $google_sheets_status = 'Yapılandırılmamış';
        $credentials_file = VRS_PLUGIN_PATH . 'credentials.json';
        if (file_exists($credentials_file)) {
            $google_sheets_status = 'Yapılandırılmış';
        }
        
        // Cron job durumu
        $cron_jobs = wp_get_scheduled_events();
        $vrs_crons = array();
        foreach ($cron_jobs as $timestamp => $cron) {
            foreach ($cron as $hook => $events) {
                if (strpos($hook, 'vrs_') === 0) {
                    $vrs_crons[$hook] = date('Y-m-d H:i:s', $timestamp);
                }
            }
        }
        
        include VRS_PLUGIN_PATH . 'admin/templates/system-status.php';
    }
    
    /**
     * Dashboard istatistikleri AJAX
     */
    public function ajax_dashboard_stats() {
        check_ajax_referer('vrs_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Yetkisiz erişim');
        }
        
        $period = sanitize_text_field($_POST['period']);
        $villa_id = intval($_POST['villa_id']);
        
        global $wpdb;
        
        $where_clause = '';
        $params = array();
        
        switch ($period) {
            case 'today':
                $where_clause = ' AND DATE(created_at) = CURDATE()';
                break;
            case 'week':
                $where_clause = ' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
                break;
            case 'month':
                $where_clause = ' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
                break;
            case 'year':
                $where_clause = ' AND created_at >= DATE_SUB(NOW(), INTERVAL 365 DAY)';
                break;
        }
        
        if ($villa_id) {
            $where_clause .= ' AND villa_id = %d';
            $params[] = $villa_id;
        }
        
        $query = "SELECT 
            COUNT(*) as total_reservations,
            SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_reservations,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_reservations,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_reservations,
            SUM(CASE WHEN status = 'confirmed' THEN total_price ELSE 0 END) as total_revenue
            FROM {$wpdb->prefix}vrs_reservations 
            WHERE 1=1 $where_clause";
        
        if (!empty($params)) {
            $stats = $wpdb->get_row($wpdb->prepare($query, $params));
        } else {
            $stats = $wpdb->get_row($query);
        }
        
        wp_send_json_success($stats);
    }
    
    /**
     * Hızlı tarih kapatma AJAX
     */
    public function ajax_quick_block_date() {
        check_ajax_referer('vrs_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Yetkisiz erişim');
        }
        
        $villa_id = intval($_POST['villa_id']);
        $date = sanitize_text_field($_POST['date']);
        $reason = sanitize_text_field($_POST['reason']);
        
        global $wpdb;
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'vrs_blocked_dates',
            array(
                'villa_id' => $villa_id,
                'blocked_date' => $date,
                'reason' => $reason,
                'created_by' => get_current_user_id(),
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%d', '%s')
        );
        
        if ($result === false) {
            wp_send_json_error('Tarih kapatılamadı');
        }
        
        wp_send_json_success('Tarih başarıyla kapatıldı');
    }
    
    /**
     * Tarih açma AJAX
     */
    public function ajax_unblock_date() {
        check_ajax_referer('vrs_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Yetkisiz erişim');
        }
        
        $villa_id = intval($_POST['villa_id']);
        $date = sanitize_text_field($_POST['date']);
        
        global $wpdb;
        
        $result = $wpdb->delete(
            $wpdb->prefix . 'vrs_blocked_dates',
            array(
                'villa_id' => $villa_id,
                'blocked_date' => $date
            ),
            array('%d', '%s')
        );
        
        if ($result === false) {
            wp_send_json_error('Tarih açılamadı');
        }
        
        wp_send_json_success('Tarih başarıyla açıldı');
    }
}

// Admin sayfalarını başlat
new VRS_Admin_Pages();