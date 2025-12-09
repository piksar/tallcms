<?php

use App\Models\TallcmsMenu;

if (!function_exists('menu')) {
    /**
     * Get a menu by location with resolved URLs
     */
    function menu(string $location): ?array
    {
        $menu = TallcmsMenu::byLocation($location);
        
        if (!$menu) {
            return null;
        }

        $items = $menu->activeItems()->with(['page', 'activeChildren.page'])->get();
        
        return $items->map(function ($item) {
            return [
                'id' => $item->id,
                'label' => $item->label,
                'url' => $item->getResolvedUrl(),
                'type' => $item->type,
                'target' => app('menu.url.resolver')->getTargetAttribute($item),
                'icon' => $item->icon,
                'css_class' => $item->css_class,
                'children' => $item->activeChildren->map(function ($child) {
                    return [
                        'id' => $child->id,
                        'label' => $child->label,
                        'url' => $child->getResolvedUrl(),
                        'type' => $child->type,
                        'target' => app('menu.url.resolver')->getTargetAttribute($child),
                        'icon' => $child->icon,
                        'css_class' => $child->css_class,
                    ];
                })->toArray(),
            ];
        })->toArray();
    }
}

if (!function_exists('render_menu')) {
    /**
     * Render a menu as HTML
     */
    function render_menu(string $location, array $options = []): string
    {
        $menu = menu($location);
        
        if (!$menu) {
            return '';
        }

        $ulClass = $options['ul_class'] ?? 'menu';
        $liClass = $options['li_class'] ?? 'menu-item';
        $linkClass = $options['link_class'] ?? 'menu-link';
        
        $html = "<ul class=\"{$ulClass}\">";
        
        foreach ($menu as $item) {
            $html .= render_menu_item($item, $liClass, $linkClass);
        }
        
        $html .= '</ul>';
        
        return $html;
    }
}

if (!function_exists('render_menu_item')) {
    /**
     * Render a single menu item
     */
    function render_menu_item(array $item, string $liClass = '', string $linkClass = ''): string
    {
        $hasChildren = !empty($item['children']);
        $liClass = trim($liClass . ($hasChildren ? ' has-children' : ''));
        
        if ($item['css_class']) {
            $liClass = trim($liClass . ' ' . $item['css_class']);
        }
        
        $html = "<li class=\"{$liClass}\">";
        
        if ($item['url']) {
            $target = $item['target'] === '_blank' ? ' target="_blank" rel="noopener"' : '';
            $icon = $item['icon'] ? "<i class=\"{$item['icon']}\"></i> " : '';
            
            $html .= "<a href=\"{$item['url']}\" class=\"{$linkClass}\"{$target}>";
            $html .= $icon . htmlspecialchars($item['label']);
            $html .= '</a>';
        } else {
            // Header/separator items without links
            $html .= "<span class=\"{$linkClass}\">" . htmlspecialchars($item['label']) . '</span>';
        }
        
        if ($hasChildren) {
            $html .= '<ul class="submenu">';
            foreach ($item['children'] as $child) {
                $html .= render_menu_item($child, 'submenu-item', $linkClass);
            }
            $html .= '</ul>';
        }
        
        $html .= '</li>';
        
        return $html;
    }
}