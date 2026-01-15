@extends('layouts.admin')

@section('title', 'Products')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Products</h2>
        <a href="{{ route('admin.products.create') }}" class="btn btn-primary">Add Product</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4 ms-auto">
                    <input type="text"
                           id="search"
                           class="form-control"
                           placeholder="Search by name or SKU">
                </div>
            </div>

            <div id="products-table">
                @include('admin.products.partials.table', ['products' => $products])
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const input = document.getElementById('search');
        const container = document.getElementById('products-table');

        input.addEventListener('input', function () {
            const value = this.value;
            const url = new URL('{{ route('admin.products.index') }}', window.location.origin);
            if (value) {
                url.searchParams.set('search', value);
            } else {
                url.searchParams.delete('search');
            }

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).then(response => response.text())
                .then(html => {
                    container.innerHTML = html;
                });
        });
    </script>
@endsection
