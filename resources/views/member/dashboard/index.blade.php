@extends('layouts.member-app')

@section('page-title')
    <div class="row bg-title">
        <!-- .page title -->
        <div class="col-lg-3 col-md-4 col-sm-4 col-xs-12">
            <h4 class="page-title"><i class="{{ $pageIcon }}"></i> {{ $pageTitle }}</h4>
        </div>
        <!-- /.page title -->
        <!-- .breadcrumb -->
        <div class="col-lg-9 col-sm-8 col-md-8 col-xs-12">
            <ol class="breadcrumb">
                <li><a href="{{ route('member.dashboard') }}">@lang('app.menu.home')</a></li>
                <li class="active">{{ $pageTitle }}</li>
            </ol>
        </div>
        <!-- /.breadcrumb -->
    </div>
@endsection

@push('head-script')
    <style>
        .col-in {
            padding: 0 20px !important;

        }

        .fc-event{
            font-size: 10px !important;
        }

    </style>
@endpush

@section('content')

    <div class="row">
        @if(\App\ModuleSetting::checkModule('projects'))
        <div class="col-md-3 col-sm-6">
            <a href="{{ route('member.projects.index') }}">
                <div class="white-box">
                    <div class="col-in row">
                        <h3 class="box-title">@lang('modules.dashboard.totalProjects')</h3>
                        <ul class="list-inline two-part">
                            <li><i class="icon-layers text-info"></i></li>
                            <li class="text-right"><span class="counter">{{ $totalProjects }}</span></li>
                        </ul>
                    </div>
                </div>
            </a>
        </div>
        @endif

        @if(\App\ModuleSetting::checkModule('timelogs'))
        <div class="col-md-3 col-sm-6">
            <a href="{{ route('member.all-time-logs.index') }}">
                <div class="white-box" style="padding-bottom: 32px">
                    <div class="col-in row">
                        <h3 class="box-title">@lang('modules.dashboard.totalHoursLogged')</h3>
                        <ul class="list-inline two-part">
                            <li><i class="icon-clock text-warning"></i></li>
                            <li class="text-right">{{ $counts->totalHoursLogged }}</li>
                        </ul>
                    </div>
                </div>
            </a>
        </div>
        @endif

        @if(\App\ModuleSetting::checkModule('tasks'))
        <div class="col-md-3 col-sm-6">
            <a href="{{ route('member.all-tasks.index') }}">
                <div class="white-box">
                    <div class="col-in row">
                        <h3 class="box-title">@lang('modules.dashboard.totalPendingTasks')</h3>
                        <ul class="list-inline two-part">
                            <li><i class="ti-alert text-danger"></i></li>
                            <li class="text-right"><span class="counter">{{ $counts->totalPendingTasks }}</span></li>
                        </ul>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-3 col-sm-6">
            <a href="{{ route('member.all-tasks.index') }}">
                <div class="white-box">
                    <div class="col-in row">
                        <h3 class="box-title">@lang('modules.dashboard.totalCompletedTasks')</h3>
                        <ul class="list-inline two-part">
                            <li><i class="ti-check-box text-success"></i></li>
                            <li class="text-right"><span class="counter">{{ $counts->totalCompletedTasks }}</span></li>
                        </ul>
                    </div>
                </div>
            </a>
        </div>
        @endif

    </div>
    <!-- .row -->

    <div class="row">

        @if(\App\ModuleSetting::checkModule('attendance'))
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">@lang('app.menu.attendance')</div>
                <div class="panel-wrapper collapse in">
                    <div class="panel-body">
                        @if(!$checkTodayHoliday)
                            @if($todayTotalClockin < $maxAttandenceInDay)
                                <div class="col-xs-6">
                                    <h3>@lang('modules.attendance.clock_in')</h3>
                                </div>
                                <div class="col-xs-6">
                                    <h3>@lang('modules.attendance.clock_in') IP</h3>
                                </div>
                                <div class="col-xs-6">
                                    @if(is_null($currenntClockIn))
                                        {{ \Carbon\Carbon::now()->timezone($global->timezone)->format('h:i A') }}
                                    @else
                                        {{ $currenntClockIn->clock_in_time->timezone($global->timezone)->format('h:i A') }}
                                    @endif
                                </div>
                                <div class="col-xs-6">
                                    {{ $currenntClockIn->clock_in_ip or request()->ip() }}
                                </div>

                                @if(!is_null($currenntClockIn) && !is_null($currenntClockIn->clock_out_time))
                                    <div class="col-xs-6 m-t-20">
                                        <label for="">@lang('modules.attendance.clock_out')</label>
                                        <br>{{ $currenntClockIn->clock_out_time->timezone($global->timezone)->format('h:i A') }}
                                    </div>
                                    <div class="col-xs-6 m-t-20">
                                        <label for="">@lang('modules.attendance.clock_out') IP</label>
                                        <br>{{ $currenntClockIn->clock_out_ip }}
                                    </div>
                                @endif

                                <div class="col-xs-8 m-t-20">
                                    <label for="">@lang('modules.attendance.working_from')</label>
                                    @if(is_null($currenntClockIn))
                                        <input type="text" class="form-control" id="working_from" name="working_from">
                                    @else
                                        <br> {{ $currenntClockIn->working_from }}
                                    @endif
                                </div>

                                <div class="col-xs-4 m-t-20">
                                    <label class="m-t-30">&nbsp;</label>
                                    @if(is_null($currenntClockIn))
                                        <button class="btn btn-success btn-sm" id="clock-in">@lang('modules.attendance.clock_in')</button>
                                    @endif
                                    @if(!is_null($currenntClockIn) && is_null($currenntClockIn->clock_out_time))
                                        <button class="btn btn-danger btn-sm" id="clock-out">@lang('modules.attendance.clock_out')</button>
                                    @endif
                                </div>
                            @else
                                <div class="col-xs-12">
                                    <div class="alert alert-info">@lang('modules.attendance.maxColckIn')</div>
                                </div>
                            @endif
                        @else
                            <div class="col-xs-12">
                                <div class="alert alert-info alert-dismissable">
                                    <b>@lang('modules.dashboard.holidayCheck') {{ ucwords($checkTodayHoliday->occassion) }}.</b> </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if(\App\ModuleSetting::checkModule('tasks'))
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">@lang('modules.dashboard.overdueTasks')</div>
                <div class="panel-wrapper collapse in">
                    <div class="panel-body">
                        <ul class="list-task list-group" data-role="tasklist">
                            <li class="list-group-item" data-role="task">
                                <strong>@lang('app.title')</strong> <span
                                        class="pull-right"><strong>@lang('app.dueDate')</strong></span>
                            </li>
                            @forelse($pendingTasks as $key=>$task)
                                <li class="list-group-item" data-role="task">
                                    {{ ($key+1).'. '.ucfirst($task->heading) }}
                                    @if(!is_null($task->project_id))
                                        <a href="{{ route('member.projects.show', $task->project_id) }}" class="text-danger">{{ ucwords($task->project->project_name) }}</a>
                                    @endif
                                    <label class="label label-danger pull-right">{{ $task->due_date->format('d M') }}</label>
                                </li>
                            @empty
                                <li class="list-group-item" data-role="task">
                                    @lang('messages.noOpenTasks')
                                </li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        @endif

    </div>

    <div class="row" >

        @if(\App\ModuleSetting::checkModule('projects'))
        <div class="col-md-6" id="project-timeline">
            <div class="panel panel-default">
                <div class="panel-heading">@lang('modules.dashboard.projectActivityTimeline')</div>
                <div class="panel-wrapper collapse in">
                    <div class="panel-body">
                        <div class="steamline">
                            @foreach($projectActivities as $activity)
                                <div class="sl-item">
                                    <div class="sl-left"><i class="fa fa-circle text-info"></i>
                                    </div>
                                    <div class="sl-right">
                                        <div><h6><a href="{{ route('member.projects.show', $activity->project_id) }}" class="text-danger">{{ ucwords($activity->project_name) }}:</a> {{ $activity->activity }}</h6> <span class="sl-date">{{ $activity->created_at->timezone($global->timezone)->diffForHumans() }}</span></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if(\App\ModuleSetting::checkModule('employees'))
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">@lang('modules.dashboard.userActivityTimeline')</div>
                <div class="panel-wrapper collapse in">
                    <div class="panel-body">
                        <div class="steamline">
                            @forelse($userActivities as $key=>$activity)
                                <div class="sl-item">
                                    <div class="sl-left">
                                        {!!  ($activity->user->image) ? '<img src="'.asset('user-uploads/avatar/'.$activity->user->image).'"
                                                                    alt="user" class="img-circle">' : '<img src="'.asset('default-profile-2.png').'"
                                                                    alt="user" class="img-circle">' !!}
                                    </div>
                                    <div class="sl-right">
                                        <div class="m-l-40"><a href="{{ route('member.employees.show', $activity->user_id) }}" class="text-success">{{ ucwords($activity->user->name) }}</a> <span  class="sl-date">{{ $activity->created_at->timezone($global->timezone)->diffForHumans() }}</span>
                                            <p>{!! ucfirst($activity->activity) !!}</p>
                                        </div>
                                    </div>
                                </div>
                                @if(count($userActivities) > ($key+1))
                                    <hr>
                                @endif
                            @empty
                                <div>@lang('messages.noActivityByThisUser')</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif



    </div>

@endsection

@push('footer-script')
<script>
    $('#clock-in').click(function () {
        var workingFrom = $('#working_from').val();

        var token = "{{ csrf_token() }}";

        $.easyAjax({
            url: '{{route('member.attendances.store')}}',
            type: "POST",
            data: {
                working_from: workingFrom,
                _token: token
            },
            success: function (response) {
                if(response.status == 'success'){
                    window.location.reload();
                }
            }
        })
    })

    @if(!is_null($currenntClockIn))
    $('#clock-out').click(function () {

        var token = "{{ csrf_token() }}";

        $.easyAjax({
            url: '{{route('member.attendances.update', $currenntClockIn->id)}}',
            type: "PUT",
            data: {
                _token: token
            },
            success: function (response) {
                if(response.status == 'success'){
                    window.location.reload();
                }
            }
        })
    })
    @endif

</script>
@endpush