<?php

namespace Tests\Unit;

use App\Support\Feeder\StudentEmailResolver;
use Tests\TestCase;

class StudentEmailResolverTest extends TestCase
{
    public function test_empty_siakad_email_uses_nim_domain(): void
    {
        $email = StudentEmailResolver::forFeeder([
            'nim' => '25222069',
            'email' => null,
        ]);

        $this->assertSame('25222069@stikes.gunungsari.id', $email);
    }

    public function test_siakad_email_is_preserved(): void
    {
        $email = StudentEmailResolver::forFeeder([
            'nim' => '25222069',
            'email' => 'Maria@Example.com',
        ]);

        $this->assertSame('maria@example.com', $email);
    }

    public function test_whitespace_only_email_uses_nim(): void
    {
        $email = StudentEmailResolver::forFeeder([
            'nim' => '25222071',
            'email' => '   ',
        ]);

        $this->assertSame('25222071@stikes.gunungsari.id', $email);
    }
}
