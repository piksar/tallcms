<?php

namespace TallCms\Cms\Filament\Concerns;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use TallCms\Cms\Services\LocaleRegistry;

/**
 * Provides auto-population of translations from the default locale.
 *
 * When switching to a locale with no translations, content is automatically
 * copied from the default locale to provide a starting point for translation.
 *
 * Also provides a "Copy from default" action for manually re-copying.
 *
 * IMPORTANT: This trait must be used alongside the LaraZeus Translatable trait
 * and must override its updatedActiveLocale method using:
 *
 * use Translatable, HasTranslationCopying {
 *     HasTranslationCopying::updatedActiveLocale insteadof Translatable;
 * }
 */
trait HasTranslationCopying
{
    /**
     * Track if we just copied content to show notification after form fills.
     */
    protected bool $justCopiedFromDefault = false;

    /**
     * Override the locale switcher update to auto-populate empty translations.
     */
    public function updatedActiveLocale(): void
    {
        if (filament('spatie-translatable')->getPersistLocale()) {
            session()->put('spatie_translatable_active_locale', $this->activeLocale);
        }

        if (blank($this->oldActiveLocale)) {
            return;
        }

        $this->resetValidation();

        $translatableAttributes = static::getResource()::getTranslatableAttributes();

        // Save current locale data before switching
        $this->otherLocaleData[$this->oldActiveLocale] = Arr::only(
            $this->form->getState(),
            $translatableAttributes
        );

        // Get the data for the new locale
        $newLocaleData = $this->otherLocaleData[$this->activeLocale] ?? [];

        // Check if the new locale is empty (no translations)
        $isEmpty = $this->isLocaleDataEmpty($newLocaleData, $translatableAttributes);

        // Get default locale from LocaleRegistry or fall back to resource default
        $defaultLocale = $this->getCopySourceLocale();

        // If empty and not the default locale, copy from default
        if ($isEmpty && $this->activeLocale !== $defaultLocale) {
            $defaultData = $this->otherLocaleData[$defaultLocale]
                ?? $this->getDefaultLocaleDataFromRecord($translatableAttributes, $defaultLocale);

            if (! $this->isLocaleDataEmpty($defaultData, $translatableAttributes)) {
                $newLocaleData = $defaultData;
                $this->justCopiedFromDefault = true;
            }
        }

        try {
            // Fill the form with the new locale data
            $this->form->fill([
                ...Arr::except(
                    $this->form->getState(),
                    $translatableAttributes
                ),
                ...$newLocaleData,
            ]);

            unset($this->otherLocaleData[$this->activeLocale]);
        } catch (ValidationException $exception) {
            $this->activeLocale = $this->oldActiveLocale;
            throw $exception;
        }

        // Show notification if we copied content
        if ($this->justCopiedFromDefault) {
            $this->justCopiedFromDefault = false;
            $defaultLabel = $this->getLocaleLabelForCopy($defaultLocale);

            Notification::make()
                ->info()
                ->title(__('tallcms::ui.n_content_copied_from', ['locale' => $defaultLabel]))
                ->body(__('tallcms::ui.t_review_and_translate_the_content_for_this_locale'))
                ->persistent()
                ->send();
        }
    }

    /**
     * Check if locale data is effectively empty.
     */
    protected function isLocaleDataEmpty(array $data, array $translatableAttributes): bool
    {
        if (empty($data)) {
            return true;
        }

        foreach ($translatableAttributes as $attribute) {
            $value = $data[$attribute] ?? null;

            // Check for non-empty content
            if ($value !== null && $value !== '' && $value !== []) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get translatable field data directly from the record for a specific locale.
     */
    protected function getDefaultLocaleDataFromRecord(array $translatableAttributes, string $locale): array
    {
        $record = $this->getRecord();
        $data = [];

        foreach ($translatableAttributes as $attribute) {
            $data[$attribute] = $record->getTranslation($attribute, $locale, false);
        }

        return $data;
    }

    /**
     * Get the source locale for copying (default locale).
     */
    protected function getCopySourceLocale(): string
    {
        if (tallcms_i18n_enabled()) {
            return app(LocaleRegistry::class)->getDefaultLocale();
        }

        return static::getResource()::getDefaultTranslatableLocale();
    }

    /**
     * Get a human-readable label for a locale.
     */
    protected function getLocaleLabelForCopy(string $locale): string
    {
        if (tallcms_i18n_enabled()) {
            return tallcms_locale_label($locale);
        }

        return strtoupper($locale);
    }

    /**
     * Create the "Copy from default" header action.
     */
    protected function getCopyFromDefaultAction(): Action
    {
        $defaultLocale = $this->getCopySourceLocale();
        $defaultLabel = $this->getLocaleLabelForCopy($defaultLocale);

        return Action::make('copyFromDefault')
            ->label(__('tallcms::fields.copy_from', ['locale' => $defaultLabel]))
            ->icon('heroicon-o-document-duplicate')
            ->color('gray')
            ->visible(fn () => tallcms_i18n_enabled()
                && $this->activeLocale !== $defaultLocale
                && count($this->getTranslatableLocales()) > 1)
            ->requiresConfirmation()
            ->modalHeading(__('tallcms::ui.n_copy_content_from', ['locale' => $defaultLabel]))
            ->modalDescription(__('tallcms::ui.n_copy_overwrite_warning', ['locale' => $defaultLabel]))
            ->modalSubmitActionLabel(__('tallcms::ui.t_copy_overwrite'))
            ->action(function () use ($defaultLocale, $defaultLabel) {
                $translatableAttributes = static::getResource()::getTranslatableAttributes();

                // Get default locale data
                $defaultData = $this->otherLocaleData[$defaultLocale]
                    ?? $this->getDefaultLocaleDataFromRecord($translatableAttributes, $defaultLocale);

                // Fill the form with default locale data
                $this->form->fill([
                    ...Arr::except(
                        $this->form->getState(),
                        $translatableAttributes
                    ),
                    ...$defaultData,
                ]);

                Notification::make()
                    ->success()
                    ->title(__('tallcms::ui.n_content_copied_from', ['locale' => $defaultLabel]))
                    ->body(__('tallcms::ui.n_copy_success_body', ['locale' => $defaultLabel]))
                    ->send();
            });
    }
}
