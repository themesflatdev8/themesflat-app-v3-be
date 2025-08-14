<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
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
        @if (\Session::has('error'))
        <div class="alert alert-danger">
            <ul>
                <li>{!! \Session::get('error') !!}</li>
            </ul>
        </div>
        @endif

        <form class="row gy-2 gx-3 align-items-center " style="margin-top:20px;margin-bottom:20px;padding:20px">
            <div class="col-auto">
                <label class="visually-hidden" for="autoSizingInput">Name</label>
                <input type="text" class="form-control" id="autoSizingInput" name="keyword" value="{{ @$_GET['keyword'] }}" placeholder="domain / name / id / email">
            </div>
            <!-- <div class="col-auto">
                    <label class="visually-hidden" for="autoSizingInputGroup">Username</label>
                    <div class="input-group">
                        <div class="input-group-text">@</div>
                        <input type="text" class="form-control" name="" id="autoSizingInputGroup" placeholder="Username">
                    </div>
                </div> -->
            {{-- <div class="col-auto">
                    <label class="visually-hidden" for="autoSizingSelect">Preference</label>
                    <select class="form-select" name="app_plan" id="autoSizingSelect">
                        <option selected> Select app plan </option>
                        <option value="free">free</option>
                        <option value="premium">premium</option>
                    </select>
                </div>
                <div class="col-auto">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="autoSizingCheck" name="no_test" value="1">
                        <label class="form-check-label" for="autoSizingCheck">
                            No test
                        </label>
                    </div>
                </div> --}}
            <div class="col-auto">
                <button type="submit" class="btn btn-success">Search</button>
            </div>
        </form>

        <div class="row gy-2 gx-3 align-items-center">
            <div class="col bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div style="overflow-x: auto;">
                        <div class="" style="margin-bottom: 10px;">
                            <form action="{{ url('/dashboard/') }}" method="GET">
                                <select name="app_plan" id="app_plan" class="form-select-sm">
                                    <option value="">Select app plan</option>
                                    <option value="free">Free</option>
                                    <option value="essential">Essential</option>
                                </select>
                                <select name="app_status" id="app_status" class="form-select-sm">
                                    <option value="">Select app status</option>
                                    <option value="1">Active</option>
                                    <option value="0">Uninstalled</option>
                                </select>
                                <select name="shopify_plan" id="shopify_plan" class="form-select-sm">
                                    <option value="">Select Shopify plan</option>
                                    @foreach ($shopifyPlans as $plan)
                                    <option value="{{ $plan }}">{{ $plan }}</option>
                                    @endforeach
                                </select>
                                <select name="loyalty_status" id="loyalty_status" class="form-select-sm">
                                    <option value="">Select loyalty status</option>
                                    <option value="yes">Yes</option>
                                    <option value="no">No</option>
                                </select>
                                <select name="quest_review" id="quest_review" class="form-select-sm">
                                    <option value="">Select review</option>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                    <option value="5">5</option>
                                </select>
                                <select name="quest_bundle" id="quest_bundle" class="form-select-sm">
                                    <option value="">Select bundle</option>
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                                <select name="quest_ext" id="quest_ext" class="form-select-sm">
                                    <option value="">Select ext</option>
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>

                                <button class="btn btn-info" type="submit"><i class="fas fa-filter text-white"></i> Filter</button>
                            </form>
                        </div>

                        <table class="table table-bordered text-center">

                            <thead>
                                <th>ID
                                    <a href="{{ route('dashboard', ['sort' => 'store_id', 'order' => request('sort') == 'store_id' && request('order') == 'desc' ? 'asc' : 'desc']) }}">
                                        <i class="fas fa-sort{{ (request('sort') == 'store_id' 
                                        && request('order') == 'asc') ? '-down' : ((request('sort') == 'store_id'
                                        && request('order') == 'desc') ? '-up' : '') }}">
                                        </i>
                                    </a>
                                </th>
                                <th>Shopify Domain
                                    <a href="{{ route('dashboard', ['sort' => 'shopify_domain', 'order' => request('sort') == 'shopify_domain' && request('order') == 'desc' ? 'asc' : 'desc']) }}">
                                        <i class="fas fa-sort{{ (request('sort') == 'shopify_domain' 
                                        && request('order') == 'asc') ? '-down' : ((request('sort') == 'shopify_domain' 
                                        && request('order') == 'desc') ? '-up' : '') }}">
                                        </i>
                                    </a>
                                </th>
                                <th>Store Name
                                    <a href="{{ route('dashboard', ['sort' => 'name', 'order' => request('sort') == 'name' && request('order') == 'desc' ? 'asc' : 'desc']) }}">
                                        <i class="fas fa-sort{{ (request('sort') == 'name' 
                                        && request('order') == 'asc') ? '-down' : ((request('sort') == 'name' 
                                        && request('order') == 'desc') ? '-up' : '') }}">
                                        </i>
                                    </a>
                                </th>
                                <th>Email
                                    <a href="{{ route('dashboard', ['sort' => 'email', 'order' => request('order') == 'asc' ? 'desc' : 'asc']) }}">
                                        <i class="fas fa-sort{{ (request('sort') == 'email' 
                                        && request('order') == 'asc') ? '-down' : ((request('sort') == 'email' 
                                        && request('order') == 'desc') ? '-up' : '') }}">
                                        </i>
                                    </a>
                                </th>
                                <th>Shopify Plan
                                    <a href="{{ route('dashboard', ['sort' => 'shopify_plan', 'order' => request('order') == 'asc' ? 'desc' : 'asc']) }}">
                                        <i class="fas fa-sort{{ (request('sort') == 'shopify_plan' 
                                        && request('order') == 'asc') ? '-down' : ((request('sort') == 'shopify_plan' 
                                        && request('order') == 'desc') ? '-up' : '') }}">
                                        </i>
                                    </a>
                                </th>
                                <th>App Plan
                                    <a href="{{ route('dashboard') }}"></a>
                                </th>
                                <th>App Status</th>
                                <th>Loyalty</th>
                                <th>Review</th>
                                <th>Embed App</th>
                                <th>App Block</th>
                                <th>Owner</th>
                                <th>Test</th>
                                <th>Blacklist</th>
                                <th>Order In Month</th>
                                <th>Cancelled On
                                    <a href="{{ route('dashboard', ['sort' => 'cancelled_on', 'order' => request('order') == 'asc' ? 'desc' : 'asc']) }}">
                                        <i class="fas fa-sort{{ (request('sort') == 'cancelled_on' 
                                        && request('order') == 'asc') ? '-down' : ((request('sort') == 'cancelled_on' 
                                        && request('order') == 'desc') ? '-up' : '') }}">
                                        </i>
                                    </a>
                                </th>
                                <th>Created At
                                    <a href="{{ route('dashboard', ['sort' => 'created_at', 'order' => request('order') == 'asc' ? 'desc' : 'asc']) }}">
                                        <i class="fas fa-sort{{ (request('sort') == 'created_at' 
                                        && request('order') == 'asc') ? '-down' : ((request('sort') == 'created_at' 
                                        && request('order') == 'desc') ? '-up' : '') }}">
                                        </i>
                                    </a>
                                </th>
                                <th>Action</th>
                            </thead>

                            <tbody>
                                @foreach ($stores as $user)
                                <tr>
                                    <td>{{ $user->store_id }} </td>
                                    <td>{{ $user->shopify_domain }} </td>
                                    <td>{{ $user->name }} </td>
                                    <td>{{ $user->email }} </td>

                                    <td>{{ $user->shopify_plan }} </td>

                                    <td>{{ $user->app_plan }} </td>
                                    <td>
                                        @if($user->app_status == 1)
                                        <b class="text-success">Active</b>
                                        @else
                                        <b class="text-danger">Uninstall</b>
                                        @endif
                                    </td>
                                    <td>{{ isset($user->loyalty['loyalty']) && $user->loyalty['loyalty'] ? 'Yes' : 'No' }}</td>
                                    <td>{{ $user->loyalty['quest_review'] ?? 'N/A' }}</td>
                                    <td id="theme-ext-{{ $user->store_id }}">Loading...</td>
                                    <td id="app-block-{{ $user->store_id }}">Loading...</td>
                                    <td>{{ $user->owner }} </td>
                                    <td>{{ $user->test ? 'Yes' : 'No' }}</td>
                                    <td>{{ $user->blackList ? 'Yes' : 'No' }}</td>
                                    <td id="orders-count-{{ $user->store_id }}">Loading...</td>
                                    <!-- <td class="{{ $user->orders_count > 0 ? 'text-info' : 'text-danger' }}">{{ $user->orders_count ?? 'N/A' }}</td> -->
                                    <td>{{ $user->cancelled_on }}</td>
                                    <td>{{ $user->created_at }} </td>

                                    <td class="d-grid gap-2">
                                        <a class="btn btn-warning btn-sm w-100" href="{{ route('store_edit', ['id' => $user->store_id]) }}">
                                            <i class="fas fa-edit text-white"> Edit</i>
                                        </a>
                                        <a class="btn btn-danger btn-sm w-100" href="{{ route('mail', ['id' => $user->store_id]) }}">
                                            <i class="fas fa-envelope"> Mail</i>
                                        </a>

                                        <form id="sync-form-{{ $user->store_id }}" action="{{ route('sync-products', ['id' => $user->store_id]) }}" method="POST" class="d-grid gap-2" style="display: inline;">
                                            {{ csrf_field() }}
                                            <button class="btn btn-success btn-sm w-100 sync-product-btn" type="submit" data-store-id="{{ $user->store_id }}">Sync product</button>
                                        </form>

                                        <form id="sync-collections-{{ $user->store_id }}" action="{{ route('sync-collections', ['id' => $user->store_id]) }}" method="POST" class="d-grid gap-2" style="display: inline;">
                                            {{ csrf_field() }}
                                            <button class="btn btn-dark btn-sm w-100 sync-collection-btn" type="submit" data-store-id="{{ $user->store_id }}">Sync Collections</button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach

                            </tbody>
                        </table>
                        <div class="justify-content-center">
                            {!! $stores->links() !!}
                        </div>
                        <div>
                            <div>
                                Page {{ $stores->currentPage() }} of {{ $stores->lastPage() }}
                            </div>
                            <div class="mt-3" style="float:right">
                                @if ($stores->previousPageUrl())
                                <a href="{{ $stores->previousPageUrl() }}" class="btn btn-secondary">Previous</a>
                                @endif

                                @if ($stores->nextPageUrl())
                                <a href="{{ $stores->nextPageUrl() }}" class="btn btn-secondary">Next</a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const storeIds = @json($stores -> pluck('store_id'));
            storeIds.forEach(storeId => {
                fetch(`dashboard/store-info?store_id=${storeId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            document.getElementById(`theme-ext-${storeId}`).innerText = 'Error';
                            document.getElementById(`app-block-${storeId}`).innerText = 'Error';
                            document.getElementById(`orders-count-${storeId}`).innerText = 'Error';
                        } else {
                            document.getElementById(`theme-ext-${storeId}`).innerText = data.theme_ext ? 'Yes' : 'No';
                            document.getElementById(`app-block-${storeId}`).innerText = data.app_block ? 'Yes' : 'No';

                            const ordersCountElement = document.getElementById(`orders-count-${storeId}`);
                            ordersCountElement.innerText = data.orders_count ? data.orders_count : '0';

                            if (data.orders_count > 0) {
                                ordersCountElement.classList.add('text-primary');
                                ordersCountElement.classList.remove('text-danger');
                            } else {
                                ordersCountElement.classList.add('text-danger');
                                ordersCountElement.classList.remove('text-primary');
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching store info:', error);
                        document.getElementById(`theme-ext-${storeId}`).innerText = 'Error';
                        document.getElementById(`app-block-${storeId}`).innerText = 'Error';
                        document.getElementById(`orders-count-${storeId}`).innerText = 'Error';
                    });
            });
        });
    </script>
</x-app-layout>