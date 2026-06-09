<?php

declare(strict_types=1);

namespace App\Services\Traits;

use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\HtmlString;

trait HasControlPanelChangelogsTab
{
    protected function controlPanelChangelogsTab(): Tab
    {
        return Tab::make('updates')
            ->label(arabic_text('تحديثات'))
            ->key('updates')
            ->icon('material-design.update')
            ->schema([
                Text::make(fn (): HtmlString => $this->changelogsMarkdown())
                    ->extraAttributes(['class' => 'block w-full']),
            ]);
    }

    private function changelogsMarkdown(): HtmlString
    {
        $markdownPath = public_path('docs/updates/changelogs.md');
        $cacheKey = implode('|', [
            'control-panel:changelogs-markdown',
            (string) config('nativephp-internal.running'),
            (string) config('nativephp-internal.platform'),
            File::exists($markdownPath) ? (string) File::lastModified($markdownPath) : 'missing',
        ]);

        $html = Cache::remember(
            $cacheKey,
            now()->addDay(),
            fn (): string => $this->buildChangelogsMarkdownHtml($markdownPath),
        );

        return new HtmlString($html);
    }

    private function buildChangelogsMarkdownHtml(string $markdownPath): string
    {
        if (! File::exists($markdownPath)) {
            $fallbackMessage = $this->escapeHtml(arabic_text('ملف التحديثات غير متوفر الآن.'));

            return <<<HTML
                <article class="mx-auto w-full max-w-3xl text-right leading-7">
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        {$fallbackMessage}
                    </p>
                </article>
                HTML;
        }

        $markdown = File::get($markdownPath);

        $markdown = preg_replace('/^\s*<div align="right">\s*/', '', $markdown) ?? $markdown;
        $markdown = preg_replace('/\s*<\/div>\s*$/', '', $markdown) ?? $markdown;
        $markdown = $this->rewriteChangelogMarkdownImageSources($markdown);

        $html = str($markdown)
            ->markdown()
            ->sanitizeHtml()
            ->toString();

        $html = preg_replace_callback(
            '/<a\b[^>]*href="([^"]+)"[^>]*>/i',
            function (array $matches): string {
                $tag = $matches[0];

                if (str_contains($tag, 'x-on:click.prevent=')) {
                    return $tag;
                }

                $href = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $openLinkNativeAware = htmlspecialchars(
                    open_link_native_aware($href),
                    ENT_QUOTES,
                    'UTF-8',
                );

                return rtrim(substr($tag, 0, -1))
                    .' x-on:click.prevent="'.$openLinkNativeAware.'"'
                    .' x-on:keydown.enter.prevent="'.$openLinkNativeAware.'"'
                    .' x-on:keydown.space.prevent="'.$openLinkNativeAware.'">';
            },
            $html,
        ) ?? $html;

        return <<<HTML
            <article class="mx-auto w-full max-w-3xl text-right leading-7
                [&_h2]:mt-12 [&_h2:first-child]:mt-0 [&_h2]:mb-4 [&_h2]:border-b [&_h2]:border-gray-200 [&_h2]:pb-2 [&_h2]:text-xl [&_h2]:font-bold [&_h2]:text-gray-800 dark:[&_h2]:border-gray-700 dark:[&_h2]:text-gray-100
                [&_h3]:mt-3 [&_h3]:mb-1.5 [&_h3]:text-base [&_h3]:font-semibold [&_h3]:text-gray-700 dark:[&_h3]:text-gray-200
                [&_p]:my-1 [&_p]:text-sm [&_p]:text-gray-600 dark:[&_p]:text-gray-300
                [&_ul]:my-2 [&_ul]:list-disc [&_ul]:list-inside [&_ul]:space-y-1
                [&_li]:text-sm [&_li]:text-gray-700 dark:[&_li]:text-gray-200
                [&_a]:font-medium [&_a]:text-info-600 hover:[&_a]:text-info-700 dark:[&_a]:text-primary-400 dark:hover:[&_a]:text-primary-500
                [&_img]:my-4 [&_img]:mx-auto [&_img]:h-auto [&_img]:rounded-lg [&_img]:border [&_img]:border-gray-200 dark:[&_img]:border-gray-700">
                {$html}
            </article>
            HTML;
    }

    private function escapeHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }

    private function rewriteChangelogMarkdownImageSources(string $markdown): string
    {
        $baseUrl = $this->changelogImageBaseUrl();

        $markdown = preg_replace_callback(
            '/!\[([^\]]*)\]\(images\/([^)\\s]+)\)/',
            function (array $matches) use ($baseUrl): string {
                $imageSource = $baseUrl.$matches[2];

                return '!['.$matches[1].']('.$imageSource.')';
            },
            $markdown,
        ) ?? $markdown;

        $markdown = preg_replace_callback(
            '/\bsrc=(["\'])images\/([^"\']+)\1/i',
            function (array $matches) use ($baseUrl): string {
                $imageSource = $baseUrl.$matches[2];

                return 'src='.$matches[1].$imageSource.$matches[1];
            },
            $markdown,
        ) ?? $markdown;

        return str_replace('](images/', ']('.$baseUrl, $markdown);
    }

    private function changelogImageBaseUrl(): string
    {
        if (config('nativephp-internal.running')) {
            return '/_assets/docs/updates/images/';
        }

        return '/docs/updates/images/';
    }
}
