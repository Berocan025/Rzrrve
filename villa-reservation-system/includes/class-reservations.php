<?php
/**
 * Rezervasyon Sistemi Core
 * Müsaitlik kontrolü, fiyat hesaplama, rezervasyon yönetimi
 */

if (!defined('ABSPATH')) {
    exit;
}

class VRS_Reservations {
    
    /**
     * Constructor
     */
    public function __construct() {
        // AJAX handlers
        add_action('wp_ajax_vrs_check_availability', array($this, 'ajax_check_availability'));
        add_action('wp_ajax_nopriv_vrs_check_availability', array($this, 'ajax_check_availability'));
        add_action('wp_ajax_vrs_calculate_price', array($this, 'ajax_calculate_price'));
        add_action('wp_ajax_nopriv_vrs_calculate_price', array($this, 'ajax_calculate_price'));
        add_action('wp_ajax_vrs_create_reservation', array($this, 'ajax_create_reservation'));
        add_action('wp_ajax_nopriv_vrs_create_reservation', array($this, 'ajax_create_reservation'));
        add_action('wp_ajax_vrs_cancel_reservation', array($this, 'ajax_cancel_reservation'));
        add_action('wp_ajax_vrs_get_blocked_dates', array($this, 'ajax_get_blocked_dates'));
        add_action('wp_ajax_nopriv_vrs_get_blocked_dates', array($this, 'ajax_get_blocked_dates'));
        
        // User dashboard
        add_action('init', array($this, 'add_rewrite_rules'));
        add_action('template_redirect', array($this, 'handle_reservation_pages'));
        
        // Admin hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_filter('manage_villa_posts_columns', array($this, 'add_villa_columns'));
        add_action('manage_villa_posts_custom_column', array($this, 'populate_villa_columns'), 10, 2);
    }
    
    /**
     * URL rewrite rules ekle
     */
    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^rezervasyonlarim/?$',
            'index.php?vrs_page=my-reservations',
            'top'
        );
        
        add_rewrite_rule(
            '^rezervasyon/([0-9]+)/?$',
            'index.php?vrs_page=reservation-details&reservation_id=$matches[1]',
            'top'
        );
        
        add_rewrite_tag('%vrs_page%', '([^&]+)');
        add_rewrite_tag('%reservation_id%', '([0-9]+)');
    }
    
    /**
     * Rezervasyon sayfalarını handle et
     */
    public function handle_reservation_pages() {
        $vrs_page = get_query_var('vrs_page');
        
        if (!$vrs_page) {
            return;
        }
        
        switch ($vrs_page) {
            case 'my-reservations':
                $this->show_my_reservations_page();
                break;
            case 'reservation-details':
                $this->show_reservation_details_page();
                break;
        }
        
        exit;
    }
    
    /**
     * Admin menü ekle
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=villa',
            __('Rezervasyonlar', 'villa-reservation-system'),
            __('Rezervasyonlar', 'villa-reservation-system'),
            'edit_posts',
            'villa-reservations',
            array($this, 'admin_reservations_page')
        );
        
        add_submenu_page(
            'edit.php?post_type=villa',
            __('Takvim Görünümü', 'villa-reservation-system'),
            __('Takvim', 'villa-reservation-system'),
            'edit_posts',
            'villa-calendar',
            array($this, 'admin_calendar_page')
        );
    }
    
    /**
     * Villa sütunları ekle
     */
    public function add_villa_columns($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $title) {
            $new_columns[$key] = $title;
            
            if ($key === 'title') {
                $new_columns['villa_reservations'] = __('Rezervasyonlar', 'villa-reservation-system');
                $new_columns['villa_availability'] = __('Müsaitlik', 'villa-reservation-system');
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Villa sütun verilerini doldur
     */
    public function populate_villa_columns($column, $post_id) {
        switch ($column) {
            case 'villa_reservations':
                $count = $this->get_villa_reservation_count($post_id);
                echo sprintf(
                    '<a href="%s">%d rezervasyon</a>',
                    admin_url('admin.php?page=villa-reservations&villa_id=' . $post_id),
                    $count
                );
                break;
                
            case 'villa_availability':
                $next_available = $this->get_next_available_date($post_id);
                if ($next_available) {
                    echo sprintf(
                        __('Sonraki müsait: %s', 'villa-reservation-system'),
                        date_i18n('d/m/Y', strtotime($next_available))
                    );
                } else {
                    echo __('Müsait', 'villa-reservation-system');
                }
                break;
        }
    }
    
    /**
     * Müsaitlik kontrolü (AJAX)
     */
    public static function ajax_check_availability() {
        check_ajax_referer('vrs_nonce', 'nonce');
        
        $villa_id = intval($_POST['villa_id']);
        $checkin = sanitize_text_field($_POST['checkin']);
        $checkout = sanitize_text_field($_POST['checkout']);
        $adults = intval($_POST['adults']);
        $children = intval($_POST['children']);
        
        $result = self::check_availability($villa_id, $checkin, $checkout, $adults, $children);
        
        if ($result['available']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Fiyat hesaplama (AJAX)
     */
    public static function ajax_calculate_price() {
        check_ajax_referer('vrs_nonce', 'nonce');
        
        $villa_id = intval($_POST['villa_id']);
        $checkin = sanitize_text_field($_POST['checkin']);
        $checkout = sanitize_text_field($_POST['checkout']);
        $adults = intval($_POST['adults']);
        $children = intval($_POST['children']);
        $children_ages = isset($_POST['children_ages']) ? array_map('intval', $_POST['children_ages']) : array();
        
        $price_data = self::calculate_price($villa_id, $checkin, $checkout, $adults, $children, $children_ages);
        
        if ($price_data) {
            wp_send_json_success($price_data);
        } else {
            wp_send_json_error(array(
                'message' => __('Fiyat hesaplanamadı.', 'villa-reservation-system')
            ));
        }
    }
    
    /**
     * Rezervasyon oluştur (AJAX)
     */
    public function ajax_create_reservation() {
        check_ajax_referer('vrs_nonce', 'nonce');
        
        // Form verilerini al ve doğrula
        $reservation_data = $this->validate_reservation_data($_POST);
        
        if (is_wp_error($reservation_data)) {
            wp_send_json_error(array(
                'message' => $reservation_data->get_error_message()
            ));
        }
        
        // Müsaitlik son kez kontrol et
        $availability = self::check_availability(
            $reservation_data['villa_id'],
            $reservation_data['checkin_date'],
            $reservation_data['checkout_date'],
            $reservation_data['adults'],
            $reservation_data['children']
        );
        
        if (!$availability['available']) {
            wp_send_json_error(array(
                'message' => __('Seçilen tarihler artık müsait değil.', 'villa-reservation-system')
            ));
        }
        
        // Rezervasyon oluştur
        $reservation_id = $this->create_reservation($reservation_data);
        
        if ($reservation_id) {
            wp_send_json_success(array(
                'reservation_id' => $reservation_id,
                'message' => __('Rezervasyon başarıyla oluşturuldu.', 'villa-reservation-system'),
                'redirect_url' => $this->get_checkout_url($reservation_id)
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Rezervasyon oluşturulamadı.', 'villa-reservation-system')
            ));
        }
    }
    
    /**
     * Rezervasyon iptal et (AJAX)
     */
    public function ajax_cancel_reservation() {
        check_ajax_referer('vrs_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('Giriş yapmanız gerekiyor.', 'villa-reservation-system')
            ));
        }
        
        $reservation_id = intval($_POST['reservation_id']);
        $result = $this->cancel_reservation($reservation_id, get_current_user_id());
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Rezervasyon iptal edildi.', 'villa-reservation-system')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Rezervasyon iptal edilemedi.', 'villa-reservation-system')
            ));
        }
    }
    
    /**
     * Müsaitlik kontrolü
     */
    public static function check_availability($villa_id, $checkin_date, $checkout_date, $adults = 1, $children = 0) {
        global $wpdb;
        
        // Tarih formatlarını doğrula
        $checkin = date('Y-m-d', strtotime($checkin_date));
        $checkout = date('Y-m-d', strtotime($checkout_date));
        
        if (!$checkin || !$checkout || $checkin >= $checkout) {
            return array(
                'available' => false,
                'message' => __('Geçersiz tarih aralığı.', 'villa-reservation-system')
            );
        }
        
        // Villa bilgilerini al
        $villa_info = VRS_Villa_System::get_villa_info($villa_id);
        
        if (!$villa_info) {
            return array(
                'available' => false,
                'message' => __('Geçersiz villa.', 'villa-reservation-system')
            );
        }
        
        // Kapasite kontrolü
        $total_guests = $adults + $children;
        if ($total_guests > $villa_info['max_capacity']) {
            return array(
                'available' => false,
                'message' => sprintf(
                    __('Maksimum kapasite %d kişidir.', 'villa-reservation-system'),
                    $villa_info['max_capacity']
                )
            );
        }
        
        if ($adults > $villa_info['max_adults']) {
            return array(
                'available' => false,
                'message' => sprintf(
                    __('Maksimum %d yetişkin kabul edilir.', 'villa-reservation-system'),
                    $villa_info['max_adults']
                )
            );
        }
        
        if ($children > $villa_info['max_children']) {
            return array(
                'available' => false,
                'message' => sprintf(
                    __('Maksimum %d çocuk kabul edilir.', 'villa-reservation-system'),
                    $villa_info['max_children']
                )
            );
        }
        
        // Minimum/maksimum konaklama süresi kontrolü
        $days = (strtotime($checkout) - strtotime($checkin)) / (24 * 60 * 60);
        
        if ($days < $villa_info['minimum_stay']) {
            return array(
                'available' => false,
                'message' => sprintf(
                    __('Minimum %d gün konaklama gereklidir.', 'villa-reservation-system'),
                    $villa_info['minimum_stay']
                )
            );
        }
        
        if ($days > $villa_info['maximum_stay']) {
            return array(
                'available' => false,
                'message' => sprintf(
                    __('Maksimum %d gün konaklama mümkündür.', 'villa-reservation-system'),
                    $villa_info['maximum_stay']
                )
            );
        }
        
        // Bloke tarihler kontrolü
        $blocked_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}vrs_blocked_dates
            WHERE villa_id = %d
            AND blocked_date >= %s
            AND blocked_date < %s
        ", $villa_id, $checkin, $checkout));
        
        if ($blocked_count > 0) {
            return array(
                'available' => false,
                'message' => __('Seçilen tarih aralığında müsait olmayan günler var.', 'villa-reservation-system')
            );
        }
        
        // Çakışan rezervasyonlar kontrolü
        $reservation_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}vrs_reservations
            WHERE villa_id = %d
            AND status IN ('confirmed', 'pending')
            AND (
                (checkin_date <= %s AND checkout_date > %s)
                OR (checkin_date < %s AND checkout_date >= %s)
                OR (checkin_date >= %s AND checkout_date <= %s)
            )
        ", $villa_id, $checkin, $checkin, $checkout, $checkout, $checkin, $checkout));
        
        if ($reservation_count > 0) {
            return array(
                'available' => false,
                'message' => __('Seçilen tarihler için zaten rezervasyon bulunuyor.', 'villa-reservation-system')
            );
        }
        
        // Google Sheets kontrolü
        if (!VRS_Google_Sheets::check_availability($villa_id, $checkin, $checkout)) {
            return array(
                'available' => false,
                'message' => __('Seçilen tarihler Google Sheets\'te bloke olarak işaretli.', 'villa-reservation-system')
            );
        }
        
        return array(
            'available' => true,
            'message' => __('Seçilen tarihler müsait.', 'villa-reservation-system'),
            'days' => $days
        );
    }
    
    /**
     * Fiyat hesaplama
     */
    public static function calculate_price($villa_id, $checkin_date, $checkout_date, $adults = 1, $children = 0, $children_ages = array()) {
        $villa_info = VRS_Villa_System::get_villa_info($villa_id);
        
        if (!$villa_info) {
            return false;
        }
        
        $checkin = date('Y-m-d', strtotime($checkin_date));
        $checkout = date('Y-m-d', strtotime($checkout_date));
        $days = (strtotime($checkout) - strtotime($checkin)) / (24 * 60 * 60);
        
        if ($days <= 0) {
            return false;
        }
        
        $pricing_type = $villa_info['pricing_type'];
        $base_price = floatval($villa_info['base_price']);
        $adult_price = floatval($villa_info['adult_price']);
        $child_price = floatval($villa_info['child_price']);
        $child_discount = floatval($villa_info['child_discount']);
        
        $breakdown = array(
            'base_price' => 0,
            'adult_total' => 0,
            'child_total' => 0,
            'subtotal' => 0,
            'taxes' => 0,
            'total' => 0,
            'days' => $days,
            'daily_rate' => 0
        );
        
        if ($pricing_type === 'fixed') {
            // Sabit fiyat
            $breakdown['base_price'] = $base_price * $days;
            $breakdown['daily_rate'] = $base_price;
        } else {
            // Kişi başı fiyatlama
            $breakdown['adult_total'] = $adult_price * $adults * $days;
            $breakdown['child_total'] = $child_price * $children * $days;
            
            // Çocuk indirimi uygula
            if ($child_discount > 0) {
                $discount_amount = ($breakdown['child_total'] * $child_discount) / 100;
                $breakdown['child_total'] -= $discount_amount;
                $breakdown['child_discount'] = $discount_amount;
            }
            
            // Minimum fiyat kontrolü
            $calculated_total = $breakdown['adult_total'] + $breakdown['child_total'];
            $minimum_total = $base_price * $days;
            
            if ($calculated_total < $minimum_total) {
                $breakdown['base_price'] = $minimum_total;
                $breakdown['adult_total'] = 0;
                $breakdown['child_total'] = 0;
            }
        }
        
        $breakdown['subtotal'] = $breakdown['base_price'] + $breakdown['adult_total'] + $breakdown['child_total'];
        
        // Vergi hesaplama (opsiyonel)
        $tax_rate = get_option('vrs_tax_rate', 0);
        if ($tax_rate > 0) {
            $breakdown['taxes'] = ($breakdown['subtotal'] * $tax_rate) / 100;
        }
        
        $breakdown['total'] = $breakdown['subtotal'] + $breakdown['taxes'];
        
        // Sezon fiyatları ve dinamik fiyatlandırma (gelecek özellik)
        $breakdown = apply_filters('vrs_calculate_price', $breakdown, $villa_id, $checkin, $checkout, $adults, $children);
        
        return $breakdown;
    }
    
    /**
     * Rezervasyon verilerini doğrula
     */
    private function validate_reservation_data($data) {
        $required_fields = array('villa_id', 'checkin_date', 'checkout_date', 'adults', 'guest_name', 'guest_email', 'guest_phone');
        
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return new WP_Error('missing_field', sprintf(__('%s alanı zorunludur.', 'villa-reservation-system'), $field));
            }
        }
        
        $villa_id = intval($data['villa_id']);
        $checkin = sanitize_text_field($data['checkin_date']);
        $checkout = sanitize_text_field($data['checkout_date']);
        $adults = intval($data['adults']);
        $children = intval($data['children']);
        
        // E-posta formatını kontrol et
        if (!is_email($data['guest_email'])) {
            return new WP_Error('invalid_email', __('Geçersiz e-posta adresi.', 'villa-reservation-system'));
        }
        
        // Tarih formatlarını kontrol et
        if (!strtotime($checkin) || !strtotime($checkout)) {
            return new WP_Error('invalid_dates', __('Geçersiz tarih formatı.', 'villa-reservation-system'));
        }
        
        // Geçmiş tarih kontrolü
        if (strtotime($checkin) < strtotime('today')) {
            return new WP_Error('past_date', __('Geçmiş tarih seçilemez.', 'villa-reservation-system'));
        }
        
        return array(
            'villa_id' => $villa_id,
            'checkin_date' => date('Y-m-d', strtotime($checkin)),
            'checkout_date' => date('Y-m-d', strtotime($checkout)),
            'adults' => max(1, $adults),
            'children' => max(0, $children),
            'children_ages' => isset($data['children_ages']) ? array_map('intval', $data['children_ages']) : array(),
            'guest_name' => sanitize_text_field($data['guest_name']),
            'guest_email' => sanitize_email($data['guest_email']),
            'guest_phone' => sanitize_text_field($data['guest_phone']),
            'special_requests' => sanitize_textarea_field($data['special_requests'] ?? ''),
            'user_id' => is_user_logged_in() ? get_current_user_id() : null
        );
    }
    
    /**
     * Rezervasyon oluştur
     */
    public function create_reservation($data) {
        global $wpdb;
        
        // Fiyat hesapla
        $price_data = self::calculate_price(
            $data['villa_id'],
            $data['checkin_date'],
            $data['checkout_date'],
            $data['adults'],
            $data['children'],
            $data['children_ages']
        );
        
        if (!$price_data) {
            return false;
        }
        
        // Rezervasyon kaydı oluştur
        $result = $wpdb->insert(
            $wpdb->prefix . 'vrs_reservations',
            array(
                'villa_id' => $data['villa_id'],
                'user_id' => $data['user_id'],
                'checkin_date' => $data['checkin_date'],
                'checkout_date' => $data['checkout_date'],
                'adults' => $data['adults'],
                'children' => $data['children'],
                'children_ages' => !empty($data['children_ages']) ? implode(',', $data['children_ages']) : null,
                'special_requests' => $data['special_requests'],
                'total_price' => $price_data['total'],
                'status' => 'pending',
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%d', '%d', '%s', '%s', '%f', '%s', '%s')
        );
        
        if (!$result) {
            return false;
        }
        
        $reservation_id = $wpdb->insert_id;
        
        // Misafir bilgilerini kaydet
        $wpdb->insert(
            $wpdb->prefix . 'vrs_guest_info',
            array(
                'reservation_id' => $reservation_id,
                'name' => $data['guest_name'],
                'email' => $data['guest_email'],
                'phone' => $data['guest_phone']
            ),
            array('%d', '%s', '%s', '%s')
        );
        
        // WooCommerce ürünü oluştur (ödeme için)
        $product_id = $this->create_reservation_product($reservation_id, $data, $price_data);
        
        if ($product_id) {
            $wpdb->update(
                $wpdb->prefix . 'vrs_reservations',
                array('product_id' => $product_id),
                array('id' => $reservation_id),
                array('%d'),
                array('%d')
            );
        }
        
        // E-posta gönder
        do_action('vrs_reservation_created', $reservation_id);
        
        return $reservation_id;
    }
    
    /**
     * Rezervasyon için WooCommerce ürünü oluştur
     */
    private function create_reservation_product($reservation_id, $reservation_data, $price_data) {
        if (!class_exists('WC_Product_Simple')) {
            return false;
        }
        
        $villa_info = VRS_Villa_System::get_villa_info($reservation_data['villa_id']);
        
        $product = new WC_Product_Simple();
        $product->set_name(sprintf(
            __('Villa Rezervasyonu #%d - %s', 'villa-reservation-system'),
            $reservation_id,
            $villa_info['title']
        ));
        
        $product->set_description(sprintf(
            __('%s tarihinde %s villa\'sı için rezervasyon', 'villa-reservation-system'),
            date_i18n('d/m/Y', strtotime($reservation_data['checkin_date'])),
            $villa_info['title']
        ));
        
        $product->set_price($price_data['total']);
        $product->set_regular_price($price_data['total']);
        $product->set_virtual(true);
        $product->set_sold_individually(true);
        $product->set_catalog_visibility('hidden');
        $product->set_status('private');
        
        // Stok yönetimi - sadece 1 adet
        $product->set_manage_stock(true);
        $product->set_stock_quantity(1);
        $product->set_stock_status('instock');
        
        $product_id = $product->save();
        
        if ($product_id) {
            // Rezervasyon ID'sini meta olarak kaydet
            update_post_meta($product_id, '_vrs_reservation_id', $reservation_id);
            update_post_meta($product_id, '_vrs_villa_id', $reservation_data['villa_id']);
        }
        
        return $product_id;
    }
    
    /**
     * Checkout URL'i al
     */
    private function get_checkout_url($reservation_id) {
        global $wpdb;
        
        $product_id = $wpdb->get_var($wpdb->prepare("
            SELECT product_id FROM {$wpdb->prefix}vrs_reservations 
            WHERE id = %d
        ", $reservation_id));
        
        if (!$product_id) {
            return home_url('/rezervasyonlarim/');
        }
        
        // Sepete ekle ve checkout'a yönlendir
        $checkout_url = add_query_arg(array(
            'add-to-cart' => $product_id,
            'quantity' => 1
        ), wc_get_checkout_url());
        
        return $checkout_url;
    }
    
    /**
     * Rezervasyon iptal et
     */
    public function cancel_reservation($reservation_id, $user_id = null) {
        global $wpdb;
        
        $reservation = $this->get_reservation($reservation_id);
        
        if (!$reservation) {
            return false;
        }
        
        // Yetki kontrolü
        if ($user_id && $reservation->user_id != $user_id) {
            return false;
        }
        
        // İptal edilebilir mi kontrol et
        if ($reservation->status === 'cancelled') {
            return false;
        }
        
        // Check-in tarihine 24 saat kaldıysa iptal edilemez
        $checkin_time = strtotime($reservation->checkin_date);
        if ($checkin_time - time() < 24 * 60 * 60) {
            return false;
        }
        
        // Rezervasyonu iptal et
        $result = $wpdb->update(
            $wpdb->prefix . 'vrs_reservations',
            array(
                'status' => 'cancelled',
                'updated_at' => current_time('mysql')
            ),
            array('id' => $reservation_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result) {
            // Google Sheets'i güncelle
            do_action('vrs_reservation_cancelled', $reservation_id);
            
            // İlgili WooCommerce siparişini iptal et
            if ($reservation->order_id) {
                $order = wc_get_order($reservation->order_id);
                if ($order) {
                    $order->update_status('cancelled', __('Rezervasyon iptal edildi.', 'villa-reservation-system'));
                }
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Rezervasyon bilgilerini al
     */
    public function get_reservation($reservation_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare("
            SELECT r.*, v.post_title as villa_name,
                   g.name as guest_name, g.email as guest_email, g.phone as guest_phone
            FROM {$wpdb->prefix}vrs_reservations r
            LEFT JOIN {$wpdb->posts} v ON r.villa_id = v.ID
            LEFT JOIN {$wpdb->prefix}vrs_guest_info g ON r.id = g.reservation_id
            WHERE r.id = %d
        ", $reservation_id));
    }
    
    /**
     * Kullanıcının rezervasyonlarını al
     */
    public function get_user_reservations($user_id, $limit = 20, $offset = 0) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT r.*, v.post_title as villa_name,
                   g.name as guest_name, g.email as guest_email
            FROM {$wpdb->prefix}vrs_reservations r
            LEFT JOIN {$wpdb->posts} v ON r.villa_id = v.ID
            LEFT JOIN {$wpdb->prefix}vrs_guest_info g ON r.id = g.reservation_id
            WHERE r.user_id = %d
            ORDER BY r.created_at DESC
            LIMIT %d OFFSET %d
        ", $user_id, $limit, $offset));
    }
    
    /**
     * Villa rezervasyon sayısını al
     */
    private function get_villa_reservation_count($villa_id) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}vrs_reservations
            WHERE villa_id = %d
        ", $villa_id));
    }
    
    /**
     * Sonraki müsait tarihi al
     */
    private function get_next_available_date($villa_id) {
        global $wpdb;
        
        $today = date('Y-m-d');
        
        // Sonraki rezervasyonu bul
        $next_checkout = $wpdb->get_var($wpdb->prepare("
            SELECT MIN(checkout_date) FROM {$wpdb->prefix}vrs_reservations
            WHERE villa_id = %d AND checkin_date > %s AND status IN ('confirmed', 'pending')
        ", $villa_id, $today));
        
        return $next_checkout ?: $today;
    }
    
    /**
     * Rezervasyonlarım sayfası
     */
    private function show_my_reservations_page() {
        if (!is_user_logged_in()) {
            auth_redirect();
        }
        
        $user_id = get_current_user_id();
        $reservations = $this->get_user_reservations($user_id);
        
        get_header();
        ?>
        <div class="vrs-my-reservations">
            <h1><?php _e('Rezervasyonlarım', 'villa-reservation-system'); ?></h1>
            
            <?php if ($reservations): ?>
                <div class="vrs-reservations-list">
                    <?php foreach ($reservations as $reservation): ?>
                        <div class="vrs-reservation-item status-<?php echo esc_attr($reservation->status); ?>">
                            <h3><?php echo esc_html($reservation->villa_name); ?></h3>
                            <div class="reservation-dates">
                                <?php echo date_i18n('d/m/Y', strtotime($reservation->checkin_date)); ?> - 
                                <?php echo date_i18n('d/m/Y', strtotime($reservation->checkout_date)); ?>
                            </div>
                            <div class="reservation-guests">
                                <?php printf('%d yetişkin, %d çocuk', $reservation->adults, $reservation->children); ?>
                            </div>
                            <div class="reservation-price">
                                <?php echo number_format($reservation->total_price, 2); ?> TL
                            </div>
                            <div class="reservation-status">
                                <?php
                                switch($reservation->status) {
                                    case 'confirmed':
                                        echo '<span class="status-confirmed">' . __('Onaylandı', 'villa-reservation-system') . '</span>';
                                        break;
                                    case 'pending':
                                        echo '<span class="status-pending">' . __('Bekliyor', 'villa-reservation-system') . '</span>';
                                        break;
                                    case 'cancelled':
                                        echo '<span class="status-cancelled">' . __('İptal', 'villa-reservation-system') . '</span>';
                                        break;
                                }
                                ?>
                            </div>
                            <div class="reservation-actions">
                                <a href="<?php echo home_url('/rezervasyon/' . $reservation->id); ?>" class="button">
                                    <?php _e('Detaylar', 'villa-reservation-system'); ?>
                                </a>
                                
                                <?php if ($reservation->status === 'confirmed' || $reservation->status === 'pending'): ?>
                                    <button type="button" class="button cancel-reservation" data-reservation-id="<?php echo $reservation->id; ?>">
                                        <?php _e('İptal Et', 'villa-reservation-system'); ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p><?php _e('Henüz rezervasyonunuz bulunmuyor.', 'villa-reservation-system'); ?></p>
            <?php endif; ?>
        </div>
        <?php
        get_footer();
    }
    
    /**
     * Rezervasyon detay sayfası
     */
    private function show_reservation_details_page() {
        $reservation_id = get_query_var('reservation_id');
        $reservation = $this->get_reservation($reservation_id);
        
        if (!$reservation) {
            wp_die(__('Rezervasyon bulunamadı.', 'villa-reservation-system'));
        }
        
        // Yetki kontrolü
        if (!current_user_can('edit_posts') && (!is_user_logged_in() || $reservation->user_id != get_current_user_id())) {
            wp_die(__('Bu rezervasyonu görüntüleme yetkiniz yok.', 'villa-reservation-system'));
        }
        
        get_header();
        ?>
        <div class="vrs-reservation-details">
            <h1><?php printf(__('Rezervasyon #%d', 'villa-reservation-system'), $reservation->id); ?></h1>
            
            <div class="reservation-info">
                <h2><?php echo esc_html($reservation->villa_name); ?></h2>
                
                <div class="info-row">
                    <strong><?php _e('Tarihler:', 'villa-reservation-system'); ?></strong>
                    <?php echo date_i18n('d/m/Y', strtotime($reservation->checkin_date)); ?> - 
                    <?php echo date_i18n('d/m/Y', strtotime($reservation->checkout_date)); ?>
                </div>
                
                <div class="info-row">
                    <strong><?php _e('Misafir Sayısı:', 'villa-reservation-system'); ?></strong>
                    <?php printf('%d yetişkin, %d çocuk', $reservation->adults, $reservation->children); ?>
                </div>
                
                <div class="info-row">
                    <strong><?php _e('Toplam Tutar:', 'villa-reservation-system'); ?></strong>
                    <?php echo number_format($reservation->total_price, 2); ?> TL
                </div>
                
                <div class="info-row">
                    <strong><?php _e('Durum:', 'villa-reservation-system'); ?></strong>
                    <?php
                    switch($reservation->status) {
                        case 'confirmed':
                            echo '<span class="status-confirmed">' . __('Onaylandı', 'villa-reservation-system') . '</span>';
                            break;
                        case 'pending':
                            echo '<span class="status-pending">' . __('Ödeme Bekliyor', 'villa-reservation-system') . '</span>';
                            break;
                        case 'cancelled':
                            echo '<span class="status-cancelled">' . __('İptal Edildi', 'villa-reservation-system') . '</span>';
                            break;
                    }
                    ?>
                </div>
                
                <?php if ($reservation->special_requests): ?>
                <div class="info-row">
                    <strong><?php _e('Özel İstekler:', 'villa-reservation-system'); ?></strong>
                    <?php echo esc_html($reservation->special_requests); ?>
                </div>
                <?php endif; ?>
                
                <div class="info-row">
                    <strong><?php _e('Rezervasyon Tarihi:', 'villa-reservation-system'); ?></strong>
                    <?php echo date_i18n('d/m/Y H:i', strtotime($reservation->created_at)); ?>
                </div>
            </div>
            
            <div class="reservation-contact">
                <h3><?php _e('İletişim Bilgileri', 'villa-reservation-system'); ?></h3>
                <p><strong><?php _e('Ad Soyad:', 'villa-reservation-system'); ?></strong> <?php echo esc_html($reservation->guest_name); ?></p>
                <p><strong><?php _e('E-posta:', 'villa-reservation-system'); ?></strong> <?php echo esc_html($reservation->guest_email); ?></p>
                <p><strong><?php _e('Telefon:', 'villa-reservation-system'); ?></strong> <?php echo esc_html($reservation->guest_phone); ?></p>
            </div>
        </div>
        <?php
        get_footer();
    }
    
    /**
     * Admin rezervasyonlar sayfası
     */
    public function admin_reservations_page() {
        global $wpdb;
        
        $villa_id = isset($_GET['villa_id']) ? intval($_GET['villa_id']) : 0;
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        
        $where_clauses = array();
        $where_values = array();
        
        if ($villa_id) {
            $where_clauses[] = 'r.villa_id = %d';
            $where_values[] = $villa_id;
        }
        
        if ($status) {
            $where_clauses[] = 'r.status = %s';
            $where_values[] = $status;
        }
        
        $where_sql = $where_clauses ? 'WHERE ' . implode(' AND ', $where_clauses) : '';
        
        $reservations = $wpdb->get_results($wpdb->prepare("
            SELECT r.*, v.post_title as villa_name,
                   g.name as guest_name, g.email as guest_email, g.phone as guest_phone
            FROM {$wpdb->prefix}vrs_reservations r
            LEFT JOIN {$wpdb->posts} v ON r.villa_id = v.ID
            LEFT JOIN {$wpdb->prefix}vrs_guest_info g ON r.id = g.reservation_id
            $where_sql
            ORDER BY r.created_at DESC
            LIMIT 100
        ", $where_values));
        
        $villas = get_posts(array(
            'post_type' => 'villa',
            'post_status' => 'publish',
            'numberposts' => -1
        ));
        ?>
        
        <div class="wrap">
            <h1><?php _e('Rezervasyonlar', 'villa-reservation-system'); ?></h1>
            
            <form method="get" action="">
                <input type="hidden" name="page" value="villa-reservations" />
                <input type="hidden" name="post_type" value="villa" />
                
                <select name="villa_id">
                    <option value=""><?php _e('Tüm Villalar', 'villa-reservation-system'); ?></option>
                    <?php foreach ($villas as $villa): ?>
                        <option value="<?php echo $villa->ID; ?>" <?php selected($villa_id, $villa->ID); ?>>
                            <?php echo esc_html($villa->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <select name="status">
                    <option value=""><?php _e('Tüm Durumlar', 'villa-reservation-system'); ?></option>
                    <option value="pending" <?php selected($status, 'pending'); ?>><?php _e('Bekliyor', 'villa-reservation-system'); ?></option>
                    <option value="confirmed" <?php selected($status, 'confirmed'); ?>><?php _e('Onaylandı', 'villa-reservation-system'); ?></option>
                    <option value="cancelled" <?php selected($status, 'cancelled'); ?>><?php _e('İptal', 'villa-reservation-system'); ?></option>
                </select>
                
                <?php submit_button(__('Filtrele', 'villa-reservation-system'), 'secondary', '', false); ?>
            </form>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'villa-reservation-system'); ?></th>
                        <th><?php _e('Villa', 'villa-reservation-system'); ?></th>
                        <th><?php _e('Misafir', 'villa-reservation-system'); ?></th>
                        <th><?php _e('Tarihler', 'villa-reservation-system'); ?></th>
                        <th><?php _e('Kişi Sayısı', 'villa-reservation-system'); ?></th>
                        <th><?php _e('Tutar', 'villa-reservation-system'); ?></th>
                        <th><?php _e('Durum', 'villa-reservation-system'); ?></th>
                        <th><?php _e('Tarih', 'villa-reservation-system'); ?></th>
                        <th><?php _e('İşlemler', 'villa-reservation-system'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($reservations): ?>
                        <?php foreach ($reservations as $reservation): ?>
                        <tr>
                            <td><?php echo $reservation->id; ?></td>
                            <td><?php echo esc_html($reservation->villa_name); ?></td>
                            <td>
                                <?php echo esc_html($reservation->guest_name); ?><br>
                                <small><?php echo esc_html($reservation->guest_email); ?></small>
                            </td>
                            <td>
                                <?php echo date_i18n('d/m/Y', strtotime($reservation->checkin_date)); ?> - 
                                <?php echo date_i18n('d/m/Y', strtotime($reservation->checkout_date)); ?>
                            </td>
                            <td><?php printf('%d+%d', $reservation->adults, $reservation->children); ?></td>
                            <td><?php echo number_format($reservation->total_price, 2); ?> TL</td>
                            <td>
                                <span class="status-<?php echo esc_attr($reservation->status); ?>">
                                    <?php
                                    switch($reservation->status) {
                                        case 'confirmed':
                                            _e('Onaylandı', 'villa-reservation-system');
                                            break;
                                        case 'pending':
                                            _e('Bekliyor', 'villa-reservation-system');
                                            break;
                                        case 'cancelled':
                                            _e('İptal', 'villa-reservation-system');
                                            break;
                                    }
                                    ?>
                                </span>
                            </td>
                            <td><?php echo date_i18n('d/m/Y', strtotime($reservation->created_at)); ?></td>
                            <td>
                                <a href="<?php echo home_url('/rezervasyon/' . $reservation->id); ?>" class="button button-small">
                                    <?php _e('Görüntüle', 'villa-reservation-system'); ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9"><?php _e('Rezervasyon bulunamadı.', 'villa-reservation-system'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Admin takvim sayfası
     */
    public function admin_calendar_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Rezervasyon Takvimi', 'villa-reservation-system'); ?></h1>
            <div id="vrs-admin-calendar"></div>
        </div>
        <?php
    }
    
    /**
     * Bloke tarihleri al (AJAX)
     */
    public function ajax_get_blocked_dates() {
        check_ajax_referer('vrs_nonce', 'nonce');
        
        $villa_id = intval($_POST['villa_id']);
        
        if (!$villa_id) {
            wp_send_json_error(array(
                'message' => __('Geçersiz villa ID.', 'villa-reservation-system')
            ));
        }
        
        global $wpdb;
        
        // Database'deki bloke tarihleri al
        $blocked_dates = $wpdb->get_col($wpdb->prepare("
            SELECT blocked_date FROM {$wpdb->prefix}vrs_blocked_dates
            WHERE villa_id = %d
            AND blocked_date >= CURDATE()
            ORDER BY blocked_date
        ", $villa_id));
        
        // Rezerve tarihleri ekle
        $reserved_dates = $wpdb->get_results($wpdb->prepare("
            SELECT checkin_date, checkout_date FROM {$wpdb->prefix}vrs_reservations
            WHERE villa_id = %d
            AND status IN ('confirmed', 'pending')
            AND checkout_date >= CURDATE()
            ORDER BY checkin_date
        ", $villa_id));
        
        // Rezerve tarihleri aralık olarak ekle
        foreach ($reserved_dates as $reservation) {
            $start = strtotime($reservation->checkin_date);
            $end = strtotime($reservation->checkout_date);
            
            for ($date = $start; $date < $end; $date += 24 * 60 * 60) {
                $blocked_dates[] = date('Y-m-d', $date);
            }
        }
        
        // Google Sheets'ten bloke tarihleri al (varsa)
        $villa_info = VRS_Villa_System::get_villa_info($villa_id);
        if ($villa_info && $villa_info['google_sheet_id']) {
            // Google Sheets API çağrısı burada yapılabilir
            // Şimdilik sadece database verilerini döndür
        }
        
        // Unique ve sort et
        $blocked_dates = array_unique($blocked_dates);
        sort($blocked_dates);
        
        wp_send_json_success($blocked_dates);
    }
}