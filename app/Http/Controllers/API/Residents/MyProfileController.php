<?php

namespace App\Http\Controllers\API\Residents;

use App\Http\Controllers\Controller;
use App\Models\Profiles\ResidentDataCorrection;
use App\Support\ResidentCompleteness;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * "Data saya": a warga reading what their RT holds about them, and saying
 * when something is wrong.
 *
 * Scoped to the caller, so there is no id to tamper with. The warga never
 * edits the record; a correction is a request the RT reviews.
 */
class MyProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->residentProfile()
            ->with(['organization', 'neighborhoodAssociation', 'citizensAssociation'])
            ->first();

        if (! $profile) {
            return response()->json(['message' => 'Belum ada data warga untuk akun ini.'], 404);
        }

        return response()->json([
            'data' => [
                'name' => $user->name,
                'phone' => $user->phone,
                'address_line' => $profile->address_line,
                'rt' => $profile->neighborhoodAssociation?->name,
                'rw' => $profile->citizensAssociation?->name,
                'organization' => $profile->organization?->name,
                'family_card' => ResidentCompleteness::maskFamilyCard($profile->family_card_number),
                'is_head_family' => (bool) $profile->is_head_family,
                'family_members_count' => $profile->family_members_count,
                'birth_date' => $profile->birth_date?->toDateString(),
                'missing' => ResidentCompleteness::missing($user, $profile),
            ],
            'corrections' => ResidentDataCorrection::query()
                ->where('user_id', $user->id)
                ->latest('id')
                ->limit(10)
                ->get()
                ->map(fn (ResidentDataCorrection $c) => $this->correction($c))
                ->values(),
            // The server decides what may be corrected; the app only renders it.
            'fields' => ResidentDataCorrection::FIELDS,
            'checks' => ResidentCompleteness::CHECKS,
        ]);
    }

    public function storeCorrection(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->residentProfile;

        if (! $profile || ! $profile->organization_id) {
            return response()->json(['message' => 'Belum ada data warga untuk akun ini.'], 404);
        }

        $validated = $request->validate([
            'field' => ['required', Rule::in(array_keys(ResidentDataCorrection::FIELDS))],
            'proposed_value' => ['nullable', 'string', 'max:255', 'required_unless:field,other'],
            'note' => ['nullable', 'string', 'max:1000', 'required_if:field,other'],
        ], [
            'proposed_value.required_unless' => 'Tulis isian yang benar.',
            'note.required_if' => 'Jelaskan apa yang perlu diperbaiki.',
        ]);

        // One open request per field: a second tap on "kirim" should not
        // queue the same question twice for the RT.
        $alreadyOpen = ResidentDataCorrection::query()
            ->where('user_id', $user->id)
            ->where('field', $validated['field'])
            ->where('status', ResidentDataCorrection::STATUS_PENDING)
            ->exists();

        if ($alreadyOpen) {
            return response()->json(
                ['message' => 'Permintaan untuk isian ini masih menunggu ditinjau pengurus RT.'],
                409,
            );
        }

        $correction = ResidentDataCorrection::create([
            'user_id' => $user->id,
            'organization_id' => $profile->organization_id,
            'field' => $validated['field'],
            'proposed_value' => $validated['proposed_value'] ?? null,
            'note' => $validated['note'] ?? null,
        ]);

        return response()->json(['data' => $this->correction($correction)], 201);
    }

    /**
     * @return array<string, mixed>
     */
    protected function correction(ResidentDataCorrection $correction): array
    {
        return [
            'id' => $correction->id,
            'field' => $correction->field,
            'field_label' => ResidentDataCorrection::FIELDS[$correction->field] ?? $correction->field,
            'proposed_value' => $correction->proposed_value,
            'note' => $correction->note,
            'status' => $correction->status,
            'created_at' => $correction->created_at?->toIso8601String(),
            'reviewed_at' => $correction->reviewed_at?->toIso8601String(),
        ];
    }
}
