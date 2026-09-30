<x-mail::message>
# Mirek ułożył puzzle! 🧩

Wszystkie {{ \App\Models\Game::PUZZLES_COUNT }} obrazków ułożone{{ $game->completed_at ? ' ('.$game->completed_at->timezone('Europe/Warsaw')->format('d.m.Y H:i').')' : '' }}.

**Trzeba mu bliknąć kasę.** 💸

<x-mail::button :url="url('/admin')">
Otwórz panel
</x-mail::button>
</x-mail::message>
