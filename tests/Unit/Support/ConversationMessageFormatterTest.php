<?php

namespace Tests\Unit\Support;

use App\Models\Guru;
use App\Models\Kawasan;
use App\Models\Pasti;
use App\Models\User;
use App\Support\ConversationMessageFormatter;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ConversationMessageFormatterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('nama_samaran')->nullable();
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('kawasans', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('dun')->nullable();
            $table->timestamps();
        });

        Schema::create('pastis', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('kawasan_id');
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        Schema::create('gurus', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->unsignedBigInteger('pasti_id')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('avatar_path')->nullable();
            $table->boolean('is_assistant')->default(false);
            $table->string('phone')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('kursus_guru')->nullable();
            $table->date('joined_at')->nullable();
            $table->boolean('active')->default(true);
            $table->boolean('terima_anugerah')->default(false);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('gurus');
        Schema::dropIfExists('pastis');
        Schema::dropIfExists('kawasans');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_it_replaces_nama_and_pasti_tokens_using_sender_context(): void
    {
        $user = User::query()->create([
            'name' => 'Nur Fatihah',
            'nama_samaran' => 'Cikgu Fati',
            'email' => 'fati@example.test',
        ]);

        $kawasan = Kawasan::query()->create([
            'name' => 'Kawasan Sik',
        ]);

        $pasti = Pasti::query()->create([
            'kawasan_id' => $kawasan->id,
            'name' => 'PASTI Al Hikmah',
        ]);

        Guru::query()->create([
            'user_id' => $user->id,
            'pasti_id' => $pasti->id,
            'active' => true,
        ]);

        $formatted = app(ConversationMessageFormatter::class)->format(
            'Assalamualaikum, saya @nama dari @pasti.',
            $user->fresh('guru.pasti')
        );

        $this->assertSame('Assalamualaikum, saya Cikgu Fati dari PASTI Al Hikmah.', $formatted);
    }
}
