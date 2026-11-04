@props(['name'])
<svg {{ $attributes->merge(['class' => 'icon']) }} viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
@switch($name)
    @case('search')<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>@break
    @case('cart')<path d="M3 4h2l2.2 11h10.6L20 7H6"/><circle cx="9" cy="19.5" r="1.3"/><circle cx="16.5" cy="19.5" r="1.3"/>@break
    @case('chat')<path d="M4 20l1.4-4.2A8 8 0 1 1 8.6 19L4 20Z"/><path d="M9 9.5c0 3 2.5 5.5 5.5 5.5l1.2-1.6-2-1-.9.8a4 4 0 0 1-2-2l.8-.9-1-2L9 9.5Z"/>@break
    @case('pin')<path d="M12 21s7-6.1 7-11.5A7 7 0 0 0 5 9.5C5 14.9 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.4"/>@break
    @case('ruler')<path d="M3 16.5 16.5 3 21 7.5 7.5 21 3 16.5Z"/><path d="m7 12.5 2 2M10 9.5l2 2M13 6.5l2 2"/>@break
    @case('ring')<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="4"/><circle cx="12" cy="5.5" r=".8"/><circle cx="12" cy="18.5" r=".8"/><circle cx="5.5" cy="12" r=".8"/><circle cx="18.5" cy="12" r=".8"/>@break
    @case('instagram')<rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17" cy="7" r=".8" fill="currentColor"/>@break
    @case('price')<path d="M4 12V4h8l8 8-8 8-8-8Z"/><circle cx="8.5" cy="8.5" r="1.2"/>@break
    @case('arrow')<path d="M5 12h14M13 6l6 6-6 6"/>@break
    @case('check')<path d="m5 12.5 4.5 4.5L19 7.5"/>@break
    @case('home')<path d="M4 11 12 4l8 7"/><path d="M6 10v10h12V10"/>@break
    @case('box')<path d="M4 8 12 4l8 4v8l-8 4-8-4V8Z"/><path d="m4 8 8 4 8-4M12 12v8"/>@break
    @case('file')<path d="M7 3h7l4 4v14H7V3Z"/><path d="M14 3v4h4M10 12h5M10 16h5"/>@break
    @case('users')<circle cx="9" cy="8" r="3.5"/><path d="M3 20c.6-3.6 3-5.5 6-5.5s5.4 1.9 6 5.5"/><path d="M16 5a3.5 3.5 0 0 1 0 6.5M18 14.8c1.6.7 2.6 2.4 3 5.2"/>@break
    @case('layers')<path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 13 9 5 9-5"/>@break
    @case('chart')<path d="M4 20V4M4 20h16"/><path d="M8 16v-4M12 16V8M16 16v-6"/>@break
    @case('gear')<circle cx="12" cy="12" r="3"/><path d="M12 3v3M12 18v3M3 12h3M18 12h3M5.6 5.6l2.1 2.1M16.3 16.3l2.1 2.1M18.4 5.6l-2.1 2.1M7.7 16.3l-2.1 2.1"/>@break
@endswitch
</svg>
