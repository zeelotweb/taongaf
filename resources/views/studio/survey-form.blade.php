<x-layouts::app :title="__('Survey')">
    
        <livewire:studio.survey-form :survey="$survey ?? app(App\Models\Survey::class)" />
    
</x-layouts::app>