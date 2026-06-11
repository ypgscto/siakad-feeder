<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\ProdiAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProdiAccessServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_prodi_user_defaults_to_assigned_prodi_not_first_in_list(): void
    {
        $user = User::factory()->create([
            'role' => 'prodi',
            'prodi_id' => 'ILMU KEPERAWATAN',
        ]);

        $this->actingAs($user);

        $master = [
            'prodi' => [
                ['id' => 'D3 Kebidanan', 'nama' => 'D3 Kebidanan'],
                ['id' => 'ILMU KEPERAWATAN', 'nama' => 'S1 Keperawatan'],
            ],
        ];

        $resolver = app(\App\Services\AcademicFilterResolver::class);
        $scoped = $resolver->resolveScoped(request(), $master);

        $this->assertSame('ILMU KEPERAWATAN', $scoped['filters']['prodi_id']);
        $this->assertCount(1, $scoped['master']['prodi']);
        $this->assertSame('S1 Keperawatan', $scoped['master']['prodi'][0]['nama']);
    }

    public function test_prodi_user_can_match_assigned_by_display_name(): void
    {
        $service = app(ProdiAccessService::class);

        $rows = [
            ['id' => 'ILMU KEPERAWATAN', 'nama' => 'S1 Keperawatan'],
        ];

        $this->assertSame('ILMU KEPERAWATAN', $service->canonicalProdiId('S1 Keperawatan', $rows));
    }

    public function test_prodi_user_is_blocked_from_other_prodi(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        app(ProdiAccessService::class)->enforceProdiId(
            'ILMU KEPERAWATAN',
            'D3 Kebidanan',
            [
                ['id' => 'ILMU KEPERAWATAN', 'nama' => 'S1 Keperawatan'],
                ['id' => 'D3 Kebidanan', 'nama' => 'D3 Kebidanan'],
            ],
        );
    }
}
