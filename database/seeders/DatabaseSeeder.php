<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(DocumentTypeSeeder::class);

        // User::factory(10)->create();

        $admin = User::firstOrCreate(
            ['email' => 'admin@dokuflow.id'],
            [
                'name' => 'Administrator',
                'password' => bcrypt('Admin123!'),
                'system_role' => 'admin',
                'is_active' => true,
            ]
        );

        if ($admin && !$admin->hasSignature()) {
            $dir = storage_path('app/public/signatures');
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
            $filePath = 'signatures/' . $admin->id . '_admin.png';
            $fullPath = storage_path('app/public/' . $filePath);

            if (function_exists('imagecreatetruecolor')) {
                $img = imagecreatetruecolor(400, 400);
                imagesavealpha($img, true);
                $trans = imagecolorallocatealpha($img, 0, 0, 0, 127);
                imagefill($img, 0, 0, $trans);
                $color = imagecolorallocate($img, 30, 50, 150);
                imagesetthickness($img, 4);
                imageline($img, 50, 200, 350, 200, $color);
                imagepng($img, $fullPath);
                imagedestroy($img);
            } else {
                file_put_contents($fullPath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='));
            }

            \App\Models\Signature::updateOrCreate(
                ['user_id' => $admin->id],
                ['file_path' => $filePath, 'signature_type' => 'draw']
            );
        }
    }
}
