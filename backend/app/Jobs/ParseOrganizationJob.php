<?php

namespace App\Jobs;

use App\Models\Organization;
use App\Models\Review;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ParseOrganizationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $url;
    protected $socketId;

    public function __construct($url, $socketId)
    {
        $this->url = $url;
        $this->socketId = $socketId;
    }

    public function handle()
    {
        try {
            $this->emitToSocket("laravel.start", $this->socketId, [
                "message" => "Задача поставлена в очередь для {$this->url}",
            ]);

            if (Organization::where('yandex_url', $this->url)->exists()) {
                $this->emitToSocket("laravel.info", $this->socketId, [
                    "message" => "Организация уже есть в базе, парсинг не требуется",
                ]);
                return;
            }

            $process = new Process(['node', base_path('scripts/parser.js'), $this->url, $this->socketId]);
            $process->setTimeout(null); // без ограничения
            $process->run();

            if (!$process->isSuccessful()) {
                $this->emitToSocket("laravel.error", $this->socketId, [
                    "message" => $process->getErrorOutput(),
                ]);
                return;
            }

            $data = json_decode($process->getOutput(), true);

            if (!$data || !is_array($data)) {
                $this->emitToSocket("laravel.error", $this->socketId, [
                    "message" => "Парсер вернул пустой результат",
                ]);
                return;
            }

            if (!empty($data['error'])) {
                $this->emitToSocket("laravel.error", $this->socketId, [
                    "message" => $data['error'],
                ]);
                return;
            }

            $org = Organization::updateOrCreate(
                ['yandex_url' => $this->url],
                [
                    'name'   => $data['organization']['name'] ?? '',
                    'rating' => $data['organization']['rating'] ?? null,
                ]
            );

            $countReviews = count($data['organization']['rating']);

            $this->emitToSocket("laravel.info", $this->socketId, [
                "message" => "Добавлена новая организация: {$data['organization']['name']} / отзывов: {$countReviews}",
            ]);

            foreach ($data['reviews'] ?? [] as $review) {
                Review::updateOrCreate(
                    [
                        'organization_id' => $org->id,
                        'author' => $review['author'] ?? '',
                        'date'   => $review['date'] ?? null,
                    ],
                    [
                        'text'   => $review['text'] ?? '',
                        'rating' => $review['rating'] ?? null,
                    ]
                );
            }

            $this->emitToSocket("laravel.finish", $this->socketId, [
                "message" => "Данные успешно сохранены в БД",
                "organization" => $org->name,
            ]);

        } catch (\Exception $e) {
            $this->emitToSocket("laravel.error", $this->socketId, [
                "message" => $e->getMessage(),
            ]);
        } finally {
            $this->emitToSocket("laravel.info", $this->socketId, [
                "message" => "Парсер завершил работу для {$this->url}",
            ]);
        }
    }

    private function emitToSocket($event, $socketId, $payload)
    {
        Http::post('http://127.0.0.1:6002/emit', [
            'event' => $event,
            'socketId' => $socketId,
            'data' => $payload,
        ]);
    }
}
