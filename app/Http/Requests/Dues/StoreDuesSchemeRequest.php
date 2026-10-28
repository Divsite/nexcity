<?php

namespace App\Http\Requests\Dues;

use App\Models\Dues\RtDuesScheme;
use App\Services\Menus\MenuContextResolver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Guards the one form that issues money owed by real neighbours.
 *
 * Two rules matter more than the rest, both because of what a mistake costs:
 *
 *   - **No future years.** Opening 2027 today would put twelve bills on every
 *     resident's phone for a year that has not started, and they would read as
 *     arrears. There is no reason to open a year early and every reason not to.
 *
 *   - **No duplicate scheme.** "Iuran Bulanan 2026" exists once. A second one
 *     would double every household's bill for the year, and nobody would notice
 *     until a resident complained.
 */
class StoreDuesSchemeRequest extends FormRequest
{
    /** Authorization is handled by `capability:add-rt-dues` on the route. */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in(RtDuesScheme::TYPES)],

            'year' => [
                'required',
                'integer',
                // Backfilling last year is legitimate — an RT catching up on
                // records. Opening next year is not.
                'min:' . (now()->year - 5),
                'max:' . now()->year,
                Rule::unique('rt_dues_schemes', 'year')
                    ->where('organization_id', $this->organizationId())
                    ->where('name', $this->input('name')),
            ],

            'programs' => ['nullable', 'string', 'max:2000'],
            'due_date' => ['nullable', 'date'],

            // A scheme with no rate bills nobody, which reads as a broken
            // button rather than an empty form.
            'rates' => ['required', 'array', 'min:1', 'max:10'],
            'rates.*.label' => ['required', 'string', 'max:60'],
            'rates.*.tier' => ['nullable', 'string', 'max:40'],
            'rates.*.amount' => ['required', 'numeric', 'min:0', 'max:100000000'],

            'default_rate' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'year.max' => __('messages.dues_year_future'),
            'year.unique' => __('messages.dues_scheme_duplicate'),
            'rates.required' => __('messages.dues_rates_required'),
            'rates.min' => __('messages.dues_rates_required'),
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $rates = (array) $this->input('rates', []);

            // The radio button must point at a rate that exists, or residents
            // with no golongan would be billed nothing at all.
            if (! array_key_exists((int) $this->input('default_rate'), array_values($rates))) {
                $validator->errors()->add('default_rate', __('messages.dues_default_rate_required'));
            }

            // Two rates for the same golongan is not a state anyone could
            // resolve later — which of the two applies?
            $tiers = collect($rates)->pluck('tier')->map(fn ($t) => $t ?: null);

            if ($tiers->count() !== $tiers->unique()->count()) {
                $validator->errors()->add('rates', __('messages.dues_rates_duplicate_tier'));
            }
        });
    }

    /**
     * The RT the officer is acting in.
     *
     * Resolved here rather than taken from the form: uniqueness must be checked
     * against the caller's own RT, and a scheme name is only unique within one.
     * Two RTs both running "Iuran Bulanan 2026" is normal.
     */
    public function organizationId(): ?int
    {
        [, $organization] = app(MenuContextResolver::class)->resolve($this->user());

        return $organization?->id;
    }
}
