<?php

declare(strict_types=1);

namespace TallCms\Cms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use TallCms\Cms\Console\Commands\Concerns\TranslatesDemoTemplateStrings;

/**
 * Seed the "Ink" template — a blog starter for writers, essayists,
 * and thought-leadership-focused creators.
 *
 * Differs from Keystone (realtor) by being content-first rather than
 * service-first: no stats, no testimonials, no FAQ. Home page centers
 * the post feed and a newsletter CTA. Seeded with 5 posts so the
 * cloned site feels populated out of the box.
 *
 * Scope: a professional-looking blog — personal, opinion, essays,
 * tech writing, newsletter companion sites. Not optimized for
 * e-commerce, portfolios, or multi-author publications (those get
 * their own templates eventually).
 *
 * Copy uses bracketed placeholders ([Your Name], [Topic], etc.) so
 * authors search-and-replace after cloning.
 */
class SeedInkTemplate extends Command
{
    use TranslatesDemoTemplateStrings;

    protected $signature = 'tallcms:seed-ink-template
                            {--owner= : User ID to own the template site (defaults to first super_admin)}
                            {--force : Delete any existing Ink template and recreate}';

    protected $description = 'Seed the Ink blog template site (home, about, archive, contact + 5 seed posts)';

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

        $existing = DB::table('tallcms_sites')->where('domain', 'ink.template')->first();
        if ($existing) {
            if (! $this->option('force')) {
                $this->components->warn(__('tallcms::console.seed_template.already_exists', ['template' => 'Ink', 'id' => $existing->id]));

                return self::SUCCESS;
            }
            $this->deleteSite((int) $existing->id);
            $this->components->info(__('tallcms::console.seed_template.removed', ['template' => 'Ink']));
        }

        $siteId = $this->createSite($ownerId);
        $this->components->info(__('tallcms::console.seed_template.created_site', ['template' => 'Ink', 'id' => $siteId]));

        $pageIds = $this->createPages($siteId, $ownerId);
        $this->components->info(__('tallcms::console.seed_template.created_pages', ['count' => count($pageIds)]));

        $this->createMenu($siteId, $pageIds);
        $this->components->info(__('tallcms::console.seed_template.created_menu'));

        $this->createPosts($siteId, $ownerId);
        $this->components->info(__('tallcms::console.seed_template.created_posts', ['count' => 5, 'type' => __('tallcms::console.seed_template.post_type_default')]));

        $this->newLine();
        $this->components->info(__('tallcms::console.seed_template.ready', ['emoji' => '✍️', 'template' => 'Ink', 'message' => __('tallcms::console.seed_template.ready_gallery')]));

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
            'name' => $this->demo('ink.ink_0480691e0d'),
            'domain' => 'ink.template',
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
            'archive' => [
                'title' => $this->demoJson('menu.archive'),
                'content' => $this->archiveContent(),
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
        foreach (['home' => 'menu.home', 'about' => 'menu.about', 'archive' => 'menu.archive', 'contact' => 'menu.contact'] as $slug => $labelKey) {
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
                'title_key' => 'ink.welcome_what_this_blog_is_about_755b6f8aa5',
                'slug' => 'welcome',
                'excerpt_key' => 'ink.a_quick_intro_to_what_you_ll_find_here_w_56d6340a2f',
                'content' => $this->welcomePost(),
            ],
            [
                'title_key' => 'ink.the_reading_list_i_return_to_88fcf8288a',
                'slug' => 'reading-list',
                'excerpt_key' => 'ink.a_running_list_of_the_books_and_essays_t_d98b8c9d14',
                'content' => $this->readingListPost(),
            ],
            [
                'title_key' => 'ink.on_thinking_in_systems_85b432b934',
                'slug' => 'thinking-in-systems',
                'excerpt_key' => 'ink.why_i_try_to_reason_about_systems_not_sy_8309ed5634',
                'content' => $this->systemsPost(),
            ],
            [
                'title_key' => 'ink.a_simpler_writing_process_5ac15e0d5c',
                'slug' => 'writing-process',
                'excerpt_key' => 'ink.after_a_decade_of_trying_fancier_systems_ed44c0bd7c',
                'content' => $this->writingProcessPost(),
            ],
            [
                'title_key' => 'ink.what_i_got_wrong_about_productivity_b629632154',
                'slug' => 'productivity-wrong',
                'excerpt_key' => 'ink.five_productivity_beliefs_i_held_firmly_e23e462189',
                'content' => $this->productivityPost(),
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
                'published_at' => now()->subDays($i * 10),
                'created_at' => now()->subDays($i * 10),
                'updated_at' => now()->subDays($i * 10),
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
                'heading' => $this->demo('ink.p_i_write_about_topic_p_187654f870'),
                'subheading' => $this->demo('ink.p_essays_field_notes_and_occasional_lon_0e106f7280'),
                'button_text' => $this->demo('ink.subscribe_d6981f7476'),
                'button_link_type' => 'custom',
                'button_url' => '#subscribe',
                'secondary_button_text' => $this->demo('ink.read_recent_posts_b9dd7808d1'),
                'secondary_button_link_type' => 'custom',
                'secondary_button_url' => '#recent',
                'layout' => 'centered',
                'height' => 'min-h-[60vh]',
                'text_alignment' => 'text-center',
                'background_color' => 'bg-gradient-to-br from-neutral to-base-300',
                'overlay_opacity' => 0,
                'button_variant' => 'btn-primary',
                'secondary_button_variant' => 'btn-ghost text-white hover:bg-white/20',
                'button_size' => 'btn-lg',
            ]),
            $this->demoBlock('posts', [
                'posts_count' => 6,
                'show_image' => true,
                'show_excerpt' => true,
                'show_date' => true,
                'show_author' => false,
                'show_categories' => false,
                'show_read_more' => true,
                'layout' => 'grid',
                'background' => 'bg-base-100',
                'padding' => 'py-16',
                'anchor_id' => 'recent',
            ]),
            $this->demoBlock('content_block', [
                'title_key' => 'ink.about_the_writer_56d9af4853',
                'body' => $this->demo('ink.p_i_m_your_name_a_role_short_descriptor_d8e6ab3550'),
                'background' => 'bg-base-200',
                'padding' => 'py-16',
            ]),
            $this->demoBlock('cta', [
                'title_key' => 'ink.get_new_posts_in_your_inbox_46ef30eb39',
                'description' => $this->demo('ink.no_paywalls_no_tracking_no_algorithm_eve_a0bfbbf781'),
                'button_text' => $this->demo('ink.subscribe_d6981f7476'),
                'button_link_type' => 'url',
                'button_url' => '#',
                'button_microcopy' => 'Swap this link for your newsletter provider (Buttondown, ConvertKit, Substack, etc.)',
                'button_variant' => 'btn-primary',
                'button_size' => 'btn-lg',
                'background' => 'bg-primary',
                'padding' => 'py-16',
                'anchor_id' => 'subscribe',
            ]),
        ]);
    }

    protected function aboutContent(): string
    {
        return implode("\n", [
            $this->demoBlock('hero', [
                'heading' => $this->demo('ink.p_about_your_name_p_183b4029a7'),
                'subheading' => $this->demo('ink.p_writer_reader_occasional_rambler_here_5b53ee225e'),
                'layout' => 'centered',
                'height' => 'min-h-[40vh]',
                'text_alignment' => 'text-center',
                'background_color' => 'bg-base-200',
                'overlay_opacity' => 0,
            ]),
            $this->demoBlock('content_block', [
                'title_key' => 'ink.hi_i_am_your_name_9c7e26bd43',
                'body' => $this->demo('ink.p_i_m_a_role_short_descriptor_based_in_e386473b0f'),
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
            $this->demoBlock('features', [
                'heading' => $this->demo('ink.what_i_write_about_b34c27bec0'),
                'features' => [
                    [
                        'icon_type' => 'heroicon',
                        'icon' => 'heroicon-o-light-bulb',
                        'title_key' => 'ink.topic_1_16864ad8c6',
                        'description' => $this->demo('ink.a_short_description_of_the_topic_and_why_2e1f6847e2'),
                    ],
                    [
                        'icon_type' => 'heroicon',
                        'icon' => 'heroicon-o-book-open',
                        'title_key' => 'ink.topic_2_320b8cdb94',
                        'description' => 'Another topic you regularly return to. Readers come here for this too; say what the angle is.',
                    ],
                    [
                        'icon_type' => 'heroicon',
                        'icon' => 'heroicon-o-map',
                        'title_key' => 'ink.topic_3_52a793bd6a',
                        'description' => $this->demo('ink.your_third_recurring_theme_optional_dele_91b37e5e7c'),
                    ],
                ],
                'columns' => '3',
                'background' => 'bg-base-200',
                'padding' => 'py-16',
            ]),
            $this->demoBlock('cta', [
                'title_key' => 'ink.stay_in_the_loop_f54254af0c',
                'description' => 'Get new posts in your inbox — or follow along wherever you prefer.',
                'button_text' => $this->demo('ink.subscribe_d6981f7476'),
                'button_link_type' => 'url',
                'button_url' => '#',
                'button_microcopy' => 'Swap this link for your newsletter signup URL',
                'button_variant' => 'btn-primary',
                'background' => 'bg-primary',
                'padding' => 'py-16',
            ]),
        ]);
    }

    protected function archiveContent(): string
    {
        return implode("\n", [
            $this->demoBlock('hero', [
                'heading' => $this->demo('ink.p_archive_p_34091e8432'),
                'subheading' => $this->demo('ink.p_every_post_newest_first_p_2ee39fdb9c'),
                'layout' => 'centered',
                'height' => 'min-h-[35vh]',
                'text_alignment' => 'text-center',
                'background_color' => 'bg-base-200',
                'overlay_opacity' => 0,
            ]),
            $this->demoBlock('posts', [
                'posts_count' => 20,
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
                'heading' => $this->demo('ink.p_get_in_touch_p_cdcac55e03'),
                'subheading' => $this->demo('ink.p_reader_questions_speaking_invitations_31eaa34164'),
                'layout' => 'centered',
                'height' => 'min-h-[35vh]',
                'text_alignment' => 'text-center',
                'background_color' => 'bg-base-200',
                'overlay_opacity' => 0,
            ]),
            $this->demoBlock('content_block', [
                'title_key' => 'ink.reach_out_ebfeac3759',
                'body' => $this->demo('ink.p_the_fastest_way_to_get_in_touch_is_th_a507b01aa0'),
                'background' => 'bg-base-100',
                'padding' => 'py-12',
            ]),
            $this->demoBlock('contact_form', [
                'title_key' => 'ink.send_a_note_353063282a',
                'fields' => [
                    ['name' => $this->demo('counsel.name_6ae999552a'), 'type' => 'text', 'label' => $this->demo('ink.your_name_ab42293e29'), 'required' => true, 'options' => []],
                    ['name' => $this->demo('counsel.email_a88b7dcd1a'), 'type' => 'email', 'label' => $this->demo('counsel.email_84add5b295'), 'required' => true, 'options' => []],
                    ['name' => $this->demo('ink.intent_e320f70e48'), 'type' => 'select', 'label' => "What's this about?", 'required' => true, 'options' => ['Reader question', 'Speaking invitation', 'Collaboration', 'Just saying hi', 'Other']],
                    ['name' => $this->demo('counsel.message_6f9b9af3cd'), 'type' => 'textarea', 'label' => $this->demo('ink.message_68f4145fee'), 'required' => true, 'options' => []],
                ],
                'submit_button_text' => $this->demo('ink.send_9bc2575c39'),
                'success_message' => $this->demo('ink.thanks_i_ll_reply_within_a_week_18bb60d161'),
                'auto_reply_message' => $this->demo('ink.thanks_for_writing_i_read_every_message_234b1dd137'),
                'button_style' => 'btn-primary',
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
        ]);
    }

    // --- Post bodies --------------------------------------------------------

    protected function welcomePost(): string
    {
        return implode("\n", [
            $this->paragraph("If you're reading this, you've stumbled onto my small corner of the internet. Welcome."),
            $this->paragraph("This blog is where I write about <strong>[Topic 1]</strong>, <strong>[Topic 2]</strong>, and occasionally <strong>[Topic 3]</strong>. It's a mix of personal essays, field notes from the work I do, and the occasional long-form piece when something's been rattling around in my head for months."),
            $this->paragraph("New posts go out every other Tuesday. You can subscribe to the email version if you'd rather not remember to check back, or follow the RSS feed if you're the RSS kind."),
            $this->paragraph('A few things to know:'),
            $this->paragraph("<strong>I'm wrong often.</strong> I try to flag when I'm speculating versus when I'm sure. I also try to come back and correct posts when I change my mind — with a note at the top rather than silently."),
            $this->paragraph("<strong>I write slowly on purpose.</strong> These are meant to be read, not skimmed. Most posts are 800–2,500 words. If that's too long for your attention, that's a reasonable reason to close the tab."),
            $this->paragraph('<strong>I love getting replies.</strong> The best part of writing in public is the conversations. Hit the <a href="/contact">contact page</a> if you want to push back, share a better example, or recommend something.'),
            $this->paragraph("That's it. Thanks for being here."),
        ]);
    }

    protected function readingListPost(): string
    {
        return implode("\n", [
            $this->paragraph("A running list of the books and essays I keep returning to. Not a definitive \"best of\" — just the ones that I've actually re-read or referenced in my own work multiple times."),
            $this->paragraph('<strong>Books</strong>'),
            $this->paragraph('<em>Thinking, Fast and Slow</em> by Daniel Kahneman — The book that made me distrust my own intuition in specific, useful ways. System 1 / System 2 is now how I think about almost any decision.'),
            $this->paragraph('<em>The Design of Everyday Things</em> by Don Norman — Still the clearest explanation of why bad design is everywhere and what to do about it. Re-read every few years.'),
            $this->paragraph('<em>Mindset</em> by Carol Dweck — Growth-mindset-as-self-help is a cliché at this point, but the underlying research is real and the book is better than its pop reputation.'),
            $this->paragraph('<strong>Essays</strong>'),
            $this->paragraph("Paul Graham's \"<em>How to Do Great Work</em>\" — Probably the most re-read essay of the last few years for me. Long, specific, and unromantic about what it takes."),
            $this->paragraph("Maria Popova's \"<em>Learning, Presence, and the Art of Self-Renewal</em>\" — A beautiful piece on why we need to periodically re-read things we already know."),
            $this->paragraph('<strong>Substacks / blogs</strong>'),
            $this->paragraph("I read these weekly: [Writer 1]'s [newsletter/blog], [Writer 2], [Writer 3]. If you like what I write, you'll probably like them."),
            $this->paragraph("I'll update this list every quarter. Got something I should be reading? <a href=\"/contact\">Tell me</a>."),
        ]);
    }

    protected function systemsPost(): string
    {
        return implode("\n", [
            $this->paragraph('Most of the advice you read tells you to fix the symptom. Your coworker dropped a task → have a tough conversation. Your customer churned → call them. Your inbox is overflowing → declare inbox zero.'),
            $this->paragraph("Sometimes the symptom is the problem. Most of the time it isn't."),
            $this->paragraph("The symptom is a signal. What's generating the signal is the system. If you keep fixing symptoms, you'll keep seeing new ones — because the system hasn't changed."),
            $this->paragraph('<strong>A real example.</strong>'),
            $this->paragraph("A few years ago I kept having the same Monday. I'd arrive at work with a long list of things I wanted to accomplish that week. By Wednesday I'd accomplished none of them because I was drowning in Slack messages and calendar invites. Friday I'd feel behind. Monday I'd make a new list. Repeat."),
            $this->paragraph("The symptom: I wasn't getting my list done. The fix-the-symptom advice was everywhere: \"block time on your calendar,\" \"turn off notifications,\" \"say no more often.\""),
            $this->paragraph("The system was: my job had two modes — reactive (handling what came at me) and proactive (what I actually wanted to do). My calendar was structured to serve the reactive mode. I'd wedge the proactive work into whatever gaps were left, which were never big enough to do real work."),
            $this->paragraph('Fixing the symptom (blocking time) helped for a week. Fixing the system — moving proactive work to the morning before reactive work could interrupt it, and moving reactive work to the afternoon so it had a natural deadline — helped for a year.'),
            $this->paragraph('<strong>The uncomfortable part.</strong>'),
            $this->paragraph("Thinking in systems usually means admitting that the symptom isn't the problem. Your relationship isn't going to improve by having one more good conversation. Your team isn't going to ship faster by working one more weekend. Your diet isn't going to work by having one more disciplined week."),
            $this->paragraph('Something bigger is generating all those symptoms. Finding it is slower, less satisfying, and more likely to actually help.'),
            $this->paragraph("This is why I keep coming back to it — not because it's an easy heuristic, but because it's one of the few that consistently forces me to ask a better question."),
        ]);
    }

    protected function writingProcessPost(): string
    {
        return implode("\n", [
            $this->paragraph('For a long time I was convinced my writing would get better if I found the right system.'),
            $this->paragraph('I tried Zettelkasten. I tried Roam. I tried morning pages. I tried the Hemingway approach (stop in the middle of a good sentence). I tried writing at 5am. I tried Scrivener. I tried outlining everything. I tried never outlining.'),
            $this->paragraph("After a decade of that, my writing process is: I open a blank document, I write the thing, I edit it twice, I publish it. The tools don't matter. The time of day doesn't matter. The outline helps if the piece is long; otherwise it doesn't."),
            $this->paragraph('<strong>What actually mattered, in rough order:</strong>'),
            $this->paragraph("<strong>Reading more than writing.</strong> Most writing problems are reading problems — specifically, a problem of having nothing interesting to say because you haven't fed the machine enough. When I'm stuck, it's because I haven't read anything new in a while."),
            $this->paragraph('<strong>Editing harder than drafting.</strong> First drafts should be bad. Good writing comes out of the second and third pass, not the first one. I had to internalize this before I could stop deleting first drafts in frustration.'),
            $this->paragraph("<strong>Writing in sentences that I could say out loud.</strong> If I can't read a sentence to a friend without feeling pompous, it's wrong. This single rule fixed more of my writing than any tool ever did."),
            $this->paragraph("<strong>Shipping incomplete ideas.</strong> Posts don't need to be complete. They need to be clear about what they are and what they aren't. A post saying \"I'm thinking about X and here's where I am\" is more useful than a half-finished post trying to be definitive."),
            $this->paragraph('<strong>Writing regularly.</strong> Not daily — I tried and hated it. But weekly, with occasional skipped weeks. The habit compounds more than the quantity does.'),
            $this->paragraph("That's it. The simplest process I've found, after many expensive detours, is just \"open doc, write, edit, publish.\" I used to think this was boring advice. Now I think it's the point."),
        ]);
    }

    protected function productivityPost(): string
    {
        return implode("\n", [
            $this->paragraph("Five productivity beliefs I held firmly — and eventually abandoned after they didn't survive contact with real work."),
            $this->paragraph("<strong>1. \"If you're not working 60+ hours, you're not serious.\"</strong>"),
            $this->paragraph("I believed this for about five years. It produced a lot of output and not much that I'd call good. The best work I've done came from 40-hour weeks with enough room for walks and sleep."),
            $this->paragraph('<strong>2. "A perfect morning routine will unlock my potential."</strong>'),
            $this->paragraph("I had the ideal morning routine on a printed card next to my bed. I followed it perfectly for about three weeks. Then I traveled, missed a morning, and the whole thing collapsed. My output the week I followed the routine perfectly was indistinguishable from the week I didn't. Routines matter; the <em>particular</em> routine doesn't."),
            $this->paragraph("<strong>3. \"If I'm tired, I need better sleep hygiene.\"</strong>"),
            $this->paragraph('Sometimes. Most of the time I was tired because my schedule was bad, my priorities were wrong, or I was avoiding something hard. Fixing the schedule was more useful than fixing the sleep.'),
            $this->paragraph('<strong>4. "Saying no is the most important skill."</strong>'),
            $this->paragraph("It's a good skill. It's not the most important one. The most important one is <em>knowing what to say yes to</em>. Saying no to everything is just a slower way of doing nothing."),
            $this->paragraph("<strong>5. \"If I had 10 more hours a week, I'd get ahead.\"</strong>"),
            $this->paragraph("I got 10 more hours a week twice in my career. Both times I used them the same way I used the other hours. The problem wasn't the number of hours; it was what I did with them. If you're not ahead now, you won't be ahead with more hours — you'll just be ahead less slowly."),
            $this->paragraph("None of these beliefs were stupid. They each had a grain of truth. But they were load-bearing for me in ways that the truth didn't actually support, and abandoning them freed up energy I didn't know I was spending."),
        ]);
    }
}
