<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateArticleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->is_admin ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'url' => ['required', 'url', Rule::unique('articles')->ignore($this->article->id)],
            'image_url' => ['nullable', 'url'],
            'author' => ['nullable', 'string', 'max:255'],
            'news_source_id' => ['required', 'exists:news_sources,id'],
            'published_at' => ['nullable', 'date'],
            'is_featured' => ['boolean'],
            'tags' => ['array'],
            'tags.*' => ['exists:tags,id'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'url.unique' => 'An article with this URL already exists.',
            'news_source_id.exists' => 'The selected news source is invalid.',
            'tags.*.exists' => 'One or more selected tags are invalid.',
        ];
    }
}
