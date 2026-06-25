@extends('layouts.app')
@section('title', 'Pusat Bantuan & Tutorial')
@section('page-title', 'Bantuan & Tutorial')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/faq.css') }}">
@endpush

@section('content')
    <div class="faq-wrapper">
        <!-- Hero Header -->
        <div class="faq-hero">
            <div class="faq-hero-content">
                <h2>Pusat Bantuan & Panduan ERP</h2>
                <p>Temukan panduan alur kerja sistem, solusi pemecahan masalah (FAQ), dan panduan langkah demi langkah untuk
                    memaksimalkan operasional bisnis retail multi-channel Anda.</p>

                @if (auth()->user() && (auth()->user()->role === 'admin' || auth()->user()->hasRole('admin')))
                    <div class="mb-4">
                        <a href="{{ route('faq.manage') }}" class="btn btn-primary btn-sm px-3 py-2"
                            style="border-radius: 8px; font-weight: 700;">
                            <i class="fas fa-cog me-1"></i> Kelola Panduan & FAQ
                        </a>
                    </div>
                @endif

                <div class="search-container">
                    <input type="text" id="faq-search" class="search-input"
                        placeholder="Cari tutorial atau kata kunci (misal: 'mapping', 'shopee', 'retur', 'pos')...">
                    <i class="fas fa-search search-icon"></i>
                </div>

                <div class="popular-tags">
                    <span class="popular-label">Pencarian Populer:</span>
                    <button class="tag-pill" data-query="Mapping SKU">Mapping SKU</button>
                    <button class="tag-pill" data-query="Re-otorisasi">Re-otorisasi</button>
                    <button class="tag-pill" data-query="Scan Kemas">Scan Kemas</button>
                    <button class="tag-pill" data-query="Stok Opname">Stok Opname</button>
                    <button class="tag-pill" data-query="Biaya Admin">Biaya Admin</button>
                    <button class="tag-pill" data-query="POS Offline">POS Offline</button>
                    <button class="tag-pill" data-query="Inbox Chat">Inbox Chat</button>
                </div>
            </div>
        </div>

        @if ($categories->isEmpty())
            <div class="text-center py-5 dashboard-card">
                <i class="fas fa-info-circle text-muted mb-3" style="font-size: 3rem;"></i>
                <h4 class="text-white">Belum Ada Panduan</h4>
                <p class="text-muted">Gunakan tombol "Kelola Panduan" di atas untuk menambahkan kategori dan panduan pertama Anda.</p>
            </div>
        @else
            <!-- Main Grid Layout -->
            <div class="row g-4">
                <!-- Sidebar Navigation -->
                <div class="col-lg-4 col-xl-3">
                    <div class="faq-sidebar">
                        @foreach ($categories as $index => $category)
                            <button class="category-nav-card {{ $index === 0 ? 'active' : '' }}"
                                data-category="{{ $category->slug }}"
                                data-default-count="{{ $category->workflows->count() + $category->faqs->count() }}"
                                style="--cat-color: {{ $category->color }}; --cat-color-rgb: {{ $category->color_rgb }};">
                                <div class="cat-icon-wrap"><i class="{{ $category->icon }}"></i></div>
                                <div class="cat-info">
                                    <h4>{{ $category->name }}</h4>
                                    <p>{{ $category->subtitle }}</p>
                                </div>
                                <span class="cat-badge"
                                    id="badge-{{ $category->slug }}">{{ $category->workflows->count() + $category->faqs->count() }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Content Area -->
                <div class="col-lg-8 col-xl-9">
                    <div class="content-card-wrapper">
                        <!-- Searching Not Found Screen -->
                        <div class="search-not-found" id="search-empty">
                            <i class="fas fa-search-minus"></i>
                            <h4>Pencarian Tidak Ditemukan</h4>
                            <p>Maaf, kami tidak dapat menemukan hasil untuk kata kunci tersebut. Coba gunakan kata kunci lainnya.</p>
                        </div>

                        @foreach ($categories as $index => $category)
                            <div class="content-card {{ $index === 0 ? 'show-active' : '' }}"
                                data-category="{{ $category->slug }}"
                                style="--cat-color: {{ $category->color }}; --cat-color-rgb: {{ $category->color_rgb }};">

                                <div class="content-header">
                                    <div class="content-header-icon"><i class="{{ $category->icon }}"></i></div>
                                    <div class="content-header-title">
                                        <h3>{{ $category->name }}</h3>
                                        <p>{{ $category->subtitle }}</p>
                                    </div>
                                    <div class="content-header-meta">
                                        @if ($category->read_time)
                                            <div>Estimasi baca: {{ $category->read_time }}</div>
                                        @endif
                                        <div>Terakhir update: {{ $category->updated_at->translatedFormat('d F Y') }}</div>
                                    </div>
                                </div>

                                @if ($category->workflows->count() > 0)
                                    <div class="section-title-wrap">
                                        <i class="fas fa-project-diagram"></i>
                                        <h4>{{ $category->workflow_title }}</h4>
                                    </div>

                                    <div class="workflow-timeline">
                                        @foreach ($category->workflows as $step)
                                            <div class="timeline-item">
                                                <div class="timeline-number">{{ $step->sort_order }}</div>
                                                <div class="timeline-content">
                                                    <h5>{!! $step->title !!}</h5>
                                                    <p>{!! $step->content !!}</p>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                @if ($category->faqs->count() > 0)
                                    <div class="section-title-wrap mt-4">
                                        <i class="fas fa-question-circle"></i>
                                        <h4>Pertanyaan Populer (FAQ)</h4>
                                    </div>

                                    <div class="faq-accordion">
                                        @foreach ($category->faqs as $faq)
                                            <div class="accordion-item">
                                                <button class="accordion-trigger">{{ $faq->title }} <i
                                                        class="fas fa-chevron-down"></i></button>
                                                <div class="accordion-content">
                                                    {!! $faq->content !!}
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <div class="feedback-widget">
                                    <div class="feedback-question">Apakah panduan kategori ini membantu Anda?</div>
                                    <div class="feedback-btns">
                                        <button class="feedback-btn yes"><i class="far fa-thumbs-up"></i> Ya, membantu</button>
                                        <button class="feedback-btn no"><i class="far fa-thumbs-down"></i> Kurang membantu</button>
                                    </div>
                                    <div class="feedback-response"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <!-- Extra Help Desk Cards -->
        <div class="row g-3 mt-4">
            <div class="col-md-6">
                <div class="help-card h-100">
                    <div class="help-card-icon whatsapp"><i class="fab fa-whatsapp"></i></div>
                    <div class="help-card-info d-flex flex-column h-100">
                        <h5>WhatsApp Customer Service</h5>
                        <p class="flex-grow-1">Butuh bantuan teknis cepat? Hubungi tim support kami via obrolan WhatsApp untuk respon instan harian.</p>
                        <div>
                            <a href="https://wa.me/6281234567890?text=Halo%20ERP%20Marketplace%20Support%2C%20saya%20butuh%20bantuan%20mengenai..."
                                target="_blank" class="help-card-btn">Hubungi WA Support</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="help-card h-100">
                    <div class="help-card-icon"><i class="fas fa-ticket-alt"></i></div>
                    <div class="help-card-info d-flex flex-column h-100">
                        <h5>Buat Tiket Bantuan</h5>
                        <p class="flex-grow-1">Kirimkan kendala server, bug sistem, atau permintaan custom fitur melalui sistem tiket bantuan kami.</p>
                        <div>
                            <a href="#" class="help-card-btn">Kirim Tiket Masalah</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const searchInput = document.getElementById('faq-search');
                const categoryCards = document.querySelectorAll('.category-nav-card');
                const contentCards = document.querySelectorAll('.content-card');
                const popularTags = document.querySelectorAll('.tag-pill');
                const searchEmptyState = document.getElementById('search-empty');

                // 1. Initial State: Load active category from localStorage or default to first category slug
                let savedCategory = localStorage.getItem('active_faq_category');
                const firstCategoryCard = document.querySelector('.category-nav-card');
                const defaultCategory = firstCategoryCard ? firstCategoryCard.getAttribute('data-category') : null;

                if (!savedCategory || !document.querySelector(`.category-nav-card[data-category="${savedCategory}"]`)) {
                    savedCategory = defaultCategory;
                }

                if (savedCategory) {
                    switchCategory(savedCategory);
                }

                // 2. Click category handler
                categoryCards.forEach(card => {
                    card.addEventListener('click', function() {
                        const category = this.getAttribute('data-category');
                        switchCategory(category);

                        // Clear search field on manual category change to prevent confusion
                        if (searchInput.value !== '') {
                            searchInput.value = '';
                            performSearch('');
                        }
                    });
                });

                // Function to switch active category
                function switchCategory(categoryName) {
                    categoryCards.forEach(card => {
                        if (card.getAttribute('data-category') === categoryName) {
                            card.classList.add('active');
                        } else {
                            card.classList.remove('active');
                        }
                    });

                    contentCards.forEach(content => {
                        if (content.getAttribute('data-category') === categoryName) {
                            content.classList.add('show-active');
                        } else {
                            content.classList.remove('show-active');
                        }
                    });

                    localStorage.setItem('active_faq_category', categoryName);
                }

                // 3. Custom Accordion Toggle Logic
                const accordionTriggers = document.querySelectorAll('.accordion-trigger');
                accordionTriggers.forEach(trigger => {
                    trigger.addEventListener('click', function() {
                        const item = this.parentElement;
                        const content = this.nextElementSibling;
                        const isActive = item.classList.contains('active');

                        if (isActive) {
                            // If currently set to 'none' (finished expanding), restore scrollHeight to animate collapse
                            if (content.style.maxHeight === 'none') {
                                content.style.maxHeight = content.scrollHeight + "px";
                                void content.offsetHeight; // force reflow
                            }
                            item.classList.remove('active');
                            content.style.maxHeight = null;
                        } else {
                            // Close other active items within the same card first (optional, for accordion effect)
                            const parentCard = this.closest('.content-card');
                            parentCard.querySelectorAll('.accordion-item.active').forEach(
                                activeItem => {
                                    const activeContent = activeItem.querySelector(
                                        '.accordion-content');
                                    if (activeContent.style.maxHeight === 'none') {
                                        activeContent.style.maxHeight = activeContent.scrollHeight +
                                            "px";
                                        void activeContent.offsetHeight;
                                    }
                                    activeItem.classList.remove('active');
                                    activeContent.style.maxHeight = null;
                                });

                            item.classList.add('active');
                            content.style.maxHeight = content.scrollHeight + "px";

                            // Reset max-height to 'none' after transition ends so content resizing or wraps don't get clipped
                            const onTransitionEnd = function(e) {
                                if (e.propertyName === 'max-height') {
                                    if (item.classList.contains('active')) {
                                        content.style.maxHeight = 'none';
                                    }
                                    content.removeEventListener('transitionend', onTransitionEnd);
                                }
                            };
                            content.addEventListener('transitionend', onTransitionEnd);
                        }
                    });
                });

                // 4. Text highlighting logic
                function removeHighlights(container) {
                    container.querySelectorAll('mark.search-highlight').forEach(mark => {
                        const parent = mark.parentNode;
                        const textNode = document.createTextNode(mark.textContent);
                        parent.replaceChild(textNode, mark);
                        parent.normalize();
                    });
                }

                function addHighlights(element, query) {
                    if (!query) return;
                    const cleanQuery = query.trim().replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
                    const regex = new RegExp(`(${cleanQuery})`, 'gi');

                    // Recursive search for text nodes
                    const textNodes = [];
                    const walk = (node) => {
                        if (node.nodeType === 3) { // Text Node
                            if (node.nodeValue.match(regex)) {
                                textNodes.push(node);
                            }
                        } else if (node.nodeType === 1 && node.childNodes && !['SCRIPT', 'STYLE', 'MARK', 'BUTTON',
                                'I'
                            ].includes(node.nodeName)) {
                            // Only search inside structural tags, avoid icons or buttons content directly
                            Array.from(node.childNodes).forEach(walk);
                        }
                    };

                    walk(element);

                    textNodes.forEach(node => {
                        const text = node.nodeValue;
                        const matches = text.match(regex);
                        if (matches && node.parentNode) {
                            const parent = node.parentNode;
                            const wrapper = document.createElement('span');
                            wrapper.innerHTML = text.replace(regex, '<mark class="search-highlight">$1</mark>');

                            // Replace text node with highlighted nodes
                            while (wrapper.firstChild) {
                                parent.insertBefore(wrapper.firstChild, node);
                            }
                            parent.removeChild(node);
                        }
                    });
                }

                // 5. Core Search Logic
                searchInput.addEventListener('input', function() {
                    const query = this.value.toLowerCase().trim();
                    performSearch(query);
                });

                // Popular keywords pills handler
                popularTags.forEach(tag => {
                    tag.addEventListener('click', function() {
                        const query = this.getAttribute('data-query');
                        searchInput.value = query;
                        searchInput.focus();
                        performSearch(query.toLowerCase());
                    });
                });

                function performSearch(query) {
                    // Reset all highlights first
                    contentCards.forEach(card => removeHighlights(card));

                    if (query.length < 2) {
                        // Restore normal view
                        searchEmptyState.style.display = 'none';

                        // Restore default category display
                        categoryCards.forEach(c => c.style.display = 'flex');

                        // Restore default badge numbers dynamically
                        categoryCards.forEach(card => {
                            const badge = card.querySelector('.cat-badge');
                            const defaultCount = card.getAttribute('data-default-count');
                            badge.innerText = defaultCount;

                            badge.style.background = '';
                            badge.style.color = '';
                            badge.style.borderColor = '';
                        });

                        // Collapse all accordions
                        document.querySelectorAll('.accordion-item').forEach(item => {
                            item.classList.remove('active');
                            item.querySelector('.accordion-content').style.maxHeight = null;
                        });

                        // Show current saved category
                        if (savedCategory) {
                            switchCategory(savedCategory);
                        }
                        return;
                    }

                    let totalMatchesFound = 0;
                    let firstMatchingCat = null;
                    const catMatches = {};

                    // Search inside each category content card
                    contentCards.forEach(card => {
                        const category = card.getAttribute('data-category');
                        let catMatchCount = 0;

                        // Check timeline workflow steps
                        const timelineItems = card.querySelectorAll('.timeline-content');
                        timelineItems.forEach(item => {
                            const text = item.textContent.toLowerCase();
                            if (text.includes(query)) {
                                catMatchCount++;
                                addHighlights(item, query);
                            }
                        });

                        // Check accordion FAQs
                        const accordionItems = card.querySelectorAll('.accordion-item');
                        accordionItems.forEach(item => {
                            const headerText = item.querySelector('.accordion-trigger').textContent
                                .toLowerCase();
                            const contentText = item.querySelector('.accordion-content').textContent
                                .toLowerCase();

                            const isMatchHeader = headerText.includes(query);
                            const isMatchContent = contentText.includes(query);

                            if (isMatchHeader || isMatchContent) {
                                catMatchCount++;

                                // Auto-expand matched FAQ item
                                item.classList.add('active');
                                const accordionContent = item.querySelector('.accordion-content');
                                accordionContent.style.maxHeight = 'none';

                                // Highlight matches
                                addHighlights(item.querySelector('.accordion-trigger'), query);
                                addHighlights(accordionContent, query);
                            } else {
                                // Collapse unmatched FAQ item
                                item.classList.remove('active');
                                item.querySelector('.accordion-content').style.maxHeight = null;
                            }
                        });

                        catMatches[category] = catMatchCount;
                        totalMatchesFound += catMatchCount;

                        if (catMatchCount > 0 && !firstMatchingCat) {
                            firstMatchingCat = category;
                        }
                    });

                    // Update sidebar state based on matches
                    categoryCards.forEach(card => {
                        const category = card.getAttribute('data-category');
                        const badge = card.querySelector('.cat-badge');
                        const matches = catMatches[category] || 0;

                        if (matches > 0) {
                            card.style.display = 'flex';
                            badge.innerText = matches;
                            badge.style.background = 'var(--warning-glow)';
                            badge.style.color = 'var(--warning)';
                            badge.style.borderColor = 'rgba(245, 158, 11, 0.3)';
                        } else {
                            // Hide unmatched category cards
                            card.style.display = 'none';
                        }
                    });

                    if (totalMatchesFound > 0) {
                        searchEmptyState.style.display = 'none';

                        // Auto-switch to the first matching category if the current one has no matches
                        const activeNavCard = document.querySelector('.category-nav-card.active');
                        const currentCat = activeNavCard ? activeNavCard.getAttribute('data-category') : null;

                        if (!currentCat || (catMatches[currentCat] || 0) === 0) {
                            switchCategory(firstMatchingCat);
                        } else {
                            // Refresh layout of active card
                            const activeContentCard = document.querySelector('.content-card.show-active');
                            if (activeContentCard) {
                                activeContentCard.classList.remove('show-active');
                                void activeContentCard.offsetWidth; // force redraw
                                activeContentCard.classList.add('show-active');
                            }
                        }
                    } else {
                        // Display not found screen
                        searchEmptyState.style.display = 'block';
                        contentCards.forEach(c => c.classList.remove('show-active'));
                        categoryCards.forEach(c => c.style.display = 'none');
                    }
                }

                // 6. Interactive Feedback Widgets
                const feedbackContainers = document.querySelectorAll('.feedback-widget');
                feedbackContainers.forEach(container => {
                    const btnsContainer = container.querySelector('.feedback-btns');
                    const responseText = container.querySelector('.feedback-response');
                    const yesBtn = container.querySelector('.feedback-btn.yes');
                    const noBtn = container.querySelector('.feedback-btn.no');

                    yesBtn.addEventListener('click', function() {
                        btnsContainer.style.display = 'none';
                        responseText.innerText =
                            'Senang bisa membantu! Terima kasih atas masukan Anda. ❤️';
                        responseText.style.display = 'block';
                    });

                    noBtn.addEventListener('click', function() {
                        btnsContainer.style.display = 'none';
                        responseText.innerText =
                            'Terima kasih atas masukannya. Masukan Anda direkam untuk membantu meningkatkan halaman panduan ini. 🙏';
                        responseText.style.color = 'var(--text-secondary)';
                        responseText.style.display = 'block';
                    });
                });
            });
        </script>
    @endpush
@endsection
