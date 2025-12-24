<?php

namespace App\Filament\Resources\TallcmsContactSubmissions\Pages;

use App\Filament\Resources\TallcmsContactSubmissions\TallcmsContactSubmissionResource;
use Filament\Resources\Pages\ListRecords;

class ListTallcmsContactSubmissions extends ListRecords
{
    protected static string $resource = TallcmsContactSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action - submissions come from the frontend
        ];
    }
}
