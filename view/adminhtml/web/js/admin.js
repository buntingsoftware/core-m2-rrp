 jQuery( document ).ready(function( $ ) {
     jQuery('#billing-freemium, #billing-automatic').addClass('btn btn-primary');
     jQuery('#choosePlanButton').click(function() {
         jQuery('#loginForm').fadeOut(function() {
             jQuery('#registerForm').fadeIn();
         });

     });
     jQuery('#registerForm .back').click(function() {
         jQuery('#registerForm').fadeOut(function() {
             jQuery('#loginForm').fadeIn();
         });
     });
     jQuery.validator.addMethod("subdomain", function(value, element) {
        return this.optional( element ) || /^[a-z0-9\-_]+$/.test( value );
    }, "Please specify a valid subdomain, using lowercase letters, numbers, hyphens and underscores.");
     jQuery("#actualLoginForm").validate({
        rules: {
            verify_bunting_subdomain: {
                required: true,
                maxlength: 50
            },
            verify_email_address: {
                required: true,
                email: true
            },
            verify_password: {
                required: true,
                maxlength: 100
            }
        },
        submitHandler: function(form) {
            submitForm(form, 'login', 'verify');
        }
     });

     jQuery("#actualRegisterForm").validate({
        rules: {
            register_email_address: {
                required: true,
                email: true
            },
            register_password: {
                required: true,
                minlength: 8,
                maxlength: 100
            },
            password_confirmation: {
                required: true,
                equalTo: "#registerForm #register_password"
            },
            forename: {
                required: true,
                maxlength: 100
            },
            surname: {
                required: true,
                maxlength: 100
            },
            company_name: {
                required: true,
                maxlength: 100
            },
            register_subdomain: {
                required: true,
                subdomain: true,
                maxlength: 100
            },
            promotional_code: {
                maxlength: 20
            }
        },
        submitHandler: function(form) {
            submitForm(form, 'register', 'register');
        }
     });

     window.allowPasswordTrigger = false;
     jQuery('.forgotPasswordTrigger').click(function(e){
         if (window.allowPasswordTrigger) {
             return true;
         }

         e.preventDefault();
         e.stopPropagation();
         jQuery('.forgotPasswordForm').toggleClass('active');
         return false;
     });

     jQuery('.forgotPasswordForm .btn').click(function(e){
         e.preventDefault();
         e.stopPropagation();
         var subdomain = jQuery('.forgotPasswordForm #bunting_forgot_subdomain').val(),
             urlRedirect = 'https://' + subdomain + '.1.bunting.com/login?a=lost_password';

         jQuery.ajax({
             type: "POST",
             url: window.bunting_domain_exists_url,
             data: {subdomain: subdomain},
             dataType: 'json',
             success: function (data) {
                 if (parseInt(data) === 1) {
                     window.location = urlRedirect;
                 } else {
                     alert('The subdomain you entered is incorrect');
                 }
             }
         });
     });
});
function submitForm(form, type, prefix) {
    jQuery('#loading').show();
    var $message = jQuery('p.message'),
        fieldErrorMappings = {
            subdomain: prefix + '_bunting_subdomain',
            email_address: prefix + '_email_address',
            property: prefix + '_password',
            name: 'company_name',
            confirm_password: 'password_confirmation',
            validation: 'verify_password'
        };

    $message.hide();
    jQuery('label.error').hide();
    jQuery('input.error').removeClass('error');

    var $this = jQuery(form),
        values = $this.serializeArray().reduce(function(obj, item) {
            obj[item.name] = item.value;
            return obj;
        }, {});

    if (type == 'register') {
        var url = window.bunting_register_url;
    }
    else {
        var url = window.bunting_login_url;
    }

    jQuery.ajax({
        type: "POST",
        url: url,
        data: values,
        dataType: 'json',
        success: function(data) {
            if (typeof data.errors === 'undefined') {
                window.location.href = window.bunting_success_url;
            }
            else {
                for (var property in data.errors) {
                    if (data.errors.hasOwnProperty(property)) {
                        var value = data.errors[property];
                        property = fieldErrorMappings.hasOwnProperty(property) ? fieldErrorMappings[property] : property;

                        if (property === 'validation') {
                            jQuery('#verify_email_address').addClass('error');
                        }

                        jQuery('#' + property).addClass('error').parent().after('<label class="error">' + value + '</label>');
                    }
                }
            }
            jQuery('#loading').hide();
        },
        always: function() {
            jQuery('#loading').hide();
        }
    });
}