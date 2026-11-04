<?php

namespace App\Actions\Distributions;

use App\Models\Distributions\DistributionRecipient;
use App\Models\Distributions\DistributionRecipientAttachment;
use App\Models\Users\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores one documentation photo against a recipient.
 *
 * **Appends.** The web form replaces every attachment when it saves, which
 * suits a single edit screen; in the field an officer takes several shots —
 * the goods, the handover, the house — and replacing on each one would leave
 * only the last. Deleting evidence of a handover because a second photo was
 * taken would be a bad way to lose an audit trail.
 *
 * Writes to the same disk and path as the web upload so both appear in the
 * same place, and a photo taken on a phone is indistinguishable from one
 * uploaded from a desk.
 */
class AttachRecipientPhoto
{
    public function handle(
        DistributionRecipient $recipient,
        UploadedFile $file,
        User $uploader,
    ): DistributionRecipientAttachment {
        $extension = $file->getClientOriginalExtension() ?: 'jpg';
        $fileName = Str::random(40) . '.' . $extension;

        Storage::disk('uploads')->putFileAs(
            DistributionRecipientAttachment::UPLOAD_PATH,
            $file,
            $fileName,
        );

        $path = DistributionRecipientAttachment::UPLOAD_PATH . '/' . $fileName;

        return DistributionRecipientAttachment::create([
            'distribution_recipient_id' => $recipient->id,
            'file_path' => $path,
            'file_name' => $fileName,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'extension' => $extension,
            'file_size' => $file->getSize(),
            'disk' => 'uploads',
            'created_by' => $uploader->id,
        ]);
    }
}
