<?php

use App\Models\User;
use App\Services\MessageService;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

new class extends Component {

    public User $user;
    public bool $showModal = false;
    public string $body = '';
    public string $resultMessage = '';
    public bool $sent = false;

    public function mount(User $user): void
    {
        $this->user = $user;
    }

    protected function rules(): array
    {
        return [
            'body' => 'required|string|min:1|max:1000',
        ];
    }

    public function send(): void
    {
        $this->validate();

        $service = new MessageService();
        $result  = $service->getOrCreateConversation(Auth::user(), $this->user);

        if (!$result['success']) {
            $this->resultMessage = $result['message'];
            return;
        }

        $sent = $service->sendMessage(
            conversation: $result['conversation'],
            sender:       Auth::user(),
            body:         $this->body,
        );

        if ($sent['success']) {
            $this->body          = '';
            $this->sent          = true;
            $this->resultMessage = 'Message sent!';
        } else {
            $this->resultMessage = $sent['message'];
        }
    }

    public function closeModal(): void
    {
        $this->showModal     = false;
        $this->body          = '';
        $this->resultMessage = '';
        $this->sent          = false;
    }

}; ?>

<div>
    <flux:button
        wire:click="$set('showModal', true)"
        size="sm"
        variant="outline"
        icon="paper-airplane"
    >
        Message
    </flux:button>

    {{-- Message modal --}}
    @if($showModal)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-zinc-900 rounded-xl p-6 w-full max-w-md space-y-4 mx-4">

                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-zinc-900 dark:text-white">
                        Message {{ $user->name }}
                    </p>
                    <button wire:click="closeModal" class="text-zinc-400 hover:text-zinc-600">
                        <i class="ti ti-x" aria-hidden="true"></i>
                    </button>
                </div>

                @if($resultMessage)
                    <div class="p-3 rounded-lg {{ $sent
                        ? 'bg-green-50 dark:bg-green-900/20 text-green-600'
                        : 'bg-red-50 dark:bg-red-900/20 text-red-600' }}">
                        <p class="text-sm">{{ $resultMessage }}</p>
                    </div>
                @endif

                @if(!$sent)
                    <flux:field>
                        <flux:textarea
                            wire:model="body"
                            rows="4"
                            placeholder="Write your message..."
                        />
                        <flux:error name="body" />
                    </flux:field>

                    <div class="flex gap-3">
                        <flux:button
                            wire:click="send"
                            wire:loading.attr="disabled"
                            variant="primary"
                            class="flex-1"
                        >
                            <span wire:loading.remove>Send message</span>
                            <span wire:loading>Sending...</span>
                        </flux:button>
                        <flux:button wire:click="closeModal" variant="ghost">
                            Cancel
                        </flux:button>
                    </div>
                @else
                    <div class="flex gap-3">
                        <a href="{{ route('messages.inbox') }}" class="flex-1">
                            <flux:button variant="primary" class="w-full">
                                Go to inbox
                            </flux:button>
                        </a>
                        <flux:button wire:click="closeModal" variant="ghost">
                            Close
                        </flux:button>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>