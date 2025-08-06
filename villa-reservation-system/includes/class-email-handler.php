<?php
/**
 * E-posta Sistemi
 * Rezervasyon e-postaları, bildirimler, Türkçe şablonlar
 */

if (!defined('ABSPATH')) {
    exit;
}

class VRS_Email_Handler {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Email hooks
        add_action('vrs_reservation_created', array($this, 'send_reservation_created_emails'));
        add_action('vrs_reservation_confirmed', array($this, 'send_reservation_confirmed_emails'));
        add_action('vrs_reservation_cancelled', array($this, 'send_reservation_cancelled_emails'));
        add_action('vrs_send_confirmation_email', array($this, 'send_confirmation_email'));
        add_action('vrs_send_reminder_email', array($this, 'send_reminder_email'));
        
        // Cron job için
        add_action('vrs_send_reminder_emails', array($this, 'send_daily_reminders'));
        add_action('init', array($this, 'schedule_reminder_emails'));
        
        // Admin notifications
        add_action('admin_init', array($this, 'maybe_send_admin_notifications'));
        
        // Email settings
        add_filter('wp_mail_content_type', array($this, 'set_html_content_type'));
        add_filter('wp_mail_charset', array($this, 'set_utf8_charset'));
    }
    
    /**
     * Reminder e-postaları için cron job planla
     */
    public function schedule_reminder_emails() {
        if (!wp_next_scheduled('vrs_send_reminder_emails')) {
            wp_schedule_event(time(), 'daily', 'vrs_send_reminder_emails');
        }
    }
    
    /**
     * E-posta content type'ını HTML yap
     */
    public function set_html_content_type() {
        return 'text/html';
    }
    
    /**
     * UTF-8 charset ayarla
     */
    public function set_utf8_charset() {
        return 'UTF-8';
    }
    
    /**
     * Rezervasyon oluşturulduğunda e-postalar gönder
     */
    public function send_reservation_created_emails($reservation_id) {
        $reservation = $this->get_reservation($reservation_id);
        
        if (!$reservation) {
            return;
        }
        
        // Müşteriye bilgi e-postası gönder
        $this->send_customer_reservation_pending_email($reservation);
        
        // Admin'e bildirim gönder
        $this->send_admin_new_reservation_email($reservation);
    }
    
    /**
     * Rezervasyon onaylandığında e-postalar gönder
     */
    public function send_reservation_confirmed_emails($reservation_id) {
        $reservation = $this->get_reservation($reservation_id);
        
        if (!$reservation) {
            return;
        }
        
        // Müşteriye onay e-postası gönder
        $this->send_customer_confirmation_email($reservation);
        
        // Admin'e bildirim gönder
        $this->send_admin_confirmation_email($reservation);
    }
    
    /**
     * Rezervasyon iptal edildiğinde e-postalar gönder
     */
    public function send_reservation_cancelled_emails($reservation_id) {
        $reservation = $this->get_reservation($reservation_id);
        
        if (!$reservation) {
            return;
        }
        
        // Müşteriye iptal e-postası gönder
        $this->send_customer_cancellation_email($reservation);
        
        // Admin'e bildirim gönder
        $this->send_admin_cancellation_email($reservation);
    }
    
    /**
     * Onay e-postası gönder
     */
    public function send_confirmation_email($reservation_id) {
        $reservation = $this->get_reservation($reservation_id);
        
        if (!$reservation) {
            return;
        }
        
        $this->send_customer_confirmation_email($reservation);
    }
    
    /**
     * Hatırlatma e-postası gönder
     */
    public function send_reminder_email($reservation_id) {
        $reservation = $this->get_reservation($reservation_id);
        
        if (!$reservation) {
            return;
        }
        
        $this->send_customer_reminder_email($reservation);
    }
    
    /**
     * Müşteri rezervasyon bekleme e-postası
     */
    private function send_customer_reservation_pending_email($reservation) {
        $subject = sprintf(
            __('Rezervasyon Başvurunuz Alındı - %s', 'villa-reservation-system'),
            $reservation->villa_name
        );
        
        $template_data = array(
            'reservation' => $reservation,
            'villa_info' => VRS_Villa_System::get_villa_info($reservation->villa_id),
            'checkout_url' => $this->get_checkout_url($reservation->id),
            'payment_deadline' => date_i18n(
                get_option('date_format') . ' ' . get_option('time_format'),
                strtotime($reservation->created_at . ' +30 minutes')
            )
        );
        
        $message = $this->get_email_template('customer-reservation-pending', $template_data);
        
        $this->send_email($reservation->guest_email, $subject, $message);
    }
    
    /**
     * Müşteri rezervasyon onay e-postası
     */
    private function send_customer_confirmation_email($reservation) {
        $subject = sprintf(
            __('Rezervasyonunuz Onaylandı - %s', 'villa-reservation-system'),
            $reservation->villa_name
        );
        
        $villa_info = VRS_Villa_System::get_villa_info($reservation->villa_id);
        
        $template_data = array(
            'reservation' => $reservation,
            'villa_info' => $villa_info,
            'checkin_instructions' => $this->get_checkin_instructions($reservation->villa_id),
            'contact_info' => $this->get_contact_info(),
            'calendar_link' => $this->generate_calendar_link($reservation)
        );
        
        $message = $this->get_email_template('customer-confirmation', $template_data);
        
        $this->send_email($reservation->guest_email, $subject, $message);
    }
    
    /**
     * Müşteri rezervasyon iptal e-postası
     */
    private function send_customer_cancellation_email($reservation) {
        $subject = sprintf(
            __('Rezervasyonunuz İptal Edildi - %s', 'villa-reservation-system'),
            $reservation->villa_name
        );
        
        $template_data = array(
            'reservation' => $reservation,
            'villa_info' => VRS_Villa_System::get_villa_info($reservation->villa_id),
            'refund_info' => $this->get_refund_info($reservation),
            'contact_info' => $this->get_contact_info()
        );
        
        $message = $this->get_email_template('customer-cancellation', $template_data);
        
        $this->send_email($reservation->guest_email, $subject, $message);
    }
    
    /**
     * Müşteri hatırlatma e-postası
     */
    private function send_customer_reminder_email($reservation) {
        $days_until_checkin = $this->get_days_until_checkin($reservation->checkin_date);
        
        $subject = sprintf(
            __('Tatil Hatırlatması - %d Gün Kaldı!', 'villa-reservation-system'),
            $days_until_checkin
        );
        
        $template_data = array(
            'reservation' => $reservation,
            'villa_info' => VRS_Villa_System::get_villa_info($reservation->villa_id),
            'days_until_checkin' => $days_until_checkin,
            'weather_info' => $this->get_weather_info($reservation->villa_id),
            'local_attractions' => $this->get_local_attractions($reservation->villa_id),
            'checkin_instructions' => $this->get_checkin_instructions($reservation->villa_id),
            'contact_info' => $this->get_contact_info()
        );
        
        $message = $this->get_email_template('customer-reminder', $template_data);
        
        $this->send_email($reservation->guest_email, $subject, $message);
    }
    
    /**
     * Admin yeni rezervasyon bildirimi
     */
    private function send_admin_new_reservation_email($reservation) {
        $admin_email = $this->get_admin_email();
        
        $subject = sprintf(
            __('[Villa Rezervasyon] Yeni Rezervasyon - %s', 'villa-reservation-system'),
            $reservation->villa_name
        );
        
        $template_data = array(
            'reservation' => $reservation,
            'villa_info' => VRS_Villa_System::get_villa_info($reservation->villa_id),
            'admin_url' => admin_url('admin.php?page=villa-reservations'),
            'reservation_url' => home_url('/rezervasyon/' . $reservation->id)
        );
        
        $message = $this->get_email_template('admin-new-reservation', $template_data);
        
        $this->send_email($admin_email, $subject, $message);
    }
    
    /**
     * Admin rezervasyon onay bildirimi
     */
    private function send_admin_confirmation_email($reservation) {
        $admin_email = $this->get_admin_email();
        
        $subject = sprintf(
            __('[Villa Rezervasyon] Rezervasyon Onaylandı - %s', 'villa-reservation-system'),
            $reservation->villa_name
        );
        
        $template_data = array(
            'reservation' => $reservation,
            'villa_info' => VRS_Villa_System::get_villa_info($reservation->villa_id),
            'admin_url' => admin_url('admin.php?page=villa-reservations'),
            'preparation_checklist' => $this->get_preparation_checklist($reservation->villa_id)
        );
        
        $message = $this->get_email_template('admin-confirmation', $template_data);
        
        $this->send_email($admin_email, $subject, $message);
    }
    
    /**
     * Admin rezervasyon iptal bildirimi
     */
    private function send_admin_cancellation_email($reservation) {
        $admin_email = $this->get_admin_email();
        
        $subject = sprintf(
            __('[Villa Rezervasyon] Rezervasyon İptal Edildi - %s', 'villa-reservation-system'),
            $reservation->villa_name
        );
        
        $template_data = array(
            'reservation' => $reservation,
            'villa_info' => VRS_Villa_System::get_villa_info($reservation->villa_id),
            'admin_url' => admin_url('admin.php?page=villa-reservations')
        );
        
        $message = $this->get_email_template('admin-cancellation', $template_data);
        
        $this->send_email($admin_email, $subject, $message);
    }
    
    /**
     * Günlük hatırlatmaları gönder
     */
    public function send_daily_reminders() {
        global $wpdb;
        
        // 3 gün sonra check-in'i olan rezervasyonlar
        $three_day_reminders = $wpdb->get_results("
            SELECT r.id FROM {$wpdb->prefix}vrs_reservations r
            WHERE r.status = 'confirmed'
            AND r.checkin_date = DATE_ADD(CURDATE(), INTERVAL 3 DAY)
            AND NOT EXISTS (
                SELECT 1 FROM {$wpdb->prefix}vrs_email_log e
                WHERE e.reservation_id = r.id 
                AND e.email_type = '3_day_reminder'
                AND e.sent_date > DATE_SUB(NOW(), INTERVAL 7 DAY)
            )
        ");
        
        foreach ($three_day_reminders as $reminder) {
            $this->send_reminder_email($reminder->id);
            $this->log_email_sent($reminder->id, '3_day_reminder');
        }
        
        // 1 gün sonra check-in'i olan rezervasyonlar
        $one_day_reminders = $wpdb->get_results("
            SELECT r.id FROM {$wpdb->prefix}vrs_reservations r
            WHERE r.status = 'confirmed'
            AND r.checkin_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
            AND NOT EXISTS (
                SELECT 1 FROM {$wpdb->prefix}vrs_email_log e
                WHERE e.reservation_id = r.id 
                AND e.email_type = '1_day_reminder'
                AND e.sent_date > DATE_SUB(NOW(), INTERVAL 2 DAY)
            )
        ");
        
        foreach ($one_day_reminders as $reminder) {
            $this->send_reminder_email($reminder->id);
            $this->log_email_sent($reminder->id, '1_day_reminder');
        }
    }
    
    /**
     * E-posta gönder
     */
    private function send_email($to, $subject, $message, $headers = null) {
        if (!$headers) {
            $headers = array(
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . $this->get_from_name() . ' <' . $this->get_from_email() . '>'
            );
        }
        
        $sent = wp_mail($to, $subject, $message, $headers);
        
        // Log the email attempt
        error_log(sprintf(
            'VRS Email %s: To=%s, Subject=%s',
            $sent ? 'SENT' : 'FAILED',
            $to,
            $subject
        ));
        
        return $sent;
    }
    
    /**
     * E-posta şablonu al
     */
    private function get_email_template($template_name, $data = array()) {
        // Template file path
        $template_file = VRS_PLUGIN_PATH . 'public/templates/emails/' . $template_name . '.php';
        
        if (!file_exists($template_file)) {
            return $this->get_fallback_template($template_name, $data);
        }
        
        // Extract data to variables
        extract($data);
        
        ob_start();
        include $template_file;
        $content = ob_get_clean();
        
        // Wrap in email layout
        return $this->wrap_email_content($content, $data);
    }
    
    /**
     * E-posta içeriğini layout ile sar
     */
    private function wrap_email_content($content, $data = array()) {
        $site_name = get_bloginfo('name');
        $site_url = home_url();
        
        $html = '<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . esc_html($site_name) . '</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .email-container { max-width: 600px; margin: 0 auto; background: #ffffff; }
        .email-header { background: #2c3e50; color: white; padding: 20px; text-align: center; }
        .email-content { padding: 30px; }
        .email-footer { background: #ecf0f1; padding: 20px; text-align: center; font-size: 12px; color: #7f8c8d; }
        .button { display: inline-block; padding: 12px 24px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
        .reservation-details { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .detail-row { margin-bottom: 10px; }
        .detail-label { font-weight: bold; color: #2c3e50; }
        .highlight { background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107; margin: 15px 0; }
        .success { background: #d4edda; border-left-color: #28a745; }
        .warning { background: #fff3cd; border-left-color: #ffc107; }
        .danger { background: #f8d7da; border-left-color: #dc3545; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>' . esc_html($site_name) . '</h1>
            <p>Villa Rezervasyon Sistemi</p>
        </div>
        
        <div class="email-content">
            ' . $content . '
        </div>
        
        <div class="email-footer">
            <p><strong>' . esc_html($site_name) . '</strong></p>
            <p>' . esc_html($this->get_contact_info()['address'] ?? '') . '</p>
            <p>Telefon: ' . esc_html($this->get_contact_info()['phone'] ?? '') . ' | E-posta: ' . esc_html($this->get_contact_info()['email'] ?? '') . '</p>
            <p><a href="' . esc_url($site_url) . '" style="color: #3498db;">' . esc_url($site_url) . '</a></p>
            <hr style="border: none; border-top: 1px solid #bdc3c7; margin: 20px 0;">
            <p>Bu e-posta otomatik olarak gönderilmiştir. Lütfen yanıtlamayın.</p>
        </div>
    </div>
</body>
</html>';
        
        return $html;
    }
    
    /**
     * Fallback şablon
     */
    private function get_fallback_template($template_name, $data) {
        $reservation = $data['reservation'] ?? null;
        
        if (!$reservation) {
            return '<p>Template bulunamadı: ' . esc_html($template_name) . '</p>';
        }
        
        switch ($template_name) {
            case 'customer-confirmation':
                return $this->get_customer_confirmation_fallback($data);
            case 'customer-reservation-pending':
                return $this->get_customer_pending_fallback($data);
            case 'admin-new-reservation':
                return $this->get_admin_new_reservation_fallback($data);
            default:
                return '<p>Rezervasyon bilgilendirme e-postası.</p>';
        }
    }
    
    /**
     * Müşteri onay fallback şablonu
     */
    private function get_customer_confirmation_fallback($data) {
        $reservation = $data['reservation'];
        $villa_info = $data['villa_info'];
        
        return sprintf('
            <h2>Rezervasyonunuz Onaylandı!</h2>
            
            <div class="highlight success">
                <p><strong>Tebrikler!</strong> %s için rezervasyonunuz başarıyla onaylandı.</p>
            </div>
            
            <div class="reservation-details">
                <h3>Rezervasyon Detayları</h3>
                <div class="detail-row">
                    <span class="detail-label">Rezervasyon No:</span> #%d
                </div>
                <div class="detail-row">
                    <span class="detail-label">Villa:</span> %s
                </div>
                <div class="detail-row">
                    <span class="detail-label">Giriş Tarihi:</span> %s (%s)
                </div>
                <div class="detail-row">
                    <span class="detail-label">Çıkış Tarihi:</span> %s (%s)
                </div>
                <div class="detail-row">
                    <span class="detail-label">Misafir Sayısı:</span> %d yetişkin, %d çocuk
                </div>
                <div class="detail-row">
                    <span class="detail-label">Toplam Tutar:</span> %s TL
                </div>
            </div>
            
            <div class="highlight">
                <h4>Önemli Bilgiler</h4>
                <p><strong>Check-in Saati:</strong> %s</p>
                <p><strong>Check-out Saati:</strong> %s</p>
                <p>Giriş günü belirtilen saatten sonra villa\'ya giriş yapabilirsiniz.</p>
            </div>
            
            <p>Herhangi bir sorunuz olması durumunda bizimle iletişime geçebilirsiniz.</p>
            <p>İyi tatiller dileriz!</p>
        ',
            esc_html($reservation->villa_name),
            $reservation->id,
            esc_html($reservation->villa_name),
            date_i18n('d/m/Y', strtotime($reservation->checkin_date)),
            date_i18n('l', strtotime($reservation->checkin_date)),
            date_i18n('d/m/Y', strtotime($reservation->checkout_date)),
            date_i18n('l', strtotime($reservation->checkout_date)),
            $reservation->adults,
            $reservation->children,
            number_format($reservation->total_price, 2),
            $villa_info['checkin_time'] ?? '15:00',
            $villa_info['checkout_time'] ?? '11:00'
        );
    }
    
    /**
     * Müşteri bekleyen rezervasyon fallback şablonu
     */
    private function get_customer_pending_fallback($data) {
        $reservation = $data['reservation'];
        $checkout_url = $data['checkout_url'] ?? '#';
        $payment_deadline = $data['payment_deadline'] ?? '';
        
        return sprintf('
            <h2>Rezervasyon Başvurunuz Alındı</h2>
            
            <div class="highlight warning">
                <p><strong>Ödeme Bekleniyor!</strong> Rezervasyonunuzu tamamlamak için ödeme yapmanız gerekmektedir.</p>
                <p><strong>Ödeme Son Tarihi:</strong> %s</p>
            </div>
            
            <div class="reservation-details">
                <h3>Rezervasyon Detayları</h3>
                <div class="detail-row">
                    <span class="detail-label">Rezervasyon No:</span> #%d
                </div>
                <div class="detail-row">
                    <span class="detail-label">Villa:</span> %s
                </div>
                <div class="detail-row">
                    <span class="detail-label">Tarih:</span> %s - %s
                </div>
                <div class="detail-row">
                    <span class="detail-label">Misafir:</span> %d yetişkin, %d çocuk
                </div>
                <div class="detail-row">
                    <span class="detail-label">Toplam Tutar:</span> %s TL
                </div>
            </div>
            
            <p style="text-align: center; margin: 30px 0;">
                <a href="%s" class="button">Ödemeyi Tamamla</a>
            </p>
            
            <div class="highlight">
                <p><strong>Önemli:</strong> Ödeme yapılmadığı takdirde rezervasyonunuz otomatik olarak iptal edilecektir.</p>
            </div>
        ',
            $payment_deadline,
            $reservation->id,
            esc_html($reservation->villa_name),
            date_i18n('d/m/Y', strtotime($reservation->checkin_date)),
            date_i18n('d/m/Y', strtotime($reservation->checkout_date)),
            $reservation->adults,
            $reservation->children,
            number_format($reservation->total_price, 2),
            esc_url($checkout_url)
        );
    }
    
    /**
     * Admin yeni rezervasyon fallback şablonu
     */
    private function get_admin_new_reservation_fallback($data) {
        $reservation = $data['reservation'];
        $admin_url = $data['admin_url'] ?? admin_url();
        
        return sprintf('
            <h2>Yeni Rezervasyon Bildirimi</h2>
            
            <div class="highlight">
                <p>Yeni bir rezervasyon başvurusu alındı ve ödeme bekleniyor.</p>
            </div>
            
            <div class="reservation-details">
                <h3>Rezervasyon Detayları</h3>
                <div class="detail-row">
                    <span class="detail-label">Rezervasyon No:</span> #%d
                </div>
                <div class="detail-row">
                    <span class="detail-label">Villa:</span> %s
                </div>
                <div class="detail-row">
                    <span class="detail-label">Misafir:</span> %s (%s)
                </div>
                <div class="detail-row">
                    <span class="detail-label">Telefon:</span> %s
                </div>
                <div class="detail-row">
                    <span class="detail-label">Tarih:</span> %s - %s
                </div>
                <div class="detail-row">
                    <span class="detail-label">Kişi Sayısı:</span> %d yetişkin, %d çocuk
                </div>
                <div class="detail-row">
                    <span class="detail-label">Tutar:</span> %s TL
                </div>
                <div class="detail-row">
                    <span class="detail-label">Durum:</span> Ödeme Bekleniyor
                </div>
            </div>
            
            <p style="text-align: center; margin: 30px 0;">
                <a href="%s" class="button">Admin Paneli</a>
            </p>
        ',
            $reservation->id,
            esc_html($reservation->villa_name),
            esc_html($reservation->guest_name),
            esc_html($reservation->guest_email),
            esc_html($reservation->guest_phone),
            date_i18n('d/m/Y', strtotime($reservation->checkin_date)),
            date_i18n('d/m/Y', strtotime($reservation->checkout_date)),
            $reservation->adults,
            $reservation->children,
            number_format($reservation->total_price, 2),
            esc_url($admin_url)
        );
    }
    
    /**
     * Utility fonksiyonlar
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
    
    private function get_checkout_url($reservation_id) {
        global $wpdb;
        
        $product_id = $wpdb->get_var($wpdb->prepare("
            SELECT product_id FROM {$wpdb->prefix}vrs_reservations 
            WHERE id = %d
        ", $reservation_id));
        
        if (!$product_id) {
            return home_url('/rezervasyonlarim/');
        }
        
        return add_query_arg(array(
            'add-to-cart' => $product_id,
            'quantity' => 1
        ), wc_get_checkout_url());
    }
    
    private function get_days_until_checkin($checkin_date) {
        return ceil((strtotime($checkin_date) - time()) / (24 * 60 * 60));
    }
    
    private function get_from_name() {
        return get_option('blogname', 'Villa Rezervasyon');
    }
    
    private function get_from_email() {
        return get_option('admin_email', 'noreply@example.com');
    }
    
    private function get_admin_email() {
        return get_option('admin_email');
    }
    
    private function get_contact_info() {
        $settings = get_option('vrs_settings', array());
        
        return array(
            'phone' => $settings['contact_phone'] ?? '',
            'email' => $settings['contact_email'] ?? get_option('admin_email'),
            'address' => $settings['contact_address'] ?? '',
            'website' => home_url()
        );
    }
    
    private function get_checkin_instructions($villa_id) {
        return get_post_meta($villa_id, '_villa_checkin_instructions', true) ?: __('Check-in talimatları admin panelinden ayarlanabilir.', 'villa-reservation-system');
    }
    
    private function get_refund_info($reservation) {
        return __('İade işlemleri 3-5 iş günü içerisinde tamamlanacaktır.', 'villa-reservation-system');
    }
    
    private function get_weather_info($villa_id) {
        return __('Hava durumu bilgileri için yerel kaynaklara bakabilirsiniz.', 'villa-reservation-system');
    }
    
    private function get_local_attractions($villa_id) {
        return array(
            __('Yerel gezilecek yerler admin panelinden ayarlanabilir.', 'villa-reservation-system')
        );
    }
    
    private function get_preparation_checklist($villa_id) {
        return array(
            __('Villa temizliği kontrol edilecek', 'villa-reservation-system'),
            __('Anahtar teslimi hazırlanacak', 'villa-reservation-system'),
            __('Misafir karşılama hazırlığı yapılacak', 'villa-reservation-system')
        );
    }
    
    private function generate_calendar_link($reservation) {
        $start_date = date('Ymd', strtotime($reservation->checkin_date));
        $end_date = date('Ymd', strtotime($reservation->checkout_date . ' +1 day'));
        $title = urlencode($reservation->villa_name . ' - Villa Tatili');
        $details = urlencode(sprintf(
            'Villa: %s\nMisafir: %d yetişkin, %d çocuk\nRezervasyon: #%d',
            $reservation->villa_name,
            $reservation->adults,
            $reservation->children,
            $reservation->id
        ));
        
        return "https://calendar.google.com/calendar/render?action=TEMPLATE&text={$title}&dates={$start_date}/{$end_date}&details={$details}";
    }
    
    private function log_email_sent($reservation_id, $email_type) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'vrs_email_log',
            array(
                'reservation_id' => $reservation_id,
                'email_type' => $email_type,
                'sent_date' => current_time('mysql')
            ),
            array('%d', '%s', '%s')
        );
    }
    
    private function maybe_send_admin_notifications() {
        // Admin bildirimlerini kontrol et ve gönder
        // Bu fonksiyon gelecekte genişletilebilir
    }
}