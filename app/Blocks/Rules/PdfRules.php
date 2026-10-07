<?php

namespace App\Blocks\Rules;

use App\Contracts\BlockRules;
use App\Contracts\DeclaresMediaFields;
use App\Models\PageBlock;
use Illuminate\Http\Request;

class PdfRules implements BlockRules, DeclaresMediaFields
{
    public function rules(bool $isCreate, ?PageBlock $block = null): array
    {
        return [
            'title' => 'nullable|string|max:255',
            'pdf_source' => 'required|in:existing,new',
            'pdf_document_id' => 'required_if:pdf_source,existing|nullable|exists:pdf_documents,id',
            'pdf_title' => 'required_if:pdf_source,new|nullable|string|max:255',
            'pdfs' => 'required_if:pdf_source,new|nullable|array|max:'.config('media.max_pdfs', 10),
            'pdfs.*' => 'mimes:pdf|max:'.config('media.max_pdf_upload_kb', 10240),
            'apply_watermark' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [];
    }

    public function casts(Request $request): array
    {
        return [];
    }

    public function mediaFields(): array
    {
        return ['pdf_source', 'pdf_document_id', 'pdf_title', 'pdfs', 'apply_watermark'];
    }
}
