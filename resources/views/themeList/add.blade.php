<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add new theme') }}
        </h2>
    </x-slot>

    <div class="container mt-4">
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

                    <form method="POST">
                        {{ csrf_field() }}

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group row mb-2">
                                    <label for="name" class="col-sm-5 col-form-label">Theme name <span class="text-danger">*</span></label>
                                    <div class="col-sm-7">
                                        <input type="text" name="name" class="form-control">
                                    </div>
                                </div>

                                <div class="form-group row mb-2">
                                    <label for="selector_cart_page" class="col-sm-5 col-form-label">selector_cart_page <span class="text-danger">*</span></label>
                                    <div class="col-sm-7">
                                        <input type="text" name="selector_cart_page" class="form-control">
                                    </div>
                                </div>

                                <div class="form-group row mb-2">
                                    <label for="position_cart_page" class="col-sm-5 col-form-label">position_cart_page <span class="text-danger">*</span></label>
                                    <div class="col-sm-7">
                                        <input type="text" name="position_cart_page" class="form-control">
                                    </div>
                                </div>

                                <div class="form-group row mb-2">
                                    <label for="style_cart_page" class="col-sm-5 col-form-label">style_cart_page</label>
                                    <div class="col-sm-7">
                                        <input type="text" name="style_cart_page" class="form-control">
                                    </div>
                                </div>

                                <div class="form-group row mb-2">
                                    <label for="selector_cart_drawer" class="col-sm-5 col-form-label">selector_cart_drawer</label>
                                    <div class="col-sm-7">
                                        <input type="text" name="selector_cart_drawer" class="form-control">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group row mb-2">
                                    <label for="position_cart_drawer" class="col-sm-5 col-form-label">position_cart_drawer</label>
                                    <div class="col-sm-7">
                                        <input type="text" name="position_cart_drawer" class="form-control">
                                    </div>
                                </div>

                                <div class="form-group row mb-2">
                                    <label for="style_cart_drawer" class="col-sm-5 col-form-label">style_cart_drawer</label>
                                    <div class="col-sm-7">
                                        <input type="text" name="style_cart_drawer" class="form-control">
                                    </div>
                                </div>

                                <div class="form-group row mb-2">
                                    <label for="selector_button_cart_drawer" class="col-sm-5 col-form-label">selector_button_cart_drawer</label>
                                    <div class="col-sm-7">
                                        <input type="text" name="selector_button_cart_drawer" class="form-control">
                                    </div>
                                </div>

                                <div class="form-group row mb-2">
                                    <label for="selector_wrap_cart_drawer" class="col-sm-5 col-form-label">selector_wrap_cart_drawer</label>
                                    <div class="col-sm-7">
                                        <input type="text" name="selector_wrap_cart_drawer" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Submit</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
