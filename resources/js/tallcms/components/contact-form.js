/**
 * Contact Form Alpine.js Component
 *
 * Handles form submission with CSRF protection, validation errors,
 * rate limiting, and signature verification.
 */
function registerContactForm() {
    if (typeof Alpine === 'undefined') return false;

    Alpine.data('contactForm', () => ({
        // State
        formData: {},
        errors: {},
        formError: '',
        submitted: false,
        submitting: false,

        // Config (populated from data attributes)
        config: null,
        submitUrl: '',
        successMessage: '',
        signature: '',
        pageUrl: '',

        init() {
            // Read config from data attribute
            const configAttr = this.$el.dataset.contactFormConfig;
            if (!configAttr) {
                console.error('Contact form missing data-contact-form-config attribute');
                return;
            }

            try {
                this.config = JSON.parse(configAttr);
            } catch (e) {
                console.error('Failed to parse contact form config:', e);
                return;
            }

            // Initialize from config
            this.submitUrl = this.config.submitUrl || '';
            this.successMessage = this.config.successMessage || 'Thank you for your message!';
            this.signature = this.config.signature || '';
            this.pageUrl = this.config.pageUrl || '';

            // Initialize form data with empty values for each field
            this.formData = (this.config.fieldNames || []).reduce(
                (acc, name) => ({ ...acc, [name]: '' }),
                { _honeypot: '' }
            );
        },

        async submit() {
            this.submitting = true;
            this.errors = {};
            this.formError = '';

            // Check for CSRF token
            const csrfMeta = document.querySelector('meta[name=csrf-token]');
            if (!csrfMeta) {
                this.formError = 'Security token missing. Please refresh the page.';
                this.submitting = false;
                return;
            }

            try {
                const response = await fetch(this.submitUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfMeta.content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        ...this.formData,
                        _config: this.config.config,
                        _signature: this.signature,
                        _pageUrl: this.pageUrl
                    })
                });

                // Handle non-JSON responses gracefully
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    console.error('Non-JSON response received:', response.status);
                    this.formError = 'Server returned an unexpected response. Please try again.';
                    return;
                }

                let data;
                try {
                    data = await response.json();
                } catch (parseError) {
                    console.error('Failed to parse JSON response:', parseError);
                    this.formError = 'Server returned an invalid response. Please try again.';
                    return;
                }

                if (response.ok) {
                    this.submitted = true;
                } else if (response.status === 422) {
                    this.errors = data.errors || {};
                } else if (response.status === 429) {
                    this.formError = data.message || 'Too many submissions. Please try again later.';
                } else if (response.status === 400) {
                    this.formError = data.message || 'Invalid request. Please refresh the page and try again.';
                } else {
                    this.formError = data.message || 'An error occurred. Please try again.';
                }
            } catch (error) {
                console.error('Contact form submission error:', error);
                this.formError = 'A network error occurred. Please check your connection and try again.';
            } finally {
                this.submitting = false;
            }
        },

        // Helper to get field error
        getFieldError(fieldName) {
            return this.errors[fieldName] ? this.errors[fieldName][0] : '';
        },

        // Helper to check if field has error
        hasFieldError(fieldName) {
            return !!this.errors[fieldName];
        }
    }));

    return true;
}

// Try to register immediately if Alpine is already loaded
if (!registerContactForm()) {
    // Otherwise wait for Alpine to initialize
    document.addEventListener('alpine:init', registerContactForm);
}
