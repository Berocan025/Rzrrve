<?php
/**
 * Customer Confirmation Email Template
 * 
 * @package VillaReservationSystem
 */

// Ensure this file is being included by WordPress
if (!defined('ABSPATH')) {
    exit;
}

// Email variables should be available:
// $reservation, $villa, $guest_info, $checkout_url
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php _e('Rezervasyon Onayı', 'villa-reservation-system'); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .email-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .email-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .email-content {
            padding: 30px;
        }
        .reservation-details {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #3498db;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
        }
        .detail-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        .detail-label {
            font-weight: 600;
            color: #495057;
        }
        .detail-value {
            color: #212529;
        }
        .payment-button {
            display: inline-block;
            background: #28a745;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            text-align: center;
            margin: 20px 0;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 14px;
            color: #6c757d;
        }
        .villa-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1><?php _e('Rezervasyon Onaylandı!', 'villa-reservation-system'); ?></h1>
        </div>
        
        <div class="email-content">
            <p><?php printf(__('Merhaba %s,', 'villa-reservation-system'), esc_html($guest_info->guest_name)); ?></p>
            
            <p><?php _e('Villa rezervasyonunuz başarıyla onaylanmıştır. Aşağıda rezervasyon detaylarınızı bulabilirsiniz:', 'villa-reservation-system'); ?></p>
            
            <?php if ($villa && has_post_thumbnail($villa->ID)) : ?>
                <?php echo get_the_post_thumbnail($villa->ID, 'medium', array('class' => 'villa-image')); ?>
            <?php endif; ?>
            
            <div class="reservation-details">
                <h3 style="margin-top: 0; color: #2c3e50;"><?php _e('Rezervasyon Detayları', 'villa-reservation-system'); ?></h3>
                
                <div class="detail-row">
                    <span class="detail-label"><?php _e('Rezervasyon No:', 'villa-reservation-system'); ?></span>
                    <span class="detail-value">#<?php echo esc_html($reservation->id); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label"><?php _e('Villa:', 'villa-reservation-system'); ?></span>
                    <span class="detail-value"><?php echo $villa ? esc_html($villa->post_title) : __('Villa Bulunamadı', 'villa-reservation-system'); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label"><?php _e('Giriş Tarihi:', 'villa-reservation-system'); ?></span>
                    <span class="detail-value"><?php echo date_i18n('d F Y', strtotime($reservation->checkin_date)); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label"><?php _e('Çıkış Tarihi:', 'villa-reservation-system'); ?></span>
                    <span class="detail-value"><?php echo date_i18n('d F Y', strtotime($reservation->checkout_date)); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label"><?php _e('Misafir Sayısı:', 'villa-reservation-system'); ?></span>
                    <span class="detail-value">
                        <?php printf(__('%d Yetişkin', 'villa-reservation-system'), $reservation->adults); ?>
                        <?php if ($reservation->children > 0) : ?>
                            <?php printf(__(', %d Çocuk', 'villa-reservation-system'), $reservation->children); ?>
                        <?php endif; ?>
                    </span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label"><?php _e('Toplam Tutar:', 'villa-reservation-system'); ?></span>
                    <span class="detail-value" style="font-size: 18px; font-weight: 600; color: #28a745;">
                        <?php echo number_format($reservation->total_price, 2); ?> ₺
                    </span>
                </div>
            </div>
            
            <?php if ($reservation->status === 'pending' && !empty($checkout_url)) : ?>
            <div style="text-align: center; margin: 30px 0;">
                <p><?php _e('Rezervasyonunuzu tamamlamak için ödemenizi gerçekleştirin:', 'villa-reservation-system'); ?></p>
                <a href="<?php echo esc_url($checkout_url); ?>" class="payment-button">
                    <?php _e('Ödeme Yap', 'villa-reservation-system'); ?>
                </a>
            </div>
            <?php endif; ?>
            
            <?php if ($guest_info->special_requests) : ?>
            <div class="reservation-details">
                <h3 style="margin-top: 0; color: #2c3e50;"><?php _e('Özel İstekleriniz', 'villa-reservation-system'); ?></h3>
                <p><?php echo nl2br(esc_html($guest_info->special_requests)); ?></p>
            </div>
            <?php endif; ?>
            
            <div style="margin: 30px 0;">
                <h3 style="color: #2c3e50;"><?php _e('Önemli Bilgiler', 'villa-reservation-system'); ?></h3>
                <ul style="padding-left: 20px;">
                    <li><?php _e('Check-in saati: 16:00', 'villa-reservation-system'); ?></li>
                    <li><?php _e('Check-out saati: 11:00', 'villa-reservation-system'); ?></li>
                    <li><?php _e('İptal politikası: Giriş tarihinden 24 saat önce ücretsiz iptal', 'villa-reservation-system'); ?></li>
                    <li><?php _e('Evcil hayvan kabul edilmemektedir', 'villa-reservation-system'); ?></li>
                </ul>
            </div>
            
            <p><?php _e('Herhangi bir sorunuz olması durumunda bizimle iletişime geçmekten çekinmeyin.', 'villa-reservation-system'); ?></p>
            
            <p style="margin-top: 30px;">
                <?php _e('Sizi ağırlamak için sabırsızlanıyoruz!', 'villa-reservation-system'); ?><br>
                <strong><?php _e('Villa Rezervasyon Ekibi', 'villa-reservation-system'); ?></strong>
            </p>
        </div>
        
        <div class="footer">
            <p><?php printf(__('Bu e-posta %s için otomatik olarak gönderilmiştir.', 'villa-reservation-system'), get_bloginfo('name')); ?></p>
            <p><?php printf(__('İletişim: %s', 'villa-reservation-system'), get_option('admin_email')); ?></p>
        </div>
    </div>
</body>
</html>