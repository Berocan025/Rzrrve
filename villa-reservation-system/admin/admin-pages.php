<?php
/**
 * Admin Pages Handler
 * 
 * @package VillaReservationSystem
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class VRS_Admin_Pages {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_vrs_admin_stats', array($this, 'ajax_get_admin_stats'));
        add_action('wp_ajax_vrs_export_reservations', array($this, 'ajax_export_reservations'));
        add_action('wp_ajax_vrs_manual_sync', array($this, 'ajax_manual_sync'));
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
            'villa-reservation',
            array($this, 'dashboard_page'),
            'dashicons-calendar-alt',
            25
        );
        
        // Dashboard (same as main page)
        add_submenu_page(
            'villa-reservation',
            __('Dashboard', 'villa-reservation-system'),
            __('Dashboard', 'villa-reservation-system'),
            'manage_options',
            'villa-reservation',
            array($this, 'dashboard_page')
        );
        
        // Settings page
        add_submenu_page(
            'villa-reservation',
            __('Ayarlar', 'villa-reservation-system'),
            __('Ayarlar', 'villa-reservation-system'),
            'manage_options',
            'villa-reservation-settings',
            array($this, 'settings_page')
        );
        
        // Reports page
        add_submenu_page(
            'villa-reservation',
            __('Raporlar', 'villa-reservation-system'),
            __('Raporlar', 'villa-reservation-system'),
            'manage_options',
            'villa-reservation-reports',
            array($this, 'reports_page')
        );
        
        // Tools page
        add_submenu_page(
            'villa-reservation',
            __('Araçlar', 'villa-reservation-system'),
            __('Araçlar', 'villa-reservation-system'),
            'manage_options',
            'villa-reservation-tools',
            array($this, 'tools_page')
        );
    }
    
    /**
     * Initialize admin settings
     */
    public function admin_init() {
        register_setting('vrs_settings', 'vrs_general_settings');
        register_setting('vrs_settings', 'vrs_email_settings');
        register_setting('vrs_settings', 'vrs_google_settings');
        
        // General settings section
        add_settings_section(
            'vrs_general',
            __('Genel Ayarlar', 'villa-reservation-system'),
            array($this, 'general_section_callback'),
            'vrs_settings'
        );
        
        // Email settings section
        add_settings_section(
            'vrs_email',
            __('E-posta Ayarları', 'villa-reservation-system'),
            array($this, 'email_section_callback'),
            'vrs_settings'
        );
        
        // Google Sheets settings section
        add_settings_section(
            'vrs_google',
            __('Google Sheets Ayarları', 'villa-reservation-system'),
            array($this, 'google_section_callback'),
            'vrs_settings'
        );
        
        // Add settings fields
        $this->add_settings_fields();
    }
    
    /**
     * Add settings fields
     */
    private function add_settings_fields() {
        // General settings
        add_settings_field(
            'default_checkin_time',
            __('Varsayılan Check-in Saati', 'villa-reservation-system'),
            array($this, 'time_field_callback'),
            'vrs_settings',
            'vrs_general',
            array('field' => 'default_checkin_time', 'default' => '15:00')
        );
        
        add_settings_field(
            'default_checkout_time',
            __('Varsayılan Check-out Saati', 'villa-reservation-system'),
            array($this, 'time_field_callback'),
            'vrs_settings',
            'vrs_general',
            array('field' => 'default_checkout_time', 'default' => '11:00')
        );
        
        add_settings_field(
            'reservation_expiry_hours',
            __('Rezervasyon Süresi (Saat)', 'villa-reservation-system'),
            array($this, 'number_field_callback'),
            'vrs_settings',
            'vrs_general',
            array('field' => 'reservation_expiry_hours', 'default' => 24, 'min' => 1, 'max' => 168)
        );
        
        add_settings_field(
            'cancellation_policy_hours',
            __('İptal Politikası (Saat)', 'villa-reservation-system'),
            array($this, 'number_field_callback'),
            'vrs_settings',
            'vrs_general',
            array('field' => 'cancellation_policy_hours', 'default' => 24, 'min' => 0, 'max' => 720)
        );
        
        // Email settings
        add_settings_field(
            'admin_email',
            __('Yönetici E-posta', 'villa-reservation-system'),
            array($this, 'email_field_callback'),
            'vrs_settings',
            'vrs_email',
            array('field' => 'admin_email', 'default' => get_option('admin_email'))
        );
        
        add_settings_field(
            'from_name',
            __('Gönderen Adı', 'villa-reservation-system'),
            array($this, 'text_field_callback'),
            'vrs_settings',
            'vrs_email',
            array('field' => 'from_name', 'default' => get_bloginfo('name'))
        );
        
        add_settings_field(
            'from_email',
            __('Gönderen E-posta', 'villa-reservation-system'),
            array($this, 'email_field_callback'),
            'vrs_settings',
            'vrs_email',
            array('field' => 'from_email', 'default' => get_option('admin_email'))
        );
        
        add_settings_field(
            'send_reminders',
            __('Hatırlatma E-postaları Gönder', 'villa-reservation-system'),
            array($this, 'checkbox_field_callback'),
            'vrs_settings',
            'vrs_email',
            array('field' => 'send_reminders', 'default' => 1)
        );
        
        // Google Sheets settings
        add_settings_field(
            'sync_frequency',
            __('Senkronizasyon Sıklığı', 'villa-reservation-system'),
            array($this, 'select_field_callback'),
            'vrs_settings',
            'vrs_google',
            array(
                'field' => 'sync_frequency',
                'default' => 'daily',
                'options' => array(
                    'hourly' => __('Saatlik', 'villa-reservation-system'),
                    'daily' => __('Günlük', 'villa-reservation-system'),
                    'weekly' => __('Haftalık', 'villa-reservation-system'),
                )
            )
        );
        
        add_settings_field(
            'auto_sync',
            __('Otomatik Senkronizasyon', 'villa-reservation-system'),
            array($this, 'checkbox_field_callback'),
            'vrs_settings',
            'vrs_google',
            array('field' => 'auto_sync', 'default' => 1)
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'villa-reservation') === false) {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1', true);
        
        wp_enqueue_script(
            'vrs-admin',
            VRS_PLUGIN_URL . 'admin/js/admin.js',
            array('jquery', 'jquery-ui-datepicker', 'chart-js'),
            VRS_VERSION,
            true
        );
        
        wp_enqueue_style(
            'vrs-admin',
            VRS_PLUGIN_URL . 'admin/css/admin.css',
            array(),
            VRS_VERSION
        );
        
        wp_enqueue_style('jquery-ui-theme', 'https://code.jquery.com/ui/1.13.2/themes/ui-lightness/jquery-ui.css');
        
        wp_localize_script('vrs-admin', 'vrsAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vrs_admin_nonce'),
            'strings' => array(
                'loading' => __('Yükleniyor...', 'villa-reservation-system'),
                'error' => __('Bir hata oluştu.', 'villa-reservation-system'),
                'success' => __('İşlem başarılı.', 'villa-reservation-system'),
                'confirm_delete' => __('Bu işlemi geri alamazsınız. Emin misiniz?', 'villa-reservation-system'),
                'sync_success' => __('Senkronizasyon tamamlandı.', 'villa-reservation-system'),
                'export_success' => __('Dışa aktarma başlatıldı.', 'villa-reservation-system'),
            )
        ));
    }
    
    /**
     * Dashboard page
     */
    public function dashboard_page() {
        global $wpdb;
        
        // Get statistics
        $stats = $this->get_dashboard_stats();
        
        ?>
        <div class="wrap villa-admin-page">
            <div class="admin-header">
                <h1><?php _e('Villa Rezervasyon Dashboard', 'villa-reservation-system'); ?></h1>
                <button class="button button-primary" id="refresh-stats">
                    <?php _e('Verileri Yenile', 'villa-reservation-system'); ?>
                </button>
            </div>
            
            <div class="admin-stats">
                <div class="stat-card">
                    <div class="stat-number"><?php echo esc_html($stats['total_villas']); ?></div>
                    <div class="stat-label"><?php _e('Toplam Villa', 'villa-reservation-system'); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo esc_html($stats['total_reservations']); ?></div>
                    <div class="stat-label"><?php _e('Toplam Rezervasyon', 'villa-reservation-system'); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo esc_html($stats['pending_reservations']); ?></div>
                    <div class="stat-label"><?php _e('Bekleyen Rezervasyon', 'villa-reservation-system'); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo esc_html($stats['this_month_revenue']); ?> ₺</div>
                    <div class="stat-label"><?php _e('Bu Ay Gelir', 'villa-reservation-system'); ?></div>
                </div>
            </div>
            
            <div class="admin-content">
                <div class="admin-row">
                    <div class="admin-col-8">
                        <div class="admin-card">
                            <h3><?php _e('Rezervasyon Grafiği', 'villa-reservation-system'); ?></h3>
                            <canvas id="reservationChart" width="400" height="200"></canvas>
                        </div>
                    </div>
                    <div class="admin-col-4">
                        <div class="admin-card">
                            <h3><?php _e('Son Rezervasyonlar', 'villa-reservation-system'); ?></h3>
                            <?php $this->recent_reservations_widget(); ?>
                        </div>
                    </div>
                </div>
                
                <div class="admin-row">
                    <div class="admin-col-6">
                        <div class="admin-card">
                            <h3><?php _e('En Popüler Villalar', 'villa-reservation-system'); ?></h3>
                            <?php $this->popular_villas_widget(); ?>
                        </div>
                    </div>
                    <div class="admin-col-6">
                        <div class="admin-card">
                            <h3><?php _e('Google Sheets Durumu', 'villa-reservation-system'); ?></h3>
                            <?php $this->google_sheets_status_widget(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        if (isset($_POST['submit'])) {
            check_admin_referer('vrs_settings_nonce');
            
            // Save settings
            if (isset($_POST['vrs_general_settings'])) {
                update_option('vrs_general_settings', $_POST['vrs_general_settings']);
            }
            if (isset($_POST['vrs_email_settings'])) {
                update_option('vrs_email_settings', $_POST['vrs_email_settings']);
            }
            if (isset($_POST['vrs_google_settings'])) {
                update_option('vrs_google_settings', $_POST['vrs_google_settings']);
            }
            
            echo '<div class="notice notice-success"><p>' . __('Ayarlar kaydedildi.', 'villa-reservation-system') . '</p></div>';
        }
        
        ?>
        <div class="wrap villa-admin-page">
            <h1><?php _e('Villa Rezervasyon Ayarları', 'villa-reservation-system'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('vrs_settings_nonce'); ?>
                <?php settings_fields('vrs_settings'); ?>
                <?php do_settings_sections('vrs_settings'); ?>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Reports page
     */
    public function reports_page() {
        ?>
        <div class="wrap villa-admin-page">
            <div class="admin-header">
                <h1><?php _e('Rezervasyon Raporları', 'villa-reservation-system'); ?></h1>
                <button class="button button-primary" id="export-reservations">
                    <?php _e('Excel\'e Aktar', 'villa-reservation-system'); ?>
                </button>
            </div>
            
            <div class="report-filters">
                <div class="filter-row">
                    <div class="filter-col">
                        <label><?php _e('Başlangıç Tarihi:', 'villa-reservation-system'); ?></label>
                        <input type="date" id="report-start-date" value="<?php echo date('Y-m-01'); ?>">
                    </div>
                    <div class="filter-col">
                        <label><?php _e('Bitiş Tarihi:', 'villa-reservation-system'); ?></label>
                        <input type="date" id="report-end-date" value="<?php echo date('Y-m-t'); ?>">
                    </div>
                    <div class="filter-col">
                        <label><?php _e('Villa:', 'villa-reservation-system'); ?></label>
                        <select id="report-villa">
                            <option value=""><?php _e('Tüm Villalar', 'villa-reservation-system'); ?></option>
                            <?php
                            $villas = get_posts(array(
                                'post_type' => 'villa',
                                'posts_per_page' => -1,
                                'post_status' => 'publish'
                            ));
                            foreach ($villas as $villa) {
                                echo '<option value="' . $villa->ID . '">' . esc_html($villa->post_title) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="filter-col">
                        <button class="button" id="generate-report"><?php _e('Rapor Oluştur', 'villa-reservation-system'); ?></button>
                    </div>
                </div>
            </div>
            
            <div id="report-results">
                <?php $this->default_report_content(); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Tools page
     */
    public function tools_page() {
        ?>
        <div class="wrap villa-admin-page">
            <h1><?php _e('Villa Rezervasyon Araçları', 'villa-reservation-system'); ?></h1>
            
            <div class="tool-section">
                <h3><?php _e('Google Sheets Senkronizasyonu', 'villa-reservation-system'); ?></h3>
                <p><?php _e('Tüm villalar için Google Sheets senkronizasyonunu manuel olarak başlatın.', 'villa-reservation-system'); ?></p>
                <button class="button button-primary" id="manual-sync-all">
                    <?php _e('Tümünü Senkronize Et', 'villa-reservation-system'); ?>
                </button>
                <div id="sync-progress" style="display: none;">
                    <p><?php _e('Senkronizasyon devam ediyor...', 'villa-reservation-system'); ?></p>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 0%"></div>
                    </div>
                </div>
            </div>
            
            <div class="tool-section">
                <h3><?php _e('Veri Temizleme', 'villa-reservation-system'); ?></h3>
                <p><?php _e('Geçmiş rezervasyonları ve gereksiz verileri temizleyin.', 'villa-reservation-system'); ?></p>
                <button class="button" id="cleanup-old-data">
                    <?php _e('Eski Verileri Temizle', 'villa-reservation-system'); ?>
                </button>
            </div>
            
            <div class="tool-section">
                <h3><?php _e('Test Araçları', 'villa-reservation-system'); ?></h3>
                <p><?php _e('E-posta gönderimi ve API bağlantılarını test edin.', 'villa-reservation-system'); ?></p>
                <button class="button" id="test-email">
                    <?php _e('Test E-postası Gönder', 'villa-reservation-system'); ?>
                </button>
                <button class="button" id="test-google-api">
                    <?php _e('Google API Test Et', 'villa-reservation-system'); ?>
                </button>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get dashboard statistics
     */
    private function get_dashboard_stats() {
        global $wpdb;
        
        $stats = array();
        
        // Total villas
        $stats['total_villas'] = wp_count_posts('villa')->publish;
        
        // Total reservations
        $stats['total_reservations'] = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->prefix}vrs_reservations
        ");
        
        // Pending reservations
        $stats['pending_reservations'] = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->prefix}vrs_reservations
            WHERE status = 'pending'
        ");
        
        // This month revenue
        $stats['this_month_revenue'] = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(total_price) FROM {$wpdb->prefix}vrs_reservations
            WHERE status = 'confirmed'
            AND MONTH(created_at) = %d
            AND YEAR(created_at) = %d
        ", date('n'), date('Y')));
        
        $stats['this_month_revenue'] = $stats['this_month_revenue'] ? number_format($stats['this_month_revenue'], 2) : '0.00';
        
        return $stats;
    }
    
    /**
     * Recent reservations widget
     */
    private function recent_reservations_widget() {
        global $wpdb;
        
        $reservations = $wpdb->get_results("
            SELECT r.*, p.post_title as villa_name
            FROM {$wpdb->prefix}vrs_reservations r
            LEFT JOIN {$wpdb->prefix}posts p ON r.villa_id = p.ID
            ORDER BY r.created_at DESC
            LIMIT 5
        ");
        
        if ($reservations) {
            echo '<ul class="recent-reservations-list">';
            foreach ($reservations as $reservation) {
                $status_class = 'status-' . $reservation->status;
                echo '<li class="reservation-item">';
                echo '<div class="reservation-villa">' . esc_html($reservation->villa_name) . '</div>';
                echo '<div class="reservation-guest">' . esc_html($reservation->guest_name) . '</div>';
                echo '<div class="reservation-dates">' . date('d.m.Y', strtotime($reservation->checkin_date)) . ' - ' . date('d.m.Y', strtotime($reservation->checkout_date)) . '</div>';
                echo '<span class="reservation-status ' . $status_class . '">' . ucfirst($reservation->status) . '</span>';
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>' . __('Henüz rezervasyon bulunmuyor.', 'villa-reservation-system') . '</p>';
        }
    }
    
    /**
     * Popular villas widget
     */
    private function popular_villas_widget() {
        global $wpdb;
        
        $popular_villas = $wpdb->get_results("
            SELECT r.villa_id, p.post_title, COUNT(*) as reservation_count
            FROM {$wpdb->prefix}vrs_reservations r
            LEFT JOIN {$wpdb->prefix}posts p ON r.villa_id = p.ID
            WHERE r.status IN ('confirmed', 'pending')
            GROUP BY r.villa_id
            ORDER BY reservation_count DESC
            LIMIT 5
        ");
        
        if ($popular_villas) {
            echo '<ul class="popular-villas-list">';
            foreach ($popular_villas as $villa) {
                echo '<li class="villa-item">';
                echo '<div class="villa-name">' . esc_html($villa->post_title) . '</div>';
                echo '<div class="villa-count">' . $villa->reservation_count . ' rezervasyon</div>';
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>' . __('Henüz veri bulunmuyor.', 'villa-reservation-system') . '</p>';
        }
    }
    
    /**
     * Google Sheets status widget
     */
    private function google_sheets_status_widget() {
        $villas = get_posts(array(
            'post_type' => 'villa',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));
        
        $connected = 0;
        $total = count($villas);
        
        foreach ($villas as $villa) {
            $google_sheet_id = get_post_meta($villa->ID, '_villa_google_sheet_id', true);
            if (!empty($google_sheet_id)) {
                $connected++;
            }
        }
        
        echo '<div class="sync-status">';
        echo '<div class="sync-stat">';
        echo '<span class="stat-number">' . $connected . '/' . $total . '</span>';
        echo '<span class="stat-label">' . __('Bağlı Villa', 'villa-reservation-system') . '</span>';
        echo '</div>';
        
        $last_sync = get_option('vrs_last_sync_time');
        if ($last_sync) {
            echo '<div class="last-sync">';
            echo __('Son Senkronizasyon:', 'villa-reservation-system') . ' ';
            echo date('d.m.Y H:i', strtotime($last_sync));
            echo '</div>';
        }
        echo '</div>';
    }
    
    /**
     * Default report content
     */
    private function default_report_content() {
        global $wpdb;
        
        $current_month_start = date('Y-m-01');
        $current_month_end = date('Y-m-t');
        
        $reservations = $wpdb->get_results($wpdb->prepare("
            SELECT r.*, p.post_title as villa_name
            FROM {$wpdb->prefix}vrs_reservations r
            LEFT JOIN {$wpdb->prefix}posts p ON r.villa_id = p.ID
            WHERE r.checkin_date BETWEEN %s AND %s
            ORDER BY r.checkin_date DESC
        ", $current_month_start, $current_month_end));
        
        echo '<h3>' . __('Bu Ay Rezervasyonları', 'villa-reservation-system') . '</h3>';
        
        if ($reservations) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead>';
            echo '<tr>';
            echo '<th>' . __('Villa', 'villa-reservation-system') . '</th>';
            echo '<th>' . __('Misafir', 'villa-reservation-system') . '</th>';
            echo '<th>' . __('Tarihler', 'villa-reservation-system') . '</th>';
            echo '<th>' . __('Kişi Sayısı', 'villa-reservation-system') . '</th>';
            echo '<th>' . __('Tutar', 'villa-reservation-system') . '</th>';
            echo '<th>' . __('Durum', 'villa-reservation-system') . '</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
            
            foreach ($reservations as $reservation) {
                echo '<tr>';
                echo '<td>' . esc_html($reservation->villa_name) . '</td>';
                echo '<td>' . esc_html($reservation->guest_name) . '</td>';
                echo '<td>' . date('d.m.Y', strtotime($reservation->checkin_date)) . ' - ' . date('d.m.Y', strtotime($reservation->checkout_date)) . '</td>';
                echo '<td>' . $reservation->adults . ' + ' . $reservation->children . '</td>';
                echo '<td>' . number_format($reservation->total_price, 2) . ' ₺</td>';
                echo '<td><span class="status-' . $reservation->status . '">' . ucfirst($reservation->status) . '</span></td>';
                echo '</tr>';
            }
            
            echo '</tbody>';
            echo '</table>';
        } else {
            echo '<p>' . __('Bu ay için rezervasyon bulunmuyor.', 'villa-reservation-system') . '</p>';
        }
    }
    
    // Settings field callbacks
    public function general_section_callback() {
        echo '<p>' . __('Genel rezervasyon sistemi ayarlarını buradan yapılandırabilirsiniz.', 'villa-reservation-system') . '</p>';
    }
    
    public function email_section_callback() {
        echo '<p>' . __('E-posta bildirimleri için ayarları yapılandırın.', 'villa-reservation-system') . '</p>';
    }
    
    public function google_section_callback() {
        echo '<p>' . __('Google Sheets entegrasyonu ayarlarını yapılandırın.', 'villa-reservation-system') . '</p>';
    }
    
    public function text_field_callback($args) {
        $options = get_option('vrs_general_settings');
        $value = isset($options[$args['field']]) ? $options[$args['field']] : $args['default'];
        echo '<input type="text" name="vrs_general_settings[' . $args['field'] . ']" value="' . esc_attr($value) . '" />';
    }
    
    public function email_field_callback($args) {
        $section = strpos($args['field'], 'admin_email') !== false || strpos($args['field'], 'from_') !== false ? 'email' : 'general';
        $options = get_option('vrs_' . $section . '_settings');
        $value = isset($options[$args['field']]) ? $options[$args['field']] : $args['default'];
        echo '<input type="email" name="vrs_' . $section . '_settings[' . $args['field'] . ']" value="' . esc_attr($value) . '" />';
    }
    
    public function number_field_callback($args) {
        $options = get_option('vrs_general_settings');
        $value = isset($options[$args['field']]) ? $options[$args['field']] : $args['default'];
        echo '<input type="number" name="vrs_general_settings[' . $args['field'] . ']" value="' . esc_attr($value) . '" min="' . $args['min'] . '" max="' . $args['max'] . '" />';
    }
    
    public function time_field_callback($args) {
        $options = get_option('vrs_general_settings');
        $value = isset($options[$args['field']]) ? $options[$args['field']] : $args['default'];
        echo '<input type="time" name="vrs_general_settings[' . $args['field'] . ']" value="' . esc_attr($value) . '" />';
    }
    
    public function checkbox_field_callback($args) {
        $section = strpos($args['field'], 'send_reminders') !== false ? 'email' : 'google';
        $options = get_option('vrs_' . $section . '_settings');
        $value = isset($options[$args['field']]) ? $options[$args['field']] : $args['default'];
        echo '<input type="checkbox" name="vrs_' . $section . '_settings[' . $args['field'] . ']" value="1" ' . checked(1, $value, false) . ' />';
    }
    
    public function select_field_callback($args) {
        $options = get_option('vrs_google_settings');
        $value = isset($options[$args['field']]) ? $options[$args['field']] : $args['default'];
        echo '<select name="vrs_google_settings[' . $args['field'] . ']">';
        foreach ($args['options'] as $option_value => $option_label) {
            echo '<option value="' . esc_attr($option_value) . '" ' . selected($value, $option_value, false) . '>' . esc_html($option_label) . '</option>';
        }
        echo '</select>';
    }
    
    // AJAX handlers
    public function ajax_get_admin_stats() {
        check_ajax_referer('vrs_admin_nonce', 'nonce');
        
        $stats = $this->get_dashboard_stats();
        wp_send_json_success($stats);
    }
    
    public function ajax_export_reservations() {
        check_ajax_referer('vrs_admin_nonce', 'nonce');
        
        // Implementation for CSV export would go here
        wp_send_json_success(array(
            'message' => __('Dışa aktarma başlatıldı.', 'villa-reservation-system')
        ));
    }
    
    public function ajax_manual_sync() {
        check_ajax_referer('vrs_admin_nonce', 'nonce');
        
        // Trigger manual sync
        do_action('vrs_sync_google_sheets');
        
        wp_send_json_success(array(
            'message' => __('Senkronizasyon tamamlandı.', 'villa-reservation-system')
        ));
    }
}

// Initialize admin pages
new VRS_Admin_Pages();