<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    <h4 class="modal-title">@lang('modules.roles.addRole')</h4>
</div>
<div class="modal-body">
    <div class="portlet-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Role</th>
                    <th>@lang('app.action')</th>
                </tr>
                </thead>
                <tbody>
                @forelse($roles as $key=>$role)
                    <tr id="role-{{ $role->id }}">
                        <td>{{ $key+1 }}</td>
                        <td>{{ ucwords($role->name) }}</td>
                          <td>
                              @if($role->id > 3)
                                  <a href="javascript:;" data-role-id="{{ $role->id }}" class="btn btn-sm btn-danger btn-rounded delete-category">@lang("app.remove")</a>
                              @else
                                  <span class="text-danger">@lang('messages.defaultRoleCantDelete')</span>
                              @endif
                          </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3">@lang('messages.noRoleFound')</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <hr>
        {!! Form::open(['id'=>'createProjectCategory','class'=>'ajax-form','method'=>'POST']) !!}
        <div class="form-body">
            <div class="row">
                <div class="col-xs-12 ">
                    <div class="form-group">
                        <label>@lang('modules.permission.roleName')</label>
                        <input type="text" name="name" id="name" class="form-control">
                    </div>
                </div>
            </div>
        </div>
        <div class="form-actions">
            <button type="button" id="save-category" class="btn btn-success"> <i class="fa fa-check"></i> @lang('app.save')</button>
        </div>
        {!! Form::close() !!}
    </div>
</div>

<script>

    $('.delete-category').click(function () {
        var roleId = $(this).data('role-id');
        var url = "{{ route('admin.role-permission.deleteRole') }}";

        var token = "{{ csrf_token() }}";

        $.easyAjax({
            type: 'POST',
            url: url,
            data: {'_token': token, 'roleId': roleId},
            success: function (response) {
                if (response.status == "success") {
                    $.unblockUI();
//                                    swal("Deleted!", response.message, "success");
                    window.location.reload();
                }
            }
        });
    });

    $('#save-category').click(function () {
        $.easyAjax({
            url: '{{route('admin.role-permission.storeRole')}}',
            container: '#createProjectCategory',
            type: "POST",
            data: $('#createProjectCategory').serialize(),
            success: function (response) {
                if(response.status == 'success'){
                    window.location.reload();
                }
            }
        })
    });
</script>