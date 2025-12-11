{{-- TallCMS Welcome Page --}}
<div class="min-h-screen bg-gradient-to-br from-amber-50 via-white to-amber-50">
    
    {{-- Hero Section --}}
    <div class="relative overflow-hidden">
        <div class="max-w-7xl mx-auto">
            <div class="relative z-10 pb-8 sm:pb-16 md:pb-20 lg:max-w-2xl lg:w-full lg:pb-28 xl:pb-32">
                
                {{-- Main Content --}}
                <div class="mt-10 mx-auto max-w-7xl px-4 sm:mt-12 sm:px-6 md:mt-16 lg:mt-20 lg:px-8 xl:mt-28">
                    <div class="sm:text-center lg:text-left">
                        
                        {{-- Logo/Branding --}}
                        <div class="mb-8">
                            <div class="flex items-center justify-center lg:justify-start">
                                <div class="flex-shrink-0">
                                    <svg class="h-16 w-16 text-amber-600" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2ZM21 9V7L15 1H5C3.89 1 3 1.89 3 3V21C3 22.11 3.89 23 5 23H19C20.11 23 21 22.11 21 21V9H21ZM19 21H5V3H13V9H19V21Z"/>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h1 class="text-4xl font-bold text-gray-900 sm:text-5xl md:text-6xl">
                                        Tall<span class="text-amber-600">CMS</span>
                                    </h1>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Welcome Message --}}
                        <div class="mb-8">
                            <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">
                                Welcome to your new CMS!
                            </h2>
                            <p class="mt-3 text-base text-gray-500 sm:mt-5 sm:text-lg sm:max-w-xl sm:mx-auto md:mt-5 md:text-xl lg:mx-0">
                                Your TallCMS installation is ready. Get started by creating your first page or setting up your site configuration.
                            </p>
                        </div>
                        
                        {{-- Action Buttons --}}
                        <div class="mt-5 sm:mt-8 sm:flex sm:justify-center lg:justify-start">
                            <div class="rounded-md shadow">
                                <a href="/admin" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-amber-600 hover:bg-amber-700 md:py-4 md:text-lg md:px-10 transition-colors">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    Access Admin Panel
                                </a>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Decorative Image/Pattern --}}
        <div class="lg:absolute lg:inset-y-0 lg:right-0 lg:w-1/2">
            <div class="h-56 w-full bg-gradient-to-br from-amber-400 to-amber-600 sm:h-72 md:h-96 lg:w-full lg:h-full opacity-10"></div>
        </div>
    </div>
    
    {{-- Quick Setup Section --}}
    <div class="py-12 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="lg:text-center">
                <h2 class="text-base text-amber-600 font-semibold tracking-wide uppercase">Quick Setup</h2>
                <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                    Get started in minutes
                </p>
                <p class="mt-4 max-w-2xl text-xl text-gray-500 lg:mx-auto">
                    Follow these simple steps to set up your website and start creating amazing content.
                </p>
            </div>
            
            <div class="mt-10">
                <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    
                    {{-- Step 1 --}}
                    <div class="bg-gray-50 rounded-lg p-6 text-center">
                        <div class="flex items-center justify-center h-12 w-12 rounded-md bg-amber-500 text-white mx-auto mb-4">
                            <span class="text-xl font-bold">1</span>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Create Your Homepage</h3>
                        <p class="text-base text-gray-500 mb-4">
                            Start by creating your first page and setting it as your homepage.
                        </p>
                        <a href="/admin/cms-pages/create" class="text-amber-600 hover:text-amber-500 font-medium">
                            Create Page →
                        </a>
                    </div>
                    
                    {{-- Step 2 --}}
                    <div class="bg-gray-50 rounded-lg p-6 text-center">
                        <div class="flex items-center justify-center h-12 w-12 rounded-md bg-amber-500 text-white mx-auto mb-4">
                            <span class="text-xl font-bold">2</span>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Configure Settings</h3>
                        <p class="text-base text-gray-500 mb-4">
                            Set up your site name, logo, and other important settings.
                        </p>
                        <a href="/admin/site-settings" class="text-amber-600 hover:text-amber-500 font-medium">
                            Site Settings →
                        </a>
                    </div>
                    
                    {{-- Step 3 --}}
                    <div class="bg-gray-50 rounded-lg p-6 text-center">
                        <div class="flex items-center justify-center h-12 w-12 rounded-md bg-amber-500 text-white mx-auto mb-4">
                            <span class="text-xl font-bold">3</span>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Create Navigation</h3>
                        <p class="text-base text-gray-500 mb-4">
                            Build your site's navigation menu to help visitors explore your content.
                        </p>
                        <a href="/admin/tallcms-menus" class="text-amber-600 hover:text-amber-500 font-medium">
                            Create Menu →
                        </a>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
    
    {{-- Features Section --}}
    <div class="py-12 bg-amber-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="lg:text-center mb-12">
                <h2 class="text-base text-amber-600 font-semibold tracking-wide uppercase">Built-in Features</h2>
                <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                    Everything you need to build great websites
                </p>
            </div>
            
            <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
                
                <div class="text-center">
                    <div class="flex items-center justify-center h-12 w-12 rounded-md bg-amber-500 text-white mx-auto mb-4">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900">Rich Editor</h3>
                    <p class="mt-2 text-base text-gray-500">Powerful content editor with custom blocks and merge tags</p>
                </div>
                
                <div class="text-center">
                    <div class="flex items-center justify-center h-12 w-12 rounded-md bg-amber-500 text-white mx-auto mb-4">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900">Responsive</h3>
                    <p class="mt-2 text-base text-gray-500">Mobile-first design that looks great on all devices</p>
                </div>
                
                <div class="text-center">
                    <div class="flex items-center justify-center h-12 w-12 rounded-md bg-amber-500 text-white mx-auto mb-4">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900">User Management</h3>
                    <p class="mt-2 text-base text-gray-500">Role-based permissions with multiple user types</p>
                </div>
                
                <div class="text-center">
                    <div class="flex items-center justify-center h-12 w-12 rounded-md bg-amber-500 text-white mx-auto mb-4">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900">TALL Stack</h3>
                    <p class="mt-2 text-base text-gray-500">Built with Tailwind, Alpine, Laravel & Livewire</p>
                </div>
                
            </div>
        </div>
    </div>
    
    {{-- Footer CTA --}}
    <div class="bg-amber-600">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:py-16 lg:px-8 lg:flex lg:items-center lg:justify-between">
            <h2 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl">
                <span class="block">Ready to get started?</span>
                <span class="block text-amber-200">Access your admin panel now.</span>
            </h2>
            <div class="mt-8 flex lg:mt-0 lg:flex-shrink-0">
                <div class="inline-flex rounded-md shadow">
                    <a href="/admin" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-amber-600 bg-white hover:bg-amber-50 transition-colors">
                        Get started
                        <svg class="ml-2 -mr-1 w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
</div>