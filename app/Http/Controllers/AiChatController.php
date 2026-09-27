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
        $role = auth()->user()?->role;

        if ($role === 'store_owner') {
            return view('store.ai-chat.index');
        }

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

        $systemMessage = $this->systemMessageForRole(auth()->user()?->role);

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

    private function systemMessageForRole(?string $role): array
    {
        if ($role === 'store_owner') {
            $content = 'You are an expert AI demand assistant for store owners in the Aquilala Irrigators Association (Bulan, Sorsogon, Philippines). Store owners see farmer cost quotations as "demand signals" - demand visibility, no action required. You help store owners understand demand, plan stock, and recommend products their store should carry.
The current user is a STORE OWNER who sells agricultural inputs. When asked about products to carry or restock, recommend from the store\'s own inventory perspective.

CRITICAL RESPONSE & FORMATTING RULES:
1. ALWAYS COMPLETE YOUR RESPONSES. Never stop mid-sentence or mid-table.
2. DO NOT embed multi-step bullet lists or long paragraphs inside table cells. Keep table entries brief and single-line.
3. USE CLEAR SECTION HEADINGS (e.g., `### Demand Overview`, `### Recommended Products to Restock`, `### What to Watch`) for main sections.
4. Generously use inline formatting for maximum scannability:
   - **Bold** for crucial numbers, product names, crops, and amounts.
   - *Italics* for climate/seasonal notes or context.
   - Standard bullet points (`-`) for concise insights.
5. HIGHLIGHT WARNINGS: Use blockquotes for critical or time-sensitive alerts. Do NOT include emojis or symbols (⚠️, ☠️, ⚡, etc.) anywhere in your response — the frontend automatically adds a warning icon to every blockquote. Format blockquotes as exactly:
   > **CRITICAL TIP:** [Insert high-priority alert here]
6. USE TABLES SPARINGLY: Reserve tables ONLY for brief numeric summaries (e.g., Stock/Purchase recommendations or pricing).
7. PRODUCT SUGGESTIONS: When the store owner\'s question involves products to stock, buy, or restock - or any cost/materials estimate - end your reply with a single keyword line exactly in this format:
   [SUGGEST:urea, complete fertilizer, 14-14-14]
   Use 2-4 short generic keywords (product names or categories) that best match the products you recommended. If the question is purely informational, general, or does not involve products, do NOT add the marker.';
        } else {
            $content = 'You are an expert agricultural AI assistant for farmers in Bulan, Sorsogon, Philippines. You specialize in recommending fertilizers, pesticides, and agricultural inputs for rice, corn, and vegetable farming in the Bicol region. Use Filipino/Tagalog words when helpful.

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
   Use 2-4 short generic keywords (product names or categories) that best match the inputs you recommended. If the question is purely informational, general, or does not involve purchasing anything, do NOT add the marker.';
        }

        return ['role' => 'system', 'content' => $content];
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

        $query = Product::active()->with(['category', 'storeOwner', 'images']);

        if (auth()->user()?->role === 'store_owner') {
            $query->where('user_id', auth()->id());
        }

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
        $gallery = $p->gallery;

        return [
            'id' => $p->id,
            'name' => $p->name,
            'display_name' => $p->display_name,
            'brand' => $p->brand,
                'category' => $p->category_display_name,
            'price' => number_format((float) $p->price, 2),
            'price_unit' => $p->price_unit ?? 'each',
            'package' => $p->size_label ?? '',
            'stock' => $p->status === 'out_of_stock' ? 'Out of Stock' : $p->stock_status,
            'store' => $p->storeOwner?->name ?? '',
            'description' => mb_strimwidth((string) $p->description, 0, 400, '…'),
            'image' => $gallery->isNotEmpty()
                ? asset('storage/' . $gallery->first()->file_path)
                : asset('images/product.png'),
            'images' => $gallery->map(fn ($img) => asset('storage/' . $img->file_path))->values(),
            'url' => auth()->user()?->role === 'store_owner'
                ? route('store.inventory.index')
                : route('farmer.products.show', $p->id),
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
