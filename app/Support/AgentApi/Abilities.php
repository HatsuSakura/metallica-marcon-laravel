<?php

namespace App\Support\AgentApi;

class Abilities
{
    public const ALL = [
        'reference:read' => 'Consultare anagrafiche operative',
        'orders:read' => 'Consultare ordini e materiali',
        'orders:create' => 'Creare ordini con materiali e contenitori',
        'orders:update' => 'Modificare ordini e materiali prima della partenza',
        'journeys:read' => 'Consultare viaggi',
        'journeys:create' => 'Creare piani di viaggio',
        'journeys:update' => 'Modificare piani di viaggio prima della partenza',
    ];
}
