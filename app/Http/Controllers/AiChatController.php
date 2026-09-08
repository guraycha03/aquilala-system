<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\ChatHistory;
use App\Models\Product;

class AiChatController extends Controller
{
    public function index()
    {
        return view('farmer.ai-chat.index');
    }

    public function send(Request $request)
    {
        $request->validate([
            'messages' => 'required|array',
            'messages.*.role' => 'required|in:user,assistant',
            'messages.*.content' => 'required|string',
        ]);

        $apiKey = config('services.groq.key');

        if (!$apiKey) {
            return response()->json(['error' => 'Groq API key is not configured.'], 500);
        }

        $systemMessage = [
            'role' => 'system',
            'content' => 'You are an expert agricultural AI assistant for farmers in Bulan, Sorsogon, Philippines. You specialize in recommending fertilizers, pesticides, and agricultural inputs for rice, corn, and vegetable farming in the Bicol region. Use Filipino/Tagalog words when helpful.

CRITICAL RESPONSE & FORMATTING RULES:
1. ALWAYS COMPLETE YOUR RESPONSES. Never stop mid-sentence or mid-table.
2. DO NOT embed multi-step bullet lists or long paragraphs inside table cells. Keep table entries brief, single-line, or strictly metric-focused.
3. USE CLEAR SECTION HEADINGS (e.g., `### 1. Soil & Site Preparation`) for main action phases instead of squeezing entire multi-step processes into a massive table.
4. Generously use inline formatting for maximum scannability:
   - **Bold** for crucial numbers, key actions, product names, and dates.
   - *Italics* for local climate context or notes.
   - Standard bullet points (`-`) for step-by-step instructions.
5. HIGHLIGHT WARNINGS: Use blockquotes for critical safety or time-sensitive alerts. Do NOT include emojis or symbols (⚠️, ☠️, ⚡, etc.) anywhere in your response — the frontend automatically adds a warning icon to every blockquote. Format blockquotes as exactly:
   > **CRITICAL TIP:** [Insert high-priority alert here]
6. USE TABLES SPARINGLY: Reserve tables ONLY for brief numeric summaries (e.g., Fertilizer Application Schedules or Cost Breakdowns).
7. PRODUCT SUGGESTIONS: When the farmer\'s question involves buying a specific input (fertilizer, pesticide, seed, soil amendment, sprayer, or any purchasable item) or a cost/materials estimate, end your reply with a single keyword line exactly in this format:
   [SUGGEST:urea, complete fertilizer, 14-14-14]
   Use 2-4 short generic keywords (product names or categories) that best match the inputs you recommended. If the question is purely informational, general, or does not involve purchasing anything, do NOT add the marker.',
        ];

        $messages = array_merge([$systemMessage], $request->messages);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->withoutVerifying()->timeout(90)->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => 'openai/gpt-oss-20b',
                'messages' => $messages,
                'temperature' => 0.7,
                'max_tokens' => 4096,
            ]);

            if ($response->failed()) {
                $error = $response->json('error.message') ?? 'Unknown Groq API error.';
                return response()->json(['error' => $error], 502);
            }

            $reply = $response->json('choices.0.message.content', '');
            $suggestions = [];

            if (preg_match('/\[[ \t\x{00A0}]*SUGGEST:[ \t\x{00A0}]*([^\]\r\n]*)\]/iu', $reply, $m) && trim($m[1]) !== '') {
                $reply = trim($reply);
                $keywords = array_values(array_filter(array_map('trim', explode(',', $m[1])), fn ($k) => $this->normalizeKeyword($k) !== ''));

                if (!empty($keywords)) {
                    $suggestions = $this->searchSuggestions($keywords);
                }
            }

            return response()->json([
                'reply' => trim($reply),
                'suggestions' => $suggestions,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to connect to AI service. ' . $e->getMessage()], 500);
        }
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

    private function searchSuggestions(array $keywords): array
    {
        $keywords = array_values(array_filter(
            array_map('trim', $keywords),
            fn ($k) => $this->normalizeKeyword($k) !== ''
        ));

        if (empty($keywords)) {
            return [];
        }

        $query = Product::active()->with(['category', 'storeOwner', 'mainImage']);
        $query->where(function ($q) use ($keywords) {
            foreach ($keywords as $keyword) {
                $like = '%' . $this->normalizeKeyword($keyword) . '%';
                $q->orWhere(function ($qq) use ($like) {
                    $qq->where('name', 'like', $like)
                        ->orWhere('brand', 'like', $like)
                        ->orWhere('product_type', 'like', $like)
                        ->orWhere('description', 'like', $like)
                        ->orWhereHas('category', fn ($c) => $c->where('name', 'like', $like));
                });
            }
        });

        $candidates = $query->orderByDesc('created_at')->limit(60)->get();

        return $this->spreadAcrossStores($candidates, 10);
    }

    private function normalizeKeyword(string $keyword): string
    {
        return trim(str_replace(
            ["\u{2010}", "\u{2011}", "\u{2012}", "\u{2013}", "\u{2014}", "\u{2212}", "\u{00A0}"],
            ['-', '-', '-', '-', '-', '-', ' '],
            $keyword
        ));
    }

    /**
     * Interleave matched products across stores so suggestions aren't
     * dominated by whichever store sorts first.
     */
    private function spreadAcrossStores($products, int $max): array
    {
        $byStore = [];
        foreach ($products as $p) {
            $byStore[$p->storeOwner?->id ?? 'none'][] = $p;
        }

        if (empty($byStore)) {
            return [];
        }

        $keys = array_keys($byStore);
        $pointers = array_fill_keys($keys, 0);
        $result = [];
        $total = 0;

        while ($total < $max) {
            $added = false;
            foreach ($keys as $key) {
                if ($total >= $max) {
                    break 2;
                }
                if (isset($byStore[$key][$pointers[$key]])) {
                    $result[] = $this->buildSuggestion($byStore[$key][$pointers[$key]]);
                    $pointers[$key]++;
                    $total++;
                    $added = true;
                }
            }
            if (!$added) {
                break;
            }
        }

        return $result;
    }

    private function buildSuggestion(Product $p): array
    {
        $package = ($p->package_size !== null ? rtrim(rtrim(number_format((float) $p->package_size, 2, '.', ''), '0'), '.') : '')
            . ($p->package_unit ? ' ' . $p->package_unit : '');

        return [
            'id' => $p->id,
            'name' => $p->name,
            'brand' => $p->brand,
            'category' => $p->category?->name ?? '',
            'price' => number_format((float) $p->price, 2),
            'price_unit' => $p->price_unit ?? 'each',
            'package' => trim($package),
            'stock' => $p->status === 'out_of_stock' ? 'Out of Stock' : $p->stock_status,
            'store' => $p->storeOwner?->name ?? '',
            'description' => mb_strimwidth((string) $p->description, 0, 400, '…'),
            'image' => $p->mainImage?->file_path
                ? asset('storage/' . $p->mainImage->file_path)
                : asset('images/product.png'),
            'url' => route('farmer.products.show', $p->id),
        ];
    }

    public function histories()
    {
        $histories = auth()->user()->chatHistories()
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

        return response()->json(['messages' => $chatHistory->messages]);
    }

    public function saveHistory(Request $request)
    {
        $request->validate([
            'id' => 'nullable|integer',
            'messages' => 'required|array|min:1',
            'messages.*.role' => 'required|in:user,assistant',
            'messages.*.content' => 'required|string',
        ]);

        $messages = array_values(array_filter($request->messages, fn ($m) => in_array($m['role'], ['user', 'assistant'], true)));

        if (empty($messages)) {
            return response()->json(['error' => 'Nothing to save.'], 422);
        }

        $firstUser = collect($messages)->firstWhere('role', 'user');
        $title = mb_strimwidth((string) ($firstUser['content'] ?? 'Chat'), 0, 60, '…');

        if ($request->filled('id')) {
            $history = auth()->user()->chatHistories()->findOrFail($request->id);
            $history->update([
                'title' => $title,
                'messages' => $messages,
            ]);
        } else {
            $history = auth()->user()->chatHistories()->create([
                'title' => $title,
                'messages' => $messages,
            ]);
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
