<?php

declare(strict_types=1);

namespace TallCms\Cms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use TallCms\Cms\Console\Commands\Concerns\TranslatesDemoTemplateStrings;

/**
 * Seed the "Spotlight" template — a premium variant of Launchpad for
 * property-launch landing pages that captures leads in the hero.
 *
 * Variation from Launchpad:
 *  - Hero uses the with-form layout so the lead-capture form sits
 *    alongside the hero copy on first paint. No scroll required to
 *    register interest. Converts better for high-intent traffic
 *    (paid ads, email campaigns, QR codes at the showflat).
 *  - No separate Register Interest section later in the page (the
 *    hero form handles it).
 *  - More premium / evocative copy tone — positioned for higher-end
 *    launches (freehold, luxury condo, landed) where the pitch is
 *    lifestyle rather than best-value.
 *
 * Same SG-specific compliance as Launchpad: CEA disclaimer page,
 * independent-marketer language, ABSD-aware buyer status field.
 */
class SeedSpotlightTemplate extends Command
{
    use TranslatesDemoTemplateStrings;

    protected $signature = 'tallcms:seed-spotlight-template
                            {--owner= : User ID to own the template site (defaults to first super_admin)}
                            {--force : Delete any existing Spotlight template and recreate}';

    protected $description = 'Seed the Spotlight template — a premium property launch landing page with in-hero lead form';

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

        $existing = DB::table('tallcms_sites')->where('domain', 'spotlight.template')->first();
        if ($existing) {
            if (! $this->option('force')) {
                $this->components->warn(__('tallcms::console.seed_template.already_exists', ['template' => 'Spotlight', 'id' => $existing->id]));

                return self::SUCCESS;
            }
            $this->deleteSite((int) $existing->id);
            $this->components->info(__('tallcms::console.seed_template.removed', ['template' => 'Spotlight']));
        }

        $siteId = $this->createSite($ownerId);
        $this->components->info(__('tallcms::console.seed_template.created_site', ['template' => 'Spotlight', 'id' => $siteId]));

        $pageIds = $this->createPages($siteId, $ownerId);
        $this->components->info(__('tallcms::console.seed_template.created_pages', ['count' => count($pageIds)]));

        $this->createMenu($siteId, $pageIds);
        $this->components->info(__('tallcms::console.seed_template.created_menu'));

        $this->newLine();
        $this->components->info(__('tallcms::console.seed_template.ready', ['emoji' => '✨', 'template' => 'Spotlight', 'message' => __('tallcms::console.seed_template.ready_gallery')]));

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
            'name' => $this->demo('spotlight.spotlight_14ab8041d7'),
            'domain' => 'spotlight.template',
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

        $lft = 1;
        foreach ([
            ['label' => $this->demo('spotlight.the_residence_7190677061'), 'type' => 'page', 'page_id' => $pageIds['home']],
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

    // --- Block helpers ------------------------------------------------------

    // --- Page contents ------------------------------------------------------

    protected function homeContent(): string
    {
        return implode("\n", [
            $this->demoBlock('hero', [
                'heading' => $this->demo('launchpad.p_project_name_p_a7e1d5f0dd'),
                'subheading' => $this->demo('spotlight.p_a_limited_collection_of_unit_count_re_77e52ae64f'),
                // Hero's with-form layout renders a form card alongside the
                // hero copy instead of duplicating contact form lower down.
                'layout' => 'with-form',
                'height' => 'min-h-[90vh]',
                'text_alignment' => 'text-left',
                'background_color' => 'bg-gradient-to-br from-neutral to-base-300',
                'overlay_opacity' => 40,
                'form_title' => 'Priority preview access',
                'form_fields' => [
                    ['name' => $this->demo('counsel.name_6ae999552a'), 'type' => 'text', 'label' => $this->demo('spotlight.name_709a23220f'), 'required' => true, 'options' => []],
                    ['name' => $this->demo('counsel.email_a88b7dcd1a'), 'type' => 'email', 'label' => $this->demo('counsel.email_84add5b295'), 'required' => true, 'options' => []],
                    ['name' => $this->demo('counsel.phone_f6be6ca910'), 'type' => 'tel', 'label' => $this->demo('counsel.phone_77064d5265'), 'required' => true, 'options' => []],
                    ['name' => $this->demo('launchpad.unit_type_5d6c102160'), 'type' => 'select', 'label' => $this->demo('spotlight.interested_in_f165d748ae'), 'required' => true, 'options' => ['1 Bedroom', '2 Bedroom', '3 Bedroom', 'Penthouse', 'Still deciding']],
                    ['name' => $this->demo('launchpad.buyer_status_1f61012a40'), 'type' => 'select', 'label' => $this->demo('spotlight.buyer_profile_6c5216dbcd'), 'required' => true, 'options' => ['Singapore Citizen', 'Singapore PR', 'Foreigner', 'Company/Trust']],
                ],
                'form_submit_text' => $this->demo('spotlight.get_the_brochure_45800248fc'),
                'form_success_message' => $this->demo('spotlight.thank_you_we_ll_be_in_touch_with_the_bro_7478294628'),
                'form_button_style' => 'btn-primary',
                'form_card_style' => 'bg-base-100 shadow-2xl',
            ]),
            $this->demoBlock('content_block', [
                'title_key' => 'spotlight.a_quiet_assertion_of_arrival_81952f2cef',
                'body' => $this->demo('spotlight.p_project_name_is_a_tenure_development_26fa2927f3'),
                'background' => 'bg-base-100',
                'padding' => 'py-24',
            ]),
            $this->demoBlock('stats', [
                'heading' => $this->demo('spotlight.at_a_glance_fca77b7e3a'),
                'stats' => [
                    ['value' => '[District #]', 'label' => $this->demo('launchpad.district_c0cb139cce')],
                    ['value' => '[Tenure]', 'label' => $this->demo('launchpad.tenure_f63815b4ce')],
                    ['value' => '[Q# YYYY]', 'label' => $this->demo('launchpad.expected_top_9e3750848f')],
                    ['value' => '[Unit Count]', 'label' => $this->demo('spotlight.residences_139374b4f5')],
                ],
                'columns' => '4',
                'background' => 'bg-base-200',
                'padding' => 'py-24',
            ]),
            $this->demoBlock('features', [
                'heading' => $this->demo('spotlight.the_residences_5f218ee12c'),
                'subheading' => $this->demo('spotlight.each_floorplan_is_considered_not_just_co_68e6dca3dd'),
                'features' => [
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-home', 'title_key' => 'launchpad.1_bedroom_a0c895ac49', 'description' => '[XXX–XXX sqft] · From $[X.XX]M'],
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-home-modern', 'title_key' => 'launchpad.2_bedroom_d0853937ca', 'description' => '[XXX–XXX sqft] · From $[X.XX]M'],
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-building-office-2', 'title_key' => 'launchpad.3_bedroom_bdb23bc861', 'description' => '[XXXX–XXXX sqft] · From $[X.XX]M'],
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-sparkles', 'title_key' => 'launchpad.penthouse_4ca9996817', 'description' => '[XXXX+ sqft] · Upon application'],
                ],
                'columns' => '4',
                'background' => 'bg-base-100',
                'padding' => 'py-24',
            ]),
            $this->demoBlock('features', [
                'heading' => $this->demo('spotlight.the_day_to_day_7da8ab94d6'),
                'subheading' => $this->demo('spotlight.amenities_designed_for_how_residents_act_bf2c5cfc98'),
                'features' => [
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-sun', 'title_key' => 'launchpad.50m_lap_pool_5c37478700', 'description' => $this->demo('spotlight.full_length_pool_with_timber_deck_and_pr_661487f62f')],
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-bolt', 'title_key' => 'spotlight.gym_wellness_8ea1bd1262', 'description' => 'Fully-equipped gym and dedicated yoga / stretch room. Residents-only, 24 hours.'],
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-fire', 'title_key' => 'spotlight.dining_pavilions_d3fb429065', 'description' => $this->demo('spotlight.bookable_pavilions_for_gatherings_with_p_790cb2e194')],
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-building-library', 'title_key' => 'spotlight.residents_club_f84f021def', 'description' => 'Clubhouse with lounge, library nook, and private event space.'],
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-cloud', 'title_key' => 'launchpad.sky_garden_3ba59cd6b1', 'description' => "Rooftop garden with panoramic views — the development's quiet centerpiece."],
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-user-group', 'title_key' => 'spotlight.children_s_play_ae6efd962a', 'description' => 'Thoughtfully-designed play area, shaded and safety-rated.'],
                ],
                'columns' => '3',
                'background' => 'bg-base-200',
                'padding' => 'py-24',
            ]),
            $this->demoBlock('content_block', [
                'title_key' => 'launchpad.location_neighborhood_name_72de06cc43',
                'body' => $this->demo('spotlight.p_neighborhood_name_is_a_single_evocati_9b3c8ec34b'),
                'background' => 'bg-base-100',
                'padding' => 'py-24',
            ]),
            // Requires the TallCMS Pro plugin. Swap coords/address/marker_title.
            $this->demoBlock('pro-map', [
                'heading' => $this->demo('spotlight.the_site_a500256ebd'),
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
                'padding' => 'py-24',
            ]),
            $this->demoBlock('stats', [
                'heading' => $this->demo('spotlight.within_reach_fd177de3bb'),
                'stats' => [
                    ['value' => '[X] min', 'label' => $this->demo('spotlight.walk_to_mrt_f65adf05c1')],
                    ['value' => '[X] min', 'label' => $this->demo('spotlight.drive_to_cbd_96a21fb1db')],
                    ['value' => '[X]', 'label' => $this->demo('launchpad.top_schools_within_2km_8e84079e04')],
                    ['value' => '[X] min', 'label' => $this->demo('spotlight.to_changi_airport_e0d2aee36a')],
                ],
                'columns' => '4',
                'background' => 'bg-base-100',
                'padding' => 'py-24',
            ]),
            $this->demoBlock('content_block', [
                'title_key' => 'spotlight.about_developer_facea923b9',
                'body' => $this->demo('spotlight.p_strong_developer_strong_has_been_shap_6a87723a3b'),
                'background' => 'bg-base-200',
                'padding' => 'py-24',
            ]),
            $this->demoBlock('cta', [
                'title_key' => 'spotlight.ready_to_view_25a00a9878',
                'description' => 'Showflat previews are by appointment. WhatsApp us directly for the earliest available slot.',
                'button_text' => $this->demo('spotlight.whatsapp_to_book_f93e96ab26'),
                'button_link_type' => 'external',
                'button_url' => 'https://wa.me/659999999?text=Hi%20I%20would%20like%20to%20book%20a%20showflat%20preview%20for%20[Project%20Name]',
                'button_microcopy' => 'Replace 659999999 with your phone and update the project name in the text= parameter.',
                'button_variant' => 'btn-success',
                'button_size' => 'btn-lg',
                'background' => 'bg-neutral',
                'padding' => 'py-24',
            ]),
            $this->demoBlock('content_block', [
                'title_key' => 'launchpad.disclaimer_4c4a2e80cc',
                'body' => $this->demo('spotlight.p_style_font_size_0_9em_color_666_this_ed1c265211'),
                'background' => 'bg-base-200',
                'padding' => 'py-16',
            ]),
        ]);
    }

    protected function thankYouContent(): string
    {
        return implode("\n", [
            $this->demoBlock('hero', [
                'heading' => $this->demo('launchpad.p_thank_you_p_ea2fd536da'),
                'subheading' => $this->demo('spotlight.p_your_details_are_in_we_ll_send_the_br_f52d44aba1'),
                'button_text' => $this->demo('spotlight.back_to_the_residence_92ed948b35'),
                'button_link_type' => 'page',
                'layout' => 'centered',
                'height' => 'min-h-[60vh]',
                'text_alignment' => 'text-center',
                'background_color' => 'bg-gradient-to-br from-neutral to-base-300',
                'overlay_opacity' => 0,
            ]),
            $this->demoBlock('content_block', [
                'title_key' => 'launchpad.what_happens_next_51ecc5b21f',
                'body' => $this->demo('spotlight.p_1_check_your_inbox_the_brochure_is_on_e31c8566df'),
                'background' => 'bg-base-100',
                'padding' => 'py-24',
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
                'body' => $this->demo('spotlight.p_this_website_is_owned_and_operated_by_b3f2674240'),
                'background' => 'bg-base-100',
                'padding' => 'py-24',
            ]),
        ]);
    }
}
