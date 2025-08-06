/**
 * Villa Reservation System - Admin JavaScript
 * Handles admin panel interactions and AJAX calls
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Admin object
    const VRSAdmin = {
        
        init: function() {
            this.bindEvents();
            this.initDatePickers();
            this.initCharts();
            this.loadDashboardStats();
        },
        
        bindEvents: function() {
            // Quick block date modal
            $(document).on('click', '#vrs-quick-block-date', this.openQuickBlockModal);
            $(document).on('click', '.vrs-modal-close', this.closeModal);
            $(document).on('submit', '#vrs-quick-block-form', this.handleQuickBlockSubmit);
            
            // Sync Google Sheets
            $(document).on('click', '#vrs-sync-sheets', this.syncAllSheets);
            
            // Test connection buttons
            $(document).on('click', '.vrs-test-connection', this.testConnection);
            $(document).on('click', '.vrs-sync-now', this.syncVilla);
            
            // Pricing type toggle
            $(document).on('change', 'input[name="pricing_type"]', this.togglePricingFields);
            
            // Dashboard stats refresh
            $(document).on('click', '#refresh-stats', this.refreshDashboardStats);
            
            // Export buttons
            $(document).on('click', '.vrs-export-csv', this.exportData);
            
            // Delete confirmations
            $(document).on('click', '.vrs-delete-confirm', this.confirmDelete);
            
            // Modal outside click
            $(document).on('click', '.vrs-modal', function(e) {
                if (e.target === this) {
                    VRSAdmin.closeModal();
                }
            });
            
            // Escape key to close modal
            $(document).on('keyup', function(e) {
                if (e.keyCode === 27) {
                    VRSAdmin.closeModal();
                }
            });
        },
        
        initDatePickers: function() {
            // Initialize date pickers with Turkish localization
            if ($.fn.datepicker) {
                $('.vrs-datepicker').datepicker({
                    dateFormat: 'yy-mm-dd',
                    minDate: 0,
                    changeMonth: true,
                    changeYear: true,
                    showButtonPanel: true,
                    monthNames: ['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran',
                                'Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'],
                    monthNamesShort: ['Oca','Şub','Mar','Nis','May','Haz',
                                     'Tem','Ağu','Eyl','Eki','Kas','Ara'],
                    dayNames: ['Pazar','Pazartesi','Salı','Çarşamba','Perşembe','Cuma','Cumartesi'],
                    dayNamesShort: ['Paz','Pzt','Sal','Çar','Per','Cum','Cmt'],
                    dayNamesMin: ['P','P','S','Ç','P','C','C'],
                    firstDay: 1
                });
            }
        },
        
        initCharts: function() {
            // Initialize Chart.js if available and element exists
            if (typeof Chart !== 'undefined' && $('#reservationChart').length) {
                this.createReservationChart();
            }
        },
        
        createReservationChart: function() {
            const ctx = document.getElementById('reservationChart').getContext('2d');
            
            // Get chart data via AJAX
            $.ajax({
                url: vrsAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vrs_get_chart_data',
                    nonce: vrsAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: response.data.labels,
                                datasets: [{
                                    label: 'Rezervasyonlar',
                                    data: response.data.reservations,
                                    borderColor: '#667eea',
                                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                                    tension: 0.4
                                }, {
                                    label: 'Gelir (₺)',
                                    data: response.data.revenue,
                                    borderColor: '#764ba2',
                                    backgroundColor: 'rgba(118, 75, 162, 0.1)',
                                    tension: 0.4,
                                    yAxisID: 'y1'
                                }]
                            },
                            options: {
                                responsive: true,
                                scales: {
                                    y: {
                                        type: 'linear',
                                        display: true,
                                        position: 'left',
                                    },
                                    y1: {
                                        type: 'linear',
                                        display: true,
                                        position: 'right',
                                        grid: {
                                            drawOnChartArea: false,
                                        },
                                    }
                                }
                            }
                        });
                    }
                }
            });
        },
        
        loadDashboardStats: function() {
            if (!$('.vrs-dashboard-stats').length) return;
            
            $.ajax({
                url: vrsAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vrs_dashboard_stats',
                    nonce: vrsAdmin.nonce,
                    period: 'month',
                    villa_id: 0
                },
                success: function(response) {
                    if (response.success) {
                        // Update stats display if needed
                        console.log('Dashboard stats loaded:', response.data);
                    }
                }
            });
        },
        
        refreshDashboardStats: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const originalText = $button.text();
            
            $button.text(vrsAdmin.strings.loading).prop('disabled', true);
            
            $.ajax({
                url: vrsAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vrs_dashboard_stats',
                    nonce: vrsAdmin.nonce,
                    period: 'month',
                    villa_id: 0
                },
                success: function(response) {
                    if (response.success) {
                        VRSAdmin.showNotice(vrsAdmin.strings.success, 'success');
                        location.reload();
                    } else {
                        VRSAdmin.showNotice(response.data || vrsAdmin.strings.error, 'error');
                    }
                },
                error: function() {
                    VRSAdmin.showNotice(vrsAdmin.strings.error, 'error');
                },
                complete: function() {
                    $button.text(originalText).prop('disabled', false);
                }
            });
        },
        
        openQuickBlockModal: function(e) {
            e.preventDefault();
            $('#vrs-quick-block-modal').fadeIn(300);
        },
        
        closeModal: function() {
            $('.vrs-modal').fadeOut(300);
            $('.vrs-modal form')[0]?.reset();
        },
        
        handleQuickBlockSubmit: function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $submitBtn = $form.find('button[type="submit"]');
            const originalText = $submitBtn.text();
            
            const formData = {
                action: 'vrs_quick_block_date',
                nonce: vrsAdmin.nonce,
                villa_id: $form.find('#vrs-block-villa').val(),
                date: $form.find('#vrs-block-date').val(),
                reason: $form.find('#vrs-block-reason').val()
            };
            
            $submitBtn.addClass('vrs-btn-loading').prop('disabled', true);
            
            $.ajax({
                url: vrsAdmin.ajaxUrl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        VRSAdmin.showNotice(vrsAdmin.strings.dateBlocked, 'success');
                        VRSAdmin.closeModal();
                    } else {
                        VRSAdmin.showNotice(response.data || vrsAdmin.strings.error, 'error');
                    }
                },
                error: function() {
                    VRSAdmin.showNotice(vrsAdmin.strings.error, 'error');
                },
                complete: function() {
                    $submitBtn.removeClass('vrs-btn-loading').prop('disabled', false);
                }
            });
        },
        
        syncAllSheets: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const originalText = $button.text();
            
            $button.html('<span class="vrs-loading"></span> ' + vrsAdmin.strings.loading)
                   .prop('disabled', true);
            
            $.ajax({
                url: vrsAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vrs_sync_all_sheets',
                    nonce: vrsAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        VRSAdmin.showNotice('Google Sheets senkronizasyonu tamamlandı.', 'success');
                    } else {
                        VRSAdmin.showNotice(response.data || vrsAdmin.strings.error, 'error');
                    }
                },
                error: function() {
                    VRSAdmin.showNotice(vrsAdmin.strings.error, 'error');
                },
                complete: function() {
                    $button.text(originalText).prop('disabled', false);
                }
            });
        },
        
        testConnection: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const villaId = $button.data('villa-id');
            const originalText = $button.text();
            
            $button.text(vrsAdmin.strings.loading).prop('disabled', true);
            
            $.ajax({
                url: vrsAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vrs_test_connection',
                    nonce: vrsAdmin.nonce,
                    villa_id: villaId
                },
                success: function(response) {
                    if (response.success) {
                        VRSAdmin.showNotice('Bağlantı başarılı!', 'success');
                        $button.closest('.vrs-sync-status').find('.status-indicator')
                               .removeClass('status-disconnected status-pending')
                               .addClass('status-connected');
                    } else {
                        VRSAdmin.showNotice(response.data || 'Bağlantı hatası!', 'error');
                        $button.closest('.vrs-sync-status').find('.status-indicator')
                               .removeClass('status-connected status-pending')
                               .addClass('status-disconnected');
                    }
                },
                error: function() {
                    VRSAdmin.showNotice(vrsAdmin.strings.error, 'error');
                },
                complete: function() {
                    $button.text(originalText).prop('disabled', false);
                }
            });
        },
        
        syncVilla: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const villaId = $button.data('villa-id');
            const originalText = $button.text();
            
            $button.text(vrsAdmin.strings.loading).prop('disabled', true);
            
            $.ajax({
                url: vrsAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vrs_sync_now',
                    nonce: vrsAdmin.nonce,
                    villa_id: villaId
                },
                success: function(response) {
                    if (response.success) {
                        VRSAdmin.showNotice('Senkronizasyon tamamlandı!', 'success');
                        $button.closest('.vrs-sync-status').find('.last-sync')
                               .text('Son senkronizasyon: ' + new Date().toLocaleString('tr-TR'));
                    } else {
                        VRSAdmin.showNotice(response.data || vrsAdmin.strings.error, 'error');
                    }
                },
                error: function() {
                    VRSAdmin.showNotice(vrsAdmin.strings.error, 'error');
                },
                complete: function() {
                    $button.text(originalText).prop('disabled', false);
                }
            });
        },
        
        togglePricingFields: function() {
            const selectedType = $(this).val();
            const $container = $(this).closest('.vrs-meta-box');
            
            $container.find('.vrs-pricing-toggle').removeClass('show');
            $container.find('.vrs-pricing-' + selectedType).addClass('show');
        },
        
        exportData: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const exportType = $button.data('export-type') || 'csv';
            const originalText = $button.text();
            
            $button.text(vrsAdmin.strings.loading).prop('disabled', true);
            
            $.ajax({
                url: vrsAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vrs_export_data',
                    nonce: vrsAdmin.nonce,
                    export_type: exportType,
                    start_date: $('#export-start-date').val(),
                    end_date: $('#export-end-date').val(),
                    villa_id: $('#export-villa').val()
                },
                success: function(response) {
                    if (response.success && response.data.download_url) {
                        // Create download link
                        const link = document.createElement('a');
                        link.href = response.data.download_url;
                        link.download = response.data.filename;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        
                        VRSAdmin.showNotice('Dışa aktarma tamamlandı!', 'success');
                    } else {
                        VRSAdmin.showNotice(response.data || vrsAdmin.strings.error, 'error');
                    }
                },
                error: function() {
                    VRSAdmin.showNotice(vrsAdmin.strings.error, 'error');
                },
                complete: function() {
                    $button.text(originalText).prop('disabled', false);
                }
            });
        },
        
        confirmDelete: function(e) {
            if (!confirm(vrsAdmin.strings.confirmDelete)) {
                e.preventDefault();
                return false;
            }
            return true;
        },
        
        showNotice: function(message, type = 'info') {
            // Remove existing notices
            $('.vrs-admin-notice').remove();
            
            const noticeClass = 'vrs-admin-notice vrs-notice ' + type;
            const notice = $('<div class="' + noticeClass + '"><p>' + message + '</p></div>');
            
            // Insert after page title
            $('.wrap h1').first().after(notice);
            
            // Auto remove after 5 seconds
            setTimeout(function() {
                notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Scroll to notice
            $('html, body').animate({
                scrollTop: notice.offset().top - 50
            }, 300);
        },
        
        // Calendar specific functions
        initCalendar: function() {
            if (!$('.vrs-calendar-container').length) return;
            
            this.loadCalendarData();
            this.bindCalendarEvents();
        },
        
        loadCalendarData: function(month = null, year = null) {
            const currentDate = new Date();
            const targetMonth = month || (currentDate.getMonth() + 1);
            const targetYear = year || currentDate.getFullYear();
            
            $.ajax({
                url: vrsAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vrs_get_calendar_data',
                    nonce: vrsAdmin.nonce,
                    month: targetMonth,
                    year: targetYear
                },
                success: function(response) {
                    if (response.success) {
                        VRSAdmin.renderCalendar(response.data);
                    }
                }
            });
        },
        
        renderCalendar: function(data) {
            // Calendar rendering logic would go here
            console.log('Calendar data:', data);
        },
        
        bindCalendarEvents: function() {
            // Calendar navigation
            $(document).on('click', '.vrs-calendar-prev', function() {
                // Navigate to previous month
            });
            
            $(document).on('click', '.vrs-calendar-next', function() {
                // Navigate to next month
            });
            
            // Day click events
            $(document).on('click', '.vrs-calendar-day', function() {
                // Handle day selection
            });
        },
        
        // Settings page functions
        initSettings: function() {
            this.bindSettingsEvents();
            this.loadSettingsValidation();
        },
        
        bindSettingsEvents: function() {
            // Test email functionality
            $(document).on('click', '#test-email', this.sendTestEmail);
            
            // Settings form validation
            $('.vrs-settings-form').on('submit', this.validateSettings);
        },
        
        sendTestEmail: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const originalText = $button.text();
            
            $button.text(vrsAdmin.strings.loading).prop('disabled', true);
            
            $.ajax({
                url: vrsAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vrs_send_test_email',
                    nonce: vrsAdmin.nonce,
                    email: $('#admin_email').val()
                },
                success: function(response) {
                    if (response.success) {
                        VRSAdmin.showNotice('Test e-postası gönderildi!', 'success');
                    } else {
                        VRSAdmin.showNotice(response.data || vrsAdmin.strings.error, 'error');
                    }
                },
                error: function() {
                    VRSAdmin.showNotice(vrsAdmin.strings.error, 'error');
                },
                complete: function() {
                    $button.text(originalText).prop('disabled', false);
                }
            });
        },
        
        validateSettings: function(e) {
            let isValid = true;
            const $form = $(this);
            
            // Clear previous errors
            $form.find('.field-error').remove();
            $form.find('.error').removeClass('error');
            
            // Email validation
            const emailFields = $form.find('input[type="email"]');
            emailFields.each(function() {
                const email = $(this).val();
                if (email && !VRSAdmin.isValidEmail(email)) {
                    $(this).addClass('error').after('<span class="field-error">Geçerli bir e-posta adresi girin.</span>');
                    isValid = false;
                }
            });
            
            // Number validation
            const numberFields = $form.find('input[type="number"]');
            numberFields.each(function() {
                const value = parseInt($(this).val());
                const min = parseInt($(this).attr('min'));
                const max = parseInt($(this).attr('max'));
                
                if (value < min || value > max) {
                    $(this).addClass('error').after('<span class="field-error">Değer ' + min + ' ile ' + max + ' arasında olmalıdır.</span>');
                    isValid = false;
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                VRSAdmin.showNotice('Lütfen form hatalarını düzeltin.', 'error');
            }
            
            return isValid;
        },
        
        loadSettingsValidation: function() {
            // Real-time validation
            $(document).on('blur', 'input[type="email"]', function() {
                const $field = $(this);
                const email = $field.val();
                
                $field.next('.field-error').remove();
                $field.removeClass('error');
                
                if (email && !VRSAdmin.isValidEmail(email)) {
                    $field.addClass('error').after('<span class="field-error">Geçerli bir e-posta adresi girin.</span>');
                }
            });
        },
        
        // Utility functions
        isValidEmail: function(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        },
        
        formatPrice: function(price) {
            return new Intl.NumberFormat('tr-TR', {
                style: 'currency',
                currency: 'TRY'
            }).format(price);
        },
        
        formatDate: function(date) {
            return new Date(date).toLocaleDateString('tr-TR');
        }
    };
    
    // Initialize admin functionality
    VRSAdmin.init();
    
    // Page-specific initializations
    if ($('.vrs-calendar-container').length) {
        VRSAdmin.initCalendar();
    }
    
    if ($('.vrs-settings-form').length) {
        VRSAdmin.initSettings();
    }
    
    // Make VRSAdmin globally available
    window.VRSAdmin = VRSAdmin;
    
    // WordPress media uploader integration (if needed)
    if (typeof wp !== 'undefined' && wp.media) {
        $(document).on('click', '.vrs-upload-button', function(e) {
            e.preventDefault();
            
            const button = $(this);
            const targetInput = button.data('target');
            
            const mediaUploader = wp.media({
                title: 'Resim Seç',
                button: {
                    text: 'Seç'
                },
                multiple: false
            });
            
            mediaUploader.on('select', function() {
                const attachment = mediaUploader.state().get('selection').first().toJSON();
                $(targetInput).val(attachment.url);
                button.next('.preview').html('<img src="' + attachment.url + '" style="max-width: 150px;">');
            });
            
            mediaUploader.open();
        });
    }
});

// Additional utility functions outside jQuery ready
(function() {
    'use strict';
    
    // Auto-save functionality for forms
    window.vrsAutoSave = function(formSelector, interval = 30000) {
        const $form = jQuery(formSelector);
        if (!$form.length) return;
        
        setInterval(function() {
            const formData = $form.serialize();
            
            jQuery.ajax({
                url: vrsAdmin.ajaxUrl,
                type: 'POST',
                data: formData + '&action=vrs_auto_save&nonce=' + vrsAdmin.nonce,
                success: function(response) {
                    if (response.success) {
                        console.log('Form auto-saved');
                    }
                }
            });
        }, interval);
    };
    
    // Export to global scope
    window.VRS = window.VRS || {};
    window.VRS.autoSave = window.vrsAutoSave;
    
})();