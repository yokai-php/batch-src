<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Symfony\App\Job\RickAndMorty;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Yokai\Batch\Job\Item\ItemReaderInterface;

final readonly class RickAndMortyApiReader implements ItemReaderInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $url,
    ) {
    }

    public function read(): iterable
    {
        $url = $this->url;
        do {
            $response = $this->httpClient->request('GET', $url)->toArray();
            $url = $response['info']['next'];
            yield from $response['results'];
        } while ($url !== null);
    }
}
