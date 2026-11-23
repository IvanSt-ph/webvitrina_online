<?php

namespace Tests\Feature;

use App\Services\TelegramBotClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramBotClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.telegram.token' => 'fake-token']);
        Http::preventStrayRequests();
    }

    private function message(): array
    {
        return [
            'verification_banner' => true, 'chat_id' => 12345,
            'text' => '<b>Подтверждение телефона WebVitrina</b>', 'parse_mode' => 'HTML',
            'reply_markup' => ['keyboard' => [[['text' => '📱 Поделиться моим номером', 'request_contact' => true]]],
                'resize_keyboard' => true, 'one_time_keyboard' => true],
        ];
    }

    private function assertTextFallback(): void
    {
        $expected = $this->message();
        unset($expected['verification_banner']);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/sendMessage')
            && $request->data() === $expected);
    }

    public function test_missing_or_corrupt_banner_falls_back_to_text(): void
    {
        Http::fake(['*' => Http::response(['ok' => true])]);
        $file = tmpfile();
        $this->assertIsResource($file);
        try {
            fwrite($file, 'not an image');
            $path = stream_get_meta_data($file)['uri'];
            foreach ([$path.'.missing', $path] as $asset) {
                $client = new class($asset) extends TelegramBotClient {
                    public function __construct(private string $asset) {}
                    protected function verificationBannerPath(): string { return $this->asset; }
                };
                $this->assertTrue($client->send($this->message()));
            }
            Http::assertSentCount(2);
            Http::assertNotSent(fn ($request) => str_ends_with($request->url(), '/sendPhoto'));
            $this->assertTextFallback();
        } finally {
            fclose($file);
        }
    }

    public function test_api_rejections_fall_back_to_text(): void
    {
        Http::fake([
            '*/sendPhoto' => Http::sequence()->push(['ok' => false], 400)->push(['ok' => false], 200),
            '*/sendMessage' => Http::response(['ok' => true]),
        ]);
        $client = new TelegramBotClient();
        $this->assertTrue($client->send($this->message()));
        $this->assertTrue($client->send($this->message()));
        Http::assertSentCount(4);
        $this->assertTextFallback();
    }

    public function test_connection_failure_falls_back_without_exposing_exception(): void
    {
        Http::fake([
            '*/sendPhoto' => fn () => throw new ConnectionException('https://api.telegram.org/botfake-token/sendPhoto'),
            '*/sendMessage' => Http::response(['ok' => true]),
        ]);
        $this->assertTrue((new TelegramBotClient())->send($this->message()));
        $this->assertTextFallback();
    }

    public function test_failure_of_both_deliveries_returns_false(): void
    {
        Http::fake(['*' => Http::response(['ok' => false], 503)]);
        $this->assertFalse((new TelegramBotClient())->send($this->message()));
        Http::assertSentCount(2);
        $this->assertTextFallback();
    }

    public function test_plain_messages_never_send_a_photo(): void
    {
        Http::fake(['*' => Http::response(['ok' => true])]);
        $message = ['chat_id' => 12345, 'text' => 'Телефон проверен!',
            'reply_markup' => ['remove_keyboard' => true]];
        $this->assertTrue((new TelegramBotClient())->send($message));
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/sendMessage')
            && $request->data() === $message);
    }
}
