<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add new blacklist') }}
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
                                <tr class="mb-2">
                                    <td class="pb-2">
                                        <label for="category" class="form-label">Category name <span class="text-danger">*</span></label>
                                    </td>
                                    <td class="pb-2">
                                        <select name="category">
                                            <option value="competitor">Competitor</option>
                                            <option value="shopify">Shopify</option>
                                        </select>
                                    </td>
                                </tr>

                                <tr>
                                    <td class="pb-2">
                                        <label for="type">Type <span class="text-danger">*</span></label>
                                    </td>
                                    <td class="pb-2">
                                        <select name="type">
                                            <option value="email_domain">Email domain</option>
                                            <option value="email">Email</option>
                                            <option value="shopify_plan">Shopify plan</option>
                                            <option value="shopify_domain">Shopify main</option>
                                            <option value="keyword_name">Keyword name</option>
                                        </select>
                                    </td>
                                </tr>

                                <tr>
                                    <td class="pb-2">
                                        <label for="value">Value <span class="text-danger">*</span></label>
                                    </td>
                                    <td>
                                        <input type="text" name="value">
                                    </td>
                                </tr>


                            </table>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
