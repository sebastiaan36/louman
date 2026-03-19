<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class RegenerateThumbnails extends Command
{
    protected $signature = 'products:regenerate-thumbnails';

    protected $description = 'Regenerate product thumbnails at 600px on the longest side';

    public function handle(): int
    {
        $products = Product::whereNotNull('photo')->get();

        if ($products->isEmpty()) {
            $this->info('No products with photos found.');

            return self::SUCCESS;
        }

        $manager = new ImageManager(new Driver);
        $bar = $this->output->createProgressBar($products->count());
        $bar->start();

        $success = 0;
        $failed = 0;

        foreach ($products as $product) {
            $originalPath = $product->photo;

            if (! Storage::disk('public')->exists($originalPath)) {
                $this->newLine();
                $this->warn("Original not found: {$originalPath}");
                $failed++;
                $bar->advance();

                continue;
            }

            try {
                $contents = Storage::disk('public')->get($originalPath);
                $image = $manager->read($contents);
                $image->scaleDown(width: 600, height: 600);

                $thumbnailPath = 'products/thumbs/'.basename($originalPath);
                Storage::disk('public')->put($thumbnailPath, (string) $image->encode());

                $success++;
            } catch (\Throwable $e) {
                $this->newLine();
                $this->warn("Failed for {$originalPath}: {$e->getMessage()}");
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done. {$success} thumbnails regenerated, {$failed} failed.");

        return self::SUCCESS;
    }
}
