<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;
use App\Models\Organization;
use App\Models\Review;
use App\Services\YandexParserService;

class OrganizationController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $page = (int) $request->get('page', 1);

        $organizations = Organization::withCount('reviews')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json($organizations);
    }

    public function store(Request $request, YandexParserService $parser)
    {
        $request->validate(['yandex_url' => 'required|url']);
        $data = $parser->parse($request->yandex_url);

        if (!$data || !is_array($data)) {
            event(new ParserErrorEvent("Парсер вернул пустой результат"));
            return response()->json(['error' => 'Ошибка парсинга'], 500);
        }

        $org = Organization::updateOrCreate(
            ['yandex_url' => $request->yandex_url],
            [
                'name'   => $data['name'] ?? '',
                'rating' => $data['rating'] ?? null,
            ]
        );

        if (config('parser.process_reviews')) {
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
        }

        event(new ParserSuccessEvent($org));
        return response()->json($org->load('reviews'));
    }

    public function reviews(Organization $organization)
    {
        return $organization->reviews()->paginate(50);
    }

    public function start(Request $request)
    {
        $url = $request->yandex_url;
        $socketId = $request->socket_id;

        $this->emitToSocket("laravel.start", $socketId, [
            "message" => "Задача запущена в Laravel",
        ]);

        dispatch(new \App\Jobs\ParseOrganizationJob($url, $socketId));

        return response()->json(['status' => 'started']);
    }

    public function destroy(Organization $organization)
    {
        try {
            // удаляем связанные отзывы
            $organization->reviews()->delete();

            // удаляем саму организацию
            $organization->delete();

            return response()->json(['message' => 'Организация и её отзывы удалены']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
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
