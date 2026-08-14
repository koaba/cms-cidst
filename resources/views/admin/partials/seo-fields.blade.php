<div class="collapse collapse-arrow bg-base-200 mt-6">
    <input type="checkbox" />
    <div class="collapse-title font-medium">Référencement (SEO)</div>
    <div class="collapse-content flex flex-col gap-4">
        <input type="text" name="seo[meta_title]"
               value="{{ old('seo.meta_title', $model->seo?->meta_title) }}"
               placeholder="Titre SEO"
               class="input input-bordered w-full" />

        <textarea name="seo[meta_description]" rows="3"
                  class="textarea textarea-bordered w-full"
                  placeholder="Description SEO">{{ old('seo.meta_description', $model->seo?->meta_description) }}</textarea>
    </div>
</div>