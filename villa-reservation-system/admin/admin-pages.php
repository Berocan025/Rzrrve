<?php
/**
 * Admin Pages for Villa Reservation System
 *
 * @package VillaReservationSystem
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
            array($this, 'main_dashboard'),
            'dashicons-calendar-alt',
            26
        );
        
        // Dashboard submenu
        add_submenu_page(
            'villa-reservation-system',
            __('Panel', 'villa-reservation-system'),
            __('Panel', 'villa-reservation-system'),
            'manage_options',
            'villa-reservation-system',
            array($this, 'main_dashboard')
        );
        
        // Reservations submenu
        add_submenu_page(
            'villa-reservation-system',
            __('Rezervasyonlar', 'villa-reservation-system'),
            __('Rezervasyonlar', 'villa-reservation-system'),
            'manage_options',
            'vrs-reservations',
            array($this, 'reservations_page')
        );
        
        // Calendar view submenu
        add_submenu_page(
            'villa-reservation-system',
            __('Takvim Görünümü', 'villa-reservation-system'),
            __('Takvim Görünümü', 'villa-reservation-system'),
            'manage_options',
            'vrs-calendar',
            array($this, 'calendar_page')
        );
        
        // Reports submenu
        add_submenu_page(
            'villa-reservation-system',
            __('Raporlar', 'villa-reservation-system'),
            __('Raporlar', 'villa-reservation-system'),
            'manage_options',
            'vrs-reports',
            array($this, 'reports_page')
        );
        
        // Settings submenu
        add_submenu_page(
            'villa-reservation-system',
            __('Ayarlar', 'villa-reservation-system'),
            __('Ayarlar', 'villa-reservation-system'),
            'manage_options',
            'vrs-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook_suffix) {
        // Only load on our admin pages
        if (strpos($hook_suffix, 'villa-reservation-system') === false && 
            strpos($hook_suffix, 'vrs-') === false) {
            return;
        }
        
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/ui-lightness/jquery-ui.css');
        
        wp_enqueue_script(
            'vrs-admin-js',
            plugin_dir_url(__FILE__) . '../assets/js/admin.js',
            array('jquery', 'jquery-ui-datepicker', 'jquery-ui-dialog'),
            VRS_VERSION,
            true
        );
        
        wp_enqueue_style(
            'vrs-admin-css',
            plugin_dir_url(__FILE__) . '../assets/css/admin.css',
            array(),
            VRS_VERSION
        );
        
        // Localize script
        wp_localize_script('vrs-admin-js', 'vrsAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vrs_admin_nonce'),
            'messages' => array(
                'confirm_delete' => __('Bu rezervasyonu silmek istediğinizden emin misiniz?', 'villa-reservation-system'),
                'confirm_cancel' => __('Bu rezervasyonu iptal etmek istediğinizden emin misiniz?', 'villa-reservation-system'),
                'saving' => __('Kaydediliyor...', 'villa-reservation-system'),
                'saved' => __('Kaydedildi!', 'villa-reservation-system'),
                'error' => __('Hata oluştu!', 'villa-reservation-system'),
            )
        ));
    }
    
    /**
     * Main dashboard page
     */
    public function main_dashboard() {
        global $wpdb;
        
        // Get statistics
        $total_reservations = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->prefix}vrs_reservations
        ");
        
        $pending_reservations = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->prefix}vrs_reservations
            WHERE status = 'pending'
        ");
        
        $confirmed_reservations = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->prefix}vrs_reservations
            WHERE status = 'confirmed'
        ");
        
        $total_villas = wp_count_posts('villa')->publish;
        
        $recent_reservations = $wpdb->get_results("
            SELECT r.*, p.post_title as villa_name
            FROM {$wpdb->prefix}vrs_reservations r
            LEFT JOIN {$wpdb->posts} p ON r.villa_id = p.ID
            ORDER BY r.created_at DESC
            LIMIT 10
        ");
        
        include_once plugin_dir_path(__FILE__) . 'templates/dashboard.php';
    }
    
    /**
     * Reservations page
     */
    public function reservations_page() {
        global $wpdb;
        
        // Handle actions
        if (isset($_POST['action']) && wp_verify_nonce($_POST['vrs_nonce'], 'vrs_admin_action')) {
            $this->handle_reservation_actions();
        }
        
        // Get filter parameters
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $villa_filter = isset($_GET['villa']) ? intval($_GET['villa']) : 0;
        $date_filter = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : '';
        
        // Build query
        $where_clauses = array();
        $query_params = array();
        
        if ($status_filter) {
            $where_clauses[] = "r.status = %s";
            $query_params[] = $status_filter;
        }
        
        if ($villa_filter) {
            $where_clauses[] = "r.villa_id = %d";
            $query_params[] = $villa_filter;
        }
        
        if ($date_filter) {
            $where_clauses[] = "DATE(r.checkin_date) = %s";
            $query_params[] = $date_filter;
        }
        
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }
        
        // Get reservations
        $reservations = $wpdb->get_results($wpdb->prepare("
            SELECT r.*, p.post_title as villa_name,
                   gi.guest_name, gi.guest_email, gi.guest_phone
            FROM {$wpdb->prefix}vrs_reservations r
            LEFT JOIN {$wpdb->posts} p ON r.villa_id = p.ID
            LEFT JOIN {$wpdb->prefix}vrs_guest_info gi ON r.id = gi.reservation_id
            {$where_sql}
            ORDER BY r.created_at DESC
        ", $query_params));
        
        // Get villas for filter
        $villas = get_posts(array(
            'post_type' => 'villa',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        include_once plugin_dir_path(__FILE__) . 'templates/reservations.php';
    }
    
    /**
     * Calendar page
     */
    public function calendar_page() {
        // Get current month/year or from request
        $current_month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
        $current_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
        
        // Get villas
        $villas = get_posts(array(
            'post_type' => 'villa',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        // Get reservations for the month
        global $wpdb;
        $start_date = sprintf('%04d-%02d-01', $current_year, $current_month);
        $end_date = date('Y-m-t', strtotime($start_date));
        
        $reservations = $wpdb->get_results($wpdb->prepare("
            SELECT r.*, p.post_title as villa_name
            FROM {$wpdb->prefix}vrs_reservations r
            LEFT JOIN {$wpdb->posts} p ON r.villa_id = p.ID
            WHERE (r.checkin_date BETWEEN %s AND %s)
               OR (r.checkout_date BETWEEN %s AND %s)
               OR (r.checkin_date <= %s AND r.checkout_date >= %s)
            ORDER BY r.checkin_date
        ", $start_date, $end_date, $start_date, $end_date, $start_date, $end_date));
        
        include_once plugin_dir_path(__FILE__) . 'templates/calendar.php';
    }
    
    /**
     * Reports page
     */
    public function reports_page() {
        global $wpdb;
        
        // Get date range from request
        $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-01');
        $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-t');
        
        // Revenue report
        $revenue_data = $wpdb->get_results($wpdb->prepare("
            SELECT 
                DATE(r.checkin_date) as date,
                SUM(r.total_price) as daily_revenue,
                COUNT(*) as reservations_count
            FROM {$wpdb->prefix}vrs_reservations r
            WHERE r.status = 'confirmed'
            AND r.checkin_date BETWEEN %s AND %s
            GROUP BY DATE(r.checkin_date)
            ORDER BY date
        ", $start_date, $end_date));
        
        // Villa popularity
        $villa_stats = $wpdb->get_results($wpdb->prepare("
            SELECT 
                p.post_title as villa_name,
                COUNT(*) as reservation_count,
                SUM(r.total_price) as total_revenue,
                AVG(r.total_price) as avg_price
            FROM {$wpdb->prefix}vrs_reservations r
            LEFT JOIN {$wpdb->posts} p ON r.villa_id = p.ID
            WHERE r.status = 'confirmed'
            AND r.checkin_date BETWEEN %s AND %s
            GROUP BY r.villa_id
            ORDER BY reservation_count DESC
        ", $start_date, $end_date));
        
        // Monthly comparison
        $monthly_stats = $wpdb->get_results("
            SELECT 
                YEAR(r.checkin_date) as year,
                MONTH(r.checkin_date) as month,
                COUNT(*) as reservations,
                SUM(r.total_price) as revenue
            FROM {$wpdb->prefix}vrs_reservations r
            WHERE r.status = 'confirmed'
            GROUP BY YEAR(r.checkin_date), MONTH(r.checkin_date)
            ORDER BY year DESC, month DESC
            LIMIT 12
        ");
        
        include_once plugin_dir_path(__FILE__) . 'templates/reports.php';
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['vrs_settings_nonce'], 'vrs_save_settings')) {
            $this->save_settings();
        }
        
        $settings = get_option('vrs_settings', array());
        
        include_once plugin_dir_path(__FILE__) . 'templates/settings.php';
    }
    
    /**
     * Handle reservation actions
     */
    private function handle_reservation_actions() {
        $action = sanitize_text_field($_POST['action']);
        $reservation_id = intval($_POST['reservation_id']);
        
        switch ($action) {
            case 'confirm_reservation':
                $this->confirm_reservation($reservation_id);
                break;
                
            case 'cancel_reservation':
                $this->cancel_reservation($reservation_id);
                break;
                
            case 'delete_reservation':
                $this->delete_reservation($reservation_id);
                break;
        }
    }
    
    /**
     * Confirm reservation
     */
    private function confirm_reservation($reservation_id) {
        global $wpdb;
        
        $result = $wpdb->update(
            $wpdb->prefix . 'vrs_reservations',
            array('status' => 'confirmed'),
            array('id' => $reservation_id),
            array('%s'),
            array('%d')
        );
        
        if ($result) {
            do_action('vrs_reservation_confirmed', $reservation_id);
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success"><p>' . __('Rezervasyon onaylandı.', 'villa-reservation-system') . '</p></div>';
            });
        }
    }
    
    /**
     * Cancel reservation
     */
    private function cancel_reservation($reservation_id) {
        global $wpdb;
        
        $result = $wpdb->update(
            $wpdb->prefix . 'vrs_reservations',
            array('status' => 'cancelled'),
            array('id' => $reservation_id),
            array('%s'),
            array('%d')
        );
        
        if ($result) {
            do_action('vrs_reservation_cancelled', $reservation_id);
            add_action('admin_notices', function() {
                echo '<div class="notice notice-warning"><p>' . __('Rezervasyon iptal edildi.', 'villa-reservation-system') . '</p></div>';
            });
        }
    }
    
    /**
     * Delete reservation
     */
    private function delete_reservation($reservation_id) {
        global $wpdb;
        
        // Delete guest info first
        $wpdb->delete(
            $wpdb->prefix . 'vrs_guest_info',
            array('reservation_id' => $reservation_id),
            array('%d')
        );
        
        // Delete reservation
        $result = $wpdb->delete(
            $wpdb->prefix . 'vrs_reservations',
            array('id' => $reservation_id),
            array('%d')
        );
        
        if ($result) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success"><p>' . __('Rezervasyon silindi.', 'villa-reservation-system') . '</p></div>';
            });
        }
    }
    
    /**
     * Save settings
     */
    private function save_settings() {
        $settings = array(
            'email_from_name' => sanitize_text_field($_POST['email_from_name']),
            'email_from_email' => sanitize_email($_POST['email_from_email']),
            'admin_email' => sanitize_email($_POST['admin_email']),
            'default_checkin_time' => sanitize_text_field($_POST['default_checkin_time']),
            'default_checkout_time' => sanitize_text_field($_POST['default_checkout_time']),
            'currency_symbol' => sanitize_text_field($_POST['currency_symbol']),
            'date_format' => sanitize_text_field($_POST['date_format']),
            'time_format' => sanitize_text_field($_POST['time_format']),
            'google_sheets_sync_interval' => intval($_POST['google_sheets_sync_interval']),
            'reservation_expiry_minutes' => intval($_POST['reservation_expiry_minutes']),
            'allow_same_day_booking' => isset($_POST['allow_same_day_booking']),
            'require_phone' => isset($_POST['require_phone']),
            'show_calendar_on_villa_page' => isset($_POST['show_calendar_on_villa_page']),
            'enable_email_notifications' => isset($_POST['enable_email_notifications']),
            'enable_sms_notifications' => isset($_POST['enable_sms_notifications']),
        );
        
        update_option('vrs_settings', $settings);
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success"><p>' . __('Ayarlar kaydedildi.', 'villa-reservation-system') . '</p></div>';
        });
    }
    
    /**
     * Handle admin AJAX requests
     */
    public function handle_admin_ajax() {
        check_ajax_referer('vrs_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Yetkiniz yok.', 'villa-reservation-system'));
        }
        
        $action = sanitize_text_field($_POST['admin_action']);
        
        switch ($action) {
            case 'block_dates':
                $this->ajax_block_dates();
                break;
                
            case 'unblock_dates':
                $this->ajax_unblock_dates();
                break;
                
            case 'get_reservation_details':
                $this->ajax_get_reservation_details();
                break;
                
            case 'sync_google_sheets':
                $this->ajax_sync_google_sheets();
                break;
                
            default:
                wp_send_json_error(array('message' => __('Geçersiz işlem.', 'villa-reservation-system')));
        }
    }
    
    /**
     * AJAX: Block dates
     */
    private function ajax_block_dates() {
        $villa_id = intval($_POST['villa_id']);
        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);
        $reason = sanitize_text_field($_POST['reason']);
        
        global $wpdb;
        
        $start = strtotime($start_date);
        $end = strtotime($end_date);
        
        for ($date = $start; $date <= $end; $date += 24 * 60 * 60) {
            $current_date = date('Y-m-d', $date);
            
            $wpdb->replace(
                $wpdb->prefix . 'vrs_blocked_dates',
                array(
                    'villa_id' => $villa_id,
                    'blocked_date' => $current_date,
                    'reason' => $reason,
                    'created_at' => current_time('mysql')
                ),
                array('%d', '%s', '%s', '%s')
            );
        }
        
        wp_send_json_success(array('message' => __('Tarihler bloke edildi.', 'villa-reservation-system')));
    }
    
    /**
     * AJAX: Unblock dates
     */
    private function ajax_unblock_dates() {
        $villa_id = intval($_POST['villa_id']);
        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);
        
        global $wpdb;
        
        $wpdb->query($wpdb->prepare("
            DELETE FROM {$wpdb->prefix}vrs_blocked_dates
            WHERE villa_id = %d
            AND blocked_date BETWEEN %s AND %s
        ", $villa_id, $start_date, $end_date));
        
        wp_send_json_success(array('message' => __('Tarihler açıldı.', 'villa-reservation-system')));
    }
    
    /**
     * AJAX: Get reservation details
     */
    private function ajax_get_reservation_details() {
        $reservation_id = intval($_POST['reservation_id']);
        
        $reservation = VRS_Reservations::get_reservation($reservation_id);
        
        if ($reservation) {
            wp_send_json_success($reservation);
        } else {
            wp_send_json_error(array('message' => __('Rezervasyon bulunamadı.', 'villa-reservation-system')));
        }
    }
    
    /**
     * AJAX: Sync Google Sheets
     */
    private function ajax_sync_google_sheets() {
        $villa_id = intval($_POST['villa_id']);
        
        if (class_exists('VRS_Google_Sheets')) {
            $google_sheets = new VRS_Google_Sheets();
            $result = $google_sheets->sync_villa($villa_id);
            
            if ($result) {
                wp_send_json_success(array('message' => __('Google Sheets senkronizasyonu tamamlandı.', 'villa-reservation-system')));
            } else {
                wp_send_json_error(array('message' => __('Senkronizasyon hatası oluştu.', 'villa-reservation-system')));
            }
        } else {
            wp_send_json_error(array('message' => __('Google Sheets sınıfı bulunamadı.', 'villa-reservation-system')));
        }
    }
}

// Initialize admin pages
new VRS_Admin_Pages();