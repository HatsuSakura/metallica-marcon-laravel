<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Models\OrderItemImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;

class OrderItemImageUploader
{
    protected ImageManager $imageManager;

    public function __construct()
    {
        $this->imageManager = new ImageManager(new GdDriver()); // usa GD, compatibile e leggero
    }

    /**
     * Carica una lista di immagini e le associa a un OrderItem
     *
     * @param OrderItem $item
     * @param UploadedFile[] $files
     * @return Collection di immagini appena create
     */
    public function upload(OrderItem $item, array $files): Collection
    {
        $uploaded = collect();

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                \Log::warning('Elemento non è un UploadedFile valido', [
                    'type' => gettype($file),
                    'class' => is_object($file) ? get_class($file) : null,
                ]);
                continue;
            }

            if (! $file->isValid()) {
                \Log::warning('File non valido', ['name' => $file->getClientOriginalName()]);
                continue;
            }

            // Genera percorso relativo e assoluto
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            $relativePath = "order-item-images/{$item->id}/$filename";
            $fullPath = storage_path("app/public/$relativePath");

            // Crea cartella se non esiste
            if (! file_exists(dirname($fullPath))) {
                mkdir(dirname($fullPath), 0775, true);
            }

            // Salva l’immagine con Intervention
            $imageFile = $this->imageManager->read($file->getRealPath());
            Storage::disk('public')->put($relativePath, (string) $imageFile->encode());

            $result = Storage::disk('public')->put($relativePath, (string) $imageFile->encode());
                if (! $result) {
                    \Log::error('⚠️ Fallita la scrittura del file con Storage::put', ['path' => $relativePath]);
                } else {
                    \Log::info('✅ File salvato correttamente con Storage::put', ['path' => $relativePath]);
                }


            // Salva il record nel DB
            $image = $item->images()->create([
                'path' => $relativePath,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);

            $uploaded->push($image);
        }

        return $uploaded;
    }

    /**
     * Elimina immagine da storage e DB
     */
    public function delete(OrderItemImage $image): void
    {
        Storage::disk('public')->delete($image->path);
        $image->delete();
    }
}
