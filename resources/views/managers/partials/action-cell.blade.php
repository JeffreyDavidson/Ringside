<div class="dropdown">
    <a href="javascript:;" class="btn btn-sm btn-clean btn-icon btn-icon-md" data-toggle="dropdown">
        <i class="flaticon-more-1"></i>
    </a>
    <div class="dropdown-menu dropdown-menu-right">
        <ul class="kt-nav">
            @can('view', $model)
                @viewbutton(['route' => route('managers.show', $model)])
                @endviewbutton
            @endcan
            @can('update', $model)
                @editbutton(['route' => route('managers.edit', $model)])
                @endeditbutton
            @endcan
            @can('delete', $model)
                @deletebutton(['route' => route('managers.destroy', $model)])
                @enddeletebutton
            @endcan
            @can('retire', $model)
                @retirebutton(['route' => route('managers.retire', $model)])
                @endretirebutton
            @endcan
            @can('unretire', $model)
                @unretirebutton(['route' => route('managers.unretire', $model)])
                @endunretirebutton
            @endcan
            @can('activate', $model)
                @activatebutton(['route' => route('managers.activate', $model)])
                @endactivatebutton
            @endcan
            @can('suspend', $model)
                @suspendbutton(['route' => route('managers.suspend', $model)])
                @endsuspendbutton
            @endcan
            @can('reinstate', $model)
                @reinstatebutton(['route' => route('managers.reinstate', $model)])
                @endreinstatebutton
            @endcan
            @can('injure', $model)
                @injurebutton(['route' => route('managers.injure', $model)])
                @endinjurebutton
            @endcan
            @can('recover', $model)
                @recoverbutton(['route' => route('managers.recover', $model)])
                @endrecoverbutton
            @endcan
        </ul>
    </div>
</div>
