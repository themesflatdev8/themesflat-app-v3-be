<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Theme') }}
        </h2>
    </x-slot>

    <div class="container">
        <div>
            @if (\Session::has('success'))
            <div class="alert alert-success">
                <ul>
                    <li>{!! \Session::get('success') !!}</li>
                </ul>
            </div>
            @endif

            @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>
        <form class="row gy-2 gx-3 align-items-center" style="margin-top:20px;padding:20px">
            <div class="col-auto">
                <label class="visually-hidden" for="autoSizingInput">Name</label>
                <input type="text" class="form-control" id="autoSizingInput" name="keyword" value="{{ @$_GET['value'] }}" placeholder="name">
            </div>

            <div class="col-auto">
                <button type="submit" class="btn btn-success">Search</button>
            </div>
            <div class="mt-3">
                <a href="{{ route('add_theme')}}" class="btn btn-primary float-right">Add new</a>
            </div>
        </form>

        <div class="row gy-2 gx-3 align-items-center">
            <div class="col bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div style="overflow-x: auto;">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>selector_cart_page</th>
                                    <th>position_cart_page</th>
                                    <th>style_cart_page</th>
                                    <th>selector_cart_drawer</th>
                                    <th>position_cart_drawer</th>
                                    <th>style_cart_drawer</th>
                                    <th>selector_button_cart_drawer</th>
                                    <th>selector_wrap_cart_drawer</th>
                                    <th>Created At</th>
                                    <th>Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                @if ($themes)
                                @foreach ($themes as $themeItem)
                                <tr>
                                    <td>{{ $themeItem->id }}</td>
                                    <td>{{ $themeItem->name }}</td>
                                    <td>{{ $themeItem->selector_cart_page }}</td>
                                    <td>{{ $themeItem->position_cart_page }}</td>
                                    <td>{{ $themeItem->style_cart_page }}</td>
                                    <td>{{ $themeItem->selector_cart_drawer }}</td>
                                    <td>{{ $themeItem->position_cart_drawer }}</td>
                                    <td>{{ $themeItem->style_cart_drawer }}</td>
                                    <td>{{ $themeItem->selector_button_cart_drawer }}</td>
                                    <td>{{ $themeItem->selector_wrap_cart_drawer }}</td>
                                    <td>{{ $themeItem->created_at }}</td>
                                    <td>
                                        <a class="btn btn-warning btn-sm" href="{{ route('theme_edit', ['id' => $themeItem->id]) }}">
                                            <i class="fas fa-edit text-white"> Edit</i>
                                        </a>

                                        <form action="{{ route('theme_delete', ['id' => $themeItem->id]) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm mt-1" onclick="return confirm('Are you sure you want to delete this item?');">
                                                <i class="fas fa-trash-alt"> Delete</i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                                @else
                                <h2 class="text-danger">There are currently no themes</h2>
                                @endif
                            </tbody>
                        </table>
                        <div class="justify-content-center">
                            {!! $themes->links() !!}
                        </div>
                        <div>
                            <div>
                                Page {{ $themes->currentPage() }} of {{ $themes->lastPage() }}
                            </div>
                            <div class="mt-3" style="float:right">
                                @if ($themes->previousPageUrl())
                                <a href="{{ $themes->previousPageUrl() }}" class="btn btn-secondary">Previous</a>
                                @endif

                                @if ($themes->nextPageUrl())
                                <a href="{{ $themes->nextPageUrl() }}" class="btn btn-secondary">Next</a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>