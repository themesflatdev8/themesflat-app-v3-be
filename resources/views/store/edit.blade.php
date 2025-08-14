<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Store') }} {{ $store->shopify_domain }}
        </h2>
    </x-slot>

    <div class="container" style="margin-top: 20px">
        <div class="row gy-2 gx-3 align-items-center">
            <div class="col bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">

                    @if (\Session::has('success'))
                    <div class="alert alert-success">
                        <ul>
                            <li>{!! \Session::get('success') !!}</li>
                        </ul>
                    </div>
                    @endif

                    <style>
                        .form-label {
                            margin-right: 15px;
                        }
                    </style>


                    <form method="POST">
                        {{ csrf_field() }}
                        <div class="mb-3">

                            <table class="mt-2">
                                <tr>
                                    <td>
                                        <label for="review" class="form-label">Review</label>

                                    </td>
                                    <td>
                                        <input type="number" value="{{ !empty($store->loyalty['quest_review']) ? $store->loyalty['quest_review'] : 0 }}" class="form-control" id="review" aria-describedby="" name="review">
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <label for="loyalty" class="form-label">Loyalty</label>
                                    </td>
                                    <td>
                                        <input type="checkbox" class="form-check-input mb-2" id="loyalty" name="loyalty" value="1" {{ @$store->loyalty['loyalty'] ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <td><label for="status" class="form-label">App Status</label></td>
                                    <td>
                                        <input type="checkbox" class="form-check-input mb-2" id="status" name="status" value="1" {{ $store->app_status == 1 ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <td><label for="shopify_plan" class="form-label">Shopify Plan</label></td>
                                    <td>
                                        <input type="text" value="{{ !empty($store->shopify_plan) ? $store->shopify_plan : '' }}" class="form-control" id="shopify_plan" aria-describedby="" name="shopify_plan">
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <label for="app_plan" class="form-label">App plan</label>
                                    </td>
                                    <td>
                                        <select name="app_plan">
                                            @foreach($allPlans as $plan)
                                            <option value="{{ $plan['name'] }}" {{ $plan['name'] == $store->app_plan ? "selected" : "" }}>{{ $plan['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td><label for="test_stores">Is Test store</label></td>
                                    <td>
                                        <input type="checkbox" class="form-check-input mb-2" id="test_stores" name="test_stores" value="1" {{ $test_exists ? 'checked' : '' }}>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        {{-- <div class="mb-3">
                            <label for="exampleInputPassword1" class="form-label">Password</label>
                            <input type="password" class="form-control" id="exampleInputPassword1">
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="exampleCheck1">
                            <label class="form-check-label" for="exampleCheck1">Check me out</label>
                        </div> --}}
                        <button type="submit" class="btn btn-primary">Save</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>