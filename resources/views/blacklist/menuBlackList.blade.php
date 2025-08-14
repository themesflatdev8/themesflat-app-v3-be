<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Black list') }}
        </h2>
    </x-slot>

    <div class="container">
        <form class="row gy-2 gx-3 align-items-center" style="margin-top:20px;padding:20px">
            <div class="col-auto">
                <label class="visually-hidden" for="autoSizingInput">Name</label>
                <input type="text" class="form-control" id="autoSizingInput" name="keyword" value="{{ @$_GET['value'] }}" placeholder="value">
            </div>

            <div class="col-auto">
                <button type="submit" class="btn btn-success">Search</button>
            </div>
            <div class="mt-3">
                <a href="{{ route('add_blackList')}}" class="btn btn-primary float-right">Add new</a>
            </div>
            <div>
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
        </form>

        <div class="row gy-2 gx-3 align-items-center">
            <div class="col bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div style="overflow-x: auto;">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Category name</th>
                                    <th>Type</th>
                                    <th>Value</th>
                                    <th>Created At</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($blackList as $blackListItem)
                                <tr>
                                    <td>{{ $blackListItem->id }}</td>
                                    <td>{{ $blackListItem->category }}</td>
                                    <td>{{ $blackListItem->type }}</td>
                                    <td>{{ $blackListItem->value }}</td>
                                    <td>{{ $blackListItem->created_at }}</td>
                                    <td>
                                        <a class="btn btn-warning btn-sm" href="{{ route('blackList_edit', ['id' => $blackListItem->id]) }}">
                                            <i class="fas fa-edit text-white"> Edit</i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="justify-content-center">
                            {!! $blackList->links() !!}
                        </div>
                        <div>
                            <div>
                                Page {{ $blackList->currentPage() }} of {{ $blackList->lastPage() }}
                            </div>
                            <div class="mt-3" style="float:right">
                                @if ($blackList->previousPageUrl())
                                <a href="{{ $blackList->previousPageUrl() }}" class="btn btn-secondary">Previous</a>
                                @endif

                                @if ($blackList->nextPageUrl())
                                <a href="{{ $blackList->nextPageUrl() }}" class="btn btn-secondary">Next</a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
