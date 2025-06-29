<div class="section">
    <h2>📋 Checklist de Migration</h2>
    <p>Utilisez cette checklist pour vous assurer que tous les aspects de la migration ont été pris en compte :</p>
    <ul class="checklist">
        @foreach($checklist as $item)
        <li>
            <div class="checkbox {{ $item['completed'] ? 'checked' : '' }}"></div>
            <div>
                <strong>{{ $item['title'] }}</strong>
                <br><small>{{ $item['description'] }}</small>
            </div>
        </li>
        @endforeach
    </ul>
</div>