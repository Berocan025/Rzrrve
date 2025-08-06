/**
 * Villa Reservation System - Admin JavaScript
 */

(function($) {
    'use strict';

    // Admin namespace
    window.VRSAdmin = {
        init: function() {
            this.initMetaBoxes();
            this.initGoogleSheets();
            this.initReservations();
            this.initCalendar();
            this.initPricing();
            this.initAjaxHandlers();
        },

        // Meta box functionality
        initMetaBoxes: function() {
            // Pricing type toggle
            $('input[name="pricing_type"]').on('change', function() {
                var pricingType = $(this).val();
                $('.vrs-pricing-fields').addClass('hidden');
                $('.vrs-pricing-fields.' + pricingType).removeClass('hidden');
            });

            // Trigger initial state
            if ($('input[name="pricing_type"]:checked').length) {
                $('input[name="pricing_type"]:checked').trigger('change');
            }
        },

        // Google Sheets functionality
        initGoogleSheets: function() {
            var self = this;

            // Test connection button
            $('.vrs-test-connection').on('click', function(e) {
                e.preventDefault();
                var button = $(this);
                var villaId = button.data('villa-id');
                
                if (!villaId) {
                    alert('Villa ID bulunamadı!');
                    return;
                }

                self.testGoogleSheetsConnection(villaId, button);
            });

            // Sync now button
            $('.vrs-sync-now').on('click', function(e) {
                e.preventDefault();
                var button = $(this);
                var villaId = button.data('villa-id');
                
                if (!villaId) {
                    alert('Villa ID bulunamadı!');
                    return;
                }

                self.syncGoogleSheets(villaId, button);
            });

            // Credentials upload form
            $('#vrs-credentials-form').on('submit', function(e) {
                e.preventDefault();
                self.saveGoogleCredentials($(this));
            });
        },

        // Reservations functionality
        initReservations: function() {
            var self = this;

            // Reservation status change
            $('.vrs-reservation-status').on('change', function() {
                var reservationId = $(this).data('reservation-id');
                var newStatus = $(this).val();
                self.updateReservationStatus(reservationId, newStatus);
            });

            // Cancel reservation
            $('.vrs-cancel-reservation').on('click', function(e) {
                e.preventDefault();
                if (confirm('Bu rezervasyonu iptal etmek istediğinizden emin misiniz?')) {
                    var reservationId = $(this).data('reservation-id');
                    self.cancelReservation(reservationId);
                }
            });

            // Bulk actions
            $('#doaction, #doaction2').on('click', function(e) {
                var action = $(this).siblings('select').val();
                if (action === 'cancel') {
                    if (!confirm('Seçili rezervasyonları iptal etmek istediğinizden emin misiniz?')) {
                        e.preventDefault();
                    }
                }
            });
        },

        // Calendar functionality
        initCalendar: function() {
            var self = this;
            
            // Calendar navigation
            $('.vrs-calendar-nav button').on('click', function(e) {
                e.preventDefault();
                var direction = $(this).data('direction');
                var currentMonth = $('.vrs-calendar').data('current-month');
                var currentYear = $('.vrs-calendar').data('current-year');
                
                self.navigateCalendar(direction, currentMonth, currentYear);
            });

            // Day click for blocking/unblocking
            $('.vrs-calendar-day').on('click', function(e) {
                if (e.target === this) { // Only if clicking on the day itself, not reservations
                    var date = $(this).data('date');
                    var villaId = $('.vrs-calendar').data('villa-id');
                    var isBlocked = $(this).hasClass('blocked');
                    
                    if (isBlocked) {
                        self.unblockDate(villaId, date, $(this));
                    } else {
                        self.blockDate(villaId, date, $(this));
                    }
                }
            });
        },

        // Pricing functionality
        initPricing: function() {
            // Price calculation preview
            $('input[name="base_price"], input[name="adult_price"], input[name="child_price"], input[name="child_discount"]').on('input', function() {
                // Update price preview if needed
                // This could be expanded to show sample calculations
            });
        },

        // AJAX handlers
        initAjaxHandlers: function() {
            // Generic AJAX error handler
            $(document).ajaxError(function(event, xhr, settings, thrownError) {
                if (xhr.status === 0) return; // Ignore aborted requests
                
                console.error('AJAX Error:', {
                    status: xhr.status,
                    error: thrownError,
                    url: settings.url
                });
                
                VRSAdmin.showMessage('AJAX hatası oluştu. Lütfen tekrar deneyin.', 'error');
            });
        },

        // Google Sheets connection test
        testGoogleSheetsConnection: function(villaId, button) {
            var originalText = button.text();
            button.text('Test ediliyor...').prop('disabled', true);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vrs_test_connection',
                    villa_id: villaId,
                    nonce: vrsAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        VRSAdmin.showMessage('Google Sheets bağlantısı başarılı!', 'success');
                        VRSAdmin.updateConnectionStatus(villaId, 'connected');
                    } else {
                        VRSAdmin.showMessage('Bağlantı hatası: ' + (response.data.message || 'Bilinmeyen hata'), 'error');
                        VRSAdmin.updateConnectionStatus(villaId, 'error');
                    }
                },
                error: function() {
                    VRSAdmin.showMessage('Bağlantı testi başarısız oldu.', 'error');
                    VRSAdmin.updateConnectionStatus(villaId, 'error');
                },
                complete: function() {
                    button.text(originalText).prop('disabled', false);
                }
            });
        },

        // Google Sheets sync
        syncGoogleSheets: function(villaId, button) {
            var originalText = button.text();
            button.text('Senkronize ediliyor...').prop('disabled', true);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vrs_sync_now',
                    villa_id: villaId,
                    nonce: vrsAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        VRSAdmin.showMessage('Senkronizasyon tamamlandı!', 'success');
                        VRSAdmin.updateLastSyncTime(villaId);
                        // Reload the page to show updated data
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        VRSAdmin.showMessage('Senkronizasyon hatası: ' + (response.data.message || 'Bilinmeyen hata'), 'error');
                    }
                },
                error: function() {
                    VRSAdmin.showMessage('Senkronizasyon başarısız oldu.', 'error');
                },
                complete: function() {
                    button.text(originalText).prop('disabled', false);
                }
            });
        },

        // Save Google credentials
        saveGoogleCredentials: function(form) {
            var formData = new FormData(form[0]);
            var submitButton = form.find('input[type="submit"]');
            var originalValue = submitButton.val();
            
            submitButton.val('Kaydediliyor...').prop('disabled', true);

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        VRSAdmin.showMessage('Google API kimlik bilgileri başarıyla kaydedildi!', 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        VRSAdmin.showMessage('Kaydetme hatası: ' + (response.data.message || 'Bilinmeyen hata'), 'error');
                    }
                },
                error: function() {
                    VRSAdmin.showMessage('Kimlik bilgileri kaydedilemedi.', 'error');
                },
                complete: function() {
                    submitButton.val(originalValue).prop('disabled', false);
                }
            });
        },

        // Update reservation status
        updateReservationStatus: function(reservationId, newStatus) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vrs_update_reservation_status',
                    reservation_id: reservationId,
                    status: newStatus,
                    nonce: vrsAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        VRSAdmin.showMessage('Rezervasyon durumu güncellendi.', 'success');
                        // Update the status badge
                        var row = $('tr[data-reservation-id="' + reservationId + '"]');
                        var statusCell = row.find('.vrs-status');
                        statusCell.removeClass().addClass('vrs-status ' + newStatus).text(newStatus.toUpperCase());
                    } else {
                        VRSAdmin.showMessage('Durum güncellenemedi: ' + (response.data.message || 'Bilinmeyen hata'), 'error');
                    }
                },
                error: function() {
                    VRSAdmin.showMessage('Rezervasyon durumu güncellenemedi.', 'error');
                }
            });
        },

        // Cancel reservation
        cancelReservation: function(reservationId) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vrs_cancel_reservation',
                    reservation_id: reservationId,
                    nonce: vrsAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        VRSAdmin.showMessage('Rezervasyon iptal edildi.', 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        VRSAdmin.showMessage('İptal işlemi başarısız: ' + (response.data.message || 'Bilinmeyen hata'), 'error');
                    }
                },
                error: function() {
                    VRSAdmin.showMessage('Rezervasyon iptal edilemedi.', 'error');
                }
            });
        },

        // Navigate calendar
        navigateCalendar: function(direction, currentMonth, currentYear) {
            var newMonth = currentMonth;
            var newYear = currentYear;
            
            if (direction === 'prev') {
                newMonth--;
                if (newMonth < 1) {
                    newMonth = 12;
                    newYear--;
                }
            } else if (direction === 'next') {
                newMonth++;
                if (newMonth > 12) {
                    newMonth = 1;
                    newYear++;
                }
            }

            // Reload calendar with new date
            var villaId = $('.vrs-calendar').data('villa-id');
            this.loadCalendar(villaId, newMonth, newYear);
        },

        // Load calendar
        loadCalendar: function(villaId, month, year) {
            var calendar = $('.vrs-calendar');
            var originalContent = calendar.html();
            
            calendar.html('<div class="vrs-loading"><div class="vrs-spinner"></div>Takvim yükleniyor...</div>');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vrs_load_calendar',
                    villa_id: villaId,
                    month: month,
                    year: year,
                    nonce: vrsAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        calendar.html(response.data.html);
                        calendar.data('current-month', month).data('current-year', year);
                        VRSAdmin.initCalendar(); // Re-initialize calendar events
                    } else {
                        calendar.html(originalContent);
                        VRSAdmin.showMessage('Takvim yüklenemedi: ' + (response.data.message || 'Bilinmeyen hata'), 'error');
                    }
                },
                error: function() {
                    calendar.html(originalContent);
                    VRSAdmin.showMessage('Takvim yüklenirken hata oluştu.', 'error');
                }
            });
        },

        // Block date
        blockDate: function(villaId, date, dayElement) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vrs_block_date',
                    villa_id: villaId,
                    date: date,
                    nonce: vrsAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        dayElement.addClass('blocked');
                        VRSAdmin.showMessage('Tarih bloke edildi.', 'success');
                    } else {
                        VRSAdmin.showMessage('Tarih bloke edilemedi: ' + (response.data.message || 'Bilinmeyen hata'), 'error');
                    }
                },
                error: function() {
                    VRSAdmin.showMessage('Tarih bloke edilirken hata oluştu.', 'error');
                }
            });
        },

        // Unblock date
        unblockDate: function(villaId, date, dayElement) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vrs_unblock_date',
                    villa_id: villaId,
                    date: date,
                    nonce: vrsAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        dayElement.removeClass('blocked');
                        VRSAdmin.showMessage('Tarih blokesi kaldırıldı.', 'success');
                    } else {
                        VRSAdmin.showMessage('Tarih blokesi kaldırılamadı: ' + (response.data.message || 'Bilinmeyen hata'), 'error');
                    }
                },
                error: function() {
                    VRSAdmin.showMessage('Tarih blokesi kaldırılırken hata oluştu.', 'error');
                }
            });
        },

        // Update connection status indicator
        updateConnectionStatus: function(villaId, status) {
            var indicator = $('.vrs-connection-indicator[data-villa-id="' + villaId + '"]');
            indicator.removeClass('connected error').addClass(status);
        },

        // Update last sync time
        updateLastSyncTime: function(villaId) {
            var now = new Date();
            var timeString = now.toLocaleString('tr-TR');
            $('.vrs-last-sync[data-villa-id="' + villaId + '"]').text('Son senkronizasyon: ' + timeString);
        },

        // Show admin message
        showMessage: function(message, type) {
            type = type || 'info';
            
            // Remove existing messages
            $('.vrs-message').remove();
            
            // Create new message
            var messageHtml = '<div class="vrs-message ' + type + '">';
            messageHtml += '<span class="dashicons dashicons-';
            
            switch (type) {
                case 'success':
                    messageHtml += 'yes-alt';
                    break;
                case 'error':
                    messageHtml += 'dismiss';
                    break;
                case 'warning':
                    messageHtml += 'warning';
                    break;
                default:
                    messageHtml += 'info';
            }
            
            messageHtml += '"></span>' + message + '</div>';
            
            // Insert message at the top of the admin container
            $('.vrs-admin-container').prepend(messageHtml);
            
            // Auto-hide success messages
            if (type === 'success') {
                setTimeout(function() {
                    $('.vrs-message.success').fadeOut();
                }, 5000);
            }
        },

        // Utility functions
        formatPrice: function(price) {
            return new Intl.NumberFormat('tr-TR', {
                style: 'currency',
                currency: 'TRY'
            }).format(price);
        },

        formatDate: function(dateString) {
            var date = new Date(dateString);
            return date.toLocaleDateString('tr-TR');
        },

        formatDateTime: function(dateString) {
            var date = new Date(dateString);
            return date.toLocaleString('tr-TR');
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        VRSAdmin.init();
    });

    // Handle tab functionality if present
    $('.nav-tab-wrapper .nav-tab').on('click', function(e) {
        e.preventDefault();
        
        var target = $(this).attr('href');
        
        // Update active tab
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Show target content
        $('.tab-content').hide();
        $(target).show();
    });

    // Handle accordion functionality
    $('.vrs-accordion-header').on('click', function() {
        var content = $(this).next('.vrs-accordion-content');
        var icon = $(this).find('.dashicons');
        
        if (content.is(':visible')) {
            content.slideUp();
            icon.removeClass('dashicons-arrow-up').addClass('dashicons-arrow-down');
        } else {
            content.slideDown();
            icon.removeClass('dashicons-arrow-down').addClass('dashicons-arrow-up');
        }
    });

    // Handle sortable tables if present
    if ($.fn.sortable) {
        $('.vrs-sortable-table tbody').sortable({
            handle: '.dashicons-menu',
            cursor: 'move',
            placeholder: 'sortable-placeholder',
            update: function(event, ui) {
                // Handle sort order update via AJAX if needed
                var order = $(this).sortable('toArray', { attribute: 'data-id' });
                console.log('New order:', order);
            }
        });
    }

})(jQuery);