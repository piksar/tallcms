@extends('installer.layout', ['currentStep' => 'configuration'])

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Configuration</h2>
            <p class="text-gray-600">Configure your TallCMS installation settings</p>
        </div>

        <form method="POST" action="{{ route('installer.install') }}" id="installation-form">
            @csrf
            
            <div class="space-y-8">
                <!-- Application Settings -->
                <div class="border border-gray-200 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        Application Settings
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="app_name" class="block text-sm font-medium text-gray-700 mb-2">Site Name</label>
                            <input type="text" 
                                   id="app_name" 
                                   name="app_name" 
                                   value="{{ old('app_name', 'TallCMS') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   required>
                            @error('app_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label for="app_url" class="block text-sm font-medium text-gray-700 mb-2">Site URL</label>
                            <input type="url" 
                                   id="app_url" 
                                   name="app_url" 
                                   value="{{ old('app_url', request()->getSchemeAndHttpHost()) }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   required>
                            @error('app_url')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label for="app_environment" class="block text-sm font-medium text-gray-700 mb-2">Environment</label>
                            <select id="app_environment" 
                                    name="app_environment"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="production" {{ old('app_environment') === 'production' ? 'selected' : '' }}>Production</option>
                                <option value="local" {{ old('app_environment') === 'local' ? 'selected' : '' }}>Local/Development</option>
                            </select>
                        </div>
                        
                        <div>
                            <div class="flex items-center">
                                <input type="checkbox" 
                                       id="app_debug" 
                                       name="app_debug" 
                                       value="1"
                                       {{ old('app_debug') ? 'checked' : '' }}
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="app_debug" class="ml-2 block text-sm font-medium text-gray-700">
                                    Enable Debug Mode
                                </label>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Only enable for development environments</p>
                        </div>
                    </div>
                </div>

                <!-- Database Settings -->
                <div class="border border-gray-200 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                        </svg>
                        Database Settings
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="db_host" class="block text-sm font-medium text-gray-700 mb-2">Database Host</label>
                            <input type="text" 
                                   id="db_host" 
                                   name="db_host" 
                                   value="{{ old('db_host', 'localhost') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   required>
                            @error('db_host')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label for="db_port" class="block text-sm font-medium text-gray-700 mb-2">Database Port</label>
                            <input type="number" 
                                   id="db_port" 
                                   name="db_port" 
                                   value="{{ old('db_port', '3306') }}"
                                   min="1" 
                                   max="65535"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   required>
                            @error('db_port')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label for="db_database" class="block text-sm font-medium text-gray-700 mb-2">Database Name</label>
                            <input type="text" 
                                   id="db_database" 
                                   name="db_database" 
                                   value="{{ old('db_database') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   required>
                            @error('db_database')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label for="db_username" class="block text-sm font-medium text-gray-700 mb-2">Database Username</label>
                            <input type="text" 
                                   id="db_username" 
                                   name="db_username" 
                                   value="{{ old('db_username') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   required>
                            @error('db_username')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div class="md:col-span-2">
                            <label for="db_password" class="block text-sm font-medium text-gray-700 mb-2">Database Password</label>
                            <input type="password" 
                                   id="db_password" 
                                   name="db_password" 
                                   value="{{ old('db_password') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            @error('db_password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="button" 
                                id="test-db-connection"
                                class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm hover:bg-blue-700 transition-colors">
                            Test Database Connection
                        </button>
                        <div id="db-test-result" class="mt-2 hidden"></div>
                    </div>
                </div>

                <!-- Admin User -->
                <div class="border border-gray-200 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 text-purple-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        Admin User
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="admin_name" class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                            <input type="text" 
                                   id="admin_name" 
                                   name="admin_name" 
                                   value="{{ old('admin_name') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   required>
                            @error('admin_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label for="admin_email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                            <input type="email" 
                                   id="admin_email" 
                                   name="admin_email" 
                                   value="{{ old('admin_email') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   required>
                            @error('admin_email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label for="admin_password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                            <input type="password" 
                                   id="admin_password" 
                                   name="admin_password" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   required
                                   minlength="8">
                            @error('admin_password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label for="admin_password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                            <input type="password" 
                                   id="admin_password_confirmation" 
                                   name="admin_password_confirmation" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   required
                                   minlength="8">
                        </div>
                    </div>
                </div>

                <!-- Mail Settings (Optional) -->
                <div class="border border-gray-200 rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                            <svg class="w-5 h-5 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            Mail Settings
                            <span class="ml-2 text-sm text-gray-500 font-normal">(Optional)</span>
                        </h3>
                        <button type="button" id="toggle-mail-settings" class="text-blue-600 hover:text-blue-800 text-sm">
                            Configure Mail
                        </button>
                    </div>
                    
                    <div id="mail-settings" class="hidden">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="mail_mailer" class="block text-sm font-medium text-gray-700 mb-2">Mailer</label>
                                <select id="mail_mailer"
                                        name="mail_mailer"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select Mailer</option>
                                    <option value="smtp" {{ old('mail_mailer') === 'smtp' ? 'selected' : '' }}>SMTP</option>
                                    <option value="ses" {{ old('mail_mailer') === 'ses' ? 'selected' : '' }}>Amazon SES</option>
                                    <option value="mail" {{ old('mail_mailer') === 'mail' ? 'selected' : '' }}>PHP Mail</option>
                                    <option value="sendmail" {{ old('mail_mailer') === 'sendmail' ? 'selected' : '' }}>Sendmail</option>
                                </select>
                            </div>
                            
                            <div id="mail-from-name-field">
                                <label for="mail_from_name" class="block text-sm font-medium text-gray-700 mb-2">From Name</label>
                                <input type="text" 
                                       id="mail_from_name" 
                                       name="mail_from_name" 
                                       value="{{ old('mail_from_name') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <div class="md:col-span-2">
                                <label for="mail_from_address" class="block text-sm font-medium text-gray-700 mb-2">From Email</label>
                                <input type="email" 
                                       id="mail_from_address" 
                                       name="mail_from_address" 
                                       value="{{ old('mail_from_address') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                        
                        <div id="smtp-settings" class="mt-6 hidden">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="mail_host" class="block text-sm font-medium text-gray-700 mb-2">SMTP Host</label>
                                    <input type="text" 
                                           id="mail_host" 
                                           name="mail_host" 
                                           value="{{ old('mail_host') }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                
                                <div>
                                    <label for="mail_port" class="block text-sm font-medium text-gray-700 mb-2">SMTP Port</label>
                                    <input type="number" 
                                           id="mail_port" 
                                           name="mail_port" 
                                           value="{{ old('mail_port', '587') }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                
                                <div>
                                    <label for="mail_username" class="block text-sm font-medium text-gray-700 mb-2">SMTP Username</label>
                                    <input type="text" 
                                           id="mail_username" 
                                           name="mail_username" 
                                           value="{{ old('mail_username') }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                
                                <div>
                                    <label for="mail_password" class="block text-sm font-medium text-gray-700 mb-2">SMTP Password</label>
                                    <input type="password" 
                                           id="mail_password" 
                                           name="mail_password" 
                                           value="{{ old('mail_password') }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                
                                <div class="md:col-span-2">
                                    <label for="mail_encryption" class="block text-sm font-medium text-gray-700 mb-2">Encryption</label>
                                    <select id="mail_encryption" 
                                            name="mail_encryption"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">None</option>
                                        <option value="tls" {{ old('mail_encryption') === 'tls' ? 'selected' : '' }}>TLS</option>
                                        <option value="ssl" {{ old('mail_encryption') === 'ssl' ? 'selected' : '' }}>SSL</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cloud Storage Settings (Optional) -->
                <div class="border border-gray-200 rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                            <svg class="w-5 h-5 text-orange-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"></path>
                            </svg>
                            Cloud Storage
                            <span class="ml-2 text-sm text-gray-500 font-normal">(Optional)</span>
                        </h3>
                        <button type="button" id="toggle-aws-settings" class="text-blue-600 hover:text-blue-800 text-sm">
                            Configure Cloud Storage
                        </button>
                    </div>

                    <p class="text-sm text-gray-500 mb-4">Use S3-compatible cloud storage for files. Works with AWS S3, DigitalOcean Spaces, MinIO, Backblaze B2, Cloudflare R2, and more.</p>

                    <div id="aws-settings" class="hidden">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label for="s3_provider" class="block text-sm font-medium text-gray-700 mb-2">Storage Provider</label>
                                <select id="s3_provider"
                                        name="s3_provider"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="local" {{ old('s3_provider', 'local') === 'local' ? 'selected' : '' }}>Local Storage (default)</option>
                                    <option value="aws" {{ old('s3_provider') === 'aws' ? 'selected' : '' }}>Amazon S3</option>
                                    <option value="digitalocean" {{ old('s3_provider') === 'digitalocean' ? 'selected' : '' }}>DigitalOcean Spaces</option>
                                    <option value="minio" {{ old('s3_provider') === 'minio' ? 'selected' : '' }}>MinIO</option>
                                    <option value="backblaze" {{ old('s3_provider') === 'backblaze' ? 'selected' : '' }}>Backblaze B2</option>
                                    <option value="cloudflare" {{ old('s3_provider') === 'cloudflare' ? 'selected' : '' }}>Cloudflare R2</option>
                                    <option value="wasabi" {{ old('s3_provider') === 'wasabi' ? 'selected' : '' }}>Wasabi</option>
                                    <option value="custom" {{ old('s3_provider') === 'custom' ? 'selected' : '' }}>Other S3-Compatible</option>
                                </select>
                            </div>

                            <div id="cloud-storage-fields" class="contents hidden">
                            <div>
                                <label for="aws_access_key_id" class="block text-sm font-medium text-gray-700 mb-2">Access Key ID</label>
                                <input type="text"
                                       id="aws_access_key_id"
                                       name="aws_access_key_id"
                                       value="{{ old('aws_access_key_id') }}"
                                       placeholder="Your access key"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div>
                                <label for="aws_secret_access_key" class="block text-sm font-medium text-gray-700 mb-2">Secret Access Key</label>
                                <input type="password"
                                       id="aws_secret_access_key"
                                       name="aws_secret_access_key"
                                       value="{{ old('aws_secret_access_key') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div>
                                <label for="aws_region" class="block text-sm font-medium text-gray-700 mb-2">Region</label>
                                <input type="text"
                                       id="aws_region"
                                       name="aws_region"
                                       value="{{ old('aws_region', 'us-east-1') }}"
                                       placeholder="us-east-1, nyc3, auto, etc."
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <p class="mt-1 text-xs text-gray-500">e.g., us-east-1 (AWS), nyc3 (DO), auto (R2)</p>
                            </div>

                            <div>
                                <label for="aws_bucket" class="block text-sm font-medium text-gray-700 mb-2">Bucket Name</label>
                                <input type="text"
                                       id="aws_bucket"
                                       name="aws_bucket"
                                       value="{{ old('aws_bucket') }}"
                                       placeholder="my-bucket"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <p class="mt-1 text-xs text-gray-500">Leave empty to use local storage</p>
                            </div>

                            <div id="endpoint-field" class="md:col-span-2 hidden">
                                <label for="aws_endpoint" class="block text-sm font-medium text-gray-700 mb-2">Custom Endpoint URL</label>
                                <input type="url"
                                       id="aws_endpoint"
                                       name="aws_endpoint"
                                       value="{{ old('aws_endpoint') }}"
                                       placeholder="https://nyc3.digitaloceanspaces.com"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <p class="mt-1 text-xs text-gray-500">Required for non-AWS providers</p>
                            </div>
                            </div><!-- end cloud-storage-fields -->

                            <div id="local-storage-info" class="md:col-span-2 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                                <div class="flex items-center text-gray-700">
                                    <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span>Files will be stored on the server's local filesystem.</span>
                                </div>
                                <p class="mt-2 text-sm text-gray-500">This is the simplest option and works well for most sites. You can switch to cloud storage later by updating your .env file.</p>
                            </div>
                        </div>

                        <div id="ses-tip" class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded text-blue-800 text-sm hidden">
                            <strong>Tip:</strong> If you selected "Amazon SES" as your mailer above, these credentials will be used for email delivery.
                            Make sure your SES account is out of sandbox mode for production use.
                        </div>

                        <div id="provider-tip" class="mt-4 p-3 bg-gray-50 border border-gray-200 rounded text-gray-700 text-sm hidden">
                            <strong>Note:</strong> <span id="provider-tip-text"></span>
                        </div>
                    </div>
                </div>

            </div>

            <div class="mt-8 flex justify-between">
                <a href="{{ route('installer.environment') }}" 
                   class="bg-gray-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-gray-700 transition-colors inline-flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Back
                </a>
                
                <button type="submit" 
                        id="install-button"
                        class="bg-green-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-green-700 transition-colors inline-flex items-center">
                    <span id="install-text">Install TallCMS</span>
                    <svg id="install-icon" class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    <svg id="install-spinner" class="w-4 h-4 ml-2 animate-spin hidden" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // State tracking
    let dbConnectionTested = false;
    let dbConnectionValid = false;
    let passwordsMatch = false;
    
    // Elements
    const form = document.getElementById('installation-form');
    const installButton = document.getElementById('install-button');
    const installText = document.getElementById('install-text');
    const installIcon = document.getElementById('install-icon');
    const installSpinner = document.getElementById('install-spinner');
    
    // Password elements
    const adminPassword = document.getElementById('admin_password');
    const adminPasswordConfirmation = document.getElementById('admin_password_confirmation');
    
    // Database elements
    const testDbButton = document.getElementById('test-db-connection');
    const dbTestResult = document.getElementById('db-test-result');
    const dbFields = ['db_host', 'db_port', 'db_database', 'db_username', 'db_password'];
    
    // Mail settings
    const toggleMailButton = document.getElementById('toggle-mail-settings');
    const mailSettings = document.getElementById('mail-settings');
    const mailMailer = document.getElementById('mail_mailer');
    const smtpSettings = document.getElementById('smtp-settings');

    // Initialize install button state
    updateInstallButtonState();

    // Password confirmation validation
    function validatePasswords() {
        const password = adminPassword.value;
        const confirmation = adminPasswordConfirmation.value;
        
        // Clear previous validation messages
        clearPasswordValidation();
        
        if (password && confirmation) {
            passwordsMatch = password === confirmation;
            
            if (!passwordsMatch) {
                showPasswordError('Passwords do not match');
            } else {
                showPasswordSuccess('Passwords match');
            }
        } else {
            passwordsMatch = false;
        }
        
        updateInstallButtonState();
    }
    
    function clearPasswordValidation() {
        const existingError = document.getElementById('password-match-error');
        const existingSuccess = document.getElementById('password-match-success');
        if (existingError) existingError.remove();
        if (existingSuccess) existingSuccess.remove();
    }
    
    function showPasswordError(message) {
        const errorDiv = document.createElement('div');
        errorDiv.id = 'password-match-error';
        errorDiv.className = 'mt-1 text-sm text-red-600';
        errorDiv.textContent = message;
        adminPasswordConfirmation.parentNode.appendChild(errorDiv);
    }
    
    function showPasswordSuccess(message) {
        const successDiv = document.createElement('div');
        successDiv.id = 'password-match-success';
        successDiv.className = 'mt-1 text-sm text-green-600';
        successDiv.textContent = '✓ ' + message;
        adminPasswordConfirmation.parentNode.appendChild(successDiv);
    }

    // Database connection validation
    function markDatabaseAsUntested() {
        dbConnectionTested = false;
        dbConnectionValid = false;
        
        // Clear any existing results
        dbTestResult.classList.add('hidden');
        
        // Show warning that database needs to be tested
        if (!document.getElementById('db-test-warning')) {
            const warningDiv = document.createElement('div');
            warningDiv.id = 'db-test-warning';
            warningDiv.className = 'mt-2 p-3 bg-yellow-50 border border-yellow-200 rounded text-yellow-800 text-sm';
            warningDiv.textContent = '⚠ Database configuration changed. Please test connection before installing.';
            testDbButton.parentNode.appendChild(warningDiv);
        }
        
        updateInstallButtonState();
    }
    
    function clearDatabaseWarning() {
        const warning = document.getElementById('db-test-warning');
        if (warning) warning.remove();
    }

    // Update install button state
    function updateInstallButtonState() {
        const canInstall = passwordsMatch && dbConnectionTested && dbConnectionValid;
        
        installButton.disabled = !canInstall;
        
        if (!canInstall) {
            installButton.className = 'bg-gray-400 text-white px-6 py-3 rounded-lg font-medium cursor-not-allowed inline-flex items-center';
            
            let reason = '';
            if (!passwordsMatch) {
                reason = 'Passwords must match';
            } else if (!dbConnectionTested) {
                reason = 'Database connection must be tested';
            } else if (!dbConnectionValid) {
                reason = 'Database connection must be successful';
            }
            
            installButton.title = reason;
        } else {
            installButton.className = 'bg-green-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-green-700 transition-colors inline-flex items-center';
            installButton.title = '';
        }
    }

    // Event listeners for password validation
    adminPassword.addEventListener('input', validatePasswords);
    adminPasswordConfirmation.addEventListener('input', validatePasswords);

    // Event listeners for database fields
    dbFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('input', markDatabaseAsUntested);
        }
    });

    // Toggle mail settings
    toggleMailButton.addEventListener('click', function() {
        mailSettings.classList.toggle('hidden');
        if (mailSettings.classList.contains('hidden')) {
            toggleMailButton.textContent = 'Configure Mail';
        } else {
            toggleMailButton.textContent = 'Hide Mail Settings';
        }
    });

    // Show/hide SMTP settings based on mailer selection
    mailMailer.addEventListener('change', function() {
        if (this.value === 'smtp') {
            smtpSettings.classList.remove('hidden');
        } else {
            smtpSettings.classList.add('hidden');
        }

        // If SES is selected, expand cloud storage settings and set provider to AWS
        if (this.value === 'ses') {
            awsSettings.classList.remove('hidden');
            toggleAwsButton.textContent = 'Hide Cloud Storage';
            s3Provider.value = 'aws';
            updateProviderUI('aws');
        }
    });

    // Toggle cloud storage settings
    const toggleAwsButton = document.getElementById('toggle-aws-settings');
    const awsSettings = document.getElementById('aws-settings');
    const s3Provider = document.getElementById('s3_provider');
    const endpointField = document.getElementById('endpoint-field');
    const cloudStorageFields = document.getElementById('cloud-storage-fields');
    const localStorageInfo = document.getElementById('local-storage-info');
    const sesTip = document.getElementById('ses-tip');
    const providerTip = document.getElementById('provider-tip');
    const providerTipText = document.getElementById('provider-tip-text');

    // Provider configurations
    const providerConfig = {
        local: {
            isLocal: true,
            needsEndpoint: false,
            showSesTip: false,
            tip: null
        },
        aws: {
            isLocal: false,
            needsEndpoint: false,
            showSesTip: true,
            tip: null
        },
        digitalocean: {
            isLocal: false,
            needsEndpoint: true,
            showSesTip: false,
            tip: 'DigitalOcean Spaces endpoint format: https://{region}.digitaloceanspaces.com (e.g., https://nyc3.digitaloceanspaces.com)'
        },
        minio: {
            isLocal: false,
            needsEndpoint: true,
            showSesTip: false,
            tip: 'Enter your MinIO server endpoint URL (e.g., https://minio.example.com:9000)'
        },
        backblaze: {
            isLocal: false,
            needsEndpoint: true,
            showSesTip: false,
            tip: 'Backblaze B2 S3-compatible endpoint format: https://s3.{region}.backblazeb2.com'
        },
        cloudflare: {
            isLocal: false,
            needsEndpoint: true,
            showSesTip: false,
            tip: 'Cloudflare R2 endpoint format: https://{account_id}.r2.cloudflarestorage.com. Use "auto" as the region.'
        },
        wasabi: {
            isLocal: false,
            needsEndpoint: true,
            showSesTip: false,
            tip: 'Wasabi endpoint format: https://s3.{region}.wasabisys.com (e.g., https://s3.us-east-1.wasabisys.com)'
        },
        custom: {
            isLocal: false,
            needsEndpoint: true,
            showSesTip: false,
            tip: 'Enter the S3-compatible endpoint URL for your storage provider.'
        }
    };

    function updateProviderUI(provider) {
        const config = providerConfig[provider] || providerConfig.custom;

        // Show/hide local storage info vs cloud storage fields
        if (config.isLocal) {
            localStorageInfo.classList.remove('hidden');
            cloudStorageFields.classList.add('hidden');
            endpointField.classList.add('hidden');
            sesTip.classList.add('hidden');
            providerTip.classList.add('hidden');
            return;
        }

        // Cloud storage selected
        localStorageInfo.classList.add('hidden');
        cloudStorageFields.classList.remove('hidden');

        // Show/hide endpoint field
        if (config.needsEndpoint) {
            endpointField.classList.remove('hidden');
        } else {
            endpointField.classList.add('hidden');
        }

        // Show/hide SES tip
        if (config.showSesTip) {
            sesTip.classList.remove('hidden');
        } else {
            sesTip.classList.add('hidden');
        }

        // Show/hide provider-specific tip
        if (config.tip) {
            providerTipText.textContent = config.tip;
            providerTip.classList.remove('hidden');
        } else {
            providerTip.classList.add('hidden');
        }
    }

    // Handle provider change
    s3Provider.addEventListener('change', function() {
        updateProviderUI(this.value);
    });

    // Initialize provider UI
    updateProviderUI(s3Provider.value);

    toggleAwsButton.addEventListener('click', function() {
        awsSettings.classList.toggle('hidden');
        if (awsSettings.classList.contains('hidden')) {
            toggleAwsButton.textContent = 'Configure Cloud Storage';
        } else {
            toggleAwsButton.textContent = 'Hide Cloud Storage';
        }
    });

    // Test database connection
    testDbButton.addEventListener('click', function() {
        const button = this;
        const originalText = button.textContent;
        
        button.textContent = 'Testing...';
        button.disabled = true;
        clearDatabaseWarning();
        
        const formData = new FormData();
        formData.append('db_host', document.getElementById('db_host').value);
        formData.append('db_port', document.getElementById('db_port').value);
        formData.append('db_database', document.getElementById('db_database').value);
        formData.append('db_username', document.getElementById('db_username').value);
        formData.append('db_password', document.getElementById('db_password').value);
        
        // Add CSRF token
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        formData.append('_token', token);

        fetch('{{ route("installer.test-database") }}', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            dbTestResult.classList.remove('hidden');
            dbConnectionTested = true;
            dbConnectionValid = data.success;
            
            if (data.success) {
                dbTestResult.className = 'mt-2 p-3 bg-green-50 border border-green-200 rounded text-green-800 text-sm';
                dbTestResult.textContent = '✓ ' + data.message;
            } else {
                dbTestResult.className = 'mt-2 p-3 bg-red-50 border border-red-200 rounded text-red-800 text-sm';
                dbTestResult.textContent = '✗ ' + data.message;
            }
            
            updateInstallButtonState();
        })
        .catch(error => {
            dbTestResult.classList.remove('hidden');
            dbTestResult.className = 'mt-2 p-3 bg-red-50 border border-red-200 rounded text-red-800 text-sm';
            dbTestResult.textContent = '✗ Connection test failed';
            dbConnectionTested = true;
            dbConnectionValid = false;
            updateInstallButtonState();
        })
        .finally(() => {
            button.textContent = originalText;
            button.disabled = false;
        });
    });

    // Handle form submission
    form.addEventListener('submit', function(e) {
        // Double-check validation before submission
        if (!passwordsMatch) {
            e.preventDefault();
            alert('Passwords do not match. Please check your admin password fields.');
            return false;
        }
        
        if (!dbConnectionTested || !dbConnectionValid) {
            e.preventDefault();
            alert('Database connection must be tested and successful before installation.');
            return false;
        }
        
        // If validation passes, proceed with installation
        installButton.disabled = true;
        installText.textContent = 'Installing...';
        installIcon.classList.add('hidden');
        installSpinner.classList.remove('hidden');
    });
    
    // Initial validation check
    validatePasswords();
});
</script>
@endsection