<?php
/**
 * WooCommerce Entegrasyonu
 * Otomatik ürün oluşturma, ödeme işleme, sipariş yönetimi
 */

if (!defined('ABSPATH')) {
    exit;
}

class VRS_WooCommerce_Integration {
    
    /**
     * Constructor
     */
    public function __construct() {
        // WooCommerce hooks
        add_action('woocommerce_order_status_completed', array($this, 'on_order_completed'));
        add_action('woocommerce_order_status_processing', array($this, 'on_order_processing'));
        add_action('woocommerce_order_status_cancelled', array($this, 'on_order_cancelled'));
        add_action('woocommerce_order_status_refunded', array($this, 'on_order_refunded'));
        
        // Checkout hooks
        add_action('woocommerce_checkout_order_processed', array($this, 'on_checkout_processed'), 10, 3);
        add_filter('woocommerce_cart_item_name', array($this, 'modify_cart_item_name'), 10, 3);
        add_filter('woocommerce_order_item_name', array($this, 'modify_order_item_name'), 10, 2);
        
        // Product hooks
        add_filter('woocommerce_is_purchasable', array($this, 'check_reservation_purchasable'), 10, 2);
        add_filter('woocommerce_product_add_to_cart_text', array($this, 'reservation_add_to_cart_text'), 10, 2);
        add_filter('woocommerce_product_single_add_to_cart_text', array($this, 'reservation_add_to_cart_text'), 10, 2);
        
        // Admin hooks
        add_action('add_meta_boxes', array($this, 'add_order_meta_boxes'));
        add_filter('woocommerce_admin_order_item_headers', array($this, 'add_order_item_headers'));
        add_action('woocommerce_admin_order_item_values', array($this, 'add_order_item_values'), 10, 3);
        
        // Email hooks
        add_action('woocommerce_email_order_details', array($this, 'add_reservation_details_to_email'), 15, 4);
        
        // Cart/Checkout customizations
        add_filter('woocommerce_checkout_fields', array($this, 'customize_checkout_fields'));
        add_action('woocommerce_checkout_process', array($this, 'validate_checkout_fields'));
        
        // Admin notifications
        add_action('admin_notices', array($this, 'admin_notices'));
    }
    
    /**
     * Sipariş tamamlandığında rezervasyonu onayla
     */
    public function on_order_completed($order_id) {
        $this->update_reservation_status($order_id, 'confirmed');
        $this->send_confirmation_emails($order_id);
        $this->update_google_sheets($order_id);
    }
    
    /**
     * Sipariş işlenmeye başladığında
     */
    public function on_order_processing($order_id) {
        $this->update_reservation_status($order_id, 'confirmed');
        $this->send_confirmation_emails($order_id);
        $this->update_google_sheets($order_id);
    }
    
    /**
     * Sipariş iptal edildiğinde
     */
    public function on_order_cancelled($order_id) {
        $this->update_reservation_status($order_id, 'cancelled');
        $this->clear_google_sheets($order_id);
    }
    
    /**
     * Sipariş iade edildiğinde
     */
    public function on_order_refunded($order_id) {
        $this->update_reservation_status($order_id, 'cancelled');
        $this->clear_google_sheets($order_id);
    }
    
    /**
     * Checkout işlemi tamamlandığında
     */
    public function on_checkout_processed($order_id, $posted_data, $order) {
        // Rezervasyon ürünleri için özel işlemler
        foreach ($order->get_items() as $item_id => $item) {
            $product_id = $item->get_product_id();
            $reservation_id = get_post_meta($product_id, '_vrs_reservation_id', true);
            
            if ($reservation_id) {
                // Rezervasyonu siparişle bağla
                global $wpdb;
                $wpdb->update(
                    $wpdb->prefix . 'vrs_reservations',
                    array('order_id' => $order_id),
                    array('id' => $reservation_id),
                    array('%d'),
                    array('%d')
                );
                
                // Ürünün stokunu sıfırla (tekrar satılmasın)
                $product = wc_get_product($product_id);
                if ($product) {
                    $product->set_stock_quantity(0);
                    $product->set_stock_status('outofstock');
                    $product->save();
                }
                
                // Order item'a rezervasyon bilgilerini ekle
                $reservation = $this->get_reservation($reservation_id);
                if ($reservation) {
                    $item->add_meta_data('_reservation_id', $reservation_id);
                    $item->add_meta_data('_villa_name', $reservation->villa_name);
                    $item->add_meta_data('_checkin_date', $reservation->checkin_date);
                    $item->add_meta_data('_checkout_date', $reservation->checkout_date);
                    $item->add_meta_data('_adults', $reservation->adults);
                    $item->add_meta_data('_children', $reservation->children);
                    $item->add_meta_data('_guest_name', $reservation->guest_name);
                    $item->add_meta_data('_guest_email', $reservation->guest_email);
                    $item->add_meta_data('_guest_phone', $reservation->guest_phone);
                    $item->save();
                }
            }
        }
    }
    
    /**
     * Sepet item adını özelleştir
     */
    public function modify_cart_item_name($product_name, $cart_item, $cart_item_key) {
        $product_id = $cart_item['product_id'];
        $reservation_id = get_post_meta($product_id, '_vrs_reservation_id', true);
        
        if ($reservation_id) {
            $reservation = $this->get_reservation($reservation_id);
            if ($reservation) {
                $product_name = sprintf(
                    '%s<br><small>%s - %s (%d gün)</small>',
                    $reservation->villa_name,
                    date_i18n('d/m/Y', strtotime($reservation->checkin_date)),
                    date_i18n('d/m/Y', strtotime($reservation->checkout_date)),
                    $this->calculate_days($reservation->checkin_date, $reservation->checkout_date)
                );
            }
        }
        
        return $product_name;
    }
    
    /**
     * Sipariş item adını özelleştir
     */
    public function modify_order_item_name($item_name, $item) {
        $reservation_id = $item->get_meta('_reservation_id');
        
        if ($reservation_id) {
            $villa_name = $item->get_meta('_villa_name');
            $checkin = $item->get_meta('_checkin_date');
            $checkout = $item->get_meta('_checkout_date');
            
            if ($villa_name && $checkin && $checkout) {
                $item_name = sprintf(
                    '%s (%s - %s)',
                    $villa_name,
                    date_i18n('d/m/Y', strtotime($checkin)),
                    date_i18n('d/m/Y', strtotime($checkout))
                );
            }
        }
        
        return $item_name;
    }
    
    /**
     * Rezervasyon ürününün satın alınabilirliğini kontrol et
     */
    public function check_reservation_purchasable($purchasable, $product) {
        $reservation_id = get_post_meta($product->get_id(), '_vrs_reservation_id', true);
        
        if ($reservation_id) {
            $reservation = $this->get_reservation($reservation_id);
            
            if (!$reservation || $reservation->status === 'cancelled') {
                return false;
            }
            
            // Rezervasyon süresinin dolup dolmadığını kontrol et
            $created_time = strtotime($reservation->created_at);
            $expiry_time = $created_time + (30 * 60); // 30 dakika
            
            if (time() > $expiry_time && $reservation->status === 'pending') {
                // Süresi dolmuş rezervasyonu iptal et
                global $wpdb;
                $wpdb->update(
                    $wpdb->prefix . 'vrs_reservations',
                    array('status' => 'expired'),
                    array('id' => $reservation_id),
                    array('%s'),
                    array('%d')
                );
                
                return false;
            }
        }
        
        return $purchasable;
    }
    
    /**
     * Sepete ekle buton metnini özelleştir
     */
    public function reservation_add_to_cart_text($text, $product) {
        $reservation_id = get_post_meta($product->get_id(), '_vrs_reservation_id', true);
        
        if ($reservation_id) {
            return __('Ödemeye Geç', 'villa-reservation-system');
        }
        
        return $text;
    }
    
    /**
     * Admin siparişler için meta box ekle
     */
    public function add_order_meta_boxes() {
        add_meta_box(
            'vrs-reservation-details',
            __('Rezervasyon Detayları', 'villa-reservation-system'),
            array($this, 'reservation_details_meta_box'),
            'shop_order',
            'normal',
            'default'
        );
    }
    
    /**
     * Rezervasyon detayları meta box
     */
    public function reservation_details_meta_box($post) {
        $order = wc_get_order($post->ID);
        $reservations = array();
        
        foreach ($order->get_items() as $item) {
            $reservation_id = $item->get_meta('_reservation_id');
            if ($reservation_id) {
                $reservation = $this->get_reservation($reservation_id);
                if ($reservation) {
                    $reservations[] = $reservation;
                }
            }
        }
        
        if (empty($reservations)) {
            echo '<p>' . __('Bu sipariş rezervasyon içermiyor.', 'villa-reservation-system') . '</p>';
            return;
        }
        
        foreach ($reservations as $reservation): ?>
            <div class="vrs-reservation-details">
                <h4><?php echo esc_html($reservation->villa_name); ?></h4>
                
                <table class="form-table">
                    <tr>
                        <th><?php _e('Rezervasyon ID:', 'villa-reservation-system'); ?></th>
                        <td>
                            <a href="<?php echo home_url('/rezervasyon/' . $reservation->id); ?>" target="_blank">
                                #<?php echo $reservation->id; ?>
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Giriş Tarihi:', 'villa-reservation-system'); ?></th>
                        <td><?php echo date_i18n('d/m/Y', strtotime($reservation->checkin_date)); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Çıkış Tarihi:', 'villa-reservation-system'); ?></th>
                        <td><?php echo date_i18n('d/m/Y', strtotime($reservation->checkout_date)); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Konaklama Süresi:', 'villa-reservation-system'); ?></th>
                        <td><?php echo $this->calculate_days($reservation->checkin_date, $reservation->checkout_date); ?> gün</td>
                    </tr>
                    <tr>
                        <th><?php _e('Misafir Sayısı:', 'villa-reservation-system'); ?></th>
                        <td><?php printf('%d yetişkin, %d çocuk', $reservation->adults, $reservation->children); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Misafir Adı:', 'villa-reservation-system'); ?></th>
                        <td><?php echo esc_html($reservation->guest_name); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('E-posta:', 'villa-reservation-system'); ?></th>
                        <td>
                            <a href="mailto:<?php echo esc_attr($reservation->guest_email); ?>">
                                <?php echo esc_html($reservation->guest_email); ?>
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Telefon:', 'villa-reservation-system'); ?></th>
                        <td>
                            <a href="tel:<?php echo esc_attr($reservation->guest_phone); ?>">
                                <?php echo esc_html($reservation->guest_phone); ?>
                            </a>
                        </td>
                    </tr>
                    <?php if ($reservation->special_requests): ?>
                    <tr>
                        <th><?php _e('Özel İstekler:', 'villa-reservation-system'); ?></th>
                        <td><?php echo esc_html($reservation->special_requests); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th><?php _e('Rezervasyon Durumu:', 'villa-reservation-system'); ?></th>
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
                                    default:
                                        echo esc_html($reservation->status);
                                }
                                ?>
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
            <hr>
        <?php endforeach;
    }
    
    /**
     * Order item başlıklarını ekle
     */
    public function add_order_item_headers($order) {
        echo '<th class="reservation-details">' . __('Rezervasyon', 'villa-reservation-system') . '</th>';
    }
    
    /**
     * Order item değerlerini ekle
     */
    public function add_order_item_values($product, $item, $item_id) {
        $reservation_id = $item->get_meta('_reservation_id');
        
        echo '<td class="reservation-details">';
        if ($reservation_id) {
            $checkin = $item->get_meta('_checkin_date');
            $checkout = $item->get_meta('_checkout_date');
            $adults = $item->get_meta('_adults');
            $children = $item->get_meta('_children');
            
            echo '<strong>' . __('Rezervasyon #', 'villa-reservation-system') . $reservation_id . '</strong><br>';
            
            if ($checkin && $checkout) {
                echo date_i18n('d/m/Y', strtotime($checkin)) . ' - ' . date_i18n('d/m/Y', strtotime($checkout)) . '<br>';
            }
            
            if ($adults || $children) {
                printf('%d yetişkin, %d çocuk', $adults, $children);
            }
        } else {
            echo '—';
        }
        echo '</td>';
    }
    
    /**
     * E-posta detaylarına rezervasyon bilgilerini ekle
     */
    public function add_reservation_details_to_email($order, $sent_to_admin, $plain_text, $email) {
        $reservations = array();
        
        foreach ($order->get_items() as $item) {
            $reservation_id = $item->get_meta('_reservation_id');
            if ($reservation_id) {
                $reservation = $this->get_reservation($reservation_id);
                if ($reservation) {
                    $reservations[] = $reservation;
                }
            }
        }
        
        if (empty($reservations)) {
            return;
        }
        
        if ($plain_text) {
            echo "\n\n" . __('REZERVASYON DETAYLARI:', 'villa-reservation-system') . "\n";
            echo str_repeat('=', 50) . "\n";
            
            foreach ($reservations as $reservation) {
                echo sprintf(__('Villa: %s', 'villa-reservation-system'), $reservation->villa_name) . "\n";
                echo sprintf(__('Tarih: %s - %s', 'villa-reservation-system'), 
                    date_i18n('d/m/Y', strtotime($reservation->checkin_date)),
                    date_i18n('d/m/Y', strtotime($reservation->checkout_date))
                ) . "\n";
                echo sprintf(__('Misafir: %d yetişkin, %d çocuk', 'villa-reservation-system'), 
                    $reservation->adults, $reservation->children) . "\n";
                echo sprintf(__('İletişim: %s (%s)', 'villa-reservation-system'), 
                    $reservation->guest_name, $reservation->guest_email) . "\n\n";
            }
        } else {
            echo '<h2>' . __('Rezervasyon Detayları', 'villa-reservation-system') . '</h2>';
            
            foreach ($reservations as $reservation) {
                echo '<div style="margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px;">';
                echo '<h3 style="margin-top: 0;">' . esc_html($reservation->villa_name) . '</h3>';
                echo '<p><strong>' . __('Tarih:', 'villa-reservation-system') . '</strong> ';
                echo date_i18n('d/m/Y', strtotime($reservation->checkin_date)) . ' - ' . date_i18n('d/m/Y', strtotime($reservation->checkout_date));
                echo ' (' . $this->calculate_days($reservation->checkin_date, $reservation->checkout_date) . ' gün)</p>';
                echo '<p><strong>' . __('Misafir Sayısı:', 'villa-reservation-system') . '</strong> ';
                printf('%d yetişkin, %d çocuk', $reservation->adults, $reservation->children);
                echo '</p>';
                echo '<p><strong>' . __('İletişim:', 'villa-reservation-system') . '</strong> ';
                echo esc_html($reservation->guest_name) . ' (' . esc_html($reservation->guest_email) . ')';
                echo '</p>';
                
                if ($reservation->special_requests) {
                    echo '<p><strong>' . __('Özel İstekler:', 'villa-reservation-system') . '</strong> ';
                    echo esc_html($reservation->special_requests);
                    echo '</p>';
                }
                echo '</div>';
            }
        }
    }
    
    /**
     * Checkout alanlarını özelleştir
     */
    public function customize_checkout_fields($fields) {
        // Rezervasyon varsa misafir bilgilerini doldur
        if ($this->cart_has_reservation()) {
            $reservation = $this->get_cart_reservation();
            
            if ($reservation) {
                $fields['billing']['billing_first_name']['default'] = $this->get_first_name($reservation->guest_name);
                $fields['billing']['billing_last_name']['default'] = $this->get_last_name($reservation->guest_name);
                $fields['billing']['billing_email']['default'] = $reservation->guest_email;
                $fields['billing']['billing_phone']['default'] = $reservation->guest_phone;
            }
        }
        
        return $fields;
    }
    
    /**
     * Checkout alanlarını doğrula
     */
    public function validate_checkout_fields() {
        // Rezervasyon ürünü varsa ek doğrulamalar
        if ($this->cart_has_reservation()) {
            // Gerekli alanları kontrol et
            if (empty($_POST['billing_phone'])) {
                wc_add_notice(__('Rezervasyon için telefon numarası gereklidir.', 'villa-reservation-system'), 'error');
            }
        }
    }
    
    /**
     * Admin bildirimleri
     */
    public function admin_notices() {
        // Bekleyen rezervasyonları göster
        if (current_user_can('manage_woocommerce')) {
            global $wpdb;
            
            $pending_count = $wpdb->get_var("
                SELECT COUNT(*) FROM {$wpdb->prefix}vrs_reservations 
                WHERE status = 'pending' 
                AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ");
            
            if ($pending_count > 0) {
                echo '<div class="notice notice-warning">';
                echo '<p>' . sprintf(
                    __('%d adet bekleyen rezervasyon bulunuyor. <a href="%s">Görüntüle</a>', 'villa-reservation-system'),
                    $pending_count,
                    admin_url('admin.php?page=villa-reservations&status=pending')
                ) . '</p>';
                echo '</div>';
            }
        }
    }
    
    /**
     * Rezervasyon durumunu güncelle
     */
    private function update_reservation_status($order_id, $status) {
        global $wpdb;
        
        $wpdb->update(
            $wpdb->prefix . 'vrs_reservations',
            array(
                'status' => $status,
                'updated_at' => current_time('mysql')
            ),
            array('order_id' => $order_id),
            array('%s', '%s'),
            array('%d')
        );
        
        // Hook tetikle
        if ($status === 'confirmed') {
            $reservations = $wpdb->get_results($wpdb->prepare("
                SELECT id FROM {$wpdb->prefix}vrs_reservations 
                WHERE order_id = %d
            ", $order_id));
            
            foreach ($reservations as $reservation) {
                do_action('vrs_reservation_confirmed', $reservation->id);
            }
        }
    }
    
    /**
     * Onay e-postalarını gönder
     */
    private function send_confirmation_emails($order_id) {
        global $wpdb;
        
        $reservations = $wpdb->get_results($wpdb->prepare("
            SELECT id FROM {$wpdb->prefix}vrs_reservations 
            WHERE order_id = %d
        ", $order_id));
        
        foreach ($reservations as $reservation) {
            do_action('vrs_send_confirmation_email', $reservation->id);
        }
    }
    
    /**
     * Google Sheets'i güncelle
     */
    private function update_google_sheets($order_id) {
        global $wpdb;
        
        $reservations = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}vrs_reservations 
            WHERE order_id = %d
        ", $order_id));
        
        foreach ($reservations as $reservation) {
            VRS_Google_Sheets::on_reservation_confirmed($reservation->id);
        }
    }
    
    /**
     * Google Sheets'i temizle
     */
    private function clear_google_sheets($order_id) {
        global $wpdb;
        
        $reservations = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}vrs_reservations 
            WHERE order_id = %d
        ", $order_id));
        
        foreach ($reservations as $reservation) {
            VRS_Google_Sheets::on_reservation_cancelled($reservation->id);
        }
    }
    
    /**
     * Rezervasyon bilgilerini al
     */
    private function get_reservation($reservation_id) {
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
     * Sepette rezervasyon var mı?
     */
    private function cart_has_reservation() {
        if (!function_exists('WC') || !WC()->cart) {
            return false;
        }
        
        foreach (WC()->cart->get_cart() as $cart_item) {
            $product_id = $cart_item['product_id'];
            $reservation_id = get_post_meta($product_id, '_vrs_reservation_id', true);
            
            if ($reservation_id) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Sepetteki rezervasyonu al
     */
    private function get_cart_reservation() {
        if (!function_exists('WC') || !WC()->cart) {
            return null;
        }
        
        foreach (WC()->cart->get_cart() as $cart_item) {
            $product_id = $cart_item['product_id'];
            $reservation_id = get_post_meta($product_id, '_vrs_reservation_id', true);
            
            if ($reservation_id) {
                return $this->get_reservation($reservation_id);
            }
        }
        
        return null;
    }
    
    /**
     * İlk adı ayır
     */
    private function get_first_name($full_name) {
        $parts = explode(' ', trim($full_name));
        return $parts[0] ?? '';
    }
    
    /**
     * Soyadı ayır
     */
    private function get_last_name($full_name) {
        $parts = explode(' ', trim($full_name));
        if (count($parts) > 1) {
            array_shift($parts);
            return implode(' ', $parts);
        }
        return '';
    }
    
    /**
     * Gün sayısını hesapla
     */
    private function calculate_days($checkin, $checkout) {
        return (strtotime($checkout) - strtotime($checkin)) / (24 * 60 * 60);
    }
    
    /**
     * Rezervasyon ürünü oluştur (static method)
     */
    public static function create_reservation_product($reservation_data, $price_data) {
        if (!class_exists('WC_Product_Simple')) {
            return false;
        }
        
        $villa_info = VRS_Villa_System::get_villa_info($reservation_data['villa_id']);
        
        $product = new WC_Product_Simple();
        $product->set_name(sprintf(
            __('Villa Rezervasyonu - %s', 'villa-reservation-system'),
            $villa_info['title']
        ));
        
        $product->set_description(sprintf(
            __('%s ile %s tarihleri arasında %s villa\'sı rezervasyonu', 'villa-reservation-system'),
            date_i18n('d/m/Y', strtotime($reservation_data['checkin_date'])),
            date_i18n('d/m/Y', strtotime($reservation_data['checkout_date'])),
            $villa_info['title']
        ));
        
        $product->set_short_description(sprintf(
            __('%d gün konaklama - %d yetişkin, %d çocuk', 'villa-reservation-system'),
            $price_data['days'],
            $reservation_data['adults'],
            $reservation_data['children']
        ));
        
        $product->set_price($price_data['total']);
        $product->set_regular_price($price_data['total']);
        $product->set_virtual(true);
        $product->set_sold_individually(true);
        $product->set_catalog_visibility('hidden');
        $product->set_status('private');
        
        // Kategoriler
        $product->set_category_ids(array());
        
        // Stok
        $product->set_manage_stock(true);
        $product->set_stock_quantity(1);
        $product->set_stock_status('instock');
        
        // Vergi sınıfı
        $product->set_tax_class('');
        
        $product_id = $product->save();
        
        return $product_id;
    }
    
    /**
     * Rezervasyon süresini kontrol et ve süresi dolanları temizle
     */
    public static function cleanup_expired_reservations() {
        global $wpdb;
        
        // 30 dakikadan eski pending rezervasyonları bul
        $expired_reservations = $wpdb->get_results("
            SELECT r.id, r.product_id 
            FROM {$wpdb->prefix}vrs_reservations r
            WHERE r.status = 'pending' 
            AND r.created_at < DATE_SUB(NOW(), INTERVAL 30 MINUTE)
        ");
        
        foreach ($expired_reservations as $reservation) {
            // Rezervasyonu iptal et
            $wpdb->update(
                $wpdb->prefix . 'vrs_reservations',
                array('status' => 'expired'),
                array('id' => $reservation->id),
                array('%s'),
                array('%d')
            );
            
            // İlgili ürünü sil
            if ($reservation->product_id) {
                wp_delete_post($reservation->product_id, true);
            }
        }
    }
}

// Expired rezervasyonları temizlemek için cron job
add_action('init', function() {
    if (!wp_next_scheduled('vrs_cleanup_expired_reservations')) {
        wp_schedule_event(time(), 'hourly', 'vrs_cleanup_expired_reservations');
    }
});

add_action('vrs_cleanup_expired_reservations', array('VRS_WooCommerce_Integration', 'cleanup_expired_reservations'));