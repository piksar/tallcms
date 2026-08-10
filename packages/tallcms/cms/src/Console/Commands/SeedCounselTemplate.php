<?php

declare(strict_types=1);

namespace TallCms\Cms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use TallCms\Cms\Console\Commands\Concerns\TranslatesDemoTemplateStrings;

/**
 * Seed the "Counsel" template — a professional law firm website.
 *
 * Tone is deliberately conservative and trust-building, the way law
 * firm websites actually need to read. No overclaiming ("best lawyer"
 * or specific case-result dollar amounts) because most jurisdictions'
 * bar advertising rules prohibit it. Copy is factual, restrained, and
 * focused on practice areas + attorney credentials + consultation
 * booking as the primary conversion.
 *
 * Scope: solo practitioners and small-to-mid firms. A boutique
 * general-practice firm, a specialist (family, employment, immigration,
 * corporate), or a small partnership. Not designed for large
 * multi-office firms with hundreds of attorneys — those need a
 * different scale of site.
 *
 * Copy uses bracketed placeholders ([Firm Name], [Attorney Name],
 * [Practice Area], [Bar Admission], etc.) for search-and-replace.
 */
class SeedCounselTemplate extends Command
{
    use TranslatesDemoTemplateStrings;

    protected $signature = 'tallcms:seed-counsel-template
                            {--owner= : User ID to own the template site (defaults to first super_admin)}
                            {--force : Delete any existing Counsel template and recreate}';

    protected $description = 'Seed the Counsel template — a professional law firm website';

    public function handle(): int
    {
        if (! Schema::hasTable('tallcms_sites') || ! Schema::hasColumn('tallcms_sites', 'is_template_source')) {
            $this->error(__('tallcms::console.seed_template.multisite_required'));

            return self::FAILURE;
        }

        $ownerId = $this->resolveOwnerId();
        if (! $ownerId) {
            $this->error(__('tallcms::console.seed_template.no_owner'));

            return self::FAILURE;
        }

        $existing = DB::table('tallcms_sites')->where('domain', 'counsel.template')->first();
        if ($existing) {
            if (! $this->option('force')) {
                $this->components->warn(__('tallcms::console.seed_template.already_exists', ['template' => 'Counsel', 'id' => $existing->id]));

                return self::SUCCESS;
            }
            $this->deleteSite((int) $existing->id);
            $this->components->info(__('tallcms::console.seed_template.removed', ['template' => 'Counsel']));
        }

        $siteId = $this->createSite($ownerId);
        $this->components->info(__('tallcms::console.seed_template.created_site', ['template' => 'Counsel', 'id' => $siteId]));

        $pageIds = $this->createPages($siteId, $ownerId);
        $this->components->info(__('tallcms::console.seed_template.created_pages', ['count' => count($pageIds)]));

        $this->createMenu($siteId, $pageIds);
        $this->components->info(__('tallcms::console.seed_template.created_menu'));

        $this->createPosts($siteId, $ownerId);
        $this->components->info(__('tallcms::console.seed_template.created_posts', ['count' => 3, 'type' => __('tallcms::console.seed_template.post_type_insight')]));

        $this->newLine();
        $this->components->info(__('tallcms::console.seed_template.ready', ['emoji' => '⚖️', 'template' => 'Counsel', 'message' => __('tallcms::console.seed_template.ready_gallery')]));

        return self::SUCCESS;
    }

    protected function resolveOwnerId(): ?int
    {
        if ($owner = $this->option('owner')) {
            return (int) $owner;
        }

        $superAdminId = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('roles.name', 'super_admin')
            ->value('model_has_roles.model_id');

        return $superAdminId ? (int) $superAdminId : DB::table('users')->orderBy('id')->value('id');
    }

    protected function deleteSite(int $siteId): void
    {
        DB::table('tallcms_menu_items')
            ->whereIn('menu_id', DB::table('tallcms_menus')->where('site_id', $siteId)->pluck('id'))
            ->delete();
        DB::table('tallcms_menus')->where('site_id', $siteId)->delete();
        DB::table('tallcms_posts')->where('site_id', $siteId)->delete();
        DB::table('tallcms_pages')->where('site_id', $siteId)->delete();
        DB::table('tallcms_site_setting_overrides')->where('site_id', $siteId)->delete();
        DB::table('tallcms_sites')->where('id', $siteId)->delete();
    }

    protected function createSite(int $ownerId): int
    {
        return (int) DB::table('tallcms_sites')->insertGetId([
            'name' => $this->demo('counsel.counsel_e235ddb8e1'),
            'domain' => 'counsel.template',
            'uuid' => (string) Str::uuid(),
            'user_id' => $ownerId,
            'is_default' => false,
            'is_active' => true,
            'is_template_source' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function createPages(int $siteId, int $ownerId): array
    {
        $pages = [
            'home' => ['title' => $this->demoJson('menu.home'), 'is_homepage' => true, 'content' => $this->homeContent()],
            'practice-areas' => ['title_key' => 'counsel.practice_areas_5dac1af047', 'content' => $this->practiceAreasContent()],
            'attorneys' => ['title_key' => 'counsel.attorneys_231cf5b0a3', 'content' => $this->attorneysContent()],
            'insights' => ['title' => $this->demoJson('menu.insights'), 'content' => $this->insightsContent()],
            'contact' => ['title' => $this->demoJson('menu.contact'), 'content' => $this->contactContent()],
        ];

        $ids = [];
        foreach ($pages as $slug => $page) {
            $ids[$slug] = (int) DB::table('tallcms_pages')->insertGetId([
                'site_id' => $siteId,
                'author_id' => $ownerId,
                'title' => $page['title'] ?? $this->demoJson($page['title_key']),
                'slug' => json_encode(['en' => $slug]),
                'content' => json_encode(['en' => $page['content']]),
                'status' => 'published',
                'is_homepage' => $page['is_homepage'] ?? false,
                'sort_order' => 0,
                'published_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $ids;
    }

    protected function createMenu(int $siteId, array $pageIds): void
    {
        $menuId = (int) DB::table('tallcms_menus')->insertGetId([
            'site_id' => $siteId,
            'name' => $this->demo('shared.menu_primary'),
            'location' => 'header',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $lft = 1;
        foreach ([
            'home' => 'Home',
            'practice-areas' => 'Practice Areas',
            'attorneys' => 'Attorneys',
            'insights' => 'Insights',
            'contact' => 'Contact',
        ] as $slug => $label) {
            DB::table('tallcms_menu_items')->insert([
                'menu_id' => $menuId,
                'type' => 'page',
                'label' => $this->demoJson($labelKey),
                'page_id' => $pageIds[$slug],
                'is_active' => true,
                '_lft' => $lft,
                '_rgt' => $lft + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $lft += 2;
        }
    }

    protected function createPosts(int $siteId, int $ownerId): void
    {
        $posts = [
            [
                'title_key' => 'counsel.what_to_expect_from_your_first_consultat_547219c4fe',
                'slug' => 'first-consultation',
                'excerpt_key' => 'counsel.before_you_book_here_is_what_a_consultat_ede2e5d4e7',
                'content' => $this->firstConsultationPost(),
            ],
            [
                'title_key' => 'counsel.how_to_choose_the_right_attorney_for_you_c8b3f4409e',
                'slug' => 'choosing-an-attorney',
                'excerpt_key' => 'counsel.not_every_firm_is_right_for_every_matter_31dcd7aa2d',
                'content' => $this->choosingAttorneyPost(),
            ],
            [
                'title_key' => 'counsel.how_our_fees_work_transparency_over_anxi_5be79d1230',
                'slug' => 'how-our-fees-work',
                'excerpt_key' => 'counsel.a_plain_english_explanation_of_how_we_bi_abe2706dce',
                'content' => $this->feesPost(),
            ],
        ];

        foreach ($posts as $i => $post) {
            DB::table('tallcms_posts')->insert([
                'site_id' => $siteId,
                'author_id' => $ownerId,
                'title' => $this->demoJson($post['title_key']),
                'slug' => json_encode(['en' => $post['slug']]),
                'excerpt' => isset($post['excerpt_key']) ? $this->demoJson($post['excerpt_key']) : json_encode(['en' => $post['excerpt'] ?? '', 'de' => $post['excerpt'] ?? '']),
                'content' => json_encode(['en' => $post['content']]),
                'status' => 'published',
                'published_at' => now()->subDays($i * 14),
                'created_at' => now()->subDays($i * 14),
                'updated_at' => now()->subDays($i * 14),
            ]);
        }
    }

    // --- Block helpers ------------------------------------------------------

    protected function paragraph(string $text): string
    {
        return '<p>'.$text.'</p>';
    }

    // --- Page contents ------------------------------------------------------

    protected function homeContent(): string
    {
        return implode("\n", [
            $this->demoBlock('hero', [
                'heading' => $this->demo('counsel.p_firm_name_p_a02886de69'),
                'subheading' => $this->demo('counsel.p_a_short_factual_tagline_e_g_practical_673f6108ed'),
                'button_text' => $this->demo('counsel.schedule_a_consultation_9d92d9236b'),
                'button_link_type' => 'custom',
                'button_url' => '#contact',
                'secondary_button_text' => $this->demo('counsel.our_practice_areas_765e16d04b'),
                'secondary_button_link_type' => 'page',
                'layout' => 'centered',
                'height' => 'min-h-[70vh]',
                'text_alignment' => 'text-center',
                'background_color' => 'bg-gradient-to-br from-neutral to-base-300',
                'overlay_opacity' => 0,
                'button_variant' => 'btn-primary',
                'secondary_button_variant' => 'btn-ghost text-white hover:bg-white/20',
                'button_size' => 'btn-lg',
            ]),
            $this->demoBlock('content_block', [
                'title_key' => 'counsel.about_firm_name_daff1e1313',
                'body' => $this->demo('counsel.p_firm_name_is_a_size_descriptor_e_g_bo_70818f9714'),
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
            $this->demoBlock('features', [
                'heading' => $this->demo('counsel.practice_areas_e219a85d64'),
                'subheading' => $this->demo('counsel.focused_expertise_in_the_matters_where_w_623cbfa5b1'),
                'features' => [
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-home', 'title_key' => 'counsel.family_law_5113092f01', 'description' => 'Divorce, custody, matrimonial agreements, adoption. We handle sensitive matters with discretion and clear communication.'],
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-building-office', 'title_key' => 'counsel.corporate_commercial_683382184a', 'description' => $this->demo('counsel.entity_formation_shareholder_agreements_d14cbdee9e')],
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-document-text', 'title_key' => 'counsel.wills_estate_planning_71a22fd016', 'description' => "Wills, trusts, lasting powers of attorney, estate administration. Thoughtful planning to protect what you've built."],
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-scale', 'title_key' => 'counsel.civil_litigation_741647b86a', 'description' => $this->demo('counsel.commercial_disputes_contract_enforcement_fd2b647358')],
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-briefcase', 'title_key' => 'counsel.employment_law_b30a4ad360', 'description' => 'Contracts, workplace disputes, wrongful termination, non-competes. Advising employers and employees alike.'],
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-globe-alt', 'title_key' => 'counsel.immigration_8daa6a3a18', 'description' => "Employment passes, PR applications, citizenship, appeals. [If you don't offer this, swap for your actual sixth area or delete.]"],
                ],
                'columns' => '3',
                'background' => 'bg-base-200',
                'padding' => 'py-16',
            ]),
            $this->demoBlock('content_block', [
                'title_key' => 'counsel.how_we_work_fc03ef8194',
                'body' => $this->demo('counsel.p_our_approach_rests_on_three_commitmen_541dec5c71'),
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
            $this->demoBlock('team', [
                'heading' => $this->demo('counsel.our_attorneys_5c26d4c1e3'),
                'subheading' => $this->demo('counsel.experienced_counsel_approachable_in_conv_ec6fe84d95'),
                'members' => [
                    [
                        'name' => $this->demo('counsel.lead_partner_name_1a41186485'),
                        'role' => 'Managing Partner · [Bar Admission Year] · [Jurisdictions]',
                        'bio' => '[Two-sentence bio. Practice focus + notable credentials or background. Avoid overclaiming.]',
                    ],
                    [
                        'name' => $this->demo('counsel.partner_name_e8dfe8879b'),
                        'role' => 'Partner · [Area of Focus]',
                        'bio' => '[Bio highlighting their specialty and what they bring to clients.]',
                    ],
                    [
                        'name' => $this->demo('counsel.associate_name_b6274e77d0'),
                        'role' => 'Senior Associate · [Area of Focus]',
                        'bio' => '[Bio including qualifications and notable matters handled.]',
                    ],
                ],
                'columns' => '3',
                'card_style' => 'bg-base-100 shadow',
                'background' => 'bg-base-200',
                'padding' => 'py-16',
            ]),
            $this->demoBlock('testimonials', [
                'heading' => $this->demo('counsel.what_clients_say_657ded8794'),
                'subheading' => $this->demo('counsel.quoted_with_permission_identifying_detai_dfe7780da8'),
                'testimonials' => [
                    [
                        'quote' => '[Firm Name] took on our matrimonial matter with skill and discretion. Clear communication throughout, and the result we hoped for.',
                        'author_name' => 'M., Partner',
                        'author_title' => 'Matrimonial matter, [Year]',
                    ],
                    [
                        'quote' => 'We engaged [Firm Name] for our shareholder agreement and have continued to use them for every commercial contract since. Responsive and practical.',
                        'author_name' => 'R., Director',
                        'author_title' => 'SME corporate client',
                    ],
                    [
                        'quote' => 'They said upfront our case was marginal, quoted a reasonable fixed fee to give it a proper try, and won. That was five years ago; still our firm of choice.',
                        'author_name' => 'J., Business owner',
                        'author_title' => 'Civil litigation',
                    ],
                ],
                'columns' => '3',
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
            $this->demoBlock('posts', [
                'posts_count' => 3,
                'show_image' => false,
                'show_excerpt' => true,
                'show_date' => true,
                'show_author' => false,
                'show_read_more' => true,
                'layout' => 'grid',
                'background' => 'bg-base-200',
                'padding' => 'py-16',
            ]),
            $this->demoBlock('cta', [
                'title_key' => 'counsel.schedule_an_initial_consultation_c12cb15770',
                'description' => $this->demo('counsel.a_30_minute_consultation_costs_x_is_comp_b053edffff'),
                'button_text' => $this->demo('counsel.schedule_consultation_05e88ca6ff'),
                'button_link_type' => 'custom',
                'button_url' => '#contact',
                'button_variant' => 'btn-primary',
                'button_size' => 'btn-lg',
                'background' => 'bg-primary',
                'padding' => 'py-16',
            ]),
            $this->demoBlock('contact_form', [
                'title_key' => 'counsel.request_a_consultation_a726c0c744',
                'description' => 'Share a brief description of your matter. We will respond within one business day to confirm whether we can assist and book a consultation if appropriate.',
                'anchor_id' => 'contact',
                'fields' => [
                    ['name' => $this->demo('counsel.name_6ae999552a'), 'type' => 'text', 'label' => $this->demo('counsel.full_name_eeb692087d'), 'required' => true, 'options' => []],
                    ['name' => $this->demo('counsel.email_a88b7dcd1a'), 'type' => 'email', 'label' => $this->demo('counsel.email_84add5b295'), 'required' => true, 'options' => []],
                    ['name' => $this->demo('counsel.phone_f6be6ca910'), 'type' => 'tel', 'label' => $this->demo('counsel.phone_77064d5265'), 'required' => true, 'options' => []],
                    ['name' => $this->demo('counsel.matter_type_2b362e96ac'), 'type' => 'select', 'label' => $this->demo('counsel.area_of_law_5af4845d8a'), 'required' => true, 'options' => ['Family Law', 'Corporate & Commercial', 'Wills & Estate Planning', 'Civil Litigation', 'Employment Law', 'Immigration', 'Other / Not sure']],
                    ['name' => $this->demo('counsel.message_6f9b9af3cd'), 'type' => 'textarea', 'label' => $this->demo('counsel.brief_description_of_the_matter_07c6106154'), 'required' => true, 'options' => []],
                ],
                'submit_button_text' => $this->demo('counsel.submit_request_8a9051c613'),
                'success_message' => $this->demo('counsel.thank_you_we_will_respond_within_one_bus_aa821180f2'),
                'auto_reply_message' => $this->demo('counsel.thank_you_for_reaching_out_to_firm_name_03ea4b7910'),
                'button_style' => 'btn-primary',
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
        ]);
    }

    protected function practiceAreasContent(): string
    {
        return implode("\n", [
            $this->demoBlock('hero', [
                'heading' => $this->demo('counsel.p_practice_areas_p_9eb937e3b1'),
                'subheading' => $this->demo('counsel.p_the_matters_we_handle_the_clients_we_94b22e615f'),
                'layout' => 'centered',
                'height' => 'min-h-[40vh]',
                'text_alignment' => 'text-center',
                'background_color' => 'bg-base-200',
                'overlay_opacity' => 0,
            ]),
            $this->demoBlock('content_block', [
                'title_key' => 'counsel.family_law_5113092f01',
                'body' => $this->demo('counsel.p_divorce_custody_matrimonial_agreement_ff483369b4'),
                'background' => 'bg-base-100',
                'padding' => 'py-12',
            ]),
            $this->demoBlock('content_block', [
                'title_key' => 'counsel.corporate_commercial_683382184a',
                'body' => $this->demo('counsel.p_we_advise_founders_owner_operators_an_da87e5f0eb'),
                'background' => 'bg-base-200',
                'padding' => 'py-12',
            ]),
            $this->demoBlock('content_block', [
                'title_key' => 'counsel.wills_estate_planning_71a22fd016',
                'body' => $this->demo('counsel.p_a_will_is_the_least_expensive_highest_9c69763f0d'),
                'background' => 'bg-base-100',
                'padding' => 'py-12',
            ]),
            $this->demoBlock('content_block', [
                'title_key' => 'counsel.civil_litigation_741647b86a',
                'body' => $this->demo('counsel.p_commercial_disputes_debt_recovery_con_5c1ac80fd0'),
                'background' => 'bg-base-200',
                'padding' => 'py-12',
            ]),
            $this->demoBlock('cta', [
                'title_key' => 'counsel.not_sure_which_area_covers_your_matter_f3a491c4c4',
                'description' => "Tell us a bit about your situation and we'll confirm whether we can assist — or refer you to a firm that can.",
                'button_text' => $this->demo('counsel.schedule_consultation_05e88ca6ff'),
                'button_link_type' => 'page',
                'button_variant' => 'btn-primary',
                'background' => 'bg-primary',
                'padding' => 'py-16',
            ]),
        ]);
    }

    protected function attorneysContent(): string
    {
        return implode("\n", [
            $this->demoBlock('hero', [
                'heading' => $this->demo('counsel.p_our_attorneys_p_ce54f88271'),
                'subheading' => $this->demo('counsel.p_the_people_who_will_handle_your_matte_5463a78d3c'),
                'layout' => 'centered',
                'height' => 'min-h-[40vh]',
                'text_alignment' => 'text-center',
                'background_color' => 'bg-base-200',
                'overlay_opacity' => 0,
            ]),
            $this->demoBlock('team', [
                'heading' => $this->demo('counsel.attorneys_231cf5b0a3'),
                'members' => [
                    [
                        'name' => $this->demo('counsel.lead_partner_name_1a41186485'),
                        'role' => 'Managing Partner',
                        'bio' => '<strong>Practice focus:</strong> [Areas]. <strong>Admitted:</strong> [Year], [Jurisdiction]. <strong>Education:</strong> [LL.B./J.D.], [University], [Year]. [One paragraph on background, notable experience, and approach — factual, not marketing-voice.]',
                    ],
                    [
                        'name' => $this->demo('counsel.partner_name_e8dfe8879b'),
                        'role' => 'Partner',
                        'bio' => '<strong>Practice focus:</strong> [Areas]. <strong>Admitted:</strong> [Year], [Jurisdiction]. <strong>Education:</strong> [LL.B./J.D.], [University], [Year]. [Background paragraph.]',
                    ],
                    [
                        'name' => $this->demo('counsel.senior_associate_name_3c5e80c2c2'),
                        'role' => 'Senior Associate',
                        'bio' => '<strong>Practice focus:</strong> [Areas]. <strong>Admitted:</strong> [Year], [Jurisdiction]. <strong>Education:</strong> [LL.B./J.D.], [University], [Year]. [Background paragraph.]',
                    ],
                    [
                        'name' => $this->demo('counsel.associate_name_b6274e77d0'),
                        'role' => 'Associate',
                        'bio' => '<strong>Practice focus:</strong> [Areas]. <strong>Admitted:</strong> [Year], [Jurisdiction]. <strong>Education:</strong> [LL.B./J.D.], [University], [Year]. [Background paragraph.]',
                    ],
                ],
                'columns' => '2',
                'card_style' => 'bg-base-100 shadow',
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
            $this->demoBlock('cta', [
                'title_key' => 'counsel.want_to_work_with_a_specific_attorney_07a83f781f',
                'description' => "Mention them by name when you book — we'll honor the request whenever possible.",
                'button_text' => $this->demo('counsel.schedule_consultation_05e88ca6ff'),
                'button_link_type' => 'page',
                'button_variant' => 'btn-primary',
                'background' => 'bg-primary',
                'padding' => 'py-16',
            ]),
        ]);
    }

    protected function insightsContent(): string
    {
        return implode("\n", [
            $this->demoBlock('hero', [
                'heading' => $this->demo('counsel.p_insights_p_57f0f6f313'),
                'subheading' => $this->demo('counsel.p_plain_english_commentary_on_the_quest_93ad7ecb4f'),
                'layout' => 'centered',
                'height' => 'min-h-[40vh]',
                'text_alignment' => 'text-center',
                'background_color' => 'bg-base-200',
                'overlay_opacity' => 0,
            ]),
            $this->demoBlock('posts', [
                'posts_count' => 20,
                'show_image' => false,
                'show_excerpt' => true,
                'show_date' => true,
                'show_author' => true,
                'show_read_more' => true,
                'layout' => 'grid',
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
        ]);
    }

    protected function contactContent(): string
    {
        return implode("\n", [
            $this->demoBlock('hero', [
                'heading' => $this->demo('counsel.p_contact_firm_name_p_c5a7196107'),
                'subheading' => $this->demo('counsel.p_we_respond_within_one_business_day_fo_cf222d99a9'),
                'layout' => 'centered',
                'height' => 'min-h-[40vh]',
                'text_alignment' => 'text-center',
                'background_color' => 'bg-base-200',
                'overlay_opacity' => 0,
            ]),
            $this->demoBlock('content_block', [
                'title_key' => 'counsel.our_office_7fe22b250f',
                'body' => $this->demo('counsel.p_strong_address_strong_br_firm_name_br_d7a89ffa22'),
                'background' => 'bg-base-100',
                'padding' => 'py-12',
            ]),
            // Requires the TallCMS Pro plugin.
            $this->demoBlock('pro-map', [
                'heading' => $this->demo('counsel.find_us_d561f1ac9c'),
                'subheading' => $this->demo('counsel.office_address_with_postal_code_d8aa4cef30'),
                'latitude' => '1.3521',
                'longitude' => '103.8198',
                'address' => '[Office address with postal code]',
                'marker_title' => '[Firm Name]',
                'contact_info' => "Office hours: Mon-Fri 9am-6pm\nPhone: [Main Phone]",
                'provider' => 'openstreetmap',
                'zoom' => 16,
                'height' => 'lg',
                'show_marker' => true,
                'scrollwheel_zoom' => false,
                'rounded' => true,
                'background' => 'bg-base-200',
                'padding' => 'py-12',
            ]),
            $this->demoBlock('contact_form', [
                'title_key' => 'counsel.request_a_consultation_a726c0c744',
                'description' => 'Please do not include confidential or time-sensitive information in this form. Submitting this form does not create an attorney-client relationship.',
                'fields' => [
                    ['name' => $this->demo('counsel.name_6ae999552a'), 'type' => 'text', 'label' => $this->demo('counsel.full_name_eeb692087d'), 'required' => true, 'options' => []],
                    ['name' => $this->demo('counsel.email_a88b7dcd1a'), 'type' => 'email', 'label' => $this->demo('counsel.email_84add5b295'), 'required' => true, 'options' => []],
                    ['name' => $this->demo('counsel.phone_f6be6ca910'), 'type' => 'tel', 'label' => $this->demo('counsel.phone_77064d5265'), 'required' => true, 'options' => []],
                    ['name' => $this->demo('counsel.matter_type_2b362e96ac'), 'type' => 'select', 'label' => $this->demo('counsel.area_of_law_5af4845d8a'), 'required' => true, 'options' => ['Family Law', 'Corporate & Commercial', 'Wills & Estate Planning', 'Civil Litigation', 'Employment Law', 'Immigration', 'Other / Not sure']],
                    ['name' => $this->demo('counsel.existing_client_6edee148f3'), 'type' => 'select', 'label' => $this->demo('counsel.are_you_an_existing_client_7180cb71b9'), 'required' => true, 'options' => ['No — new enquiry', 'Yes']],
                    ['name' => $this->demo('counsel.message_6f9b9af3cd'), 'type' => 'textarea', 'label' => $this->demo('counsel.brief_description_of_the_matter_07c6106154'), 'required' => true, 'options' => []],
                ],
                'submit_button_text' => $this->demo('counsel.submit_request_8a9051c613'),
                'success_message' => $this->demo('counsel.thank_you_we_will_respond_within_one_bus_aa821180f2'),
                'auto_reply_message' => $this->demo('counsel.thank_you_for_contacting_firm_name_we_ha_319644c025'),
                'button_style' => 'btn-primary',
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
        ]);
    }

    // --- Post bodies --------------------------------------------------------

    protected function firstConsultationPost(): string
    {
        return implode("\n", [
            $this->paragraph('Most people who book a consultation with us are doing so for the first time. Here is what to expect, so you arrive prepared and leave with clarity.'),
            $this->paragraph('<strong>Before the meeting</strong>'),
            $this->paragraph("When you book, we will send a short confirmation with the attorney you'll meet, a firm address, and a pre-consultation form asking for basic context about your matter. Please fill it in — even briefly — because it helps us prepare and makes the hour more useful to you."),
            $this->paragraph('Bring copies (not originals) of any documents relevant to your matter: contracts, correspondence, court papers, medical records, bank statements, emails. If in doubt, bring it.'),
            $this->paragraph('<strong>During the meeting</strong>'),
            $this->paragraph('Our consultations are 60 minutes. The first 15 minutes are about understanding your situation: what has happened, what you are trying to achieve, and what you have already tried. The remainder is our assessment — what the law says, where the risks are, what your options look like, and what we would recommend.'),
            $this->paragraph("We try hard to be honest about strong cases, marginal ones, and the ones where you don't need us at all. If we recommend a different firm for your matter, we will tell you in plain terms why."),
            $this->paragraph('<strong>After the meeting</strong>'),
            $this->paragraph("If we're a good fit and you want to proceed, we send you an engagement letter within one business day. It spells out scope, fees, and expected timeline. You sign it, we open a file, work begins."),
            $this->paragraph("If we're not a good fit — or if after thinking about it you'd like to speak to another firm — that is fine too. The consultation stands on its own."),
            $this->paragraph('Book a consultation through the <a href="/contact">contact form</a> or call us at [Phone].'),
        ]);
    }

    protected function choosingAttorneyPost(): string
    {
        return implode("\n", [
            $this->paragraph('We refer work to other firms weekly — when a matter falls outside our practice areas, when the scale is wrong for us, or when a potential client and our firm are not a good fit. Over the years this has clarified for us what makes a good match.'),
            $this->paragraph('Here is how we think about it — useful whether you are choosing us or someone else.'),
            $this->paragraph('<strong>Subject-matter fit.</strong> Legal work is specialized. A corporate-commercial firm that occasionally takes on a divorce is almost certainly weaker at it than a family-law specialist. For anything significant, find a firm that does this type of work every week — not one where your matter is unusual.'),
            $this->paragraph('<strong>Scale fit.</strong> A large firm charging $800/hour is not the right fit for a $50,000 employment dispute. A solo practitioner may not have the bench depth to take on a $50 million commercial case. Ask candidly how your matter compares to their typical engagement. The answer matters.'),
            $this->paragraph('<strong>Fee structure fit.</strong> Hourly, fixed fee, contingency, retainer — each has trade-offs. A fee structure that works for routine contract work (fixed fee) is the wrong structure for open-ended litigation (hourly with periodic updates). Ask them to explain what they typically do for matters like yours and why.'),
            $this->paragraph('<strong>Communication fit.</strong> If you like daily updates, hire a firm that provides them. If you prefer a monthly check-in, say so. Mismatched communication expectations are the most common source of client dissatisfaction, in our experience. Raise it in the first meeting.'),
            $this->paragraph("<strong>Person fit.</strong> You are going to be talking about uncomfortable things — money, family, business problems — with this person, possibly for months. If you don't trust them after 60 minutes of conversation, keep looking. Good lawyers are not rare."),
            $this->paragraph("We try to be honest with prospective clients when we're not the right firm. Any good firm should do the same."),
        ]);
    }

    protected function feesPost(): string
    {
        return implode("\n", [
            $this->paragraph('Clients rarely enjoy discussing legal fees. We try to make it simpler than most firms do.'),
            $this->paragraph('<strong>How we structure fees</strong>'),
            $this->paragraph('We use four fee structures, depending on the matter:'),
            $this->paragraph('<strong>1. Fixed fee.</strong> For well-defined engagements — a simple will, a standard shareholder agreement, a trademark filing — we quote a fixed fee upfront. You know the cost before we start. If the scope changes, we tell you before doing the extra work.'),
            $this->paragraph('<strong>2. Hourly.</strong> For matters where scope is hard to predict — most litigation, complex transactions, ongoing advisory — we bill hourly, at the rate listed in the engagement letter. We send itemized invoices monthly with time entries you can review.'),
            $this->paragraph('<strong>3. Retainer.</strong> For ongoing client relationships, we bill monthly for a block of hours. Unused hours roll into the following month; excess hours bill at our standard hourly rate.'),
            $this->paragraph('<strong>4. Contingency / conditional fee.</strong> Only for specific matters (e.g. certain personal injury or debt recovery claims) and only where the law permits. Not available for most family or corporate work.'),
            $this->paragraph('<strong>What happens if the quote is wrong</strong>'),
            $this->paragraph('Sometimes a matter grows beyond what was foreseeable at the outset. When that happens, we stop, tell you, and ask — before we do the extra work. We do not present surprise charges at month-end. If we underestimated, we will often absorb part of the overrun; we consider it our mistake to price realistically at the outset.'),
            $this->paragraph("<strong>What if you can't afford us</strong>"),
            $this->paragraph('We refer at cost or pro bono in certain circumstances, particularly for family-law matters involving domestic violence or custody issues for clients of limited means. Ask during the consultation. There is no shame in it, and we would rather refer you than take work you cannot pay for.'),
            $this->paragraph('If you would like to discuss fees for your specific matter, book a <a href="/contact">consultation</a> — we will give you a realistic quote or range.'),
        ]);
    }
}
