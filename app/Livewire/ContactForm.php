<?php

namespace App\Livewire;

use App\Mail\ContactFormAdminNotification;
use App\Mail\ContactFormAutoReply;
use App\Models\TallcmsContactSubmission;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ContactForm extends Component
{
    public array $config = [];
    public array $formData = [];
    public string $honeypot = '';
    public bool $submitted = false;

    public function mount(array $config): void
    {
        $this->config = $config;

        // Initialize formData keys from field definitions
        foreach ($config['fields'] ?? [] as $field) {
            $this->formData[$field['name']] = '';
        }
    }

    protected function rules(): array
    {
        $rules = ['honeypot' => 'size:0'];

        foreach ($this->config['fields'] ?? [] as $field) {
            $key = "formData.{$field['name']}";
            $fieldRules = $field['required'] ? ['required'] : ['nullable'];

            match ($field['type']) {
                'email' => $fieldRules = array_merge($fieldRules, ['email', 'max:255']),
                'tel' => $fieldRules[] = 'max:50',
                'select' => $fieldRules[] = Rule::in($field['options'] ?? []),
                'textarea' => $fieldRules[] = 'max:5000',
                default => $fieldRules[] = 'max:255',
            };

            $rules[$key] = $fieldRules;
        }

        return $rules;
    }

    protected function validationAttributes(): array
    {
        $attributes = [];

        foreach ($this->config['fields'] ?? [] as $field) {
            $attributes["formData.{$field['name']}"] = strtolower($field['label']);
        }

        return $attributes;
    }

    public function submit(): void
    {
        // Rate limiting check
        $key = 'contact-form:' . request()->ip();

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $this->addError('form', 'Too many submissions. Please try again in a few minutes.');

            return;
        }

        // Honeypot check - silently reject spam
        if (! empty($this->honeypot)) {
            // Pretend success to confuse bots
            $this->submitted = true;

            return;
        }

        // Validate
        $this->validate();

        // Hit the rate limiter
        RateLimiter::hit($key, 600); // 10 minute decay

        // Build form_data array with field metadata
        $formDataWithMeta = [];
        foreach ($this->config['fields'] ?? [] as $field) {
            $formDataWithMeta[] = [
                'name' => $field['name'],
                'label' => $field['label'],
                'type' => $field['type'],
                'value' => $this->formData[$field['name']] ?? '',
            ];
        }

        // Extract name and email for core columns (for search/indexing)
        $name = $this->formData['name'] ?? null;
        $email = $this->formData['email'] ?? null;

        // Store submission
        $submission = TallcmsContactSubmission::create([
            'name' => $name,
            'email' => $email,
            'form_data' => $formDataWithMeta,
            'page_url' => request()->url(),
        ]);

        // Queue admin notification
        $adminEmail = config('tallcms.contact_email');
        if ($adminEmail) {
            Mail::to($adminEmail)->queue(new ContactFormAdminNotification($submission));
        } else {
            Log::warning('Contact form submission received but no admin email configured', [
                'submission_id' => $submission->id,
            ]);
        }

        // Queue auto-reply only if valid email provided
        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Mail::to($email)->queue(new ContactFormAutoReply($submission));
        }

        // Reset form dynamically
        $this->formData = array_fill_keys(array_keys($this->formData), '');
        $this->submitted = true;
    }

    public function render()
    {
        return view('livewire.contact-form');
    }
}
