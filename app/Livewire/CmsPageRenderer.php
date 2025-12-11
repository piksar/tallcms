<?php

namespace App\Livewire;

use App\Models\CmsPage;
use App\Services\CustomBlockDiscoveryService;
use App\Services\MergeTagService;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Livewire\Component;

class CmsPageRenderer extends Component
{
    public CmsPage $page;
    public string $renderedContent;
    
    public function mount(string $slug = '/')
    {
        // Handle root URL - find homepage or show welcome
        if ($slug === '/') {
            $homepage = CmsPage::where('is_homepage', true)
                ->published()
                ->first();
                
            if (!$homepage) {
                // No homepage exists - show welcome page for fresh installation
                return $this->showWelcomePage();
            }
            
            $this->page = $homepage;
        } else {
            // Try to find page by slug, also handle /page/slug format
            $cleanSlug = ltrim($slug, '/');
            if (str_starts_with($cleanSlug, 'page/')) {
                $cleanSlug = str_replace('page/', '', $cleanSlug);
            }
            
            $this->page = CmsPage::withSlug($cleanSlug)
                ->published()
                ->firstOrFail();
        }
            
        $this->renderPageContent();
    }
    
    protected function showWelcomePage(): void
    {
        // Create a virtual welcome page
        $this->page = new CmsPage([
            'title' => 'Welcome to TallCMS',
            'slug' => '/',
            'content' => '', // Will be handled by special template
            'status' => 'published',
            'meta_title' => 'Welcome to TallCMS',
            'meta_description' => 'Get started with your new TallCMS installation',
        ]);
        
        // Set a flag to render welcome content
        $this->renderedContent = 'WELCOME_PAGE';
    }
    
    protected function renderPageContent(): void
    {
        // Render rich content with auto-discovered custom blocks
        $renderedContent = RichContentRenderer::make($this->page->content)
            ->customBlocks(CustomBlockDiscoveryService::getBlocksArray())
            ->toHtml();
            
        // Process merge tags in the rendered content
        $this->renderedContent = MergeTagService::replaceTags($renderedContent, $this->page);
    }
    
    public function render()
    {
        return view('livewire.page')
            ->layout('layouts.app', [
                'title' => $this->page->meta_title ?: $this->page->title,
                'description' => $this->page->meta_description,
                'featuredImage' => $this->page->featured_image,
            ]);
    }
}
