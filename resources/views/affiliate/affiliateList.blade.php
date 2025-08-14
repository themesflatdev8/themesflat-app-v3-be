<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Affiliate') }}
        </h2>
    </x-slot>



    <div class="container">
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
        <div class="row" style="margin-top:20px;padding:20px">
            <div class="col-md-6">
                <form class="row gy-2 gx-3 align-items-center">

                    <div class="col-auto">
                        <label class="visually-hidden" for="autoSizingInput">Name</label>
                        <input type="text" class="form-control" id="autoSizingInput" name="keyword" value="{{ @$_GET['value'] }}" placeholder="domain / iframe / cookie">
                    </div>

                    <div class="col-auto">
                        <button type="submit" class="btn btn-success">Search</button>
                    </div>
                    <div class="mt-3">
                        <a href="{{ route('add_affiliate')}}" class="btn btn-primary float-right">+ Add new</a>
                    </div>
                </form>
            </div>
            <div class="col-md-6 d-flex justify-content-between">
                <div>
                    <form action="{{ route('toggle_affiliate') }}" method="POST">
                        @csrf
                        <label>Affiliate Status:
                            @if($affiliateStatus == 'on')
                            <span class="text-success">ON</span>
                            @else
                            <span class="text-danger">OFF</span>
                            @endif
                        </label>
                        @if($affiliateStatus == 'on')
                        <button type="submit" class="btn btn-danger">
                            Off Now
                        </button>
                        @else
                        <button type="submit" class="btn btn-success">
                            On Now
                        </button>
                        @endif
                    </form>
                </div>

                <div>
                    <a href="{{ route('download_sample_csv') }}" class="btn btn-secondary text-white mb-2">
                        <i class="fas fa-file-download"></i> CSV Template
                    </a>
                    <form action="{{ route('chunking') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="file" id="csv_file" name="csv_file" class="form-control d-none" accept=".csv" required>
                        <button type="button" class="btn btn-info text-white" onclick="document.getElementById('csv_file').click()">
                            <i class="fas fa-file-import"></i> Import csv file
                        </button>
                        <input type="submit" class="d-none" id="submit_button">
                    </form>
                </div>
            </div>
        </div>


        <div class="row gy-2 gx-3 align-items-center">

            <div class="col bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div style="overflow-x: auto;">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Domain</th>
                                    <th>Iframe</th>
                                    <th>Cookie name</th>
                                    <th>Timeout</th>
                                    <th>Created At</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($affiliate as $affiliateItem)
                                <tr>
                                    <td>{{ $affiliateItem->id }}</td>
                                    <td>{{ $affiliateItem->domain }}</td>
                                    <td>
                                        @php
                                        // Decode JSON if it's a string
                                        $iframeArray = is_string($affiliateItem->iframe) ? json_decode($affiliateItem->iframe, true) : $affiliateItem->iframe;
                                        @endphp
                                        @if(is_array($iframeArray))
                                        @foreach($iframeArray as $iframeItem)
                                        {{ $iframeItem }}<br>
                                        @endforeach
                                        @else
                                        {{ $affiliateItem->iframe }}
                                        @endif
                                    </td>
                                    <td>{{ $affiliateItem->cookie_name }}</td>
                                    <td>{{ $affiliateItem->timeout }}</td>
                                    <td>{{ $affiliateItem->created_at }}</td>
                                    <td>
                                        <a class="btn btn-warning btn-sm" href="{{ route('affiliate_edit', ['id' => $affiliateItem->id]) }}">
                                            <i class="fas fa-edit text-white"> Edit</i>
                                        </a>

                                        <form action="{{ route('affiliate_delete', ['id' => $affiliateItem->id]) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this item?');">
                                                <i class="fas fa-trash-alt"> Delete</i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>

                        </table>

                        <div class="justify-content-center">
                            {!! $affiliate->links() !!}
                        </div>
                        <div>
                            <div>
                                Page {{ $affiliate->currentPage() }} of {{ $affiliate->lastPage() }}
                            </div>
                            <div class="mt-3" style="float:right">
                                @if ($affiliate->previousPageUrl())
                                <a href="{{ $affiliate->previousPageUrl() }}" class="btn btn-secondary">Previous</a>
                                @endif

                                @if ($affiliate->nextPageUrl())
                                <a href="{{ $affiliate->nextPageUrl() }}" class="btn btn-secondary">Next</a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<script>
    document.getElementById('csv_file').addEventListener('change', function() {
        document.getElementById('submit_button').click();
    });
</script>