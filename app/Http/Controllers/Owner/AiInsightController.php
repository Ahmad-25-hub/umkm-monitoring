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
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AiInsightController extends Controller
{
    public function index(Request $request, BuildOwnerNavigationContextAction $navigation): View
    {
        return view('dashboard.ai-insight', [
            ...$navigation->execute($request),
            'messages' => $request->session()->get($this->sessionKey($request).'.messages', []),
            'isConfigured' => filled(config('services.groq.api_key')) || ! empty(array_filter((array) config('services.groq.api_keys', []))),
        ]);
    }

    public function store(AskAiInsightRequest $request, GroqInsightClient $client, AnswerBusinessInsightAction $answer): JsonResponse
    {
        /** @var Business $business */
        $business = $request->attributes->get('activeBusiness');
        $key = $this->sessionKey($request);
        $message = $request->validated('message');
        $messages = $request->session()->get($key.'.messages', []);
        $previous = $request->session()->get($key.'.context');
        $deadline = microtime(true) + 32;

        try {
            $report = $client->interpret($message, $previous, $messages, $deadline);

            if ($report['name'] === 'continue_conversation') {
                $report = $previous ?? ['name' => 'nadi_help', 'arguments' => ['topic' => 'overview']];
            }

            $reply = $answer->execute($business, $report);
        } catch (InsightUnavailableException $exception) {
            return response()->json(['message' => $exception->getMessage()], $exception->status);
        }

        if ($report['name'] !== 'decline_question' || ($report['arguments']['reason'] ?? null) === 'clarification') {
            try {
                $narrative = $client->compose($message, $report, $reply['analysis_context'] ?? $reply['message'], $messages, $deadline);
                $reply = [...$reply, 'details' => $reply['message'], 'message' => $narrative];
            } catch (InsightUnavailableException) {
                $reply['notice'] = 'Penjelasan Nadi belum tersedia. Berikut laporan datanya; Anda tetap bisa melanjutkan percakapan.';
                Log::info('AI Insight used report fallback.');
            }
        }

        unset($reply['analysis_context']);

        $timestamp = now(Task::TIMEZONE)->format('H:i');
        $userMessage = ['role' => 'user', 'message' => $message, 'source' => null, 'time' => $timestamp];
        $assistantMessage = ['role' => 'assistant', ...$reply, 'time' => $timestamp];
        $request->session()->put($key.'.messages', array_slice([...$messages, $userMessage, $assistantMessage], -10));

        if (in_array($report['name'], ['sales_summary', 'sales_comparison', 'task_summary', 'product_ranking', 'product_comparison', 'sales_breakdown', 'employee_performance', 'report_bundle'], true)) {
            $request->session()->put($key.'.context', $report);
        }

        Log::info('AI Insight reports generated.', [
            'user_id' => $request->user()->id,
            'business_id' => $business->id,
            'reports' => $report['name'] === 'report_bundle' ? array_column($report['arguments']['reports'], 'name') : [$report['name']],
        ]);

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
