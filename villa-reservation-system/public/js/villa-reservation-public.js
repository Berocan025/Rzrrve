/**
 * Villa Rezervasyon Sistemi - Public JavaScript
 * Tarih seçimi, form doğrulama, AJAX işlemleri, fiyat hesaplama
 */

(function($) {
    'use strict';

    // Global değişkenler
    var vrsSettings = {
        dateFormat: 'dd/mm/yy',
        minDate: 0,
        firstDay: 1, // Pazartesi
        monthNames: ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran',
                    'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'],
        monthNamesShort: ['Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz',
                         'Tem', 'Ağu', 'Eyl', 'Eki', 'Kas', 'Ara'],
        dayNames: ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'],
        dayNamesShort: ['Paz', 'Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt'],
        dayNamesMin: ['Pa', 'Pt', 'Sa', 'Ça', 'Pe', 'Cu', 'Ct']
    };

    // Plugin sınıfı
    function VillaReservationForm(element, options) {
        this.element = $(element);
        this.form = this.element.find('.vrs-reservation-form');
        this.villaId = this.form.find('input[name="villa_id"]').val();
        this.villaData = window.vrsVillaData ? window.vrsVillaData[this.villaId] : {};
        this.blockedDates = [];
        this.isSubmitting = false;
        
        this.init();
    }

    VillaReservationForm.prototype = {
        init: function() {
            this.initDatePickers();
            this.initNumberInputs();
            this.initGuestHandlers();
            this.initFormValidation();
            this.initFormSubmission();
            this.loadBlockedDates();
            
            // Form yüklendiğinde enable/disable kontrolü
            this.checkFormValidity();
        },

        initDatePickers: function() {
            var self = this;
            var $checkinInput = this.form.find('.vrs-checkin-date');
            var $checkoutInput = this.form.find('.vrs-checkout-date');

            // Check-in datepicker
            $checkinInput.datepicker($.extend({}, vrsSettings, {
                onSelect: function(selectedDate) {
                    self.onCheckinSelect(selectedDate);
                },
                beforeShowDay: function(date) {
                    return self.isDateAvailable(date);
                }
            }));

            // Check-out datepicker
            $checkoutInput.datepicker($.extend({}, vrsSettings, {
                onSelect: function(selectedDate) {
                    self.onCheckoutSelect(selectedDate);
                },
                beforeShowDay: function(date) {
                    return self.isDateAvailable(date, true);
                }
            }));

            // Tarih değişikliklerini dinle
            $checkinInput.on('change', function() {
                self.calculateNights();
                self.validateDates();
                self.calculatePrice();
            });

            $checkoutInput.on('change', function() {
                self.calculateNights();
                self.validateDates();
                self.calculatePrice();
            });
        },

        initNumberInputs: function() {
            var self = this;

            // Artırma/azaltma butonları
            this.form.on('click', '.vrs-number-decrease, .vrs-number-increase', function(e) {
                e.preventDefault();
                
                var $button = $(this);
                var targetId = $button.data('target');
                var $input = $('#' + targetId);
                var currentValue = parseInt($input.val()) || 0;
                var min = parseInt($input.attr('min')) || 0;
                var max = parseInt($input.attr('max')) || 999;
                var newValue;

                if ($button.hasClass('vrs-number-decrease')) {
                    newValue = Math.max(min, currentValue - 1);
                } else {
                    newValue = Math.min(max, currentValue + 1);
                }

                $input.val(newValue).trigger('change');
            });

            // Input değişikliklerini dinle
            this.form.find('.vrs-guest-input').on('change input', function() {
                self.updateGuestCounts();
                self.updateChildrenAges();
                self.validateGuests();
                self.calculatePrice();
            });
        },

        initGuestHandlers: function() {
            var self = this;
            
            // Çocuk sayısı değiştiğinde yaş seçimi göster/gizle
            this.form.find('input[name="children"]').on('change', function() {
                self.updateChildrenAges();
            });
        },

        initFormValidation: function() {
            var self = this;

            // Gerçek zamanlı doğrulama
            this.form.find('input[required]').on('blur', function() {
                self.validateField($(this));
            });

            this.form.find('input[type="email"]').on('blur', function() {
                self.validateEmail($(this));
            });

            this.form.find('input[type="tel"]').on('blur', function() {
                self.validatePhone($(this));
            });

            // Şartlar checkbox'ı
            this.form.find('input[name="accept_terms"]').on('change', function() {
                self.checkFormValidity();
            });
        },

        initFormSubmission: function() {
            var self = this;

            this.form.on('submit', function(e) {
                e.preventDefault();
                
                if (self.isSubmitting) {
                    return false;
                }

                if (self.validateForm()) {
                    self.submitForm();
                }
            });
        },

        loadBlockedDates: function() {
            var self = this;

            $.ajax({
                url: vrs_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'vrs_get_blocked_dates',
                    villa_id: this.villaId,
                    nonce: vrs_ajax.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        self.blockedDates = response.data;
                        
                        // Datepicker'ları yenile
                        self.form.find('.vrs-datepicker').datepicker('refresh');
                    }
                }
            });
        },

        onCheckinSelect: function(selectedDate) {
            var $checkoutInput = this.form.find('.vrs-checkout-date');
            var checkinDate = this.parseDate(selectedDate);
            var minCheckoutDate = new Date(checkinDate);
            minCheckoutDate.setDate(minCheckoutDate.getDate() + this.villaData.minimumStay);

            // Check-out datepicker'ın minimum tarihini güncelle
            $checkoutInput.datepicker('option', 'minDate', minCheckoutDate);

            // Eğer mevcut check-out tarihi minimum tarihten küçükse temizle
            var currentCheckout = $checkoutInput.val();
            if (currentCheckout) {
                var checkoutDate = this.parseDate(currentCheckout);
                if (checkoutDate <= checkinDate) {
                    $checkoutInput.val('');
                }
            }
        },

        onCheckoutSelect: function(selectedDate) {
            this.calculateNights();
            this.validateDates();
        },

        calculateNights: function() {
            var checkinStr = this.form.find('.vrs-checkin-date').val();
            var checkoutStr = this.form.find('.vrs-checkout-date').val();

            if (!checkinStr || !checkoutStr) {
                this.updateNightsDisplay(0);
                return 0;
            }

            var checkinDate = this.parseDate(checkinStr);
            var checkoutDate = this.parseDate(checkoutStr);
            var nights = Math.ceil((checkoutDate - checkinDate) / (1000 * 60 * 60 * 24));

            this.updateNightsDisplay(nights);
            return nights;
        },

        updateNightsDisplay: function(nights) {
            this.form.find('.vrs-nights-value').text(nights);
            this.form.find('.vrs-nights-text').text(nights + ' gece');
        },

        updateGuestCounts: function() {
            var adults = parseInt(this.form.find('input[name="adults"]').val()) || 0;
            var children = parseInt(this.form.find('input[name="children"]').val()) || 0;
            var total = adults + children;

            this.form.find('.vrs-guests-value').text(total);

            // Kapasite kontrolü
            if (total > this.villaData.maxCapacity) {
                this.showError(vrs_ajax.i18n.max_capacity_exceeded);
                this.form.find('.vrs-guests-value').addClass('over-capacity');
            } else {
                this.form.find('.vrs-guests-value').removeClass('over-capacity');
            }
        },

        updateChildrenAges: function() {
            var childrenCount = parseInt(this.form.find('input[name="children"]').val()) || 0;
            var $agesContainer = this.form.find('.vrs-children-ages');
            var $agesInputContainer = $agesContainer.find('.vrs-children-ages-container');

            if (childrenCount > 0) {
                $agesContainer.show();
                $agesInputContainer.empty();

                for (var i = 0; i < childrenCount; i++) {
                    var ageInput = $('<div class="vrs-child-age-input">' +
                        '<label>' + (i + 1) + '. Çocuk Yaşı:</label>' +
                        '<select name="children_ages[]" required>' +
                            '<option value="">Yaş seçin</option>' +
                            '<option value="0">0-2 yaş (Bebek)</option>' +
                            '<option value="1">3-12 yaş (Çocuk)</option>' +
                            '<option value="2">13-17 yaş (Genç)</option>' +
                        '</select>' +
                    '</div>');
                    
                    $agesInputContainer.append(ageInput);
                }
            } else {
                $agesContainer.hide();
                $agesInputContainer.empty();
            }
        },

        validateDates: function() {
            var checkinStr = this.form.find('.vrs-checkin-date').val();
            var checkoutStr = this.form.find('.vrs-checkout-date').val();

            if (!checkinStr || !checkoutStr) {
                return false;
            }

            var checkinDate = this.parseDate(checkinStr);
            var checkoutDate = this.parseDate(checkoutStr);
            var nights = Math.ceil((checkoutDate - checkinDate) / (1000 * 60 * 60 * 24));

            // Minimum konaklama kontrolü
            if (nights < this.villaData.minimumStay) {
                this.showError(
                    'Minimum ' + this.villaData.minimumStay + ' gün konaklama gereklidir.'
                );
                return false;
            }

            // Maksimum konaklama kontrolü
            if (nights > this.villaData.maximumStay) {
                this.showError(
                    'Maksimum ' + this.villaData.maximumStay + ' gün konaklama mümkündür.'
                );
                return false;
            }

            return true;
        },

        validateGuests: function() {
            var adults = parseInt(this.form.find('input[name="adults"]').val()) || 0;
            var children = parseInt(this.form.find('input[name="children"]').val()) || 0;
            var total = adults + children;

            // Minimum yetişkin kontrolü
            if (adults < 1) {
                this.showError('En az 1 yetişkin gereklidir.');
                return false;
            }

            // Maksimum kapasite kontrolü
            if (total > this.villaData.maxCapacity) {
                this.showError(vrs_ajax.i18n.max_capacity_exceeded);
                return false;
            }

            // Maksimum yetişkin kontrolü
            if (adults > this.villaData.maxAdults) {
                this.showError('Maksimum ' + this.villaData.maxAdults + ' yetişkin kabul edilir.');
                return false;
            }

            // Maksimum çocuk kontrolü
            if (children > this.villaData.maxChildren) {
                this.showError('Maksimum ' + this.villaData.maxChildren + ' çocuk kabul edilir.');
                return false;
            }

            return true;
        },

        validateField: function($field) {
            var value = $field.val().trim();
            var isValid = true;

            if ($field.prop('required') && !value) {
                isValid = false;
                $field.addClass('error');
            } else {
                $field.removeClass('error');
            }

            return isValid;
        },

        validateEmail: function($field) {
            var email = $field.val().trim();
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (email && !emailRegex.test(email)) {
                $field.addClass('error');
                return false;
            } else {
                $field.removeClass('error');
                return true;
            }
        },

        validatePhone: function($field) {
            var phone = $field.val().trim();
            var phoneRegex = /^[\d\s\-\+\(\)]+$/;

            if (phone && (!phoneRegex.test(phone) || phone.length < 10)) {
                $field.addClass('error');
                return false;
            } else {
                $field.removeClass('error');
                return true;
            }
        },

        validateForm: function() {
            var isValid = true;
            var self = this;

            // Tarih doğrulama
            if (!this.validateDates()) {
                isValid = false;
            }

            // Misafir doğrulama
            if (!this.validateGuests()) {
                isValid = false;
            }

            // Gerekli alanları kontrol et
            this.form.find('input[required], select[required]').each(function() {
                if (!self.validateField($(this))) {
                    isValid = false;
                }
            });

            // E-posta doğrulama
            var $emailField = this.form.find('input[type="email"]');
            if (!this.validateEmail($emailField)) {
                isValid = false;
            }

            // Telefon doğrulama
            var $phoneField = this.form.find('input[type="tel"]');
            if (!this.validatePhone($phoneField)) {
                isValid = false;
            }

            // Şartlar kontrolü
            if (!this.form.find('input[name="accept_terms"]').is(':checked')) {
                this.showError('Kullanım şartlarını kabul etmelisiniz.');
                isValid = false;
            }

            return isValid;
        },

        calculatePrice: function() {
            var self = this;
            var checkinStr = this.form.find('.vrs-checkin-date').val();
            var checkoutStr = this.form.find('.vrs-checkout-date').val();
            var adults = parseInt(this.form.find('input[name="adults"]').val()) || 0;
            var children = parseInt(this.form.find('input[name="children"]').val()) || 0;

            // Tarihler eksikse fiyat hesaplama
            if (!checkinStr || !checkoutStr) {
                this.hidePriceDetails();
                return;
            }

            // Müsaitlik kontrolü ve fiyat hesaplama
            this.showPriceLoading();

            var childrenAges = [];
            this.form.find('select[name="children_ages[]"]').each(function() {
                var age = $(this).val();
                if (age !== '') {
                    childrenAges.push(parseInt(age));
                }
            });

            $.ajax({
                url: vrs_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'vrs_calculate_price',
                    villa_id: this.villaId,
                    checkin: checkinStr,
                    checkout: checkoutStr,
                    adults: adults,
                    children: children,
                    children_ages: childrenAges,
                    nonce: vrs_ajax.nonce
                },
                success: function(response) {
                    self.hidePriceLoading();
                    
                    if (response.success) {
                        self.displayPriceDetails(response.data);
                        self.checkFormValidity();
                    } else {
                        self.showError(response.data.message || 'Fiyat hesaplanamadı.');
                        self.hidePriceDetails();
                    }
                },
                error: function() {
                    self.hidePriceLoading();
                    self.showError('Fiyat hesaplanırken bir hata oluştu.');
                    self.hidePriceDetails();
                }
            });
        },

        submitForm: function() {
            var self = this;
            
            this.isSubmitting = true;
            this.showSubmitLoading();
            this.hideMessages();

            var formData = this.form.serialize();

            $.ajax({
                url: vrs_ajax.ajax_url,
                type: 'POST',
                data: formData + '&action=vrs_create_reservation',
                success: function(response) {
                    self.hideSubmitLoading();
                    self.isSubmitting = false;

                    if (response.success) {
                        self.showSuccess(response.data.message);
                        
                        // Ödeme sayfasına yönlendir
                        if (response.data.redirect_url) {
                            setTimeout(function() {
                                window.location.href = response.data.redirect_url;
                            }, 2000);
                        }
                    } else {
                        self.showError(response.data.message || 'Rezervasyon oluşturulamadı.');
                    }
                },
                error: function() {
                    self.hideSubmitLoading();
                    self.isSubmitting = false;
                    self.showError('Rezervasyon oluşturulurken bir hata oluştu. Lütfen tekrar deneyin.');
                }
            });
        },

        checkFormValidity: function() {
            var isValid = true;
            var $submitBtn = this.form.find('.vrs-submit-btn');

            // Temel alanların dolu olup olmadığını kontrol et
            var checkin = this.form.find('.vrs-checkin-date').val();
            var checkout = this.form.find('.vrs-checkout-date').val();
            var adults = parseInt(this.form.find('input[name="adults"]').val()) || 0;
            var guestName = this.form.find('input[name="guest_name"]').val().trim();
            var guestEmail = this.form.find('input[name="guest_email"]').val().trim();
            var guestPhone = this.form.find('input[name="guest_phone"]').val().trim();
            var acceptTerms = this.form.find('input[name="accept_terms"]').is(':checked');

            if (!checkin || !checkout || adults < 1 || !guestName || !guestEmail || !guestPhone || !acceptTerms) {
                isValid = false;
            }

            // Fiyat hesaplanmış mı kontrol et
            if (!this.form.find('.vrs-price-details').is(':visible')) {
                isValid = false;
            }

            $submitBtn.prop('disabled', !isValid);
        },

        showPriceLoading: function() {
            this.form.find('.vrs-price-loading').show();
            this.form.find('.vrs-price-details').hide();
        },

        hidePriceLoading: function() {
            this.form.find('.vrs-price-loading').hide();
        },

        displayPriceDetails: function(priceData) {
            var $priceDetails = this.form.find('.vrs-price-details');
            
            // Fiyat verilerini güncelle
            $priceDetails.find('.vrs-daily-price').text(this.formatPrice(priceData.daily_rate || 0));
            $priceDetails.find('.vrs-daily-amount').text(this.formatPrice(priceData.daily_rate || 0));
            $priceDetails.find('.vrs-subtotal').text(this.formatPrice(priceData.subtotal));
            $priceDetails.find('.vrs-total-price').text(this.formatPrice(priceData.total));

            // Vergi varsa göster
            if (priceData.taxes > 0) {
                $priceDetails.find('.vrs-tax-amount').text(this.formatPrice(priceData.taxes));
                $priceDetails.find('.vrs-tax-row').show();
            } else {
                $priceDetails.find('.vrs-tax-row').hide();
            }

            $priceDetails.show();
        },

        hidePriceDetails: function() {
            this.form.find('.vrs-price-details').hide();
        },

        showSubmitLoading: function() {
            var $btn = this.form.find('.vrs-submit-btn');
            $btn.find('.vrs-btn-text').hide();
            $btn.find('.vrs-btn-loading').show();
            $btn.prop('disabled', true);
        },

        hideSubmitLoading: function() {
            var $btn = this.form.find('.vrs-submit-btn');
            $btn.find('.vrs-btn-text').show();
            $btn.find('.vrs-btn-loading').hide();
            $btn.prop('disabled', false);
        },

        showError: function(message) {
            var $errorDiv = this.form.find('.vrs-error-message');
            $errorDiv.text(message).show();
            
            // Scroll to error
            $('html, body').animate({
                scrollTop: $errorDiv.offset().top - 100
            }, 500);
        },

        showSuccess: function(message) {
            var $successDiv = this.form.find('.vrs-success-message');
            $successDiv.text(message).show();
            
            // Scroll to success
            $('html, body').animate({
                scrollTop: $successDiv.offset().top - 100
            }, 500);
        },

        hideMessages: function() {
            this.form.find('.vrs-error-message, .vrs-success-message').hide();
        },

        isDateAvailable: function(date, isCheckout) {
            // Geçmiş tarihler müsait değil
            var today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (date < today) {
                return [false, 'past-date', 'Geçmiş tarih'];
            }

            // Bloke tarihler
            var dateStr = this.formatDateForComparison(date);
            
            for (var i = 0; i < this.blockedDates.length; i++) {
                if (this.blockedDates[i] === dateStr) {
                    return [false, 'blocked-date', 'Müsait değil'];
                }
            }

            // Check-out için check-in tarihinden sonra olmalı
            if (isCheckout) {
                var checkinStr = this.form.find('.vrs-checkin-date').val();
                if (checkinStr) {
                    var checkinDate = this.parseDate(checkinStr);
                    if (date <= checkinDate) {
                        return [false, 'invalid-checkout', 'Geçersiz çıkış tarihi'];
                    }
                }
            }

            return [true, 'available', 'Müsait'];
        },

        parseDate: function(dateStr) {
            // dd/mm/yy formatından Date objesine çevir
            var parts = dateStr.split('/');
            if (parts.length === 3) {
                var day = parseInt(parts[0], 10);
                var month = parseInt(parts[1], 10) - 1; // JavaScript ayları 0-bazlı
                var year = parseInt(parts[2], 10);
                
                // 2 haneli yıl için 2000 ekle
                if (year < 100) {
                    year += 2000;
                }
                
                return new Date(year, month, day);
            }
            
            return new Date(dateStr);
        },

        formatDateForComparison: function(date) {
            // YYYY-MM-DD formatında string döndür
            var year = date.getFullYear();
            var month = ('0' + (date.getMonth() + 1)).slice(-2);
            var day = ('0' + date.getDate()).slice(-2);
            
            return year + '-' + month + '-' + day;
        },

        formatPrice: function(amount) {
            return parseFloat(amount).toLocaleString('tr-TR', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 2
            }) + ' TL';
        }
    };

    // jQuery plugin
    $.fn.villaReservationForm = function(options) {
        return this.each(function() {
            if (!$.data(this, 'villaReservationForm')) {
                $.data(this, 'villaReservationForm', new VillaReservationForm(this, options));
            }
        });
    };

    // DOM hazır olduğunda başlat
    $(document).ready(function() {
        $('.vrs-reservation-form-container').villaReservationForm();

        // AJAX için blocked dates endpoint'i ekle
        if (typeof vrs_ajax !== 'undefined') {
            // Bloke tarihleri almak için AJAX endpoint'i
            $(document).on('vrsNeedBlockedDates', function(e, villaId, callback) {
                $.ajax({
                    url: vrs_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'vrs_get_blocked_dates',
                        villa_id: villaId,
                        nonce: vrs_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success && typeof callback === 'function') {
                            callback(response.data);
                        }
                    }
                });
            });
        }
    });

})(jQuery);