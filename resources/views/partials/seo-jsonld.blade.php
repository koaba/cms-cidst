@if($model instanceof \App\Models\Article)
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'NewsArticle',
    'headline' => $model->title,
    'description' => \App\Services\SeoService::description($model),
    'image' => array_filter([\App\Services\SeoService::image($model)]),
    'datePublished' => optional($model->published_at)->toIso8601String(),
    'dateModified' => optional($model->updated_at)->toIso8601String(),
    'author' => [
        '@type' => 'Organization',
        'name' => config('app.name', 'CIDST'),
    ],
    'publisher' => [
        '@type' => 'Organization',
        'name' => config('app.name', 'CIDST'),
    ],
    'mainEntityOfPage' => [
        '@type' => 'WebPage',
        '@id' => $model->publicUrl(),
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endif