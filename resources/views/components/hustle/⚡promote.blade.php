<?php

use App\Models\Editorial;
use App\Models\Book;
use App\Models\User;
use App\Services\CommerceService;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

new class extends Component {

    public User $profileOwner;
    public string $serviceType = '';
    public string $contentType = 'editorial';
    public ?int $contentId = null;
    public string $message = '';
    public bool $success = false;

    public function mount(User $profileOwner): void
    {
        $this->profileOwner = $profileOwner;

        // Default to first allowed service
        $services = $profileOwner->profileCommerceSetting?->allowed_services ?? [];
        $this->serviceType = $services[0] ?? '';
    }

    protected function rules(): array
    {
        return [
            'serviceType' => 'required|string',
            'contentType' => 'required|in:editorial,book',
            'contentId'   => 'required|integer',
        ];
    }

    public function submit(): void
    {
        $this->validate();

        $content = $this->contentType === 'editorial'
            ? Editorial::where('id', $this->contentId)
                ->where('user_id', Auth::id())
                ->firstOrFail()
            : Book::where('id', $this->contentId)
                ->where('user_id', Auth::id())
                ->firstOrFail();

        $service = new CommerceService();
        $result  = $service->createPromotion(
            hustler:      Auth::user(),
            profileOwner: $this->profileOwner,
            promotable:   $content,
            serviceType:  $this->serviceType,
        );

        $this->message = $result['message'];
        $this->success = $result['success'];

        if (isset($result['redirect'])) {
            $this->redirect($result['redirect']);
        }
    }

    public function with(): array
    {
        $user = Auth::user();
        return [
            'settings'   => $this->profileOwner->profileCommerceSetting,
            'editorials' => Editorial::where('user_id', $user->id)->published()->get(),
            'books'      => Book::where('user_id', $user->id)->published()->get(),
            'wallet'     => $user->wallet,
        ];
    }

}; ?>

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-6">

    {{-- Header --}}
    <div>
        <a href="{{ route('hustle.index') }}" wire:navigate
           class="text-xs text-zinc-400 hover:text-zinc-600 mb-1 block">
            ← Back to hustle
        </a>
        <flux:heading size="xl">
            Promote on {{ $profileOwner->name }}'s profile
        </flux:heading>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
            {{ number_format($profileOwner->publisherMetrics?->follower_count ?? 0) }} followers
            · {{ number_format(($profileOwner->publisherMetrics?->engagement_rate ?? 0) * 100, 1) }}% engagement
        </p>
    </div>

    @if($message)
        <flux:callout :variant="$success ? 'success' : 'danger'" :icon="$success ? 'check-circle' : 'x-circle'">
            {{ $message }}
        </flux:callout>
    @endif

    {{-- Promotion fee notice --}}
    @if($settings?->promotion_fee > 0)
        <div class="p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800 rounded-xl">
            <p class="text-sm text-amber-700 dark:text-amber-400">
                Flat promotion fee: <strong>{{ $settings->promotion_fee }} tokens</strong>
            </p>
            <p class="text-xs text-amber-600 mt-0.5">
                Your balance: {{ $wallet?->token_balance ?? 0 }} tokens
            </p>
        </div>
    @endif

    {{-- Form --}}
    <div class="space-y-4">

        {{-- Service type --}}
        <flux:field>
            <flux:label>Service type</flux:label>
            <flux:select wire:model="serviceType">
                @foreach($settings?->allowed_services ?? [] as $service)
                    <flux:select.option value="{{ $service }}">
                        {{ ucfirst(str_replace('_', ' ', $service)) }}
                    </flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="serviceType" />
        </flux:field>

        {{-- Content type --}}
        <flux:field>
            <flux:label>What are you promoting?</flux:label>
            <flux:select wire:model.live="contentType">
                <flux:select.option value="editorial">Editorial</flux:select.option>
                <flux:select.option value="book">Book</flux:select.option>
            </flux:select>
        </flux:field>

        {{-- Content selection --}}
        <flux:field>
            <flux:label>Select content</flux:label>
            <flux:select wire:model="contentId">
                <flux:select.option value="">Choose...</flux:select.option>
                @if($contentType === 'editorial')
                    @foreach($editorials as $editorial)
                        <flux:select.option value="{{ $editorial->id }}">
                            {{ $editorial->title }}
                        </flux:select.option>
                    @endforeach
                @else
                    @foreach($books as $book)
                        <flux:select.option value="{{ $book->id }}">
                            {{ $book->title }}
                        </flux:select.option>
                    @endforeach
                @endif
            </flux:select>
            <flux:error name="contentId" />
        </flux:field>

        {{-- Commission info --}}
        @if($serviceType === 'sale_refer')
            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 rounded-xl">
                <p class="text-xs text-blue-600 dark:text-blue-400 font-medium mb-2">
                    How sales referral works:
                </p>
                <ul class="text-xs text-blue-600 dark:text-blue-500 space-y-1">
                    <li>→ You get a unique referral link to share</li>
                    <li>→ Any sale made through your link earns you {{ config('commerce.hustler_commission') }}% commission</li>
                    <li>→ {{ $profileOwner->name }} earns {{ config('commerce.profile_owner_share') }}% from each sale</li>
                    <li>→ Platform takes only {{ config('commerce.platform_commerce_cut') }}%</li>
                </ul>
            </div>
        @endif

        <flux:button wire:click="submit" wire:loading.attr="disabled" variant="primary" class="w-full">
            <span wire:loading.remove>Submit promotion request</span>
            <span wire:loading>Submitting...</span>
        </flux:button>
    </div>

</div>