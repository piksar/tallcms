<?php

declare(strict_types=1);

namespace TallCms\Cms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use TallCms\Cms\Console\Commands\Concerns\TranslatesDemoTemplateStrings;

/**
 * Seed the PUSH.SG landing page — the meta template.
 *
 * This is the marketing site for PUSH.SG itself: a Singapore landing-page
 * builder for property agents, operating since 2017. The template
 * showcases the platform's own capabilities by BEING built on the
 * platform. Agents visiting the site see what their own can look like.
 *
 * The copy is PUSH.SG-specific (Singapore, property agents, since 2017)
 * rather than generic SaaS fill-in-the-blank. Anyone else who clones it
 * would rewrite — that's fine; the structure still transfers.
 */
class SeedPushsgTemplate extends Command
{
    use TranslatesDemoTemplateStrings;

    protected $signature = 'tallcms:seed-pushsg-template
                            {--owner= : User ID to own the template site (defaults to first super_admin)}
                            {--force : Delete any existing PUSH.SG template and recreate}';

    protected $description = 'Seed the PUSH.SG landing page template — the SaaS marketing site for the platform itself';

    public function handle(): int
    {
        if (! Schema::hasTable('tallcms_sites') || ! Schema::hasColumn('tallcms_sites', 'is_template_source')) {
            $this->error(__('tallcms::console.seed_template.multisite_required'));

            return self::FAILURE;
        }

        $ownerId = $this->resolveOwnerId();
        if (! $ownerId) {
            $this->error(__('tallcms::console.seed_template.no_owner_short'));

            return self::FAILURE;
        }

        $existing = DB::table('tallcms_sites')->where('domain', 'pushsg.template')->first();
        if ($existing) {
            if (! $this->option('force')) {
                $this->components->warn(__('tallcms::console.seed_template.already_exists', ['template' => 'PUSH.SG', 'id' => $existing->id]));

                return self::SUCCESS;
            }
            $this->deleteSite((int) $existing->id);
            $this->components->info(__('tallcms::console.seed_template.removed', ['template' => 'PUSH.SG']));
        }

        $siteId = $this->createSite($ownerId);
        $this->components->info(__('tallcms::console.seed_template.created_site', ['template' => 'PUSH.SG', 'id' => $siteId]));

        $pageIds = $this->createPages($siteId, $ownerId);
        $this->components->info(__('tallcms::console.seed_template.created_pages', ['count' => count($pageIds)]));

        $this->createMenu($siteId, $pageIds);
        $this->components->info(__('tallcms::console.seed_template.created_menu'));

        $this->newLine();
        $this->components->info(__('tallcms::console.seed_template.ready', ['emoji' => '🚀', 'template' => 'PUSH.SG', 'message' => __('tallcms::console.seed_template.ready_since')]));

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
            'name' => $this->demo('pushsg.push_sg_33f2c2472b'),
            'domain' => 'pushsg.template',
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
            'pricing' => ['title_key' => 'pushsg.pricing_a0d9bbad5f', 'content' => $this->pricingContent()],
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
            'pricing' => 'Pricing',
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

    protected function homeContent(): string
    {
        return implode("\n", [
            $this->demoBlock('hero', [
                'heading' => $this->demo('pushsg.p_professional_websites_and_landing_pag_8ea1319548'),
                'subheading' => $this->demo('pushsg.p_singapore_s_landing_page_builder_for_1260b7da58'),
                'button_text' => $this->demo('pushsg.start_building_811fcbce6c'),
                'button_link_type' => 'custom',
                'button_url' => '/admin',
                'secondary_button_text' => $this->demo('pushsg.see_pricing_52a8d5d7d8'),
                'secondary_button_link_type' => 'page',
                'layout' => 'centered',
                'height' => 'min-h-[80vh]',
                'text_alignment' => 'text-center',
                'background_color' => 'bg-gradient-to-br from-primary to-secondary',
                'overlay_opacity' => 0,
                'button_variant' => 'btn-primary',
                'secondary_button_variant' => 'btn-ghost text-white hover:bg-white/20',
                'button_size' => 'btn-lg',
            ]),
            $this->demoBlock('stats', [
                'heading' => $this->demo('pushsg.singapore_built_since_2017_caf1be5045'),
                'stats' => [
                    ['value' => '8+', 'label' => $this->demo('pushsg.years_in_business_d21a75875c')],
                    ['value' => '500+', 'label' => $this->demo('pushsg.sites_launched_9e6fc36ec2')],
                    ['value' => '5', 'label' => $this->demo('pushsg.verticals_supported_b2a52aff75')],
                    ['value' => '10 min', 'label' => $this->demo('pushsg.from_signup_to_live_6a3dcfad71')],
                ],
                'columns' => '4',
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
            $this->demoBlock('content_block', [
                'title_key' => 'pushsg.from_real_estate_to_any_profession_7b33190426',
                'body' => $this->demo('pushsg.p_push_sg_started_in_2017_as_a_landing_6711195860'),
                'background' => 'bg-base-200',
                'padding' => 'py-16',
            ]),
            $this->demoBlock('features', [
                'heading' => $this->demo('pushsg.what_you_get_8ac7aa173f'),
                'subheading' => $this->demo('pushsg.a_complete_toolkit_for_professionals_who_632e41a923'),
                'features' => [
                    [
                        'icon_type' => 'heroicon',
                        'icon' => 'heroicon-o-squares-2x2',
                        'title_key' => 'pushsg.drag_and_drop_editor_98f7541d36',
                        'description' => $this->demo('pushsg.block_based_page_editor_with_live_previe_914deb71a1'),
                    ],
                    [
                        'icon_type' => 'heroicon',
                        'icon' => 'heroicon-o-paint-brush',
                        'title_key' => 'pushsg.profession_specific_templates_8aa62478d0',
                        'description' => $this->demo('pushsg.start_from_a_polished_template_tuned_for_ddec816778'),
                    ],
                    [
                        'icon_type' => 'heroicon',
                        'icon' => 'heroicon-o-building-office-2',
                        'title_key' => 'pushsg.multiple_sites_one_account_0d94bd374b',
                        'description' => $this->demo('pushsg.run_a_personal_brand_plus_a_separate_lan_7358f095ae'),
                    ],
                    [
                        'icon_type' => 'heroicon',
                        'icon' => 'heroicon-o-envelope',
                        'title_key' => 'pushsg.lead_capture_that_works_317f4d5b69',
                        'description' => $this->demo('pushsg.contact_forms_with_per_form_custom_auto_97586e72ca'),
                    ],
                    [
                        'icon_type' => 'heroicon',
                        'icon' => 'heroicon-o-device-phone-mobile',
                        'title_key' => 'pushsg.whatsapp_first_ctas_4ef5c7ba70',
                        'description' => $this->demo('pushsg.built_in_whatsapp_integration_with_pre_f_f30308c4d1'),
                    ],
                    [
                        'icon_type' => 'heroicon',
                        'icon' => 'heroicon-o-shield-check',
                        'title_key' => 'pushsg.compliance_aware_defaults_391e5bbd05',
                        'description' => $this->demo('pushsg.cea_disclaimers_for_property_attorney_cl_9433bc5740'),
                    ],
                ],
                'columns' => '3',
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
            $this->demoBlock('features', [
                'heading' => $this->demo('pushsg.ready_made_templates_by_profession_6cad1ca025'),
                'subheading' => $this->demo('pushsg.pick_the_one_that_fits_swap_in_your_name_5a5715d504'),
                'features' => [
                    [
                        'icon_type' => 'heroicon',
                        'icon' => 'heroicon-o-home',
                        'title_key' => 'pushsg.real_estate_agent_704882e552',
                        'description' => $this->demo('pushsg.personal_brand_site_with_bio_services_te_0f6c5972e8'),
                    ],
                    [
                        'icon_type' => 'heroicon',
                        'icon' => 'heroicon-o-building-office',
                        'title_key' => 'pushsg.new_launch_landing_page_8aeaea7457',
                        'description' => $this->demo('pushsg.hero_with_lead_form_project_details_unit_7701fe5ab6'),
                    ],
                    [
                        'icon_type' => 'heroicon',
                        'icon' => 'heroicon-o-scale',
                        'title_key' => 'pushsg.law_firm_attorney_3cc48a979a',
                        'description' => $this->demo('pushsg.conservative_trust_building_site_for_sol_bb49f69aa0'),
                    ],
                    [
                        'icon_type' => 'heroicon',
                        'icon' => 'heroicon-o-pencil-square',
                        'title_key' => 'pushsg.blog_newsletter_71874cc0ac',
                        'description' => $this->demo('pushsg.content_first_site_for_writers_essayists_84bb389d33'),
                    ],
                ],
                'columns' => '2',
                'background' => 'bg-base-200',
                'padding' => 'py-16',
            ]),
            $this->demoBlock('testimonials', [
                'heading' => $this->demo('pushsg.what_customers_say_a80a357d86'),
                'testimonials' => [
                    [
                        'quote' => "I was paying an agency S\$3,000 per landing page per new launch. PUSH.SG gave me the same thing in an hour for a fraction of the cost. I've spun up 12 landing pages in the past year.",
                        'author_name' => '[Customer Name]',
                        'author_title' => 'Top producer, [Real Estate Agency]',
                    ],
                    [
                        'quote' => 'Our firm needed a website that looked professional without reading like marketing fluff. The law-firm template hit the tone exactly — factual, conservative, compliance-aware. Up in a weekend.',
                        'author_name' => '[Customer Name]',
                        'author_title' => 'Managing Partner, [Law Firm]',
                    ],
                    [
                        'quote' => "I've been writing online for years and could never find a template that didn't scream blogger-in-2012. Picked the Ink template, swapped in my bio, done. Readers actually finish posts now.",
                        'author_name' => '[Customer Name]',
                        'author_title' => 'Writer and consultant',
                    ],
                ],
                'columns' => '3',
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
            $this->demoBlock('faq', [
                'heading' => $this->demo('keystone.frequently_asked_0b8a19ed98'),
                'items' => [
                    ['question' => 'Do I need to know any code?', 'answer' => 'No. The editor is drag-and-drop; the templates are done. You type your name, phone, license number — and you are live.'],
                    ['question' => 'Can I use my own domain?', 'answer' => 'Yes. Every paid plan includes custom domains. Verify once, we handle TLS automatically.'],
                    ['question' => 'What about my existing push.sg sites?', 'answer' => 'Current customers migrate for free. Reach out via the contact form and we\'ll walk you through it.'],
                    ['question' => 'How many sites can I have?', 'answer' => 'Depends on your plan — see Pricing. Most agents start on Solo (3 sites) and upgrade to Pro (10 sites) once they start doing new launches.'],
                    ['question' => 'Is this CEA-compliant?', 'answer' => 'Yes. Disclaimer language, independent-marketer notices, and data-handling text are pre-written and reviewed. You can customize them, but the defaults pass muster.'],
                    ['question' => 'Do you do custom design?', 'answer' => 'No — that\'s exactly what we built PUSH.SG to avoid. If you want a bespoke designer site, use an agency. If you want to launch fast and look good, use us.'],
                ],
                'background' => 'bg-base-200',
                'padding' => 'py-16',
            ]),
            $this->demoBlock('cta', [
                'title_key' => 'pushsg.ready_to_build_8c9b9ba9ea',
                'description' => $this->demo('pushsg.if_you_have_a_beta_account_log_in_and_la_25f71aa524'),
                'button_text' => $this->demo('pushsg.start_building_811fcbce6c'),
                'button_link_type' => 'custom',
                'button_url' => '/admin',
                'button_variant' => 'btn-primary',
                'button_size' => 'btn-lg',
                'background' => 'bg-primary',
                'padding' => 'py-16',
            ]),
        ]);
    }

    protected function pricingContent(): string
    {
        return implode("\n", [
            $this->demoBlock('hero', [
                'heading' => $this->demo('pushsg.p_simple_pricing_per_site_p_312c7c666d'),
                'subheading' => $this->demo('pushsg.p_all_plans_include_the_full_editor_tem_7a7e348423'),
                'layout' => 'centered',
                'height' => 'min-h-[40vh]',
                'text_alignment' => 'text-center',
                'background_color' => 'bg-base-200',
                'overlay_opacity' => 0,
            ]),
            $this->demoBlock('pricing', [
                'section_title' => 'Choose your plan',
                'section_subtitle' => 'Start small. Upgrade as you scale your pipeline.',
                'plans' => [
                    [
                        'name' => $this->demo('pushsg.solo_9fc93acaa4'),
                        'description' => $this->demo('pushsg.for_individual_agents_building_a_persona_7e44893913'),
                        'is_popular' => false,
                        'currency_symbol' => 'S$',
                        'price' => '29',
                        'billing_period' => 'month',
                        'features' => [
                            ['text' => 'Up to 3 sites', 'included' => true],
                            ['text' => 'Custom domain support', 'included' => true],
                            ['text' => 'All templates included', 'included' => true],
                            ['text' => 'Email support', 'included' => true],
                            ['text' => 'Priority support', 'included' => false],
                        ],
                    ],
                    [
                        'name' => $this->demo('pushsg.pro_66d0c5e6b1'),
                        'description' => $this->demo('pushsg.for_active_agents_running_new_launch_cam_398e6e980d'),
                        'is_popular' => true,
                        'popular_badge_text' => $this->demo('pushsg.most_popular_5adbdd07e7'),
                        'currency_symbol' => 'S$',
                        'price' => '79',
                        'billing_period' => 'month',
                        'features' => [
                            ['text' => 'Up to 10 sites', 'included' => true],
                            ['text' => 'Custom domain support', 'included' => true],
                            ['text' => 'All templates included', 'included' => true],
                            ['text' => 'WhatsApp priority support', 'included' => true],
                            ['text' => 'Early access to new templates', 'included' => true],
                        ],
                    ],
                    [
                        'name' => $this->demo('pushsg.team_218887269a'),
                        'description' => $this->demo('pushsg.for_agency_branches_and_property_teams_1728b480e1'),
                        'is_popular' => false,
                        'currency_symbol' => 'S$',
                        'price' => '199',
                        'billing_period' => 'month',
                        'features' => [
                            ['text' => 'Unlimited sites', 'included' => true],
                            ['text' => 'Custom domain support', 'included' => true],
                            ['text' => 'All templates included', 'included' => true],
                            ['text' => 'Dedicated account manager', 'included' => true],
                            ['text' => 'Custom template requests', 'included' => true],
                        ],
                    ],
                ],
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
            $this->demoBlock('faq', [
                'heading' => $this->demo('pushsg.pricing_questions_34a4cc6da8'),
                'items' => [
                    ['question' => 'How do I sign up?', 'answer' => "We are in invite-only beta. Reach out via the contact form and tell us a bit about what you are building — we are onboarding new users weekly, weighted toward professions we can support well (real estate, law, blogs, consulting). Public signups open once we are confident every template ships as good as we\\'d want to ship it ourselves."],
                    ['question' => 'Can I change plans?', 'answer' => 'Yes, any time. Upgrades are prorated; downgrades take effect at the next billing cycle.'],
                    ['question' => 'What happens to my sites if I cancel?', 'answer' => 'Sites go offline, but you keep your data for 90 days in case you come back. After 90 days, data is permanently deleted unless you export it first.'],
                    ['question' => 'Can I pay annually for a discount?', 'answer' => 'Yes — annual plans get 2 months free (equivalent to ~17% off). Contact us for annual pricing.'],
                ],
                'background' => 'bg-base-200',
                'padding' => 'py-16',
            ]),
        ]);
    }

    protected function contactContent(): string
    {
        return implode("\n", [
            $this->demoBlock('hero', [
                'heading' => $this->demo('ink.p_get_in_touch_p_cdcac55e03'),
                'subheading' => $this->demo('pushsg.p_questions_about_plans_migrations_or_w_6cf0e2d021'),
                'layout' => 'centered',
                'height' => 'min-h-[40vh]',
                'text_alignment' => 'text-center',
                'background_color' => 'bg-base-200',
                'overlay_opacity' => 0,
            ]),
            $this->demoBlock('content_block', [
                'title_key' => 'pushsg.reach_us_086f6a10b6',
                'body' => $this->demo('pushsg.p_strong_whatsapp_phone_strong_main_wha_e8e8690e2d'),
                'background' => 'bg-base-100',
                'padding' => 'py-12',
            ]),
            $this->demoBlock('contact_form', [
                'title_key' => 'pushsg.drop_us_a_note_22e115762b',
                'fields' => [
                    ['name' => $this->demo('counsel.name_6ae999552a'), 'type' => 'text', 'label' => $this->demo('ink.your_name_ab42293e29'), 'required' => true, 'options' => []],
                    ['name' => $this->demo('counsel.email_a88b7dcd1a'), 'type' => 'email', 'label' => $this->demo('counsel.email_84add5b295'), 'required' => true, 'options' => []],
                    ['name' => $this->demo('counsel.phone_f6be6ca910'), 'type' => 'tel', 'label' => $this->demo('launchpad.phone_sg_preferred_0f8e58017d'), 'required' => false, 'options' => []],
                    ['name' => $this->demo('pushsg.topic_b415e16fbe'), 'type' => 'select', 'label' => $this->demo('pushsg.what_is_this_about_d9c256d322'), 'required' => true, 'options' => ['New signup questions', 'Migration from old PUSH.SG', 'Existing customer support', 'Custom template request (Team plan)', 'Partnership / agency enquiry', 'Other']],
                    ['name' => $this->demo('counsel.message_6f9b9af3cd'), 'type' => 'textarea', 'label' => $this->demo('ink.message_68f4145fee'), 'required' => true, 'options' => []],
                ],
                'submit_button_text' => $this->demo('keystone.send_message_c70a890d14'),
                'success_message' => $this->demo('pushsg.thanks_we_ll_reply_within_one_business_d_da6ed78c91'),
                'auto_reply_message' => $this->demo('pushsg.hi_thanks_for_reaching_out_to_push_sg_we_ff0465ec5d'),
                'button_style' => 'btn-primary',
                'background' => 'bg-base-200',
                'padding' => 'py-16',
            ]),
        ]);
    }
}
