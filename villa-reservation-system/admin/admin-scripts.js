/**
 * Villa Reservation System - Admin Scripts
 */

jQuery(document).ready(function($) {
    
    // Pricing type toggle
    $('.vrs-pricing-type-radio').on('change', function() {
        var selectedType = $(this).val();
        $('.vrs-pricing-fields').removeClass('active');
        $('.vrs-pricing-fields[data-type="' + selectedType + '"]').addClass('active');
    });
    
    // Initialize pricing fields on page load
    var activePricingType = $('.vrs-pricing-type-radio:checked').val();
    if (activePricingType) {
        $('.vrs-pricing-fields[data-type="' + activePricingType + '"]').addClass('active');
    }
    
    // Google Sheets test connection
    $('.vrs-test-connection').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var villaId = $button.data('villa-id');
        var originalText = $button.text();
        
        $button.text(vrsAdmin.testingText).prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'vrs_test_google_sheets_connection',
                villa_id: villaId,
                nonce: vrsAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showAdminMessage(vrsAdmin.connectionSuccessText, 'success');
                    updateSyncStatus('success', vrsAdmin.connectionSuccessText);
                } else {
                    showAdminMessage(vrsAdmin.connectionFailedText + ' ' + (response.data.message || ''), 'error');
                    updateSyncStatus('error', response.data.message || vrsAdmin.connectionFailedText);
                }
            },
            error: function() {
                showAdminMessage(vrsAdmin.ajaxErrorText, 'error');
                updateSyncStatus('error', vrsAdmin.ajaxErrorText);
            },
            complete: function() {
                $button.text(originalText).prop('disabled', false);
            }
        });
    });
    
    // Google Sheets sync now
    $('.vrs-sync-now').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var villaId = $button.data('villa-id');
        var originalText = $button.text();
        
        $button.text(vrsAdmin.syncingText).prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'vrs_sync_google_sheets_now',
                villa_id: villaId,
                nonce: vrsAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showAdminMessage(vrsAdmin.syncSuccessText, 'success');
                    updateSyncStatus('success', vrsAdmin.syncSuccessText);
                    updateLastSyncTime();
                } else {
                    showAdminMessage(vrsAdmin.syncFailedText + ' ' + (response.data.message || ''), 'error');
                    updateSyncStatus('error', response.data.message || vrsAdmin.syncFailedText);
                }
            },
            error: function() {
                showAdminMessage(vrsAdmin.ajaxErrorText, 'error');
                updateSyncStatus('error', vrsAdmin.ajaxErrorText);
            },
            complete: function() {
                $button.text(originalText).prop('disabled', false);
            }
        });
    });
    
    // Villa settings AJAX update
    $('.vrs-villa-settings-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitButton = $form.find('input[type="submit"]');
        var originalText = $submitButton.val();
        
        $submitButton.val(vrsAdmin.savingText).prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: $form.serialize() + '&action=vrs_update_villa_settings&nonce=' + vrsAdmin.nonce,
            success: function(response) {
                if (response.success) {
                    showAdminMessage(vrsAdmin.settingsUpdatedText, 'success');
                } else {
                    showAdminMessage(vrsAdmin.settingsUpdateFailedText + ' ' + (response.data.message || ''), 'error');
                }
            },
            error: function() {
                showAdminMessage(vrsAdmin.ajaxErrorText, 'error');
            },
            complete: function() {
                $submitButton.val(originalText).prop('disabled', false);
            }
        });
    });
    
    // Reservation status change
    $('.vrs-reservation-status-select').on('change', function() {
        var $select = $(this);
        var reservationId = $select.data('reservation-id');
        var newStatus = $select.val();
        var originalStatus = $select.data('original-status');
        
        if (newStatus === originalStatus) {
            return;
        }
        
        if (!confirm(vrsAdmin.confirmStatusChangeText)) {
            $select.val(originalStatus);
            return;
        }
        
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
                    showAdminMessage(vrsAdmin.statusUpdatedText, 'success');
                    $select.data('original-status', newStatus);
                    updateReservationRow(reservationId, newStatus);
                } else {
                    showAdminMessage(vrsAdmin.statusUpdateFailedText + ' ' + (response.data.message || ''), 'error');
                    $select.val(originalStatus);
                }
            },
            error: function() {
                showAdminMessage(vrsAdmin.ajaxErrorText, 'error');
                $select.val(originalStatus);
            }
        });
    });
    
    // Cancel reservation
    $('.vrs-cancel-reservation').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm(vrsAdmin.confirmCancelText)) {
            return;
        }
        
        var $button = $(this);
        var reservationId = $button.data('reservation-id');
        var originalText = $button.text();
        
        $button.text(vrsAdmin.cancellingText).prop('disabled', true);
        
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
                    showAdminMessage(vrsAdmin.cancelSuccessText, 'success');
                    updateReservationRow(reservationId, 'cancelled');
                    $button.remove();
                } else {
                    showAdminMessage(vrsAdmin.cancelFailedText + ' ' + (response.data.message || ''), 'error');
                    $button.text(originalText).prop('disabled', false);
                }
            },
            error: function() {
                showAdminMessage(vrsAdmin.ajaxErrorText, 'error');
                $button.text(originalText).prop('disabled', false);
            }
        });
    });
    
    // Reservation filters
    $('.vrs-reservation-filters select, .vrs-reservation-filters input').on('change', function() {
        var $form = $(this).closest('form');
        $form.submit();
    });
    
    // Calendar villa selection
    $('#vrs-calendar-villa-select').on('change', function() {
        var villaId = $(this).val();
        if (villaId) {
            loadCalendar(villaId);
        } else {
            $('#vrs-calendar-display').html('<p>' + vrsAdmin.selectVillaText + '</p>');
        }
    });
    
    // Load calendar for selected villa
    function loadCalendar(villaId) {
        var $calendarDisplay = $('#vrs-calendar-display');
        $calendarDisplay.html('<div class="vrs-loading"><div class="vrs-loading-spinner"></div>' + vrsAdmin.loadingCalendarText + '</div>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'vrs_load_calendar',
                villa_id: villaId,
                nonce: vrsAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $calendarDisplay.html(response.data.html);
                    initializeCalendarEvents();
                } else {
                    $calendarDisplay.html('<div class="vrs-error"><h4>' + vrsAdmin.calendarLoadFailedText + '</h4><p>' + (response.data.message || '') + '</p></div>');
                }
            },
            error: function() {
                $calendarDisplay.html('<div class="vrs-error"><h4>' + vrsAdmin.calendarLoadErrorText + '</h4><p>' + vrsAdmin.ajaxErrorText + '</p></div>');
            }
        });
    }
    
    // Initialize calendar events
    function initializeCalendarEvents() {
        // Calendar navigation
        $('.vrs-calendar-nav button').on('click', function() {
            var direction = $(this).data('direction');
            var currentMonth = $('.vrs-calendar-header').data('current-month');
            var currentYear = $('.vrs-calendar-header').data('current-year');
            var villaId = $('#vrs-calendar-villa-select').val();
            
            // Calculate new month/year
            var newDate = new Date(currentYear, currentMonth + direction, 1);
            var newMonth = newDate.getMonth();
            var newYear = newDate.getFullYear();
            
            loadCalendarMonth(villaId, newMonth, newYear);
        });
        
        // Date blocking/unblocking
        $('.vrs-calendar-date').on('click', function() {
            var $date = $(this);
            var date = $date.data('date');
            var villaId = $('#vrs-calendar-villa-select').val();
            var isBlocked = $date.hasClass('blocked');
            
            if (isBlocked) {
                unblockDate(villaId, date, $date);
            } else {
                blockDate(villaId, date, $date);
            }
        });
    }
    
    // Block date
    function blockDate(villaId, date, $dateElement) {
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
                    $dateElement.addClass('blocked');
                    showAdminMessage(vrsAdmin.dateBlockedText, 'success');
                } else {
                    showAdminMessage(vrsAdmin.dateBlockFailedText + ' ' + (response.data.message || ''), 'error');
                }
            },
            error: function() {
                showAdminMessage(vrsAdmin.dateBlockErrorText, 'error');
            }
        });
    }
    
    // Unblock date
    function unblockDate(villaId, date, $dateElement) {
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
                    $dateElement.removeClass('blocked');
                    showAdminMessage(vrsAdmin.dateUnblockedText, 'success');
                } else {
                    showAdminMessage(vrsAdmin.dateUnblockFailedText + ' ' + (response.data.message || ''), 'error');
                }
            },
            error: function() {
                showAdminMessage(vrsAdmin.dateUnblockErrorText, 'error');
            }
        });
    }
    
    // Load specific calendar month
    function loadCalendarMonth(villaId, month, year) {
        var $calendarDisplay = $('#vrs-calendar-display');
        $calendarDisplay.html('<div class="vrs-loading"><div class="vrs-loading-spinner"></div>' + vrsAdmin.loadingCalendarText + '</div>');
        
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
                    $calendarDisplay.html(response.data.html);
                    initializeCalendarEvents();
                } else {
                    $calendarDisplay.html('<div class="vrs-error"><h4>' + vrsAdmin.calendarLoadFailedText + '</h4><p>' + (response.data.message || '') + '</p></div>');
                }
            },
            error: function() {
                $calendarDisplay.html('<div class="vrs-error"><h4>' + vrsAdmin.calendarLoadErrorText + '</h4><p>' + vrsAdmin.ajaxErrorText + '</p></div>');
            }
        });
    }
    
    // Helper functions
    function showAdminMessage(message, type) {
        var messageClass = 'notice-' + type;
        var $notice = $('<div class="notice ' + messageClass + ' is-dismissible"><p>' + message + '</p></div>');
        
        $('.wrap h1').after($notice);
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
        
        // Add dismiss functionality
        $notice.on('click', '.notice-dismiss', function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        });
    }
    
    function updateSyncStatus(status, message) {
        var $syncStatus = $('.vrs-sync-status');
        $syncStatus.removeClass('success error warning').addClass(status);
        $syncStatus.text(message);
    }
    
    function updateLastSyncTime() {
        var now = new Date();
        var timeString = now.toLocaleString();
        $('.vrs-last-sync-time').text(timeString);
    }
    
    function updateReservationRow(reservationId, newStatus) {
        var $row = $('[data-reservation-id="' + reservationId + '"]').closest('tr');
        var $statusCell = $row.find('.reservation-status');
        
        // Update status badge
        var statusLabels = {
            'pending': vrsAdmin.statusLabels.pending,
            'confirmed': vrsAdmin.statusLabels.confirmed,
            'cancelled': vrsAdmin.statusLabels.cancelled,
            'completed': vrsAdmin.statusLabels.completed
        };
        
        var statusClass = 'vrs-status-' + newStatus;
        var statusLabel = statusLabels[newStatus] || newStatus;
        
        $statusCell.html('<span class="vrs-status-badge ' + statusClass + '">' + statusLabel + '</span>');
        
        // Update row styling if needed
        $row.removeClass('status-pending status-confirmed status-cancelled status-completed')
            .addClass('status-' + newStatus);
    }
    
    // Google Sheets credentials upload
    $('#vrs-google-credentials-form').on('submit', function(e) {
        var fileInput = $('#google_credentials_file')[0];
        if (!fileInput.files.length) {
            e.preventDefault();
            alert(vrsAdmin.selectFileText);
            return;
        }
        
        var file = fileInput.files[0];
        if (file.type !== 'application/json') {
            e.preventDefault();
            alert(vrsAdmin.invalidFileTypeText);
            return;
        }
    });
    
    // Villa meta box tabs (if implementing tabbed interface)
    $('.vrs-meta-box-tab').on('click', function(e) {
        e.preventDefault();
        var tabId = $(this).data('tab');
        
        // Hide all tab contents
        $('.vrs-meta-box-content').hide();
        $('.vrs-meta-box-tab').removeClass('active');
        
        // Show selected tab
        $('#' + tabId).show();
        $(this).addClass('active');
    });
    
    // Initialize first tab as active
    $('.vrs-meta-box-tab:first').trigger('click');
    
    // Bulk actions for reservations
    $('#doaction, #doaction2').on('click', function(e) {
        var action = $(this).siblings('select').val();
        if (action === 'cancel') {
            var checkedBoxes = $('input[name="reservation[]"]:checked');
            if (checkedBoxes.length === 0) {
                e.preventDefault();
                alert(vrsAdmin.selectReservationsText);
                return;
            }
            
            if (!confirm(vrsAdmin.confirmBulkCancelText)) {
                e.preventDefault();
                return;
            }
        }
    });
    
    // Auto-refresh reservations (every 30 seconds)
    if ($('.vrs-reservations-list').length > 0) {
        setInterval(function() {
            refreshReservationsList();
        }, 30000);
    }
    
    function refreshReservationsList() {
        var $table = $('.vrs-reservations-list table tbody');
        var originalHtml = $table.html();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'vrs_refresh_reservations',
                nonce: vrsAdmin.nonce
            },
            success: function(response) {
                if (response.success && response.data.html !== originalHtml) {
                    $table.html(response.data.html);
                    showAdminMessage(vrsAdmin.reservationsUpdatedText, 'info');
                }
            },
            error: function() {
                // Silently fail for auto-refresh
                console.log('Auto-refresh failed');
            }
        });
    }
    
    // Tooltips for admin interface
    $('.vrs-tooltip').on('mouseenter', function() {
        var tooltip = $(this).data('tooltip');
        var $tooltip = $('<div class="vrs-tooltip-content">' + tooltip + '</div>');
        
        $('body').append($tooltip);
        
        var offset = $(this).offset();
        $tooltip.css({
            position: 'absolute',
            top: offset.top - $tooltip.outerHeight() - 5,
            left: offset.left + ($(this).outerWidth() / 2) - ($tooltip.outerWidth() / 2),
            zIndex: 9999
        });
    }).on('mouseleave', function() {
        $('.vrs-tooltip-content').remove();
    });
});

// Global admin object for localization
var vrsAdmin = vrsAdmin || {};

// Add global functions for external use
window.VRSAdmin = {
    showMessage: function(message, type) {
        jQuery(function($) {
            var messageClass = 'notice-' + type;
            var $notice = $('<div class="notice ' + messageClass + ' is-dismissible"><p>' + message + '</p></div>');
            $('.wrap h1').after($notice);
            
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        });
    },
    
    refreshData: function() {
        jQuery(function($) {
            location.reload();
        });
    }
};