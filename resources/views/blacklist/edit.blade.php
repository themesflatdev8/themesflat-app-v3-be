<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit blacklist') }}
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

                    @if ($blackListItem)
                    <form method="POST">
                        {{ csrf_field() }}
                        <div class="mb-3">
                            <table class="mt-2">
                                <tr class="mb-2">
                                    <td class="pb-2">
                                        <label for="category" class="form-label">Category name <span class="text-danger">*</span></label>
                                    </td>
                                    <td class="pb-2">
                                        <select name="category">
                                            <option value="competitor" {{ $blackListItem->category == 'competitor' ? "selected" : "" }}>Competitor</option>
                                            <option value="shopify" {{ $blackListItem->category == 'shopify' ? "selected" : "" }}>Shopify</option>
                                        </select>
                                    </td>
                                </tr>

                                <tr>
                                    <td class="pb-2">
                                        <label for="type">Type <span class="text-danger">*</span></label>
                                    </td>
                                    <td class="pb-2">
                                        <select name="type">
                                            <option value="email_domain" {{ $blackListItem->type == 'email_domain' ? "selected" : "" }}>Email domain</option>
                                            <option value="email" {{ $blackListItem->type == 'email' ? "selected" : "" }}>Email</option>
                                            <option value="shopify_plan" {{ $blackListItem->type == 'shopify_plan' ? "selected" : "" }}>Shopify plan</option>
                                            <option value="shopify_domain" {{ $blackListItem->type == 'shopify_domain' ? "selected" : "" }}>Shopify main</option>
                                            <option value="keyword_name" {{ $blackListItem->type == 'keyword_name' ? "selected" : "" }}>Keyword name</option>
                                        </select>
                                    </td>
                                </tr>

                                <tr>
                                    <td class="pb-2">
                                        <label for="value">Value <span class="text-danger">*</span></label>
                                    </td>
                                    <td>
                                        <input type="text" name="value" value="{{ $blackListItem->value }}">
                                    </td>
                                </tr>

                            </table>
                        </div>

                        <button type="submit" class="btn btn-primary">Submit</button>
                    </form>
                    @else
                    <h2 class="text-danger">Not found blacklist item</h2>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
