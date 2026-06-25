@extends('layouts.app')
@section('title', 'Pusat Bantuan & Tutorial')
@section('page-title', 'Bantuan & Tutorial')



@section('content')
    <div class="container-fluid px-0">
        <!-- Hero Header -->
        <div class="card border mb-4 bg-light shadow-sm text-center">
            <div class="card-body p-5">
                <h2 class="fw-bold text-dark mb-2">Pusat Bantuan & Panduan ERP</h2>
                <p class="text-muted mx-auto mb-4" style="max-width: 800px;">
                    Temukan panduan alur kerja sistem, solusi pemecahan masalah (FAQ), dan panduan langkah demi langkah
                    untuk
                    memaksimalkan operasional bisnis retail multi-channel Anda.
                </p>

                @if (auth()->user() && (auth()->user()->role === 'admin' || auth()->user()->hasRole('admin')))
                    <div class="mb-4">
                        <a href="{{ route('faq.manage') }}" class="btn btn-primary btn-sm px-3 py-2 fw-bold"
                            style="border-radius: 8px;">
                            <i class="fas fa-cog me-1"></i> Kelola Panduan & FAQ
                        </a>
                    </div>
                @endif

                <div class="mx-auto mb-3" style="max-width: 600px;">
                    <div class="input-group shadow-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" id="faq-search" class="form-control border-start-0 py-2.5"
                            placeholder="Cari tutorial atau kata kunci (misal: 'mapping', 'shopee', 'retur', 'pos')...">
                    </div>
                </div>

                <div class="d-flex flex-wrap justify-content-center align-items-center gap-2 mt-3">
                    <span class="small text-muted me-1">Pencarian Populer:</span>
                    <button class="btn btn-outline-secondary btn-sm rounded-pill tag-pill py-1 px-3"
                        data-query="Mapping SKU">Mapping SKU</button>
                    <button class="btn btn-outline-secondary btn-sm rounded-pill tag-pill py-1 px-3"
                        data-query="Re-otorisasi">Re-otorisasi</button>
                    <button class="btn btn-outline-secondary btn-sm rounded-pill tag-pill py-1 px-3"
                        data-query="Scan Kemas">Scan Kemas</button>
                    <button class="btn btn-outline-secondary btn-sm rounded-pill tag-pill py-1 px-3"
                        data-query="Stok Opname">Stok Opname</button>
                    <button class="btn btn-outline-secondary btn-sm rounded-pill tag-pill py-1 px-3"
                        data-query="Biaya Admin">Biaya Admin</button>
                    <button class="btn btn-outline-secondary btn-sm rounded-pill tag-pill py-1 px-3"
                        data-query="POS Offline">POS Offline</button>
                    <button class="btn btn-outline-secondary btn-sm rounded-pill tag-pill py-1 px-3"
                        data-query="Inbox Chat">Inbox Chat</button>
                </div>
            </div>
        </div>

        @if ($categories->isEmpty())
            <div class="text-center py-5 card border shadow-sm mb-4">
                <div class="card-body">
                    <i class="fas fa-info-circle text-muted mb-3" style="font-size: 3rem;"></i>
                    <h4 class="text-dark fw-bold">Belum Ada Panduan</h4>
                    <p class="text-muted mb-0">Gunakan tombol "Kelola Panduan" di atas untuk menambahkan kategori dan
                        panduan pertama Anda.</p>
                </div>
            </div>
        @else
            <!-- Main Grid Layout -->
            <div class="row g-4">
                <!-- Sidebar Navigation -->
                <div class="col-lg-4 col-xl-3">
                    <div class="list-group category-list">
                        @foreach ($categories as $index => $category)
                            <button
                                class="list-group-item list-group-item-action d-flex align-items-center justify-content-between p-3 border rounded category-nav-card mb-2 {{ $index === 0 ? 'active bg-light border-primary' : 'bg-white' }}"
                                data-category="{{ $category->slug }}"
                                data-default-count="{{ $category->workflows->count() + $category->faqs->count() }}"
                                style="border-left: 4px solid {{ $category->color }} !important;">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="fs-4" style="color: {{ $category->color }};"><i
                                            class="{{ $category->icon }}"></i></div>
                                    <div class="text-start">
                                        <h6 class="mb-0 fw-bold text-dark">{{ $category->name }}</h6>
                                        <small class="text-muted">{{ $category->subtitle }}</small>
                                    </div>
                                </div>
                                <span class="badge bg-secondary rounded-pill cat-badge"
                                    id="badge-{{ $category->slug }}">{{ $category->workflows->count() + $category->faqs->count() }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Content Area -->
                <div class="col-lg-8 col-xl-9">
                    <div class="content-card-wrapper">
                        <!-- Searching Not Found Screen -->
                        <div class="card border shadow-sm text-center py-5 px-4 mb-4" id="search-empty"
                            style="display: none;">
                            <div class="card-body">
                                <i class="fas fa-search-minus text-muted mb-3" style="font-size: 3rem;"></i>
                                <h4 class="text-dark fw-bold">Pencarian Tidak Ditemukan</h4>
                                <p class="text-muted mb-0">Maaf, kami tidak dapat menemukan hasil untuk kata kunci tersebut.
                                    Coba gunakan kata kunci lainnya.</p>
                            </div>
                        </div>

                        @foreach ($categories as $index => $category)
                            <div class="card border shadow-sm content-card mb-4 {{ $index === 0 ? 'show-active' : '' }}"
                                data-category="{{ $category->slug }}"
                                style="display: {{ $index === 0 ? 'block' : 'none' }}; border-left: 4px solid {{ $category->color }} !important;">
                                <div class="card-body p-4">

                                    <div
                                        class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-3 flex-wrap gap-2">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="fs-2" style="color: {{ $category->color }};"><i
                                                    class="{{ $category->icon }}"></i></div>
                                            <div>
                                                <h3 class="h5 fw-bold text-dark mb-1">{{ $category->name }}</h3>
                                                <p class="text-muted mb-0 small">{{ $category->subtitle }}</p>
                                            </div>
                                        </div>
                                        <div class="text-end text-muted small">
                                            @if ($category->read_time)
                                                <div><i class="far fa-clock"></i> Estimasi baca: {{ $category->read_time }}
                                                </div>
                                            @endif
                                            <div><i class="far fa-calendar-alt"></i> Terakhir update:
                                                {{ $category->updated_at->translatedFormat('d F Y') }}</div>
                                        </div>
                                    </div>

                                    @if ($category->workflows->count() > 0)
                                        <div class="d-flex align-items-center gap-2 mb-3 mt-4 text-dark">
                                            <i class="fas fa-project-diagram text-primary"></i>
                                            <h5 class="fw-bold mb-0" style="font-size: 1.05rem;">
                                                {{ $category->workflow_title }}</h5>
                                        </div>

                                        <div class="workflow-timeline mb-4">
                                            @foreach ($category->workflows as $step)
                                                <div class="d-flex align-items-stretch mb-3">
                                                    <div class="d-flex flex-column align-items-center me-3 col-auto">
                                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm"
                                                            style="width: 28px; height: 28px; min-width: 28px; min-height: 28px; font-size: 0.85rem; z-index: 2;">
                                                            {{ $step->sort_order }}
                                                        </div>
                                                        @if (!$loop->last)
                                                            <div class="vr flex-grow-1 bg-secondary opacity-25 my-2"></div>
                                                        @endif
                                                    </div>
                                                    <div class="card border flex-grow-1">
                                                        <div class="card-body p-3 timeline-content">
                                                            <h6 class="fw-bold text-dark mb-2">{!! $step->title !!}</h6>
                                                            <p class="text-muted mb-0 small">{!! $step->content !!}</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if ($category->faqs->count() > 0)
                                        <div class="d-flex align-items-center gap-2 mb-3 mt-4 text-dark">
                                            <i class="fas fa-question-circle text-warning"></i>
                                            <h5 class="fw-bold mb-0" style="font-size: 1.05rem;">Pertanyaan Populer (FAQ)
                                            </h5>
                                        </div>

                                        <div class="accordion d-flex flex-column gap-2 mb-4"
                                            id="faqAccordion-{{ $category->id }}">
                                            @foreach ($category->faqs as $faq)
                                                <div class="accordion-item border shadow-sm rounded">
                                                    <h2 class="accordion-header" id="faqHeading-{{ $faq->id }}">
                                                        <button
                                                            class="accordion-button collapsed fw-bold py-3 px-4 text-dark bg-white"
                                                            type="button" data-bs-toggle="collapse"
                                                            data-bs-target="#faqCollapse-{{ $faq->id }}"
                                                            aria-expanded="false"
                                                            aria-controls="faqCollapse-{{ $faq->id }}">
                                                            {{ $faq->title }}
                                                        </button>
                                                    </h2>
                                                    <div id="faqCollapse-{{ $faq->id }}"
                                                        class="accordion-collapse collapse"
                                                        aria-labelledby="faqHeading-{{ $faq->id }}"
                                                        data-bs-parent="#faqAccordion-{{ $category->id }}">
                                                        <div class="accordion-body text-muted small bg-light py-3 px-4">
                                                            {!! $faq->content !!}
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    <div
                                        class="d-flex justify-content-between align-items-center border-top pt-3 mt-4 flex-wrap gap-2 feedback-widget">
                                        <div class="small fw-semibold text-dark feedback-question">Apakah panduan kategori
                                            ini membantu Anda?</div>
                                        <div class="d-flex gap-2 feedback-btns">
                                            <button class="btn btn-outline-success btn-sm feedback-btn yes"><i
                                                    class="far fa-thumbs-up"></i> Ya, membantu</button>
                                            <button class="btn btn-outline-danger btn-sm feedback-btn no"><i
                                                    class="far fa-thumbs-down"></i> Kurang membantu</button>
                                        </div>
                                        <div class="text-success small fw-bold feedback-response" style="display: none;">
                                        </div>
                                    </div>
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
                <div class="card border shadow-sm h-100">
                    <div class="card-body d-flex gap-3 p-4">
                        <div class="fs-2 text-success"><i class="fab fa-whatsapp"></i></div>
                        <div class="d-flex flex-column h-100">
                            <h5 class="fw-bold text-dark mb-1">WhatsApp Customer Service</h5>
                            <p class="text-muted small flex-grow-1 mb-3">Butuh bantuan teknis cepat? Hubungi tim support
                                kami via obrolan WhatsApp untuk respon instan harian.</p>
                            <div>
                                <a href="https://wa.me/6281234567890?text=Halo%20ERP%20Marketplace%20Support%2C%20saya%20butuh%20bantuan%20mengenai..."
                                    target="_blank" class="btn btn-success btn-sm px-3 fw-semibold">Hubungi WA Support</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card border shadow-sm h-100">
                    <div class="card-body d-flex gap-3 p-4">
                        <div class="fs-2 text-primary"><i class="fas fa-ticket-alt"></i></div>
                        <div class="d-flex flex-column h-100">
                            <h5 class="fw-bold text-dark mb-1">Buat Tiket Bantuan</h5>
                            <p class="text-muted small flex-grow-1 mb-3">Kirimkan kendala server, bug sistem, atau
                                permintaan custom fitur melalui sistem tiket bantuan kami.</p>
                            <div>
                                <a href="#" class="btn btn-primary btn-sm px-3 fw-semibold">Kirim Tiket Masalah</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    @push('scripts')
        <script>
            $(document).ready(function() {
                const $searchInput = $('#faq-search');
                const $categoryCards = $('.category-nav-card');
                const $contentCards = $('.content-card');
                const $popularTags = $('.tag-pill');
                const $searchEmptyState = $('#search-empty');

                // 1. Initial State: Load active category from localStorage or default to first category slug
                let savedCategory = localStorage.getItem('active_faq_category');
                const firstCategorySlug = $categoryCards.first().data('category');

                if (!savedCategory || !$(`.category-nav-card[data-category="${savedCategory}"]`).length) {
                    savedCategory = firstCategorySlug;
                }

                if (savedCategory) {
                    switchCategory(savedCategory);
                }

                // 2. Click category handler
                $categoryCards.on('click', function() {
                    const category = $(this).data('category');
                    switchCategory(category);

                    // Clear search field on manual category change to prevent confusion
                    if ($searchInput.val() !== '') {
                        $searchInput.val('');
                        performSearch('');
                    }
                });

                // Function to switch active category
                function switchCategory(categoryName) {
                    $categoryCards.each(function() {
                        const $card = $(this);
                        if ($card.data('category') === categoryName) {
                            $card.addClass('active bg-light border-primary');
                            $card.removeClass('bg-white');
                        } else {
                            $card.removeClass('active bg-light border-primary');
                            $card.addClass('bg-white');
                        }
                    });

                    $contentCards.each(function() {
                        const $content = $(this);
                        if ($content.data('category') === categoryName) {
                            $content.addClass('show-active').show();
                        } else {
                            $content.removeClass('show-active').hide();
                        }
                    });

                    localStorage.setItem('active_faq_category', categoryName);
                }

                // 4. Text highlighting logic
                function removeHighlights(container) {
                    $(container).find('mark.search-highlight').each(function() {
                        const parent = this.parentNode;
                        const textNode = document.createTextNode($(this).text());
                        parent.replaceChild(textNode, this);
                        parent.normalize();
                    });
                }

                // Custom walk function that operates on element DOM node
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
                            wrapper.innerHTML = text.replace(regex,
                                '<mark class="bg-warning text-dark px-1 rounded search-highlight">$1</mark>'
                            );

                            // Replace text node with highlighted nodes
                            while (wrapper.firstChild) {
                                parent.insertBefore(wrapper.firstChild, node);
                            }
                            parent.removeChild(node);
                        }
                    });
                }

                // 5. Core Search Logic
                $searchInput.on('input', function() {
                    const query = $(this).val().toLowerCase().trim();
                    performSearch(query);
                });

                // Popular keywords pills handler
                $popularTags.on('click', function() {
                    const query = $(this).data('query');
                    $searchInput.val(query).focus();
                    performSearch(query.toLowerCase());
                });

                function performSearch(query) {
                    // Reset all highlights first
                    $contentCards.each(function() {
                        removeHighlights(this);
                    });

                    if (query.length < 2) {
                        // Restore normal view
                        $searchEmptyState.hide();

                        // Restore default category display
                        $categoryCards.css('display', 'flex');

                        // Restore default badge numbers dynamically
                        $categoryCards.each(function() {
                            const $card = $(this);
                            const $badge = $card.find('.cat-badge');
                            const defaultCount = $card.attr('data-default-count');
                            $badge.text(defaultCount);
                            $badge.removeClass('bg-warning text-dark').addClass('bg-secondary');
                        });

                        // Collapse all accordions
                        $('.accordion-collapse').removeClass('show');
                        $('.accordion-button').addClass('collapsed').attr('aria-expanded', 'false');

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
                    $contentCards.each(function() {
                        const $card = $(this);
                        const category = $card.data('category');
                        let catMatchCount = 0;

                        // Check timeline workflow steps
                        const $timelineItems = $card.find('.timeline-content');
                        $timelineItems.each(function() {
                            const $item = $(this);
                            const text = $item.text().toLowerCase();
                            if (text.includes(query)) {
                                catMatchCount++;
                                addHighlights($item[0], query);
                            }
                        });

                        // Check accordion FAQs
                        const $accordionItems = $card.find('.accordion-item');
                        $accordionItems.each(function() {
                            const $item = $(this);
                            const $trigger = $item.find('.accordion-button');
                            const $collapse = $item.find('.accordion-collapse');
                            const headerText = $trigger.text().toLowerCase();
                            const contentText = $collapse.text().toLowerCase();

                            const isMatchHeader = headerText.includes(query);
                            const isMatchContent = contentText.includes(query);

                            if (isMatchHeader || isMatchContent) {
                                catMatchCount++;

                                // Auto-expand matched FAQ item
                                $trigger.removeClass('collapsed').attr('aria-expanded', 'true');
                                $collapse.addClass('show');

                                // Highlight matches
                                addHighlights($trigger[0], query);
                                addHighlights($collapse.find('.accordion-body')[0], query);
                            } else {
                                // Collapse unmatched FAQ item
                                $trigger.addClass('collapsed').attr('aria-expanded', 'false');
                                $collapse.removeClass('show');
                            }
                        });

                        catMatches[category] = catMatchCount;
                        totalMatchesFound += catMatchCount;

                        if (catMatchCount > 0 && !firstMatchingCat) {
                            firstMatchingCat = category;
                        }
                    });

                    // Update sidebar state based on matches
                    $categoryCards.each(function() {
                        const $card = $(this);
                        const category = $card.data('category');
                        const $badge = $card.find('.cat-badge');
                        const matches = catMatches[category] || 0;

                        if (matches > 0) {
                            $card.css('display', 'flex');
                            $badge.text(matches);
                            $badge.removeClass('bg-secondary').addClass('bg-warning text-dark');
                        } else {
                            // Hide unmatched category cards
                            $card.css('display', 'none');
                        }
                    });

                    if (totalMatchesFound > 0) {
                        $searchEmptyState.hide();

                        // Auto-switch to the first matching category if the current one has no matches
                        const $activeNavCard = $('.category-nav-card.active');
                        const currentCat = $activeNavCard.length ? $activeNavCard.data('category') : null;

                        if (!currentCat || (catMatches[currentCat] || 0) === 0) {
                            switchCategory(firstMatchingCat);
                        } else {
                            // Refresh layout of active card
                            const $activeContentCard = $('.content-card.show-active');
                            if ($activeContentCard.length) {
                                $activeContentCard.removeClass('show-active');
                                void $activeContentCard[0].offsetWidth; // force redraw
                                $activeContentCard.addClass('show-active');
                            }
                        }
                    } else {
                        // Display not found screen
                        $searchEmptyState.show();
                        $contentCards.removeClass('show-active').hide();
                        $categoryCards.hide();
                    }
                }

                // 6. Interactive Feedback Widgets
                $('.feedback-widget').each(function() {
                    const $container = $(this);
                    const $btnsContainer = $container.find('.feedback-btns');
                    const $responseText = $container.find('.feedback-response');
                    const $yesBtn = $container.find('.feedback-btn.yes');
                    const $noBtn = $container.find('.feedback-btn.no');

                    $yesBtn.on('click', function() {
                        $btnsContainer.hide();
                        $responseText.text('Senang bisa membantu! Terima kasih atas masukan Anda. ❤️')
                            .show();
                    });

                    $noBtn.on('click', function() {
                        $btnsContainer.hide();
                        $responseText.text(
                                'Terima kasih atas masukannya. Masukan Anda direkam untuk membantu meningkatkan halaman panduan ini. 🙏'
                            )
                            .removeClass('text-success').addClass('text-muted')
                            .show();
                    });
                });
            });
        </script>
    @endpush
@endsection
