<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ResizeImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:resize {--scale=0.6 : Scale factor for resizing (default: 0.6 = 60%)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resize images in Character/icon, Equipment, Trait, and TacticalSkill directories';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $scale = (float) $this->option('scale');

        $directories = [
            storage_path('app/public/Character/icon'),
            storage_path('app/public/Equipment'),
            storage_path('app/public/Trait'),
            storage_path('app/public/TacticalSkill'),
        ];

        $this->info("Starting image resize with scale factor: {$scale}");

        $totalProcessed = 0;
        $totalFailed = 0;
        $totalBacked = 0;

        foreach ($directories as $directory) {
            if (!File::exists($directory)) {
                $this->warn("Directory not found: {$directory}");
                continue;
            }

            // Create origin directory for backup
            $originDirectory = $directory . '/origin';
            if (!File::exists($originDirectory)) {
                File::makeDirectory($originDirectory, 0755, true);
                $this->info("Created backup directory: {$originDirectory}");
            }

            $this->info("\nProcessing directory: {$directory}");

            // Check if origin directory has images
            $originFiles = [];
            if (File::exists($originDirectory)) {
                $originFiles = File::files($originDirectory);
                $originFiles = array_filter($originFiles, function($file) {
                    return in_array(strtolower($file->getExtension()), ['png', 'jpg', 'jpeg', 'gif']);
                });
            }

            // Get images from main directory
            $files = File::files($directory);
            $imageFiles = array_filter($files, function($file) {
                return in_array(strtolower($file->getExtension()), ['png', 'jpg', 'jpeg', 'gif']);
            });

            // If origin has images, use those as source
            if (count($originFiles) > 0) {
                $this->info("Found " . count($originFiles) . " images in origin directory");

                foreach ($originFiles as $file) {
                    try {
                        $filename = $file->getFilename();
                        $originPath = $file->getPathname();
                        $targetPath = $directory . '/' . $filename;

                        // Resize from origin to target directory
                        // First copy to target
                        File::copy($originPath, $targetPath);

                        // Then resize the target
                        $result = $this->resizeImage($targetPath, $scale);
                        if ($result) {
                            $this->line("✓ Resized: {$filename}");
                            $totalProcessed++;
                        } else {
                            $this->error("✗ Failed: {$filename}");
                            $totalFailed++;
                        }
                    } catch (\Exception $e) {
                        $this->error("✗ Error processing {$file->getFilename()}: {$e->getMessage()}");
                        $totalFailed++;
                    }
                }
            } else {
                // No origin directory, process normally
                $this->info("Found " . count($imageFiles) . " images");

                foreach ($imageFiles as $file) {
                    try {
                        $filename = $file->getFilename();
                        $originalPath = $file->getPathname();
                        $backupPath = $originDirectory . '/' . $filename;

                        // Backup original file to origin directory if not already backed up
                        if (!File::exists($backupPath)) {
                            File::copy($originalPath, $backupPath);
                            $totalBacked++;
                        }

                        // Resize image
                        $result = $this->resizeImage($originalPath, $scale);
                        if ($result) {
                            $this->line("✓ Resized: {$filename}");
                            $totalProcessed++;
                        } else {
                            $this->error("✗ Failed: {$filename}");
                            $totalFailed++;
                        }
                    } catch (\Exception $e) {
                        $this->error("✗ Error processing {$file->getFilename()}: {$e->getMessage()}");
                        $totalFailed++;
                    }
                }
            }
        }

        $this->newLine();
        $this->info("=================================");
        $this->info("Resize completed!");
        $this->info("Backed up: {$totalBacked}");
        $this->info("Successfully resized: {$totalProcessed}");
        $this->error("Failed: {$totalFailed}");
        $this->info("=================================");

        return 0;
    }

    /**
     * Resize an image by scale factor
     *
     * @param string $imagePath
     * @param float $scale
     * @return bool
     */
    private function resizeImage(string $imagePath, float $scale): bool
    {
        $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));

        // Suppress libpng warnings for incorrect sRGB profiles
        error_reporting(E_ERROR | E_PARSE);

        // Load image based on type
        switch ($extension) {
            case 'png':
                $source = @imagecreatefrompng($imagePath);
                break;
            case 'jpg':
            case 'jpeg':
                $source = @imagecreatefromjpeg($imagePath);
                break;
            case 'gif':
                $source = @imagecreatefromgif($imagePath);
                break;
            default:
                return false;
        }

        // Restore error reporting
        error_reporting(E_ALL);

        if (!$source) {
            return false;
        }

        // Get original dimensions
        $originalWidth = imagesx($source);
        $originalHeight = imagesy($source);

        // Calculate new dimensions
        $newWidth = (int) ($originalWidth * $scale);
        $newHeight = (int) ($originalHeight * $scale);

        // Create new image
        $resized = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency for PNG and GIF
        if ($extension === 'png' || $extension === 'gif') {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
            imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
        }

        // Resize image
        imagecopyresampled(
            $resized,
            $source,
            0, 0, 0, 0,
            $newWidth,
            $newHeight,
            $originalWidth,
            $originalHeight
        );

        // Save image based on type
        $result = false;
        switch ($extension) {
            case 'png':
                $result = imagepng($resized, $imagePath, 9);
                break;
            case 'jpg':
            case 'jpeg':
                $result = imagejpeg($resized, $imagePath, 90);
                break;
            case 'gif':
                $result = imagegif($resized, $imagePath);
                break;
        }

        // Free memory
        imagedestroy($source);
        imagedestroy($resized);

        return $result;
    }
}
