<div class="collapse collapse-arrow bg-base-200 mt-6">
    <input type="checkbox" />
    <div class="collapse-title font-medium">Référencement (SEO)</div>
    <div class="collapse-content flex flex-col gap-4">
        <input type="text" name="seo[meta_title]"
               value="{{ old('seo.meta_title', $model?->seo?->meta_title) }}"
               placeholder="Titre SEO"
               class="input input-bordered w-full" />

        <textarea name="seo[meta_description]" rows="3"
                  class="textarea textarea-bordered w-full"
                  placeholder="Description SEO">{{ old('seo.meta_description', $model?->seo?->meta_description) }}</textarea>

        <div>
            <input type="url" name="seo[canonical_url]"
                   value="{{ old('seo.canonical_url', $model?->seo?->canonical_url) }}"
                   placeholder="URL canonique (optionnelle, http ou https)"
                   class="input input-bordered w-full" />
            @error('seo.canonical_url') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>

        <label class="label cursor-pointer justify-start gap-3">
            <input type="hidden" name="seo[no_index]" value="0">
            <input type="checkbox" name="seo[no_index]" value="1" class="checkbox"
                   @checked(old('seo.no_index', $model?->seo?->no_index))>
            <span class="label-text">Ne pas indexer (noindex, retirée du sitemap)</span>
        </label>
    </div>
</div>