@extends('layouts.master')

@section('title')
    {{__('Roles')}}
@endsection

@section('main_content')
    <div class="erp-table-section">
        <div class="container-fluid">
            <div class="cards">
                <div class="card-body">
            <div class="table-header">
                <h4>{{__('Add New Role')}}</h4>
                <small class="text-warning">{{ __('Set role permissions') }}</small>
            </div>
            <div class="row justify-content-center mt-2 roles-permissions p-16">
                <div class="col-md-12 order-form-section">
                    <form action="{{ route('admin.roles.update', $role->id) }}" method="post" class="row ajaxform_instant_reload">
                        @csrf
                        @method('PUT')

                        <div class="col-12 form-group">
                            <label for="name" class="required">{{ __("Role Name") }}</label>
                            <input type="text" name="name" id="name" class="form-control" value="{{ $role->name }}" placeholder="{{ __("Enter role name") }}" required>
                        </div>

                        <div class="col-12">
                            <h4 class="mt-4 pt-50">{{ __("Role Permissions") }}</h4>

                            <div class="table-responsive">
                                <table class="table table-hover table-bordered">
                                    <tbody>
                                    <tr>
                                        <td class="text-nowrap fw-bolder">
                                            {{ __("Setup role permissions") }}
                                        </td>
                                        <td>
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="selectAll">
                                                <label class="custom-control-label fw-bold" for="selectAll">{{ __('Select All') }}</label>
                                            </div>
                                        </td>
                                    </tr>
                                    @foreach($groups as $key => $group)
                                        <tr>
                                            <td class="text-nowrap fw-bolder">{{ $key }}</td>
                                            <td>
                                                <div class="d-flex">
                                                    @foreach($group as $permission)
                                                    <div class="custom-control custom-checkbox mr-3 me-lg-5">
                                                        <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" class="custom-control-input" id="id_{{ $permission->id }}" @checked($role->hasPermissionTo($permission->name))>
                                                        <label class="custom-control-label" for="id_{{ $permission->id }}">
                                                            {{ ucfirst(str($permission->name)->explode('-')->last() == 'list' ? 'All '.str($permission->name)->explode('-')->last() : str($permission->name)->explode('-')->last()) }}
                                                        </label>
                                                    </div>
                                                    @endforeach
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="col-12 text-center mt-2">
                                <div class="offcanvas-footer mt-3">
                                    <button type="reset" class="reset-btn" data-bs-dismiss="offcanvas"
                                        aria-label="Reset"> <i class="fas fa-undo-alt"></i> {{ __('Reset') }}
                                    </button>
                                    <button class="submit-btn" type="submit"> <i class="fas fa-save"></i> {{ __('Save') }}</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
           </div>
         </div>
        </div>
    </div>
@endsection

