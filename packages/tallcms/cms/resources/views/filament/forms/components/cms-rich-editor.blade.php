{{--
    CMS Rich Editor - Enhanced Block Panel

    This view extends Filament's RichEditor with:
    - Search functionality for finding blocks quickly
    - Blocks grouped by category
    - Icons displayed alongside block names

    IMPORTANT - FILAMENT UPGRADE CHECKLIST:
    ========================================
    This view is copied from Filament v4.x rich-editor.blade.php with modifications
    to the custom blocks panel section only. When upgrading Filament:

    1. Compare vendor/filament/forms/resources/views/components/rich-editor.blade.php
       with this file using diff
    2. The ONLY modified section is inside: <div x-show="isPanelActive('customBlocks')">
       Look for "Enhanced Block Panel with Search and Categories" comment
    3. Keep data-state-path, data-livewire-id, and data-editor-key on the Alpine root
       (block-chrome.js uses them to persist TipTap JSON after custom-block insert)
    4. Apply any Filament changes to the unmodified sections of this view
    5. Test block insertion, search, and category collapse functionality
    6. Verify editorSelection variable still exists in richEditorFormComponent

    Last synced with: Filament Forms v4.x (January 2026)
    Modified section: Lines ~180-295 (customBlocks panel)
--}}
{{-- Load daisyUI preview styles only on pages with the CMS editor.

     Standalone TallCMS ships preview.css as a Vite entry; plugin-mode
     adopters typically don't have it in their host vite.config.js, so
     calling @vite unconditionally would crash every admin page that
     renders the rich editor with `Illuminate\Foundation\ViteException`.

     ViteManifest::hasEntry() returns true if the entry is in the
     manifest (built mode) or if Vite is hot (dev mode), and false
     otherwise — silently skipping the directive in plugin mode where
     the basics are already covered by the package's pre-built
     tallcms-admin.css registered via FilamentAsset. To opt in to full
     preview styles in plugin mode, add the file to your vite.config.js
     and rebuild. --}}
@pushOnce('styles')
    @if (\TallCms\Cms\Support\ViteManifest::hasEntry('resources/css/filament/admin/preview.css'))
        @vite('resources/css/filament/admin/preview.css')
    @endif
@endPushOnce

@php
    $customBlocks = $getCustomBlocks();
    $extraAttributeBag = $getExtraAttributeBag();
    $fieldWrapperView = $getFieldWrapperView();
    $id = $getId();
    $isDisabled = $isDisabled();
    $livewireKey = $getLivewireKey();
    $key = $getKey();
    $mergeTags = $getMergeTags();
    $statePath = $getStatePath();
    $mentions = $getMentionsForJs();
    $tools = $getTools();
    $toolbarButtons = $getToolbarButtons();
    $floatingToolbars = $getFloatingToolbars();
    $linkProtocols = $getLinkProtocols();
    $fileAttachmentsMaxSize = $getFileAttachmentsMaxSize();
    $fileAttachmentsAcceptedFileTypes = $getFileAttachmentsAcceptedFileTypes();

    // Enhanced block panel data (this view is only loaded when Filament v4.x is detected)
    $groupedBlocks = $getGroupedBlocks();
    $blockCategories = $getBlockCategories();
@endphp

<x-dynamic-component :component="$fieldWrapperView" :field="$field">
    <x-filament::input.wrapper
        :valid="! $errors->has($statePath)"
        x-cloak
        :attributes="
            \Filament\Support\prepare_inherited_attributes($extraAttributeBag)
                ->class(['fi-fo-rich-editor'])
        "
    >
        <div
            x-load
            x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('rich-editor', 'filament/forms') }}"
            x-data="richEditorFormComponent({
                        acceptedFileTypes: @js($fileAttachmentsAcceptedFileTypes),
                        acceptedFileTypesValidationMessage: @js($fileAttachmentsAcceptedFileTypes ? __('filament-forms::components.rich_editor.file_attachments_accepted_file_types_message', ['values' => implode(', ', $fileAttachmentsAcceptedFileTypes)]) : null),
                        activePanel: @js($getActivePanel()),
                        canAttachFiles: @js($hasFileAttachments()),
                        deleteCustomBlockButtonIconHtml: @js(\Filament\Support\generate_icon_html(\Filament\Support\Icons\Heroicon::Trash, alias: \Filament\Forms\View\FormsIconAlias::COMPONENTS_RICH_EDITOR_PANELS_CUSTOM_BLOCK_DELETE_BUTTON)->toHtml()),
                        editCustomBlockButtonIconHtml: @js(\Filament\Support\generate_icon_html(\Filament\Support\Icons\Heroicon::PencilSquare, alias: \Filament\Forms\View\FormsIconAlias::COMPONENTS_RICH_EDITOR_PANELS_CUSTOM_BLOCK_EDIT_BUTTON)->toHtml()),
                        extensions: @js($getTipTapJsExtensions()),
                        floatingToolbars: @js($floatingToolbars),
                        getMentionLabelsUsing: async (mentions) => {
                            return await $wire.callSchemaComponentMethod(
                                @js($key),
                                'getMentionLabelsForJs',
                                { mentions },
                            )
                        },
                        getMentionSearchResultsUsing: async (query, char) => {
                            return await $wire.callSchemaComponentMethod(
                                @js($key),
                                'getMentionSearchResultsForJs',
                                { search: query, char },
                            )
                        },
                        hasResizableImages: @js($hasResizableImages()),
                        isDisabled: @js($isDisabled),
                        isLiveDebounced: @js($isLiveDebounced()),
                        isLiveOnBlur: @js($isLiveOnBlur()),
                        key: @js($key),
                        linkProtocols: @js($linkProtocols),
                        liveDebounce: @js($getNormalizedLiveDebounce()),
                        livewireId: @js($this->getId()),
                        maxFileSize: @js($fileAttachmentsMaxSize),
                        maxFileSizeValidationMessage: @js($fileAttachmentsMaxSize ? trans_choice('filament-forms::components.rich_editor.file_attachments_max_size_message', $fileAttachmentsMaxSize, ['max' => $fileAttachmentsMaxSize]) : null),
                        mentions: @js($mentions),
                        mergeTags: @js($mergeTags),
                        noMergeTagSearchResultsMessage: @js($getNoMergeTagSearchResultsMessage()),
                        placeholder: @js($getPlaceholder()),
                        state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')", isOptimisticallyLive: false) }},
                        statePath: @js($statePath),
                        textColors: @js($getTextColorsForJs()),
                        uploadingFileMessage: @js($getUploadingFileMessage()),
                    })"
            x-bind:class="{
                'fi-fo-rich-editor-uploading-file': isUploadingFile,
            }"
            data-state-path="{{ $statePath }}"
            data-livewire-id="{{ $this->getId() }}"
            data-editor-key="{{ $key }}"
            wire:ignore
            wire:key="{{ $livewireKey }}.{{
                substr(md5(serialize([
                    $isDisabled,
                ])), 0, 64)
            }}"
        >
            @if ((! $isDisabled) && filled($toolbarButtons))
                <div class="fi-fo-rich-editor-toolbar">
                    @foreach ($toolbarButtons as $button => $buttonGroup)
                        <div class="fi-fo-rich-editor-toolbar-group">
                            @foreach ($buttonGroup as $button)
                                {{ $tools[$button] ?? throw new LogicException("Toolbar button [{$button}] cannot be found.") }}
                            @endforeach
                        </div>
                    @endforeach
                </div>
            @endif

            <div
                x-show="isUploadingFile"
                x-cloak
                class="fi-fo-rich-editor-uploading-file-message"
            >
                {{ \Filament\Support\generate_loading_indicator_html() }}

                <span>
                    {{ $getUploadingFileMessage() }}
                </span>
            </div>

            <div
                x-show="! isUploadingFile && fileValidationMessage"
                x-cloak
                class="fi-fo-rich-editor-file-validation-message"
            >
                <span
                    x-text="fileValidationMessage"
                    x-show="! isUploadingFile && fileValidationMessage"
                ></span>
            </div>

            <div
                {{ $getExtraInputAttributeBag()->class(['fi-fo-rich-editor-main']) }}
            >
                <div class="fi-fo-rich-editor-content fi-prose" x-ref="editor">
                    @foreach ($floatingToolbars as $nodeName => $buttons)
                        <div
                            x-ref="floatingToolbar::{{ $nodeName }}"
                            class="fi-fo-rich-editor-floating-toolbar fi-not-prose"
                        >
                            @foreach ($buttons as $button)
                                {{ $tools[$button] }}
                            @endforeach
                        </div>
                    @endforeach
                </div>

                @if (! $isDisabled)
                    <div
                        x-show="isPanelActive()"
                        x-cloak
                        class="fi-fo-rich-editor-panels"
                    >
                        <div
                            x-show="isPanelActive('customBlocks')"
                            x-cloak
                            class="fi-fo-rich-editor-panel flex flex-col"
                        >
                            <div class="fi-fo-rich-editor-panel-header">
                                <p class="fi-fo-rich-editor-panel-heading">
                                    {{ __('filament-forms::components.rich_editor.tools.custom_blocks') }}
                                </p>

                                <div
                                    class="fi-fo-rich-editor-panel-close-btn-ctn"
                                >
                                    <button
                                        type="button"
                                        x-on:click="togglePanel()"
                                        class="fi-icon-btn"
                                    >
                                        {{ \Filament\Support\generate_icon_html(\Filament\Support\Icons\Heroicon::XMark, alias: \Filament\Forms\View\FormsIconAlias::COMPONENTS_RICH_EDITOR_PANELS_CUSTOM_BLOCKS_CLOSE_BUTTON) }}
                                    </button>
                                </div>
                            </div>

                            {{-- Enhanced Block Panel with Search and Categories --}}
                            @if (count($groupedBlocks) > 0)
                                <div
                                    class="fi-fo-rich-editor-custom-blocks-list flex flex-col h-full"
                                    x-data="{
                                        searchQuery: '',
                                        labels: {
                                            recentlyUsed: @js(__('tallcms::editor.recently_used')),
                                            other: @js(__('tallcms::editor.other')),
                                        },
                                        blocks: @js($groupedBlocks),
                                        categories: @js($blockCategories),
                                        componentKey: @js($key),
                                        expandedCategories: ['recent', ...Object.keys(@js($groupedBlocks))],
                                        editorSelectionWarned: false,
                                        recentBlockIds: [],
                                        maxRecentBlocks: 5,
                                        storageKey: 'tallcms.editor.recentBlocks',
                                        activeTab: 'blocks',
                                        outlineItems: [],
                                        sortableInstance: null,

                                        init() {
                                            try {
                                                const stored = localStorage.getItem(this.storageKey);
                                                if (stored) {
                                                    const parsed = JSON.parse(stored);
                                                    if (Array.isArray(parsed)) this.recentBlockIds = parsed;
                                                }
                                            } catch (e) { /* private mode or bad JSON: ignore */ }

                                            // Subscribe to outline updates emitted by the TipTap extension.
                                            // Events bubble from .ProseMirror up to .fi-fo-rich-editor, which
                                            // is a common ancestor of both the editor and this panel.
                                            const wrapper = this.$el.closest('.fi-fo-rich-editor');
                                            if (wrapper) {
                                                wrapper.addEventListener('cms-block-outline-changed', (event) => {
                                                    this.outlineItems = event.detail?.items ?? [];
                                                });

                                                // Slash command insert: the TipTap extension already deleted the
                                                // /trigger range; we just route the chosen block into the same
                                                // mountAction flow that the picker buttons use.
                                                wrapper.addEventListener('cms-slash-insert', (event) => {
                                                    const blockId = event.detail?.blockId;
                                                    if (!blockId) return;
                                                    if (typeof editorSelection === 'undefined') {
                                                        if (!this.editorSelectionWarned) {
                                                            console.warn('CmsRichEditor: editorSelection not found in parent scope');
                                                            this.editorSelectionWarned = true;
                                                        }
                                                        return;
                                                    }
                                                    this.insertBlock(blockId, editorSelection);
                                                });
                                            }
                                        },

                                        iconHtmlForBlock(blockId) {
                                            return this.blockLookup[blockId]?.iconHtml ?? '';
                                        },

                                        labelForBlock(blockId) {
                                            return this.blockLookup[blockId]?.label ?? blockId;
                                        },

                                        dispatchOutlineAction(action, args) {
                                            const wrapper = this.$el.closest('.fi-fo-rich-editor');
                                            const proseMirror = wrapper?.querySelector('.ProseMirror');
                                            if (!proseMirror) return;
                                            proseMirror.dispatchEvent(new CustomEvent('cms-block-action', {
                                                detail: { action, args },
                                                bubbles: false,
                                            }));
                                        },

                                        scrollToBlock(item) {
                                            this.dispatchOutlineAction('scrollTo', { pos: item.pos });
                                        },

                                        async ensureSortable() {
                                            if (this.sortableInstance) return;

                                            // block-chrome.js sets window.tallcmsSortable on load; the TipTap
                                            // extension loader is async, so it may not be ready when Alpine
                                            // first runs. Poll briefly, then degrade to read-only outline.
                                            let attempts = 0;
                                            while (!window.tallcmsSortable && attempts < 40) {
                                                await new Promise(r => setTimeout(r, 50));
                                                attempts++;
                                            }
                                            if (!window.tallcmsSortable) {
                                                console.warn('CMS Block Outline: Sortable failed to load; drag-reorder disabled');
                                                return;
                                            }

                                            const list = this.$refs.outlineList;
                                            if (!list) return;

                                            this.sortableInstance = window.tallcmsSortable.create(list, {
                                                animation: 150,
                                                handle: '.fi-cms-outline-handle',
                                                ghostClass: 'fi-cms-outline-ghost',
                                                onEnd: (evt) => {
                                                    if (evt.oldIndex === evt.newIndex) return;
                                                    const fromPos = parseInt(evt.item.dataset.pos, 10);
                                                    if (Number.isNaN(fromPos)) return;

                                                    // Optimistic reorder so any re-render between now and the
                                                    // editor's outline-changed echo matches Sortable's view.
                                                    const moved = this.outlineItems.find(i => i.pos === fromPos);
                                                    if (moved) {
                                                        const next = this.outlineItems.filter(i => i.pos !== fromPos);
                                                        next.splice(evt.newIndex, 0, moved);
                                                        this.outlineItems = next;
                                                    }

                                                    this.dispatchOutlineAction('moveTo', {
                                                        fromPos,
                                                        toIndex: evt.newIndex,
                                                    });
                                                },
                                            });
                                        },

                                        switchTab(name) {
                                            this.activeTab = name;
                                            if (name === 'outline') this.ensureSortable();
                                        },

                                        get isSearching() {
                                            return this.searchQuery.trim().length > 0;
                                        },

                                        get searchTerms() {
                                            return this.searchQuery.toLowerCase().trim().split(/\s+/).filter(Boolean);
                                        },

                                        rankBlock(block) {
                                            const label = block.label.toLowerCase();
                                            const id = block.id.toLowerCase();
                                            const first = this.searchTerms[0] ?? '';
                                            if (label === first) return 0;
                                            if (label.startsWith(first)) return 1;
                                            if (label.includes(first)) return 2;
                                            if (id.includes(first)) return 3;
                                            return 4;
                                        },

                                        get filteredBlocks() {
                                            if (!this.isSearching) return this.blocks;
                                            const terms = this.searchTerms;
                                            const result = {};
                                            for (const [cat, catBlocks] of Object.entries(this.blocks)) {
                                                const filtered = catBlocks
                                                    .filter(b => terms.every(t => b.searchable.includes(t)))
                                                    .sort((a, b) => this.rankBlock(a) - this.rankBlock(b));
                                                if (filtered.length > 0) result[cat] = filtered;
                                            }
                                            return result;
                                        },

                                        get blockLookup() {
                                            const lookup = {};
                                            for (const catBlocks of Object.values(this.blocks)) {
                                                for (const b of catBlocks) lookup[b.id] = b;
                                            }
                                            return lookup;
                                        },

                                        get recentBlocks() {
                                            if (this.isSearching) return [];
                                            const lookup = this.blockLookup;
                                            return this.recentBlockIds
                                                .map(id => lookup[id])
                                                .filter(Boolean)
                                                .slice(0, this.maxRecentBlocks);
                                        },

                                        get displayedBlocks() {
                                            if (this.isSearching) return this.filteredBlocks;
                                            const result = {};
                                            if (this.recentBlocks.length > 0) result['recent'] = this.recentBlocks;
                                            Object.assign(result, this.blocks);
                                            return result;
                                        },

                                        get effectiveCategories() {
                                            return { recent: { label: this.labels.recentlyUsed }, ...this.categories };
                                        },

                                        get totalFilteredCount() {
                                            return Object.values(this.filteredBlocks).flat().length;
                                        },

                                        get hasMultipleCategories() {
                                            return Object.keys(this.displayedBlocks).length > 1;
                                        },

                                        isExpanded(category) {
                                            return this.expandedCategories.includes(category);
                                        },

                                        toggleCategory(category) {
                                            if (this.isExpanded(category)) {
                                                this.expandedCategories = this.expandedCategories.filter(c => c !== category);
                                            } else {
                                                this.expandedCategories.push(category);
                                            }
                                        },

                                        rememberBlock(blockId) {
                                            const next = [blockId, ...this.recentBlockIds.filter(id => id !== blockId)]
                                                .slice(0, this.maxRecentBlocks);
                                            this.recentBlockIds = next;
                                            try {
                                                localStorage.setItem(this.storageKey, JSON.stringify(next));
                                            } catch (e) { /* storage unavailable: in-memory only */ }
                                        },

                                        insertBlock(blockId, selection) {
                                            this.rememberBlock(blockId);
                                            $wire.mountAction(
                                                'customBlock',
                                                { editorSelection: selection, id: blockId, mode: 'insert' },
                                                { schemaComponent: this.componentKey },
                                            );
                                        }
                                    }"
                                >
                                    {{-- Tab Strip: Blocks | Outline --}}
                                    <div class="fi-cms-block-tabs flex border-b" role="tablist">
                                        <button
                                            type="button"
                                            role="tab"
                                            x-on:click="switchTab('blocks')"
                                            :aria-selected="activeTab === 'blocks'"
                                            :class="activeTab === 'blocks' ? 'fi-cms-block-tab-active' : ''"
                                            class="fi-cms-block-tab flex-1 px-3 py-2 text-sm font-medium transition-colors"
                                        >
                                            {{ __('tallcms::editor.blocks') }}
                                        </button>
                                        <button
                                            type="button"
                                            role="tab"
                                            x-on:click="switchTab('outline')"
                                            :aria-selected="activeTab === 'outline'"
                                            :class="activeTab === 'outline' ? 'fi-cms-block-tab-active' : ''"
                                            class="fi-cms-block-tab flex-1 px-3 py-2 text-sm font-medium transition-colors"
                                        >
                                            {{ __('tallcms::editor.outline') }}
                                            <span x-show="outlineItems.length > 0" x-text="`(${outlineItems.length})`" class="opacity-60 ms-1"></span>
                                        </button>
                                    </div>

                                    {{-- Search Input (Blocks tab) --}}
                                    <div x-show="activeTab === 'blocks'" class="fi-cms-block-search flex items-center gap-2 border-b px-3 py-2">
                                        <div class="shrink-0 text-gray-400 dark:text-gray-500">
                                            {!! \Filament\Support\generate_icon_html('heroicon-m-magnifying-glass', 'h-4 w-4')->toHtml() !!}
                                        </div>
                                        <input
                                            type="text"
                                            x-model="searchQuery"
                                            placeholder="{{ __('tallcms::editor.search_blocks') }}"
                                            class="fi-input block w-full border-none bg-transparent py-1 text-sm text-gray-950 outline-none placeholder:text-gray-400 focus:ring-0 dark:text-white dark:placeholder:text-gray-500"
                                        />
                                    </div>

                                    {{-- Grouped Blocks (Blocks tab) --}}
                                    <div x-show="activeTab === 'blocks'" class="fi-cms-block-categories flex-1 overflow-y-auto">
                                        <template x-for="(catBlocks, category) in displayedBlocks" :key="category">
                                            <div class="fi-cms-block-category" x-show="catBlocks.length > 0">
                                                {{-- Collapsible Category Header --}}
                                                <template x-if="hasMultipleCategories">
                                                    <button
                                                        type="button"
                                                        x-on:click="toggleCategory(category)"
                                                        class="fi-cms-block-category-heading sticky top-0 z-10 flex w-full items-center justify-between gap-2 px-3 py-2 text-start text-xs font-semibold uppercase tracking-wider transition-colors"
                                                    >
                                                        <span x-text="effectiveCategories[category]?.label ?? labels.other"></span>
                                                        <span class="transition-transform duration-200" :class="{ 'rotate-180': isExpanded(category) }">
                                                            {!! \Filament\Support\generate_icon_html('heroicon-m-chevron-down', 'h-4 w-4')->toHtml() !!}
                                                        </span>
                                                    </button>
                                                </template>

                                                {{-- Block Buttons --}}
                                                <div
                                                    x-show="!hasMultipleCategories || isExpanded(category)"
                                                    x-cloak
                                                    x-transition:enter="transition ease-out duration-100"
                                                    x-transition:enter-start="opacity-0 -translate-y-1"
                                                    x-transition:enter-end="opacity-100 translate-y-0"
                                                    x-transition:leave="transition ease-in duration-75"
                                                    x-transition:leave-start="opacity-100 translate-y-0"
                                                    x-transition:leave-end="opacity-0 -translate-y-1"
                                                    class="fi-cms-block-category-items flex flex-col gap-0.5 px-2 py-1"
                                                >
                                                    <template x-for="block in catBlocks" :key="block.id">
                                                        <button
                                                            draggable="true"
                                                            type="button"
                                                            x-data="{ isLoading: false }"
                                                            x-on:click="
                                                                if (typeof editorSelection === 'undefined') {
                                                                    if (!editorSelectionWarned) {
                                                                        console.warn('CmsRichEditor: editorSelection not found in parent scope');
                                                                        editorSelectionWarned = true;
                                                                    }
                                                                    return;
                                                                }
                                                                isLoading = true;
                                                                insertBlock(block.id, editorSelection);
                                                            "
                                                            x-on:dragstart="$event.dataTransfer.setData('customBlock', block.id); rememberBlock(block.id)"
                                                            x-on:open-modal.window="isLoading = false"
                                                            x-on:run-rich-editor-commands.window="isLoading = false"
                                                            class="fi-fo-rich-editor-custom-block-btn fi-cms-block-btn flex w-full items-center gap-2 rounded-lg px-2 py-1.5 text-start text-sm transition-colors hover:bg-gray-100 dark:hover:bg-white/5"
                                                        >
                                                            {{-- Loading Indicator --}}
                                                            <template x-if="isLoading">
                                                                {!! \Filament\Support\generate_loading_indicator_html((new \Illuminate\View\ComponentAttributeBag([])))->toHtml() !!}
                                                            </template>

                                                            {{-- Block Icon --}}
                                                            <template x-if="!isLoading">
                                                                <span class="fi-cms-block-icon h-4 w-4 shrink-0" x-html="block.iconHtml"></span>
                                                            </template>

                                                            {{-- Block Label --}}
                                                            <span x-text="block.label"></span>
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>

                                        {{-- No Results Message --}}
                                        <div
                                            x-show="totalFilteredCount === 0"
                                            x-cloak
                                            class="fi-cms-block-no-results px-3 py-6 text-center text-sm text-gray-500 dark:text-gray-400"
                                        >
                                            <p>No blocks match your search.</p>
                                        </div>
                                    </div>

                                    {{-- Outline (Outline tab) --}}
                                    <div x-show="activeTab === 'outline'" x-cloak class="fi-cms-block-outline flex-1 overflow-y-auto">
                                        <ul x-ref="outlineList" class="fi-cms-block-outline-list flex flex-col gap-0.5 p-2" x-show="outlineItems.length > 0">
                                            <template x-for="item in outlineItems" :key="`${item.pos}-${item.id}`">
                                                <li class="fi-cms-block-outline-item flex items-center gap-2 rounded-lg px-2 py-1.5" :data-pos="item.pos">
                                                    <span class="fi-cms-outline-handle shrink-0" aria-hidden="true" title="Drag to reorder">
                                                        {!! \Filament\Support\generate_icon_html('heroicon-m-bars-3', 'h-4 w-4')->toHtml() !!}
                                                    </span>
                                                    <button
                                                        type="button"
                                                        x-on:click="scrollToBlock(item)"
                                                        :title="item.title || labelForBlock(item.id)"
                                                        class="fi-cms-block-outline-target flex flex-1 min-w-0 items-center gap-2 text-start text-sm"
                                                    >
                                                        <span class="fi-cms-block-icon h-4 w-4 shrink-0" x-html="iconHtmlForBlock(item.id)"></span>
                                                        <span class="flex flex-1 flex-col min-w-0 leading-tight">
                                                            <span class="block truncate" x-text="item.title || labelForBlock(item.id)"></span>
                                                            <span x-show="item.title" x-text="labelForBlock(item.id)" class="block text-xs opacity-60 truncate"></span>
                                                        </span>
                                                    </button>
                                                </li>
                                            </template>
                                        </ul>
                                        <div
                                            x-show="outlineItems.length === 0"
                                            class="px-3 py-6 text-center text-sm text-gray-500 dark:text-gray-400"
                                        >
                                            <p>{{ __('tallcms::editor.no_blocks_outline') }}</p>
                                        </div>
                                    </div>
                                </div>
                            @else
                                {{-- Fallback: Standard Block List --}}
                                <div class="fi-fo-rich-editor-custom-blocks-list">
                                    @foreach ($customBlocks as $block)
                                        @php
                                            $blockId = $block::getId();
                                        @endphp

                                        <button
                                            draggable="true"
                                            type="button"
                                            x-data="{ isLoading: false }"
                                            x-on:click="
                                                isLoading = true

                                                $wire.mountAction(
                                                    'customBlock',
                                                    { editorSelection, id: @js($blockId), mode: 'insert' },
                                                    { schemaComponent: @js($key) },
                                                )
                                            "
                                            x-on:dragstart="$event.dataTransfer.setData('customBlock', @js($blockId))"
                                            x-on:open-modal.window="isLoading = false"
                                            x-on:run-rich-editor-commands.window="isLoading = false"
                                            class="fi-fo-rich-editor-custom-block-btn"
                                        >
                                            {{
                                                \Filament\Support\generate_loading_indicator_html((new \Illuminate\View\ComponentAttributeBag([
                                                    'x-show' => 'isLoading',
                                                ])))
                                            }}

                                            {{ $block::getLabel() }}
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div
                            x-show="isPanelActive('mergeTags')"
                            x-cloak
                            class="fi-fo-rich-editor-panel"
                        >
                            <div class="fi-fo-rich-editor-panel-header">
                                <p class="fi-fo-rich-editor-panel-heading">
                                    {{ __('filament-forms::components.rich_editor.tools.merge_tags') }}
                                </p>

                                <div
                                    class="fi-fo-rich-editor-panel-close-btn-ctn"
                                >
                                    <button
                                        type="button"
                                        x-on:click="togglePanel()"
                                        class="fi-icon-btn"
                                    >
                                        {{ \Filament\Support\generate_icon_html(\Filament\Support\Icons\Heroicon::XMark, alias: \Filament\Forms\View\FormsIconAlias::COMPONENTS_RICH_EDITOR_PANELS_MERGE_TAGS_CLOSE_BUTTON) }}
                                    </button>
                                </div>
                            </div>

                            <div class="fi-fo-rich-editor-merge-tags-list">
                                @foreach ($mergeTags as $tagId => $tagLabel)
                                    <button
                                        draggable="true"
                                        type="button"
                                        x-on:click="insertMergeTag(@js($tagId))"
                                        x-on:dragstart="$event.dataTransfer.setData('mergeTag', @js($tagId))"
                                        class="fi-fo-rich-editor-merge-tag-btn"
                                    >
                                        <span
                                            data-type="mergeTag"
                                            data-id="{{ $tagId }}"
                                        >
                                            {{ $tagLabel }}
                                        </span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </x-filament::input.wrapper>
</x-dynamic-component>
