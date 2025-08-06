<?php
/**
 * Plugin Name: Villa Rezervasyon Sistemi
 * Plugin URI: https://example.com/villa-reservation-system
 * Description: WordPress için profesyonel villa/bungalov rezervasyon sistemi. WooCommerce entegrasyonu, Google Sheets senkronizasyonu ve tam Türkçe desteği.
 * Version: 1.0.0
 * Author: Villa Rezervasyon Sistemi
 * Text Domain: villa-reservation-system
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

// Eklenti sabitleri
define('VRS_PLUGIN_FILE', __FILE__);
define('VRS_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('VRS_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('VRS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VRS_VERSION', '1.0.0');
define('VRS_MINIMUM_WP_VERSION', '5.0');
define('VRS_MINIMUM_WC_VERSION', '5.0');

/**
 * Ana Villa Rezervasyon Sistemi sınıfı
 */
class VillaReservationSystem {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Plugin yüklendiğinde tek sefer çalışır
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        add_action('init', array($this, 'load_textdomain'));
        
        // Activation ve deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        register_uninstall_hook(__FILE__, array('VillaReservationSystem', 'uninstall'));
    }
    
    /**
     * Plugin initialization
     */
    public function init() {
        // WooCommerce kontrolü
        if (!$this->is_woocommerce_active()) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }
        
        // Minimum versiyon kontrolü
        if (!$this->is_wp_version_compatible() || !$this->is_wc_version_compatible()) {
            add_action('admin_notices', array($this, 'version_notice'));
            return;
        }
        
        $this->load_dependencies();
        $this->init_hooks();
        $this->init_classes();
    }
    
    /**
     * Dil dosyalarını yükle
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'villa-reservation-system',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }
    
    /**
     * Bağımlılıkları yükle
     */
    private function load_dependencies() {
        // Core sınıflar
        require_once VRS_PLUGIN_PATH . 'includes/class-villa-system.php';
        require_once VRS_PLUGIN_PATH . 'includes/class-google-sheets.php';
        require_once VRS_PLUGIN_PATH . 'includes/class-reservations.php';
        require_once VRS_PLUGIN_PATH . 'includes/class-email-handler.php';
        require_once VRS_PLUGIN_PATH . 'includes/class-woocommerce-integration.php';
        
        // Admin sınıflar
        if (is_admin()) {
            require_once VRS_PLUGIN_PATH . 'admin/admin-pages.php';
            require_once VRS_PLUGIN_PATH . 'admin/villa-settings.php';
            require_once VRS_PLUGIN_PATH . 'admin/reservation-calendar.php';
        }
    }
    
    /**
     * WordPress hooks'ları başlat
     */
    private function init_hooks() {
        // CSS ve JS yükle
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX hooks
        add_action('wp_ajax_vrs_check_availability', array('VRS_Reservations', 'ajax_check_availability'));
        add_action('wp_ajax_nopriv_vrs_check_availability', array('VRS_Reservations', 'ajax_check_availability'));
        add_action('wp_ajax_vrs_calculate_price', array('VRS_Reservations', 'ajax_calculate_price'));
        add_action('wp_ajax_nopriv_vrs_calculate_price', array('VRS_Reservations', 'ajax_calculate_price'));
        
        // Custom post type
        add_action('init', array('VRS_Villa_System', 'register_villa_post_type'));
        
        // Shortcodes
        add_shortcode('villa_reservation_form', array('VRS_Villa_System', 'reservation_form_shortcode'));
        add_shortcode('villa_calendar', array('VRS_Villa_System', 'calendar_shortcode'));
    }
    
    /**
     * Frontend CSS ve JS dosyalarını yükle
     */
    public function enqueue_public_scripts() {
        wp_enqueue_style(
            'vrs-public-style',
            VRS_PLUGIN_URL . 'public/css/villa-reservation-public.css',
            array(),
            VRS_VERSION
        );
        
        wp_enqueue_script(
            'vrs-public-script',
            VRS_PLUGIN_URL . 'public/js/villa-reservation-public.js',
            array('jquery', 'jquery-ui-datepicker'),
            VRS_VERSION,
            true
        );
        
        // AJAX URL'i frontend'e gönder
        wp_localize_script('vrs-public-script', 'vrs_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vrs_nonce'),
            'i18n' => array(
                'loading' => __('Yükleniyor...', 'villa-reservation-system'),
                'select_dates' => __('Lütfen tarih seçiniz', 'villa-reservation-system'),
                'select_guests' => __('Lütfen misafir sayısını seçiniz', 'villa-reservation-system'),
                'dates_not_available' => __('Seçilen tarihler müsait değil', 'villa-reservation-system'),
                'max_capacity_exceeded' => __('Maksimum kapasite aşıldı', 'villa-reservation-system'),
            )
        ));
        
        // jQuery UI theme
        wp_enqueue_style('jquery-ui-theme', 'https://code.jquery.com/ui/1.13.0/themes/ui-lightness/jquery-ui.css');
    }
    
    /**
     * Admin CSS ve JS dosyalarını yükle
     */
    public function enqueue_admin_scripts($hook) {
        // Sadece villa ile ilgili sayfalarda yükle
        if (strpos($hook, 'villa') === false && get_post_type() !== 'villa') {
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
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vrs_nonce'),
            'testingText' => __('Test ediliyor...', 'villa-reservation-system'),
            'syncingText' => __('Senkronize ediliyor...', 'villa-reservation-system'),
            'savingText' => __('Kaydediliyor...', 'villa-reservation-system'),
            'cancellingText' => __('İptal ediliyor...', 'villa-reservation-system'),
            'loadingCalendarText' => __('Takvim yükleniyor...', 'villa-reservation-system'),
            'connectionSuccessText' => __('Bağlantı testi başarılı.', 'villa-reservation-system'),
            'connectionFailedText' => __('Bağlantı testi başarısız.', 'villa-reservation-system'),
            'syncSuccessText' => __('Senkronizasyon tamamlandı!', 'villa-reservation-system'),
            'syncFailedText' => __('Senkronizasyon başarısız.', 'villa-reservation-system'),
            'settingsUpdatedText' => __('Villa ayarları başarıyla güncellendi.', 'villa-reservation-system'),
            'settingsUpdateFailedText' => __('Ayarlar güncellenemedi.', 'villa-reservation-system'),
            'statusUpdatedText' => __('Rezervasyon durumu güncellendi.', 'villa-reservation-system'),
            'statusUpdateFailedText' => __('Durum güncellenemedi.', 'villa-reservation-system'),
            'cancelSuccessText' => __('Rezervasyon iptal edildi.', 'villa-reservation-system'),
            'cancelFailedText' => __('İptal işlemi başarısız.', 'villa-reservation-system'),
            'calendarLoadFailedText' => __('Takvim yüklenemedi.', 'villa-reservation-system'),
            'calendarLoadErrorText' => __('Takvim yüklenirken hata oluştu.', 'villa-reservation-system'),
            'dateBlockedText' => __('Tarih bloke edildi.', 'villa-reservation-system'),
            'dateBlockFailedText' => __('Tarih bloke edilemedi.', 'villa-reservation-system'),
            'dateBlockErrorText' => __('Tarih bloke edilirken hata oluştu.', 'villa-reservation-system'),
            'dateUnblockedText' => __('Tarih blokesi kaldırıldı.', 'villa-reservation-system'),
            'dateUnblockFailedText' => __('Tarih blokesi kaldırılamadı.', 'villa-reservation-system'),
            'dateUnblockErrorText' => __('Tarih blokesi kaldırılırken hata oluştu.', 'villa-reservation-system'),
            'ajaxErrorText' => __('Bir hata oluştu. Lütfen tekrar deneyin.', 'villa-reservation-system'),
            'selectVillaText' => __('Lütfen takvim görünümü için bir villa seçin.', 'villa-reservation-system'),
            'selectFileText' => __('Lütfen bir dosya seçin.', 'villa-reservation-system'),
            'invalidFileTypeText' => __('Lütfen geçerli bir JSON dosyası seçin.', 'villa-reservation-system'),
            'selectReservationsText' => __('Lütfen en az bir rezervasyon seçin.', 'villa-reservation-system'),
            'reservationsUpdatedText' => __('Rezervasyonlar güncellendi.', 'villa-reservation-system'),
            'confirmStatusChangeText' => __('Rezervasyon durumunu değiştirmek istediğinizden emin misiniz?', 'villa-reservation-system'),
            'confirmCancelText' => __('Bu rezervasyonu iptal etmek istediğinizden emin misiniz?', 'villa-reservation-system'),
            'confirmBulkCancelText' => __('Seçili rezervasyonları iptal etmek istediğinizden emin misiniz?', 'villa-reservation-system'),
            'statusLabels' => array(
                'pending' => __('Beklemede', 'villa-reservation-system'),
                'confirmed' => __('Onaylandı', 'villa-reservation-system'),
                'cancelled' => __('İptal Edildi', 'villa-reservation-system'),
                'completed' => __('Tamamlandı', 'villa-reservation-system')
            )
        ));
    }
    
    /**
     * WooCommerce aktif mi kontrol et
     */
    private function is_woocommerce_active() {
        return in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')));
    }
    
    /**
     * WordPress versiyon uyumluluğu
     */
    private function is_wp_version_compatible() {
        return version_compare(get_bloginfo('version'), VRS_MINIMUM_WP_VERSION, '>=');
    }
    
    /**
     * WooCommerce versiyon uyumluluğu
     */
    private function is_wc_version_compatible() {
        if (!function_exists('WC')) {
            return false;
        }
        return version_compare(WC()->version, VRS_MINIMUM_WC_VERSION, '>=');
    }
    
    /**
     * WooCommerce eksik uyarısı
     */
    public function woocommerce_missing_notice() {
        echo '<div class="error"><p>';
        echo sprintf(
            __('Villa Rezervasyon Sistemi eklentisi çalışmak için %s gerektirir.', 'villa-reservation-system'),
            '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>'
        );
        echo '</p></div>';
    }
    
    /**
     * Versiyon uyarısı
     */
    public function version_notice() {
        echo '<div class="error"><p>';
        echo sprintf(
            __('Villa Rezervasyon Sistemi en az WordPress %s ve WooCommerce %s gerektirir.', 'villa-reservation-system'),
            VRS_MINIMUM_WP_VERSION,
            VRS_MINIMUM_WC_VERSION
        );
        echo '</p></div>';
    }
    
    /**
     * Plugin aktif edildiğinde çalışır
     */
    public function activate() {
        // Database tablolarını oluştur
        $this->create_tables();
        
        // Default ayarları kaydet
        $this->set_default_options();
        
        // Rewrite rules'ı temizle
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deaktif edildiğinde çalışır
     */
    public function deactivate() {
        // Rewrite rules'ı temizle
        flush_rewrite_rules();
        
        // Cron jobs'ları temizle
        wp_clear_scheduled_hook('vrs_sync_google_sheets');
        wp_clear_scheduled_hook('vrs_send_reminder_emails');
    }
    
    /**
     * Plugin silindiğinde çalışır
     */
    public static function uninstall() {
        // Tabloları sil
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'vrs_reservations',
            $wpdb->prefix . 'vrs_villa_settings',
            $wpdb->prefix . 'vrs_blocked_dates',
            $wpdb->prefix . 'vrs_pricing',
            $wpdb->prefix . 'vrs_guest_info',
            $wpdb->prefix . 'vrs_email_log',
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        // Options'ları sil
        delete_option('vrs_version');
        delete_option('vrs_settings');
        delete_option('vrs_google_sheets_credentials');
        
        // Meta fields'ları sil
        delete_post_meta_by_key('_villa_max_capacity');
        delete_post_meta_by_key('_villa_max_adults');
        delete_post_meta_by_key('_villa_max_children');
        delete_post_meta_by_key('_villa_google_sheet_url');
        delete_post_meta_by_key('_villa_pricing');
        delete_post_meta_by_key('_villa_minimum_stay');
        delete_post_meta_by_key('_villa_maximum_stay');
    }
    
    /**
     * Database tablolarını oluştur
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Rezervasyonlar tablosu
        $table_name = $wpdb->prefix . 'vrs_reservations';
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            villa_id bigint(20) NOT NULL,
            order_id bigint(20) DEFAULT NULL,
            user_id bigint(20) DEFAULT NULL,
            checkin_date date NOT NULL,
            checkout_date date NOT NULL,
            adults tinyint(3) NOT NULL DEFAULT 0,
            children tinyint(3) NOT NULL DEFAULT 0,
            children_ages varchar(255) DEFAULT NULL,
            special_requests text,
            total_price decimal(10,2) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY villa_id (villa_id),
            KEY order_id (order_id),
            KEY user_id (user_id),
            KEY checkin_date (checkin_date),
            KEY checkout_date (checkout_date),
            KEY status (status)
        ) $charset_collate;";
        
        // Villa ayarları tablosu
        $table_name2 = $wpdb->prefix . 'vrs_villa_settings';
        $sql2 = "CREATE TABLE $table_name2 (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            villa_id bigint(20) NOT NULL,
            setting_key varchar(255) NOT NULL,
            setting_value longtext,
            PRIMARY KEY (id),
            UNIQUE KEY villa_setting (villa_id, setting_key)
        ) $charset_collate;";
        
        // Bloke tarihler tablosu
        $table_name3 = $wpdb->prefix . 'vrs_blocked_dates';
        $sql3 = "CREATE TABLE $table_name3 (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            villa_id bigint(20) NOT NULL,
            blocked_date date NOT NULL,
            reason varchar(255) DEFAULT NULL,
            created_by bigint(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY villa_date (villa_id, blocked_date),
            KEY villa_id (villa_id),
            KEY blocked_date (blocked_date)
        ) $charset_collate;";
        
        // Fiyatlandırma tablosu
        $table_name4 = $wpdb->prefix . 'vrs_pricing';
        $sql4 = "CREATE TABLE $table_name4 (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            villa_id bigint(20) NOT NULL,
            date_from date DEFAULT NULL,
            date_to date DEFAULT NULL,
            adult_price decimal(10,2) NOT NULL DEFAULT 0,
            child_price decimal(10,2) NOT NULL DEFAULT 0,
            base_price decimal(10,2) NOT NULL DEFAULT 0,
            min_guests tinyint(3) NOT NULL DEFAULT 1,
            max_guests tinyint(3) NOT NULL DEFAULT 10,
            priority tinyint(3) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY villa_id (villa_id),
            KEY date_range (date_from, date_to)
        ) $charset_collate;";
        
        // Misafir bilgileri tablosu
        $table_name5 = $wpdb->prefix . 'vrs_guest_info';
        $sql5 = "CREATE TABLE $table_name5 (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            reservation_id bigint(20) NOT NULL,
            name varchar(255) NOT NULL,
            email varchar(255) NOT NULL,
            phone varchar(50) DEFAULT NULL,
            address text DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY reservation_guest (reservation_id),
            KEY email (email)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        dbDelta($sql2);
        dbDelta($sql3);
        dbDelta($sql4);
        dbDelta($sql5);
        
        // E-posta log tablosu
        $table_name6 = $wpdb->prefix . 'vrs_email_log';
        $sql6 = "CREATE TABLE $table_name6 (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            reservation_id bigint(20) NOT NULL,
            email_type varchar(50) NOT NULL,
            sent_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY reservation_id (reservation_id),
            KEY email_type (email_type),
            KEY sent_date (sent_date)
        ) $charset_collate;";
        
        dbDelta($sql6);
        
        // Versiyon bilgisini kaydet
        add_option('vrs_version', VRS_VERSION);
    }
    
    /**
     * Default ayarları kaydet
     */
    private function set_default_options() {
        $default_settings = array(
            'currency' => 'TRY',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
            'default_checkin_time' => '15:00',
            'default_checkout_time' => '11:00',
            'admin_email_notifications' => 'yes',
            'customer_email_notifications' => 'yes',
            'google_sheets_sync' => 'yes',
            'sync_interval' => 'hourly',
        );
        
        add_option('vrs_settings', $default_settings);
    }
    
    /**
     * Sınıfları başlat
     */
    private function init_classes() {
        new VRS_Villa_System();
        new VRS_Google_Sheets();
        new VRS_Reservations();
        new VRS_WooCommerce_Integration();
        new VRS_Email_Handler();
    }
}

// Plugin'i başlat
VillaReservationSystem::get_instance();