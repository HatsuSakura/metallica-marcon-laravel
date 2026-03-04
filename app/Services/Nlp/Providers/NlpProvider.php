<?php

namespace App\Services\Nlp\Providers;

interface NlpProvider
{
    /**
     * Parse logistics natural language query into structured LogisticsQuery array.
     *
     * @param  string  $query
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function parseLogistics(string $query, array $context = []): array;
}

