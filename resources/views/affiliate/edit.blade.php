<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit affiliate') }}
        </h2>
    </x-slot>


    <div class="container mt-4">
        <div class="card">
            <div class="card-body">

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

                @if ($affiliateItem)
                <form method="POST">
                    {{ csrf_field() }}
                    <div class="mb-3">
                        <label for="domain" class="form-label">Domain <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="domain" placeholder="Enter domain" value="{{ $affiliateItem->domain }}">
                    </div>

                    <div class="mb-3">
                        <label for="cookie_name" class="form-label">Cookie name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="cookie_name" placeholder="Enter cookie name" value="{{ $affiliateItem->cookie_name }}">
                    </div>

                    <div class="mb-3">
                        <label for="timeout" class="form-label">Timeout</label>
                        <input type="number" min="0" class="form-control" name="timeout" placeholder="Enter timeout" value="{{ $affiliateItem->timeout }}">
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <label for="iframe">Iframe</label>
                            <button type="button" id="add-iframe" class="btn btn-primary btn-sm">+ Add iframe</button>
                        </div>
                        <div class="iframe-wrapper" id="iframe-wrapper">
                            @if (!empty($iframeArray) && is_array($iframeArray))
                            @foreach ($iframeArray as $iframe)
                            <div class="iframe-group">
                                <input type="text" class="form-control" name="iframe[]" value="{{ $iframe }}" required placeholder="https://example.com">
                                <button type="button" class="btn btn-danger remove-iframe">X</button>
                            </div>
                            @endforeach
                            @else
                            <div class="iframe-group">
                                <input type="text" class="form-control" name="iframe[]" value="" required placeholder="https://example.com">
                                <button type="button" class="btn btn-danger remove-iframe">X</button>
                            </div>
                            @endif
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success">Save</button>
                </form>
                @else
                <h2 class="text-danger">Not found affiliate item</h2>
                @endif
            </div>
        </div>
    </div>

    <style>
        .iframe-wrapper {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .iframe-group {
            display: flex;
            align-items: center;
            flex: 1 1 calc(50% - 10px);
            background-color: #ffffff;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .iframe-group input {
            flex: 1;
            margin-right: 10px;
        }

        .remove-iframe {
            background-color: #ff4d4d;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
        }

        .remove-iframe:hover {
            background-color: #e60000;
        }

        #add-iframe {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            display: block;
            margin: 20px 0;
        }

        #add-iframe:hover {
            background-color: #0056b3;
        }
    </style>

    <script>
        document.getElementById('add-iframe').addEventListener('click', function() {
            var wrapper = document.getElementById('iframe-wrapper');
            var newIframe = document.createElement('div');
            newIframe.className = 'iframe-group';
            newIframe.innerHTML = '<input type="text" class="form-control" name="iframe[]" value="" required placeholder="https://example.com"/><button type="button" class="btn btn-danger remove-iframe">X</button>';
            wrapper.appendChild(newIframe);
        });

        document.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('remove-iframe')) {
                e.target.parentNode.remove();
            }
        });
    </script>
</x-app-layout>