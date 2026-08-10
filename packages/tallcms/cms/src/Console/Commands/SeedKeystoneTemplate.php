<?php

declare(strict_types=1);

namespace TallCms\Cms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use TallCms\Cms\Console\Commands\Concerns\TranslatesDemoTemplateStrings;

/**
 * Seed the "Keystone" template — a professional realtor starter site.
 *
 * Creates an is_template_source=true site with a home/about/insights/contact
 * page set, three seed blog posts, and a primary menu. Appears in the
 * Template Gallery for SaaS users to clone and rename. Safe to run
 * repeatedly — skips if a site with the Keystone slug already exists.
 *
 * Scope: professional website for realtors. No property-listing integration
 * (that's the future real-estate plugin). The template covers the generic
 * "I'm a trusted property advisor — here's my bio + services + some
 * insights + contact me" structure that every realtor needs.
 *
 * Copy uses bracketed placeholders ([Agent Name], [City], [Year], etc.) so
 * agents can search-and-replace after cloning.
 */
class SeedKeystoneTemplate extends Command
{
    use TranslatesDemoTemplateStrings;

    protected $signature = 'tallcms:seed-keystone-template
                            {--owner= : User ID to own the template site (defaults to first super_admin)}
                            {--force : Delete any existing Keystone template and recreate}';

    protected $description = 'Seed the Keystone realtor template site (home, about, insights, contact + 3 seed posts)';

    public function handle(): int
    {
        if (! Schema::hasTable('tallcms_sites')) {
            $this->error(__('tallcms::console.seed_template.sites_table_missing'));

            return self::FAILURE;
        }

        if (! Schema::hasColumn('tallcms_sites', 'is_template_source')) {
            $this->error(__('tallcms::console.seed_template.template_source_column_missing'));

            return self::FAILURE;
        }

        $ownerId = $this->resolveOwnerId();
        if (! $ownerId) {
            $this->error(__('tallcms::console.seed_template.no_owner'));

            return self::FAILURE;
        }

        $existing = DB::table('tallcms_sites')->where('domain', 'keystone.template')->first();
        if ($existing) {
            if (! $this->option('force')) {
                $this->components->warn(__('tallcms::console.seed_template.already_exists', ['template' => 'Keystone', 'id' => $existing->id]));

                return self::SUCCESS;
            }
            $this->deleteSite((int) $existing->id);
            $this->components->info(__('tallcms::console.seed_template.removed', ['template' => 'Keystone']));
        }

        $siteId = $this->createSite($ownerId);
        $this->components->info(__('tallcms::console.seed_template.created_site', ['template' => 'Keystone', 'id' => $siteId]));

        $pageIds = $this->createPages($siteId, $ownerId);
        $this->components->info(__('tallcms::console.seed_template.created_pages', ['count' => count($pageIds)]));

        $this->createMenu($siteId, $pageIds);
        $this->components->info(__('tallcms::console.seed_template.created_menu'));

        $this->createPosts($siteId, $ownerId);
        $this->components->info(__('tallcms::console.seed_template.created_posts', ['count' => 3, 'type' => __('tallcms::console.seed_template.post_type_insight')]));

        $this->newLine();
        $this->components->info(__('tallcms::console.seed_template.ready', ['emoji' => '🏠', 'template' => 'Keystone', 'message' => __('tallcms::console.seed_template.ready_gallery')]));

        return self::SUCCESS;
    }

    protected function resolveOwnerId(): ?int
    {
        if ($owner = $this->option('owner')) {
            return (int) $owner;
        }

        // First super_admin
        $superAdminId = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('roles.name', 'super_admin')
            ->value('model_has_roles.model_id');

        if ($superAdminId) {
            return (int) $superAdminId;
        }

        // Fallback: first user
        return DB::table('users')->orderBy('id')->value('id');
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
            'name' => $this->demo('keystone.keystone_a8a633c9d3'),
            'domain' => 'keystone.template',
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
            'about' => [
                'title' => $this->demoJson('menu.about'),
                'content' => $this->aboutContent(),
            ],
            'insights' => [
                'title' => $this->demoJson('menu.insights'),
                'content' => $this->insightsContent(),
            ],
            'contact' => [
                'title' => $this->demoJson('menu.contact'),
                'content' => $this->contactContent(),
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
        foreach (['home' => 'menu.home', 'about' => 'menu.about', 'insights' => 'menu.insights', 'contact' => 'menu.contact'] as $slug => $labelKey) {
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
                'title' => "A first-time buyer's guide to [City]",
                'slug' => 'first-time-buyer-guide',
                'excerpt' => 'Everything a first-time buyer in [City] needs to know — from pre-approval to keys-in-hand. A practical, step-by-step walkthrough.',
                'content' => $this->firstTimeBuyerPost(),
            ],
            [
                'title' => "What's happening in the [City] property market — [Year Quarter]",
                'slug' => 'market-update',
                'excerpt' => "Median prices, days-on-market, neighborhood movers. A quarterly read of what's actually selling and for how much.",
                'content' => $this->marketUpdatePost(),
            ],
            [
                'title_key' => 'keystone.10_things_i_tell_every_client_who_is_sel_c6accbdcc5',
                'slug' => 'selling-your-home-tips',
                'excerpt' => 'After 15 years and 300+ transactions, these are the 10 principles that have consistently helped my clients sell faster and for more.',
                'content' => $this->sellingTipsPost(),
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

    /**
     * Emit a custom block as TipTap content HTML.
     */

    /**
     * Emit a paragraph node (TipTap renders it as a <p> when placed between blocks).
     */
    protected function paragraph(string $text): string
    {
        return '<p>'.htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8').'</p>';
    }

    // --- Page contents ------------------------------------------------------

    protected function homeContent(): string
    {
        return implode("\n", [
            $this->demoBlock('hero', [
                'heading' => $this->demo('keystone.p_hi_i_m_agent_name_p_0cdde208bb'),
                'subheading' => $this->demo('keystone.p_helping_families_buy_sell_and_rent_in_fe372f5ec5'),
                'button_text' => $this->demo('keystone.book_a_consultation_fed8ca77df'),
                'button_link_type' => 'custom',
                'button_url' => '#contact',
                'secondary_button_text' => $this->demo('keystone.about_me_e3ba4ef34d'),
                'secondary_button_link_type' => 'page',
                'layout' => 'centered',
                'height' => 'min-h-[70vh]',
                'text_alignment' => 'text-center',
                'background_color' => 'bg-gradient-to-br from-primary to-secondary',
                'overlay_opacity' => 0,
                'button_variant' => 'btn-primary',
                'secondary_button_variant' => 'btn-ghost text-white hover:bg-white/20',
                'button_size' => 'btn-lg',
            ]),
            $this->demoBlock('stats', [
                'heading' => $this->demo('keystone.a_track_record_built_one_home_at_a_time_f857fa4132'),
                'stats' => [
                    ['value' => '300+', 'label' => $this->demo('keystone.transactions_closed_86270ee912')],
                    ['value' => '$200M', 'label' => $this->demo('keystone.in_property_sold_7d5159a636')],
                    ['value' => '15', 'label' => $this->demo('keystone.years_licensed_9a8942f908')],
                    ['value' => '4.9/5', 'label' => $this->demo('keystone.client_rating_06453d7ba3')],
                ],
                'columns' => '4',
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
            $this->demoBlock('features', [
                'heading' => $this->demo('keystone.how_i_can_help_53f0e244d8'),
                'subheading' => $this->demo('keystone.three_services_one_advisor_straightforwa_c2391c4d9d'),
                'features' => [
                    [
                        'icon_type' => 'heroicon',
                        'icon' => 'heroicon-o-home',
                        'title_key' => 'keystone.selling_your_home_e1e52561d9',
                        'description' => $this->demo('keystone.pricing_strategy_staging_advice_listing_6a40fbee8f'),
                    ],
                    [
                        'icon_type' => 'heroicon',
                        'icon' => 'heroicon-o-key',
                        'title_key' => 'keystone.buying_your_home_13bee28533',
                        'description' => "Shortlisting that respects your budget, honest neighborhood walk-throughs, and negotiations on your behalf — not the seller's.",
                    ],
                    [
                        'icon_type' => 'heroicon',
                        'icon' => 'heroicon-o-building-office',
                        'title_key' => 'keystone.rental_advisory_ae954cc0ec',
                        'description' => $this->demo('keystone.for_landlords_and_tenants_tenant_screeni_d7ecc4d6f7'),
                    ],
                ],
                'columns' => '3',
                'background' => 'bg-base-200',
                'padding' => 'py-16',
            ]),
            $this->demoBlock('content_block', [
                'title_key' => 'keystone.a_little_about_me_a3f3058aa6',
                'body' => $this->demo('keystone.p_i_grew_up_in_city_and_have_watched_it_cbe89bafee'),
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
            $this->demoBlock('testimonials', [
                'heading' => $this->demo('counsel.what_clients_say_657ded8794'),
                'testimonials' => [
                    [
                        'quote' => "[Agent Name] made our first home purchase feel manageable. We were clueless; she was patient and honest — including about places we shouldn't buy.",
                        'author_name' => 'The Chen family',
                        'author_title' => 'First-time buyers, 2024',
                    ],
                    [
                        'quote' => "Sold our place in 11 days, $30k above asking. The pricing and staging advice were spot-on. We'd recommend her to anyone.",
                        'author_name' => 'Sarah & Michael',
                        'author_title' => 'Condo sellers, 2024',
                    ],
                    [
                        'quote' => 'Straightforward, fast to respond, and refused to let us overpay. That last one is rare in this industry.',
                        'author_name' => 'David L.',
                        'author_title' => 'HDB upgrader, 2023',
                    ],
                ],
                'columns' => '3',
                'background' => 'bg-base-200',
                'padding' => 'py-16',
            ]),
            $this->demoBlock('posts', [
                'posts_count' => 3,
                'show_image' => true,
                'show_excerpt' => true,
                'show_date' => true,
                'show_author' => false,
                'show_categories' => false,
                'show_read_more' => true,
                'layout' => 'grid',
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
            $this->demoBlock('faq', [
                'heading' => $this->demo('keystone.frequently_asked_0b8a19ed98'),
                'items' => [
                    ['question' => 'How do you price a property?', 'answer' => "I combine recent comparable sales, current listings in the same neighborhood, and market conditions. For sellers, I provide a written pricing analysis before we list. For buyers, I'll tell you if a place is priced above market before you make an offer."],
                    ['question' => 'What areas do you cover?', 'answer' => "Primarily [City] and its neighboring districts. I don't take clients outside my area of expertise — you're better served by an agent who knows the streets."],
                    ['question' => "What's your commission?", 'answer' => 'Standard for the market, transparent, and discussed upfront. No hidden fees, no surprise charges at closing.'],
                    ['question' => 'How do we start?', 'answer' => "Book a 30-minute consultation through the form below. No commitment — we'll talk about what you're trying to do and whether I'm the right fit."],
                ],
                'background' => 'bg-base-200',
                'padding' => 'py-16',
            ]),
            $this->demoBlock('contact_form', [
                'title' => "Let's talk",
                'description' => "Tell me what you're thinking and I'll be in touch within 24 hours. No hard sells, no mailing list tricks.",
                'anchor_id' => 'contact',
                'fields' => [
                    ['name' => $this->demo('counsel.name_6ae999552a'), 'type' => 'text', 'label' => $this->demo('ink.your_name_ab42293e29'), 'required' => true, 'options' => []],
                    ['name' => $this->demo('counsel.email_a88b7dcd1a'), 'type' => 'email', 'label' => $this->demo('counsel.email_84add5b295'), 'required' => true, 'options' => []],
                    ['name' => $this->demo('counsel.phone_f6be6ca910'), 'type' => 'tel', 'label' => $this->demo('counsel.phone_77064d5265'), 'required' => false, 'options' => []],
                    ['name' => $this->demo('ink.intent_e320f70e48'), 'type' => 'select', 'label' => "I'm looking to", 'required' => true, 'options' => ['Buy', 'Sell', 'Rent', 'Just exploring']],
                    ['name' => $this->demo('counsel.message_6f9b9af3cd'), 'type' => 'textarea', 'label' => $this->demo('keystone.a_bit_more_about_your_situation_af171a55a6'), 'required' => true, 'options' => []],
                ],
                'submit_button_text' => $this->demo('keystone.send_message_c70a890d14'),
                'success_message' => $this->demo('keystone.thanks_i_ll_be_in_touch_within_24_hours_0c52aee141'),
                'auto_reply_message' => $this->demo('keystone.hi_thanks_for_reaching_out_i_ve_got_your_23b85ae60c'),
                'button_style' => 'btn-primary',
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
        ]);
    }

    protected function aboutContent(): string
    {
        return implode("\n", [
            $this->demoBlock('hero', [
                'heading' => $this->demo('keystone.p_about_agent_name_p_ea37e1366b'),
                'subheading' => $this->demo('keystone.p_licensed_since_year_based_in_city_fam_355c4f4cd7'),
                'layout' => 'centered',
                'height' => 'min-h-[40vh]',
                'text_alignment' => 'text-center',
                'background_color' => 'bg-base-200',
                'overlay_opacity' => 0,
            ]),
            $this->demoBlock('content_block', [
                'title_key' => 'keystone.my_story_fda78037d9',
                'body' => $this->demo('keystone.p_i_got_into_real_estate_by_accident_i_af78a146ea'),
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
            $this->demoBlock('features', [
                'heading' => $this->demo('keystone.credentials_dd097a2297'),
                'features' => [
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-academic-cap', 'title_key' => 'keystone.cea_licensed_2f33185922', 'description' => $this->demo('keystone.license_registered_with_the_council_for_ee513eed26')],
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-building-office-2', 'title_key' => 'keystone.agency_5c47e26c6a', 'description' => "Associated with [Agency Name], one of [City]'s established property firms."],
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-trophy', 'title_key' => 'keystone.recognition_343bc89c03', 'description' => $this->demo('keystone.top_producer_year_and_year_platinum_circ_df19093093')],
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-chart-bar', 'title_key' => 'keystone.specializations_e6e6a1cdf4', 'description' => $this->demo('keystone.hdb_resale_private_condo_rental_advisory_e38e8fdd14')],
                ],
                'columns' => '2',
                'background' => 'bg-base-200',
                'padding' => 'py-16',
            ]),
            $this->demoBlock('cta', [
                'title' => "Let's talk about your move",
                'description' => "I'll give you a straight answer on what your place is worth, or what you can realistically afford — no pressure, no commitment.",
                'button_text' => $this->demo('keystone.start_the_conversation_22851118af'),
                'button_link_type' => 'page',
                'background' => 'bg-primary',
                'padding' => 'py-16',
                'text_color' => 'text-white',
            ]),
        ]);
    }

    protected function insightsContent(): string
    {
        return implode("\n", [
            $this->demoBlock('hero', [
                'heading' => $this->demo('keystone.p_property_insights_p_2489cbc6a1'),
                'subheading' => $this->demo('keystone.p_market_commentary_buying_and_selling_4cc87b946b'),
                'layout' => 'centered',
                'height' => 'min-h-[40vh]',
                'text_alignment' => 'text-center',
                'background_color' => 'bg-base-200',
                'overlay_opacity' => 0,
            ]),
            $this->demoBlock('posts', [
                'posts_count' => 12,
                'show_image' => true,
                'show_excerpt' => true,
                'show_date' => true,
                'show_author' => false,
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
                'heading' => $this->demo('keystone.p_let_s_talk_p_95278977f4'),
                'subheading' => $this->demo('keystone.p_reach_out_and_i_will_respond_personal_c0804b0fc8'),
                'layout' => 'centered',
                'height' => 'min-h-[40vh]',
                'text_alignment' => 'text-center',
                'background_color' => 'bg-base-200',
                'overlay_opacity' => 0,
            ]),
            $this->demoBlock('content_block', [
                'title_key' => 'keystone.how_to_reach_me_e541789643',
                'body' => $this->demo('keystone.p_strong_phone_whatsapp_strong_phone_br_2e6ae1f321'),
                'background' => 'bg-base-100',
                'padding' => 'py-12',
            ]),
            $this->demoBlock('contact_form', [
                'title_key' => 'keystone.send_me_a_note_4ab6c2e898',
                'fields' => [
                    ['name' => $this->demo('counsel.name_6ae999552a'), 'type' => 'text', 'label' => $this->demo('ink.your_name_ab42293e29'), 'required' => true, 'options' => []],
                    ['name' => $this->demo('counsel.email_a88b7dcd1a'), 'type' => 'email', 'label' => $this->demo('counsel.email_84add5b295'), 'required' => true, 'options' => []],
                    ['name' => $this->demo('counsel.phone_f6be6ca910'), 'type' => 'tel', 'label' => $this->demo('counsel.phone_77064d5265'), 'required' => false, 'options' => []],
                    ['name' => $this->demo('ink.intent_e320f70e48'), 'type' => 'select', 'label' => "I'm looking to", 'required' => true, 'options' => ['Buy', 'Sell', 'Rent', 'Just exploring']],
                    ['name' => $this->demo('counsel.message_6f9b9af3cd'), 'type' => 'textarea', 'label' => $this->demo('keystone.your_message_f190c98ece'), 'required' => true, 'options' => []],
                ],
                'submit_button_text' => $this->demo('keystone.send_message_c70a890d14'),
                'success_message' => $this->demo('keystone.thanks_i_ll_be_in_touch_within_24_hours_5d61d966d7'),
                'auto_reply_message' => $this->demo('keystone.hi_thanks_for_reaching_out_i_ve_received_c780ffce3a'),
                'button_style' => 'btn-primary',
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
        ]);
    }

    // --- Post bodies --------------------------------------------------------

    protected function firstTimeBuyerPost(): string
    {
        return implode("\n", [
            $this->paragraph("Buying your first home is one of the largest financial decisions you'll make, and the process is full of jargon and moving parts. Here's the 8-step path that works for most first-time buyers I've worked with in [City]."),
            $this->paragraph("1. Get pre-approved before you shop. Lenders will tell you exactly how much you can borrow. Without this, you're window-shopping. Budget 1-2 weeks."),
            $this->paragraph('2. Nail down your non-negotiables. Three things: location (be specific about neighborhoods), size (bedroom count is usually the clearest filter), and budget ceiling. Everything else is trade-offs.'),
            $this->paragraph('3. Shortlist with an agent you trust. A good agent saves you weeks of viewing bad fits. Ask for 3 listings that match your criteria within 48 hours.'),
            $this->paragraph("4. Walk through 5-8 properties maximum. More than that and you lose the ability to compare. Take photos, take notes, take your partner's temperature."),
            $this->paragraph("5. Make a first offer 5-10% below asking for an opening position. Unless it's a hot unit with multiple bids — then the game is different and your agent should coach you through it."),
            $this->paragraph('6. Negotiate. This is where the agent earns their commission. Counter-offers, contingencies, closing timeline, inclusions — all on the table.'),
            $this->paragraph('7. Close. Legal paperwork, final inspection, key handover. Your lawyer does most of this; you show up at a few meetings.'),
            $this->paragraph("8. Move in and breathe. The hard part's over. The hard part's over."),
            $this->paragraph("Questions? The easiest way to reach me is the contact form on the home page — I'll personally respond within 24 hours."),
        ]);
    }

    protected function marketUpdatePost(): string
    {
        return implode("\n", [
            $this->paragraph("Every quarter I write a short commentary on what's actually moving in [City]'s property market — not the headlines, the street-level reality. This edition covers [Year Quarter]."),
            $this->paragraph('<strong>Median prices:</strong> Resale HDB held steady quarter-over-quarter; private condos up a modest [X]% on thin volume; new launches continue to attract first-timer demand.'),
            $this->paragraph('<strong>Days on market:</strong> Well-priced listings are moving in 2-4 weeks. Anything above the comparable-sales range sits for 60+ days and eventually discounts to clear.'),
            $this->paragraph('<strong>Neighborhood movers:</strong> [Neighborhood A] saw stronger buyer interest as the new MRT station approached operational date; [Neighborhood B] inventory thinned after several large developments completed.'),
            $this->paragraph("<strong>What it means for sellers:</strong> Price realistically. The market is rewarding sellers who list at fair value and penalizing those who chase last year's peak."),
            $this->paragraph('<strong>What it means for buyers:</strong> Shortlisting matters more than speed. There are good units; there are also overpriced ones sitting unsold. Your agent should know which is which.'),
            $this->paragraph("Reach out if you want the numbers for a specific development or neighborhood — I'm happy to share what I'm seeing on the ground."),
        ]);
    }

    protected function sellingTipsPost(): string
    {
        return implode("\n", [
            $this->paragraph('After 300+ transactions, these are the 10 things I find myself saying to every seller. Not original — but consistently true.'),
            $this->paragraph('<strong>1. Price is 80% of the game.</strong> Everything else — staging, listing copy, marketing — matters at the margin. Price matters first.'),
            $this->paragraph('<strong>2. List at market, not at hope.</strong> Overpriced listings get stale fast and sell for less than a correctly-priced listing would have.'),
            $this->paragraph('<strong>3. Declutter before you stage.</strong> Buyers need to imagine themselves living there. Your stuff is in the way of that.'),
            $this->paragraph("<strong>4. Clean like your mother-in-law is coming.</strong> Not kidding. Professional clean, including windows. It's the cheapest ROI in the entire sale."),
            $this->paragraph('<strong>5. Photos are everything online.</strong> Invest in a professional photographer. Buyers decide whether to view your place from the listing photos.'),
            $this->paragraph('<strong>6. Price rounds to the nearest K.</strong> No $847,250 listing prices. It signals you care more about a spreadsheet than about selling.'),
            $this->paragraph("<strong>7. Don't over-renovate.</strong> The $30k kitchen upgrade rarely returns $30k at sale. Fix the obvious, leave the rest."),
            $this->paragraph("<strong>8. Accept feedback.</strong> If three viewings all mention the same thing, it's the thing. Listen to the market."),
            $this->paragraph('<strong>9. First offer is often the best offer.</strong> The hot-market instinct to wait for higher rarely pays off.'),
            $this->paragraph('<strong>10. Hire an agent who tells you the truth.</strong> Not the agent who promises the highest price — the one who tells you what the market will actually bear.'),
            $this->paragraph("Thinking about selling? Reach out — the consultation is free and I'll give you a realistic read on your place in 30 minutes."),
        ]);
    }
}
