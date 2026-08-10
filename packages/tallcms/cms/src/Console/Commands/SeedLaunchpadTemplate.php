<?php

declare(strict_types=1);

namespace TallCms\Cms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use TallCms\Cms\Console\Commands\Concerns\TranslatesDemoTemplateStrings;

/**
 * Seed the "Launchpad" template — a landing page for a property launch.
 *
 * Singapore-shaped: designed for licensed property agents marketing new
 * launches (condo, executive condo, landed) as independent marketers.
 * The page matches the conventions of current SG new-launch sites:
 * single-page scroll, project stats as tiles, location narrative, unit
 * mix, amenities, brochure gate, register-interest form, and the
 * legally-required independent-marketer disclaimer.
 *
 * Scope: one project per site. For a multi-project agent, they'd clone
 * this once per launch (Narra Residences, The Collective, etc.) and
 * customize. The Template Gallery makes that straightforward.
 *
 * Copy uses bracketed placeholders for fields that change per launch:
 * [Project Name], [District #], [Developer], [TOP], [Unit Count],
 * [99-year leasehold / freehold], unit type sizes and prices, etc.
 */
class SeedLaunchpadTemplate extends Command
{
    use TranslatesDemoTemplateStrings;

    protected $signature = 'tallcms:seed-launchpad-template
                            {--owner= : User ID to own the template site (defaults to first super_admin)}
                            {--force : Delete any existing Launchpad template and recreate}';

    protected $description = 'Seed the Launchpad template for new property launch landing pages';

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

        $existing = DB::table('tallcms_sites')->where('domain', 'launchpad.template')->first();
        if ($existing) {
            if (! $this->option('force')) {
                $this->components->warn(__('tallcms::console.seed_template.already_exists', ['template' => 'Launchpad', 'id' => $existing->id]));

                return self::SUCCESS;
            }
            $this->deleteSite((int) $existing->id);
            $this->components->info(__('tallcms::console.seed_template.removed', ['template' => 'Launchpad']));
        }

        $siteId = $this->createSite($ownerId);
        $this->components->info(__('tallcms::console.seed_template.created_site', ['template' => 'Launchpad', 'id' => $siteId]));

        $pageIds = $this->createPages($siteId, $ownerId);
        $this->components->info(__('tallcms::console.seed_template.created_pages', ['count' => count($pageIds)]));

        $this->createMenu($siteId, $pageIds);
        $this->components->info(__('tallcms::console.seed_template.created_menu'));

        $this->createPosts($siteId, $ownerId);
        $this->components->info(__('tallcms::console.seed_template.created_posts', ['count' => 3, 'type' => __('tallcms::console.seed_template.post_type_update')]));

        $this->newLine();
        $this->components->info(__('tallcms::console.seed_template.ready', ['emoji' => '🏢', 'template' => 'Launchpad', 'message' => __('tallcms::console.seed_template.ready_gallery')]));

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
            'name' => $this->demo('launchpad.launchpad_38d7d441ad'),
            'domain' => 'launchpad.template',
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
            'home' => [
                'title' => $this->demoJson('menu.home'),
                'is_homepage' => true,
                'content' => $this->homeContent(),
            ],
            'thank-you' => [
                'title_key' => 'launchpad.thank_you_30c59ce5f2',
                'content' => $this->thankYouContent(),
            ],
            'disclaimer' => [
                'title_key' => 'launchpad.disclaimer_4c4a2e80cc',
                'content' => $this->disclaimerContent(),
            ],
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

        // Landing pages usually just have anchor nav within the home page
        // plus a disclaimer link in the footer. Keep the primary menu tiny.
        $lft = 1;
        foreach ([
            ['label' => $this->demo('launchpad.project_f6f4da8d93'), 'type' => 'page', 'page_id' => $pageIds['home']],
            ['label' => $this->demo('launchpad.register_interest_00d4c5ce5f'), 'type' => 'page', 'page_id' => $pageIds['home'], 'anchor' => 'register'],
            ['label' => $this->demo('launchpad.disclaimer_4c4a2e80cc'), 'type' => 'page', 'page_id' => $pageIds['disclaimer']],
        ] as $item) {
            DB::table('tallcms_menu_items')->insert([
                'menu_id' => $menuId,
                'type' => $item['type'],
                'label' => json_encode(['en' => $item['label']]),
                'page_id' => $item['page_id'] ?? null,
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
                'title_key' => 'launchpad.showflat_preview_now_open_book_your_slot_9585519462',
                'slug' => 'showflat-preview-open',
                'excerpt' => 'The [Project Name] showflat preview is open by appointment. Slots fill fast during the preview phase — reserve yours now.',
                'content' => $this->showflatPost(),
            ],
            [
                'title_key' => 'launchpad.construction_update_what_has_happened_si_c400a336f2',
                'slug' => 'construction-update',
                'excerpt' => 'A brief progress report on where [Project Name] is in its build timeline, ahead of TOP in [Q# YYYY].',
                'content' => $this->constructionPost(),
            ],
            [
                'title_key' => 'launchpad.what_to_expect_at_the_project_name_showf_b40c65032a',
                'slug' => 'what-to-expect',
                'excerpt' => "First time viewing a new launch showflat? Here's a quick walk-through of what you'll see, what to ask, and what to bring.",
                'content' => $this->whatToExpectPost(),
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
                'published_at' => now()->subDays($i * 7),
                'created_at' => now()->subDays($i * 7),
                'updated_at' => now()->subDays($i * 7),
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
                'heading' => $this->demo('launchpad.p_project_name_p_a7e1d5f0dd'),
                'subheading' => $this->demo('launchpad.p_short_evocative_tagline_e_g_nature_in_ed621ecac7'),
                'button_text' => $this->demo('launchpad.register_interest_4ae0d3ceff'),
                'button_link_type' => 'custom',
                'button_url' => '#register',
                'secondary_button_text' => $this->demo('launchpad.book_showflat_preview_ac56ea8d7b'),
                'secondary_button_link_type' => 'external',
                'secondary_button_url' => 'https://wa.me/659999999?text=Hi%20I%20am%20interested%20in%20[Project%20Name]',
                'secondary_button_microcopy' => 'WhatsApp link — replace 659999999 with your phone and update the project name in the text= parameter.',
                'layout' => 'centered',
                'height' => 'min-h-[85vh]',
                'text_alignment' => 'text-center',
                'background_color' => 'bg-gradient-to-br from-neutral to-base-300',
                'overlay_opacity' => 0,
                'button_variant' => 'btn-primary',
                'secondary_button_variant' => 'btn-success',
                'button_size' => 'btn-lg',
            ]),
            $this->demoBlock('content_block', [
                'title_key' => 'launchpad.about_project_name_846f91a7a9',
                'body' => $this->demo('launchpad.p_project_name_is_a_tenure_99_year_leas_d00c6a4412'),
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
            $this->demoBlock('stats', [
                'heading' => $this->demo('launchpad.key_details_72122f44c9'),
                'stats' => [
                    ['value' => '[District #]', 'label' => $this->demo('launchpad.district_c0cb139cce')],
                    ['value' => '[Tenure]', 'label' => $this->demo('launchpad.tenure_f63815b4ce')],
                    ['value' => '[Q# YYYY]', 'label' => $this->demo('launchpad.expected_top_9e3750848f')],
                    ['value' => '[Unit Count]', 'label' => $this->demo('launchpad.total_units_92cb331d86')],
                ],
                'columns' => '4',
                'background' => 'bg-base-200',
                'padding' => 'py-16',
            ]),
            $this->demoBlock('features', [
                'heading' => $this->demo('launchpad.unit_mix_85983280d3'),
                'subheading' => $this->demo('launchpad.indicative_sizes_and_starting_prices_fin_68f94ceb94'),
                'features' => [
                    [
                        'icon_type' => 'heroicon',
                        'icon' => 'heroicon-o-home',
                        'title_key' => 'launchpad.1_bedroom_a0c895ac49',
                        'description' => '[XXX-XXX sqft] · From $[X.XX]M',
                    ],
                    [
                        'icon_type' => 'heroicon',
                        'icon' => 'heroicon-o-home-modern',
                        'title_key' => 'launchpad.2_bedroom_d0853937ca',
                        'description' => '[XXX-XXX sqft] · From $[X.XX]M',
                    ],
                    [
                        'icon_type' => 'heroicon',
                        'icon' => 'heroicon-o-building-office-2',
                        'title_key' => 'launchpad.3_bedroom_bdb23bc861',
                        'description' => '[XXXX-XXXX sqft] · From $[X.XX]M',
                    ],
                    [
                        'icon_type' => 'heroicon',
                        'icon' => 'heroicon-o-sparkles',
                        'title_key' => 'launchpad.penthouse_4ca9996817',
                        'description' => '[XXXX+ sqft] · POA',
                    ],
                ],
                'columns' => '4',
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
            $this->demoBlock('features', [
                'heading' => $this->demo('launchpad.amenities_facilities_6af407ece1'),
                'features' => [
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-sun', 'title_key' => 'launchpad.50m_lap_pool_5c37478700', 'description' => $this->demo('launchpad.full_length_swimming_pool_with_sun_loung_5a8733a262')],
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-bolt', 'title_key' => 'launchpad.fully_equipped_gym_7f6267e417', 'description' => 'Strength and cardio equipment, open 24/7 to residents.'],
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-fire', 'title_key' => 'launchpad.bbq_pavilions_2dbb1b6903', 'description' => $this->demo('launchpad.multiple_bookable_pavilions_for_gatherin_b348e01e8f')],
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-building-library', 'title_key' => 'launchpad.clubhouse_8e9158ac43', 'description' => 'A residents-only clubhouse for events and private bookings.'],
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-cloud', 'title_key' => 'launchpad.sky_garden_3ba59cd6b1', 'description' => "Rooftop garden with panoramic views — the development's signature feature."],
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-user-group', 'title' => "Children's Playground", 'description' => 'Thoughtfully-designed play area, shaded and safety-rated.'],
                ],
                'columns' => '3',
                'background' => 'bg-base-200',
                'padding' => 'py-16',
            ]),
            $this->demoBlock('content_block', [
                'title_key' => 'launchpad.location_neighborhood_name_72de06cc43',
                'body' => $this->demo('launchpad.p_neighborhood_name_is_two_sentence_evo_7d68d5d378'),
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
            // Requires the TallCMS Pro plugin for the pro-map block.
            // Defaults below drop a marker on central Singapore — swap the
            // lat/lng, address, and marker_title to your project's site.
            // OpenStreetMap provider requires no API key.
            $this->demoBlock('pro-map', [
                'heading' => $this->demo('launchpad.find_the_site_5250a6bf11'),
                'subheading' => $this->demo('launchpad.project_address_with_postal_code_90bdd69220'),
                'latitude' => '1.3521',
                'longitude' => '103.8198',
                'address' => '[Project address with postal code]',
                'marker_title' => '[Project Name]',
                'contact_info' => "Showflat: [Address]\nHours: [Mon-Sun, by appointment]\nWhatsApp: [Phone]",
                'provider' => 'openstreetmap',
                'zoom' => 15,
                'height' => 'lg',
                'show_marker' => true,
                'scrollwheel_zoom' => false,
                'rounded' => true,
                'background' => 'bg-base-200',
                'padding' => 'py-16',
            ]),
            $this->demoBlock('stats', [
                'heading' => $this->demo('launchpad.what_s_nearby_c4d3f24443'),
                'stats' => [
                    ['value' => '[X]', 'label' => $this->demo('launchpad.min_walk_to_mrt_station_ba725d4e3b')],
                    ['value' => '[X]', 'label' => $this->demo('launchpad.min_drive_to_cbd_81be75dbd4')],
                    ['value' => '[X]', 'label' => $this->demo('launchpad.top_schools_within_2km_8e84079e04')],
                    ['value' => '[X]', 'label' => $this->demo('launchpad.retail_malls_within_5min_9b6ccaa3b6')],
                ],
                'columns' => '4',
                'background' => 'bg-base-200',
                'padding' => 'py-16',
            ]),
            $this->demoBlock('cta', [
                'title_key' => 'launchpad.download_the_full_brochure_d3967d5f5d',
                'description' => 'Complete unit mix, floor plans, site plan, developer details, and pricing indications.',
                'button_text' => $this->demo('launchpad.download_brochure_81ccb84505'),
                'button_link_type' => 'custom',
                'button_url' => '#register',
                'button_microcopy' => 'Complete the form below to receive the brochure by email.',
                'button_variant' => 'btn-primary',
                'button_size' => 'btn-lg',
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
            $this->demoBlock('contact_form', [
                'title_key' => 'launchpad.register_your_interest_277666eb39',
                'description' => 'Fill in your details and our sales team will reach out with the brochure, pricing, and showflat availability within 24 hours.',
                'anchor_id' => 'register',
                'fields' => [
                    ['name' => $this->demo('counsel.name_6ae999552a'), 'type' => 'text', 'label' => $this->demo('counsel.full_name_eeb692087d'), 'required' => true, 'options' => []],
                    ['name' => $this->demo('counsel.email_a88b7dcd1a'), 'type' => 'email', 'label' => $this->demo('counsel.email_84add5b295'), 'required' => true, 'options' => []],
                    ['name' => $this->demo('counsel.phone_f6be6ca910'), 'type' => 'tel', 'label' => $this->demo('launchpad.phone_sg_preferred_0f8e58017d'), 'required' => true, 'options' => []],
                    ['name' => $this->demo('launchpad.unit_type_5d6c102160'), 'type' => 'select', 'label' => $this->demo('launchpad.preferred_unit_type_8d67964575'), 'required' => true, 'options' => ['1 Bedroom', '2 Bedroom', '3 Bedroom', 'Penthouse', 'Still deciding']],
                    ['name' => $this->demo('launchpad.buyer_status_1f61012a40'), 'type' => 'select', 'label' => $this->demo('launchpad.buyer_status_c517bddf0b'), 'required' => true, 'options' => ['Singapore Citizen', 'Singapore PR', 'Foreigner', 'Company/Trust']],
                    ['name' => $this->demo('counsel.message_6f9b9af3cd'), 'type' => 'textarea', 'label' => $this->demo('launchpad.anything_specific_you_want_to_know_8d9813c4bd'), 'required' => false, 'options' => []],
                ],
                'submit_button_text' => $this->demo('launchpad.register_interest_4ae0d3ceff'),
                'success_message' => $this->demo('launchpad.thank_you_we_ll_be_in_touch_with_the_bro_b51de030f0'),
                'auto_reply_message' => $this->demo('launchpad.thanks_for_registering_your_interest_in_c66561dc50'),
                'redirect_page_id' => null,
                'button_style' => 'btn-primary',
                'background' => 'bg-primary',
                'padding' => 'py-16',
            ]),
            $this->demoBlock('content_block', [
                'title_key' => 'launchpad.the_developer_ee1e4fcb45',
                'body' => $this->demo('launchpad.p_strong_developer_strong_has_been_shap_568251e9c0'),
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
            $this->demoBlock('content_block', [
                'title_key' => 'launchpad.disclaimer_4c4a2e80cc',
                'body' => $this->demo('launchpad.p_style_font_size_0_9em_color_666_this_6dbefed317'),
                'background' => 'bg-base-200',
                'padding' => 'py-12',
            ]),
        ]);
    }

    protected function thankYouContent(): string
    {
        return implode("\n", [
            $this->demoBlock('hero', [
                'heading' => $this->demo('launchpad.p_thank_you_p_ea2fd536da'),
                'subheading' => $this->demo('launchpad.p_we_ve_got_your_details_and_will_send_001b108508'),
                'button_text' => $this->demo('launchpad.back_to_the_project_cc2f1eb3fd'),
                'button_link_type' => 'page',
                'layout' => 'centered',
                'height' => 'min-h-[60vh]',
                'text_alignment' => 'text-center',
                'background_color' => 'bg-gradient-to-br from-neutral to-base-300',
                'overlay_opacity' => 0,
            ]),
            $this->demoBlock('content_block', [
                'title_key' => 'launchpad.what_happens_next_51ecc5b21f',
                'body' => $this->demo('launchpad.p_1_check_your_inbox_the_brochure_is_on_7ded0e9c82'),
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
        ]);
    }

    protected function disclaimerContent(): string
    {
        return implode("\n", [
            $this->demoBlock('hero', [
                'heading' => $this->demo('launchpad.p_disclaimer_p_b67167f4de'),
                'subheading' => $this->demo('launchpad.p_the_fine_print_for_project_name_p_cc24f20acd'),
                'layout' => 'centered',
                'height' => 'min-h-[30vh]',
                'text_alignment' => 'text-center',
                'background_color' => 'bg-base-200',
                'overlay_opacity' => 0,
            ]),
            $this->demoBlock('content_block', [
                'title_key' => 'launchpad.independent_marketing_website_d77fecd9d5',
                'body' => $this->demo('launchpad.p_this_website_is_owned_and_operated_by_7ed4da86d1'),
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
        ]);
    }

    // --- Post bodies --------------------------------------------------------

    protected function showflatPost(): string
    {
        return implode("\n", [
            $this->paragraph("The [Project Name] showflat preview is now open by appointment. During the preview phase, slots are limited and allocated on a first-reserved basis — so book early if you're keen to view."),
            $this->paragraph('<strong>What the preview includes:</strong>'),
            $this->paragraph('• A fully-furnished show unit — typically a [preview unit type, e.g. 3-bedroom premium] laid out to scale so you can experience the space as it will be delivered.'),
            $this->paragraph('• Site plan walkthrough with one of our specialists, covering facilities, orientation, and the unit mix.'),
            $this->paragraph('• Indicative pricing, payment schedule, and availability.'),
            $this->paragraph('• Answers to the specific questions you have — bring them.'),
            $this->paragraph('<strong>What to bring:</strong>'),
            $this->paragraph('• Photo ID (NRIC or passport).'),
            $this->paragraph('• Your buyer status (Singapore Citizen, PR, Foreigner, Company/Trust) — this affects ABSD and eligibility.'),
            $this->paragraph("• If you're buying with a partner or family member, bring them too."),
            $this->paragraph('<strong>Book your slot:</strong> fill in the form on the <a href="/">project page</a> or WhatsApp us directly.'),
        ]);
    }

    protected function constructionPost(): string
    {
        return implode("\n", [
            $this->paragraph('A quick progress update on [Project Name] ahead of expected TOP in <strong>[Q# YYYY]</strong>.'),
            $this->paragraph('<strong>Site works</strong>: Groundworks and piling completed [Month YYYY]. Structural works currently at [X]% — the main tower reaches [floor number] level, with the [feature, e.g. sky garden] lift frame taking shape.'),
            $this->paragraph('<strong>Timeline</strong>: Topping out expected by [Q# YYYY]. Façade and M&E fit-out follows through [timeframe]. TOP remains scheduled for [Q# YYYY], with CSC expected [X] months later.'),
            $this->paragraph("<strong>What this means for buyers</strong>: Units sold during the launch phase remain under the original payment schedule — progress payments trigger at each construction milestone per the official S&P. If you're a current buyer and would like a personalized progress update on your unit, we can arrange that — reach out via WhatsApp."),
            $this->paragraph("<strong>For prospective buyers</strong>: Construction progress doesn't change the showflat preview experience — the showflat is built to the same specifications as the delivered unit. Pricing and availability are refreshed at each release phase."),
        ]);
    }

    protected function whatToExpectPost(): string
    {
        return implode("\n", [
            $this->paragraph("First time viewing a new launch showflat? Here's what typically happens — and what to pay attention to."),
            $this->paragraph('<strong>The walkthrough (30-45 minutes)</strong>'),
            $this->paragraph("You'll start with a brief welcome and orientation at the marketing suite — usually a dedicated space next to the showflat. A specialist will walk you through the site plan (where the towers are, where the amenities are, orientation of each block) and the unit mix (what sizes and layouts are available, how pricing works at each level)."),
            $this->paragraph("Then you'll walk through the show unit itself — typically a premium unit type that shows the development at its best. Take notes on: kitchen layout (is it closed or open?), storage (adequate for your family?), natural light (which direction does the unit face?), and the feel of the space. Photos are usually allowed."),
            $this->paragraph('<strong>What to ask</strong>'),
            $this->paragraph('• <em>Unit availability</em>: which stacks (column of units on the same floor across levels) are still available at my budget?'),
            $this->paragraph("• <em>Pricing per square foot</em>: what's the psf range, and how does it compare to nearby comparable launches?"),
            $this->paragraph('• <em>Orientation</em>: which direction does the unit face (north, east-facing sunrise, etc.) and what will I see?'),
            $this->paragraph('• <em>Floor premium</em>: how much does the same unit cost on different floors?'),
            $this->paragraph('• <em>Payment schedule</em>: what are the progress payment milestones?'),
            $this->paragraph("• <em>ABSD</em>: if you're a second-property buyer, foreigner, or PR, get clarity on your total cost including stamp duties."),
            $this->paragraph('<strong>What to bring</strong>'),
            $this->paragraph("Your ID, your buyer status info, and ideally your partner / family member if this is a joint purchase. If you're ready to move quickly, your IPA (In-Principle Approval from a bank) helps — but it's not required for the initial viewing."),
            $this->paragraph('<strong>After the viewing</strong>'),
            $this->paragraph("There's no obligation. A good agent will follow up with the brochure, answer questions you didn't think to ask in person, and wait for you to decide. If you feel pressured — find another agent."),
            $this->paragraph("Book a preview via the form on the <a href=\"/\">project page</a>. We'll confirm your slot within a business day."),
        ]);
    }
}
