<?php
// app/Services/OrderItemUpdater.php
namespace App\Services;

use App\Models\OrderItem;
use App\Services\OrderItemImageUploader;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;

class OrderItemUpdater
{
    protected OrderItemImageUploader $imageUploader;

    public function __construct(OrderItemImageUploader $imageUploader)
    {
        $this->imageUploader = $imageUploader;
    }

    public function update(OrderItem $item, array $data): OrderItem
    {
        // Separiamo immagini da altri dati
        $images = Arr::pull($data, 'images', []);

        // Aggiorna i campi dell'item
        $item->fill($data);
        $item->save();

        // Se ci sono immagini caricate, le processiamo
        if (!empty($images)) {
            $this->imageUploader->upload($item, $images); // array di UploadedFile
        }

        return $item->fresh('images');
    }

    public function safeUpdate(OrderItem $item, array $data, int $userId): array
    {
        $incomingUpdatedAt = $data['updated_at'] ?? null;

        if ($incomingUpdatedAt && $incomingUpdatedAt !== $item->updated_at->toISOString()) {
            // Conflitto solo se modificato da altro utente
            if ($item->updated_by_user_id !== $userId) {
                return [
                    'status' => 'conflict',
                    'conflict' => [
                        'id' => $item->id,
                        'reason' => 'modified by another user',
                        'updatedItem' => $item->fresh(),
                    ]
                ];
            }
        }

        $images = Arr::pull($data, 'images', []); // può contenere UploadedFile[] oppure array vuoto

        $item->fill($data);
        $item->updated_by_user_id = $userId;
        $item->save();

        if (!empty($images)) {
            $this->imageUploader->upload($item, $images);
        }

        // Ricarica l'item con la relazione images
        $fresh = $item->fresh(['images']);

        return [
            'status' => 'saved',
            'item' => $fresh,
        ];
    }

}
