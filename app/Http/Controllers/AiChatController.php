<?php

namespace App\Http\Controllers;

use App\AI\AiConversationContext;
use App\AI\AquilalaAssistant;
use App\Models\ChatHistory;
use App\Services\ProductSearchService;
use Illuminate\Http\Request;

class AiChatController extends Controller
{
    /**
     * How many prior turns are sent with a request.
     *
     * Enough for the model to see what "it" referred to, few enough that the
     * request stays small. Everything the assistant actually needs to remember
     * lives in the structured context, not in this window.
     */
    private const HISTORY_TURNS = 8;

    public function __construct(
        private readonly AquilalaAssistant $assistant,
        private readonly ProductSearchService $products,
    ) {
    }

    public function index()
    {
        $user = auth()->user();
        $data = [
            'aiAvatar' => $user?->aiAvatarUrl() ?? asset('images/ai-avatar.png'),
            'aiAvatarKey' => $user?->ai_avatar && \App\Services\AiAvatars::has($user->ai_avatar)
                ? $user->ai_avatar
                : \App\Services\AiAvatars::defaultKey(),
            'aiAvatars' => \App\Services\AiAvatars::choices(),
            // The member's own picture, for the messages they send.
            'myPhoto' => $user?->profilePhotoUrl(),
            'myInitials' => $user?->initials() ?? '?',
            'myName' => $user?->name ?? '',
            'aiModel' => app(\App\AI\AiLlmClient::class)->modelName(),
            'aiProvider' => app(\App\AI\AiLlmClient::class)->provider(),
        ];

        // One shared chat view for every role keeps the UI consistent.
        // Role-specific routes are resolved from $routeBase inside the view.
        return view('farmer.ai-chat.index', $data);
    }

    /**
     * Save this account's choice of AI assistant avatar.
     *
     * The avatars belong to the system: the account stores which one it
     * picked, never a file of its own. Nothing is uploaded, so nothing a
     * member sends ends up on the server, and the avatar looks the same on
     * every device.
     */
    public function updateAvatar(Request $request)
    {
        $validated = $request->validate([
            'avatar' => ['required', 'string', \Illuminate\Validation\Rule::in(array_keys(\App\Services\AiAvatars::all()))],
        ], [
            'avatar.required' => 'Please choose an avatar.',
            'avatar.in' => 'That avatar is not one of ours.',
        ]);

        $user = $request->user();
        $key = $validated['avatar'];

        // An account that still carries an uploaded image from before the
        // avatar set existed drops the file when it picks a system one.
        if ($user->ai_avatar && ! \App\Services\AiAvatars::has($user->ai_avatar)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($user->ai_avatar);
        }

        $user->forceFill(['ai_avatar' => $key])->save();

        return response()->json([
            'ok' => true,
            'url' => \App\Services\AiAvatars::url($key),
        ]);
    }

    /**
     * Answer one turn.
     *
     * The request carries the visible transcript for continuity plus the
     * structured conversation context. The transcript is never handed to the
     * model as the source of truth — it is only used by the intent router for
     * reference resolution. Everything shown to the farmer has already been
     * read from the database or the rule engine by the time this method
     * returns.
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'messages' => 'required|array',
            'messages.*.role' => 'required|in:user,assistant',
            'messages.*.content' => 'required|string',
            'context' => 'nullable|array',
        ]);

        $message = $this->latestUserMessage($validated['messages']);

        if ($message === null) {
            return response()->json([
                'error' => 'No question was included in the request.',
            ], 422);
        }

        try {
            $result = $this->assistant->handle(
                $message,
                $request->user(),
                $this->recentHistory($validated['messages']),
                $request->input('context'),
            );
        } catch (\Throwable $e) {
            // The chat must never show a stack trace or die silently: one
            // clean sentence the user can act on, and the detail in the log.
            \Illuminate\Support\Facades\Log::error('aquilala.ai.assistant.failed', [
                'user_id' => $request->user()?->id,
                'error'   => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'The AI failed to process your message. Please try again in a moment.',
            ], 500);
        }

        return response()->json([
            'reply'       => $result['reply'],
            'type'        => $result['type'],
            'intent'      => $result['intent'],
            'source'      => $result['source'],
            'suggestions' => $result['suggestions'],
            // Cards for products the reply names in prose, so the chat can
            // draw a real tile beside each name. Absent on most turns.
            'products'    => $result['products'] ?? [],
            'context'     => $result['context'],
        ]);
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    private function latestUserMessage(array $messages): ?string
    {
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            if (($messages[$i]['role'] ?? null) === 'user') {
                $content = trim((string) $messages[$i]['content']);

                if ($content !== '') {
                    return $content;
                }
            }
        }

        return null;
    }

    /**
     * Everything before the message being answered, most recent last.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return array<int, array{role: string, content: string}>
     */
    private function recentHistory(array $messages): array
    {
        $history = array_slice($messages, 0, max(count($messages) - 1, 0));

        return array_slice($history, -self::HISTORY_TURNS);
    }

    public function suggestions(Request $request)
    {
        $request->validate([
            'keywords' => 'required|array',
            'keywords.*' => 'required|string',
        ]);

        return response()->json([
            'suggestions' => $this->searchSuggestions($request->keywords),
        ]);
    }

    /**
     * @param  array<int, string>  $keywords
     * @return array<int, array<string, mixed>>
     */
    private function searchSuggestions(array $keywords): array
    {
        $found = collect();

        foreach ($keywords as $keyword) {
            $found = $found->merge($this->products->search((string) $keyword, auth()->user()));
        }

        // Interleaving happens per query, so dedupe across them while keeping
        // the order the farmer's keywords imply.
        $seen = [];
        $cards = [];

        foreach ($found->unique('id')->take(ProductSearchService::MAX_RESULTS) as $product) {
            if (isset($seen[$product->id])) {
                continue;
            }

            $seen[$product->id] = true;
            $cards[] = $this->products->card($product, auth()->user());
        }

        return $cards;
    }

    public function histories()
    {
        $histories = auth()->user()
            ->chatHistories()
            ->orderByDesc('updated_at')
            ->get(['id', 'title', 'updated_at']);

        return response()->json([
            'histories' => $histories->map(fn ($h) => [
                'id' => $h->id,
                'title' => $h->title,
                'updated_at' => $h->updated_at->diffForHumans(),
            ])->values(),
        ]);
    }

    public function history(ChatHistory $chatHistory)
    {
        $this->authorizeHistory($chatHistory);

        return response()->json([
            'messages' => $chatHistory->messages,
            // Restored so "how much is it?" still resolves after a reload.
            'context' => $chatHistory->ai_context,
        ]);
    }

    public function saveHistory(Request $request)
    {
        $request->validate([
            'id' => 'nullable|integer',
            'messages' => 'required|array|min:1',
            'messages.*.role' => 'required|in:user,assistant',
            'messages.*.content' => 'required|string',
            'context' => 'nullable|array',
        ]);

        $messages = array_values(array_filter(
            $request->messages,
            fn ($m) => in_array($m['role'], ['user', 'assistant'], true)
        ));

        if (empty($messages)) {
            return response()->json(['error' => 'Nothing to save.'], 422);
        }

        $firstUser = collect($messages)->firstWhere('role', 'user');
        $title = mb_strimwidth((string) ($firstUser['content'] ?? 'Chat'), 0, 60, '…');

        $payload = [
            'title'   => $title,
            'messages' => $messages,
            'ai_context' => AiConversationContext::fromArray($request->input('context'))->toArray(),
        ];

        if ($request->filled('id')) {
            $history = auth()->user()->chatHistories()->findOrFail($request->id);
            $history->update($payload);
        } else {
            $history = auth()->user()->chatHistories()->create($payload);
        }

        return response()->json(['id' => $history->id, 'title' => $history->title]);
    }

    public function destroyHistory(ChatHistory $chatHistory)
    {
        $this->authorizeHistory($chatHistory);
        $chatHistory->delete();

        return response()->json(['ok' => true]);
    }

    private function authorizeHistory(ChatHistory $chatHistory): void
    {
        abort_unless($chatHistory->user_id === auth()->id(), 403, 'Unauthorized access to this chat history.');
    }
}
