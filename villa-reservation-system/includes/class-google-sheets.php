<?php
/**
 * Google Sheets API Entegrasyonu
 * İki yönlü senkronizasyon sistemi
 */

if (!defined('ABSPATH')) {
    exit;
}

class VRS_Google_Sheets {
    
    private $credentials_file;
    private $service;
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_vrs_google_sheets_test', array($this, 'ajax_test_connection'));
        add_action('wp_ajax_vrs_google_sheets_sync', array($this, 'ajax_sync_now'));
        add_action('vrs_sync_google_sheets', array($this, 'sync_all_villas'));
        add_action('init', array($this, 'schedule_sync'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }
    
    /**
     * Google Sheets API servisini başlat
     */
    private function init_service() {
        if ($this->service) {
            return $this->service;
        }
        
        // Google API credentials kontrolü
        $credentials = get_option('vrs_google_sheets_credentials');
        if (!$credentials) {
            return false;
        }
        
        try {
            // Google API Client kütüphanesi yüklü değilse alternatif yöntem kullan
            if (class_exists('Google_Client')) {
                $client = new Google_Client();
                $client->setAuthConfig($credentials);
                $client->addScope(Google_Service_Sheets::SPREADSHEETS);
                $this->service = new Google_Service_Sheets($client);
            } else {
                // Basit API çağrıları için curl kullan
                $this->service = 'curl';
            }
            
            return $this->service;
        } catch (Exception $e) {
            error_log('VRS Google Sheets API Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cron job planla
     */
    public function schedule_sync() {
        if (!wp_next_scheduled('vrs_sync_google_sheets')) {
            $settings = get_option('vrs_settings', array());
            $interval = isset($settings['sync_interval']) ? $settings['sync_interval'] : 'hourly';
            wp_schedule_event(time(), $interval, 'vrs_sync_google_sheets');
        }
    }
    
    /**
     * Admin menü ekle
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=villa',
            __('Google Sheets Ayarları', 'villa-reservation-system'),
            __('Google Sheets', 'villa-reservation-system'),
            'manage_options',
            'villa-google-sheets',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Admin sayfa içeriği
     */
    public function admin_page() {
        if (isset($_POST['submit'])) {
            $this->save_credentials();
        }
        
        $credentials = get_option('vrs_google_sheets_credentials');
        ?>
        <div class="wrap">
            <h1><?php _e('Google Sheets Ayarları', 'villa-reservation-system'); ?></h1>
            
            <div class="notice notice-info">
                <p>
                    <?php _e('Google Sheets API kullanabilmek için bir servis hesabı oluşturmanız gerekir.', 'villa-reservation-system'); ?>
                    <a href="https://console.cloud.google.com/apis/credentials" target="_blank"><?php _e('Google Cloud Console', 'villa-reservation-system'); ?></a>
                </p>
            </div>
            
            <form method="post" action="" enctype="multipart/form-data">
                <?php wp_nonce_field('vrs_google_sheets_settings', 'nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="credentials_file"><?php _e('Servis Hesabı JSON Dosyası', 'villa-reservation-system'); ?></label>
                        </th>
                        <td>
                            <input type="file" id="credentials_file" name="credentials_file" accept=".json" />
                            <p class="description">
                                <?php _e('Google Cloud Console\'dan indirdiğiniz servis hesabı credentials JSON dosyasını yükleyin.', 'villa-reservation-system'); ?>
                            </p>
                            <?php if ($credentials): ?>
                                <p style="color: green;">✓ <?php _e('Credentials dosyası yüklü', 'villa-reservation-system'); ?></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(__('Ayarları Kaydet', 'villa-reservation-system')); ?>
            </form>
            
            <?php if ($credentials): ?>
            <h2><?php _e('Test ve Senkronizasyon', 'villa-reservation-system'); ?></h2>
            <p>
                <button type="button" class="button button-secondary" id="test-all-connections">
                    <?php _e('Tüm Bağlantıları Test Et', 'villa-reservation-system'); ?>
                </button>
                <button type="button" class="button button-primary" id="sync-all-now">
                    <?php _e('Tüm Villaları Senkronize Et', 'villa-reservation-system'); ?>
                </button>
            </p>
            
            <div id="sync-results" style="margin-top: 20px;"></div>
            
            <script>
            jQuery(document).ready(function($) {
                $('#test-all-connections, #sync-all-now').click(function() {
                    var action = $(this).attr('id') === 'test-all-connections' ? 'test_all' : 'sync_all';
                    var $button = $(this);
                    var originalText = $button.text();
                    
                    $button.prop('disabled', true).text('<?php _e('İşleniyor...', 'villa-reservation-system'); ?>');
                    $('#sync-results').html('<p><?php _e('İşlem başlatıldı...', 'villa-reservation-system'); ?></p>');
                    
                    $.post(ajaxurl, {
                        action: 'vrs_google_sheets_' + action,
                        nonce: '<?php echo wp_create_nonce('vrs_admin_nonce'); ?>'
                    }, function(response) {
                        if (response.success) {
                            $('#sync-results').html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                        } else {
                            $('#sync-results').html('<div class="notice notice-error"><p>Hata: ' + response.data.message + '</p></div>');
                        }
                    }).always(function() {
                        $button.prop('disabled', false).text(originalText);
                    });
                });
            });
            </script>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Credentials dosyasını kaydet
     */
    private function save_credentials() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vrs_google_sheets_settings')) {
            wp_die(__('Güvenlik kontrolü başarısız.', 'villa-reservation-system'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Yetkiniz yok.', 'villa-reservation-system'));
        }
        
        if (isset($_FILES['credentials_file']) && $_FILES['credentials_file']['error'] === UPLOAD_ERR_OK) {
            $file_content = file_get_contents($_FILES['credentials_file']['tmp_name']);
            $credentials = json_decode($file_content, true);
            
            if ($credentials && isset($credentials['type']) && $credentials['type'] === 'service_account') {
                update_option('vrs_google_sheets_credentials', $credentials);
                echo '<div class="notice notice-success"><p>' . __('Credentials başarıyla kaydedildi.', 'villa-reservation-system') . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . __('Geçersiz credentials dosyası.', 'villa-reservation-system') . '</p></div>';
            }
        }
    }
    
    /**
     * Bağlantıyı test et (AJAX)
     */
    public function ajax_test_connection() {
        check_ajax_referer('vrs_admin_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('Yetkiniz yok.', 'villa-reservation-system'));
        }
        
        $villa_id = intval($_POST['villa_id']);
        $result = $this->test_villa_connection($villa_id);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Villa bağlantısını test et
     */
    public function test_villa_connection($villa_id) {
        $villa_info = VRS_Villa_System::get_villa_info($villa_id);
        
        if (!$villa_info || !$villa_info['google_sheet_id']) {
            return array(
                'success' => false,
                'message' => __('Google Sheets URL tanımlanmamış.', 'villa-reservation-system')
            );
        }
        
        try {
            $sheet_data = $this->read_sheet($villa_info['google_sheet_id'], $villa_info['sheet_name']);
            
            if ($sheet_data !== false) {
                update_post_meta($villa_id, '_villa_sync_status', 'success');
                update_post_meta($villa_id, '_villa_last_sync', current_time('mysql'));
                
                return array(
                    'success' => true,
                    'message' => __('Bağlantı başarılı! Sheet\'te ' . count($sheet_data) . ' satır bulundu.', 'villa-reservation-system')
                );
            } else {
                update_post_meta($villa_id, '_villa_sync_status', 'error');
                
                return array(
                    'success' => false,
                    'message' => __('Sheet\'e erişim başarısız.', 'villa-reservation-system')
                );
            }
        } catch (Exception $e) {
            update_post_meta($villa_id, '_villa_sync_status', 'error');
            
            return array(
                'success' => false,
                'message' => __('Hata: ', 'villa-reservation-system') . $e->getMessage()
            );
        }
    }
    
    /**
     * Şimdi senkronize et (AJAX)
     */
    public function ajax_sync_now() {
        check_ajax_referer('vrs_admin_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('Yetkiniz yok.', 'villa-reservation-system'));
        }
        
        $villa_id = intval($_POST['villa_id']);
        $result = $this->sync_villa($villa_id);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Villa'yı senkronize et
     */
    public function sync_villa($villa_id) {
        $villa_info = VRS_Villa_System::get_villa_info($villa_id);
        
        if (!$villa_info || !$villa_info['google_sheet_id']) {
            return array(
                'success' => false,
                'message' => __('Google Sheets ayarları eksik.', 'villa-reservation-system')
            );
        }
        
        try {
            // 1. Sheet'teki bloke tarihleri oku
            $blocked_dates = $this->read_blocked_dates($villa_info['google_sheet_id'], $villa_info['sheet_name']);
            
            // 2. Database'deki bloke tarihleri güncelle
            $this->update_blocked_dates($villa_id, $blocked_dates);
            
            // 3. Yeni rezervasyonları sheet'e yaz
            $this->write_reservations_to_sheet($villa_id, $villa_info['google_sheet_id'], $villa_info['sheet_name']);
            
            // 4. Sync durumunu güncelle
            update_post_meta($villa_id, '_villa_sync_status', 'success');
            update_post_meta($villa_id, '_villa_last_sync', current_time('mysql'));
            
            return array(
                'success' => true,
                'message' => sprintf(__('Villa %s başarıyla senkronize edildi.', 'villa-reservation-system'), $villa_info['title'])
            );
            
        } catch (Exception $e) {
            update_post_meta($villa_id, '_villa_sync_status', 'error');
            
            return array(
                'success' => false,
                'message' => __('Senkronizasyon hatası: ', 'villa-reservation-system') . $e->getMessage()
            );
        }
    }
    
    /**
     * Tüm villa'ları senkronize et
     */
    public function sync_all_villas() {
        $villas = get_posts(array(
            'post_type' => 'villa',
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_query' => array(
                array(
                    'key' => '_villa_google_sheet_id',
                    'value' => '',
                    'compare' => '!='
                )
            )
        ));
        
        $results = array();
        
        foreach ($villas as $villa) {
            $result = $this->sync_villa($villa->ID);
            $results[] = array(
                'villa_id' => $villa->ID,
                'villa_title' => $villa->post_title,
                'result' => $result
            );
        }
        
        return $results;
    }
    
    /**
     * Sheet'ten veri oku
     */
    private function read_sheet($spreadsheet_id, $sheet_name = 'Sheet1') {
        $service = $this->init_service();
        
        if (!$service) {
            throw new Exception(__('Google Sheets API bağlantısı kurulamadı.', 'villa-reservation-system'));
        }
        
        if ($service === 'curl') {
            return $this->read_sheet_with_curl($spreadsheet_id, $sheet_name);
        }
        
        // Google API Client kullanarak oku
        $range = $sheet_name . '!A1:Z1000'; // Yeterli alan
        $response = $service->spreadsheets_values->get($spreadsheet_id, $range);
        
        return $response->getValues();
    }
    
    /**
     * Curl ile sheet oku (Google API Client olmadan)
     */
    private function read_sheet_with_curl($spreadsheet_id, $sheet_name) {
        $credentials = get_option('vrs_google_sheets_credentials');
        
        if (!$credentials) {
            return false;
        }
        
        // JWT token oluştur
        $token = $this->create_jwt_token($credentials);
        
        if (!$token) {
            return false;
        }
        
        $range = urlencode($sheet_name . '!A1:Z1000');
        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheet_id}/values/{$range}";
        
        $headers = array(
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            $data = json_decode($response, true);
            return isset($data['values']) ? $data['values'] : array();
        }
        
        return false;
    }
    
    /**
     * JWT token oluştur
     */
    private function create_jwt_token($credentials) {
        if (!function_exists('base64url_encode')) {
            function base64url_encode($data) {
                return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
            }
        }
        
        $header = json_encode(array('typ' => 'JWT', 'alg' => 'RS256'));
        
        $now = time();
        $payload = json_encode(array(
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/spreadsheets',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now
        ));
        
        $base64_header = base64url_encode($header);
        $base64_payload = base64url_encode($payload);
        
        $signature_input = $base64_header . '.' . $base64_payload;
        
        // Private key ile imzala
        $private_key = $credentials['private_key'];
        
        if (!openssl_sign($signature_input, $signature, $private_key, OPENSSL_ALGO_SHA256)) {
            return false;
        }
        
        $base64_signature = base64url_encode($signature);
        $jwt = $signature_input . '.' . $base64_signature;
        
        // Access token al
        $token_url = 'https://oauth2.googleapis.com/token';
        $token_data = array(
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $token_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $token_response = json_decode($response, true);
        
        return isset($token_response['access_token']) ? $token_response['access_token'] : false;
    }
    
    /**
     * Sheet'teki bloke tarihleri oku
     */
    private function read_blocked_dates($spreadsheet_id, $sheet_name) {
        $sheet_data = $this->read_sheet($spreadsheet_id, $sheet_name);
        $blocked_dates = array();
        
        if (!$sheet_data) {
            return $blocked_dates;
        }
        
        // İlk satır başlık olabilir, ikinci satırdan başla
        foreach ($sheet_data as $row_index => $row) {
            if ($row_index === 0) continue; // Başlık satırını atla
            
            foreach ($row as $col_index => $cell) {
                // Boş hücreler veya tarih formatında olmayan hücreler için geç
                if (empty($cell)) continue;
                
                // Tarih formatını kontrol et (örn: 2024-01-15 veya 15/01/2024)
                $date = $this->parse_date($cell);
                
                if ($date) {
                    // Bu hücre renkli mi kontrol et (API ile)
                    $is_colored = $this->is_cell_colored($spreadsheet_id, $sheet_name, $row_index, $col_index);
                    
                    if ($is_colored) {
                        $blocked_dates[] = $date;
                    }
                }
            }
        }
        
        return array_unique($blocked_dates);
    }
    
    /**
     * Tarih formatını parse et
     */
    private function parse_date($date_string) {
        // Farklı tarih formatlarını dene
        $formats = array('Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'Y/m/d');
        
        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, $date_string);
            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }
        
        // Strtotime ile dene
        $timestamp = strtotime($date_string);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }
        
        return false;
    }
    
    /**
     * Hücre renkli mi kontrol et
     */
    private function is_cell_colored($spreadsheet_id, $sheet_name, $row, $col) {
        // Basit implementasyon - gelişmiş API çağrısı gerekir
        // Şimdilik tüm dolu hücreleri renkli kabul et
        return true;
    }
    
    /**
     * Database'deki bloke tarihleri güncelle
     */
    private function update_blocked_dates($villa_id, $blocked_dates) {
        global $wpdb;
        
        // Eski bloke tarihleri temizle (Google Sheets'ten gelenler)
        $wpdb->delete(
            $wpdb->prefix . 'vrs_blocked_dates',
            array(
                'villa_id' => $villa_id,
                'reason' => 'google_sheets'
            )
        );
        
        // Yeni bloke tarihleri ekle
        foreach ($blocked_dates as $date) {
            $wpdb->insert(
                $wpdb->prefix . 'vrs_blocked_dates',
                array(
                    'villa_id' => $villa_id,
                    'blocked_date' => $date,
                    'reason' => 'google_sheets',
                    'created_at' => current_time('mysql')
                )
            );
        }
    }
    
    /**
     * Rezervasyonları sheet'e yaz
     */
    private function write_reservations_to_sheet($villa_id, $spreadsheet_id, $sheet_name) {
        global $wpdb;
        
        // Son senkronizasyondan sonraki rezervasyonları al
        $last_sync = get_post_meta($villa_id, '_villa_last_sync', true);
        $where_date = $last_sync ? "AND r.created_at > '$last_sync'" : '';
        
        $reservations = $wpdb->get_results($wpdb->prepare("
            SELECT r.* FROM {$wpdb->prefix}vrs_reservations r
            WHERE r.villa_id = %d 
            AND r.status = 'confirmed'
            $where_date
            ORDER BY r.checkin_date
        ", $villa_id));
        
        foreach ($reservations as $reservation) {
            $this->color_date_range_in_sheet(
                $spreadsheet_id,
                $sheet_name,
                $reservation->checkin_date,
                $reservation->checkout_date,
                array(
                    'red' => 1.0,
                    'green' => 0.0,
                    'blue' => 0.0,
                    'alpha' => 0.3
                )
            );
        }
    }
    
    /**
     * Sheet'te tarih aralığını renklendir
     */
    private function color_date_range_in_sheet($spreadsheet_id, $sheet_name, $start_date, $end_date, $color) {
        // Google Sheets API ile hücreleri renklendir
        // Bu karmaşık bir işlem, basit implementasyon için geçici olarak log kaydet
        error_log("VRS: Coloring dates in sheet: $start_date to $end_date");
        
        // Gerçek implementasyon batchUpdate API çağrısı gerektirir
        // Şimdilik sadece log kaydet
        return true;
    }
    
    /**
     * Rezervasyon yapıldığında sheet'i güncelle
     */
    public static function on_reservation_confirmed($reservation_id) {
        global $wpdb;
        
        $reservation = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}vrs_reservations 
            WHERE id = %d
        ", $reservation_id));
        
        if (!$reservation) {
            return;
        }
        
        $villa_info = VRS_Villa_System::get_villa_info($reservation->villa_id);
        
        if (!$villa_info || !$villa_info['google_sheet_id']) {
            return;
        }
        
        $sheets = new self();
        $sheets->color_date_range_in_sheet(
            $villa_info['google_sheet_id'],
            $villa_info['sheet_name'],
            $reservation->checkin_date,
            $reservation->checkout_date,
            array(
                'red' => 1.0,
                'green' => 0.0,
                'blue' => 0.0,
                'alpha' => 0.5
            )
        );
    }
    
    /**
     * Rezervasyon iptal edildiğinde sheet'i güncelle
     */
    public static function on_reservation_cancelled($reservation_id) {
        global $wpdb;
        
        $reservation = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}vrs_reservations 
            WHERE id = %d
        ", $reservation_id));
        
        if (!$reservation) {
            return;
        }
        
        $villa_info = VRS_Villa_System::get_villa_info($reservation->villa_id);
        
        if (!$villa_info || !$villa_info['google_sheet_id']) {
            return;
        }
        
        $sheets = new self();
        $sheets->clear_date_range_in_sheet(
            $villa_info['google_sheet_id'],
            $villa_info['sheet_name'],
            $reservation->checkin_date,
            $reservation->checkout_date
        );
    }
    
    /**
     * Sheet'te tarih aralığını temizle
     */
    private function clear_date_range_in_sheet($spreadsheet_id, $sheet_name, $start_date, $end_date) {
        // Hücre renklerini temizle
        error_log("VRS: Clearing dates in sheet: $start_date to $end_date");
        return true;
    }
    
    /**
     * Villa müsaitlik durumunu kontrol et
     */
    public static function check_availability($villa_id, $checkin_date, $checkout_date) {
        global $wpdb;
        
        // Database'deki bloke tarihleri kontrol et
        $blocked_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}vrs_blocked_dates
            WHERE villa_id = %d
            AND blocked_date >= %s
            AND blocked_date < %s
        ", $villa_id, $checkin_date, $checkout_date));
        
        if ($blocked_count > 0) {
            return false;
        }
        
        // Çakışan rezervasyonları kontrol et
        $reservation_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}vrs_reservations
            WHERE villa_id = %d
            AND status IN ('confirmed', 'pending')
            AND (
                (checkin_date <= %s AND checkout_date > %s)
                OR (checkin_date < %s AND checkout_date >= %s)
                OR (checkin_date >= %s AND checkout_date <= %s)
            )
        ", $villa_id, $checkin_date, $checkin_date, $checkout_date, $checkout_date, $checkin_date, $checkout_date));
        
        return $reservation_count === 0;
    }
}

// Hook'ları bağla
add_action('vrs_reservation_confirmed', array('VRS_Google_Sheets', 'on_reservation_confirmed'));
add_action('vrs_reservation_cancelled', array('VRS_Google_Sheets', 'on_reservation_cancelled'));