<?php
/**
 * Villa Reservation System - Admin Pages
 * 
 * @package VillaReservationSystem
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Villa Reservation System Admin Pages
 */
class VRS_Admin_Pages {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_init', array($this, 'admin_init'));
    }
    
    /**
     * Admin menu oluştur
     */
    public function add_admin_menu() {
        // Ana menü
        add_menu_page(
            __('Villa Rezervasyon', 'villa-reservation-system'),
            __('Villa Rezervasyon', 'villa-reservation-system'),
            'manage_options',
            'villa-reservations',
            array($this, 'main_page'),
            'dashicons-calendar-alt',
            25
        );
        
        // Alt menüler
        add_submenu_page(
            'villa-reservations',
            __('Genel Bakış', 'villa-reservation-system'),
            __('Genel Bakış', 'villa-reservation-system'),
            'manage_options',
            'villa-reservations',
            array($this, 'main_page')
        );
        
        add_submenu_page(
            'villa-reservations',
            __('Tüm Villalar', 'villa-reservation-system'),
            __('Tüm Villalar', 'villa-reservation-system'),
            'edit_posts',
            'edit.php?post_type=villa'
        );
        
        add_submenu_page(
            'villa-reservations',
            __('Villa Ekle', 'villa-reservation-system'),
            __('Villa Ekle', 'villa-reservation-system'),
            'edit_posts',
            'post-new.php?post_type=villa'
        );
        
        add_submenu_page(
            'villa-reservations',
            __('Ayarlar', 'villa-reservation-system'),
            __('Ayarlar', 'villa-reservation-system'),
            'manage_options',
            'villa-reservation-settings',
            array($this, 'settings_page')
        );
        
        add_submenu_page(
            'villa-reservations',
            __('Raporlar', 'villa-reservation-system'),
            __('Raporlar', 'villa-reservation-system'),
            'manage_options',
            'villa-reservation-reports',
            array($this, 'reports_page')
        );
    }
    
    /**
     * Admin scripts enqueue
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'villa-reservation') !== false || get_post_type() === 'villa') {
            wp_enqueue_script('jquery-ui-datepicker');
            wp_enqueue_style('jquery-ui-style', 'https://code.jquery.com/ui/1.12.1/themes/ui-lightness/jquery-ui.css');
            
            wp_enqueue_style(
                'vrs-admin-styles',
                VRS_PLUGIN_URL . 'admin/admin-styles.css',
                array(),
                VRS_VERSION
            );
            
            wp_enqueue_script(
                'vrs-admin-script',
                VRS_PLUGIN_URL . 'admin/admin-script.js',
                array('jquery', 'jquery-ui-datepicker'),
                VRS_VERSION,
                true
            );
            
            wp_localize_script('vrs-admin-script', 'vrsAdmin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('vrs_admin_nonce'),
                'strings' => array(
                    'confirm_delete' => __('Bu işlemi geri alamazsınız. Emin misiniz?', 'villa-reservation-system'),
                    'loading' => __('Yükleniyor...', 'villa-reservation-system'),
                    'error' => __('Bir hata oluştu.', 'villa-reservation-system'),
                    'success' => __('İşlem başarıyla tamamlandı.', 'villa-reservation-system'),
                )
            ));
        }
    }
    
    /**
     * Admin init
     */
    public function admin_init() {
        // Settings
        register_setting('vrs_settings', 'vrs_general_settings');
        register_setting('vrs_settings', 'vrs_email_settings');
        register_setting('vrs_settings', 'vrs_google_settings');
        
        // Settings sections
        add_settings_section(
            'vrs_general_section',
            __('Genel Ayarlar', 'villa-reservation-system'),
            array($this, 'general_section_callback'),
            'vrs_general_settings'
        );
        
        add_settings_section(
            'vrs_email_section',
            __('E-posta Ayarları', 'villa-reservation-system'),
            array($this, 'email_section_callback'),
            'vrs_email_settings'
        );
        
        add_settings_section(
            'vrs_google_section',
            __('Google Sheets Ayarları', 'villa-reservation-system'),
            array($this, 'google_section_callback'),
            'vrs_google_settings'
        );
        
        // Settings fields
        $this->add_settings_fields();
    }
    
    /**
     * Ana sayfa
     */
    public function main_page() {
        ?>
        <div class="wrap vrs-admin-page">
            <h1><?php _e('Villa Rezervasyon Sistemi', 'villa-reservation-system'); ?></h1>
            
            <?php $this->show_dashboard_stats(); ?>
            
            <div class="vrs-admin-card">
                <h2><?php _e('Son Rezervasyonlar', 'villa-reservation-system'); ?></h2>
                <?php $this->show_recent_reservations(); ?>
            </div>
            
            <div class="vrs-admin-card">
                <h2><?php _e('Hızlı İşlemler', 'villa-reservation-system'); ?></h2>
                <?php $this->show_quick_actions(); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Ayarlar sayfası
     */
    public function settings_page() {
        if (isset($_POST['submit'])) {
            $this->save_settings();
        }
        ?>
        <div class="wrap vrs-admin-page">
            <h1><?php _e('Villa Rezervasyon Ayarları', 'villa-reservation-system'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('vrs_settings_nonce'); ?>
                
                <div class="vrs-settings-section">
                    <h2><?php _e('Genel Ayarlar', 'villa-reservation-system'); ?></h2>
                    <div class="vrs-admin-card">
                        <?php $this->render_general_settings(); ?>
                    </div>
                </div>
                
                <div class="vrs-settings-section">
                    <h2><?php _e('E-posta Ayarları', 'villa-reservation-system'); ?></h2>
                    <div class="vrs-admin-card">
                        <?php $this->render_email_settings(); ?>
                    </div>
                </div>
                
                <div class="vrs-settings-section">
                    <h2><?php _e('Google Sheets Ayarları', 'villa-reservation-system'); ?></h2>
                    <div class="vrs-admin-card">
                        <?php $this->render_google_settings(); ?>
                    </div>
                </div>
                
                <?php submit_button(__('Ayarları Kaydet', 'villa-reservation-system')); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Raporlar sayfası
     */
    public function reports_page() {
        ?>
        <div class="wrap vrs-admin-page">
            <h1><?php _e('Rezervasyon Raporları', 'villa-reservation-system'); ?></h1>
            
            <?php $this->show_reports_filters(); ?>
            <?php $this->show_reports_content(); ?>
        </div>
        <?php
    }
    
    /**
     * Dashboard istatistikleri
     */
    private function show_dashboard_stats() {
        global $wpdb;
        
        // Bugünkü rezervasyonlar
        $today_reservations = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}vrs_reservations 
            WHERE DATE(created_at) = %s
        ", date('Y-m-d')));
        
        // Bu ayki rezervasyonlar
        $month_reservations = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}vrs_reservations 
            WHERE YEAR(created_at) = %d AND MONTH(created_at) = %d
        ", date('Y'), date('n')));
        
        // Toplam rezervasyonlar
        $total_reservations = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->prefix}vrs_reservations
        ");
        
        // Aktif villalar
        $active_villas = wp_count_posts('villa')->publish;
        
        // Bu ayki gelir
        $month_revenue = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(total_price) FROM {$wpdb->prefix}vrs_reservations 
            WHERE status = 'confirmed'
            AND YEAR(created_at) = %d AND MONTH(created_at) = %d
        ", date('Y'), date('n')));
        
        ?>
        <div class="vrs-stats-grid">
            <div class="vrs-stat-widget">
                <span class="vrs-stat-number"><?php echo $today_reservations; ?></span>
                <div class="vrs-stat-label"><?php _e('Bugünkü Rezervasyonlar', 'villa-reservation-system'); ?></div>
            </div>
            <div class="vrs-stat-widget">
                <span class="vrs-stat-number"><?php echo $month_reservations; ?></span>
                <div class="vrs-stat-label"><?php _e('Bu Ayki Rezervasyonlar', 'villa-reservation-system'); ?></div>
            </div>
            <div class="vrs-stat-widget">
                <span class="vrs-stat-number"><?php echo $total_reservations; ?></span>
                <div class="vrs-stat-label"><?php _e('Toplam Rezervasyonlar', 'villa-reservation-system'); ?></div>
            </div>
            <div class="vrs-stat-widget">
                <span class="vrs-stat-number"><?php echo $active_villas; ?></span>
                <div class="vrs-stat-label"><?php _e('Aktif Villalar', 'villa-reservation-system'); ?></div>
            </div>
            <div class="vrs-stat-widget">
                <span class="vrs-stat-number"><?php echo number_format($month_revenue, 2); ?> ₺</span>
                <div class="vrs-stat-label"><?php _e('Bu Ayki Gelir', 'villa-reservation-system'); ?></div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Son rezervasyonları göster
     */
    private function show_recent_reservations() {
        global $wpdb;
        
        $reservations = $wpdb->get_results("
            SELECT r.*, p.post_title as villa_name, gi.guest_name, gi.guest_email
            FROM {$wpdb->prefix}vrs_reservations r
            LEFT JOIN {$wpdb->posts} p ON r.villa_id = p.ID
            LEFT JOIN {$wpdb->prefix}vrs_guest_info gi ON r.id = gi.reservation_id
            ORDER BY r.created_at DESC
            LIMIT 10
        ");
        
        if (empty($reservations)) {
            echo '<p>' . __('Henüz rezervasyon bulunmuyor.', 'villa-reservation-system') . '</p>';
            return;
        }
        
        ?>
        <table class="vrs-reservations-table">
            <thead>
                <tr>
                    <th><?php _e('Villa', 'villa-reservation-system'); ?></th>
                    <th><?php _e('Misafir', 'villa-reservation-system'); ?></th>
                    <th><?php _e('Check-in', 'villa-reservation-system'); ?></th>
                    <th><?php _e('Check-out', 'villa-reservation-system'); ?></th>
                    <th><?php _e('Durum', 'villa-reservation-system'); ?></th>
                    <th><?php _e('Tutar', 'villa-reservation-system'); ?></th>
                    <th><?php _e('İşlemler', 'villa-reservation-system'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reservations as $reservation): ?>
                <tr>
                    <td><?php echo esc_html($reservation->villa_name); ?></td>
                    <td><?php echo esc_html($reservation->guest_name); ?></td>
                    <td><?php echo date_i18n('d.m.Y', strtotime($reservation->checkin_date)); ?></td>
                    <td><?php echo date_i18n('d.m.Y', strtotime($reservation->checkout_date)); ?></td>
                    <td>
                        <span class="vrs-status-badge vrs-status-<?php echo esc_attr($reservation->status); ?>">
                            <?php echo $this->get_status_label($reservation->status); ?>
                        </span>
                    </td>
                    <td><?php echo number_format($reservation->total_price, 2); ?> ₺</td>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=villa-reservations&action=view&id=' . $reservation->id); ?>" 
                           class="vrs-button vrs-button-primary"><?php _e('Görüntüle', 'villa-reservation-system'); ?></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
    
    /**
     * Hızlı işlemler
     */
    private function show_quick_actions() {
        ?>
        <div class="vrs-quick-actions" style="display: flex; gap: 15px; flex-wrap: wrap;">
            <a href="<?php echo admin_url('post-new.php?post_type=villa'); ?>" class="vrs-button vrs-button-primary">
                <?php _e('Yeni Villa Ekle', 'villa-reservation-system'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=villa-reservation-calendar'); ?>" class="vrs-button vrs-button-secondary">
                <?php _e('Takvim Görünümü', 'villa-reservation-system'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=villa-reservation-reports'); ?>" class="vrs-button vrs-button-secondary">
                <?php _e('Raporları Görüntüle', 'villa-reservation-system'); ?>
            </a>
            <button type="button" class="vrs-button vrs-button-success" id="sync-all-sheets">
                <?php _e('Tüm Sheets\'i Senkronize Et', 'villa-reservation-system'); ?>
            </button>
        </div>
        <?php
    }
    
    /**
     * Genel ayarlar render
     */
    private function render_general_settings() {
        $settings = get_option('vrs_general_settings', array());
        ?>
        <div class="vrs-form-grid">
            <div class="vrs-form-group">
                <label for="default_checkin_time"><?php _e('Varsayılan Check-in Saati', 'villa-reservation-system'); ?></label>
                <input type="time" name="vrs_general_settings[default_checkin_time]" 
                       value="<?php echo esc_attr($settings['default_checkin_time'] ?? '15:00'); ?>" />
            </div>
            
            <div class="vrs-form-group">
                <label for="default_checkout_time"><?php _e('Varsayılan Check-out Saati', 'villa-reservation-system'); ?></label>
                <input type="time" name="vrs_general_settings[default_checkout_time]" 
                       value="<?php echo esc_attr($settings['default_checkout_time'] ?? '11:00'); ?>" />
            </div>
            
            <div class="vrs-form-group">
                <label for="min_stay_days"><?php _e('Minimum Konaklama (Gün)', 'villa-reservation-system'); ?></label>
                <input type="number" name="vrs_general_settings[min_stay_days]" 
                       value="<?php echo esc_attr($settings['min_stay_days'] ?? '2'); ?>" min="1" />
            </div>
            
            <div class="vrs-form-group">
                <label for="max_stay_days"><?php _e('Maksimum Konaklama (Gün)', 'villa-reservation-system'); ?></label>
                <input type="number" name="vrs_general_settings[max_stay_days]" 
                       value="<?php echo esc_attr($settings['max_stay_days'] ?? '30'); ?>" min="1" />
            </div>
            
            <div class="vrs-form-group">
                <label for="advance_booking_days"><?php _e('Kaç Gün Önceden Rezervasyon', 'villa-reservation-system'); ?></label>
                <input type="number" name="vrs_general_settings[advance_booking_days]" 
                       value="<?php echo esc_attr($settings['advance_booking_days'] ?? '365'); ?>" min="1" />
            </div>
            
            <div class="vrs-form-group">
                <label for="cancellation_hours"><?php _e('İptal Süresi (Saat)', 'villa-reservation-system'); ?></label>
                <input type="number" name="vrs_general_settings[cancellation_hours]" 
                       value="<?php echo esc_attr($settings['cancellation_hours'] ?? '24'); ?>" min="1" />
                <div class="description"><?php _e('Check-in tarihinden kaç saat öncesine kadar iptal edilebilir.', 'villa-reservation-system'); ?></div>
            </div>
        </div>
        <?php
    }
    
    /**
     * E-posta ayarları render
     */
    private function render_email_settings() {
        $settings = get_option('vrs_email_settings', array());
        ?>
        <div class="vrs-form-grid">
            <div class="vrs-form-group">
                <label for="from_name"><?php _e('Gönderen Adı', 'villa-reservation-system'); ?></label>
                <input type="text" name="vrs_email_settings[from_name]" 
                       value="<?php echo esc_attr($settings['from_name'] ?? get_bloginfo('name')); ?>" />
            </div>
            
            <div class="vrs-form-group">
                <label for="from_email"><?php _e('Gönderen E-posta', 'villa-reservation-system'); ?></label>
                <input type="email" name="vrs_email_settings[from_email]" 
                       value="<?php echo esc_attr($settings['from_email'] ?? get_option('admin_email')); ?>" />
            </div>
            
            <div class="vrs-form-group">
                <label for="admin_email"><?php _e('Yönetici E-posta', 'villa-reservation-system'); ?></label>
                <input type="email" name="vrs_email_settings[admin_email]" 
                       value="<?php echo esc_attr($settings['admin_email'] ?? get_option('admin_email')); ?>" />
            </div>
            
            <div class="vrs-form-group">
                <label for="send_confirmation"><?php _e('Onay E-postası Gönder', 'villa-reservation-system'); ?></label>
                <input type="checkbox" name="vrs_email_settings[send_confirmation]" 
                       value="1" <?php checked($settings['send_confirmation'] ?? '1', '1'); ?> />
            </div>
            
            <div class="vrs-form-group">
                <label for="send_reminder"><?php _e('Hatırlatma E-postası Gönder', 'villa-reservation-system'); ?></label>
                <input type="checkbox" name="vrs_email_settings[send_reminder]" 
                       value="1" <?php checked($settings['send_reminder'] ?? '1', '1'); ?> />
            </div>
            
            <div class="vrs-form-group">
                <label for="reminder_days"><?php _e('Hatırlatma Günleri', 'villa-reservation-system'); ?></label>
                <input type="text" name="vrs_email_settings[reminder_days]" 
                       value="<?php echo esc_attr($settings['reminder_days'] ?? '3,1'); ?>" />
                <div class="description"><?php _e('Check-in tarihinden kaç gün önce hatırlatma gönderilsin (virgülle ayırın).', 'villa-reservation-system'); ?></div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Google ayarları render
     */
    private function render_google_settings() {
        $settings = get_option('vrs_google_settings', array());
        ?>
        <div class="vrs-form-grid">
            <div class="vrs-form-group">
                <label for="auto_sync"><?php _e('Otomatik Senkronizasyon', 'villa-reservation-system'); ?></label>
                <input type="checkbox" name="vrs_google_settings[auto_sync]" 
                       value="1" <?php checked($settings['auto_sync'] ?? '1', '1'); ?> />
                <div class="description"><?php _e('Google Sheets ile otomatik senkronizasyon yapılsın.', 'villa-reservation-system'); ?></div>
            </div>
            
            <div class="vrs-form-group">
                <label for="sync_interval"><?php _e('Senkronizasyon Aralığı', 'villa-reservation-system'); ?></label>
                <select name="vrs_google_settings[sync_interval]">
                    <option value="hourly" <?php selected($settings['sync_interval'] ?? 'daily', 'hourly'); ?>><?php _e('Saatlik', 'villa-reservation-system'); ?></option>
                    <option value="daily" <?php selected($settings['sync_interval'] ?? 'daily', 'daily'); ?>><?php _e('Günlük', 'villa-reservation-system'); ?></option>
                    <option value="weekly" <?php selected($settings['sync_interval'] ?? 'daily', 'weekly'); ?>><?php _e('Haftalık', 'villa-reservation-system'); ?></option>
                </select>
            </div>
            
            <div class="vrs-form-group">
                <label for="color_reservations"><?php _e('Rezervasyonları Renklendir', 'villa-reservation-system'); ?></label>
                <input type="checkbox" name="vrs_google_settings[color_reservations]" 
                       value="1" <?php checked($settings['color_reservations'] ?? '1', '1'); ?> />
                <div class="description"><?php _e('Rezerve tarihleri Google Sheets\'te renklendir.', 'villa-reservation-system'); ?></div>
            </div>
            
            <div class="vrs-form-group">
                <label for="reservation_color"><?php _e('Rezervasyon Rengi', 'villa-reservation-system'); ?></label>
                <input type="color" name="vrs_google_settings[reservation_color]" 
                       value="<?php echo esc_attr($settings['reservation_color'] ?? '#ff0000'); ?>" />
            </div>
        </div>
        
        <div style="margin-top: 20px;">
            <h3><?php _e('Service Account JSON', 'villa-reservation-system'); ?></h3>
            <div class="vrs-credentials-upload">
                <input type="file" name="service_account_json" accept=".json" />
                <p><?php _e('Google Service Account JSON dosyasını yükleyin.', 'villa-reservation-system'); ?></p>
                <?php if (!empty($settings['service_account_file'])): ?>
                    <p><strong><?php _e('Mevcut dosya:', 'villa-reservation-system'); ?></strong> <?php echo esc_html($settings['service_account_file']); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Rapor filtreleri
     */
    private function show_reports_filters() {
        ?>
        <div class="vrs-filters">
            <h3><?php _e('Filtreler', 'villa-reservation-system'); ?></h3>
            <form method="get" action="">
                <input type="hidden" name="page" value="villa-reservation-reports" />
                <div class="vrs-filter-row">
                    <div class="vrs-filter-group">
                        <label for="date_from"><?php _e('Başlangıç Tarihi', 'villa-reservation-system'); ?></label>
                        <input type="date" name="date_from" value="<?php echo esc_attr($_GET['date_from'] ?? ''); ?>" />
                    </div>
                    <div class="vrs-filter-group">
                        <label for="date_to"><?php _e('Bitiş Tarihi', 'villa-reservation-system'); ?></label>
                        <input type="date" name="date_to" value="<?php echo esc_attr($_GET['date_to'] ?? ''); ?>" />
                    </div>
                    <div class="vrs-filter-group">
                        <label for="villa_id"><?php _e('Villa', 'villa-reservation-system'); ?></label>
                        <select name="villa_id">
                            <option value=""><?php _e('Tüm Villalar', 'villa-reservation-system'); ?></option>
                            <?php
                            $villas = get_posts(array('post_type' => 'villa', 'numberposts' => -1));
                            foreach ($villas as $villa) {
                                echo '<option value="' . $villa->ID . '" ' . selected($_GET['villa_id'] ?? '', $villa->ID, false) . '>' . esc_html($villa->post_title) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="vrs-filter-group">
                        <label for="status"><?php _e('Durum', 'villa-reservation-system'); ?></label>
                        <select name="status">
                            <option value=""><?php _e('Tüm Durumlar', 'villa-reservation-system'); ?></option>
                            <option value="pending" <?php selected($_GET['status'] ?? '', 'pending'); ?>><?php _e('Beklemede', 'villa-reservation-system'); ?></option>
                            <option value="confirmed" <?php selected($_GET['status'] ?? '', 'confirmed'); ?>><?php _e('Onaylandı', 'villa-reservation-system'); ?></option>
                            <option value="cancelled" <?php selected($_GET['status'] ?? '', 'cancelled'); ?>><?php _e('İptal Edildi', 'villa-reservation-system'); ?></option>
                        </select>
                    </div>
                    <div class="vrs-filter-group">
                        <button type="submit" class="vrs-button vrs-button-primary"><?php _e('Filtrele', 'villa-reservation-system'); ?></button>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }
    
    /**
     * Rapor içeriği
     */
    private function show_reports_content() {
        global $wpdb;
        
        // Filter değerlerini al
        $date_from = $_GET['date_from'] ?? date('Y-m-01');
        $date_to = $_GET['date_to'] ?? date('Y-m-t');
        $villa_id = $_GET['villa_id'] ?? '';
        $status = $_GET['status'] ?? '';
        
        // WHERE clause oluştur
        $where_conditions = array("r.created_at BETWEEN %s AND %s");
        $where_values = array($date_from . ' 00:00:00', $date_to . ' 23:59:59');
        
        if ($villa_id) {
            $where_conditions[] = "r.villa_id = %d";
            $where_values[] = $villa_id;
        }
        
        if ($status) {
            $where_conditions[] = "r.status = %s";
            $where_values[] = $status;
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        // Toplam istatistikler
        $total_reservations = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}vrs_reservations r
            WHERE $where_clause
        ", $where_values));
        
        $total_revenue = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(total_price) FROM {$wpdb->prefix}vrs_reservations r
            WHERE $where_clause AND status = 'confirmed'
        ", $where_values));
        
        $avg_nights = $wpdb->get_var($wpdb->prepare("
            SELECT AVG(DATEDIFF(checkout_date, checkin_date)) FROM {$wpdb->prefix}vrs_reservations r
            WHERE $where_clause
        ", $where_values));
        
        ?>
        <div class="vrs-stats-grid">
            <div class="vrs-stat-widget">
                <span class="vrs-stat-number"><?php echo $total_reservations; ?></span>
                <div class="vrs-stat-label"><?php _e('Toplam Rezervasyon', 'villa-reservation-system'); ?></div>
            </div>
            <div class="vrs-stat-widget">
                <span class="vrs-stat-number"><?php echo number_format($total_revenue, 2); ?> ₺</span>
                <div class="vrs-stat-label"><?php _e('Toplam Gelir', 'villa-reservation-system'); ?></div>
            </div>
            <div class="vrs-stat-widget">
                <span class="vrs-stat-number"><?php echo number_format($avg_nights, 1); ?></span>
                <div class="vrs-stat-label"><?php _e('Ortalama Gece Sayısı', 'villa-reservation-system'); ?></div>
            </div>
        </div>
        
        <div class="vrs-admin-card">
            <h2><?php _e('Detaylı Rezervasyon Listesi', 'villa-reservation-system'); ?></h2>
            <?php
            $reservations = $wpdb->get_results($wpdb->prepare("
                SELECT r.*, p.post_title as villa_name, gi.guest_name, gi.guest_email
                FROM {$wpdb->prefix}vrs_reservations r
                LEFT JOIN {$wpdb->posts} p ON r.villa_id = p.ID
                LEFT JOIN {$wpdb->prefix}vrs_guest_info gi ON r.id = gi.reservation_id
                WHERE $where_clause
                ORDER BY r.created_at DESC
            ", $where_values));
            
            if (!empty($reservations)) {
                ?>
                <table class="vrs-reservations-table">
                    <thead>
                        <tr>
                            <th><?php _e('Villa', 'villa-reservation-system'); ?></th>
                            <th><?php _e('Misafir', 'villa-reservation-system'); ?></th>
                            <th><?php _e('Check-in', 'villa-reservation-system'); ?></th>
                            <th><?php _e('Check-out', 'villa-reservation-system'); ?></th>
                            <th><?php _e('Gece', 'villa-reservation-system'); ?></th>
                            <th><?php _e('Misafir Sayısı', 'villa-reservation-system'); ?></th>
                            <th><?php _e('Durum', 'villa-reservation-system'); ?></th>
                            <th><?php _e('Tutar', 'villa-reservation-system'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $reservation): ?>
                        <tr>
                            <td><?php echo esc_html($reservation->villa_name); ?></td>
                            <td><?php echo esc_html($reservation->guest_name); ?></td>
                            <td><?php echo date_i18n('d.m.Y', strtotime($reservation->checkin_date)); ?></td>
                            <td><?php echo date_i18n('d.m.Y', strtotime($reservation->checkout_date)); ?></td>
                            <td><?php echo $this->calculate_nights($reservation->checkin_date, $reservation->checkout_date); ?></td>
                            <td><?php echo ($reservation->adults + $reservation->children); ?></td>
                            <td>
                                <span class="vrs-status-badge vrs-status-<?php echo esc_attr($reservation->status); ?>">
                                    <?php echo $this->get_status_label($reservation->status); ?>
                                </span>
                            </td>
                            <td><?php echo number_format($reservation->total_price, 2); ?> ₺</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php
            } else {
                echo '<p>' . __('Belirtilen kriterlere uygun rezervasyon bulunamadı.', 'villa-reservation-system') . '</p>';
            }
            ?>
        </div>
        <?php
    }
    
    /**
     * Ayarları kaydet
     */
    private function save_settings() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'vrs_settings_nonce')) {
            return;
        }
        
        if (isset($_POST['vrs_general_settings'])) {
            update_option('vrs_general_settings', $_POST['vrs_general_settings']);
        }
        
        if (isset($_POST['vrs_email_settings'])) {
            update_option('vrs_email_settings', $_POST['vrs_email_settings']);
        }
        
        if (isset($_POST['vrs_google_settings'])) {
            update_option('vrs_google_settings', $_POST['vrs_google_settings']);
        }
        
        // Service account dosyası yükleme
        if (isset($_FILES['service_account_json']) && $_FILES['service_account_json']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = wp_upload_dir();
            $vrs_dir = $upload_dir['basedir'] . '/villa-reservation-system';
            
            if (!file_exists($vrs_dir)) {
                wp_mkdir_p($vrs_dir);
            }
            
            $file_name = 'service-account.json';
            $file_path = $vrs_dir . '/' . $file_name;
            
            if (move_uploaded_file($_FILES['service_account_json']['tmp_name'], $file_path)) {
                $settings = get_option('vrs_google_settings', array());
                $settings['service_account_file'] = $file_name;
                update_option('vrs_google_settings', $settings);
            }
        }
        
        echo '<div class="vrs-admin-notice success"><p>' . __('Ayarlar başarıyla kaydedildi.', 'villa-reservation-system') . '</p></div>';
    }
    
    /**
     * Durum etiketi al
     */
    private function get_status_label($status) {
        $labels = array(
            'pending' => __('Beklemede', 'villa-reservation-system'),
            'confirmed' => __('Onaylandı', 'villa-reservation-system'),
            'cancelled' => __('İptal Edildi', 'villa-reservation-system'),
            'completed' => __('Tamamlandı', 'villa-reservation-system')
        );
        
        return $labels[$status] ?? $status;
    }
    
    /**
     * Gece sayısını hesapla
     */
    private function calculate_nights($checkin, $checkout) {
        $checkin_date = new DateTime($checkin);
        $checkout_date = new DateTime($checkout);
        return $checkin_date->diff($checkout_date)->days;
    }
    
    /**
     * Settings sections callbacks
     */
    public function general_section_callback() {
        echo '<p>' . __('Rezervasyon sistemi için genel ayarları yapılandırın.', 'villa-reservation-system') . '</p>';
    }
    
    public function email_section_callback() {
        echo '<p>' . __('E-posta bildirimlerini yapılandırın.', 'villa-reservation-system') . '</p>';
    }
    
    public function google_section_callback() {
        echo '<p>' . __('Google Sheets entegrasyonunu yapılandırın.', 'villa-reservation-system') . '</p>';
    }
    
    /**
     * Settings fields ekle
     */
    private function add_settings_fields() {
        // Bu method settings fields eklemek için kullanılabilir
        // Şimdilik boş bırakıyoruz çünkü custom render kullanıyoruz
    }
}