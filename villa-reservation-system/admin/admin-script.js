/**
 * Villa Reservation System - Admin Scripts
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Admin object
    var VRSAdmin = {
        
        /**
         * Initialize admin functionality
         */
        init: function() {
            this.bindEvents();
            this.initDatePickers();
            this.initGoogleSheets();
            this.initPricingToggles();
            this.initCalendar();
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Google Sheets sync
            $(document).on('click', '#sync-all-sheets', this.syncAllSheets);
            $(document).on('click', '.vrs-sync-now', this.syncVilla);
            $(document).on('click', '.vrs-test-connection', this.testConnection);
            
            // Reservation actions
            $(document).on('click', '.vrs-confirm-reservation', this.confirmReservation);
            $(document).on('click', '.vrs-cancel-reservation', this.cancelReservation);
            $(document).on('click', '.vrs-delete-reservation', this.deleteReservation);
            
            // Date blocking
            $(document).on('click', '.vrs-block-dates', this.blockDates);
            $(document).on('click', '.vrs-unblock-dates', this.unblockDates);
            
            // Form submissions
            $(document).on('submit', '.vrs-admin-form', this.handleFormSubmit);
            
            // Quick actions
            $(document).on('click', '.vrs-quick-action', this.handleQuickAction);
            
            // File upload
            $(document).on('change', 'input[name="service_account_json"]', this.handleFileUpload);
        },
        
        /**
         * Initialize date pickers
         */
        initDatePickers: function() {
            $('.vrs-datepicker').datepicker({
                dateFormat: 'yy-mm-dd',
                changeMonth: true,
                changeYear: true,
                minDate: 0,
                monthNames: [
                    'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran',
                    'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'
                ],
                monthNamesShort: [
                    'Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz',
                    'Tem', 'Ağu', 'Eyl', 'Eki', 'Kas', 'Ara'
                ],
                dayNames: [
                    'Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'
                ],
                dayNamesShort: ['Paz', 'Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt'],
                dayNamesMin: ['Pz', 'Pt', 'Sa', 'Ça', 'Pe', 'Cu', 'Ct'],
                firstDay: 1
            });
        },
        
        /**
         * Initialize Google Sheets functionality
         */
        initGoogleSheets: function() {
            // Update sync status indicators
            this.updateSyncStatuses();
            
            // Auto-refresh sync status every 30 seconds
            setInterval(function() {
                VRSAdmin.updateSyncStatuses();
            }, 30000);
        },
        
        /**
         * Initialize pricing toggles
         */
        initPricingToggles: function() {
            $(document).on('change', 'input[name="pricing_type"]', function() {
                var pricingType = $(this).val();
                var $fields = $('.vrs-pricing-fields');
                
                if (pricingType === 'per_person') {
                    $fields.removeClass('hidden');
                } else {
                    $fields.addClass('hidden');
                }
            });
            
            // Initialize on page load
            $('input[name="pricing_type"]:checked').trigger('change');
        },
        
        /**
         * Initialize calendar
         */
        initCalendar: function() {
            if ($('.vrs-calendar').length) {
                this.loadCalendarData();
            }
        },
        
        /**
         * Sync all Google Sheets
         */
        syncAllSheets: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var originalText = $btn.text();
            
            $btn.text(vrsAdmin.strings.loading).prop('disabled', true);
            
            $.ajax({
                url: vrsAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'vrs_sync_all_sheets',
                    nonce: vrsAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        VRSAdmin.showNotice('success', response.data.message || vrsAdmin.strings.success);
                        VRSAdmin.updateSyncStatuses();
                    } else {
                        VRSAdmin.showNotice('error', response.data.message || vrsAdmin.strings.error);
                    }
                },
                error: function() {
                    VRSAdmin.showNotice('error', vrsAdmin.strings.error);
                },
                complete: function() {
                    $btn.text(originalText).prop('disabled', false);
                }
            });
        },
        
        /**
         * Sync individual villa
         */
        syncVilla: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var villaId = $btn.data('villa-id');
            var originalText = $btn.text();
            
            $btn.text(vrsAdmin.strings.loading).prop('disabled', true);
            
            $.ajax({
                url: vrsAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'vrs_sync_villa',
                    villa_id: villaId,
                    nonce: vrsAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        VRSAdmin.showNotice('success', response.data.message || vrsAdmin.strings.success);
                        VRSAdmin.updateVillaSyncStatus(villaId);
                    } else {
                        VRSAdmin.showNotice('error', response.data.message || vrsAdmin.strings.error);
                    }
                },
                error: function() {
                    VRSAdmin.showNotice('error', vrsAdmin.strings.error);
                },
                complete: function() {
                    $btn.text(originalText).prop('disabled', false);
                }
            });
        },
        
        /**
         * Test Google Sheets connection
         */
        testConnection: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var villaId = $btn.data('villa-id');
            var originalText = $btn.text();
            
            $btn.text(vrsAdmin.strings.loading).prop('disabled', true);
            
            $.ajax({
                url: vrsAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'vrs_test_connection',
                    villa_id: villaId,
                    nonce: vrsAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        VRSAdmin.showNotice('success', 'Bağlantı başarılı!');
                    } else {
                        VRSAdmin.showNotice('error', response.data.message || 'Bağlantı hatası!');
                    }
                },
                error: function() {
                    VRSAdmin.showNotice('error', 'Bağlantı test edilemedi!');
                },
                complete: function() {
                    $btn.text(originalText).prop('disabled', false);
                }
            });
        },
        
        /**
         * Confirm reservation
         */
        confirmReservation: function(e) {
            e.preventDefault();
            
            if (!confirm(vrsAdmin.strings.confirm_delete)) {
                return;
            }
            
            var $btn = $(this);
            var reservationId = $btn.data('reservation-id');
            
            $btn.addClass('vrs-loading');
            
            $.ajax({
                url: vrsAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'vrs_confirm_reservation',
                    reservation_id: reservationId,
                    nonce: vrsAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        VRSAdmin.showNotice('success', 'Rezervasyon onaylandı!');
                        location.reload();
                    } else {
                        VRSAdmin.showNotice('error', response.data.message || vrsAdmin.strings.error);
                    }
                },
                error: function() {
                    VRSAdmin.showNotice('error', vrsAdmin.strings.error);
                },
                complete: function() {
                    $btn.removeClass('vrs-loading');
                }
            });
        },
        
        /**
         * Cancel reservation
         */
        cancelReservation: function(e) {
            e.preventDefault();
            
            if (!confirm('Bu rezervasyonu iptal etmek istediğinizden emin misiniz?')) {
                return;
            }
            
            var $btn = $(this);
            var reservationId = $btn.data('reservation-id');
            
            $btn.addClass('vrs-loading');
            
            $.ajax({
                url: vrsAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'vrs_cancel_reservation',
                    reservation_id: reservationId,
                    nonce: vrsAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        VRSAdmin.showNotice('success', 'Rezervasyon iptal edildi!');
                        location.reload();
                    } else {
                        VRSAdmin.showNotice('error', response.data.message || vrsAdmin.strings.error);
                    }
                },
                error: function() {
                    VRSAdmin.showNotice('error', vrsAdmin.strings.error);
                },
                complete: function() {
                    $btn.removeClass('vrs-loading');
                }
            });
        },
        
        /**
         * Delete reservation
         */
        deleteReservation: function(e) {
            e.preventDefault();
            
            if (!confirm(vrsAdmin.strings.confirm_delete)) {
                return;
            }
            
            var $btn = $(this);
            var reservationId = $btn.data('reservation-id');
            
            $btn.addClass('vrs-loading');
            
            $.ajax({
                url: vrsAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'vrs_delete_reservation',
                    reservation_id: reservationId,
                    nonce: vrsAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        VRSAdmin.showNotice('success', 'Rezervasyon silindi!');
                        $btn.closest('tr').fadeOut();
                    } else {
                        VRSAdmin.showNotice('error', response.data.message || vrsAdmin.strings.error);
                    }
                },
                error: function() {
                    VRSAdmin.showNotice('error', vrsAdmin.strings.error);
                },
                complete: function() {
                    $btn.removeClass('vrs-loading');
                }
            });
        },
        
        /**
         * Block dates
         */
        blockDates: function(e) {
            e.preventDefault();
            
            var villaId = prompt('Villa ID:');
            var startDate = prompt('Başlangıç tarihi (YYYY-MM-DD):');
            var endDate = prompt('Bitiş tarihi (YYYY-MM-DD):');
            var reason = prompt('Neden:') || 'Manuel blok';
            
            if (!villaId || !startDate || !endDate) {
                return;
            }
            
            $.ajax({
                url: vrsAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'vrs_block_dates',
                    villa_id: villaId,
                    start_date: startDate,
                    end_date: endDate,
                    reason: reason,
                    nonce: vrsAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        VRSAdmin.showNotice('success', 'Tarihler bloke edildi!');
                        VRSAdmin.refreshCalendar();
                    } else {
                        VRSAdmin.showNotice('error', response.data.message || vrsAdmin.strings.error);
                    }
                },
                error: function() {
                    VRSAdmin.showNotice('error', vrsAdmin.strings.error);
                }
            });
        },
        
        /**
         * Unblock dates
         */
        unblockDates: function(e) {
            e.preventDefault();
            
            var villaId = prompt('Villa ID:');
            var startDate = prompt('Başlangıç tarihi (YYYY-MM-DD):');
            var endDate = prompt('Bitiş tarihi (YYYY-MM-DD):');
            
            if (!villaId || !startDate || !endDate) {
                return;
            }
            
            $.ajax({
                url: vrsAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'vrs_unblock_dates',
                    villa_id: villaId,
                    start_date: startDate,
                    end_date: endDate,
                    nonce: vrsAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        VRSAdmin.showNotice('success', 'Tarihler açıldı!');
                        VRSAdmin.refreshCalendar();
                    } else {
                        VRSAdmin.showNotice('error', response.data.message || vrsAdmin.strings.error);
                    }
                },
                error: function() {
                    VRSAdmin.showNotice('error', vrsAdmin.strings.error);
                }
            });
        },
        
        /**
         * Handle form submissions
         */
        handleFormSubmit: function(e) {
            var $form = $(this);
            var $submitBtn = $form.find('input[type="submit"], button[type="submit"]');
            var originalText = $submitBtn.val() || $submitBtn.text();
            
            $submitBtn.prop('disabled', true).val(vrsAdmin.strings.loading);
            
            // Re-enable after 3 seconds to prevent permanent disable
            setTimeout(function() {
                $submitBtn.prop('disabled', false).val(originalText);
            }, 3000);
        },
        
        /**
         * Handle quick actions
         */
        handleQuickAction: function(e) {
            var $link = $(this);
            var href = $link.attr('href');
            
            // Add loading state
            $link.addClass('vrs-loading');
            
            // Navigate after short delay
            setTimeout(function() {
                window.location.href = href;
            }, 100);
        },
        
        /**
         * Handle file upload
         */
        handleFileUpload: function() {
            var file = this.files[0];
            var $container = $(this).closest('.vrs-credentials-upload');
            
            if (file) {
                if (file.type !== 'application/json') {
                    VRSAdmin.showNotice('error', 'Lütfen geçerli bir JSON dosyası seçin.');
                    return;
                }
                
                $container.addClass('file-selected');
                VRSAdmin.showNotice('success', 'Dosya seçildi: ' + file.name);
            }
        },
        
        /**
         * Update sync statuses
         */
        updateSyncStatuses: function() {
            $.ajax({
                url: vrsAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'vrs_get_sync_statuses',
                    nonce: vrsAdmin.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        $.each(response.data, function(villaId, status) {
                            VRSAdmin.updateVillaSyncStatus(villaId, status);
                        });
                    }
                }
            });
        },
        
        /**
         * Update villa sync status
         */
        updateVillaSyncStatus: function(villaId, status) {
            var $statusEl = $('.vrs-sync-status[data-villa-id="' + villaId + '"]');
            
            if ($statusEl.length && status) {
                $statusEl.removeClass('success error warning')
                        .addClass(status.class)
                        .text(status.message);
            }
        },
        
        /**
         * Load calendar data
         */
        loadCalendarData: function() {
            var month = $('.vrs-calendar').data('month') || new Date().getMonth() + 1;
            var year = $('.vrs-calendar').data('year') || new Date().getFullYear();
            
            $.ajax({
                url: vrsAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'vrs_get_calendar_data',
                    month: month,
                    year: year,
                    nonce: vrsAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        VRSAdmin.renderCalendar(response.data);
                    }
                }
            });
        },
        
        /**
         * Render calendar
         */
        renderCalendar: function(data) {
            var $calendar = $('.vrs-calendar-grid');
            
            // Clear existing content
            $calendar.empty();
            
            // Add day headers
            var dayHeaders = ['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'];
            $.each(dayHeaders, function(index, day) {
                $calendar.append('<div class="vrs-calendar-day-header">' + day + '</div>');
            });
            
            // Add calendar days
            if (data.days) {
                $.each(data.days, function(index, day) {
                    var $dayEl = $('<div class="vrs-calendar-day"></div>');
                    
                    if (day.otherMonth) {
                        $dayEl.addClass('other-month');
                    }
                    
                    if (day.today) {
                        $dayEl.addClass('today');
                    }
                    
                    $dayEl.append('<div class="vrs-calendar-day-number">' + day.number + '</div>');
                    
                    if (day.events) {
                        $.each(day.events, function(i, event) {
                            var $eventEl = $('<div class="vrs-calendar-event"></div>');
                            $eventEl.text(event.title);
                            
                            if (event.type === 'blocked') {
                                $eventEl.addClass('blocked');
                            }
                            
                            $dayEl.append($eventEl);
                        });
                    }
                    
                    $calendar.append($dayEl);
                });
            }
        },
        
        /**
         * Refresh calendar
         */
        refreshCalendar: function() {
            if ($('.vrs-calendar').length) {
                this.loadCalendarData();
            }
        },
        
        /**
         * Show admin notice
         */
        showNotice: function(type, message) {
            var $notice = $('<div class="vrs-admin-notice ' + type + '"><p>' + message + '</p></div>');
            
            // Remove existing notices
            $('.vrs-admin-notice').remove();
            
            // Add new notice
            $('.vrs-admin-page h1').after($notice);
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                $notice.fadeOut();
            }, 5000);
            
            // Scroll to notice
            $('html, body').animate({
                scrollTop: $notice.offset().top - 100
            }, 500);
        },
        
        /**
         * Format currency
         */
        formatCurrency: function(amount) {
            return new Intl.NumberFormat('tr-TR', {
                style: 'currency',
                currency: 'TRY'
            }).format(amount);
        },
        
        /**
         * Format date
         */
        formatDate: function(date) {
            return new Intl.DateTimeFormat('tr-TR').format(new Date(date));
        },
        
        /**
         * Validate form
         */
        validateForm: function($form) {
            var isValid = true;
            
            $form.find('input[required], select[required], textarea[required]').each(function() {
                var $field = $(this);
                var value = $field.val().trim();
                
                if (!value) {
                    $field.addClass('error');
                    isValid = false;
                } else {
                    $field.removeClass('error');
                }
            });
            
            return isValid;
        },
        
        /**
         * Debounce function
         */
        debounce: function(func, wait, immediate) {
            var timeout;
            return function() {
                var context = this, args = arguments;
                var later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                var callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
        }
    };
    
    // Initialize admin functionality
    VRSAdmin.init();
    
    // Make VRSAdmin globally available
    window.VRSAdmin = VRSAdmin;
});

/**
 * Villa meta box functionality
 */
jQuery(document).ready(function($) {
    
    // Villa settings meta box
    if ($('#villa-settings-metabox').length) {
        
        // Capacity validation
        $('#max_adults, #max_children').on('change', function() {
            var maxAdults = parseInt($('#max_adults').val()) || 0;
            var maxChildren = parseInt($('#max_children').val()) || 0;
            var totalCapacity = maxAdults + maxChildren;
            
            $('#max_capacity').val(totalCapacity);
        });
        
        // Stay duration validation
        $('#minimum_stay, #maximum_stay').on('change', function() {
            var minStay = parseInt($('#minimum_stay').val()) || 0;
            var maxStay = parseInt($('#maximum_stay').val()) || 0;
            
            if (maxStay > 0 && minStay > maxStay) {
                alert('Minimum konaklama süresi, maksimum süreden büyük olamaz.');
                $(this).val('');
            }
        });
    }
    
    // Pricing meta box
    if ($('#villa-pricing-metabox').length) {
        
        // Pricing type toggle
        $('input[name="pricing_type"]').on('change', function() {
            var pricingType = $(this).val();
            var $perPersonFields = $('.per-person-pricing');
            
            if (pricingType === 'per_person') {
                $perPersonFields.show();
            } else {
                $perPersonFields.hide();
            }
        });
        
        // Initialize pricing display
        $('input[name="pricing_type"]:checked').trigger('change');
        
        // Price validation
        $('.price-input').on('change', function() {
            var value = parseFloat($(this).val());
            
            if (value < 0) {
                alert('Fiyat 0\'dan küçük olamaz.');
                $(this).val('0');
            }
        });
    }
    
    // Google Sheets meta box
    if ($('#villa-google-sheets-metabox').length) {
        
        // Extract Sheet ID from URL
        $('#google_sheet_url').on('blur', function() {
            var url = $(this).val();
            var sheetId = '';
            
            // Extract sheet ID from Google Sheets URL
            var matches = url.match(/\/spreadsheets\/d\/([a-zA-Z0-9-_]+)/);
            if (matches) {
                sheetId = matches[1];
                $('#google_sheet_id').val(sheetId);
            }
        });
        
        // Test connection button
        $('.vrs-test-connection').on('click', function() {
            var $btn = $(this);
            var villaId = $btn.data('villa-id') || $('#post_ID').val();
            var sheetUrl = $('#google_sheet_url').val();
            
            if (!sheetUrl) {
                alert('Lütfen önce Google Sheets URL\'sini girin.');
                return;
            }
            
            $btn.prop('disabled', true).text('Test ediliyor...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vrs_test_villa_connection',
                    villa_id: villaId,
                    sheet_url: sheetUrl,
                    nonce: $('#vrs_nonce').val()
                },
                success: function(response) {
                    if (response.success) {
                        alert('Bağlantı başarılı!');
                        $('.sync-status').removeClass('error').addClass('success').text('Bağlantı başarılı');
                    } else {
                        alert('Bağlantı hatası: ' + response.data.message);
                        $('.sync-status').removeClass('success').addClass('error').text('Bağlantı hatası');
                    }
                },
                error: function() {
                    alert('Test işlemi başarısız oldu.');
                    $('.sync-status').removeClass('success').addClass('error').text('Test hatası');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Test Bağlantısı');
                }
            });
        });
        
        // Sync now button
        $('.vrs-sync-now').on('click', function() {
            var $btn = $(this);
            var villaId = $btn.data('villa-id') || $('#post_ID').val();
            
            $btn.prop('disabled', true).text('Senkronize ediliyor...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vrs_sync_villa_now',
                    villa_id: villaId,
                    nonce: $('#vrs_nonce').val()
                },
                success: function(response) {
                    if (response.success) {
                        alert('Senkronizasyon tamamlandı!');
                        $('.last-sync').text('Şimdi');
                    } else {
                        alert('Senkronizasyon hatası: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('Senkronizasyon başarısız oldu.');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Şimdi Senkronize Et');
                }
            });
        });
    }
});