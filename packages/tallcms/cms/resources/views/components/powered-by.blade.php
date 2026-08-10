@if(\TallCms\Cms\Models\SiteSetting::get('show_powered_by', true))
    <p class="text-xs opacity-60">
        {{ __('tallcms::frontend.powered_by') }} <a href="https://tallcms.com" target="_blank" rel="noopener noreferrer" class="hover:underline">TallCMS</a>
    </p>
@endif
