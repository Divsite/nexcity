<?php

namespace Tests\Unit\Support;

use App\Support\IndonesianPhone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class IndonesianPhoneTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function spellings(): array
    {
        return [
            'local' => ['081234567890', '6281234567890'],
            'local with spaces' => ['0812 3456 7890', '6281234567890'],
            'international' => ['+6281234567890', '6281234567890'],
            'international as the seeders write it' => ['+62 812 3456 7890', '6281234567890'],
            'without the zero' => ['81234567890', '6281234567890'],
            'with dashes' => ['62-812-3456-7890', '6281234567890'],
            // What phone($v, 'MY') made of a typed 0812…
            'the Malaysian formatting mistake' => ['+6081234567890', '6281234567890'],
        ];
    }

    #[Test]
    #[DataProvider('spellings')]
    public function every_spelling_of_one_number_normalizes_the_same(string $input, string $expected): void
    {
        $this->assertSame($expected, IndonesianPhone::normalize($input));
    }

    #[Test]
    public function a_real_malaysian_mobile_is_not_rewritten(): void
    {
        // +601… is how Malaysian mobiles start; only +608 is the mistake.
        $this->assertNull(IndonesianPhone::normalize('+60123456789'));
    }

    #[Test]
    public function landlines_and_junk_are_rejected(): void
    {
        $this->assertNull(IndonesianPhone::normalize('021 7654321'));
        $this->assertNull(IndonesianPhone::normalize('0812'));
        $this->assertNull(IndonesianPhone::normalize('bukan nomor'));
        $this->assertNull(IndonesianPhone::normalize(null));
    }
}
