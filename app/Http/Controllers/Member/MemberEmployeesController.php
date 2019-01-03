<?php

namespace App\Http\Controllers\Member;

use App\EmployeeDetails;
use App\Helper\Reply;
use App\Http\Requests\Member\User\StoreUser;
use App\Http\Requests\Member\User\UpdateEmployee;
use App\ModuleSetting;
use App\Notifications\NewUser;
use App\Project;
use App\ProjectTimeLog;
use App\Role;
use App\Task;
use App\User;
use App\UserActivity;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class MemberEmployeesController extends MemberBaseController
{

    public function __construct() {
        parent::__construct();
        $this->pageTitle = __('app.menu.employees');
        $this->pageIcon = 'icon-user';

        if(!ModuleSetting::checkModule('employees')){
            abort(403);
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        if(!$this->user->can('view_employees')){
            abort(403);
        }
        $this->employees = User::allEmployees();
        return view('member.employees.index', $this->data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {
        if(!$this->user->can('add_employees')){
            abort(403);
        }

        $employee = new EmployeeDetails();
        $this->fields = $employee->getCustomFieldGroupsWithFields()->fields;
        return view('member.employees.create', $this->data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreUser $request) {
        $user = new User();
        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->password = Hash::make($request->input('password'));
        $user->mobile = $request->input('mobile');
        $user->save();

        if ($user->id) {
            $employee = new EmployeeDetails();
            $employee->user_id = $user->id;
            $employee->job_title = $request->job_title;
            $employee->address = $request->address;
            $employee->hourly_rate = $request->hourly_rate;
            $employee->slack_username = $request->slack_username;
            $employee->save();
        }


        // To add custom fields data
        if ($request->get('custom_fields_data')) {
            $employee->updateCustomFieldData($request->get('custom_fields_data'));
        }


        $user->attachRole(2);

        // Notify User
        $user->notify(new NewUser($request->input('password')));

        $this->logSearchEntry($user->id, $user->name, 'admin.employees.show');

        return Reply::redirect(route('member.employees.index'), __('messages.employeeAdded'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) {
        if(!$this->user->can('view_employees')){
            abort(403);
        }

        $this->employee = User::withoutGlobalScope('active')->findOrFail($id);
        $this->taskCompleted = Task::where('user_id', $id)->where('status', 'completed')->count();
        $this->hoursLogged = ProjectTimeLog::where('user_id', $id)->sum('total_hours');
        $this->activities = UserActivity::where('user_id', $id)->orderBy('id', 'desc')->get();
        $this->projects = Project::select('projects.id', 'projects.project_name', 'projects.deadline', 'projects.completion_percent')
            ->join('project_members', 'project_members.project_id', '=', 'projects.id')
            ->where('project_members.user_id', '=', $id)
            ->get();
        return view('member.employees.show', $this->data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {
        if(!$this->user->can('edit_employees')){
            abort(403);
        }

        $this->userDetail = User::withoutGlobalScope('active')->findOrFail($id);
        $this->employeeDetail = EmployeeDetails::where('user_id', '=', $this->userDetail->id)->first();

        if(!is_null($this->employeeDetail)){
            $this->employeeDetail = $this->employeeDetail->withCustomFields();
            $this->fields = $this->employeeDetail->getCustomFieldGroupsWithFields()->fields;
        }

        return view('member.employees.edit', $this->data);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateEmployee $request, $id) {
        $user = User::withoutGlobalScope('active')->findOrFail($id);
        $user->name = $request->input('name');
        $user->email = $request->input('email');
        if ($request->password != '') {
            $user->password = Hash::make($request->input('password'));
        }
        $user->mobile = $request->input('mobile');
        $user->save();

        $employee = EmployeeDetails::where('user_id', '=', $user->id)->first();
        if (empty($employee)) {
            $employee = new EmployeeDetails();
            $employee->user_id = $user->id;
        }
        $employee->job_title = $request->job_title;
        $employee->address = $request->address;
        $employee->hourly_rate = $request->hourly_rate;
        $employee->slack_username = $request->slack_username;
        $employee->save();

        // To add custom fields data
        if ($request->get('custom_fields_data')) {
            $employee->updateCustomFieldData($request->get('custom_fields_data'));
        }

        return Reply::success(__('messages.employeeUpdated'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        $user = User::withoutGlobalScope('active')->findOrFail($id);

        if ($user->id == 1) {
            return Reply::error(__('messages.adminCannotDelete'));
        }

        User::destroy($id);
        return Reply::success(__('messages.employeeDeleted'));
    }

    public function data(Request $request) {
        $users = User::join('role_user', 'role_user.user_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'role_user.role_id')
            ->select('users.id', 'users.name', 'users.email', 'users.created_at')
            ->where('roles.name', 'employee');

        if($request->employee != 'all' && $request->employee != ''){
            $users = $users->where('users.id', $request->employee);
        }

        $users = $users->get();

        return DataTables::of($users)
            ->addColumn('action', function ($row) {
                $action = '';

                if($this->user->can('edit_employees')){
                    $action.= ' <a href="' . route('member.employees.edit', [$row->id]) . '" class="btn btn-info btn-circle"
                      data-toggle="tooltip" data-original-title="Edit"><i class="fa fa-pencil" aria-hidden="true"></i></a>';
                }

                if($this->user->can('view_employees')){
                    $action.= ' <a href="' . route('member.employees.show', [$row->id]) . '" class="btn btn-success btn-circle"
                      data-toggle="tooltip" data-original-title="View Employee Details"><i class="fa fa-search" aria-hidden="true"></i></a>';
                }

                if($this->user->can('delete_employees')){
                    $action.= ' <a href="javascript:;" class="btn btn-danger btn-circle sa-params"
                      data-toggle="tooltip" data-user-id="' . $row->id . '" data-original-title="Delete"><i class="fa fa-times" aria-hidden="true"></i></a>';
                }

                return $action;

            })
            ->editColumn(
                'created_at',
                function ($row) {
                    return Carbon::parse($row->created_at)->format('d F, Y');
                }
            )
            ->editColumn('name', function ($row) {
                if ($row->hasRole('admin')) {
                    return '<a href="' . route('member.employees.show', $row->id) . '">' . ucwords($row->name) . '</a><br> <label class="label label-danger">admin</label>';
                }
                if ($row->hasRole('project_admin')) {
                    return '<a href="' . route('member.employees.show', $row->id) . '">' . ucwords($row->name) . '</a><br> <label class="label label-info">project admin</label>';
                }
                return '<a href="' . route('member.employees.show', $row->id) . '">' . ucwords($row->name) . '</a>';
            })
            ->rawColumns(['name', 'action'])
            ->make(true);
    }

    public function tasks($userId, $hideCompleted) {
        $tasks = Task::join('projects', 'projects.id', '=', 'tasks.project_id')
            ->select('tasks.id', 'projects.project_name', 'tasks.heading', 'tasks.due_date', 'tasks.status', 'tasks.project_id')
            ->where('tasks.user_id', $userId);

        if ($hideCompleted == '1') {
            $tasks->where('tasks.status', '=', 'incomplete');
        }

        $tasks->get();

        return DataTables::of($tasks)
            ->editColumn('due_date', function ($row) {
                if ($row->due_date->isPast()) {
                    return '<span class="text-danger">' . $row->due_date->format('d M, y') . '</span>';
                }
                return '<span class="text-success">' . $row->due_date->format('d M, y') . '</span>';
            })
            ->editColumn('heading', function ($row) {
                return ucfirst($row->heading);
            })
            ->editColumn('status', function ($row) {
                if ($row->status == 'incomplete') {
                    return '<label class="label label-danger">Incomplete</label>';
                }
                return '<label class="label label-success">Completed</label>';
            })
            ->editColumn('project_name', function ($row) {
                return '<a href="' . route('admin.projects.show', $row->project_id) . '">' . ucfirst($row->project_name) . '</a>';
            })
            ->rawColumns(['status', 'project_name', 'due_date'])
            ->removeColumn('project_id')
            ->make(true);
    }

    public function timeLogs($userId) {
        $timeLogs = ProjectTimeLog::join('projects', 'projects.id', '=', 'project_time_logs.project_id')
            ->select('project_time_logs.id', 'projects.project_name', 'project_time_logs.start_time', 'project_time_logs.end_time', 'project_time_logs.total_hours', 'project_time_logs.memo', 'project_time_logs.project_id')
            ->where('project_time_logs.user_id', $userId);
        $timeLogs->get();

        return DataTables::of($timeLogs)
            ->editColumn('start_time', function ($row) {
                return $row->start_time->format('d M, Y h:i A');
            })
            ->editColumn('end_time', function ($row) {
                if (!is_null($row->end_time)) {
                    return $row->end_time->format('d M, Y h:i A');
                }
                else {
                    return "<label class='label label-success'>Active</label>";
                }
            })
            ->editColumn('project_name', function ($row) {
                return '<a href="' . route('admin.projects.show', $row->project_id) . '">' . ucfirst($row->project_name) . '</a>';
            })
            ->rawColumns(['end_time', 'project_name'])
            ->removeColumn('project_id')
            ->make(true);
    }

    public function export() {
        $rows = User::join('role_user', 'role_user.user_id', '=', 'users.id')
            ->withoutGlobalScope('active')
            ->join('roles', 'roles.id', '=', 'role_user.role_id')
            ->where('roles.name', '<>', 'client')
            ->leftJoin('employee_details', 'users.id', '=', 'employee_details.user_id')
            ->select(
                'users.id',
                'users.name',
                'users.email',
                'users.mobile',
                'employee_details.job_title',
                'employee_details.address',
                'employee_details.hourly_rate',
                'users.created_at'
            )
            ->groupBy('users.id')
            ->get();

        // Initialize the array which will be passed into the Excel
        // generator.
        $exportArray = [];

        // Define the Excel spreadsheet headers
        $exportArray[] = ['ID', 'Name', 'Email', 'Mobile', 'Job Title', 'Address', 'Hourly Rate', 'Created at'];

        // Convert each member of the returned collection into an array,
        // and append it to the payments array.
        foreach ($rows as $row) {
            $exportArray[] = $row->toArray();
        }

        // Generate and return the spreadsheet
        Excel::create('Employees', function ($excel) use ($exportArray) {

            // Set the spreadsheet title, creator, and description
            $excel->setTitle('Employees');
            $excel->setCreator('Worksuite')->setCompany($this->companyName);
            $excel->setDescription('Employees file');

            // Build the spreadsheet, passing in the payments array
            $excel->sheet('sheet1', function ($sheet) use ($exportArray) {
                $sheet->fromArray($exportArray, null, 'A1', false, false);

                $sheet->row(1, function ($row) {

                    // call row manipulation methods
                    $row->setFont(array(
                        'bold' => true
                    ));

                });

            });


        })->download('xlsx');
    }

    public function assignRole(Request $request) {
        $userId = $request->userId;
        $roleName = $request->role;
        $adminRole = Role::where('name', 'admin')->first();
        $projectAdminRole = Role::where('name', 'project_admin')->first();
        $employeeRole = Role::where('name', 'employee')->first();
        $user = User::findOrFail($userId);

        switch ($roleName) {
            case "admin"        :
                $user->detachRoles($user->roles);
                $user->roles()->attach($adminRole->id);
                $user->roles()->attach($employeeRole->id);
                break;

            case "project_admin" :
                $user->detachRoles($user->roles);
                $user->roles()->attach($projectAdminRole->id);
                $user->roles()->attach($employeeRole->id);
                break;

            case "none"         :
                $user->detachRoles($user->roles);
                $user->roles()->attach($employeeRole->id);
                break;
        }
        return Reply::success(__('messages.roleAssigned'));
    }

    public function assignProjectAdmin(Request $request) {
        $userId = $request->userId;
        $projectId = $request->projectId;
        $project = Project::findOrFail($projectId);
        $project->project_admin = $userId;
        $project->save();

        return Reply::success(__('messages.roleAssigned'));
    }

}
