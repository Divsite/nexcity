<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Announcements — the thing an RT or a mosque says out loud.
 *
 * Today this happens over a loudspeaker and in a WhatsApp group: a death in
 * the neighbourhood, kerja bakti on Sunday, the qurban programme opening. Both
 * channels are ephemeral — miss the announcement and it is gone, and neither
 * has any notion of who was allowed to hear it.
 *
 * Two tables rather than one enum column, because the kinds of announcement a
 * mosque makes are not a fixed list a developer should own. A takmir who wants
 * a "Tabungan Qurban" category should be able to add one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcement_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->nullable();

            // An RT does not announce jadwal kajian; a mosque does not announce
            // siskamling. Showing every category to both would make the form a
            // list of things that do not apply.
            $table->enum('applies_to', ['rt', 'mosque', 'both'])->default('both');

            // What the form should suggest — an author may still override it,
            // except where the model forbids it (see Announcement::audience).
            $table->enum('default_audience', ['public', 'members', 'staff'])
                ->default('members');

            // Urgent categories are the ones worth waking someone for. A death
            // notice is; a financial recap is not.
            $table->boolean('is_urgent')->default(false);

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('announcement_category_id')->constrained();

            $table->string('title');
            $table->text('body');

            /**
             * Who may read it.
             *
             *  public  — anyone, including people who have never heard of this
             *            organization. Only a mosque may use it: mosque
             *            programmes are open by design, an RT's affairs are not.
             *  members — people who belong to this organization.
             *  staff   — pengurus only; coordination, not news.
             */
            $table->enum('audience', ['public', 'members', 'staff'])->default('members');

            // Kerja bakti, sholat jenazah and kajian all answer "when and
            // where". Nullable because a financial recap answers neither.
            $table->dateTime('event_at')->nullable();
            $table->string('event_location')->nullable();

            // Stays at the top of the feed while it matters.
            $table->boolean('is_pinned')->default(false);

            // Null means draft. An announcement is not visible because someone
            // saved it — it is visible because someone published it.
            $table->timestamp('published_at')->nullable();

            // Sunday's kerja bakti is not news on Monday.
            $table->timestamp('expires_at')->nullable();

            // Optional link to the thing being announced: a qurban programme, a
            // distribution, a dues scheme. Lets the reader tap through instead
            // of being told to "check the app".
            $table->nullableMorphs('announceable');

            $table->string('cover_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // The feed query: this organization, published, newest first.
            $table->index(['organization_id', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('announcement_categories');
    }
};
