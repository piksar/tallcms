@props([
    'animation' => '',
    'duration' => 'anim-duration-500',
    'delay' => 0,
    'tag' => 'div',
    'controller' => false,
    'useParent' => false,
    'notProse' => null,
])

@php
    $hasAnimation = ! empty($animation);
    $delayMs = (int) $delay;
    // Section roots opt out of a parent `.prose` wrapper so grid/hero layout
    // is not restyled. Nested `.prose` inside `.not-prose` is ignored by
    // Tailwind Typography — blocks that render rich text must pass false.
    $notProseClass = ($notProse ?? $tag === 'section') ? 'not-prose' : '';
@endphp

<{{ $tag }}
    @if($controller)
        {{-- Controller: ALWAYS sets up state for synchronized children (even if animation is empty) --}}
        x-data="{ tallcmsShown: false, tallcmsReducedMotion: window.matchMedia('(prefers-reduced-motion: reduce)').matches }"
        x-intersect:enter.once="tallcmsShown = true"
    @elseif($useParent && $hasAnimation)
        {{-- Child using parent's state (must be inside controller) --}}
        {{-- Orphaned fallback: if tallcmsShown undefined, stay visible without animation --}}
        :class="{
            'tallcms-animate animate-{{ $animation }} {{ $duration }}': typeof tallcmsShown !== 'undefined' && (tallcmsShown || tallcmsReducedMotion),
            'opacity-0': typeof tallcmsShown !== 'undefined' && !tallcmsShown && !tallcmsReducedMotion
        }"
        @if($delayMs > 0)
            style="animation-delay: {{ $delayMs }}ms"
        @endif
    @elseif($hasAnimation)
        {{-- Standalone: own x-data, animates independently --}}
        x-data="{ tallcmsShown: false, tallcmsReducedMotion: window.matchMedia('(prefers-reduced-motion: reduce)').matches }"
        x-intersect:enter.once="tallcmsShown = true"
        :class="tallcmsShown || tallcmsReducedMotion ? 'tallcms-animate animate-{{ $animation }} {{ $duration }}' : 'opacity-0'"
        @if($delayMs > 0)
            style="animation-delay: {{ $delayMs }}ms"
        @endif
    @endif
    {{ $attributes->class([$notProseClass]) }}
>
    {{ $slot }}
</{{ $tag }}>
