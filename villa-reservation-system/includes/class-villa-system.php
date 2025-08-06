<?php
/**
 * Villa Sistem Yönetimi
 * Villa custom post type, meta fields ve temel işlevler
 */

if (!defined('ABSPATH')) {
    exit;
}

class VRS_Villa_System {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'register_villa_post_type'));
        add_action('add_meta_boxes', array($this, 'add_villa_meta_boxes'));
        add_action('save_post', array($this, 'save_villa_meta'));
        add_filter('the_content', array($this, 'add_reservation_form_to_villa'));
        add_action('wp_ajax_vrs_update_villa_settings', array($this, 'ajax_update_villa_settings'));
    }
    
    /**
     * Villa custom post type'ını kaydet
     */
    public static function register_villa_post_type() {
        $labels = array(
            'name'                  => __('Villalar', 'villa-reservation-system'),
            'singular_name'         => __('Villa', 'villa-reservation-system'),
            'menu_name'             => __('Villa Rezervasyon', 'villa-reservation-system'),
            'name_admin_bar'        => __('Villa', 'villa-reservation-system'),
            'archives'              => __('Villa Arşivi', 'villa-reservation-system'),
            'attributes'            => __('Villa Özellikleri', 'villa-reservation-system'),
            'parent_item_colon'     => __('Ana Villa:', 'villa-reservation-system'),
            'all_items'             => __('Tüm Villalar', 'villa-reservation-system'),
            'add_new_item'          => __('Yeni Villa Ekle', 'villa-reservation-system'),
            'add_new'               => __('Yeni Ekle', 'villa-reservation-system'),
            'new_item'              => __('Yeni Villa', 'villa-reservation-system'),
            'edit_item'             => __('Villa Düzenle', 'villa-reservation-system'),
            'update_item'           => __('Villa Güncelle', 'villa-reservation-system'),
            'view_item'             => __('Villa Görüntüle', 'villa-reservation-system'),
            'view_items'            => __('Villaları Görüntüle', 'villa-reservation-system'),
            'search_items'          => __('Villa Ara', 'villa-reservation-system'),
            'not_found'             => __('Villa bulunamadı', 'villa-reservation-system'),
            'not_found_in_trash'    => __('Çöp kutusunda villa bulunamadı', 'villa-reservation-system'),
            'featured_image'        => __('Villa Resmi', 'villa-reservation-system'),
            'set_featured_image'    => __('Villa Resmi Seç', 'villa-reservation-system'),
            'remove_featured_image' => __('Villa Resmini Kaldır', 'villa-reservation-system'),
            'use_featured_image'    => __('Villa Resmi Olarak Kullan', 'villa-reservation-system'),
            'insert_into_item'      => __('Villa içine ekle', 'villa-reservation-system'),
            'uploaded_to_this_item' => __('Bu villaya yüklenen', 'villa-reservation-system'),
            'items_list'            => __('Villa listesi', 'villa-reservation-system'),
            'items_list_navigation' => __('Villa listesi navigasyonu', 'villa-reservation-system'),
            'filter_items_list'     => __('Villa listesini filtrele', 'villa-reservation-system'),
        );
        
        $args = array(
            'label'                 => __('Villa', 'villa-reservation-system'),
            'description'           => __('Kiralık villa ve bungalovlar', 'villa-reservation-system'),
            'labels'                => $labels,
            'supports'              => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'revisions'),
            'taxonomies'            => array('villa_category', 'villa_tag'),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 26,
            'menu_icon'             => 'dashicons-admin-home',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'show_in_rest'          => true,
            'rewrite'               => array('slug' => 'villa'),
        );
        
        register_post_type('villa', $args);
        
        // Taxonomies kaydet
        self::register_villa_taxonomies();
    }
    
    /**
     * Villa kategorileri ve etiketleri
     */
    public static function register_villa_taxonomies() {
        // Villa kategorileri
        $category_labels = array(
            'name'              => __('Villa Kategorileri', 'villa-reservation-system'),
            'singular_name'     => __('Villa Kategorisi', 'villa-reservation-system'),
            'search_items'      => __('Kategori Ara', 'villa-reservation-system'),
            'all_items'         => __('Tüm Kategoriler', 'villa-reservation-system'),
            'parent_item'       => __('Ana Kategori', 'villa-reservation-system'),
            'parent_item_colon' => __('Ana Kategori:', 'villa-reservation-system'),
            'edit_item'         => __('Kategori Düzenle', 'villa-reservation-system'),
            'update_item'       => __('Kategori Güncelle', 'villa-reservation-system'),
            'add_new_item'      => __('Yeni Kategori Ekle', 'villa-reservation-system'),
            'new_item_name'     => __('Yeni Kategori Adı', 'villa-reservation-system'),
            'menu_name'         => __('Kategoriler', 'villa-reservation-system'),
        );
        
        register_taxonomy('villa_category', array('villa'), array(
            'hierarchical'      => true,
            'labels'            => $category_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'show_in_rest'      => true,
            'rewrite'           => array('slug' => 'villa-kategori'),
        ));
        
        // Villa etiketleri
        $tag_labels = array(
            'name'                       => __('Villa Etiketleri', 'villa-reservation-system'),
            'singular_name'              => __('Villa Etiketi', 'villa-reservation-system'),
            'search_items'               => __('Etiket Ara', 'villa-reservation-system'),
            'popular_items'              => __('Popüler Etiketler', 'villa-reservation-system'),
            'all_items'                  => __('Tüm Etiketler', 'villa-reservation-system'),
            'edit_item'                  => __('Etiket Düzenle', 'villa-reservation-system'),
            'update_item'                => __('Etiket Güncelle', 'villa-reservation-system'),
            'add_new_item'               => __('Yeni Etiket Ekle', 'villa-reservation-system'),
            'new_item_name'              => __('Yeni Etiket Adı', 'villa-reservation-system'),
            'separate_items_with_commas' => __('Etiketleri virgülle ayırın', 'villa-reservation-system'),
            'add_or_remove_items'        => __('Etiket ekle veya kaldır', 'villa-reservation-system'),
            'choose_from_most_used'      => __('En çok kullanılanlardan seç', 'villa-reservation-system'),
            'not_found'                  => __('Etiket bulunamadı', 'villa-reservation-system'),
            'menu_name'                  => __('Etiketler', 'villa-reservation-system'),
        );
        
        register_taxonomy('villa_tag', array('villa'), array(
            'hierarchical'          => false,
            'labels'                => $tag_labels,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'update_count_callback' => '_update_post_term_count',
            'query_var'             => true,
            'show_in_rest'          => true,
            'rewrite'               => array('slug' => 'villa-etiket'),
        ));
    }
    
    /**
     * Villa meta box'larını ekle
     */
    public function add_villa_meta_boxes() {
        add_meta_box(
            'villa-settings',
            __('Villa Ayarları', 'villa-reservation-system'),
            array($this, 'villa_settings_meta_box'),
            'villa',
            'normal',
            'high'
        );
        
        add_meta_box(
            'villa-pricing',
            __('Fiyatlandırma', 'villa-reservation-system'),
            array($this, 'villa_pricing_meta_box'),
            'villa',
            'normal',
            'high'
        );
        
        add_meta_box(
            'villa-google-sheets',
            __('Google Sheets Entegrasyonu', 'villa-reservation-system'),
            array($this, 'villa_google_sheets_meta_box'),
            'villa',
            'side',
            'default'
        );
        
        add_meta_box(
            'villa-reservations',
            __('Rezervasyonlar', 'villa-reservation-system'),
            array($this, 'villa_reservations_meta_box'),
            'villa',
            'side',
            'default'
        );
    }
    
    /**
     * Villa ayarları meta box
     */
    public function villa_settings_meta_box($post) {
        wp_nonce_field('save_villa_meta', 'villa_meta_nonce');
        
        $max_capacity = get_post_meta($post->ID, '_villa_max_capacity', true) ?: 8;
        $max_adults = get_post_meta($post->ID, '_villa_max_adults', true) ?: 6;
        $max_children = get_post_meta($post->ID, '_villa_max_children', true) ?: 4;
        $minimum_stay = get_post_meta($post->ID, '_villa_minimum_stay', true) ?: 1;
        $maximum_stay = get_post_meta($post->ID, '_villa_maximum_stay', true) ?: 30;
        $checkin_time = get_post_meta($post->ID, '_villa_checkin_time', true) ?: '15:00';
        $checkout_time = get_post_meta($post->ID, '_villa_checkout_time', true) ?: '11:00';
        $child_age_ranges = get_post_meta($post->ID, '_villa_child_age_ranges', true) ?: array(
            '0-2' => 'Bebek (0-2 yaş)',
            '3-12' => 'Çocuk (3-12 yaş)',
            '13-17' => 'Genç (13-17 yaş)'
        );
        ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="villa_max_capacity"><?php _e('Maksimum Kapasite', 'villa-reservation-system'); ?></label>
                </th>
                <td>
                    <input type="number" id="villa_max_capacity" name="villa_max_capacity" 
                           value="<?php echo esc_attr($max_capacity); ?>" min="1" max="50" />
                    <p class="description"><?php _e('Villa\'da aynı anda kalabilecek maksimum kişi sayısı', 'villa-reservation-system'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="villa_max_adults"><?php _e('Maksimum Yetişkin', 'villa-reservation-system'); ?></label>
                </th>
                <td>
                    <input type="number" id="villa_max_adults" name="villa_max_adults" 
                           value="<?php echo esc_attr($max_adults); ?>" min="1" max="30" />
                    <p class="description"><?php _e('Kabul edilebilecek maksimum yetişkin sayısı', 'villa-reservation-system'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="villa_max_children"><?php _e('Maksimum Çocuk', 'villa-reservation-system'); ?></label>
                </th>
                <td>
                    <input type="number" id="villa_max_children" name="villa_max_children" 
                           value="<?php echo esc_attr($max_children); ?>" min="0" max="20" />
                    <p class="description"><?php _e('Kabul edilebilecek maksimum çocuk sayısı', 'villa-reservation-system'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="villa_minimum_stay"><?php _e('Minimum Konaklama (Gün)', 'villa-reservation-system'); ?></label>
                </th>
                <td>
                    <input type="number" id="villa_minimum_stay" name="villa_minimum_stay" 
                           value="<?php echo esc_attr($minimum_stay); ?>" min="1" max="365" />
                    <p class="description"><?php _e('Minimum kaç gün konaklama zorunlu', 'villa-reservation-system'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="villa_maximum_stay"><?php _e('Maksimum Konaklama (Gün)', 'villa-reservation-system'); ?></label>
                </th>
                <td>
                    <input type="number" id="villa_maximum_stay" name="villa_maximum_stay" 
                           value="<?php echo esc_attr($maximum_stay); ?>" min="1" max="365" />
                    <p class="description"><?php _e('Maksimum kaç gün konaklama mümkün', 'villa-reservation-system'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="villa_checkin_time"><?php _e('Check-in Saati', 'villa-reservation-system'); ?></label>
                </th>
                <td>
                    <input type="time" id="villa_checkin_time" name="villa_checkin_time" 
                           value="<?php echo esc_attr($checkin_time); ?>" />
                    <p class="description"><?php _e('Giriş saati (örn: 15:00)', 'villa-reservation-system'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="villa_checkout_time"><?php _e('Check-out Saati', 'villa-reservation-system'); ?></label>
                </th>
                <td>
                    <input type="time" id="villa_checkout_time" name="villa_checkout_time" 
                           value="<?php echo esc_attr($checkout_time); ?>" />
                    <p class="description"><?php _e('Çıkış saati (örn: 11:00)', 'villa-reservation-system'); ?></p>
                </td>
            </tr>
        </table>
        
        <?php
    }
    
    /**
     * Villa fiyatlandırma meta box
     */
    public function villa_pricing_meta_box($post) {
        $base_price = get_post_meta($post->ID, '_villa_base_price', true) ?: 0;
        $adult_price = get_post_meta($post->ID, '_villa_adult_price', true) ?: 0;
        $child_price = get_post_meta($post->ID, '_villa_child_price', true) ?: 0;
        $child_discount = get_post_meta($post->ID, '_villa_child_discount', true) ?: 50;
        $pricing_type = get_post_meta($post->ID, '_villa_pricing_type', true) ?: 'per_person';
        ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="villa_pricing_type"><?php _e('Fiyatlandırma Türü', 'villa-reservation-system'); ?></label>
                </th>
                <td>
                    <select id="villa_pricing_type" name="villa_pricing_type">
                        <option value="per_person" <?php selected($pricing_type, 'per_person'); ?>>
                            <?php _e('Kişi Başı', 'villa-reservation-system'); ?>
                        </option>
                        <option value="fixed" <?php selected($pricing_type, 'fixed'); ?>>
                            <?php _e('Sabit Fiyat', 'villa-reservation-system'); ?>
                        </option>
                    </select>
                    <p class="description"><?php _e('Fiyatlandırma nasıl hesaplanacak?', 'villa-reservation-system'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="villa_base_price"><?php _e('Temel Fiyat (TL)', 'villa-reservation-system'); ?></label>
                </th>
                <td>
                    <input type="number" id="villa_base_price" name="villa_base_price" 
                           value="<?php echo esc_attr($base_price); ?>" min="0" step="0.01" />
                    <p class="description"><?php _e('Günlük temel fiyat (sabit fiyat için) veya minimum fiyat', 'villa-reservation-system'); ?></p>
                </td>
            </tr>
            
            <tr class="per-person-pricing" <?php echo $pricing_type !== 'per_person' ? 'style="display:none;"' : ''; ?>>
                <th scope="row">
                    <label for="villa_adult_price"><?php _e('Yetişkin Fiyatı (TL)', 'villa-reservation-system'); ?></label>
                </th>
                <td>
                    <input type="number" id="villa_adult_price" name="villa_adult_price" 
                           value="<?php echo esc_attr($adult_price); ?>" min="0" step="0.01" />
                    <p class="description"><?php _e('Kişi başı yetişkin günlük fiyatı', 'villa-reservation-system'); ?></p>
                </td>
            </tr>
            
            <tr class="per-person-pricing" <?php echo $pricing_type !== 'per_person' ? 'style="display:none;"' : ''; ?>>
                <th scope="row">
                    <label for="villa_child_price"><?php _e('Çocuk Fiyatı (TL)', 'villa-reservation-system'); ?></label>
                </th>
                <td>
                    <input type="number" id="villa_child_price" name="villa_child_price" 
                           value="<?php echo esc_attr($child_price); ?>" min="0" step="0.01" />
                    <p class="description"><?php _e('Kişi başı çocuk günlük fiyatı (yaş gruplarına göre)', 'villa-reservation-system'); ?></p>
                </td>
            </tr>
            
            <tr class="per-person-pricing" <?php echo $pricing_type !== 'per_person' ? 'style="display:none;"' : ''; ?>>
                <th scope="row">
                    <label for="villa_child_discount"><?php _e('Çocuk İndirimi (%)', 'villa-reservation-system'); ?></label>
                </th>
                <td>
                    <input type="number" id="villa_child_discount" name="villa_child_discount" 
                           value="<?php echo esc_attr($child_discount); ?>" min="0" max="100" />
                    <p class="description"><?php _e('Çocuklar için uygulanan indirim oranı', 'villa-reservation-system'); ?></p>
                </td>
            </tr>
        </table>
        
        <script>
        jQuery(document).ready(function($) {
            $('#villa_pricing_type').change(function() {
                if ($(this).val() === 'per_person') {
                    $('.per-person-pricing').show();
                } else {
                    $('.per-person-pricing').hide();
                }
            });
        });
        </script>
        
        <?php
    }
    
    /**
     * Google Sheets meta box
     */
    public function villa_google_sheets_meta_box($post) {
        $google_sheet_url = get_post_meta($post->ID, '_villa_google_sheet_url', true);
        $google_sheet_id = get_post_meta($post->ID, '_villa_google_sheet_id', true);
        $sheet_name = get_post_meta($post->ID, '_villa_sheet_name', true) ?: 'Sheet1';
        $sync_status = get_post_meta($post->ID, '_villa_sync_status', true);
        $last_sync = get_post_meta($post->ID, '_villa_last_sync', true);
        ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="villa_google_sheet_url"><?php _e('Google Sheets URL', 'villa-reservation-system'); ?></label>
                </th>
                <td>
                    <input type="url" id="villa_google_sheet_url" name="villa_google_sheet_url" 
                           value="<?php echo esc_attr($google_sheet_url); ?>" style="width: 100%;" 
                           placeholder="https://docs.google.com/spreadsheets/d/..." />
                    <p class="description"><?php _e('Google Sheets belgesinin URL\'si', 'villa-reservation-system'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="villa_sheet_name"><?php _e('Sayfa Adı', 'villa-reservation-system'); ?></label>
                </th>
                <td>
                    <input type="text" id="villa_sheet_name" name="villa_sheet_name" 
                           value="<?php echo esc_attr($sheet_name); ?>" />
                    <p class="description"><?php _e('Kullanılacak sayfa adı (varsayılan: Sheet1)', 'villa-reservation-system'); ?></p>
                </td>
            </tr>
            
            <?php if ($sync_status): ?>
            <tr>
                <th scope="row"><?php _e('Senkronizasyon Durumu', 'villa-reservation-system'); ?></th>
                <td>
                    <span class="sync-status <?php echo esc_attr($sync_status); ?>">
                        <?php 
                        switch($sync_status) {
                            case 'success':
                                _e('Başarılı', 'villa-reservation-system');
                                break;
                            case 'error':
                                _e('Hata', 'villa-reservation-system');
                                break;
                            default:
                                _e('Bilinmiyor', 'villa-reservation-system');
                        }
                        ?>
                    </span>
                    
                    <?php if ($last_sync): ?>
                    <br>
                    <small><?php printf(__('Son senkronizasyon: %s', 'villa-reservation-system'), 
                        date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_sync))); ?></small>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endif; ?>
        </table>
        
        <p>
            <button type="button" class="button button-secondary" id="test-sync">
                <?php _e('Bağlantıyı Test Et', 'villa-reservation-system'); ?>
            </button>
            <button type="button" class="button button-primary" id="sync-now">
                <?php _e('Şimdi Senkronize Et', 'villa-reservation-system'); ?>
            </button>
        </p>
        
        <script>
        jQuery(document).ready(function($) {
            $('#test-sync, #sync-now').click(function() {
                var action = $(this).attr('id') === 'test-sync' ? 'test' : 'sync';
                var $button = $(this);
                var originalText = $button.text();
                
                $button.prop('disabled', true).text('<?php _e('İşleniyor...', 'villa-reservation-system'); ?>');
                
                $.post(ajaxurl, {
                    action: 'vrs_google_sheets_' + action,
                    villa_id: <?php echo $post->ID; ?>,
                    nonce: '<?php echo wp_create_nonce('vrs_admin_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        alert(response.data.message);
                    } else {
                        alert('Hata: ' + response.data.message);
                    }
                }).always(function() {
                    $button.prop('disabled', false).text(originalText);
                });
            });
        });
        </script>
        
        <?php
    }
    
    /**
     * Villa rezervasyonları meta box
     */
    public function villa_reservations_meta_box($post) {
        global $wpdb;
        
        $reservations = $wpdb->get_results($wpdb->prepare("
            SELECT r.*, u.display_name, o.post_status as order_status
            FROM {$wpdb->prefix}vrs_reservations r
            LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
            LEFT JOIN {$wpdb->posts} o ON r.order_id = o.ID
            WHERE r.villa_id = %d
            ORDER BY r.checkin_date DESC
            LIMIT 10
        ", $post->ID));
        
        if ($reservations): ?>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php _e('Tarih', 'villa-reservation-system'); ?></th>
                        <th><?php _e('Misafir', 'villa-reservation-system'); ?></th>
                        <th><?php _e('Durum', 'villa-reservation-system'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservations as $reservation): ?>
                    <tr>
                        <td>
                            <?php echo date_i18n('d/m/Y', strtotime($reservation->checkin_date)); ?> - 
                            <?php echo date_i18n('d/m/Y', strtotime($reservation->checkout_date)); ?>
                            <br>
                            <small><?php printf('%d yetişkin, %d çocuk', $reservation->adults, $reservation->children); ?></small>
                        </td>
                        <td>
                            <?php echo esc_html($reservation->display_name ?: 'Misafir'); ?>
                            <br>
                            <small><?php echo number_format($reservation->total_price, 2); ?> TL</small>
                        </td>
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
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <p style="text-align: center; margin-top: 10px;">
                <a href="<?php echo admin_url('admin.php?page=villa-reservations&villa_id=' . $post->ID); ?>" class="button">
                    <?php _e('Tüm Rezervasyonları Görüntüle', 'villa-reservation-system'); ?>
                </a>
            </p>
        <?php else: ?>
            <p><?php _e('Henüz rezervasyon bulunmuyor.', 'villa-reservation-system'); ?></p>
        <?php endif;
    }
    
    /**
     * Villa meta verilerini kaydet
     */
    public function save_villa_meta($post_id) {
        // Nonce kontrolü
        if (!isset($_POST['villa_meta_nonce']) || !wp_verify_nonce($_POST['villa_meta_nonce'], 'save_villa_meta')) {
            return;
        }
        
        // Autosave kontrolü
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Yetki kontrolü
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Post type kontrolü
        if (get_post_type($post_id) !== 'villa') {
            return;
        }
        
        // Meta verileri kaydet
        $meta_fields = array(
            'villa_max_capacity',
            'villa_max_adults',
            'villa_max_children',
            'villa_minimum_stay',
            'villa_maximum_stay',
            'villa_checkin_time',
            'villa_checkout_time',
            'villa_base_price',
            'villa_adult_price',
            'villa_child_price',
            'villa_child_discount',
            'villa_pricing_type',
            'villa_google_sheet_url',
            'villa_sheet_name'
        );
        
        foreach ($meta_fields as $field) {
            if (isset($_POST[$field])) {
                $value = sanitize_text_field($_POST[$field]);
                update_post_meta($post_id, '_' . $field, $value);
            }
        }
        
        // Google Sheets URL değiştiyse sheet ID'yi çıkar
        if (isset($_POST['villa_google_sheet_url'])) {
            $url = sanitize_text_field($_POST['villa_google_sheet_url']);
            if (preg_match('/\/spreadsheets\/d\/([a-zA-Z0-9-_]+)/', $url, $matches)) {
                update_post_meta($post_id, '_villa_google_sheet_id', $matches[1]);
            }
        }
    }
    
    /**
     * Villa içeriğine rezervasyon formunu ekle
     */
    public function add_reservation_form_to_villa($content) {
        if (is_singular('villa') && is_main_query() && in_the_loop()) {
            $content .= self::reservation_form_shortcode(array('villa_id' => get_the_ID()));
        }
        return $content;
    }
    
    /**
     * Rezervasyon formu shortcode
     */
    public static function reservation_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'villa_id' => get_the_ID(),
        ), $atts);
        
        $villa_id = intval($atts['villa_id']);
        
        if (!$villa_id || get_post_type($villa_id) !== 'villa') {
            return '<p>' . __('Geçersiz villa ID.', 'villa-reservation-system') . '</p>';
        }
        
        ob_start();
        include VRS_PLUGIN_PATH . 'public/templates/reservation-form.php';
        return ob_get_clean();
    }
    
    /**
     * Takvim shortcode
     */
    public static function calendar_shortcode($atts) {
        $atts = shortcode_atts(array(
            'villa_id' => get_the_ID(),
            'months' => 3,
        ), $atts);
        
        $villa_id = intval($atts['villa_id']);
        $months = intval($atts['months']);
        
        if (!$villa_id || get_post_type($villa_id) !== 'villa') {
            return '<p>' . __('Geçersiz villa ID.', 'villa-reservation-system') . '</p>';
        }
        
        ob_start();
        include VRS_PLUGIN_PATH . 'public/templates/villa-calendar.php';
        return ob_get_clean();
    }
    
    /**
     * Villa ayarlarını AJAX ile güncelle
     */
    public function ajax_update_villa_settings() {
        check_ajax_referer('vrs_admin_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('Yetkiniz yok.', 'villa-reservation-system'));
        }
        
        $villa_id = intval($_POST['villa_id']);
        $settings = $_POST['settings'];
        
        foreach ($settings as $key => $value) {
            $key = sanitize_key($key);
            $value = sanitize_text_field($value);
            update_post_meta($villa_id, '_villa_' . $key, $value);
        }
        
        wp_send_json_success(array(
            'message' => __('Ayarlar güncellendi.', 'villa-reservation-system')
        ));
    }
    
    /**
     * Villa bilgilerini al
     */
    public static function get_villa_info($villa_id) {
        $villa_id = intval($villa_id);
        
        if (!$villa_id || get_post_type($villa_id) !== 'villa') {
            return false;
        }
        
        return array(
            'id' => $villa_id,
            'title' => get_the_title($villa_id),
            'content' => get_post_field('post_content', $villa_id),
            'excerpt' => get_the_excerpt($villa_id),
            'thumbnail' => get_the_post_thumbnail_url($villa_id, 'large'),
            'max_capacity' => get_post_meta($villa_id, '_villa_max_capacity', true) ?: 8,
            'max_adults' => get_post_meta($villa_id, '_villa_max_adults', true) ?: 6,
            'max_children' => get_post_meta($villa_id, '_villa_max_children', true) ?: 4,
            'minimum_stay' => get_post_meta($villa_id, '_villa_minimum_stay', true) ?: 1,
            'maximum_stay' => get_post_meta($villa_id, '_villa_maximum_stay', true) ?: 30,
            'checkin_time' => get_post_meta($villa_id, '_villa_checkin_time', true) ?: '15:00',
            'checkout_time' => get_post_meta($villa_id, '_villa_checkout_time', true) ?: '11:00',
            'base_price' => get_post_meta($villa_id, '_villa_base_price', true) ?: 0,
            'adult_price' => get_post_meta($villa_id, '_villa_adult_price', true) ?: 0,
            'child_price' => get_post_meta($villa_id, '_villa_child_price', true) ?: 0,
            'child_discount' => get_post_meta($villa_id, '_villa_child_discount', true) ?: 50,
            'pricing_type' => get_post_meta($villa_id, '_villa_pricing_type', true) ?: 'per_person',
            'google_sheet_url' => get_post_meta($villa_id, '_villa_google_sheet_url', true),
            'google_sheet_id' => get_post_meta($villa_id, '_villa_google_sheet_id', true),
            'sheet_name' => get_post_meta($villa_id, '_villa_sheet_name', true) ?: 'Sheet1',
        );
    }
}