<?php

namespace App\Http\Controllers;

use App\Models\CmsPage;
use App\Models\CmsPost;
use App\Services\CustomBlockDiscoveryService;
use App\Services\MergeTagService;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PreviewController extends Controller
{
    public function page(Request $request, CmsPage $page): View
    {
        $device = $request->get('device', 'desktop');
        
        // Render the page content exactly like CmsPageRenderer does
        $renderedContent = $this->renderContent($page->content, $page);
        
        return view('preview.page', [
            'page' => $page,
            'renderedContent' => $renderedContent,
            'device' => $device,
            'type' => 'page',
        ]);
    }

    public function post(Request $request, CmsPost $post): View
    {
        $device = $request->get('device', 'desktop');
        
        // Render the post content exactly like CmsPageRenderer does
        $renderedContent = $this->renderContent($post->content, $post);
        
        return view('preview.post', [
            'post' => $post,
            'renderedContent' => $renderedContent,
            'device' => $device,
            'type' => 'post',
        ]);
    }

    private function renderContent($content, $model): string
    {
        // Handle content that might be an array from Eloquent JSON casting
        if (is_array($content)) {
            $content = json_encode($content);
        }
        
        // Render rich content with auto-discovered custom blocks (same as CmsPageRenderer)
        // Use toUnsafeHtml() to preserve Alpine.js attributes (x-data, x-model, etc.)
        $renderedContent = RichContentRenderer::make($content)
            ->customBlocks(CustomBlockDiscoveryService::getBlocksArray())
            ->toUnsafeHtml();
            
        // Process merge tags in the rendered content
        return MergeTagService::replaceTags($renderedContent, $model);
    }
}