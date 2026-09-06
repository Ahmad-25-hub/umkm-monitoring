<?php

namespace App\Http\Controllers\Owner;

use App\Actions\AnswerBusinessInsightAction;
use App\Actions\BuildOwnerNavigationContextAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\AskAiInsightRequest;
use App\Models\Business;
use App\Models\Task;
use App\Support\GroqInsightClient;
use App\Support\InsightUnavailableException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiInsightController extends Controller
{
    public function index(Request $request, BuildOwnerNavigationContextAction $navigation): View
    {
        return view('dashboard.ai-insight', [
            ...$navigation->execute($request),
            'messages' => $request->session()->get($this->sessionKey($request).'.messages', []),
            'isConfigured' => filled(config('services.groq.api_key')),
        ]);
    }

    public function store(AskAiInsightRequest $request, GroqInsightClient $client, AnswerBusinessInsightAction $answer): JsonResponse
    {
        /** @var Business $business */
        $business = $request->attributes->get('activeBusiness');
        $key = $this->sessionKey($request);
        $message = $request->validated('message');

        try {
            $report = $client->interpret($message, $request->session()->get($key.'.context'));
            $reply = $answer->execute($business, $report);
        } catch (InsightUnavailableException $exception) {
            return response()->json(['message' => $exception->getMessage()], $exception->status);
        }

        $timestamp = now(Task::TIMEZONE)->format('H:i');
        $userMessage = ['role' => 'user', 'message' => $message, 'source' => null, 'time' => $timestamp];
        $assistantMessage = ['role' => 'assistant', ...$reply, 'time' => $timestamp];
        $messages = $request->session()->get($key.'.messages', []);
        $request->session()->put($key.'.messages', array_slice([...$messages, $userMessage, $assistantMessage], -10));

        if (in_array($report['name'], ['sales_summary', 'sales_comparison', 'task_summary'], true)) {
            $request->session()->put($key.'.context', $report);
        }

        return response()->json(['messages' => [$userMessage, $assistantMessage]]);
    }

    public function destroy(AskAiInsightRequest $request): JsonResponse
    {
        $request->session()->forget($this->sessionKey($request));

        return response()->json(['message' => 'Percakapan dihapus. Mulai pertanyaan baru tentang usaha Anda.']);
    }

    private function sessionKey(Request $request): string
    {
        return 'ai_insight.'.$request->user()->id.'.'.$request->attributes->get('activeBusiness')->id;
    }
}
