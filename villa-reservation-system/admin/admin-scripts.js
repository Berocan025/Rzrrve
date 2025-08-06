/**
 * Villa Reservation System - Admin Scripts
 * 
 * @package VillaReservationSystem
 * @version 1.0.0
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Admin AJAX handler
    function adminAjax(action, data, callback) {
        var requestData = {
            action: 'vrs_admin_action',
            admin_action: action,
            nonce: vrsAdmin.nonce
        };
        
        $.extend(requestData, data);
        
        $.post(vrsAdmin.ajaxurl, requestData)
            .done(function(response) {
                if (response.success) {
                    if (callback && typeof callback === 'function') {
                        callback(true, response.data);
                    }
                    showNotice(response.data.message || vrsAdmin.strings.success, 'success');
                } else {
                    if (callback && typeof callback === 'function') {
                        callback(false, response.data);
                    }
                    showNotice(response.data.message || vrsAdmin.strings.error, 'error');
                }
            })
            .fail(function() {
                if (callback && typeof callback === 'function') {
                    callback(false, null);
                }
                showNotice(vrsAdmin.strings.error, 'error');
            });
    }
    
    // Show admin notice
    function showNotice(message, type) {
        var noticeClass = 'notice notice-' + type + ' is-dismissible';
        var noticeHtml = '<div class="' + noticeClass + '"><p>' + message + '</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
        
        $('.wrap h1').after(noticeHtml);
        
        // Auto dismiss success notices after 3 seconds
        if (type === 'success') {
            setTimeout(function() {
                $('.notice-success').fadeOut();
            }, 3000);
        }
    }
    
    // Loading state management
    function setLoadingState(element, loading) {
        if (loading) {
            element.prop('disabled', true).addClass('loading');
            element.find('.text').hide();
            if (!element.find('.spinner').length) {
                element.append('<span class="spinner is-active" style="float: none; margin: 0 5px;"></span>');
            }
        } else {
            element.prop('disabled', false).removeClass('loading');
            element.find('.text').show();
            element.find('.spinner').remove();
        }
    }
    
    // Confirm and Cancel Reservations
    $(document).on('click', '.vrs-confirm-reservation', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var reservationId = button.data('id');
        
        if (!reservationId) {
            showNotice('Geçersiz rezervasyon ID.', 'error');
            return;
        }
        
        if (!confirm('Bu rezervasyonu onaylamak istediğinizden emin misiniz?')) {
            return;
        }
        
        setLoadingState(button, true);
        
        adminAjax('confirm_reservation', {
            reservation_id: reservationId
        }, function(success, data) {
            setLoadingState(button, false);
            
            if (success) {
                // Update status in table
                var row = button.closest('tr');
                row.find('.vrs-status').removeClass('pending').addClass('confirmed').text('Onaylandı');
                button.remove();
                
                // Remove cancel button if it's still pending
                var cancelBtn = row.find('.vrs-cancel-reservation');
                if (cancelBtn.length) {
                    cancelBtn.text('İptal Et');
                }
            }
        });
    });
    
    $(document).on('click', '.vrs-cancel-reservation', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var reservationId = button.data('id');
        
        if (!reservationId) {
            showNotice('Geçersiz rezervasyon ID.', 'error');
            return;
        }
        
        if (!confirm(vrsAdmin.strings.confirm_cancel)) {
            return;
        }
        
        setLoadingState(button, true);
        
        adminAjax('cancel_reservation', {
            reservation_id: reservationId
        }, function(success, data) {
            setLoadingState(button, false);
            
            if (success) {
                // Update status in table
                var row = button.closest('tr');
                row.find('.vrs-status').removeClass('pending confirmed').addClass('cancelled').text('İptal');
                row.find('.vrs-confirm-reservation, .vrs-cancel-reservation').remove();
            }
        });
    });
    
    // Google Sheets sync
    $(document).on('click', '.vrs-sync-villa', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var villaId = button.data('villa');
        
        if (!villaId) {
            showNotice('Geçersiz villa ID.', 'error');
            return;
        }
        
        setLoadingState(button, true);
        
        adminAjax('sync_villa', {
            villa_id: villaId
        }, function(success, data) {
            setLoadingState(button, false);
            
            if (success) {
                // Update last sync time in table
                var row = button.closest('tr');
                var now = new Date();
                var timeString = now.toLocaleDateString('tr-TR') + ' ' + now.toLocaleTimeString('tr-TR', {hour: '2-digit', minute: '2-digit'});
                row.find('td:nth-child(4)').text(timeString);
            }
        });
    });
    
    // Villa meta box interactions
    $(document).on('click', '.vrs-test-connection', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var villaId = button.data('villa-id');
        
        if (!villaId) {
            showNotice('Villa ID bulunamadı.', 'error');
            return;
        }
        
        setLoadingState(button, true);
        
        $.post(ajaxurl, {
            action: 'vrs_test_villa_connection',
            villa_id: villaId,
            nonce: $('#vrs_nonce').val()
        })
        .done(function(response) {
            setLoadingState(button, false);
            
            if (response.success) {
                showNotice('Bağlantı başarılı!', 'success');
                $('.vrs-sync-status').removeClass('error').addClass('success').text('Bağlı');
            } else {
                showNotice(response.data.message || 'Bağlantı hatası!', 'error');
                $('.vrs-sync-status').removeClass('success').addClass('error').text('Hata');
            }
        })
        .fail(function() {
            setLoadingState(button, false);
            showNotice('Bağlantı test edilirken hata oluştu.', 'error');
        });
    });
    
    $(document).on('click', '.vrs-sync-now', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var villaId = button.data('villa-id');
        
        if (!villaId) {
            showNotice('Villa ID bulunamadı.', 'error');
            return;
        }
        
        setLoadingState(button, true);
        
        $.post(ajaxurl, {
            action: 'vrs_sync_villa_now',
            villa_id: villaId,
            nonce: $('#vrs_nonce').val()
        })
        .done(function(response) {
            setLoadingState(button, false);
            
            if (response.success) {
                showNotice('Senkronizasyon tamamlandı!', 'success');
                var now = new Date();
                $('.vrs-last-sync').text(now.toLocaleDateString('tr-TR') + ' ' + now.toLocaleTimeString('tr-TR'));
            } else {
                showNotice(response.data.message || 'Senkronizasyon hatası!', 'error');
            }
        })
        .fail(function() {
            setLoadingState(button, false);
            showNotice('Senkronizasyon sırasında hata oluştu.', 'error');
        });
    });
    
    // Pricing type toggle
    $(document).on('change', 'input[name="_vrs_pricing_type"]', function() {
        var pricingType = $(this).val();
        var fixedFields = $('.vrs-pricing-fixed');
        var perPersonFields = $('.vrs-pricing-per-person');
        
        if (pricingType === 'fixed') {
            fixedFields.show();
            perPersonFields.hide();
        } else {
            fixedFields.hide();
            perPersonFields.show();
        }
    });
    
    // Initialize pricing fields visibility
    if ($('input[name="_vrs_pricing_type"]:checked').length) {
        $('input[name="_vrs_pricing_type"]:checked').trigger('change');
    }
    
    // Calendar navigation
    $(document).on('click', '.vrs-calendar-nav a', function(e) {
        e.preventDefault();
        window.location.href = $(this).attr('href');
    });
    
    // Calendar day click
    $(document).on('click', '.vrs-calendar-day:not(.other-month)', function() {
        var date = $(this).data('date');
        if (date) {
            // Open day detail modal or navigate to day view
            // This can be expanded based on requirements
            console.log('Calendar day clicked:', date);
        }
    });
    
    // Reservation details modal
    $(document).on('click', '.vrs-calendar-reservation', function(e) {
        e.stopPropagation();
        var reservationId = $(this).data('reservation-id');
        if (reservationId) {
            // Open reservation details
            window.open(
                vrsAdmin.adminUrl + 'admin.php?page=vrs-reservation-details&id=' + reservationId,
                '_blank'
            );
        }
    });
    
    // Form validation for settings
    $('form[action*="vrs-settings"]').on('submit', function(e) {
        var isValid = true;
        var form = $(this);
        
        // Validate email fields
        form.find('input[type="email"]').each(function() {
            var email = $(this).val();
            if (email && !isValidEmail(email)) {
                showNotice('Geçersiz e-posta adresi: ' + email, 'error');
                isValid = false;
                return false;
            }
        });
        
        // Validate number fields
        form.find('input[type="number"]').each(function() {
            var min = $(this).attr('min');
            var max = $(this).attr('max');
            var value = parseInt($(this).val());
            
            if (min && value < parseInt(min)) {
                showNotice('Minimum değer: ' + min, 'error');
                isValid = false;
                return false;
            }
            
            if (max && value > parseInt(max)) {
                showNotice('Maksimum değer: ' + max, 'error');
                isValid = false;
                return false;
            }
        });
        
        if (!isValid) {
            e.preventDefault();
        }
    });
    
    // File upload validation for Google Sheets
    $('input[name="credentials_file"]').on('change', function() {
        var file = this.files[0];
        
        if (file) {
            // Check file type
            if (file.type !== 'application/json') {
                showNotice('Sadece JSON dosyaları kabul edilir.', 'error');
                $(this).val('');
                return;
            }
            
            // Check file size (max 1MB)
            if (file.size > 1024 * 1024) {
                showNotice('Dosya boyutu 1MB\'dan küçük olmalıdır.', 'error');
                $(this).val('');
                return;
            }
        }
    });
    
    // Data export functionality
    $(document).on('click', '.vrs-export-data', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var exportType = button.data('type');
        var startDate = $('input[name="start_date"]').val();
        var endDate = $('input[name="end_date"]').val();
        
        if (!startDate || !endDate) {
            showNotice('Lütfen tarih aralığı seçin.', 'error');
            return;
        }
        
        // Create download URL
        var downloadUrl = vrsAdmin.adminUrl + 'admin-ajax.php?action=vrs_export_data&type=' + exportType + 
                         '&start_date=' + startDate + '&end_date=' + endDate + '&nonce=' + vrsAdmin.nonce;
        
        // Trigger download
        window.location.href = downloadUrl;
    });
    
    // Auto-refresh functionality for dashboard
    if ($('.vrs-dashboard').length && vrsAdmin.autoRefresh) {
        setInterval(function() {
            $('.vrs-stats-grid .vrs-stat-number').each(function() {
                var statElement = $(this);
                var statType = statElement.closest('.vrs-stat-card').data('stat-type');
                
                if (statType) {
                    // Refresh specific stat
                    $.post(vrsAdmin.ajaxurl, {
                        action: 'vrs_get_stat',
                        stat_type: statType,
                        nonce: vrsAdmin.nonce
                    })
                    .done(function(response) {
                        if (response.success) {
                            statElement.text(response.data.value);
                        }
                    });
                }
            });
        }, 30000); // Refresh every 30 seconds
    }
    
    // Advanced filters toggle
    $(document).on('click', '.vrs-toggle-advanced-filters', function(e) {
        e.preventDefault();
        
        var filtersContainer = $('.vrs-advanced-filters');
        var button = $(this);
        
        filtersContainer.slideToggle();
        
        if (filtersContainer.is(':visible')) {
            button.text('Gelişmiş Filtreleri Gizle');
        } else {
            button.text('Gelişmiş Filtreler');
        }
    });
    
    // Bulk actions for reservations
    $(document).on('change', '.vrs-select-all-reservations', function() {
        var isChecked = $(this).is(':checked');
        $('.vrs-reservation-checkbox').prop('checked', isChecked);
        toggleBulkActions();
    });
    
    $(document).on('change', '.vrs-reservation-checkbox', function() {
        toggleBulkActions();
        
        // Update select all checkbox
        var totalCheckboxes = $('.vrs-reservation-checkbox').length;
        var checkedCheckboxes = $('.vrs-reservation-checkbox:checked').length;
        
        $('.vrs-select-all-reservations').prop('checked', totalCheckboxes === checkedCheckboxes);
    });
    
    function toggleBulkActions() {
        var checkedCount = $('.vrs-reservation-checkbox:checked').length;
        var bulkActions = $('.vrs-bulk-actions');
        
        if (checkedCount > 0) {
            bulkActions.show();
            bulkActions.find('.selected-count').text(checkedCount);
        } else {
            bulkActions.hide();
        }
    }
    
    $(document).on('click', '.vrs-bulk-action-btn', function(e) {
        e.preventDefault();
        
        var action = $(this).data('action');
        var selectedIds = [];
        
        $('.vrs-reservation-checkbox:checked').each(function() {
            selectedIds.push($(this).val());
        });
        
        if (selectedIds.length === 0) {
            showNotice('Lütfen en az bir rezervasyon seçin.', 'error');
            return;
        }
        
        var confirmMessage = 'Seçili ' + selectedIds.length + ' rezervasyon için ' + action + ' işlemini gerçekleştirmek istediğinizden emin misiniz?';
        
        if (!confirm(confirmMessage)) {
            return;
        }
        
        var button = $(this);
        setLoadingState(button, true);
        
        adminAjax('bulk_reservation_action', {
            action: action,
            reservation_ids: selectedIds
        }, function(success, data) {
            setLoadingState(button, false);
            
            if (success) {
                // Refresh page to show updated data
                window.location.reload();
            }
        });
    });
    
    // Real-time search for reservations
    var searchTimeout;
    $(document).on('input', '.vrs-search-reservations', function() {
        var searchTerm = $(this).val();
        var searchContainer = $('.vrs-search-results');
        
        clearTimeout(searchTimeout);
        
        if (searchTerm.length < 3) {
            searchContainer.empty().hide();
            return;
        }
        
        searchTimeout = setTimeout(function() {
            $.post(vrsAdmin.ajaxurl, {
                action: 'vrs_search_reservations',
                search: searchTerm,
                nonce: vrsAdmin.nonce
            })
            .done(function(response) {
                if (response.success && response.data.results) {
                    var resultsHtml = '';
                    response.data.results.forEach(function(reservation) {
                        resultsHtml += '<div class="vrs-search-result" data-id="' + reservation.id + '">';
                        resultsHtml += '<strong>#' + reservation.id + '</strong> - ' + reservation.guest_name;
                        resultsHtml += '<br><small>' + reservation.villa_name + ' (' + reservation.checkin_date + ')</small>';
                        resultsHtml += '</div>';
                    });
                    
                    searchContainer.html(resultsHtml).show();
                } else {
                    searchContainer.html('<div class="vrs-no-results">Sonuç bulunamadı</div>').show();
                }
            });
        }, 500);
    });
    
    // Search result click
    $(document).on('click', '.vrs-search-result', function() {
        var reservationId = $(this).data('id');
        window.location.href = vrsAdmin.adminUrl + 'admin.php?page=vrs-reservation-details&id=' + reservationId;
    });
    
    // Helper functions
    function isValidEmail(email) {
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    function formatDate(date) {
        return date.toLocaleDateString('tr-TR');
    }
    
    function formatTime(date) {
        return date.toLocaleTimeString('tr-TR', {hour: '2-digit', minute: '2-digit'});
    }
    
    function formatCurrency(amount) {
        return new Intl.NumberFormat('tr-TR', {
            style: 'currency',
            currency: 'TRY'
        }).format(amount);
    }
    
    // Initialize tooltips if available
    if ($.fn.tooltip) {
        $('[title]').tooltip();
    }
    
    // Initialize date pickers
    if ($.fn.datepicker) {
        $('.vrs-date-picker').datepicker({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true
        });
    }
    
    // Print functionality
    $(document).on('click', '.vrs-print', function(e) {
        e.preventDefault();
        window.print();
    });
    
    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl+S to save (prevent browser save)
        if (e.ctrlKey && e.keyCode === 83) {
            e.preventDefault();
            var saveButton = $('.vrs-btn-primary[type="submit"]');
            if (saveButton.length) {
                saveButton.click();
            }
        }
        
        // Escape to close modals
        if (e.keyCode === 27) {
            $('.vrs-modal').hide();
            $('.vrs-search-results').hide();
        }
    });
    
    // Dismiss notices
    $(document).on('click', '.notice-dismiss', function() {
        $(this).closest('.notice').fadeOut();
    });
    
    // Initialize page
    console.log('VRS Admin Scripts Loaded');
});