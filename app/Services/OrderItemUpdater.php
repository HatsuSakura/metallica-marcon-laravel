<?php
// app/Services/OrderItemUpdater.php
namespace App\Services;

use App\Models\OrderItem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class OrderItemUpdater
{
    public function __construct(
        protected OrderItemImageUploader $imageUploader,
        protected OrderItemExplosionSync $explosionSync,   // <= NEW: DI del sync esplosioni
    ) {}

    /**
     * Salva l'item + immagini + (eventuale) esplosione in TRANSAZIONE.
     * Accetta 'explosions' come array o JSON string (nel caso di multipart).
     */
    public function update(OrderItem $item, array $data): OrderItem
    {
        return DB::transaction(function () use ($item, $data) {

            // 1) separa immagini ed esplosioni dal resto
            $images     = Arr::pull($data, 'images', []);
            $explosions = Arr::pull($data, 'explosions', null);

            // se arrivano in multipart possono essere stringhe JSON
            if (is_string($explosions)) {
                $decoded = json_decode($explosions, true);
                $explosions = is_array($decoded) ? $decoded : null;
            }

            // 2) aggiorna i campi "base" dell'item
            $item->fill($data);
            $item->save();

            // 3) immagini (se presenti)
            if (!empty($images)) {
                $this->imageUploader->upload($item, $images);
            }

            // 4) esplosione (se presente): replace atomico
            if (is_array($explosions)) {
                $this->explosionSync->sync($item->id, $explosions);
            }

            // 5) ritorna fresco con relazioni utili
            return $item->fresh(['images', 'explosions.catalogItem', 'explosions.children.catalogItem']);
        });
    }

    /**
     * Versione "safe" con detection conflitti (se la usi in alcuni endpoint).
     * Integro la stessa logica di sopra per explosions/images.
     */
    public function safeUpdate(OrderItem $item, array $data, int $userId): array
    {
        $incomingUpdatedAt = $data['updated_at'] ?? null;
        if ($incomingUpdatedAt && $incomingUpdatedAt !== $item->updated_at?->toISOString()) {
            if ($item->updated_by_user_id !== $userId) {
                return [
                    'status'   => 'conflict',
                    'conflict' => [
                        'id'           => $item->id,
                        'reason'       => 'modified by another user',
                        'updatedItem'  => $item->fresh(),
                    ],
                ];
            }
        }

        return DB::transaction(function () use ($item, $data, $userId) {
            $images     = Arr::pull($data, 'images', []);
            $explosions = Arr::pull($data, 'explosions', null);

            if (is_string($explosions)) {
                $decoded = json_decode($explosions, true);
                $explosions = is_array($decoded) ? $decoded : null;
            }

            $item->fill($data);
            $item->updated_by_user_id = $userId;
            $item->save();

            if (!empty($images)) {
                $this->imageUploader->upload($item, $images);
            }

            if (is_array($explosions)) {
                $this->explosionSync->sync($item->id, $explosions);
            }

            return [
                'status' => 'saved',
                'item'   => $item->fresh(['images', 'explosions.catalogItem', 'explosions.children.catalogItem']),
            ];
        });
    }
}
