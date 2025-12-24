<?php

namespace Tests\Feature;

use App\Livewire\ContactForm;
use App\Mail\ContactFormAdminNotification;
use App\Mail\ContactFormAutoReply;
use App\Models\TallcmsContactSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class ContactFormSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function getDefaultConfig(): array
    {
        return [
            'title' => 'Contact Us',
            'description' => 'Fill out the form below.',
            'fields' => [
                ['name' => 'name', 'type' => 'text', 'label' => 'Name', 'required' => true, 'options' => []],
                ['name' => 'email', 'type' => 'email', 'label' => 'Email', 'required' => true, 'options' => []],
                ['name' => 'message', 'type' => 'textarea', 'label' => 'Message', 'required' => true, 'options' => []],
            ],
            'submit_button_text' => 'Send Message',
            'success_message' => 'Thank you for your message!',
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Clear rate limiter before each test
        RateLimiter::clear('contact-form:127.0.0.1');

        // Fake mail
        Mail::fake();
    }

    public function test_contact_form_renders_all_configured_fields(): void
    {
        Livewire::test(ContactForm::class, ['config' => $this->getDefaultConfig()])
            ->assertSee('Name')
            ->assertSee('Email')
            ->assertSee('Message')
            ->assertSee('Send Message');
    }

    public function test_successful_form_submission_creates_database_record(): void
    {
        Livewire::test(ContactForm::class, ['config' => $this->getDefaultConfig()])
            ->set('formData.name', 'John Doe')
            ->set('formData.email', 'john@example.com')
            ->set('formData.message', 'Hello, this is a test message.')
            ->call('submit')
            ->assertSet('submitted', true);

        $this->assertDatabaseHas('tallcms_contact_submissions', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $submission = TallcmsContactSubmission::first();
        $this->assertNotNull($submission);
        $this->assertCount(3, $submission->form_data);

        // Check that form_data contains the expected structure
        $formData = collect($submission->form_data);
        $this->assertEquals('John Doe', $formData->firstWhere('name', 'name')['value']);
        $this->assertEquals('john@example.com', $formData->firstWhere('name', 'email')['value']);
        $this->assertEquals('Hello, this is a test message.', $formData->firstWhere('name', 'message')['value']);
    }

    public function test_form_submission_queues_admin_notification_when_configured(): void
    {
        config(['tallcms.contact_email' => 'admin@example.com']);

        Livewire::test(ContactForm::class, ['config' => $this->getDefaultConfig()])
            ->set('formData.name', 'Jane Doe')
            ->set('formData.email', 'jane@example.com')
            ->set('formData.message', 'Test message')
            ->call('submit');

        Mail::assertQueued(ContactFormAdminNotification::class, function ($mail) {
            return $mail->submission->name === 'Jane Doe';
        });
    }

    public function test_form_submission_queues_auto_reply_when_email_provided(): void
    {
        config(['tallcms.contact_email' => 'admin@example.com']);

        Livewire::test(ContactForm::class, ['config' => $this->getDefaultConfig()])
            ->set('formData.name', 'Jane Doe')
            ->set('formData.email', 'jane@example.com')
            ->set('formData.message', 'Test message')
            ->call('submit');

        Mail::assertQueued(ContactFormAutoReply::class, function ($mail) {
            return $mail->submission->email === 'jane@example.com';
        });
    }

    public function test_validation_errors_for_required_fields(): void
    {
        Livewire::test(ContactForm::class, ['config' => $this->getDefaultConfig()])
            ->set('formData.name', '')
            ->set('formData.email', '')
            ->set('formData.message', '')
            ->call('submit')
            ->assertHasErrors([
                'formData.name' => 'required',
                'formData.email' => 'required',
                'formData.message' => 'required',
            ]);

        $this->assertDatabaseCount('tallcms_contact_submissions', 0);
    }

    public function test_email_validation(): void
    {
        Livewire::test(ContactForm::class, ['config' => $this->getDefaultConfig()])
            ->set('formData.name', 'John')
            ->set('formData.email', 'not-an-email')
            ->set('formData.message', 'Test')
            ->call('submit')
            ->assertHasErrors(['formData.email' => 'email']);
    }

    public function test_honeypot_rejects_spam_silently(): void
    {
        Livewire::test(ContactForm::class, ['config' => $this->getDefaultConfig()])
            ->set('formData.name', 'Spammer')
            ->set('formData.email', 'spam@example.com')
            ->set('formData.message', 'Buy cheap stuff!')
            ->set('honeypot', 'http://spam-site.com') // Bot filled the honeypot
            ->call('submit')
            ->assertSet('submitted', true); // Appears successful to confuse bots

        // But no database record was created
        $this->assertDatabaseCount('tallcms_contact_submissions', 0);
    }

    public function test_rate_limiting_blocks_excessive_submissions(): void
    {
        $config = $this->getDefaultConfig();

        // Submit 3 times successfully
        for ($i = 1; $i <= 3; $i++) {
            Livewire::test(ContactForm::class, ['config' => $config])
                ->set('formData.name', "User $i")
                ->set('formData.email', "user{$i}@example.com")
                ->set('formData.message', 'Test message')
                ->call('submit')
                ->assertSet('submitted', true);
        }

        // 4th submission should be rate limited
        Livewire::test(ContactForm::class, ['config' => $config])
            ->set('formData.name', 'Rate Limited User')
            ->set('formData.email', 'limited@example.com')
            ->set('formData.message', 'Should be blocked')
            ->call('submit')
            ->assertHasErrors(['form'])
            ->assertSet('submitted', false);

        // Only 3 records in database
        $this->assertDatabaseCount('tallcms_contact_submissions', 3);
    }

    public function test_form_resets_after_successful_submission(): void
    {
        Livewire::test(ContactForm::class, ['config' => $this->getDefaultConfig()])
            ->set('formData.name', 'John Doe')
            ->set('formData.email', 'john@example.com')
            ->set('formData.message', 'Test message')
            ->call('submit')
            ->assertSet('submitted', true)
            ->assertSet('formData.name', '')
            ->assertSet('formData.email', '')
            ->assertSet('formData.message', '');
    }

    public function test_select_field_validation(): void
    {
        $config = $this->getDefaultConfig();
        $config['fields'][] = [
            'name' => 'service',
            'type' => 'select',
            'label' => 'Service',
            'required' => true,
            'options' => ['Design', 'Development', 'SEO'],
        ];

        // Invalid option should fail
        Livewire::test(ContactForm::class, ['config' => $config])
            ->set('formData.name', 'John')
            ->set('formData.email', 'john@example.com')
            ->set('formData.message', 'Test')
            ->set('formData.service', 'InvalidOption')
            ->call('submit')
            ->assertHasErrors(['formData.service']);

        // Valid option should pass
        Livewire::test(ContactForm::class, ['config' => $config])
            ->set('formData.name', 'John')
            ->set('formData.email', 'john@example.com')
            ->set('formData.message', 'Test')
            ->set('formData.service', 'Design')
            ->call('submit')
            ->assertHasNoErrors(['formData.service']);
    }

    public function test_optional_fields_can_be_empty(): void
    {
        $config = $this->getDefaultConfig();
        $config['fields'][] = [
            'name' => 'phone',
            'type' => 'tel',
            'label' => 'Phone',
            'required' => false,
            'options' => [],
        ];

        Livewire::test(ContactForm::class, ['config' => $config])
            ->set('formData.name', 'John Doe')
            ->set('formData.email', 'john@example.com')
            ->set('formData.message', 'Test message')
            ->set('formData.phone', '') // Empty optional field
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('submitted', true);

        $this->assertDatabaseCount('tallcms_contact_submissions', 1);
    }

    public function test_model_mark_as_read_methods(): void
    {
        $submission = TallcmsContactSubmission::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'form_data' => [
                ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'value' => 'Test User'],
            ],
            'page_url' => 'http://example.com/contact',
            'is_read' => false,
        ]);

        $this->assertFalse($submission->is_read);

        $submission->markAsRead();
        $this->assertTrue($submission->fresh()->is_read);

        $submission->markAsUnread();
        $this->assertFalse($submission->fresh()->is_read);
    }

    public function test_model_scopes(): void
    {
        TallcmsContactSubmission::create([
            'name' => 'Unread User',
            'email' => 'unread@example.com',
            'form_data' => [],
            'is_read' => false,
        ]);

        TallcmsContactSubmission::create([
            'name' => 'Read User',
            'email' => 'read@example.com',
            'form_data' => [],
            'is_read' => true,
        ]);

        $this->assertEquals(1, TallcmsContactSubmission::unread()->count());
        $this->assertEquals(2, TallcmsContactSubmission::recent()->count());
    }
}
