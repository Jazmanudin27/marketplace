@extends('layouts.app')
@section('title', 'Kelola Panduan & FAQ')
@section('page-title', 'Kelola Panduan & FAQ')



@section('content')
    <div class="manage-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="{{ route('faq.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Kembali ke Pusat Bantuan
            </a>
            <div>
                <button class="btn btn-primary btn-sm me-2" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                    <i class="fas fa-folder-plus me-1"></i> Tambah Kategori
                </button>
                <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addItemModal">
                    <i class="fas fa-plus me-1"></i> Tambah Item (Workflow / FAQ)
                </button>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle me-2 fs-5"></i>
                    <span>{{ session('success') }}</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle me-2 fs-5"></i>
                    <span>Gagal menyimpan. Silakan cek kembali input Anda.</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row g-4">
            <!-- Sidebar Kategori -->
            <div class="col-lg-4">
                <div class="card border shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title fw-bold text-dark mb-3"><i class="fas fa-folder text-primary me-2"></i>Daftar Kategori</h5>

                        @if ($categories->isEmpty())
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-folder-open mb-2" style="font-size: 2rem;"></i>
                                <p>Belum ada kategori.</p>
                            </div>
                        @else
                            <div class="category-list-wrapper">
                                @foreach ($categories as $index => $category)
                                    <div class="category-item-card card border mb-2 p-3 {{ $index === 0 ? 'active bg-light border-primary' : 'bg-white' }}"
                                        data-category-slug="{{ $category->slug }}" data-category-id="{{ $category->id }}"
                                        style="cursor: pointer; border-left: 4px solid {{ $category->color }} !important;">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="cat-icon-wrap fs-4" style="color: {{ $category->color }};">
                                                    <i class="{{ $category->icon }}"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 fw-bold text-dark">{{ $category->name }}</h6>
                                                    <small class="text-muted">{{ $category->subtitle ?? 'Tidak ada deskripsi' }}</small>
                                                </div>
                                            </div>
                                            <div class="d-flex gap-1">
                                                <button class="btn btn-outline-info btn-xs p-1 edit-category-btn"
                                                    data-id="{{ $category->id }}" data-name="{{ $category->name }}"
                                                    data-subtitle="{{ $category->subtitle }}"
                                                    data-icon="{{ $category->icon }}" data-color="{{ $category->color }}"
                                                    data-color-rgb="{{ $category->color_rgb }}"
                                                    data-read-time="{{ $category->read_time }}"
                                                    data-workflow-title="{{ $category->workflow_title }}"
                                                    data-sort-order="{{ $category->sort_order }}" data-bs-toggle="modal"
                                                    data-bs-target="#editCategoryModal">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form action="{{ route('faq.categories.destroy', $category) }}" method="POST"
                                                    onsubmit="return confirm('Hapus kategori ini beserta seluruh isinya?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger btn-xs p-1">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- List Item (Workflow & FAQ) -->
            <div class="col-lg-8">
                <div class="card border shadow-sm h-100">
                    <div class="card-body">
                        @foreach ($categories as $index => $category)
                            <div class="item-tab-content"
                                id="items-for-{{ $category->slug }}"
                                style="display: {{ $index === 0 ? 'block' : 'none' }};">
                                <h5 class="fw-bold text-dark mb-4 border-bottom pb-2">
                                    <i class="fas fa-list-ul text-primary me-2"></i>Panduan untuk: {{ $category->name }}
                                </h5>

                                <!-- Section Workflow -->
                                <div class="mb-4">
                                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                                        <i class="fas fa-project-diagram me-1 text-primary"></i> Workflow
                                        ({{ $category->workflow_title }})
                                    </h6>
                                    @if ($category->workflows->isEmpty())
                                        <p class="text-muted small">Belum ada langkah workflow untuk kategori ini.</p>
                                    @else
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped align-middle mb-0 small">
                                                <thead class="table-light text-dark">
                                                    <tr>
                                                        <th width="80" class="text-center">Langkah</th>
                                                        <th>Judul</th>
                                                        <th>Deskripsi</th>
                                                        <th width="100" class="text-end">Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($category->workflows as $step)
                                                        <tr>
                                                            <td class="text-center font-monospace fw-bold text-info">
                                                                {{ $step->sort_order }}</td>
                                                            <td class="text-dark fw-bold">{{ $step->title }}</td>
                                                            <td class="text-muted">
                                                                {{ Str::limit(strip_tags($step->content), 80) }}</td>
                                                            <td class="text-end">
                                                                <div class="d-inline-flex gap-1">
                                                                    <button class="btn btn-outline-warning btn-sm edit-item-btn"
                                                                        data-id="{{ $step->id }}"
                                                                        data-category-id="{{ $category->id }}"
                                                                        data-type="workflow" data-title="{{ $step->title }}"
                                                                        data-content="{{ $step->content }}"
                                                                        data-sort-order="{{ $step->sort_order }}"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#editItemModal">
                                                                        <i class="fas fa-edit"></i>
                                                                    </button>
                                                                    <form action="{{ route('faq.items.destroy', $step) }}"
                                                                        method="POST"
                                                                        onsubmit="return confirm('Hapus item ini?')">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button type="submit"
                                                                            class="btn btn-outline-danger btn-sm">
                                                                            <i class="fas fa-trash"></i>
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </div>

                                <!-- Section FAQ -->
                                <div>
                                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                                        <i class="fas fa-question-circle me-1 text-warning"></i> Tanya Jawab (FAQ)
                                    </h6>
                                    @if ($category->faqs->isEmpty())
                                        <p class="text-muted small">Belum ada FAQ untuk kategori ini.</p>
                                    @else
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped align-middle mb-0 small">
                                                <thead class="table-light text-dark">
                                                    <tr>
                                                        <th width="60" class="text-center">No</th>
                                                        <th>Pertanyaan</th>
                                                        <th>Jawaban</th>
                                                        <th width="100" class="text-end">Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($category->faqs as $indexFaq => $faq)
                                                        <tr>
                                                            <td class="text-center text-muted">{{ $indexFaq + 1 }}</td>
                                                            <td class="text-dark fw-bold">{{ $faq->title }}</td>
                                                            <td class="text-muted">
                                                                {{ Str::limit(strip_tags($faq->content), 80) }}</td>
                                                            <td class="text-end">
                                                                <div class="d-inline-flex gap-1">
                                                                    <button
                                                                        class="btn btn-outline-warning btn-sm edit-item-btn"
                                                                        data-id="{{ $faq->id }}"
                                                                        data-category-id="{{ $category->id }}"
                                                                        data-type="faq" data-title="{{ $faq->title }}"
                                                                        data-content="{{ $faq->content }}"
                                                                        data-sort-order="{{ $faq->sort_order }}"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#editItemModal">
                                                                        <i class="fas fa-edit"></i>
                                                                    </button>
                                                                    <form action="{{ route('faq.items.destroy', $faq) }}"
                                                                        method="POST"
                                                                        onsubmit="return confirm('Hapus item ini?')">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button type="submit"
                                                                            class="btn btn-outline-danger btn-sm">
                                                                            <i class="fas fa-trash"></i>
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Tambah Kategori -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-folder-plus me-1 text-primary"></i> Tambah Kategori Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('faq.categories.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Kategori</label>
                            <input type="text" name="name" class="form-control" required
                                placeholder="Contoh: Integrasi Toko">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Subtitle Kategori</label>
                            <input type="text" name="subtitle" class="form-control"
                                placeholder="Contoh: Shopee & TikTok Shop">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">FontAwesome Icon</label>
                            <input type="text" name="icon" class="form-control" required
                                placeholder="Contoh: fas fa-plug">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Hex Color</label>
                                <input type="text" name="color" class="form-control" required
                                    placeholder="Contoh: #6C63FF">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Color RGB (comma separated)</label>
                                <input type="text" name="color_rgb" class="form-control" required
                                    placeholder="Contoh: 108, 99, 255">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Estimasi Baca (mnt)</label>
                                <input type="text" name="read_time" class="form-control" placeholder="Contoh: 3 mnt">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Judul Alur Kerja</label>
                                <input type="text" name="workflow_title" class="form-control" required
                                    value="Alur Kerja">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Urutan Tampil</label>
                            <input type="number" name="sort_order" class="form-control" required value="0">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Edit Kategori -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-1 text-primary"></i> Edit Kategori</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editCategoryForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Kategori</label>
                            <input type="text" id="edit_cat_name" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Subtitle Kategori</label>
                            <input type="text" id="edit_cat_subtitle" name="subtitle" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">FontAwesome Icon</label>
                            <input type="text" id="edit_cat_icon" name="icon" class="form-control" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Hex Color</label>
                                <input type="text" id="edit_cat_color" name="color" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Color RGB (comma separated)</label>
                                <input type="text" id="edit_cat_color_rgb" name="color_rgb" class="form-control"
                                    required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Estimasi Baca (mnt)</label>
                                <input type="text" id="edit_cat_read_time" name="read_time" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Judul Alur Kerja</label>
                                <input type="text" id="edit_cat_workflow_title" name="workflow_title"
                                    class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Urutan Tampil</label>
                            <input type="number" id="edit_cat_sort_order" name="sort_order" class="form-control"
                                required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Tambah Item -->
    <div class="modal fade" id="addItemModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus me-1 text-success"></i> Tambah Item Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('faq.items.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kategori</label>
                                <select name="faq_category_id" class="form-select" required>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tipe Item</label>
                                <select name="type" class="form-select" required>
                                    <option value="workflow">Langkah Alur Kerja (Workflow)</option>
                                    <option value="faq">Tanya Jawab (FAQ)</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Judul / Pertanyaan</label>
                            <input type="text" name="title" class="form-control" required
                                placeholder="Contoh: Langkah 1 atau Bagaimana cara...">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Konten / Jawaban (Mendukung HTML dasar seperti &lt;strong&gt;,
                                &lt;b&gt;, &lt;br&gt;)</label>
                            <textarea name="content" class="form-control" rows="5" required placeholder="Isi penjelasan detail..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nomor Urutan Tampil (Penting untuk Alur Kerja)</label>
                            <input type="number" name="sort_order" class="form-control" required value="1">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Edit Item -->
    <div class="modal fade" id="editItemModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-1 text-warning"></i> Edit Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editItemForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kategori</label>
                                <select id="edit_item_category_id" name="faq_category_id" class="form-select" required>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tipe Item</label>
                                <select id="edit_item_type" name="type" class="form-select" required>
                                    <option value="workflow">Langkah Alur Kerja (Workflow)</option>
                                    <option value="faq">Tanya Jawab (FAQ)</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Judul / Pertanyaan</label>
                            <input type="text" id="edit_item_title" name="title" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Konten / Jawaban (Mendukung HTML dasar seperti &lt;strong&gt;,
                                &lt;b&gt;, &lt;br&gt;)</label>
                            <textarea id="edit_item_content" name="content" class="form-control" rows="5" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nomor Urutan Tampil</label>
                            <input type="number" id="edit_item_sort_order" name="sort_order" class="form-control"
                                required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            const $catCards = $('.category-item-card');
            const $itemTabs = $('.item-tab-content');

            // 1. Sidebar Category Click Handler (Tabs)
            $catCards.on('click', function(e) {
                // If clicked edit or delete button/form, don't trigger tab switch
                if ($(e.target).closest('.edit-category-btn, form').length) {
                    return;
                }

                $catCards.each(function() {
                    $(this).removeClass('active bg-light border-primary').addClass('bg-white');
                });
                $(this).addClass('active bg-light border-primary').removeClass('bg-white');

                const slug = $(this).data('category-slug');
                $itemTabs.hide();
                $('#items-for-' + slug).show();
            });

            // 2. Populate Modal Form for Category Edit
            $('.edit-category-btn').on('click', function() {
                const $btn = $(this);
                const id = $btn.data('id');
                const name = $btn.data('name');
                const subtitle = $btn.data('subtitle');
                const icon = $btn.data('icon');
                const color = $btn.data('color');
                const colorRgb = $btn.data('color-rgb');
                const readTime = $btn.data('read-time');
                const workflowTitle = $btn.data('workflow-title');
                const sortOrder = $btn.data('sort-order');

                // Set Form Action
                $('#editCategoryForm').attr('action', `/faq/categories/${id}`);

                // Set inputs
                $('#edit_cat_name').val(name);
                $('#edit_cat_subtitle').val(subtitle);
                $('#edit_cat_icon').val(icon);
                $('#edit_cat_color').val(color);
                $('#edit_cat_color_rgb').val(colorRgb);
                $('#edit_cat_read_time').val(readTime);
                $('#edit_cat_workflow_title').val(workflowTitle);
                $('#edit_cat_sort_order').val(sortOrder);
            });

            // 3. Populate Modal Form for Item Edit
            $('.edit-item-btn').on('click', function() {
                const $btn = $(this);
                const id = $btn.data('id');
                const categoryId = $btn.data('category-id');
                const type = $btn.data('type');
                const title = $btn.data('title');
                const content = $btn.data('content');
                const sortOrder = $btn.data('sort-order');

                // Set Form Action
                $('#editItemForm').attr('action', `/faq/items/${id}`);

                // Set inputs
                $('#edit_item_category_id').val(categoryId);
                $('#edit_item_type').val(type);
                $('#edit_item_title').val(title);
                $('#edit_item_content').val(content);
                $('#edit_item_sort_order').val(sortOrder);
            });
        });
    </script>
@endpush
