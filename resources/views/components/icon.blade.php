<div>
@props(['name', 'class' => 'h-5 w-5'])

@if ($name === 'home')
    <svg xmlns="http://www.w3.org/2000/svg" class="{{ $class }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M3 12l9-9 9 9v9a3 3 0 01-3 3H6a3 3 0 01-3-3v-9z"/>
    </svg>
@endif

@if ($name === 'search')
    <svg xmlns="http://www.w3.org/2000/svg" class="{{ $class }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 110-15 7.5 7.5 0 010 15z"/>
    </svg>
@endif

@if ($name === 'cart')
    <svg xmlns="http://www.w3.org/2000/svg" class="{{ $class }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13l-1.293 2.707A1 1 0 007 17h12a1 1 0 00.894-1.447L17 7H6"/>
    </svg>
@endif

@if ($name === 'user')
    <svg xmlns="http://www.w3.org/2000/svg" class="{{ $class }}" fill="currentColor" viewBox="0 0 24 24">
        <path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 
                 2.3-5 5 2.3 5 5 5zm0 
                 2c-3.3 0-10 1.7-10 
                 5v3h20v-3c0-3.3-6.7-5-10-5z"/>
    </svg>
@endif

@if ($name === 'settings')
    <svg xmlns="http://www.w3.org/2000/svg" class="{{ $class }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M11.983 3.5a1.5 1.5 0 012.53 0l.544.94a1.5 1.5 0 001.221.75l1.086.09a1.5 1.5 0 011.34 1.34l.09 1.086a1.5 1.5 0 00.75 1.22l.94.545a1.5 1.5 0 010 2.53l-.94.545a1.5 1.5 0 00-.75 1.221l-.09 1.086a1.5 1.5 0 01-1.34 1.34l-1.086.09a1.5 1.5 0 00-1.221.75l-.545.94a1.5 1.5 0 01-2.53 0l-.544-.94a1.5 1.5 0 00-1.221-.75l-1.086-.09a1.5 1.5 0 01-1.34-1.34l-.09-1.086a1.5 1.5 0 00-.75-1.221l-.94-.545a1.5 1.5 0 010-2.53l.94-.545a1.5 1.5 0 00.75-1.22l.09-1.087a1.5 1.5 0 011.34-1.34l1.086-.09a1.5 1.5 0 001.221-.75l.545-.94z"/>
        <circle cx="12" cy="12" r="3"/>
    </svg>
@endif

@if ($name === 'filter')
    <svg xmlns="http://www.w3.org/2000/svg" class="{{ $class }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h18M6 12h12M9 19h6"/>
    </svg>
@endif

@if ($name === 'menu')
    <svg xmlns="http://www.w3.org/2000/svg" class="{{ $class }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
    </svg>
@endif


@if ($name === 'sale')
    <svg xmlns="http://www.w3.org/2000/svg" class="{{ $class }}" 
         fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
              d="M7 7h.01M3 3v6l6 6 12-12-6-6H3z"/>
        <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
              d="M16 16l4 4"/>
    </svg>
@endif


@if ($name === 'heart')
    <svg xmlns="http://www.w3.org/2000/svg" class="{{ $class }}" fill="currentColor" viewBox="0 0 24 24">
        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 
                 2 6 4 4 6.5 4c1.74 0 3.41 1 4.22 2.44C11.09 5 
                 12.76 4 14.5 4 17 4 19 6 19 8.5c0 3.78-3.4 
                 6.86-8.55 11.54L12 21.35z"/>
    </svg>
@endif

</div>