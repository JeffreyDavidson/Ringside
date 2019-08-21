<div class="dropdown">
    <a href="javascript:;" class="btn btn-sm btn-clean btn-icon btn-icon-md" data-toggle="dropdown">
        <i class="flaticon-more-1"></i>
    </a>
    <div class="dropdown-menu dropdown-menu-right">
        <ul class="kt-nav">
            @can('view', $model)
                @viewbutton(['route' => route('referees.show', $model)])
                @endviewbutton
            @endcan
            @can('update', $model)
                @editbutton(['route' => route('referees.edit', $model)])
                @endeditbutton
            @endcan
            @can('delete', $model)
                @deletebutton(['route' => route('referees.destroy', $model)])
                @enddeletebutton
            @endcan
            @can('retire', $model)
                @retirebutton(['route' => route('referees.retire', $model)])
                @endretirebutton
            @endcan
            @can('unretire', $model)
                @unretirebutton(['route' => route('referees.unretire', $model)])
                @endunretirebutton
            @endcan
            @can('activate', $model)
                @viewbutton(['route' => route('referees.activate', $model)])
                @endviewbutton
            @endcan
            @can('suspend', $model)
                @suspendbutton(['route' => route('referees.suspend', $model)])
                @endsuspendbutton
            @endcan
            @can('reinstate', $model)
                @reinstatebutton(['route' => route('referees.reinstate', $model)])
                @endreinstatebutton
            @endcan
            @can('injure', $model)
                @injurebutton(['route' => route('referees.injure', $model)])
                @endinjurebutton
            @endcan
            @can('recover', $model)
                @recoverbutton(['route' => route('referees.recover', $model)])
                @endrecoverbutton
            @endcan
        </ul>
    </div>
</div>
