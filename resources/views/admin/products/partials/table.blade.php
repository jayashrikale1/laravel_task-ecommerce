<div class="table-responsive">
    <table class="table table-striped table-bordered align-middle mb-3">
        <thead class="table-light">
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>SKU</th>
            <th class="text-end">Price</th>
            <th class="text-end">Stock</th>
            <th>Status</th>
            <th class="text-center">Actions</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($products as $product)
            <tr>
                <td>{{ $product->id }}</td>
                <td>{{ $product->name }}</td>
                <td>{{ $product->sku }}</td>
                <td class="text-end">{{ number_format($product->price, 2) }}</td>
                <td class="text-end">{{ $product->stock }}</td>
                <td>
                    <span class="badge {{ $product->is_active ? 'bg-success' : 'bg-secondary' }}">
                        {{ $product->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td class="text-center">
                    <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-sm btn-outline-primary me-1">Edit</a>
                    <form action="{{ route('admin.products.destroy', $product) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                onclick="return confirm('Are you sure you want to delete this product?')">
                            Delete
                        </button>
                    </form>
                    <form action="{{ route('admin.products.toggle', $product) }}" method="POST" class="d-inline">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                            {{ $product->is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center">No products found.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

{{ $products->links('pagination::bootstrap-5') }}
