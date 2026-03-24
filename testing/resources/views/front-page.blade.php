@extends('layouts.app')

@section('content')
    @include('partials.page-header')

    @if ($featuredPost)
        <section class="mb-16">
            <article class="bg-black text-white overflow-hidden p-8">
                @if ($featuredPost->hasThumbnail())
                    <figure class="aspect-video mb-6">
                        <img src="{{ $featuredPost->thumbnail('large') }}" alt="{{ $featuredPost->title() }}" class="w-full h-full object-cover">
                    </figure>
                @endif

                <x-heading level="h2" size="2xl" class="mb-4">
                    <x-link href="{{ $featuredPost->permalink() }}" variant="unstyled" class="text-white hover:text-white transition-colors">
                        {{ $featuredPost->title() }}
                </x-link>
            </x-heading>
            <p class="text-white text-lg mb-6">{{ $featuredPost->excerpt(30) }}</p>
            <x-button href="{{ $featuredPost->permalink() }}" variant="inverse">
                Read More
            </x-button>
        </article>
        </section>
    @endif

    @if ($recentPosts->count() > 0)
        <section class="mb-16">
            <header class="mb-8">
                <x-heading level="h3" size="2xl">Recent Posts</x-heading>
            </header>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach ($recentPosts as $post)
                    <article class="bg-white overflow-hidden border border-black hover:bg-white transition-colors">
                        @if ($post->hasThumbnail())
                            <figure class="aspect-video">
                                <a href="{{ $post->permalink() }}">
                                    <img src="{{ $post->thumbnail('medium') }}" alt="{{ $post->title() }}" class="w-full h-full object-cover">
                                </a>
                            </figure>
                        @endif

                        <div class="p-6">
                            <x-heading level="h4" size="xl" class="mb-3">
                                <x-link href="{{ $post->permalink() }}" variant="unstyled" class="hover:text-black transition-colors">
                                    {{ $post->title() }}
                                </x-link>
                            </x-heading>
                            <p class="text-black mb-4">{{ $post->excerpt(20) }}</p>
                            <div class="flex items-center justify-between text-sm text-black">
                                <time datetime="{{ $post->post_date->format('Y-m-d') }}" class="font-medium">
                                    {{ $post->post_date->format('M j, Y') }}
                                </time>

                                @if ($post->categories())
                                    <div class="flex gap-2">
                                        @foreach ($post->categories() as $category)
                                            <span class="bg-black text-white px-2 py-1 text-xs">
                                                {{ $category->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    @if ($featuredSeeds->count() > 0)
        <section class="mb-16">
            <header class="flex items-center justify-between mb-8">
                <x-heading level="h3" size="2xl">Featured Seeds</x-heading>
                <x-link href="/seeds/" weight="medium">
                    View All Seeds
                </x-link>
            </header>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach ($featuredSeeds as $seed)
                    <article class="bg-white overflow-hidden border border-black hover:bg-white transition-colors">
                        @if ($seed->hasThumbnail())
                            <figure class="aspect-square">
                                <a href="{{ $seed->permalink() }}">
                                    <img src="{{ $seed->thumbnail('large') }}" alt="{{ $seed->title() }}" class="w-full h-full object-cover">
                                </a>
                            </figure>
                        @endif

                        <div class="p-4">
                            <x-heading level="h4" size="sm" class="mb-2">
                                <x-link href="{{ $seed->permalink() }}" variant="unstyled" class="hover:text-black transition-colors">
                                    {{ $seed->title() }}
                                </x-link>
                            </x-heading>
                            <p class="text-black text-sm mb-3">{{ $seed->excerpt(15) }}</p>

                            @if ($seed->categories())
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($seed->categories() as $category)
                                        <span class="bg-black text-white px-2 py-1 text-xs">
                                            {{ $category->name }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    <aside class="bg-black text-white p-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
            <div>
                <x-heading level="h4" size="3xl" color="white" class="mb-2">{{ $totalPosts }}</x-heading>
                <p class="text-white font-medium">Blog Posts</p>
            </div>
            <div>
                <x-heading level="h4" size="3xl" color="white" class="mb-2">{{ $totalSeeds }}</x-heading>
                    <p class="text-white font-medium">Seeds</p>
                </div>
            <div>
                <x-heading level="h4" size="3xl" color="white" class="mb-2">{{ $totalCategories }}</x-heading>
                <p class="text-white font-medium">Categories</p>
            </div>
        </div>
  </aside>
@endsection
